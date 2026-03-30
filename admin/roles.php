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

$permissionLabels = [
  'MANAGE_ALL' => 'Quản lí người dùng',
  'MANAGE_STAFF' => 'Quản lí nhân viên',
  'MANAGE_MEMBERS' => 'Quản lí hội viên',
  'MANAGE_PACKAGES' => 'Quản lí gói tập',
  'MANAGE_TRAINERS' => 'Quản lí luyện tập',
  'MANAGE_SERVICES_NUTRITION' => 'Quản lí dịch vụ dinh dưỡng',
  'MANAGE_SALES' => 'Quản lí bán hàng',
  'MANAGE_INVENTORY' => 'Quản lí kho',
  'MANAGE_EQUIPMENT' => 'Quản lí thiết bị',
  'MANAGE_FEEDBACK' => 'Phản hồi thông báo',
  'MANAGE_PROMOTIONS' => 'Quản lí ưu đãi',
  'VIEW_REPORTS' => 'Báo cáo thống kê',
];

$permissionPageScopes = [
  'MANAGE_ALL' => 'Người dùng, Vai trò',
  'MANAGE_STAFF' => 'Nhân viên',
  'MANAGE_MEMBERS' => 'Hội viên, Gói tập hội viên, Hạng hội viên, Địa chỉ, Máy đo BMI',
  'MANAGE_PACKAGES' => 'Quản lí gói tập',
  'MANAGE_TRAINERS' => 'Huấn luyện viên, Lớp tập, Lịch tập',
  'MANAGE_SERVICES_NUTRITION' => 'Dịch vụ, Gán dịch vụ hội viên, Dinh dưỡng, Danh sách món ăn, Món trong thực đơn, Gán dinh dưỡng hội viên',
  'MANAGE_SALES' => 'Sản phẩm, Danh mục, Đơn hàng, Chi tiết đơn hàng, Giỏ hàng',
  'MANAGE_INVENTORY' => 'Phiếu nhập, Phiếu xuất, Nhà cung cấp',
  'MANAGE_EQUIPMENT' => 'Thiết bị, Bảo trì thiết bị',
  'MANAGE_FEEDBACK' => 'Phản hồi, Thông báo, Tiếp nhận liên hệ',
  'MANAGE_PROMOTIONS' => 'Khuyến mãi theo hạng, Lịch sử sử dụng',
  'VIEW_REPORTS' => 'Báo cáo thống kê',
];

$permissionActions = [
  'view' => 'Xem',
  'add' => 'Thêm',
  'edit' => 'Sửa',
  'delete' => 'Xóa',
];

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
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rolePermissionModalData = [];
$roleIds = array_map('intval', array_column($roles, 'id'));

$permissionCodes = array_keys($permissionLabels);
$hasPermissionsTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions' LIMIT 1")->fetchColumn();
if ($hasPermissionsTable) {
  $permissionRows = $db->query("SELECT id, code FROM permissions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($permissionRows as $permissionRow) {
    $code = (string) ($permissionRow['code'] ?? '');
    if ($code === '' || in_array($code, $permissionCodes, true)) {
      continue;
    }
    $permissionCodes[] = $code;
  }
}

$hasRoleActionPermissionTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_action_permissions' LIMIT 1")->fetchColumn();
$hasRolePermissionsTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' LIMIT 1")->fetchColumn();

foreach ($roles as $role) {
  $roleId = (int) ($role['id'] ?? 0);
  if ($roleId <= 0) {
    continue;
  }

  $rolePermissionModalData[$roleId] = [
    'id' => $roleId,
    'name' => (string) ($role['name'] ?? ''),
    'permissions' => [],
  ];

  foreach ($permissionCodes as $code) {
    $rolePermissionModalData[$roleId]['permissions'][$code] = [
      'view' => false,
      'add' => false,
      'edit' => false,
      'delete' => false,
    ];
  }
}

