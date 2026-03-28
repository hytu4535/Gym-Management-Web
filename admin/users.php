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

// Bộ lọc
$filterUsername = trim((string) ($_GET['username'] ?? ''));
$filterEmail = trim((string) ($_GET['email'] ?? ''));
$filterPhone = trim((string) ($_GET['phone'] ?? ''));
$filterRoleId = trim((string) ($_GET['role_id'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterStatusDb = $filterStatus === 'inactive' ? 'locked' : $filterStatus;

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

$whereClauses = [];
$whereParams = [];

if ($filterUsername !== '') {
  $whereClauses[] = 'u.username LIKE ?';
  $whereParams[] = '%' . $filterUsername . '%';
}

if ($filterEmail !== '') {
  $whereClauses[] = 'u.email LIKE ?';
  $whereParams[] = '%' . $filterEmail . '%';
}

if ($hasPhoneColumn && $filterPhone !== '') {
  $whereClauses[] = 'u.phone LIKE ?';
  $whereParams[] = '%' . $filterPhone . '%';
}

if ($filterRoleId !== '' && ctype_digit($filterRoleId)) {
  $whereClauses[] = 'u.role_id = ?';
  $whereParams[] = (int) $filterRoleId;
}

if ($filterStatus !== '') {
  $whereClauses[] = 'u.status = ?';
  $whereParams[] = $filterStatusDb;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Lấy tổng số users
$countSql = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id" . $whereSql;
$countStmt = $db->prepare($countSql);
$countStmt->execute($whereParams);
$totalRecords = (int) ($countStmt->fetch()['total'] ?? 0);
$totalPages = ceil($totalRecords / $itemsPerPage);

// Lấy danh sách users
if ($hasPhoneColumn) {
  $sql = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.password, r.name AS role, u.role_id, u.status, u.created_at
            FROM users u
            JOIN roles r ON u.role_id = r.id
            $whereSql
            ORDER BY u.id ASC
            LIMIT $itemsPerPage OFFSET $offset";
} else {
  $sql = "SELECT u.id, u.username, u.full_name, u.email, u.password, r.name AS role, u.role_id, u.status, u.created_at
            FROM users u
            JOIN roles r ON u.role_id = r.id
            $whereSql
            ORDER BY u.id ASC
            LIMIT $itemsPerPage OFFSET $offset";
}
$stmt = $db->prepare($sql);
$stmt->execute($whereParams);
$users = $stmt->fetchAll();

// Lấy danh sách roles để dùng cho form
$roles = $db->query("SELECT id, name FROM roles WHERE status='active'")->fetchAll();

// Lấy validation errors từ session
$validationErrors = $_SESSION['validation_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

$generalMessage = '';
if (!empty($validationErrors) && isset($validationErrors['general'])) {
  $generalMessage = $validationErrors['general'];
}

// Xóa session errors sau khi lấy
unset($_SESSION['validation_errors']);
unset($_SESSION['form_data']);

// Kiểm tra nếu có validation error trên từng user cụ thể (edit)
$editUserId = $_GET['edit'] ?? null;
$editUserData = null;
if ($editUserId && !empty($validationErrors)) {
    $editStmt = $db->prepare("SELECT u.*, r.name AS role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $editStmt->execute([$editUserId]);
    $editUserData = $editStmt->fetch();
}

// Helper function để hiển thị lỗi
function getFieldError($fieldName, $errors) {
    return $errors[$fieldName] ?? '';
}

function getFieldValue($fieldName, $formData, $defaultValue = '') {
    return $formData[$fieldName] ?? $defaultValue;
}
?>


<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Quản lý Users</h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-12">
          <div class="card card-primary collapsed-card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-filter"></i> Lọc users</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <form method="GET" action="users.php">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Tên đăng nhập</label>
                      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($filterUsername) ?>" placeholder="Nhập tên đăng nhập">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Email</label>
                      <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($filterEmail) ?>" placeholder="Nhập email">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Số điện thoại</label>
                      <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($filterPhone) ?>" placeholder="Nhập số điện thoại">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Vai trò</label>
                      <select name="role_id" class="form-control">
                        <option value="">-- Tất cả vai trò --</option>
                        <?php foreach ($roles as $role): ?>
                          <option value="<?= $role['id'] ?>" <?= $filterRoleId !== '' && (int) $filterRoleId === (int) $role['id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Trạng thái</label>
                      <select name="status" class="form-control">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block mb-3">
                      <i class="fas fa-search"></i> Lọc
                    </button>
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <a href="users.php" class="btn btn-secondary btn-block mb-3">
                      <i class="fas fa-redo"></i> Xóa bộ lọc
                    </a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php if (!empty($generalMessage)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($generalMessage) ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>
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
          <table class="table table-bordered table-striped js-admin-table">
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
                <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
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
                    <span class="badge badge-success">Hoạt động</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Bị khóa</span>
                  <?php endif; ?>
                </td>
                <td><?= $u['created_at'] ?></td>
                <td>
                  <!-- Nút xem chi tiết -->
                  <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailUserModal<?= $u['id'] ?>">
                    <i class="fas fa-eye"></i>
                  </button>
                  <!-- Nút sửa mở modal -->
                  <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal<?= $u['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <form action="process/user_management.php" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái user này?');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <?php if($u['status']=='active'): ?>
                      <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-lock"></i> Khóa
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-unlock"></i> Mở khóa
                      </button>
                    <?php endif; ?>
                  </form>
                  <!-- Nút xoá -->
                  <a href="process/user_management.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa user này?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>

              <!-- Modal sửa user -->
              <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <form action="process/user_management.php" method="POST" novalidate>
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Sửa User</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">

                        <div class="form-group">
                          <label>Tên đăng nhập (Không thể sửa)</label>
                          <input type="text" class="form-control" value="<?= $u['username'] ?>" disabled>
                        </div>
                        <div class="form-group">
                          <label>Họ Tên</label>
                          <input type="text" class="form-control <?= getFieldError('full_name', $validationErrors) ? 'is-invalid' : '' ?>" name="full_name" value="<?= htmlspecialchars(getFieldValue('full_name', $formData, $u['full_name'] ?? '')) ?>" data-field="full_name">
                          <small class="text-danger d-block mt-2" style="<?= getFieldError('full_name', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('full_name', $validationErrors) ?></small>
                        </div>
                        <div class="form-group">
                          <label>Email (Không thể sửa)</label>
                          <input type="text" inputmode="email" class="form-control" value="<?= $u['email'] ?>" disabled>
                        </div>
                        <div class="form-group">
                          <label>Số điện thoại</label>
                          <input type="text" class="form-control <?= getFieldError('phone', $validationErrors) ? 'is-invalid' : '' ?>" name="phone" value="<?= htmlspecialchars(getFieldValue('phone', $formData, $u['phone'] ?? '')) ?>" placeholder="Nhập số bắt đầu bằng 0 hoặc +84" data-field="phone">
                          <small class="text-danger d-block mt-2" style="<?= getFieldError('phone', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('phone', $validationErrors) ?></small>
                        </div>
                        <div class="form-group">
                          <label>Mật khẩu (để trống nếu không đổi)</label>
                          <div class="input-group">
                            <input type="password" class="form-control password-input <?= getFieldError('password', $validationErrors) ? 'is-invalid' : '' ?>" name="password" placeholder="Nhập mật khẩu mới" data-field="password">
                            <div class="input-group-append">
                              <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                                <i class="fas fa-eye"></i>
                              </span>
                            </div>
                          </div>
                          <small class="text-danger d-block mt-2" style="<?= getFieldError('password', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password', $validationErrors) ?></small>
                        </div>
                        <div class="form-group">
                          <label>Xác nhận lại mật khẩu</label>
                          <div class="input-group">
                            <input type="password" class="form-control password-confirm <?= getFieldError('password_confirm', $validationErrors) ? 'is-invalid' : '' ?>" name="password_confirm" placeholder="Xác nhận mật khẩu" data-field="password_confirm">
                            <div class="input-group-append">
                              <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                                <i class="fas fa-eye"></i>
                              </span>
                            </div>
                          </div>
                          <small class="text-danger d-block mt-2" style="<?= getFieldError('password_confirm', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password_confirm', $validationErrors) ?></small>
                        </div>
                        <div class="form-group">
                          <label>Vai trò</label>
                          <select class="form-control <?= getFieldError('role_id', $validationErrors) ? 'is-invalid' : '' ?>" name="role_id" data-field="role_id">
                            <?php foreach($roles as $r): ?>
                              <option value="<?= $r['id'] ?>" <?= ($formData ? (getFieldValue('role_id', $formData) == $r['id']) : ($u['role_id']==$r['id'])) ? 'selected' : '' ?>>
                                <?= $r['name'] ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="text-danger d-block mt-2" style="<?= getFieldError('role_id', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('role_id', $validationErrors) ?></small>
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

              <!-- Modal xem chi tiết user -->
              <div class="modal fade" id="detailUserModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Chi tiết User</h5>
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>ID</label>
                            <input type="text" class="form-control" value="<?= $u['id'] ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Trạng thái</label>
                            <input type="text" class="form-control" value="<?= $u['status'] == 'active' ? 'Active' : 'Inactive' ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Họ Tên</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Email</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Vai trò</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['role']) ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Ngày tạo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($u['created_at']) ?>" readonly>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    </div>
                  </div>
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
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">Đầu tiên</a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Trước</a>
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
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                  </li>
                <?php endif;
              endfor;
              
              if ($endPage < $totalPages): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sau</a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">Cuối cùng</a>
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
      <form action="process/user_management.php" method="POST" novalidate>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Thêm User Mới</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Tên đăng nhập</label>
              <input type="text" class="form-control <?= getFieldError('username', $validationErrors) ? 'is-invalid' : '' ?>" name="username" value="<?= htmlspecialchars(getFieldValue('username', $formData)) ?>" data-field="username">
              <small class="text-danger d-block mt-2" style="<?= getFieldError('username', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('username', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Họ Tên</label>
              <input type="text" class="form-control <?= getFieldError('full_name', $validationErrors) ? 'is-invalid' : '' ?>" name="full_name" value="<?= htmlspecialchars(getFieldValue('full_name', $formData)) ?>" data-field="full_name">
              <small class="text-danger d-block mt-2" style="<?= getFieldError('full_name', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('full_name', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="text" inputmode="email" class="form-control <?= getFieldError('email', $validationErrors) ? 'is-invalid' : '' ?>" name="email" value="<?= htmlspecialchars(getFieldValue('email', $formData)) ?>" data-field="email">
              <small class="text-danger d-block mt-2" style="<?= getFieldError('email', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('email', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Số điện thoại</label>
              <input type="text" class="form-control <?= getFieldError('phone', $validationErrors) ? 'is-invalid' : '' ?>" name="phone" value="<?= htmlspecialchars(getFieldValue('phone', $formData)) ?>" placeholder="Nhập số bắt đầu bằng 0 hoặc +84" data-field="phone">
              <small class="text-danger d-block mt-2" style="<?= getFieldError('phone', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('phone', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Mật khẩu</label>
              <div class="input-group">
                <input type="password" class="form-control password-input <?= getFieldError('password', $validationErrors) ? 'is-invalid' : '' ?>" name="password" placeholder="Nhập mật khẩu" data-field="password">
                <div class="input-group-append">
                  <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
              <small class="text-danger d-block mt-2" style="<?= getFieldError('password', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Xác nhận lại mật khẩu</label>
              <div class="input-group">
                <input type="password" class="form-control password-confirm <?= getFieldError('password_confirm', $validationErrors) ? 'is-invalid' : '' ?>" name="password_confirm" placeholder="Xác nhận mật khẩu" data-field="password_confirm">
                <div class="input-group-append">
                  <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
              <small class="text-danger d-block mt-2" style="<?= getFieldError('password_confirm', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password_confirm', $validationErrors) ?></small>
            </div>
            <div class="form-group">
              <label>Vai trò</label>
              <select class="form-control <?= getFieldError('role_id', $validationErrors) ? 'is-invalid' : '' ?>" name="role_id" data-field="role_id">
                <option value="">-- Chọn vai trò --</option>
                <?php foreach($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= getFieldValue('role_id', $formData) == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-danger d-block mt-2" style="<?= getFieldError('role_id', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('role_id', $validationErrors) ?></small>
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

<!-- Modal edit user với validation error -->
<?php if ($editUserData && !empty($validationErrors)): ?>
<div class="modal fade show" id="errorEditModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
  <div class="modal-dialog">
    <form action="process/user_management.php" method="POST" novalidate>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Sửa User</h5>
          <button type="button" class="close" onclick="document.body.classList.remove('modal-open'); window.location.href='users.php'">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $editUserData['id'] ?>">

          <div class="form-group">
            <label>Tên đăng nhập (Không thể sửa)</label>
            <input type="text" class="form-control" value="<?= $editUserData['username'] ?>" disabled>
          </div>
          <div class="form-group">
            <label>Họ Tên</label>
            <input type="text" class="form-control <?= getFieldError('full_name', $validationErrors) ? 'is-invalid' : '' ?>" name="full_name" value="<?= htmlspecialchars(getFieldValue('full_name', $formData, $editUserData['full_name'] ?? '')) ?>" data-field="full_name">
            <small class="text-danger d-block mt-2" style="<?= getFieldError('full_name', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('full_name', $validationErrors) ?></small>
          </div>
          <div class="form-group">
            <label>Email (Không thể sửa)</label>
            <input type="text" inputmode="email" class="form-control" value="<?= $editUserData['email'] ?>" disabled>
          </div>
          <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" class="form-control <?= getFieldError('phone', $validationErrors) ? 'is-invalid' : '' ?>" name="phone" value="<?= htmlspecialchars(getFieldValue('phone', $formData, $editUserData['phone'] ?? '')) ?>" placeholder="Nhập số bắt đầu bằng 0 hoặc +84" data-field="phone">
            <small class="text-danger d-block mt-2" style="<?= getFieldError('phone', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('phone', $validationErrors) ?></small>
          </div>
          <div class="form-group">
            <label>Mật khẩu (để trống nếu không đổi)</label>
            <div class="input-group">
              <input type="password" class="form-control password-input <?= getFieldError('password', $validationErrors) ? 'is-invalid' : '' ?>" name="password" placeholder="Nhập mật khẩu mới" data-field="password">
              <div class="input-group-append">
                <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>
            <small class="text-danger d-block mt-2" style="<?= getFieldError('password', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password', $validationErrors) ?></small>
          </div>
          <div class="form-group">
            <label>Xác nhận lại mật khẩu</label>
            <div class="input-group">
              <input type="password" class="form-control password-confirm <?= getFieldError('password_confirm', $validationErrors) ? 'is-invalid' : '' ?>" name="password_confirm" placeholder="Xác nhận mật khẩu" data-field="password_confirm">
              <div class="input-group-append">
                <span class="input-group-text js-toggle-password" role="button" tabindex="0" aria-label="Hiện/ẩn mật khẩu">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>
            <small class="text-danger d-block mt-2" style="<?= getFieldError('password_confirm', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('password_confirm', $validationErrors) ?></small>
          </div>
          <div class="form-group">
            <label>Vai trò</label>
            <select class="form-control <?= getFieldError('role_id', $validationErrors) ? 'is-invalid' : '' ?>" name="role_id" data-field="role_id">
              <?php foreach($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($formData ? (getFieldValue('role_id', $formData) == $r['id']) : ($editUserData['role_id']==$r['id'])) ? 'selected' : '' ?>>
                  <?= $r['name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-danger d-block mt-2" style="<?= getFieldError('role_id', $validationErrors) ? 'display: block;' : 'display: none;' ?>"><?= getFieldError('role_id', $validationErrors) ?></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.body.classList.remove('modal-open'); window.location.href='users.php'">Đóng</button>
          <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>

<style>
.is-invalid {
  border-color: #dc3545 !important;
  background-color: #fff5f5;
}

.is-invalid:focus {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.text-danger {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

small.text-danger {
  display: block;
  font-weight: 500;
}

/* Modal scrollable body - apply to all modals */
.modal-body {
  max-height: calc(100vh - 200px);
  overflow-y: auto !important;
  padding: 1.5rem;
}

/* Modal error backdrop */
#errorEditModal {
  z-index: 2000 !important;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
}

/* Lock body scroll when modal is shown */
body.modal-open {
  overflow: hidden;
}

#errorEditModal .modal-dialog {
  position: relative;
  margin-top: 1.75rem;
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 0.5rem;
  max-width: 500px;
}

/* Eye icon clickable and visible */
.input-group-text {
  cursor: pointer !important;
  pointer-events: auto !important;
  background-color: #e9ecef;
  border: 1px solid #ced4da;
  transition: all 0.3s ease;
  padding: 0.375rem 0.75rem;
  user-select: none;
}

.input-group-text:hover {
  background-color: #dee2e6;
}

.input-group-text:active {
  background-color: #c3e6cb;
}

.input-group-text i {
  pointer-events: none;
  cursor: pointer;
}
</style>

<script>
function togglePasswordVisibilityForToggleEl(toggleEl) {
  const inputGroup = toggleEl.closest('.input-group');
  if (!inputGroup) return;

  const input = inputGroup.querySelector('input');
  const iconElement = toggleEl.querySelector('i');
  if (!input || !iconElement) return;

  if (input.type === 'password') {
    input.type = 'text';
    iconElement.classList.remove('fa-eye');
    iconElement.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    iconElement.classList.remove('fa-eye-slash');
    iconElement.classList.add('fa-eye');
  }
}

function togglePasswordDisplay(userId) {
  const group = document.querySelector(`.password-display-group[data-id="${userId}"]`);
  if (!group) return;

  const masked = group.querySelector('.password-masked');
  const actual = group.querySelector('.password-actual');
  const btn = group.querySelector('button');
  const icon = btn ? btn.querySelector('i') : null;
  if (!masked || !actual || !btn || !icon) return;

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

function getErrorContainer(input) {
  return input.closest('.form-group')?.querySelector('small.text-danger') || null;
}

function showError(input, message) {
  const errorMsg = getErrorContainer(input);
  if (errorMsg) {
    errorMsg.textContent = message;
    errorMsg.style.display = 'block';
  }
  input.classList.add('is-invalid');
}

function hideError(input) {
  const errorMsg = getErrorContainer(input);
  if (errorMsg) {
    errorMsg.textContent = '';
    errorMsg.style.display = 'none';
  }
  input.classList.remove('is-invalid');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
  if (!phone) return true;
  return /^(?:\+84\d{9}|0\d{9,10})$/.test(phone);
}

function validateField(input, isAddForm = true) {
  const fieldName = input.getAttribute('data-field');
  const value = input.value.trim();
  const form = input.closest('form');
  const actionType = form?.querySelector('input[name="action"]')?.value || '';
  const effectiveIsAddForm = actionType === 'add' || isAddForm;

  if (!fieldName) return true;
  hideError(input);

  if (fieldName === 'username') {
    if (effectiveIsAddForm && !value) return showError(input, 'Vui lòng nhập tên đăng nhập'), false;
    if (value && value.length < 3) return showError(input, 'Tên đăng nhập phải có ít nhất 3 ký tự'), false;
  }

  if (fieldName === 'full_name') {
    if (!value) return showError(input, 'Vui lòng nhập họ tên'), false;
    if (value.length < 2) return showError(input, 'Họ tên phải có ít nhất 2 ký tự'), false;
  }

  if (fieldName === 'email') {
    if (effectiveIsAddForm && !value) return showError(input, 'Vui lòng nhập email'), false;
    if (value && !isValidEmail(value)) return showError(input, 'Email không hợp lệ'), false;
  }

  if (fieldName === 'phone') {
    if (!isValidPhone(value)) return showError(input, 'Vui lòng nhập số điện thoại bắt đầu bằng 0 hoặc +84 và phải có 10-11 số'), false;
  }

  if (fieldName === 'password') {
    if (actionType === 'add' && !value) return showError(input, 'Vui lòng nhập mật khẩu'), false;
    if (value && value.length < 6) return showError(input, 'Mật khẩu phải có ít nhất 6 ký tự'), false;
  }

  if (fieldName === 'password_confirm') {
    const passwordInput = form ? form.querySelector('input[name="password"]') : null;
    if (actionType === 'add' && !value) return showError(input, 'Vui lòng xác nhận mật khẩu'), false;
    if (passwordInput && value && passwordInput.value !== value) return showError(input, 'Mật khẩu xác nhận không khớp'), false;
  }

  if (fieldName === 'role_id') {
    if (!value) return showError(input, 'Vui lòng chọn vai trò'), false;
  }

  return true;
}

document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('invalid', function(e) {
    const form = e.target && e.target.closest ? e.target.closest('form') : null;
    if (form && form.hasAttribute('novalidate')) {
      e.preventDefault();
    }
  }, true);

  document.addEventListener('click', function(e) {
    const toggleEl = e.target.closest ? e.target.closest('.js-toggle-password') : null;
    if (!toggleEl) return;
    e.preventDefault();
    togglePasswordVisibilityForToggleEl(toggleEl);
  });

  document.addEventListener('keydown', function(e) {
    const toggleEl = e.target.closest ? e.target.closest('.js-toggle-password') : null;
    if (!toggleEl) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      togglePasswordVisibilityForToggleEl(toggleEl);
    }
  });

  document.addEventListener('blur', function(e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-field')) {
      const form = e.target.closest('form');
      const actionType = form?.querySelector('input[name="action"]')?.value || '';
      validateField(e.target, actionType === 'add');
    }
  }, true);

  document.addEventListener('input', function(e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-field')) {
      const form = e.target.closest('form');
      const actionType = form?.querySelector('input[name="action"]')?.value || '';
      validateField(e.target, actionType === 'add');
    }
  }, true);

  document.addEventListener('change', function(e) {
    if (e.target.tagName === 'SELECT' && e.target.hasAttribute && e.target.hasAttribute('data-field')) {
      const form = e.target.closest('form');
      const actionType = form?.querySelector('input[name="action"]')?.value || '';
      validateField(e.target, actionType === 'add');
    }
  }, true);

  const hasValidationErrors = document.querySelectorAll('.is-invalid').length > 0;
  const errorEditModal = document.getElementById('errorEditModal');
  const addUserModal = document.getElementById('addUserModal');

  if (hasValidationErrors) {
    if (errorEditModal) {
      errorEditModal.style.display = 'block';
      errorEditModal.classList.add('show');
      document.body.classList.add('modal-open');
    } else if (addUserModal) {
      $(addUserModal).modal('show');
    }
  }
});

document.addEventListener('submit', function(e) {
  if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return;

  const form = e.target;
  const actionType = form.querySelector('input[name="action"]')?.value || '';
  const isAddForm = actionType === 'add';
  const fields = form.querySelectorAll('[data-field]');
  let isValid = true;

  fields.forEach(function(field) {
    if (!validateField(field, isAddForm)) {
      isValid = false;
    }
  });

  if (!isValid) {
    e.preventDefault();
  }
});
</script>
