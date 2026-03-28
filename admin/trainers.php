<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý HLV";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_TRAINERS
checkPermission('MANAGE_TRAINERS');

include '../includes/functions.php';

$db = getDB();

function hasTableColumn(PDO $db, $table, $column)
{
  $stmt = $db->prepare("SELECT 1
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = ?
                          AND COLUMN_NAME = ?
                        LIMIT 1");
  $stmt->execute([(string) $table, (string) $column]);
  return (bool) $stmt->fetchColumn();
}

function getTrainerRoleIds(PDO $db)
{
  $stmt = $db->query("SELECT id, name FROM roles WHERE status = 'active'");
  $trainerRoleIds = [];

  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $role) {
    $roleName = trim((string) ($role['name'] ?? ''));
    if ($roleName === '') {
      continue;
    }

    $normalized = function_exists('mb_strtolower')
      ? mb_strtolower($roleName, 'UTF-8')
      : strtolower($roleName);

    if (strpos($normalized, 'huấn luyện') !== false || strpos($normalized, 'huan luyen') !== false || strpos($normalized, 'trainer') !== false) {
      $trainerRoleIds[] = (int) $role['id'];
    }
  }

  return array_values(array_unique($trainerRoleIds));
}

function loadTrainerUsers(PDO $db, array $trainerRoleIds)
{
  if (empty($trainerRoleIds)) {
    return [];
  }

  $sql = "SELECT id, username, full_name, email, phone, role_id
      FROM users
      WHERE status = 'active' AND full_name <> '' AND phone IS NOT NULL AND phone <> ''";

  $placeholders = implode(',', array_fill(0, count($trainerRoleIds), '?'));
  $sql .= " AND role_id IN ($placeholders)";

  $sql .= " ORDER BY email ASC, username ASC";

  $stmt = $db->prepare($sql);
  $stmt->execute($trainerRoleIds);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTrainerUserById(PDO $db, $userId)
{
  $stmt = $db->prepare("SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role_id, r.name AS role_name
              FROM users u
              LEFT JOIN roles r ON r.id = u.role_id
              WHERE u.id = ?");
  $stmt->execute([$userId]);

  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  return $user ?: null;
}

function isValidTrainerPhone($phone)
{
  return preg_match('/^0[0-9]{9,10}$/', $phone) === 1;
}

function isTrainerRoleUser(array $user, array $trainerRoleIds)
{
  if (empty($trainerRoleIds)) {
    return false;
  }

  return in_array((int) ($user['role_id'] ?? 0), $trainerRoleIds, true);
}

$trainerRoleIds = getTrainerRoleIds($db);
$trainerUsers = loadTrainerUsers($db, $trainerRoleIds);
$trainerHasUsersIdColumn = hasTableColumn($db, 'trainers', 'users_id');
$filterName = trim((string) ($_GET['name'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$filterPhone = trim((string) ($_GET['phone'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$trainerTypesFilter = $db->query("SELECT DISTINCT type FROM trainers WHERE type IS NOT NULL AND type <> '' ORDER BY type ASC")->fetchAll(PDO::FETCH_COLUMN);

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') { $whereClauses[] = 't.full_name LIKE ?'; $whereParams[] = '%' . $filterName . '%'; }
if ($filterType !== '') { $whereClauses[] = 't.type = ?'; $whereParams[] = $filterType; }
if ($filterPhone !== '') { $whereClauses[] = 't.phone LIKE ?'; $whereParams[] = '%' . $filterPhone . '%'; }
if ($filterStatus !== '') { $whereClauses[] = 't.status = ?'; $whereParams[] = $filterStatus; }
$trainerWhereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
    $type = sanitize($_POST['type'] ?? 'Nội bộ');
    $users_id = isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0;
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
    $status = sanitize($_POST['status']);

    if ($users_id <= 0) {
      setFlashMessage('danger', 'Vui lòng chọn tài khoản / email HLV.');
      redirect('trainers.php');
      exit;
    }

    $selectedUser = fetchTrainerUserById($db, $users_id);
    if (!$selectedUser || !isTrainerRoleUser($selectedUser, $trainerRoleIds)) {
      setFlashMessage('danger', 'Tài khoản đã chọn không thuộc vai trò Huấn luyện viên.');
      redirect('trainers.php');
      exit;
    }

    $full_name = trim((string) ($selectedUser['full_name'] ?? ''));
    $phone = preg_replace('/\D+/', '', (string) ($selectedUser['phone'] ?? ''));

    if ($full_name === '') {
      setFlashMessage('danger', 'Tài khoản đã chọn chưa có Họ tên.');
      redirect('trainers.php');
      exit;
    }

    if (!isValidTrainerPhone($phone)) {
      setFlashMessage('danger', 'Tài khoản đã chọn chưa có SĐT hợp lệ bắt đầu bằng 0.');
      redirect('trainers.php');
      exit;
    }

        try {
      if ($trainerHasUsersIdColumn) {
        $stmt = $db->prepare("INSERT INTO trainers (users_id, full_name, type, phone, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$users_id, $full_name, $type, $phone, $status]);
      } else {
        $stmt = $db->prepare("INSERT INTO trainers (full_name, type, phone, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $type, $phone, $status]);
      }
            setFlashMessage('success', 'Thêm HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
      $type = sanitize($_POST['type'] ?? 'Nội bộ');
      $users_id = isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0;
      $full_name = sanitize($_POST['full_name'] ?? '');
        $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
        $status = sanitize($_POST['status']);

      if ($users_id <= 0) {
        setFlashMessage('danger', 'Vui lòng chọn tài khoản / email HLV.');
        redirect('trainers.php');
        exit;
      }

      $selectedUser = fetchTrainerUserById($db, $users_id);
      if (!$selectedUser || !isTrainerRoleUser($selectedUser, $trainerRoleIds)) {
        setFlashMessage('danger', 'Tài khoản đã chọn không thuộc vai trò Huấn luyện viên.');
        redirect('trainers.php');
        exit;
      }

      $full_name = trim((string) ($selectedUser['full_name'] ?? ''));
      $phone = preg_replace('/\D+/', '', (string) ($selectedUser['phone'] ?? ''));

      if ($full_name === '') {
        setFlashMessage('danger', 'Tài khoản đã chọn chưa có Họ tên.');
        redirect('trainers.php');
        exit;
      }

      if (!isValidTrainerPhone($phone)) {
        setFlashMessage('danger', 'Tài khoản đã chọn chưa có SĐT hợp lệ bắt đầu bằng 0.');
        redirect('trainers.php');
        exit;
      }

        try {
        if ($trainerHasUsersIdColumn) {
          $stmt = $db->prepare("UPDATE trainers SET users_id = ?, full_name = ?, type = ?, phone = ?, status = ? WHERE id = ?");
          $stmt->execute([$users_id, $full_name, $type, $phone, $status, $id]);
        } else {
          $stmt = $db->prepare("UPDATE trainers SET full_name = ?, type = ?, phone = ?, status = ? WHERE id = ?");
          $stmt->execute([$full_name, $type, $phone, $status, $id]);
        }
            setFlashMessage('success', 'Cập nhật HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM trainers WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa HLV (có thể đang có lịch tập liên kết). ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }
}

// Lấy danh sách HLV
$trainerSelectUsersIdSql = $trainerHasUsersIdColumn
  ? "t.users_id AS trainer_users_id"
  : "NULL AS trainer_users_id";

$trainerSelectUserLabelSql = $trainerHasUsersIdColumn
  ? "COALESCE(NULLIF(u.full_name, ''), NULLIF(u.email, ''), NULLIF(u.username, ''), '') AS trainer_user_label"
  : "'' AS trainer_user_label";

$trainerUserJoinSql = $trainerHasUsersIdColumn
  ? "LEFT JOIN users u ON u.id = t.users_id"
  : "";

$stmt = $db->prepare("SELECT t.*, $trainerSelectUsersIdSql, $trainerSelectUserLabelSql
                    FROM trainers t
                    $trainerUserJoinSql
                    $trainerWhereSql
                    ORDER BY t.id DESC");
$stmt->execute($whereParams);
$trainers = $stmt->fetchAll();

// Lấy flash message
$flash = getFlashMessage();

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
            <h1 class="m-0">Quản lý HLV (Trainers)</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">HLV</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php
          $filterMode = 'server';
          $filterAction = 'trainers.php';
          $filterFieldsHtml = '
            <div class="col-md-3"><div class="form-group mb-0"><label>Họ tên</label><input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Họ tên HLV"></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Loại</label><select name="type" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($trainerTypesFilter as $trainerTypeOption) {
            $selected = $filterType === $trainerTypeOption ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . htmlspecialchars($trainerTypeOption) . '" ' . $selected . '>' . htmlspecialchars($trainerTypeOption) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>SĐT</label><input type="text" name="phone" class="form-control" value="' . htmlspecialchars($filterPhone) . '" placeholder="Số điện thoại"></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="hoạt động" ' . ($filterStatus === 'hoạt động' ? 'selected' : '') . '>Hoạt động</option><option value="nghỉ việc" ' . ($filterStatus === 'nghỉ việc' ? 'selected' : '') . '>Nghỉ việc</option></select></div></div>
          ';
          include 'layout/filter-card.php';
        ?>

        <!-- Thông báo -->
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <?= $flash['message'] ?>
        </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách HLV</h3>
                <div class="card-tools">
                  <button type="button" id="openAddTrainerModal" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm HLV
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Loại</th>
                    <th>SĐT</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($trainers as $trainer): ?>
                  <tr>
                    <td><?= $trainer['id'] ?></td>
                    <td><?= htmlspecialchars($trainer['full_name']) ?></td>
                    <td>
                      <?php if ($trainer['type'] === 'Nội bộ'): ?>
                        <span class="badge badge-info">Nội bộ</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Tự do</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trainer['phone']) ?></td>
                    <td>
                      <?php if ($trainer['status'] === 'hoạt động'): ?>
                        <span class="badge badge-success">Hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Nghỉ việc</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $trainer['id'] ?>"
                        data-fullname="<?= htmlspecialchars($trainer['full_name']) ?>"
                        data-type="<?= $trainer['type'] ?>"
                        data-users-id="<?= (int) ($trainer['trainer_users_id'] ?? 0) ?>"
                        data-user-label="<?= htmlspecialchars($trainer['trainer_user_label'] ?? '') ?>"
                        data-user-fullname="<?= htmlspecialchars($trainer['full_name'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($trainer['phone']) ?>"
                        data-status="<?= htmlspecialchars($trainer['status'] ?? '') ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $trainer['id'] ?>"
                        data-name="<?= htmlspecialchars($trainer['full_name']) ?>"
                        data-toggle="modal" data-target="#deleteTrainerModal">
                        <i class="fas fa-trash"></i>
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

    <!-- Modal Thêm HLV -->
    <div class="modal fade" id="addTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm HLV mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control trainer-type-select" name="type" data-field="type">
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-free-group d-none">
                <label>Họ tên HLV <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-name-free" name="full_name" placeholder="Nhập họ tên HLV" data-field="full_name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="trainer-name-internal-group">
                <div class="form-group">
                  <label>Chọn tài khoản/email <span class="text-danger">*</span></label>
                  <select class="form-control trainer-account-select select2" name="users_id" data-field="users_id" style="width: 100%;">
                    <option value="">-- Chọn tài khoản/email --</option>
                    <?php foreach ($trainerUsers as $user): ?>
                      <?php $accountLabel = !empty($user['email']) ? $user['email'] : ($user['username'] ?? ''); ?>
                      <option
                        value="<?= (int) $user['id'] ?>"
                        data-full-name="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        <?= htmlspecialchars($accountLabel) ?><?= !empty($user['username']) && !empty($user['email']) ? ' (' . htmlspecialchars($user['username']) . ')' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-danger d-block mt-2" style="display:none;"></small>
                </div>
                <div class="form-group mb-0">
                  <label>Họ tên theo tài khoản/email</label>
                  <input type="text" class="form-control trainer-account-fullname" value="" readonly>
                </div>
              </div>
              <div class="form-group mt-3">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-phone-input" name="phone" placeholder="Nhập SĐT" inputmode="numeric" data-field="phone">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="nghỉ việc">Nghỉ việc</option>
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

    <!-- Modal Sửa HLV -->
    <div class="modal fade" id="editTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa thông tin HLV</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control trainer-type-select" name="type" id="edit-type" data-field="type">
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-free-group d-none">
                <label>Họ tên HLV <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-name-free" name="full_name" id="edit-fullname-free" placeholder="Nhập họ tên HLV" data-field="full_name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="trainer-name-internal-group">
                <div class="form-group">
                  <label>Chọn tài khoản/email <span class="text-danger">*</span></label>
                  <select class="form-control trainer-account-select select2" name="users_id" id="edit-users-id" data-field="users_id" style="width: 100%;">
                    <option value="">-- Chọn tài khoản/email --</option>
                    <?php foreach ($trainerUsers as $user): ?>
                      <?php $accountLabel = !empty($user['email']) ? $user['email'] : ($user['username'] ?? ''); ?>
                      <option
                        value="<?= (int) $user['id'] ?>"
                        data-full-name="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        <?= htmlspecialchars($accountLabel) ?><?= !empty($user['username']) && !empty($user['email']) ? ' (' . htmlspecialchars($user['username']) . ')' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-danger d-block mt-2" style="display:none;"></small>
                </div>
                <div class="form-group mb-0">
                  <label>Họ tên theo tài khoản/email</label>
                  <input type="text" class="form-control trainer-account-fullname" id="edit-account-fullname" value="" readonly>
                </div>
              </div>
              <div class="form-group mt-3">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-phone-input" name="phone" id="edit-phone" inputmode="numeric" data-field="phone">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="nghỉ việc">Nghỉ việc</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Xóa HLV -->
    <div class="modal fade" id="deleteTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header">
              <h5 class="modal-title">Xác nhận xóa</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa HLV <strong id="delete-name"></strong>?</p>
              <p class="text-danger"><small>Hành động này không thể hoàn tác! Nếu HLV đang có lịch tập sẽ không xóa được.</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-danger">Xóa</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>

<?php include 'layout/footer.php'; ?>

<!-- Script xử lý modal -->
<script>
function syncTrainerNameFields($modal) {
  if (!$modal || !$modal.length) return;

  const $freeGroup = $modal.find('.trainer-name-free-group');
  const $internalGroup = $modal.find('.trainer-name-internal-group');
  const $freeInput = $modal.find('.trainer-name-free');
  const $accountSelect = $modal.find('.trainer-account-select');
  const $accountFullName = $modal.find('.trainer-account-fullname');
  const $phoneInput = $modal.find('.trainer-phone-input');

  $freeGroup.addClass('d-none');
  $internalGroup.removeClass('d-none');
  $freeInput.prop('disabled', true).val('');
  $accountSelect.prop('disabled', false);
  $phoneInput.prop('readonly', true);
  applyInternalAccountSelection($modal);
}

function applyInternalAccountSelection($modal) {
  const $accountSelect = $modal.find('.trainer-account-select');
  const $selected = $accountSelect.find('option:selected');
  const fullName = String($selected.data('full-name') || '').trim();
  const phone = String($selected.data('phone') || '').replace(/\D+/g, '');

  $modal.find('.trainer-account-fullname').val(fullName);
  $modal.find('.trainer-phone-input').val(phone);
}

function ensureSelectOption($select, value, text, fullName, phone) {
  if (!$select.length || !value) return;
  const existing = $select.find('option').filter(function() {
    return String($(this).val()) === String(value);
  });
  if (!existing.length) {
    const option = new Option(text, value, true, true);
    $(option).attr('data-full-name', fullName || '');
    $(option).attr('data-phone', phone || '');
    $select.prepend(option);
  } else {
    existing.attr('data-full-name', fullName || '');
    existing.attr('data-phone', phone || '');
  }
}

function fillEditModal($button) {
  const $modal = $('#editTrainerModal');
  const type = $button.data('type');
  const fullName = $button.data('fullname') || '';
  const usersId = $button.data('users-id') || '';
  const userLabel = $button.data('user-label') || '';
  const userFullName = $button.data('user-fullname') || fullName;
  const phone = String($button.attr('data-phone') || '').replace(/\D+/g, '');

  $modal.find('#edit-id').val($button.data('id'));
  $modal.find('#edit-type').val(type);
  $modal.find('#edit-phone').val(phone);
  $modal.find('#edit-status').val($button.data('status'));

  $modal.find('#edit-fullname-free').val('');
  ensureSelectOption(
    $modal.find('#edit-users-id'),
    usersId,
    userLabel || userFullName,
    userFullName,
    phone
  );
  $modal.find('#edit-users-id').val(usersId ? String(usersId) : '');

  syncTrainerNameFields($modal);
}

function resetAddModal() {
  const $modal = $('#addTrainerModal');
  $modal.find('.trainer-type-select').val('Nội bộ');
  $modal.find('.trainer-phone-input').val('');
  $modal.find('.trainer-name-free').val('');
  $modal.find('.trainer-account-select').val('');
  $modal.find('.trainer-account-fullname').val('');
  syncTrainerNameFields($modal);
}

function initTrainerAccountSelect2() {
  if (!$.fn.select2) return;

  $('.trainer-account-select').each(function() {
    const $select = $(this);
    const $modal = $select.closest('.modal');

    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      theme: 'bootstrap4',
      width: '100%',
      dropdownParent: $modal.length ? $modal : $(document.body),
      placeholder: '-- Chọn tài khoản/email --',
      allowClear: false
    });
  });
}

$(function() {
  initTrainerAccountSelect2();

  // Click vào thanh chọn sẽ mở dropdown, gõ trong ô search để lọc tài khoản/email
  $(document).on('focus', '.select2-container--bootstrap4 .select2-selection--single', function() {
    const $select = $(this).closest('.select2-container').prev('select.trainer-account-select');
    if ($select.length) {
      $select.select2('open');
    }
  });

  $(document).on('select2:open', function() {
    const searchField = document.querySelector('.select2-container--open .select2-search__field');
    if (searchField) {
      searchField.focus();
    }
  });

  // Chỉ cho phép nhập số cho SĐT HLV
  $(document).on('input', '.trainer-phone-input', function() {
    if ($(this).prop('readonly')) return;
    const digitsOnly = String($(this).val() || '').replace(/\D+/g, '');
    $(this).val(digitsOnly);
  });

  $(document).on('click', '#openAddTrainerModal', function() {
    resetAddModal();
    $('#addTrainerModal').modal('show');
  });

  $(document).on('change', '.trainer-type-select', function() {
    syncTrainerNameFields($(this).closest('.modal-content'));
  });

  $(document).on('change', '.trainer-account-select', function() {
    applyInternalAccountSelection($(this).closest('.modal-content'));
  });

  // Điền dữ liệu vào modal sửa
  $(document).on('click', '.btn-edit', function() {
    fillEditModal($(this));
    $('#editTrainerModal').modal('show');
  });

  // Điền dữ liệu vào modal xóa
  $(document).on('click', '.btn-delete', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });

  $('#addTrainerModal, #editTrainerModal').on('show.bs.modal', function() {
    syncTrainerNameFields($(this).find('.modal-content'));
    initTrainerAccountSelect2();
  });
});

(function() {
  function message(field) {
    if (field === 'users_id') return 'Vui lòng chọn tài khoản/email';
    if (field === 'type') return 'Vui lòng chọn loại HLV';
    if (field === 'phone') return 'Số điện thoại tài khoản phải bắt đầu bằng 0 và gồm 10-11 số';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }

  function getBox(input) {
    return input.closest('.form-group')?.querySelector('small.text-danger') || null;
  }

  function show(input, text) {
    const box = getBox(input);
    if (box) {
      box.textContent = text;
      box.style.display = 'block';
    }
    input.classList.add('is-invalid');
  }

  function clear(input) {
    const box = getBox(input);
    if (box) {
      box.textContent = '';
      box.style.display = 'none';
    }
    input.classList.remove('is-invalid');
  }

  function validate(input) {
    const field = input.getAttribute('data-field');
    const value = String(input.value || '').trim();
    clear(input);

    if (!field) return true;

    if (field === 'full_name') {
      return true;
    }

    if (field === 'users_id') {
      if (!value) {
        show(input, message(field));
        return false;
      }
      return true;
    }

    if (field === 'phone') {
      if (!value) {
        show(input, 'Tài khoản/email đã chọn chưa có số điện thoại');
        return false;
      }
      if (!/^0[0-9]{9,10}$/.test(value)) {
        show(input, message(field));
        return false;
      }
      return true;
    }

    if (!value) {
      show(input, message(field));
      return false;
    }

    return true;
  }

  document.addEventListener('invalid', function(e) {
    const form = e.target && e.target.closest ? e.target.closest('form') : null;
    if (form && form.hasAttribute('novalidate')) e.preventDefault();
  }, true);

  document.addEventListener('input', function(e) {
    if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('change', function(e) {
    if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('submit', function(e) {
    if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return;
    let ok = true;
    e.target.querySelectorAll('[data-field]').forEach(function(field) {
      if (!validate(field)) ok = false;
    });
    if (!ok) e.preventDefault();
  }, true);

})();
</script>