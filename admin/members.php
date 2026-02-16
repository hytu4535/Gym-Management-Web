<?php 
$page_title = "Quản lý Hội Viên";
require_once '../includes/database.php';

// Xử lý các hành động
$db = getDB();
$message = '';
$messageType = '';

// Hàm tự động xác định tier dựa trên total_spent
function getTierByTotalSpent($db, $total_spent) {
    $stmt = $db->query("SELECT id FROM member_tiers WHERE min_spent <= $total_spent ORDER BY min_spent DESC LIMIT 1");
    $tier = $stmt->fetch();
    return $tier ? $tier['id'] : 1; // Mặc định là tier 1 (Đồng)
}

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Xóa hội viên thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $users_id = $_POST['users_id'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $status = $_POST['status'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Cập nhật - Lấy total_spent hiện tại và tính tier
            $stmt = $db->prepare("SELECT total_spent FROM members WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $total_spent = $stmt->fetchColumn() ?: 0;
            $tier_id = getTierByTotalSpent($db, $total_spent);
            
            $stmt = $db->prepare("UPDATE members SET users_id=?, full_name=?, phone=?, address=?, height=?, weight=?, status=?, tier_id=? WHERE id=?");
            $stmt->execute([$users_id, $full_name, $phone, $address, $height, $weight, $status, $tier_id, $_POST['id']]);
            $message = "Cập nhật hội viên thành công!";
        } else {
            // Thêm mới - Mặc định tier 1 (Đồng) vì total_spent = 0
            $tier_id = 1;
            $stmt = $db->prepare("INSERT INTO members (users_id, full_name, phone, address, height, weight, status, tier_id, join_date, total_spent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 0)");
            $stmt->execute([$users_id, $full_name, $phone, $address, $height, $weight, $status, $tier_id]);
            $message = "Thêm hội viên thành công!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Lấy danh sách hội viên
$stmt = $db->query("SELECT m.*, u.email, t.name as tier_name, t.level as tier_level 
                    FROM members m 
                    LEFT JOIN users u ON m.users_id = u.id 
                    LEFT JOIN member_tiers t ON m.tier_id = t.id 
                    ORDER BY m.id DESC");
$members = $stmt->fetchAll();

// Lấy danh sách users cho form
$users = $db->query("SELECT id, email FROM users")->fetchAll();

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
            <h1 class="m-0">Quản lý Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Hội Viên</li>
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
                <h3 class="card-title">Danh sách Hội Viên</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#memberModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Hội Viên
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table id="memberTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Hạng HV</th>
                    <th>Tổng Chi</th>
                    <th>Chiều cao</th>
                    <th>Cân nặng</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($members as $member): ?>
                  <tr>
                    <td><?php echo $member['id']; ?></td>
                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                    <td>
                      <?php 
                      $badgeClass = ['Đồng' => 'secondary', 'Bạc' => 'light', 'Vàng' => 'warning', 'Bạch Kim' => 'primary', 'Kim Cương' => 'info'];
                      $class = $badgeClass[$member['tier_name']] ?? 'secondary';
                      ?>
                      <span class="badge badge-<?php echo $class; ?>"><?php echo $member['tier_name']; ?></span>
                    </td>
                    <td><?php echo number_format($member['total_spent'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo $member['height'] ? $member['height'] . ' cm' : 'N/A'; ?></td>
                    <td><?php echo $member['weight'] ? $member['weight'] . ' kg' : 'N/A'; ?></td>
                    <td>
                      <span class="badge badge-<?php echo $member['status'] == 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo $member['status'] == 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                      </span>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm" onclick='editMember(<?php echo json_encode($member); ?>)' data-toggle="modal" data-target="#memberModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">
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
<div class="modal fade" id="memberModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Hội Viên</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" id="member_id">
          <div class="form-group">
            <label>Tài khoản</label>
            <select name="users_id" id="users_id" class="form-control" required>
              <option value="">--- Chọn tài khoản ---</option>
              <?php foreach ($users as $user): ?>
              <option value="<?php echo $user['id']; ?>"><?php echo $user['email']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Họ tên</label>
            <input type="text" name="full_name" id="full_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="phone" id="phone" class="form-control">
          </div>
          <div class="form-group">
            <label>Địa chỉ</label>
            <textarea name="address" id="address" class="form-control" rows="3"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Chiều cao (cm)</label>
                <input type="number" step="0.01" name="height" id="height" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Cân nặng (kg)</label>
                <input type="number" step="0.01" name="weight" id="weight" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group" id="tier_info_group" style="display: none;">
            <label>Hạng hội viên hiện tại</label>
            <div class="alert alert-info mb-0">
              <i class="fas fa-info-circle"></i> <span id="tier_display"></span>
              <br><small>Hạng hội viên được tự động cập nhật dựa trên tổng chi tiêu.</small>
            </div>
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
  document.getElementById('modalTitle').innerText = 'Thêm Hội Viên';
  document.getElementById('member_id').value = '';
  document.getElementById('users_id').value = '';
  document.getElementById('full_name').value = '';
  document.getElementById('phone').value = '';
  document.getElementById('address').value = '';
  document.getElementById('height').value = '';
  document.getElementById('weight').value = '';
  document.getElementById('status').value = 'active';
  document.getElementById('tier_info_group').style.display = 'none';
}

function editMember(member) {
  document.getElementById('modalTitle').innerText = 'Sửa Hội Viên';
  document.getElementById('member_id').value = member.id;
  document.getElementById('users_id').value = member.users_id;
  document.getElementById('full_name').value = member.full_name;
  document.getElementById('phone').value = member.phone || '';
  document.getElementById('address').value = member.address || '';
  document.getElementById('height').value = member.height || '';
  document.getElementById('weight').value = member.weight || '';
  document.getElementById('status').value = member.status;
  
  // Hiển thị thông tin tier tự động
  document.getElementById('tier_info_group').style.display = 'block';
  var tierBadge = '<span class="badge badge-';
  var tierClass = {'Đồng': 'secondary', 'Bạc': 'light', 'Vàng': 'warning', 'Bạch Kim': 'primary', 'Kim Cương': 'info'};
  var badgeColor = tierClass[member.tier_name] || 'secondary';
  tierBadge += badgeColor + '">' + member.tier_name + '</span>';
  tierBadge += ' - Tổng chi: ' + new Intl.NumberFormat('vi-VN').format(member.total_spent) + ' VNĐ';
  document.getElementById('tier_display').innerHTML = tierBadge;
}
</script>

<?php include 'layout/footer.php'; ?>