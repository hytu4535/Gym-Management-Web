<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Thiết bị";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/functions.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_EQUIPMENT
checkPermission('MANAGE_EQUIPMENT');

$permissions = $_SESSION['permissions'] ?? [];
$hasManageAll = in_array('MANAGE_ALL', $permissions, true);
$equipmentActionSet = $_SESSION['user_action_permissions']['MANAGE_EQUIPMENT'] ?? null;

if ($hasManageAll) {
  $canAddEquipment = true;
  $canEditEquipment = true;
  $canDeleteEquipment = true;
} elseif (is_array($equipmentActionSet)) {
  $canAddEquipment = !empty($equipmentActionSet['add']);
  $canEditEquipment = !empty($equipmentActionSet['edit']);
  $canDeleteEquipment = !empty($equipmentActionSet['delete']);
} else {
  $legacyManageEquipment = in_array('MANAGE_EQUIPMENT', $permissions, true);
  $canAddEquipment = $legacyManageEquipment;
  $canEditEquipment = $legacyManageEquipment;
  $canDeleteEquipment = $legacyManageEquipment;
}

function setEquipmentFlash($type, $message) {
  $_SESSION['equipment_flash'] = [
    'type' => $type,
    'message' => $message
  ];
}

function tableHasColumn(PDO $db, $tableName, $columnName) {
  // MySQL does not allow binding identifiers, so validate before interpolation.
  if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $tableName) || !preg_match('/^[A-Za-z0-9_]+$/', (string) $columnName)) {
    return false;
  }

  // Some MySQL/PDO configurations don't support placeholders in SHOW statements.
  $quotedColumn = $db->quote($columnName);
  $query = "SHOW COLUMNS FROM `{$tableName}` LIKE {$quotedColumn}";
  $stmt = $db->query($query);
  return $stmt ? (bool) $stmt->fetch(PDO::FETCH_ASSOC) : false;
}

function equipmentNameExists(PDO $db, $name, $excludeId = null) {
  if ($excludeId !== null) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE name = ? AND id <> ?");
    $stmt->execute([$name, (int) $excludeId]);
  } else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE name = ?");
    $stmt->execute([$name]);
  }

  return (int) $stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_equipment_id'])) {
  if (!$canEditEquipment) {
    header('Location: no_permission.php');
    exit;
  }

  $id = intval($_POST['edit_equipment_id']);
  $name = sanitize($_POST['edit_equipment_name']);
  $quantity = intval($_POST['edit_equipment_quantity']);

  if ($name === '' || $quantity < 1) {
    setEquipmentFlash('error', 'Dữ liệu thiết bị không hợp lệ!');
    header('Location: equipment.php');
    exit;
  }

  $db = getDB();

  if (equipmentNameExists($db, $name, $id)) {
    setEquipmentFlash('error', 'Tên thiết bị đã tồn tại, vui lòng nhập tên khác!');
    header('Location: equipment.php');
    exit;
  }

  $stmt = $db->prepare("UPDATE equipment SET name = ?, quantity = ? WHERE id = ?");
  if ($stmt->execute([$name, $quantity, $id])) {
    setEquipmentFlash('success', 'Cập nhật thiết bị thành công!');
  } else {
    setEquipmentFlash('error', 'Lỗi khi cập nhật thiết bị!');
  }

  header('Location: equipment.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_equipment_id'])) {
  if (!$canDeleteEquipment) {
    header('Location: no_permission.php');
    exit;
  }

  $id = intval($_POST['delete_equipment_id']);
  $db = getDB();

  if (tableHasColumn($db, 'equipment_maintenance', 'status')) {
    $openMaintenanceStmt = $db->prepare("SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ? AND status IN ('cho_bao_tri', 'dang_bao_tri')");
    $openMaintenanceStmt->execute([$id]);
  } else {
    // Backward-compatible fallback for old schema without maintenance status column.
    $openMaintenanceStmt = $db->prepare("SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ?");
    $openMaintenanceStmt->execute([$id]);
  }
  $hasOpenMaintenance = (int) $openMaintenanceStmt->fetchColumn() > 0;

  if ($hasOpenMaintenance) {
    setEquipmentFlash('error', 'Thiết bị đang có phiếu bảo trì mở, không thể xóa!');
    header('Location: equipment.php');
    exit;
  }

  $stmt = $db->prepare("DELETE FROM equipment WHERE id = ?");
  if ($stmt->execute([$id])) {
    setEquipmentFlash('success', 'Xóa thiết bị thành công!');
  } else {
    setEquipmentFlash('error', 'Lỗi khi xóa thiết bị!');
  }

  header('Location: equipment.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_name'])) {
  if (!$canAddEquipment) {
    header('Location: no_permission.php');
    exit;
  }

  $name = sanitize($_POST['equipment_name']);
  $quantity = intval($_POST['equipment_quantity']);

  if ($name === '' || $quantity < 1) {
    setEquipmentFlash('error', 'Dữ liệu thiết bị không hợp lệ!');
    header('Location: equipment.php');
    exit;
  }

  $db = getDB();

  if (equipmentNameExists($db, $name)) {
    setEquipmentFlash('error', 'Tên thiết bị đã tồn tại, vui lòng nhập tên khác!');
    header('Location: equipment.php');
    exit;
  }

  $stmt = $db->prepare("INSERT INTO equipment (name, quantity, status) VALUES (?, ?, 'dang su dung')");
  if ($stmt->execute([$name, $quantity])) {
    setEquipmentFlash('success', 'Thêm thiết bị thành công!');
  } else {
    setEquipmentFlash('error', 'Lỗi khi thêm thiết bị!');
  }

  header('Location: equipment.php');
  exit;
}

