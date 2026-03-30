<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Thiết bị";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
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

// layout chung
include 'layout/header.php';
include 'layout/sidebar.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_maintenance_id'])) {
  checkPermission('MANAGE_SALES', 'edit');

  $maintenanceId = intval($_POST['edit_maintenance_id']);
  $equipmentId = intval($_POST['edit_equipment_id']);
  $maintenanceDate = sanitize($_POST['edit_maintenance_date']);
  $description = sanitize($_POST['edit_description']);

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') {
  $whereClauses[] = 'name LIKE ?';
  $whereParams[] = '%' . $filterName . '%';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_id'])) {
  checkPermission('MANAGE_SALES', 'add');

  $equipmentId = intval($_POST['equipment_id']);
  $maintenanceDate = sanitize($_POST['maintenance_date']);
  $description = sanitize($_POST['description']);

  $checkStmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE id = ?");
  $checkStmt->execute([$equipmentId]);

  if ((int) $checkStmt->fetchColumn() === 0) {
    echo "<script>alert('Thiết bị không tồn tại!');window.location='equipment-maintenance.php';</script>";
    exit;
  }

  $insertStmt = $db->prepare("INSERT INTO equipment_maintenance (equipment_id, maintenance_date, description) VALUES (?, ?, ?)");

  if ($insertStmt->execute([$equipmentId, $maintenanceDate, $description])) {
    $updateStatusStmt = $db->prepare("UPDATE equipment SET status = 'bao tri' WHERE id = ?");
    $updateStatusStmt->execute([$equipmentId]);
    echo "<script>alert('Thêm lịch bảo trì thành công!');window.location='equipment-maintenance.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi thêm lịch bảo trì!');window.location='equipment-maintenance.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_maintenance_id'])) {
  checkPermission('MANAGE_SALES', 'delete');

  $maintenanceId = intval($_POST['delete_maintenance_id']);
  $deleteStmt = $db->prepare("DELETE FROM equipment_maintenance WHERE id = ?");

  if ($deleteStmt->execute([$maintenanceId])) {
    echo "<script>alert('Xóa lịch bảo trì thành công!');window.location='equipment-maintenance.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi xóa lịch bảo trì!');window.location='equipment-maintenance.php';</script>";
  }
  exit;
}

$equipmentStmt = $db->query("SELECT id, name FROM equipment ORDER BY name ASC");
$equipments = $equipmentStmt->fetchAll();

$maintenanceStmt = $db->query("SELECT em.id, em.equipment_id, e.name AS equipment_name, em.maintenance_date, em.description FROM equipment_maintenance em INNER JOIN equipment e ON e.id = em.equipment_id ORDER BY em.maintenance_date DESC, em.id DESC");
$maintenances = $maintenanceStmt->fetchAll();

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
                  require_once '../includes/functions.php';
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
                      <?php elseif ($equipment['status'] === 'bao tri'): ?>
                        <span class="badge badge-warning">Bảo trì</span>
                      <?php elseif ($equipment['status'] === 'ngung hoat dong'): ?>
                        <span class="badge badge-danger">Ngừng hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-secondary"><?= htmlspecialchars($equipment['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <?php if ($canEditEquipment): ?>
                      <button class="btn btn-warning btn-sm edit-equipment-btn"
                        data-id="<?= $equipment['id'] ?>"
                        data-name="<?= htmlspecialchars($equipment['name']) ?>"
                        data-quantity="<?= $equipment['quantity'] ?>"
                        data-status="<?= $equipment['status'] ?>">
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
          <div class="form-group">
            <label for="edit_equipment_status">Tình trạng</label>
            <select class="form-control" id="edit_equipment_status" name="edit_equipment_status">
              <option value="dang su dung">Đang sử dụng</option>
              <option value="bao tri">Bảo trì</option>
              <option value="ngung hoat dong">Ngừng hoạt động</option>
            </select>
            <small class="text-danger d-none validation-error"></small>
          </div>
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
  var validStatuses = ['dang su dung', 'bao tri', 'ngung hoat dong'];

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
    var $status = $form.find('select[name="equipment_status"], select[name="edit_equipment_status"]');

    $form.find('.form-control').each(function() {
      clearFieldError($(this));
    });

    if (!$name.val().trim()) {
      setFieldError($name, 'Vui lòng nhập tên thiết bị.');
      isValid = false;
    }

    if (!$quantity.val().trim()) {
      setFieldError($quantity, 'Vui lòng nhập số lượng.');
      isValid = false;
    } else if (Number($quantity.val()) < 1) {
      setFieldError($quantity, 'Số lượng phải lớn hơn hoặc bằng 1.');
      isValid = false;
    }

    if (!validStatuses.includes($status.val())) {
      setFieldError($status, 'Vui lòng chọn tình trạng hợp lệ.');
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
    clearFieldError($(this));
  });

  $('.edit-equipment-btn').on('click', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var quantity = $(this).data('quantity');
    var status = $(this).data('status');
    $('#edit_equipment_id').val(id);
    $('#edit_equipment_name').val(name);
    $('#edit_equipment_quantity').val(quantity);
    $('#edit_equipment_status').val(status);
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

<!-- Handle sửa thiết bị (PHP) -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_equipment_id'])) {
    if (!$canEditEquipment) {
      echo "<script>alert('Bạn không có quyền này');window.location='no_permission.php';</script>";
      exit;
    }

    require_once '../includes/functions.php';
    $id = intval($_POST['edit_equipment_id']);
    $name = sanitize($_POST['edit_equipment_name']);
    $quantity = intval($_POST['edit_equipment_quantity']);
    $status = sanitize($_POST['edit_equipment_status']);
  $validStatuses = ['dang su dung', 'bao tri', 'ngung hoat dong'];
  if (!in_array($status, $validStatuses, true)) {
    $status = 'dang su dung';
  }
    $db = getDB();
    $stmt = $db->prepare("UPDATE equipment SET name=?, quantity=?, status=? WHERE id=?");
    if ($stmt->execute([$name, $quantity, $status, $id])) {
        echo "<script>alert('Cập nhật thiết bị thành công!');window.location='equipment.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi cập nhật thiết bị!');</script>";
    }
}
?>

