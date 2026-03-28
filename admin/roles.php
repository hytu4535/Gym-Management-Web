<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Vai trò";

// kiểm tra đăng nhập
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

$validationErrors = $_SESSION['validation_errors'] ?? [];
$generalMessage = '';
if (!empty($validationErrors) && isset($validationErrors['general'])) {
  $generalMessage = $validationErrors['general'];
}
unset($_SESSION['validation_errors']);

$filterName = trim((string) ($_GET['name'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') {
  $whereClauses[] = 'name LIKE ?';
  $whereParams[] = '%' . $filterName . '%';
}
if ($filterStatus !== '') {
  $whereClauses[] = 'status = ?';
  $whereParams[] = $filterStatus;
}
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Lấy danh sách roles
$sql = "SELECT * FROM roles" . $whereSql . " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($whereParams);
$roles = $stmt->fetchAll();
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Quản lý Vai trò</h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($generalMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($generalMessage) ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>
      <?php
        $filterMode = 'server';
        $filterAction = 'roles.php';
        $filterFieldsHtml = '
          <div class="col-md-4">
            <div class="form-group mb-0">
              <label>Tên vai trò</label>
              <input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Nhập tên vai trò">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-0">
              <label>Trạng thái</label>
              <select name="status" class="form-control">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active" ' . ($filterStatus === 'active' ? 'selected' : '') . '>Active</option>
                <option value="inactive" ' . ($filterStatus === 'inactive' ? 'selected' : '') . '>Inactive</option>
              </select>
            </div>
          </div>
        ';
        $filterAction = 'roles.php';
        include 'layout/filter-card.php';
      ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Danh sách Vai trò</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRoleModal">
              <i class="fas fa-plus"></i> Thêm Vai trò
            </button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped js-admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên vai trò</th>
                <th>Mô tả</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($roles as $r): ?>
              <tr>
                <td><?= $r['id'] ?></td>
                <td><?= $r['name'] ?></td>
                <td><?= $r['description'] ?></td>
                <td>
                  <?php if($r['status']=='active'): ?>
                    <span class="badge badge-success">Active</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <!-- Nút sửa mở modal -->
                  <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editRoleModal<?= $r['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Nút xoá -->
                  <a href="process/role_management.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa vai trò này?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>

              <!-- Modal sửa role -->
              <div class="modal fade" id="editRoleModal<?= $r['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <form action="process/role_management.php" method="POST">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Sửa Vai trò</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">

                        <div class="form-group">
                          <label>Tên vai trò</label>
                          <input type="text" class="form-control" name="name" value="<?= $r['name'] ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Mô tả</label>
                          <input type="text" class="form-control" name="description" value="<?= $r['description'] ?>">
                        </div>
                        <div class="form-group">
                          <label>Trạng thái</label>
                          <select class="form-control" name="status" required>
                            <option value="active" <?= $r['status']=='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= $r['status']=='inactive'?'selected':'' ?>>Inactive</option>
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

  <!-- Modal thêm role -->
  <div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="process/role_management.php" method="POST">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Thêm Vai trò Mới</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Tên vai trò</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group">
              <label>Mô tả</label>
              <input type="text" class="form-control" name="description">
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
