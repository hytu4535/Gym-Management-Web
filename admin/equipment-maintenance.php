<?php
session_start(); // luôn khởi tạo session

$page_title = "Bảo trì thiết bị";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_EQUIPMENT
checkPermission('MANAGE_EQUIPMENT');

require_once '../config/db.php';
require_once '../includes/functions.php';

$db = getDB();

function tableHasColumn(PDO $db, $tableName, $columnName) {
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
    return false;
  }

  $table = "`" . $tableName . "`";
  $column = $db->quote($columnName);
  $sql = "SHOW COLUMNS FROM {$table} LIKE {$column}";
  $stmt = $db->query($sql);
  return $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

$maintenanceHasStatus = tableHasColumn($db, 'equipment_maintenance', 'status');

function setMaintenanceFlash($type, $message) {
  $_SESSION['maintenance_flash'] = [
    'type' => $type,
    'message' => $message
  ];
}

function normalizeMaintenanceStatus($status) {
  $allowed = ['cho_bao_tri', 'dang_bao_tri', 'hoan_thanh', 'huy'];
  return in_array($status, $allowed, true) ? $status : 'dang_bao_tri';
}

function inferOpenMaintenanceStatusByDate($maintenanceDate) {
  $value = (string) $maintenanceDate;
  $date = DateTime::createFromFormat('Y-m-d', $value);
  if (!$date || $date->format('Y-m-d') !== $value) {
    return 'dang_bao_tri';
  }

  $today = (new DateTime('today'))->format('Y-m-d');
  return $value > $today ? 'cho_bao_tri' : 'dang_bao_tri';
}

function resolveMaintenanceStatusFromInput($requestedStatus, $maintenanceDate) {
  $normalized = normalizeMaintenanceStatus($requestedStatus);
  if (in_array($normalized, ['hoan_thanh', 'huy'], true)) {
    return $normalized;
  }

  // Open statuses are derived from maintenance date to keep business rules consistent.
  return inferOpenMaintenanceStatusByDate($maintenanceDate);
}

function syncEquipmentStatus(PDO $db, $equipmentId, $maintenanceHasStatus) {
  if ($maintenanceHasStatus) {
    $openStmt = $db->prepare("SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ? AND status IN ('cho_bao_tri', 'dang_bao_tri')");
    $openStmt->execute([$equipmentId]);
  } else {
    $openStmt = $db->prepare("SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ?");
    $openStmt->execute([$equipmentId]);
  }
  $openCount = (int) $openStmt->fetchColumn();

  $newStatus = $openCount > 0 ? 'bao tri' : 'dang su dung';
  $updateStmt = $db->prepare("UPDATE equipment SET status = ? WHERE id = ?");
  $updateStmt->execute([$newStatus, $equipmentId]);
}

