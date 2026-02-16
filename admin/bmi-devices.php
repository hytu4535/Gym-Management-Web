<?php 
$page_title = "Quản lý Máy Đo BMI";
require_once '../includes/database.php';

// Xử lý các hành động
$db = getDB();
$message = '';
$messageType = '';

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM bmi_devices WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Xóa máy đo BMI thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_code = $_POST['device_code'];
    $location = $_POST['location'];
    $status = $_POST['status'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Cập nhật
            $stmt = $db->prepare("UPDATE bmi_devices SET device_code=?, location=?, status=? WHERE id=?");
            $stmt->execute([$device_code, $location, $status, $_POST['id']]);
            $message = "Cập nhật máy đo BMI thành công!";
        } else {
            // Thêm mới
            $stmt = $db->prepare("INSERT INTO bmi_devices (device_code, location, status) VALUES (?, ?, ?)");
            $stmt->execute([$device_code, $location, $status]);
            $message = "Thêm máy đo BMI thành công!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Lấy danh sách máy đo BMI
$stmt = $db->query("SELECT * FROM bmi_devices ORDER BY id DESC");
$devices = $stmt->fetchAll();

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
            <h1 class="m-0">Quản lý Máy Đo BMI</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Máy Đo BMI</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
          <?php echo $message; ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Máy Đo BMI</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#deviceModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Máy Đo
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table id="deviceTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Mã Máy</th>
                    <th>Vị Trí</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($devices as $device): ?>
                  <tr>
                    <td><?php echo $device['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($device['device_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($device['location'] ?? 'N/A'); ?></td>
                    <td>
                      <?php 
                      $statusMap = [
                        'active' => ['text' => 'Hoạt động', 'class' => 'success'],
                        'inactive' => ['text' => 'Không hoạt động', 'class' => 'secondary'],
                        'maintenance' => ['text' => 'Bảo trì', 'class' => 'warning']
                      ];
                      $status = $statusMap[$device['status']] ?? $statusMap['inactive'];
                      ?>
                      <span class="badge badge-<?php echo $status['class']; ?>">
                        <?php echo $status['text']; ?>
                      </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($device['created_at'])); ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm" onclick='editDevice(<?php echo json_encode($device); ?>)' data-toggle="modal" data-target="#deviceModal" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?action=delete&id=<?php echo $device['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </a>
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

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="deviceModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Máy Đo BMI</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" id="device_id">
          <div class="form-group">
            <label>Mã máy <span class="text-danger">*</span></label>
            <input type="text" name="device_code" id="device_code" class="form-control" required placeholder="BMI-001">
          </div>
          <div class="form-group">
            <label>Vị trí</label>
            <input type="text" name="location" id="location" class="form-control" placeholder="Tầng 1 - Khu A">
          </div>
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="status" class="form-control">
              <option value="active">Hoạt động</option>
              <option value="inactive">Không hoạt động</option>
              <option value="maintenance">Bảo trì</option>
            </select>
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

<script>
function resetForm() {
  document.getElementById('modalTitle').innerText = 'Thêm Máy Đo BMI';
  document.getElementById('device_id').value = '';
  document.getElementById('device_code').value = '';
  document.getElementById('location').value = '';
  document.getElementById('status').value = 'active';
}

function editDevice(device) {
  document.getElementById('modalTitle').innerText = 'Sửa Máy Đo BMI';
  document.getElementById('device_id').value = device.id;
  document.getElementById('device_code').value = device.device_code;
  document.getElementById('location').value = device.location || '';
  document.getElementById('status').value = device.status;
}

// Initialize DataTable
$(document).ready(function() {
    $('#deviceTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        },
        "pageLength": 10,
        "order": [[0, "desc"]]
    });
});
</script>

<?php include 'layout/footer.php'; ?>
