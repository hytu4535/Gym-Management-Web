<?php
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_maintenance_id'])) {
  $maintenanceId = intval($_POST['edit_maintenance_id']);
  $equipmentId = intval($_POST['edit_equipment_id']);
  $maintenanceDate = sanitize($_POST['edit_maintenance_date']);
  $description = sanitize($_POST['edit_description']);

  $checkStmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE id = ?");
  $checkStmt->execute([$equipmentId]);

  if ((int) $checkStmt->fetchColumn() === 0) {
    echo "<script>alert('Thiết bị không tồn tại!');window.location='equipment-maintenance.php';</script>";
    exit;
  }

  $updateStmt = $db->prepare("UPDATE equipment_maintenance SET equipment_id = ?, maintenance_date = ?, description = ? WHERE id = ?");

  if ($updateStmt->execute([$equipmentId, $maintenanceDate, $description, $maintenanceId])) {
    $updateStatusStmt = $db->prepare("UPDATE equipment SET status = 'bao tri' WHERE id = ?");
    $updateStatusStmt->execute([$equipmentId]);
    echo "<script>alert('Cập nhật lịch bảo trì thành công!');window.location='equipment-maintenance.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật lịch bảo trì!');window.location='equipment-maintenance.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_id'])) {
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

$page_title = "Bảo trì thiết bị";
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
                    <td><?= htmlspecialchars($maintenance['description']) ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm edit-maintenance-btn"
                        data-id="<?= $maintenance['id'] ?>"
                        data-equipment-id="<?= $maintenance['equipment_id'] ?>"
                        data-maintenance-date="<?= $maintenance['maintenance_date'] ?>"
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

<div class="modal fade" id="addMaintenanceModal" tabindex="-1" role="dialog" aria-labelledby="addMaintenanceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="equipment-maintenance.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addMaintenanceModalLabel">Thêm lịch bảo trì</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="equipment_id">Thiết bị</label>
            <select class="form-control" id="equipment_id" name="equipment_id" required>
              <option value="">-- Chọn thiết bị --</option>
              <?php foreach ($equipments as $equipment): ?>
                <option value="<?= $equipment['id'] ?>"><?= htmlspecialchars($equipment['name']) ?> #<?= $equipment['id'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="maintenance_date">Ngày bảo trì</label>
            <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" required>
          </div>
          <div class="form-group">
            <label for="description">Ghi chú</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editMaintenanceModal" tabindex="-1" role="dialog" aria-labelledby="editMaintenanceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="equipment-maintenance.php">
        <input type="hidden" name="edit_maintenance_id" id="edit_maintenance_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editMaintenanceModalLabel">Chỉnh sửa lịch bảo trì</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_equipment_id">Thiết bị</label>
            <select class="form-control" id="edit_equipment_id" name="edit_equipment_id" required>
              <?php foreach ($equipments as $equipment): ?>
                <option value="<?= $equipment['id'] ?>"><?= htmlspecialchars($equipment['name']) ?> #<?= $equipment['id'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_maintenance_date">Ngày bảo trì</label>
            <input type="date" class="form-control" id="edit_maintenance_date" name="edit_maintenance_date" required>
          </div>
          <div class="form-group">
            <label for="edit_description">Ghi chú</label>
            <textarea class="form-control" id="edit_description" name="edit_description" rows="3" required></textarea>
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
      <form method="POST" action="equipment-maintenance.php">
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
  $('.edit-maintenance-btn').on('click', function() {
    $('#edit_maintenance_id').val($(this).data('id'));
    $('#edit_equipment_id').val($(this).data('equipment-id'));
    $('#edit_maintenance_date').val($(this).data('maintenance-date'));
    $('#edit_description').val($(this).data('description'));
    $('#editMaintenanceModal').modal('show');
  });

  $('.delete-maintenance-btn').on('click', function() {
    $('#delete_maintenance_id').val($(this).data('id'));
    $('#deleteMaintenanceModal').modal('show');
  });
});
</script>