if (!empty($roleIds)) {
  if ($hasRoleActionPermissionTable) {
    $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
    $stmtRoleActionPerms = $db->prepare("SELECT role_id, permission_code, can_view, can_add, can_edit, can_delete FROM role_action_permissions WHERE role_id IN ($placeholders)");
    $stmtRoleActionPerms->execute($roleIds);
    $rows = $stmtRoleActionPerms->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $roleId = (int) ($row['role_id'] ?? 0);
      $permissionCode = (string) ($row['permission_code'] ?? '');
      if ($roleId <= 0 || $permissionCode === '' || !isset($rolePermissionModalData[$roleId]['permissions'][$permissionCode])) {
        continue;
      }

      $rolePermissionModalData[$roleId]['permissions'][$permissionCode] = [
        'view' => (int) ($row['can_view'] ?? 0) === 1,
        'add' => (int) ($row['can_add'] ?? 0) === 1,
        'edit' => (int) ($row['can_edit'] ?? 0) === 1,
        'delete' => (int) ($row['can_delete'] ?? 0) === 1,
      ];
    }
  } elseif ($hasRolePermissionsTable && $hasPermissionsTable) {
    // Fallback cho dữ liệu cũ: role_permissions chỉ có cấp module, nên bật toàn bộ action của module đó.
    $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
    $stmtLegacyPerms = $db->prepare("SELECT rp.role_id, p.code FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id IN ($placeholders)");
    $stmtLegacyPerms->execute($roleIds);
    $rows = $stmtLegacyPerms->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $roleId = (int) ($row['role_id'] ?? 0);
      $permissionCode = (string) ($row['code'] ?? '');
      if ($roleId <= 0 || $permissionCode === '' || !isset($rolePermissionModalData[$roleId]['permissions'][$permissionCode])) {
        continue;
      }

      $rolePermissionModalData[$roleId]['permissions'][$permissionCode] = [
        'view' => true,
        'add' => true,
        'edit' => true,
        'delete' => true,
      ];
    }
  }
}

$permissionModules = [];
foreach ($permissionCodes as $permissionCode) {
  $permissionModules[$permissionCode] = $permissionLabels[$permissionCode] ?? $permissionCode;
}
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
                <td><?= (int) $r['id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars((string) ($r['description'] ?? '')) ?></td>
                <td>
                  <?php if($r['status']=='active'): ?>
                    <span class="badge badge-success">Active</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#rolePermissionModal" onclick="openRolePermissionModal(<?= (int) $r['id'] ?>)">
                    <i class="fas fa-user-shield"></i>
                  </button>
                  <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editRoleModal<?= (int) $r['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <a href="process/role_management.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa vai trò này?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>

              <div class="modal fade" id="editRoleModal<?= (int) $r['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <form action="process/role_management.php" method="POST">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Sửa Vai trò</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">

                        <div class="form-group">
                          <label>Tên vai trò</label>
                          <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($r['name']) ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Mô tả</label>
                          <input type="text" class="form-control" name="description" value="<?= htmlspecialchars((string) ($r['description'] ?? '')) ?>">
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

  <div class="modal fade" id="rolePermissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <form action="process/role_management.php" method="POST" id="rolePermissionForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="rolePermissionModalTitle">Phân quyền vai trò</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="update_permissions">
            <input type="hidden" name="role_id" id="permission_role_id" value="">

            <div class="table-responsive">
              <table class="table table-bordered table-striped mb-0">
                <thead>
                  <tr>
                    <th>Chức năng</th>
                    <?php foreach ($permissionActions as $actionLabel): ?>
                      <th class="text-center"><?= htmlspecialchars($actionLabel) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($permissionModules as $permCode => $permLabel): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($permLabel) ?></strong>
                        <?php if (!empty($permissionPageScopes[$permCode])): ?>
                          <br>
                          <small class="text-muted"><?= htmlspecialchars($permissionPageScopes[$permCode]) ?></small>
                        <?php endif; ?>
                      </td>
                      <?php foreach ($permissionActions as $actionKey => $actionLabel): ?>
                        <td class="text-center">
                          <input type="checkbox" class="js-role-permission-checkbox" name="permissions[<?= htmlspecialchars($permCode) ?>][<?= htmlspecialchars($actionKey) ?>]" value="1" data-perm-code="<?= htmlspecialchars($permCode) ?>" data-action-key="<?= htmlspecialchars($actionKey) ?>">
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Lưu phân quyền</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
const rolePermissionModalData = <?= json_encode($rolePermissionModalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function openRolePermissionModal(roleId) {
  const modalData = rolePermissionModalData[String(roleId)] || rolePermissionModalData[roleId] || null;
  const roleIdInput = document.getElementById('permission_role_id');
  const title = document.getElementById('rolePermissionModalTitle');
  const checkboxes = document.querySelectorAll('.js-role-permission-checkbox');

  if (!roleIdInput || !title) {
    return;
  }

  roleIdInput.value = roleId;
  title.textContent = modalData ? `Phân quyền vai trò: ${modalData.name || ''}` : 'Phân quyền vai trò';

  checkboxes.forEach((checkbox) => {
    checkbox.checked = false;
  });

  if (modalData && modalData.permissions) {
    Object.keys(modalData.permissions).forEach((permCode) => {
      const permissionSet = modalData.permissions[permCode] || {};
      Object.keys(permissionSet).forEach((actionKey) => {
        if (permissionSet[actionKey]) {
          const checkbox = document.querySelector(`.js-role-permission-checkbox[data-perm-code="${permCode}"][data-action-key="${actionKey}"]`);
          if (checkbox) {
            checkbox.checked = true;
          }
        }
      });
    });
  }
}
</script>