<!-- Handle xóa thiết bị (PHP) -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_equipment_id'])) {
    if (!$canDeleteEquipment) {
      echo "<script>alert('Bạn không có quyền này');window.location='no_permission.php';</script>";
      exit;
    }

    require_once '../includes/functions.php';
    $id = intval($_POST['delete_equipment_id']);
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM equipment WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo "<script>alert('Xóa thiết bị thành công!');window.location='equipment.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa thiết bị!');</script>";
    }
}
?>

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
          <div class="form-group">
            <label for="equipment_status">Tình trạng</label>
            <select class="form-control" id="equipment_status" name="equipment_status">
              <option value="dang su dung">Đang sử dụng</option>
              <option value="bao tri">Bảo trì</option>
              <option value="ngung hoat dong">Ngừng hoạt động</option>
            </select>
            <small class="text-danger d-none validation-error"></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Thêm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Handle thêm thiết bị (PHP) -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_name'])) {
    if (!$canAddEquipment) {
      echo "<script>alert('Bạn không có quyền này');window.location='no_permission.php';</script>";
      exit;
    }

    require_once '../includes/functions.php';
    $name = sanitize($_POST['equipment_name']);
    $quantity = intval($_POST['equipment_quantity']);
    $status = sanitize($_POST['equipment_status']);
  $validStatuses = ['dang su dung', 'bao tri', 'ngung hoat dong'];
  if (!in_array($status, $validStatuses, true)) {
    $status = 'dang su dung';
  }
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO equipment (name, quantity, status) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $quantity, $status])) {
        echo "<script>alert('Thêm thiết bị thành công!');window.location='equipment.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi thêm thiết bị!');</script>";
    }
}
?>