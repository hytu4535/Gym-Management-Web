<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Users";

// gọi auth trước để kiểm tra đăng nhập
include '../includes/auth.php';   

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_ALL
checkPermission('MANAGE_ALL');

// layout chung
include 'layout/header.php'; 
include 'layout/sidebar.php';

$db = getDB();

// Lấy danh sách users
$sql = "SELECT u.id, u.username, u.email, r.name AS role, u.role_id, u.status, u.created_at
        FROM users u
        JOIN roles r ON u.role_id = r.id";
$stmt = $db->query($sql);
$users = $stmt->fetchAll();

// Lấy danh sách roles để dùng cho form
$roles = $db->query("SELECT id, name FROM roles WHERE status='active'")->fetchAll();
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Quản lý Users</h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Danh sách Users</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
              <i class="fas fa-plus"></i> Thêm User
            </button>
          </div>
        </div>
        <div class="card-body">
          <!-- Bảng có class data-table -->
          <table class="table table-bordered table-striped data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($users as $u): ?>
              <tr>
                <td><?= $u['id'] ?></td>
                <td><?= $u['username'] ?></td>
                <td><?= $u['email'] ?></td>
                <td><span class="badge badge-info"><?= $u['role'] ?></span></td>
                <td>
                  <?php if(strtolower($u['status'])=='active'): ?>
                    <span class="badge badge-success">Active</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td><?= $u['created_at'] ?></td>
                <td>
                  <!-- Nút sửa mở modal -->
                  <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal<?= $u['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Nút xoá -->
                  <a href="process/user_management.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa user này?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>

              <!-- Modal sửa user -->
              <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <form action="process/user_management.php" method="POST">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Sửa User</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">

                        <div class="form-group">
                          <label>Tên đăng nhập</label>
                          <input type="text" class="form-control" name="username" value="<?= $u['username'] ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Email</label>
                          <input type="email" class="form-control" name="email" value="<?= $u['email'] ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Mật khẩu (để trống nếu không đổi)</label>
                          <input type="password" class="form-control" name="password">
                        </div>
                        <div class="form-group">
                          <label>Vai trò</label>
                          <select class="form-control" name="role_id" required>
                            <?php foreach($roles as $r): ?>
                              <option value="<?= $r['id'] ?>" <?= $u['role_id']==$r['id']?'selected':'' ?>>
                                <?= $r['name'] ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Trạng thái</label>
                          <select class="form-control" name="status" required>
                            <option value="active" <?= strtolower($u['status'])=='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= strtolower($u['status'])=='inactive'?'selected':'' ?>>Inactive</option>
                          </select>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal thêm user -->
  <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="process/user_management.php" method="POST">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Thêm User Mới</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Tên đăng nhập</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
              <label>Mật khẩu</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-group">
              <label>Vai trò</label>
              <select class="form-control" name="role_id" required>
                <?php foreach($roles as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select class="form-control" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<!-- Khởi tạo DataTables -->
<script>
  $(document).ready(function() {
    if ($.fn.DataTable.isDataTable('.data-table')) {
      $('.data-table').DataTable().destroy();
    }
    $('.data-table').DataTable({
      paging: true,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false,
      responsive: true
    });
  });
</script>
