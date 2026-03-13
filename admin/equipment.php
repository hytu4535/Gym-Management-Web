<?php 
$page_title = "Quản lý thiết bị";
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
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách thiết bị</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addEquipmentModal">
                    <i class="fas fa-plus"></i> Thêm thiết bị
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
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
                  $stmt = $db->query("SELECT * FROM equipment ORDER BY id DESC");
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
                      <button class="btn btn-warning btn-sm edit-equipment-btn"
                        data-id="<?= $equipment['id'] ?>"
                        data-name="<?= htmlspecialchars($equipment['name']) ?>"
                        data-quantity="<?= $equipment['quantity'] ?>"
                        data-status="<?= $equipment['status'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-equipment-btn"
                        data-id="<?= $equipment['id'] ?>"
                        data-name="<?= htmlspecialchars($equipment['name']) ?>">
                        <i class="fas fa-trash"></i>
                      </button>
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
      <form id="editEquipmentForm" method="POST" action="equipment.php">
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
            <input type="text" class="form-control" id="edit_equipment_name" name="edit_equipment_name" required>
          </div>
          <div class="form-group">
            <label for="edit_equipment_quantity">Số lượng</label>
            <input type="number" class="form-control" id="edit_equipment_quantity" name="edit_equipment_quantity" min="1" required>
          </div>
          <div class="form-group">
            <label for="edit_equipment_status">Tình trạng</label>
            <select class="form-control" id="edit_equipment_status" name="edit_equipment_status" required>
              <option value="dang su dung">Đang sử dụng</option>
              <option value="bao tri">Bảo trì</option>
              <option value="ngung hoat dong">Ngừng hoạt động</option>
            </select>
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
});
</script>

<!-- Handle sửa thiết bị (PHP) -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_equipment_id'])) {
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
      <form id="addEquipmentForm" method="POST" action="equipment.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addEquipmentModalLabel">Thêm Thiết Bị</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="equipment_name">Tên thiết bị</label>
            <input type="text" class="form-control" id="equipment_name" name="equipment_name" required>
          </div>
          <div class="form-group">
            <label for="equipment_quantity">Số lượng</label>
            <input type="number" class="form-control" id="equipment_quantity" name="equipment_quantity" min="1" required>
          </div>
          <div class="form-group">
            <label for="equipment_status">Tình trạng</label>
            <select class="form-control" id="equipment_status" name="equipment_status" required>
              <option value="dang su dung">Đang sử dụng</option>
              <option value="bao tri">Bảo trì</option>
              <option value="ngung hoat dong">Ngừng hoạt động</option>
            </select>
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
