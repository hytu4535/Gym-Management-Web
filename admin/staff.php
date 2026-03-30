<?php
session_start();
$page_title = "Quản lý Staff";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('MANAGE_STAFF');

$resolveStaffActionPermission = static function (string $requiredAction): bool {
  $permissions = $_SESSION['permissions'] ?? [];
  $actionPermissions = $_SESSION['user_action_permissions'] ?? [];

  if (!is_array($permissions)) {
    return false;
  }

  if (!empty($_SESSION['is_admin_role']) || strtolower((string) ($_SESSION['role'] ?? '')) === 'admin') {
    return true;
  }

  $actionKeyMap = [
    'view' => 'view',
    'add' => 'add',
    'create' => 'add',
    'edit' => 'edit',
    'update' => 'edit',
    'delete' => 'delete',
    'remove' => 'delete',
  ];

  $normalizedAction = strtolower(trim($requiredAction));
  $normalizedAction = $actionKeyMap[$normalizedAction] ?? 'view';

  if (is_array($actionPermissions) && !empty($actionPermissions)) {
    if (isset($actionPermissions['MANAGE_STAFF']) && is_array($actionPermissions['MANAGE_STAFF'])) {
      return !empty($actionPermissions['MANAGE_STAFF'][$normalizedAction]);
    }

    return $normalizedAction === 'view' && in_array('MANAGE_STAFF', $permissions, true);
  }

  return $normalizedAction === 'view' && in_array('MANAGE_STAFF', $permissions, true);
};

$canAddStaff = $resolveStaffActionPermission('add');
$canEditStaff = $resolveStaffActionPermission('edit');
$canDeleteStaff = $resolveStaffActionPermission('delete');

$db = getDB();
$hasDepartmentIdColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'department_id' LIMIT 1")->fetchColumn();
$hasUserFullNameColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'full_name' LIMIT 1")->fetchColumn();
$staffUserIdColumnName = 'users_id';
$staffUserIdSelectSql = 's.users_id AS users_id';
$staffUserJoinSql = ' LEFT JOIN users u ON s.users_id = u.id';

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
  "SELECT s.id, $staffUserIdSelectSql, s.full_name, s.position, $departmentSelectSql, s.status, u.username, u.email, u.phone, u.role_id AS linked_role_id
   FROM staff s
   " . $staffUserJoinSql . $departmentJoinSql . $whereSql . " ORDER BY s.id DESC"
);
$stmt->execute($whereParams);
$staffList = $stmt->fetchAll();
$usedUserIds = [];
if ($staffUserIdColumnName !== null) {
  $usedUserIds = array_map('intval', $db->query("SELECT $staffUserIdColumnName FROM staff")->fetchAll(PDO::FETCH_COLUMN));
}

$usersNameSelect = $hasUserFullNameColumn ? 'full_name' : 'username AS full_name';
$users = $db->query("SELECT u.id, u.username, $usersNameSelect, u.email, u.phone, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.email ASC, u.username ASC")->fetchAll();
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
            <?php if ($canAddStaff): ?>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#staffModal" onclick="resetStaffForm()">
              <i class="fas fa-plus"></i> Thêm Staff
            </button>
            <?php endif; ?>
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
                      <?php if ($canEditStaff): ?>
                      <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#staffModal" onclick='editStaff(<?php echo $staffJson; ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>
                      <?php endif; ?>
                      <?php if ($canDeleteStaff): ?>
                      <a href="process/staff_management.php?action=delete&id=<?php echo (int) $staff['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa staff này?');">
                        <i class="fas fa-trash"></i>
                      </a>
                      <?php endif; ?>
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
                <option value="<?php echo (int) $user['id']; ?>" data-used="<?php echo $isUsed ? '1' : '0'; ?>" data-name="<?php echo htmlspecialchars((string) ($user['full_name'] ?? ''), ENT_QUOTES); ?>" data-phone="<?php echo htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES); ?>" data-position="<?php echo htmlspecialchars((string) ($user['role_name'] ?? ''), ENT_QUOTES); ?>">
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
            <select id="position_display" class="form-control" disabled>
              <option value="">--- Chọn chức vụ ---</option>
              <?php foreach ($roles as $role): ?>
                <?php $roleLabel = $role['name'] . (!empty($role['description']) ? ' - ' . $role['description'] : ''); ?>
                <option value="<?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="position" id="position" value="">
            <small class="text-muted d-block">Chức vụ được tự động lấy theo tài khoản / email đã chọn.</small>
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

<?php include 'layout/footer.php'; ?>

<script>
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
    $('#position').val('');
    $('#position_display').val('');
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
    $('#position').val(staff.position || '');
    $('#position_display').val(staff.position || '');
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
    var positionInput = document.getElementById('position');
    var positionDisplay = document.getElementById('position_display');
    var error = document.getElementById('users_id_error');
    var formMode = document.getElementById('staff_form_mode').value || 'add';
    var isUsed = selectedOption && selectedOption.getAttribute('data-used') === '1';
    var positionValue = selectedOption ? (selectedOption.getAttribute('data-position') || '') : '';

    fullNameInput.value = selectedOption ? (selectedOption.getAttribute('data-name') || '') : '';
    phoneInput.value = selectedOption ? (selectedOption.getAttribute('data-phone') || '') : '';
    if (positionInput) {
      positionInput.value = positionValue;
    }

    if (positionDisplay && positionValue) {
      var hasMatchingOption = Array.from(positionDisplay.options).some(function (option) {
        return option.value === positionValue;
      });
      if (hasMatchingOption) {
        positionDisplay.value = positionValue;
      }
    }

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