// layout chung
include 'layout/header.php';
include 'layout/sidebar.php';

$db = getDB();

$filterName = trim((string) ($_GET['name'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') {
  $whereClauses[] = 'name LIKE ?';
  $whereParams[] = '%' . $filterName . '%';
}
if ($filterStatus !== '') {
  if ($filterStatus === 'bao tri') {
    // Backward-compatible: include old status literals if they still exist in DB.
    $whereClauses[] = "status IN ('bao tri', 'dang bao tri', 'cho bao tri')";
  } else {
    $whereClauses[] = 'status = ?';
    $whereParams[] = $filterStatus;
  }
}
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$allEquipmentNameStmt = $db->query("SELECT id, name FROM equipment");
$allEquipmentNames = $allEquipmentNameStmt ? $allEquipmentNameStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$equipmentFlash = $_SESSION['equipment_flash'] ?? null;
unset($_SESSION['equipment_flash']);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý thiết bị</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Equipment</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php if (is_array($equipmentFlash) && !empty($equipmentFlash['message'])): ?>
          <div class="alert alert-<?= ($equipmentFlash['type'] ?? 'success') === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars((string) $equipmentFlash['message']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>
        <?php
          $filterMode = 'server';
          $filterAction = 'equipment.php';
          $filterFieldsHtml = '
            <div class="col-md-6">
              <div class="form-group mb-0">
                <label>Tên thiết bị</label>
                <input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Nhập tên thiết bị">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-0">
                <label>Tình trạng</label>
                <select name="status" class="form-control">
                  <option value="">-- Tất cả tình trạng --</option>
                  <option value="dang su dung" ' . ($filterStatus === 'dang su dung' ? 'selected' : '') . '>Đang sử dụng</option>
                  <option value="bao tri" ' . ($filterStatus === 'bao tri' ? 'selected' : '') . '>Bảo trì</option>
                  <option value="ngung hoat dong" ' . ($filterStatus === 'ngung hoat dong' ? 'selected' : '') . '>Ngừng hoạt động</option>
                </select>
              </div>
            </div>
          ';
          include 'layout/filter-card.php';
        ?>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách thiết bị</h3>
                <div class="card-tools">
                  <?php if ($canAddEquipment): ?>
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addEquipmentModal">
                    <i class="fas fa-plus"></i> Thêm thiết bị
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên thiết bị</th>
                    <th>Số lượng</th>
                    <th>Tình trạng</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $db = getDB();
                  $stmt = $db->prepare("SELECT * FROM equipment" . $whereSql . " ORDER BY id DESC");
                  $stmt->execute($whereParams);
                  $equipments = $stmt->fetchAll();
                  foreach ($equipments as $equipment): ?>
                  <tr>
                    <td><?= $equipment['id'] ?></td>
                    <td><?= htmlspecialchars($equipment['name']) ?></td>
                    <td><?= $equipment['quantity'] ?></td>
                    <td>
                      <?php if ($equipment['status'] === 'dang su dung'): ?>
                        <span class="badge badge-success">Đang sử dụng</span>
                      <?php elseif (in_array($equipment['status'], ['bao tri', 'dang bao tri', 'cho bao tri'], true)): ?>
                        <span class="badge badge-warning">Bảo trì</span>
                      <?php elseif ($equipment['status'] === 'ngung hoat dong'): ?>
                        <span class="badge badge-danger">Ngừng hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-secondary"><?= htmlspecialchars($equipment['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($canEditEquipment): ?>
                      <button class="btn btn-warning btn-sm edit-equipment-btn"
                        data-id="<?= $equipment['id'] ?>"
                        data-name="<?= htmlspecialchars($equipment['name']) ?>"
                        data-quantity="<?= $equipment['quantity'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <?php endif; ?>
                      <?php if ($canDeleteEquipment): ?>
                      <button class="btn btn-danger btn-sm delete-equipment-btn"
                        data-id="<?= $equipment['id'] ?>"
                        data-name="<?= htmlspecialchars($equipment['name']) ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php include 'layout/footer.php'; ?>

<!-- Modal Sửa Thiết Bị -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editEquipmentForm" method="POST" action="equipment.php" novalidate>
        <input type="hidden" name="edit_equipment_id" id="edit_equipment_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editEquipmentModalLabel">Sửa Thiết Bị</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_equipment_name">Tên thiết bị</label>
            <input type="text" class="form-control" id="edit_equipment_name" name="edit_equipment_name">
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="edit_equipment_quantity">Số lượng</label>
            <input type="number" class="form-control" id="edit_equipment_quantity" name="edit_equipment_quantity" min="1">
            <small class="text-danger d-none validation-error"></small>
          </div>
          <p class="text-muted mb-0">Trạng thái bảo trì được đồng bộ tự động từ trang bảo trì thiết bị.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Xóa Thiết Bị -->
<div class="modal fade" id="deleteEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteEquipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="deleteEquipmentForm" method="POST" action="equipment.php">
        <input type="hidden" name="delete_equipment_id" id="delete_equipment_id">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteEquipmentModalLabel">Xóa Thiết Bị</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa thiết bị <strong id="delete_equipment_name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger">Xóa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  var equipmentFlash = <?php echo json_encode($equipmentFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  var equipmentNameRegistry = <?php echo json_encode($allEquipmentNames, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

  if (equipmentFlash && equipmentFlash.message) {
    if (equipmentFlash.type === 'success' && typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: equipmentFlash.message,
        timer: 1800,
        showConfirmButton: false
      });
    }
  }

  function normalizeName(value) {
    return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
  }

  function isDuplicateName(name, excludeId) {
    var normalizedInput = normalizeName(name);
    if (!normalizedInput) {
      return false;
    }

    return equipmentNameRegistry.some(function(item) {
      if (!item || typeof item.name === 'undefined') {
        return false;
      }

      var itemId = parseInt(item.id, 10);
      if (excludeId !== null && !Number.isNaN(itemId) && itemId === excludeId) {
        return false;
      }

      return normalizeName(item.name) === normalizedInput;
    });
  }

  function setFieldError($field, message) {
    var $error = $field.closest('.form-group').find('.validation-error');
    $field.addClass('is-invalid');
    $error.text(message).removeClass('d-none');
  }

  function clearFieldError($field) {
    var $error = $field.closest('.form-group').find('.validation-error');
    $field.removeClass('is-invalid');
    $error.text('').addClass('d-none');
  }

  function validateEquipmentForm($form) {
    var isValid = true;
    var $name = $form.find('input[name="equipment_name"], input[name="edit_equipment_name"]');
    var $quantity = $form.find('input[name="equipment_quantity"], input[name="edit_equipment_quantity"]');
    var excludeId = null;

    if ($form.attr('id') === 'editEquipmentForm') {
      excludeId = parseInt($form.find('input[name="edit_equipment_id"]').val(), 10);
      if (Number.isNaN(excludeId)) {
        excludeId = null;
      }
    }

    $form.find('.form-control').each(function() {
      clearFieldError($(this));
    });

    if (!$name.val().trim()) {
      setFieldError($name, 'Vui lòng nhập tên thiết bị.');
      isValid = false;
    } else if (isDuplicateName($name.val(), excludeId)) {
      setFieldError($name, 'Tên thiết bị đã tồn tại. Vui lòng nhập tên khác.');
      isValid = false;
    }

    if (!$quantity.val().trim()) {
      setFieldError($quantity, 'Vui lòng nhập số lượng.');
      isValid = false;
    } else if (Number($quantity.val()) < 1) {
      setFieldError($quantity, 'Số lượng phải lớn hơn hoặc bằng 1.');
      isValid = false;
    }

    return isValid;
  }

  $('#addEquipmentForm, #editEquipmentForm').on('submit', function(e) {
    if (!validateEquipmentForm($(this))) {
      e.preventDefault();
    }
  });

  $('#addEquipmentForm, #editEquipmentForm').find('.form-control').on('input change', function() {
    var $field = $(this);
    clearFieldError($field);

    if ($field.is('input[name="equipment_name"], input[name="edit_equipment_name"]')) {
      var $form = $field.closest('form');
      var excludeId = null;
      if ($form.attr('id') === 'editEquipmentForm') {
        excludeId = parseInt($form.find('input[name="edit_equipment_id"]').val(), 10);
        if (Number.isNaN(excludeId)) {
          excludeId = null;
        }
      }

      if ($field.val().trim() && isDuplicateName($field.val(), excludeId)) {
        setFieldError($field, 'Tên thiết bị đã tồn tại. Vui lòng nhập tên khác.');
      }
    }
  });

  $('.edit-equipment-btn').on('click', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var quantity = $(this).data('quantity');
    $('#edit_equipment_id').val(id);
    $('#edit_equipment_name').val(name);
    $('#edit_equipment_quantity').val(quantity);
    $('#editEquipmentModal').modal('show');
  });
  $('.delete-equipment-btn').on('click', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    $('#delete_equipment_id').val(id);
    $('#delete_equipment_name').text(name);
    $('#deleteEquipmentModal').modal('show');
  });

  $('#addEquipmentModal, #editEquipmentModal').on('hidden.bs.modal', function() {
    $(this).find('.form-control').each(function() {
      clearFieldError($(this));
    });
  });
});
</script>

<!-- Modal Thêm Thiết Bị -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addEquipmentForm" method="POST" action="equipment.php" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="addEquipmentModalLabel">Thêm Thiết Bị</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="equipment_name">Tên thiết bị</label>
            <input type="text" class="form-control" id="equipment_name" name="equipment_name">
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="equipment_quantity">Số lượng</label>
            <input type="number" class="form-control" id="equipment_quantity" name="equipment_quantity" min="1">
            <small class="text-danger d-none validation-error"></small>
          </div>
          <p class="text-muted mb-0">Thiết bị mới sẽ mặc định ở trạng thái Đang sử dụng.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Thêm</button>
        </div>
      </form>
    </div>
  </div>
</div>