function hasOpenMaintenanceForEquipment(PDO $db, $equipmentId, $maintenanceHasStatus, $excludeMaintenanceId = null) {
  $equipmentId = (int) $equipmentId;
  if ($equipmentId <= 0) {
    return false;
  }

  $params = [$equipmentId];

  if ($maintenanceHasStatus) {
    $sql = "SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ? AND status IN ('cho_bao_tri', 'dang_bao_tri')";
  } else {
    $sql = "SELECT COUNT(*) FROM equipment_maintenance WHERE equipment_id = ?";
  }

  if ($excludeMaintenanceId !== null) {
    $sql .= " AND id <> ?";
    $params[] = (int) $excludeMaintenanceId;
  }

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return (int) $stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_maintenance_id'])) {
  checkPermission('MANAGE_EQUIPMENT', 'edit');

  $maintenanceId = intval($_POST['edit_maintenance_id']);
  $maintenanceDate = sanitize($_POST['edit_maintenance_date']);
  $description = sanitize($_POST['edit_description']);
  $status = resolveMaintenanceStatusFromInput(sanitize($_POST['edit_status'] ?? 'dang_bao_tri'), $maintenanceDate);

  $recordStmt = $db->prepare("SELECT equipment_id FROM equipment_maintenance WHERE id = ?");
  $recordStmt->execute([$maintenanceId]);
  $equipmentId = (int) $recordStmt->fetchColumn();

  if ($equipmentId <= 0) {
    setMaintenanceFlash('danger', 'Phiếu bảo trì không tồn tại!');
    header('Location: equipment-maintenance.php');
    exit;
  }

  if (in_array($status, ['cho_bao_tri', 'dang_bao_tri'], true)
      && hasOpenMaintenanceForEquipment($db, $equipmentId, $maintenanceHasStatus, $maintenanceId)) {
    setMaintenanceFlash('danger', 'Thiết bị này đã có một phiếu đang mở (chờ/đang bảo trì), không thể tạo trùng.');
    header('Location: equipment-maintenance.php');
    exit;
  }

  if ($maintenanceHasStatus) {
    $updateStmt = $db->prepare("UPDATE equipment_maintenance SET maintenance_date = ?, description = ?, status = ? WHERE id = ?");
    $result = $updateStmt->execute([$maintenanceDate, $description, $status, $maintenanceId]);
  } else {
    $updateStmt = $db->prepare("UPDATE equipment_maintenance SET maintenance_date = ?, description = ? WHERE id = ?");
    $result = $updateStmt->execute([$maintenanceDate, $description, $maintenanceId]);
  }

  if ($result) {
    syncEquipmentStatus($db, $equipmentId, $maintenanceHasStatus);
    setMaintenanceFlash('success', 'Cập nhật lịch bảo trì thành công!');
  } else {
    setMaintenanceFlash('danger', 'Lỗi khi cập nhật lịch bảo trì!');
  }
  header('Location: equipment-maintenance.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_id'])) {
  checkPermission('MANAGE_EQUIPMENT', 'add');

  $equipmentId = intval($_POST['equipment_id']);
  $maintenanceDate = sanitize($_POST['maintenance_date']);
  $description = sanitize($_POST['description']);
  $status = inferOpenMaintenanceStatusByDate($maintenanceDate);

  $checkStmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE id = ?");
  $checkStmt->execute([$equipmentId]);

  if ((int) $checkStmt->fetchColumn() === 0) {
    setMaintenanceFlash('danger', 'Thiết bị không tồn tại!');
    header('Location: equipment-maintenance.php');
    exit;
  }

  if (hasOpenMaintenanceForEquipment($db, $equipmentId, $maintenanceHasStatus)) {
    setMaintenanceFlash('danger', 'Thiết bị này đã có phiếu chờ/đang bảo trì, không thể thêm trùng.');
    header('Location: equipment-maintenance.php');
    exit;
  }

  if ($maintenanceHasStatus) {
    $insertStmt = $db->prepare("INSERT INTO equipment_maintenance (equipment_id, maintenance_date, description, status) VALUES (?, ?, ?, ?)");
    $result = $insertStmt->execute([$equipmentId, $maintenanceDate, $description, $status]);
  } else {
    $insertStmt = $db->prepare("INSERT INTO equipment_maintenance (equipment_id, maintenance_date, description) VALUES (?, ?, ?)");
    $result = $insertStmt->execute([$equipmentId, $maintenanceDate, $description]);
  }

  if ($result) {
    syncEquipmentStatus($db, $equipmentId, $maintenanceHasStatus);
    setMaintenanceFlash('success', 'Thêm lịch bảo trì thành công!');
  } else {
    setMaintenanceFlash('danger', 'Lỗi khi thêm lịch bảo trì!');
  }
  header('Location: equipment-maintenance.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_maintenance_id'])) {
  checkPermission('MANAGE_EQUIPMENT', 'delete');

  $maintenanceId = intval($_POST['delete_maintenance_id']);

  $recordStmt = $db->prepare("SELECT equipment_id FROM equipment_maintenance WHERE id = ?");
  $recordStmt->execute([$maintenanceId]);
  $equipmentId = (int) $recordStmt->fetchColumn();

  if ($equipmentId <= 0) {
    setMaintenanceFlash('danger', 'Phiếu bảo trì không tồn tại!');
    header('Location: equipment-maintenance.php');
    exit;
  }

  $deleteStmt = $db->prepare("DELETE FROM equipment_maintenance WHERE id = ?");

  if ($deleteStmt->execute([$maintenanceId])) {
    syncEquipmentStatus($db, $equipmentId, $maintenanceHasStatus);
    setMaintenanceFlash('success', 'Xóa lịch bảo trì thành công!');
  } else {
    setMaintenanceFlash('danger', 'Lỗi khi xóa lịch bảo trì!');
  }
  header('Location: equipment-maintenance.php');
  exit;
}

