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

// Kiểm tra cột phone tồn tại
$checkColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();
$hasPhoneColumn = !empty($checkColumn);

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Lấy tổng số users
$countSql = "SELECT COUNT(*) as total FROM users";
$countStmt = $db->query($countSql);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $itemsPerPage);

// Lấy danh sách users
if ($hasPhoneColumn) {
    $sql = "SELECT u.id, u.username, u.email, u.phone, u.password, r.name AS role, u.role_id, u.status, u.created_at
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
            LIMIT $itemsPerPage OFFSET $offset";
} else {
    $sql = "SELECT u.id, u.username, u.email, u.password, r.name AS role, u.role_id, u.status, u.created_at
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
            LIMIT $itemsPerPage OFFSET $offset";
}
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
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Mật khẩu</th>
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
                <td><?= isset($u['phone']) ? $u['phone'] : 'N/A' ?></td>
                <td>
                  <div class="password-display-group" data-id="<?= $u['id'] ?>">
                    <span class="password-masked">••••••••</span>
                    <span class="password-actual" style="display: none;"><?= htmlspecialchars(substr($u['password'], 0, 15)) . (strlen($u['password']) > 15 ? '...' : '') ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="togglePasswordDisplay(<?= $u['id'] ?>)" style="padding: 2px 8px; margin-left: 5px;">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
                <td><span class="badge badge-info"><?= $u['role'] ?></span></td>
                <td>
                  <?php if($u['status']=='active'): ?>
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
                          <label>Số điện thoại</label>
                          <input type="text" class="form-control" name="phone" value="<?= isset($u['phone']) ? $u['phone'] : '' ?>" placeholder="Nhập số điện thoại">
                        </div>
                        <div class="form-group">
                          <label>Mật khẩu (để trống nếu không đổi)</label>
                          <div class="input-group">
                            <input type="password" class="form-control password-input" name="password" placeholder="Nhập mật khẩu mới">
                            <div class="input-group-append">
                              <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility(this)">
                                <i class="fas fa-eye"></i>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label>Xác nhận lại mật khẩu</label>
                          <div class="input-group">
                            <input type="password" class="form-control password-confirm" name="password_confirm" placeholder="Xác nhận mật khẩu">
                            <div class="input-group-append">
                              <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility(this)">
                                <i class="fas fa-eye"></i>
                              </span>
                            </div>
                          </div>
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
                            <option value="active" <?= $u['status']=='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= $u['status']=='inactive'?'selected':'' ?>>Inactive</option>
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
          
          <!-- Pagination -->
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=1">Đầu tiên</a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <span class="page-link">Đầu tiên</span>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">Trước</span>
                </li>
              <?php endif; ?>

              <?php 
              // Hiển thị các số trang
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);
              
              if ($startPage > 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif;
              
              for ($i = $startPage; $i <= $endPage; $i++):
                if ($i == $page): ?>
                  <li class="page-item active">
                    <span class="page-link"><?= $i ?></span>
                  </li>
                <?php else: ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endif;
              endfor;
              
              if ($endPage < $totalPages): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $totalPages ?>">Cuối cùng</a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <span class="page-link">Sau</span>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">Cuối cùng</span>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
          <div class="text-center text-muted">
            <small>Trang <?= $page ?> / <?= $totalPages ?> (Tổng: <?= $totalRecords ?> người dùng)</small>
          </div>
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
              <label>Số điện thoại</label>
              <input type="text" class="form-control" name="phone" placeholder="Nhập số điện thoại">
            </div>
            <div class="form-group">
              <label>Mật khẩu</label>
              <div class="input-group">
                <input type="password" class="form-control password-input" name="password" placeholder="Nhập mật khẩu" required>
                <div class="input-group-append">
                  <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility(this)">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Xác nhận lại mật khẩu</label>
              <div class="input-group">
                <input type="password" class="form-control password-confirm" name="password_confirm" placeholder="Xác nhận mật khẩu" required>
                <div class="input-group-append">
                  <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility(this)">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
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
</div>

<?php include 'layout/footer.php'; ?>

<script>
// Hàm toggle password visibility
function togglePasswordVisibility(btn) {
  const input = btn.closest('.input-group').querySelector('input');
  const icon = btn.querySelector('i');
  
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Hàm toggle password display
function togglePasswordDisplay(userId) {
  const group = document.querySelector(`.password-display-group[data-id="${userId}"]`);
  const masked = group.querySelector('.password-masked');
  const actual = group.querySelector('.password-actual');
  const btn = group.querySelector('button');
  const icon = btn.querySelector('i');
  
  if (masked.style.display !== 'none') {
    masked.style.display = 'none';
    actual.style.display = 'inline';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    masked.style.display = 'inline';
    actual.style.display = 'none';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Validation khi submit form
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', function(e) {
    const action = this.querySelector('input[name="action"]').value;
    const passwordInput = this.querySelector('input[name="password"]');
    const passwordConfirm = this.querySelector('input[name="password_confirm"]');
    
    // Nếu là form thêm, password là required
    if (action === 'add') {
      if (!passwordInput.value.trim()) {
        e.preventDefault();
        alert('Vui lòng nhập mật khẩu');
        return false;
      }
      if (!passwordConfirm.value.trim()) {
        e.preventDefault();
        alert('Vui lòng xác nhận mật khẩu');
        return false;
      }
      if (passwordInput.value !== passwordConfirm.value) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp');
        return false;
      }
    }
    // Nếu là form sửa, nếu nhập mật khẩu thì phải xác nhận
    else if (action === 'edit') {
      if (passwordInput.value.trim() || passwordConfirm.value.trim()) {
        if (passwordInput.value !== passwordConfirm.value) {
          e.preventDefault();
          alert('Mật khẩu xác nhận không khớp');
          return false;
        }
      }
    }
  });
});
</script>
