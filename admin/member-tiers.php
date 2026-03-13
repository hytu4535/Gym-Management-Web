<?php 
$page_title = "Quản lý Hạng Hội Viên";
require_once '../includes/database.php';

// Xử lý các hành động
$db = getDB();
$message = '';
$messageType = '';

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $level = $_POST['level'];
    $min_spent = $_POST['min_spent'];
    $base_discount = $_POST['base_discount'];
    $status = $_POST['status'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Cập nhật
            $stmt = $db->prepare("UPDATE member_tiers SET name=?, level=?, min_spent=?, base_discount=?, status=? WHERE id=?");
            $stmt->execute([$name, $level, $min_spent, $base_discount, $status, $id]);
            $message = "Cập nhật hạng hội viên thành công!";
        } else {
            // Thêm mới
            $stmt = $db->prepare("INSERT INTO member_tiers (name, level, min_spent, base_discount, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $level, $min_spent, $base_discount, $status]);
            $message = "Thêm hạng hội viên thành công!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Lấy danh sách hạng
$stmt = $db->query("SELECT * FROM member_tiers ORDER BY level");
$tiers = $stmt->fetchAll();

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
            <h1 class="m-0">Quản lý Hạng Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Hạng Hội Viên</li>
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
                <h3 class="card-title">Danh sách Hạng Hội Viên</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#tierModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Hạng
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table id="tierTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Hạng</th>
                    <th>Cấp Độ</th>
                    <th>Chi Tiêu Tối Thiểu</th>
                    <th>Giảm Giá (%)</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($tiers as $tier): ?>
                  <tr>
                    <td><?php echo $tier['id']; ?></td>
                    <td>
                      <?php 
                      $badgeClass = ['Đồng' => 'secondary', 'Bạc' => 'light', 'Vàng' => 'warning', 'Bạch Kim' => 'primary', 'Kim Cương' => 'info'];
                      $class = $badgeClass[$tier['name']] ?? 'secondary';
                      ?>
                      <span class="badge badge-<?php echo $class; ?>"><?php echo htmlspecialchars($tier['name']); ?></span>
                    </td>
                    <td><?php echo $tier['level']; ?></td>
                    <td><?php echo number_format($tier['min_spent'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo $tier['base_discount']; ?>%</td>
                    <td>
                      <span class="badge badge-<?php echo $tier['status'] == 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo $tier['status'] == 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                      </span>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm" onclick='editTier(<?php echo json_encode($tier); ?>)' data-toggle="modal" data-target="#tierModal">
                        <i class="fas fa-edit"></i>
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

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="tierModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Hạng Hội Viên</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" id="tier_id">
          <div class="form-group">
            <label>Tên hạng</label>
            <input type="text" name="name" id="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Cấp độ</label>
            <input type="number" name="level" id="level" class="form-control" required min="1">
          </div>
          <div class="form-group">
            <label>Chi tiêu tối thiểu (VNĐ)</label>
            <input type="number" step="0.01" name="min_spent" id="min_spent" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Giảm giá cơ bản (%)</label>
            <input type="number" step="0.01" name="base_discount" id="base_discount" class="form-control" required min="0" max="100">
          </div>
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="status" class="form-control">
              <option value="active">Hoạt động</option>
              <option value="inactive">Không hoạt động</option>
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
  document.getElementById('modalTitle').innerText = 'Thêm Hạng Hội Viên';
  document.getElementById('tier_id').value = '';
  document.getElementById('name').value = '';
  document.getElementById('level').value = '';
  document.getElementById('min_spent').value = '';
  document.getElementById('base_discount').value = '';
  document.getElementById('status').value = 'active';
}

function editTier(tier) {
  document.getElementById('modalTitle').innerText = 'Sửa Hạng Hội Viên';
  document.getElementById('tier_id').value = tier.id;
  document.getElementById('name').value = tier.name;
  document.getElementById('level').value = tier.level;
  document.getElementById('min_spent').value = tier.min_spent;
  document.getElementById('base_discount').value = tier.base_discount;
  document.getElementById('status').value = tier.status;
}
</script>

<?php include 'layout/footer.php'; ?>