$maintenanceFlash = $_SESSION['maintenance_flash'] ?? null;
unset($_SESSION['maintenance_flash']);

$equipmentStmt = $db->query("SELECT id, name FROM equipment ORDER BY name ASC");
$equipments = $equipmentStmt->fetchAll();

$busyEquipmentIds = [];
if ($maintenanceHasStatus) {
  $busyStmt = $db->query("SELECT DISTINCT equipment_id FROM equipment_maintenance WHERE status IN ('cho_bao_tri', 'dang_bao_tri')");
} else {
  $busyStmt = $db->query("SELECT DISTINCT equipment_id FROM equipment_maintenance");
}
if ($busyStmt) {
  $busyEquipmentIds = array_map('intval', $busyStmt->fetchAll(PDO::FETCH_COLUMN));
}
$busyEquipmentMap = array_fill_keys($busyEquipmentIds, true);

$availableEquipments = array_values(array_filter($equipments, function ($equipment) use ($busyEquipmentMap) {
  return empty($busyEquipmentMap[(int) $equipment['id']]);
}));
$hasAvailableEquipment = !empty($availableEquipments);

$maintenanceSelect = "SELECT em.id, em.equipment_id, e.name AS equipment_name, em.maintenance_date, em.description";
if ($maintenanceHasStatus) {
  $maintenanceSelect .= ", em.status";
} else {
  $maintenanceSelect .= ", 'dang_bao_tri' AS status";
}
$maintenanceSelect .= " FROM equipment_maintenance em INNER JOIN equipment e ON e.id = em.equipment_id ORDER BY em.maintenance_date DESC, em.id DESC";
$maintenanceStmt = $db->query($maintenanceSelect);
$maintenances = $maintenanceStmt->fetchAll();

// layout chung
include 'layout/header.php';
include 'layout/sidebar.php';

?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Bảo trì thiết bị</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Equipment Maintenance</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php if ($maintenanceFlash): ?>
          <div class="row">
            <div class="col-12">
              <div class="alert alert-<?= $maintenanceFlash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($maintenanceFlash['message']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            </div>
          </div>
        <?php endif; ?>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Lịch sử bảo trì</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMaintenanceModal">
                    <i class="fas fa-plus"></i> Thêm lịch bảo trì
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>ID Thiết bị</th>
                    <th>Ngày bảo trì</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($maintenances as $maintenance): ?>
                  <tr>
                    <td><?= $maintenance['id'] ?></td>
                    <td><?= htmlspecialchars($maintenance['equipment_name']) ?> #<?= $maintenance['equipment_id'] ?></td>
                    <td><?= $maintenance['maintenance_date'] ?></td>
                    <td>
                      <?php if (($maintenance['status'] ?? 'dang_bao_tri') === 'hoan_thanh'): ?>
                        <span class="badge badge-success">Hoàn thành</span>
                      <?php elseif (($maintenance['status'] ?? 'dang_bao_tri') === 'huy'): ?>
                        <span class="badge badge-secondary">Hủy</span>
                      <?php elseif (($maintenance['status'] ?? 'dang_bao_tri') === 'cho_bao_tri'): ?>
                        <span class="badge badge-info">Chờ bảo trì</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Đang bảo trì</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($maintenance['description']) ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm edit-maintenance-btn"
                        data-id="<?= $maintenance['id'] ?>"
                        data-equipment-id="<?= $maintenance['equipment_id'] ?>"
                        data-equipment-name="<?= htmlspecialchars($maintenance['equipment_name']) ?>"
                        data-maintenance-date="<?= $maintenance['maintenance_date'] ?>"
                        data-status="<?= htmlspecialchars($maintenance['status'] ?? 'dang_bao_tri') ?>"
                        data-description="<?= htmlspecialchars($maintenance['description']) ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-maintenance-btn" data-id="<?= $maintenance['id'] ?>"><i class="fas fa-trash"></i></button>
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

