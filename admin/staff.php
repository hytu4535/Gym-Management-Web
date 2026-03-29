<?php
session_start();
$page_title = "Quản lý Staff";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('MANAGE_STAFF');

$db = getDB();
$hasDepartmentIdColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'department_id' LIMIT 1")->fetchColumn();
$hasUserFullNameColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'full_name' LIMIT 1")->fetchColumn();

// Bộ lọc danh sách
$filterName = trim((string) ($_GET['name'] ?? ''));
$filterEmail = trim((string) ($_GET['email'] ?? ''));
$filterPhone = trim((string) ($_GET['phone'] ?? ''));
$filterPosition = trim((string) ($_GET['position'] ?? ''));
$filterDepartment = trim((string) ($_GET['department_id'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$whereParams = [];

if ($filterName !== '') {
    $whereClauses[] = 's.full_name LIKE ?';
    $whereParams[] = '%' . $filterName . '%';
}

if ($filterEmail !== '') {
    $whereClauses[] = 'u.email LIKE ?';
    $whereParams[] = '%' . $filterEmail . '%';
}

if ($filterPhone !== '') {
    $whereClauses[] = '(u.phone LIKE ? OR s.phone LIKE ?)';
    $whereParams[] = '%' . $filterPhone . '%';
    $whereParams[] = '%' . $filterPhone . '%';
}

if ($filterPosition !== '') {
    $whereClauses[] = 's.position LIKE ?';
    $whereParams[] = '%' . $filterPosition . '%';
}

if ($hasDepartmentIdColumn && $filterDepartment !== '' && ctype_digit($filterDepartment)) {
    $whereClauses[] = 's.department_id = ?';
    $whereParams[] = (int) $filterDepartment;
}

if ($filterStatus !== '') {
    $whereClauses[] = 's.status = ?';
    $whereParams[] = $filterStatus;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$departmentSelectSql = $hasDepartmentIdColumn
  ? 's.department_id, d.name AS department_name'
  : 'NULL AS department_id, NULL AS department_name';
$departmentJoinSql = $hasDepartmentIdColumn
  ? ' LEFT JOIN departments d ON s.department_id = d.id'
  : '';

$stmt = $db->prepare(
  "SELECT s.id, s.users_id, s.full_name, s.position, $departmentSelectSql, s.status, u.username, u.email, u.phone, u.role_id AS linked_role_id
   FROM staff s
   LEFT JOIN users u ON s.users_id = u.id" . $departmentJoinSql . $whereSql . " ORDER BY s.id DESC"
);
$stmt->execute($whereParams);
$staffList = $stmt->fetchAll();
$usedUserIds = array_map('intval', $db->query("SELECT users_id FROM staff")->fetchAll(PDO::FETCH_COLUMN));

$permissionModules = [
  'MANAGE_STAFF' => 'Quản lí nhân viên',
  'MANAGE_MEMBERS' => 'Quản lí hội viên',
  'MANAGE_PACKAGES' => 'Quản lí gói tập',
  'MANAGE_TRAINERS' => 'Quản lí luyện tập',
  'MANAGE_SERVICES_NUTRITION' => 'QL dịch vụ và dinh dưỡng',
  'MANAGE_SALES' => 'Quản lí bán hàng',
  'MANAGE_INVENTORY' => 'Quản lí kho',
  'MANAGE_EQUIPMENT' => 'Quản lí thiết bị',
  'MANAGE_FEEDBACK' => 'Phản hồi và thông báo',
  'VIEW_REPORTS' => 'Báo cáo thống kê',
  'MANAGE_ALL' => 'Quản lí tài khoản',
];

$permissionActions = [
  'view' => 'Xem',
  'edit' => 'Sửa',
  'delete' => 'Xóa',
  'add' => 'Thêm',
];

$staffPermissionMap = [];
$staffPermissionModalData = [];
$staffUserIds = [];
foreach ($staffList as $staffRow) {
  $uid = (int) ($staffRow['users_id'] ?? 0);
  if ($uid > 0) {
    $staffUserIds[] = $uid;
  }
}

$staffUserIds = array_values(array_unique($staffUserIds));
if (!empty($staffUserIds)) {
  $placeholders = implode(',', array_fill(0, count($staffUserIds), '?'));
  $permStmt = $db->prepare("SELECT user_id, permission_code, can_view, can_add, can_edit, can_delete FROM user_permissions WHERE user_id IN ($placeholders)");
  $permStmt->execute($staffUserIds);
  $permissionRows = $permStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($permissionRows as $row) {
    $uid = (int) ($row['user_id'] ?? 0);
    $code = (string) ($row['permission_code'] ?? '');
    if ($uid <= 0 || $code === '') {
      continue;
    }

    $staffPermissionMap[$uid][$code] = [
      'view' => (int) ($row['can_view'] ?? 0) === 1,
      'add' => (int) ($row['can_add'] ?? 0) === 1,
      'edit' => (int) ($row['can_edit'] ?? 0) === 1,
      'delete' => (int) ($row['can_delete'] ?? 0) === 1,
    ];
  }
}

foreach ($staffList as $staffRow) {
  $uid = (int) ($staffRow['users_id'] ?? 0);
  if ($uid <= 0) {
    continue;
  }

  $staffPermissionModalData[$uid] = [
    'user_id' => $uid,
    'username' => (string) ($staffRow['username'] ?? ''),
    'full_name' => (string) ($staffRow['full_name'] ?? ''),
    'linked_role_id' => (int) ($staffRow['linked_role_id'] ?? 0),
    'permissions' => $staffPermissionMap[$uid] ?? [],
  ];
}

$usersNameSelect = $hasUserFullNameColumn ? 'full_name' : 'username AS full_name';
$users = $db->query("SELECT id, username, $usersNameSelect, email, phone FROM users ORDER BY email ASC, username ASC")->fetchAll();
$roles = $db->query("SELECT id, name, description FROM roles WHERE status = 'active' ORDER BY id ASC")->fetchAll();
$departments = $hasDepartmentIdColumn ? $db->query("SELECT id, name FROM departments ORDER BY id ASC")->fetchAll() : [];

function staffStatusLabel($status)
{
    switch ($status) {
        case 'active':
            return ['label' => 'Đang làm', 'class' => 'success'];
        case 'inactive':
            return ['label' => 'Đã nghỉ việc', 'class' => 'secondary'];
        case 'on_leave':
            return ['label' => 'Tạm nghỉ', 'class' => 'warning'];
        default:
            return ['label' => 'Không xác định', 'class' => 'dark'];
    }
}

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Quản lý Staff</h1>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php
        $filterMode = 'server';
        $filterAction = 'staff.php';
        $filterFieldsHtml = '
          <div class="col-md-2"><div class="form-group mb-0"><label>Họ tên</label><input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Họ tên"></div></div>
          <div class="col-md-2"><div class="form-group mb-0"><label>Email</label><input type="text" name="email" class="form-control" value="' . htmlspecialchars($filterEmail) . '" placeholder="Email"></div></div>
          <div class="col-md-2"><div class="form-group mb-0"><label>SĐT</label><input type="text" name="phone" class="form-control" value="' . htmlspecialchars($filterPhone) . '" placeholder="SĐT"></div></div>
          <div class="col-md-2"><div class="form-group mb-0"><label>Chức vụ</label><input type="text" name="position" class="form-control" value="' . htmlspecialchars($filterPosition) . '" placeholder="Chức vụ"></div></div>';
        if ($hasDepartmentIdColumn) {
            $filterFieldsHtml .= '<div class="col-md-2"><div class="form-group mb-0"><label>Phòng ban</label><select name="department_id" class="form-control"><option value="">-- Tất cả --</option>';
            foreach ($departments as $departmentOption) {
                $selected = $filterDepartment !== '' && (int) $filterDepartment === (int) $departmentOption['id'] ? 'selected' : '';
                $filterFieldsHtml .= '<option value="' . (int) $departmentOption['id'] . '" ' . $selected . '>' . htmlspecialchars($departmentOption['name']) . '</option>';
            }
            $filterFieldsHtml .= '</select></div></div>';
        }
        $filterFieldsHtml .= '<div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="active" ' . ($filterStatus === 'active' ? 'selected' : '') . '>Đang làm</option><option value="inactive" ' . ($filterStatus === 'inactive' ? 'selected' : '') . '>Đã nghỉ việc</option><option value="on_leave" ' . ($filterStatus === 'on_leave' ? 'selected' : '') . '>Tạm nghỉ</option></select></div></div>';
        include 'layout/filter-card.php';
      ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Danh sách Staff</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#staffModal" onclick="resetStaffForm()">
              <i class="fas fa-plus"></i> Thêm Staff
            </button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped js-admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Chức vụ</th>
                <?php if ($hasDepartmentIdColumn): ?><th>Phòng ban</th><?php endif; ?>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($staffList)): ?>
                <?php foreach ($staffList as $staff): ?>
                  <?php
                    $statusInfo = staffStatusLabel($staff['status'] ?? '');
                    $staffJson = json_encode($staff, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                  ?>
                  <tr>
                    <td><?php echo (int) $staff['id']; ?></td>
                    <td><?php echo htmlspecialchars($staff['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($staff['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($staff['phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($staff['position'] ?? ''); ?></td>
                    <?php if ($hasDepartmentIdColumn): ?><td><?php echo htmlspecialchars($staff['department_name'] ?? ''); ?></td><?php endif; ?>
                    <td><span class="badge badge-<?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span></td>
                    <td>
                      <?php $linkedUserId = (int) ($staff['users_id'] ?? 0); ?>
                      <?php if ($linkedUserId > 0): ?>
                        <button type="button" class="btn btn-secondary btn-sm js-open-staff-user-permissions" data-user-id="<?php echo $linkedUserId; ?>" title="Phân quyền user">
                          <i class="fas fa-key"></i>
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-sm" title="Không có tài khoản liên kết" disabled>
                          <i class="fas fa-key"></i>
                        </button>
                      <?php endif; ?>
                      <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#staffModal" onclick='editStaff(<?php echo $staffJson; ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="process/staff_management.php?action=delete&id=<?php echo (int) $staff['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa staff này?');">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="<?php echo $hasDepartmentIdColumn ? '8' : '7'; ?>" class="text-center">Chưa có staff nào.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="staffModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="staffModalTitle">Thêm Staff Mới</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="process/staff_management.php" method="POST" id="staffForm" novalidate>
        <input type="hidden" name="action" id="staff_action" value="add">
        <input type="hidden" name="id" id="staff_id" value="">
        <input type="hidden" id="staff_form_mode" value="add">
        <div class="modal-body">
          <div class="form-group">
            <label>Tài khoản / Email</label>
            <select name="users_id" id="users_id" class="form-control select2bs4" required data-placeholder="--- Chọn tài khoản ---" style="width: 100%;">
              <option value="">--- Chọn tài khoản ---</option>
              <?php foreach ($users as $user): ?>
                <?php
                  $userLabel = trim((string) ($user['username'] ?? '')) . ' / ' . trim((string) ($user['email'] ?? ''));
                  $isUsed = in_array((int) $user['id'], $usedUserIds, true);
                ?>
                <option value="<?php echo (int) $user['id']; ?>" data-used="<?php echo $isUsed ? '1' : '0'; ?>" data-name="<?php echo htmlspecialchars((string) ($user['full_name'] ?? ''), ENT_QUOTES); ?>" data-phone="<?php echo htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES); ?>">
                  <?php echo htmlspecialchars($userLabel); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small id="users_id_error" class="text-danger d-none">Tài khoản / email này đã được dùng trong bảng staff.</small>
          </div>
          <div class="form-group">
            <label>Họ tên</label>
            <input type="text" id="full_name" class="form-control" readonly>
            <small class="text-muted">Họ tên được tự động lấy theo tài khoản / email đã chọn.</small>
          </div>
          <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" id="phone_display" class="form-control" readonly>
            <small class="text-muted">Số điện thoại được tự động lấy theo tài khoản / email đã chọn.</small>
          </div>
          <div class="form-group">
            <label>Chức vụ</label>
            <select name="position" id="position" class="form-control" required>
              <option value="">--- Chọn chức vụ ---</option>
              <?php foreach ($roles as $role): ?>
                <?php $roleLabel = $role['name'] . (!empty($role['description']) ? ' - ' . $role['description'] : ''); ?>
                <option value="<?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($hasDepartmentIdColumn): ?>
            <div class="form-group">
              <label>Phòng ban</label>
              <select name="department_id" id="department_id" class="form-control" required>
                <option value="">--- Chọn phòng ban ---</option>
                <?php foreach ($departments as $department): ?>
                  <option value="<?php echo (int) $department['id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="status" class="form-control" required>
              <option value="active">Đang làm</option>
              <option value="inactive">Đã nghỉ việc</option>
              <option value="on_leave">Tạm nghỉ</option>
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

<div class="modal fade" id="staffUserPermissionModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form action="process/user_management.php" method="POST" id="staffUserPermissionForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="staffUserPermissionModalTitle">Phân quyền user</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_permissions">
          <input type="hidden" name="user_id" id="staff_permission_user_id" value="">

          <div class="alert alert-info mb-3 d-none" id="staffUserPermissionAdminNotice">
            Tài khoản Admin là vai trò cao nhất và luôn có toàn bộ quyền.
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
              <thead>
                <tr>
                  <th>Chức năng</th>
                  <?php foreach ($permissionActions as $actionLabel): ?>
                    <th class="text-center"><?php echo htmlspecialchars($actionLabel); ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($permissionModules as $permCode => $permLabel): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($permLabel); ?></strong></td>
                    <?php foreach ($permissionActions as $actionKey => $actionLabel): ?>
                      <td class="text-center">
                        <input type="checkbox" class="js-staff-user-permission-checkbox" name="permissions[<?php echo htmlspecialchars($permCode); ?>][<?php echo htmlspecialchars($actionKey); ?>]" value="1" data-perm-code="<?php echo htmlspecialchars($permCode); ?>" data-action-key="<?php echo htmlspecialchars($actionKey); ?>">
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
          <button type="submit" class="btn btn-primary" id="staffUserPermissionSubmitBtn">Lưu phân quyền</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
  const staffUserPermissionModalData = <?php echo json_encode($staffPermissionModalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  function openStaffUserPermissionModal(userId) {
    var modalData = staffUserPermissionModalData[String(userId)] || staffUserPermissionModalData[userId] || null;
    if (!modalData) {
      return;
    }

    var isAdminUser = Number(modalData.linked_role_id || 0) === 4;
    var modalTitle = document.getElementById('staffUserPermissionModalTitle');
    var userIdInput = document.getElementById('staff_permission_user_id');
    var adminNotice = document.getElementById('staffUserPermissionAdminNotice');
    var submitBtn = document.getElementById('staffUserPermissionSubmitBtn');
    var checkboxes = document.querySelectorAll('#staffUserPermissionModal .js-staff-user-permission-checkbox');

    if (modalTitle) {
      var name = modalData.full_name || modalData.username || 'User';
      modalTitle.textContent = 'Phân quyền: ' + name;
    }
    if (userIdInput) {
      userIdInput.value = modalData.user_id || '';
    }

    if (adminNotice) {
      adminNotice.classList.toggle('d-none', !isAdminUser);
    }
    if (submitBtn) {
      submitBtn.style.display = isAdminUser ? 'none' : 'inline-block';
    }

    checkboxes.forEach(function(checkbox) {
      var permCode = checkbox.getAttribute('data-perm-code') || '';
      var actionKey = checkbox.getAttribute('data-action-key') || '';
      var currentPerm = (modalData.permissions && modalData.permissions[permCode]) ? modalData.permissions[permCode] : null;
      var checked = isAdminUser ? true : !!(currentPerm && currentPerm[actionKey]);
      checkbox.checked = checked;
      checkbox.disabled = isAdminUser;
    });

    $('#staffUserPermissionModal').modal('show');
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-open-staff-user-permissions').forEach(function(button) {
      button.addEventListener('click', function () {
        openStaffUserPermissionModal(this.getAttribute('data-user-id'));
      });
    });
  });

  function initStaffAccountSelect2() {
    if (!$.fn.select2) {
      return;
    }

    var $select = $('#users_id');
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      theme: 'bootstrap4',
      width: '100%',
      placeholder: '--- Chọn tài khoản ---',
      dropdownParent: $('#staffModal'),
      allowClear: true
    });
  }

  function resetStaffForm() {
    document.getElementById('staffModalTitle').innerText = 'Thêm Staff Mới';
    document.getElementById('staff_action').value = 'add';
    document.getElementById('staff_id').value = '';
    document.getElementById('staff_form_mode').value = 'add';
    $('#users_id').prop('disabled', false).val(null).trigger('change');
    $('#position').val('').trigger('change');
    if ($('#department_id').length) {
      $('#department_id').val('').trigger('change');
    }
    $('#status').val('active').trigger('change');
    document.getElementById('full_name').value = '';
    document.getElementById('phone_display').value = '';
    clearUserValidation();
  }

  function editStaff(staff) {
    document.getElementById('staffModalTitle').innerText = 'Chỉnh sửa Staff';
    document.getElementById('staff_action').value = 'edit';
    document.getElementById('staff_id').value = staff.id || '';
    document.getElementById('staff_form_mode').value = 'edit';
    $('#users_id').prop('disabled', true).val(String(staff.users_id || '')).trigger('change');
    $('#position').val(staff.position || '').trigger('change');
    if ($('#department_id').length) {
      $('#department_id').val(staff.department_id || '').trigger('change');
    }
    $('#status').val(staff.status || 'active').trigger('change');
    syncSelectedUserInfo();
    clearUserValidation();
  }

  function clearUserValidation() {
    var error = document.getElementById('users_id_error');
    if (error) {
      error.classList.add('d-none');
    }
    $('#users_id').removeClass('is-invalid');
  }

  function syncSelectedUserInfo() {
    var userSelect = document.getElementById('users_id');
    var selectedOption = userSelect && userSelect.options[userSelect.selectedIndex] ? userSelect.options[userSelect.selectedIndex] : null;
    var fullNameInput = document.getElementById('full_name');
    var phoneInput = document.getElementById('phone_display');
    var error = document.getElementById('users_id_error');
    var formMode = document.getElementById('staff_form_mode').value || 'add';
    var isUsed = selectedOption && selectedOption.getAttribute('data-used') === '1';

    fullNameInput.value = selectedOption ? (selectedOption.getAttribute('data-name') || '') : '';
    phoneInput.value = selectedOption ? (selectedOption.getAttribute('data-phone') || '') : '';

    if (formMode === 'add' && userSelect && !userSelect.disabled && userSelect.value && isUsed) {
      error.classList.remove('d-none');
      $('#users_id').addClass('is-invalid');
      return false;
    }

    clearUserValidation();
    return true;
  }

  $('#users_id').on('change', function () {
    syncSelectedUserInfo();
  });

  $('#staffModal').on('shown.bs.modal', function () {
    initStaffAccountSelect2();
    syncSelectedUserInfo();
  });

  $('#staffForm').on('submit', function (event) {
    if (!syncSelectedUserInfo()) {
      event.preventDefault();
      return false;
    }

    if (document.getElementById('staff_form_mode').value === 'add' && !$('#users_id').val()) {
      event.preventDefault();
      $('#users_id').addClass('is-invalid');
      document.getElementById('users_id_error').textContent = 'Vui lòng chọn tài khoản / email.';
      document.getElementById('users_id_error').classList.remove('d-none');
      return false;
    }
  });

  $('#staffModal').on('hidden.bs.modal', function () {
    resetStaffForm();
  });
</script>