<?php if ($maintenanceFlash): ?>
<script>
  $(function() {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: <?= json_encode($maintenanceFlash['type'] === 'success' ? 'success' : 'error') ?>,
        title: <?= json_encode($maintenanceFlash['message']) ?>,
        timer: 1800,
        showConfirmButton: false
      });
    }
  });
</script>
<?php endif; ?>

<div class="modal fade" id="addMaintenanceModal" tabindex="-1" role="dialog" aria-labelledby="addMaintenanceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addMaintenanceForm" method="POST" action="equipment-maintenance.php" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="addMaintenanceModalLabel">Thêm lịch bảo trì</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="equipment_id">Thiết bị</label>
            <select class="form-control" id="equipment_id" name="equipment_id" <?= $hasAvailableEquipment ? '' : 'disabled' ?>>
              <option value="">-- Chọn thiết bị --</option>
              <?php foreach ($availableEquipments as $equipment): ?>
                <option value="<?= $equipment['id'] ?>"><?= htmlspecialchars($equipment['name']) ?> #<?= $equipment['id'] ?></option>
              <?php endforeach; ?>
              <?php if (!$hasAvailableEquipment): ?>
                <option value="" disabled>Không còn thiết bị khả dụng</option>
              <?php endif; ?>
            </select>
            <small class="text-muted d-block mt-1">Chỉ hiển thị thiết bị chưa có phiếu chờ/đang bảo trì.</small>
            <?php if (!$hasAvailableEquipment): ?>
              <small class="text-warning d-block mt-1">Hiện không có thiết bị nào có thể thêm lịch bảo trì mới.</small>
            <?php endif; ?>
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="maintenance_date">Ngày bảo trì</label>
            <input type="date" class="form-control" id="maintenance_date" name="maintenance_date">
            <small id="maintenance_status_hint" class="text-muted d-block mt-1">Trạng thái sẽ tự động theo ngày bảo trì.</small>
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="description">Ghi chú</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            <small class="text-danger d-none validation-error"></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" <?= $hasAvailableEquipment ? '' : 'disabled' ?>>Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editMaintenanceModal" tabindex="-1" role="dialog" aria-labelledby="editMaintenanceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editMaintenanceForm" method="POST" action="equipment-maintenance.php" novalidate>
        <input type="hidden" name="edit_maintenance_id" id="edit_maintenance_id">
        <input type="hidden" name="edit_equipment_id" id="edit_equipment_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editMaintenanceModalLabel">Chỉnh sửa lịch bảo trì</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_equipment_display">Thiết bị</label>
            <input type="text" class="form-control" id="edit_equipment_display" readonly>
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="edit_maintenance_date">Ngày bảo trì</label>
            <input type="date" class="form-control" id="edit_maintenance_date" name="edit_maintenance_date">
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="edit_status">Trạng thái</label>
            <select class="form-control" id="edit_status" name="edit_status">
              <option value="cho_bao_tri">Chờ bảo trì</option>
              <option value="dang_bao_tri">Đang bảo trì</option>
              <option value="hoan_thanh">Hoàn thành</option>
              <option value="huy">Hủy</option>
            </select>
            <small class="text-danger d-none validation-error"></small>
          </div>
          <div class="form-group">
            <label for="edit_description">Ghi chú</label>
            <textarea class="form-control" id="edit_description" name="edit_description" rows="3"></textarea>
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

<div class="modal fade" id="deleteMaintenanceModal" tabindex="-1" role="dialog" aria-labelledby="deleteMaintenanceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="deleteMaintenanceForm" method="POST" action="equipment-maintenance.php">
        <input type="hidden" name="delete_maintenance_id" id="delete_maintenance_id">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteMaintenanceModalLabel">Xóa lịch bảo trì</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc muốn xóa lịch bảo trì này?</p>
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

  function validateMaintenanceForm($form) {
    var isValid = true;
    var $equipment = $form.find('select[name="equipment_id"], input[name="edit_equipment_id"]');
    var $date = $form.find('input[name="maintenance_date"], input[name="edit_maintenance_date"]');
    var $status = $form.find('select[name="maintenance_status"], select[name="edit_status"]');
    var $description = $form.find('textarea[name="description"], textarea[name="edit_description"]');

    $form.find('.form-control').each(function() {
      clearFieldError($(this));
    });

    if (!$equipment.val()) {
      setFieldError($equipment, 'Vui lòng chọn thiết bị.');
      isValid = false;
    }

    if (!$date.val()) {
      setFieldError($date, 'Vui lòng chọn ngày bảo trì.');
      isValid = false;
    }

    if ($status.length && !$status.val()) {
      setFieldError($status, 'Vui lòng chọn trạng thái bảo trì.');
      isValid = false;
    }

    if (!$description.val().trim()) {
      setFieldError($description, 'Vui lòng nhập ghi chú bảo trì.');
      isValid = false;
    }

    return isValid;
  }

  $('#addMaintenanceForm, #editMaintenanceForm').on('submit', function(e) {
    if (!validateMaintenanceForm($(this))) {
      e.preventDefault();
    }
  });

  $('#addMaintenanceForm, #editMaintenanceForm').find('.form-control').on('input change', function() {
    clearFieldError($(this));
  });

  function inferOpenStatusByDate(dateValue) {
    if (!dateValue) {
      return 'dang_bao_tri';
    }

    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var selected = new Date(dateValue + 'T00:00:00');
    if (isNaN(selected.getTime())) {
      return 'dang_bao_tri';
    }

    return selected > today ? 'cho_bao_tri' : 'dang_bao_tri';
  }

  function updateAddStatusHint() {
    var dateValue = $('#maintenance_date').val();
    var status = inferOpenStatusByDate(dateValue);
    var text = status === 'cho_bao_tri'
      ? 'Trạng thái dự kiến: Chờ bảo trì (do ngày bảo trì ở tương lai).'
      : 'Trạng thái dự kiến: Đang bảo trì.';
    $('#maintenance_status_hint').text(text);
  }

  $('#maintenance_date').on('change input', updateAddStatusHint);
  updateAddStatusHint();

  $('#edit_maintenance_date').on('change input', function() {
    var $status = $('#edit_status');
    var current = $status.val();
    if (current === 'cho_bao_tri' || current === 'dang_bao_tri') {
      $status.val(inferOpenStatusByDate($(this).val()));
    }
  });

  $('.data-table tbody').on('click', '.edit-maintenance-btn', function(e) {
    e.preventDefault();
    var $btn = $(this).closest('.edit-maintenance-btn');
    $('#edit_maintenance_id').val($btn.data('id'));
    $('#edit_equipment_id').val($btn.data('equipment-id'));
    $('#edit_equipment_display').val($btn.data('equipment-name') + ' #' + $btn.data('equipment-id'));
    $('#edit_maintenance_date').val($btn.data('maintenance-date'));
    $('#edit_status').val($btn.data('status'));
    $('#edit_description').val($btn.data('description'));
    $('#editMaintenanceModal').modal('show');
  });

  $('.data-table tbody').on('click', '.delete-maintenance-btn', function(e) {
    e.preventDefault();
    var $btn = $(this).closest('.delete-maintenance-btn');
    $('#delete_maintenance_id').val($btn.data('id'));
    $('#deleteMaintenanceModal').modal('show');
  });

  $('#deleteMaintenanceForm').on('submit', function() {
    var $submit = $(this).find('button[type="submit"]');
    $submit.prop('disabled', true).text('Đang xóa...');
  });

  $('#addMaintenanceModal, #editMaintenanceModal').on('hidden.bs.modal', function() {
    $(this).find('.form-control').each(function() {
      clearFieldError($(this));
    });
  });
});
</script>
