<?php
session_start();

$page_title = "Quản lý lớp tập";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

// Dùng cùng quyền với nhóm quản lý luyện tập hiện có.
checkPermission('MANAGE_TRAINERS');

$resolveTrainerActionPermission = static function (string $requiredAction): bool {
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
    if (isset($actionPermissions['MANAGE_TRAINERS']) && is_array($actionPermissions['MANAGE_TRAINERS'])) {
      return !empty($actionPermissions['MANAGE_TRAINERS'][$normalizedAction]);
    }

    return $normalizedAction === 'view' && in_array('MANAGE_TRAINERS', $permissions, true);
  }

  return $normalizedAction === 'view' && in_array('MANAGE_TRAINERS', $permissions, true);
};

$canAddClass = $resolveTrainerActionPermission('add');
$canEditClass = $resolveTrainerActionPermission('edit');
$canDeleteClass = $resolveTrainerActionPermission('delete');

include '../includes/functions.php';

$db = getDB();

function isValidScheduleTime($time)
{
  return preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', trim((string) $time)) === 1;
}

function normalizeScheduleTime($time)
{
  $time = trim((string) $time);
  if ($time === '') {
    return '';
  }

  return substr($time, 0, 5);
}

function isValidScheduleRange($startTime, $endTime)
{
  if (!isValidScheduleTime($startTime) || !isValidScheduleTime($endTime)) {
    return false;
  }

  return strtotime(normalizeScheduleTime($startTime)) < strtotime(normalizeScheduleTime($endTime));
}

function normalizeDayToken($value)
{
  $value = trim((string) $value);
  if ($value === '') {
    return '';
  }

  $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
  $value = str_replace(['thu ', 'thứ '], 'thu ', $value);
  $value = str_replace('chủ nhật', 'cn', $value);
  $value = str_replace('chu nhat', 'cn', $value);

  $map = [
    'thu 2' => '2',
    'thu 3' => '3',
    'thu 4' => '4',
    'thu 5' => '5',
    'thu 6' => '6',
    'thu 7' => '7',
    'cn' => 'cn'
  ];

  return $map[$value] ?? $value;
}

function normalizeScheduleDaysValue($raw)
{
  if (is_array($raw)) {
    $parts = $raw;
  } else {
    $parts = explode(',', (string) $raw);
  }

  $normalized = [];
  foreach ($parts as $part) {
    $token = normalizeDayToken($part);
    if ($token !== '') {
      $normalized[] = $token;
    }
  }

  $normalized = array_values(array_unique($normalized));
  return $normalized;
}

function formatScheduleDaysForStorage(array $days)
{
  $labels = [
    '2' => 'Thứ 2',
    '3' => 'Thứ 3',
    '4' => 'Thứ 4',
    '5' => 'Thứ 5',
    '6' => 'Thứ 6',
    '7' => 'Thứ 7',
    'cn' => 'Chủ nhật'
  ];

  $result = [];
  foreach ($days as $day) {
    $result[] = $labels[$day] ?? $day;
  }

  return implode(', ', $result);
}

function hasScheduleDayIntersection(array $a, array $b)
{
  return !empty(array_intersect($a, $b));
}

function isTimeRangeOverlap($startA, $endA, $startB, $endB)
{
  if (!isValidScheduleRange($startA, $endA) || !isValidScheduleRange($startB, $endB)) {
    return false;
  }

  $aStart = strtotime(normalizeScheduleTime($startA));
  $aEnd = strtotime(normalizeScheduleTime($endA));
  $bStart = strtotime(normalizeScheduleTime($startB));
  $bEnd = strtotime(normalizeScheduleTime($endB));

  return ($aStart < $bEnd) && ($bStart < $aEnd);
}

function findClassConflictMessage(PDO $db, ?int $trainerId, string $room, string $startTime, string $endTime, array $days, int $excludeClassId = 0)
{
  if (empty($days) || !isValidScheduleRange($startTime, $endTime)) {
    return null;
  }

  if (($trainerId === null || $trainerId <= 0) && trim($room) === '') {
    return null;
  }

  $query = "SELECT id, class_name, trainer_id, room, schedule_start_time, schedule_end_time, schedule_days
            FROM class_schedules
            WHERE status = 'active'";
  $params = [];

  if ($excludeClassId > 0) {
    $query .= " AND id <> ?";
    $params[] = $excludeClassId;
  }

  if ($trainerId !== null && $trainerId > 0 && trim($room) !== '') {
    $query .= " AND (trainer_id = ? OR room = ?)";
    $params[] = $trainerId;
    $params[] = trim($room);
  } elseif ($trainerId !== null && $trainerId > 0) {
    $query .= " AND trainer_id = ?";
    $params[] = $trainerId;
  } elseif (trim($room) !== '') {
    $query .= " AND room = ?";
    $params[] = trim($room);
  }

  $stmt = $db->prepare($query);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $row) {
    $otherDays = normalizeScheduleDaysValue((string) ($row['schedule_days'] ?? ''));
    if (!hasScheduleDayIntersection($days, $otherDays)) {
      continue;
    }

    if (!isTimeRangeOverlap($startTime, $endTime, (string) ($row['schedule_start_time'] ?? ''), (string) ($row['schedule_end_time'] ?? ''))) {
      continue;
    }

    $className = (string) ($row['class_name'] ?? ('#' . $row['id']));
    if ($trainerId !== null && $trainerId > 0 && (int) ($row['trainer_id'] ?? 0) === $trainerId) {
      return 'Huấn luyện viên đang bị trùng lịch với lớp "' . $className . '".';
    }

    if (trim($room) !== '' && trim((string) ($row['room'] ?? '')) === trim($room)) {
      return 'Phòng tập đang bị trùng lịch với lớp "' . $className . '".';
    }
  }

  return null;
}

function supportsStructuredScheduleTime(PDO $db)
{
  static $supportsStructured = null;

  if ($supportsStructured !== null) {
    return $supportsStructured;
  }

  $stmt = $db->query(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'class_schedules'
       AND COLUMN_NAME IN ('schedule_start_time', 'schedule_end_time')"
  );

  $supportsStructured = ((int) $stmt->fetchColumn() === 2);
  return $supportsStructured;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
    checkPermission('MANAGE_TRAINERS', 'add');

        $class_name = sanitize($_POST['class_name'] ?? '');
        $class_type = sanitize($_POST['class_type'] ?? '');
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
    $schedule_start_time = sanitize($_POST['schedule_start_time'] ?? '');
    $schedule_end_time = sanitize($_POST['schedule_end_time'] ?? '');
        $scheduleDays = normalizeScheduleDaysValue($_POST['schedule_days'] ?? []);
        $schedule_days = formatScheduleDaysForStorage($scheduleDays);
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $price_per_session = max(0, floatval($_POST['price_per_session'] ?? 0));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (!isValidScheduleRange($schedule_start_time, $schedule_end_time)) {
      setFlashMessage('danger', 'Vui lòng nhập giờ bắt đầu và giờ kết thúc hợp lệ, giờ bắt đầu phải nhỏ hơn giờ kết thúc.');
      redirect('classes.php');
      exit;
    }

    if (empty($scheduleDays)) {
      setFlashMessage('danger', 'Vui lòng chọn ít nhất 1 ngày học trong tuần.');
      redirect('classes.php');
      exit;
    }

    $conflictMessage = findClassConflictMessage($db, $trainer_id, $room, $schedule_start_time, $schedule_end_time, $scheduleDays, 0);
    if ($conflictMessage !== null) {
      setFlashMessage('danger', $conflictMessage);
      redirect('classes.php');
      exit;
    }

    $schedule_start_time = normalizeScheduleTime($schedule_start_time);
    $schedule_end_time = normalizeScheduleTime($schedule_end_time);
    $structuredScheduleTime = supportsStructuredScheduleTime($db);

        try {
      if ($structuredScheduleTime) {
        $stmt = $db->prepare("INSERT INTO class_schedules (class_name, class_type, trainer_id, schedule_start_time, schedule_end_time, schedule_days, capacity, enrolled_count, room, price_per_session, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)");
        $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_start_time, $schedule_end_time, $schedule_days, $capacity, $room, $price_per_session, $status]);
      } else {
        $stmt = $db->prepare("INSERT INTO class_schedules (class_name, class_type, trainer_id, schedule_days, capacity, enrolled_count, room, price_per_session, status) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)");
        $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_days, $capacity, $room, $price_per_session, $status]);
      }
            setFlashMessage('success', 'Thêm lớp tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      checkPermission('MANAGE_TRAINERS', 'edit');

        $id = intval($_POST['id'] ?? 0);
        $class_name = sanitize($_POST['class_name'] ?? '');
        $class_type = sanitize($_POST['class_type'] ?? '');
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
      $schedule_start_time = sanitize($_POST['schedule_start_time'] ?? '');
      $schedule_end_time = sanitize($_POST['schedule_end_time'] ?? '');
        $scheduleDays = normalizeScheduleDaysValue($_POST['schedule_days'] ?? []);
        $schedule_days = formatScheduleDaysForStorage($scheduleDays);
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $price_per_session = max(0, floatval($_POST['price_per_session'] ?? 0));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

      if (!isValidScheduleRange($schedule_start_time, $schedule_end_time)) {
        setFlashMessage('danger', 'Vui lòng nhập giờ bắt đầu và giờ kết thúc hợp lệ, giờ bắt đầu phải nhỏ hơn giờ kết thúc.');
        redirect('classes.php');
        exit;
      }

      if (empty($scheduleDays)) {
        setFlashMessage('danger', 'Vui lòng chọn ít nhất 1 ngày học trong tuần.');
        redirect('classes.php');
        exit;
      }

      $schedule_start_time = normalizeScheduleTime($schedule_start_time);
      $schedule_end_time = normalizeScheduleTime($schedule_end_time);
    $structuredScheduleTime = supportsStructuredScheduleTime($db);

        try {
            $checkStmt = $db->prepare("SELECT enrolled_count FROM class_schedules WHERE id = ?");
            $checkStmt->execute([$id]);
            $currentClass = $checkStmt->fetch();

            if (!$currentClass) {
                throw new Exception('Không tìm thấy lớp tập.');
            }

            if ((int) $currentClass['enrolled_count'] > $capacity) {
                throw new Exception('Sức chứa mới không được nhỏ hơn số hội viên đã đăng ký (' . (int) $currentClass['enrolled_count'] . ').');
            }

            $registrationStmt = $db->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ? AND status = 'active'");
            $registrationStmt->execute([$id]);
            $activeRegistrations = (int) $registrationStmt->fetchColumn();

            $currentPriceStmt = $db->prepare("SELECT price_per_session FROM class_schedules WHERE id = ?");
            $currentPriceStmt->execute([$id]);
            $currentPrice = (float) $currentPriceStmt->fetchColumn();

            if ($activeRegistrations > 0 && ((float) $price_per_session !== (float) $currentPrice)) {
              throw new Exception('Không thể thay đổi giá lớp khi đã có hội viên đăng ký.');
            }

            $conflictMessage = findClassConflictMessage($db, $trainer_id, $room, $schedule_start_time, $schedule_end_time, $scheduleDays, $id);
            if ($conflictMessage !== null) {
              throw new Exception($conflictMessage);
            }

            if ($structuredScheduleTime) {
              $stmt = $db->prepare("UPDATE class_schedules SET class_name = ?, class_type = ?, trainer_id = ?, schedule_start_time = ?, schedule_end_time = ?, schedule_days = ?, capacity = ?, room = ?, price_per_session = ?, status = ? WHERE id = ?");
              $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_start_time, $schedule_end_time, $schedule_days, $capacity, $room, $price_per_session, $status, $id]);
            } else {
              $stmt = $db->prepare("UPDATE class_schedules SET class_name = ?, class_type = ?, trainer_id = ?, schedule_days = ?, capacity = ?, room = ?, price_per_session = ?, status = ? WHERE id = ?");
              $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_days, $capacity, $room, $price_per_session, $status, $id]);
            }
            setFlashMessage('success', 'Cập nhật lớp tập thành công!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
      checkPermission('MANAGE_TRAINERS', 'delete');

        $id = intval($_POST['id'] ?? 0);

        try {
            $registrationStmt = $db->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ?");
            $registrationStmt->execute([$id]);
            $registrationCount = (int) $registrationStmt->fetchColumn();

            if ($registrationCount > 0) {
              $stmt = $db->prepare("UPDATE class_schedules SET status = 'inactive' WHERE id = ?");
              $stmt->execute([$id]);
              setFlashMessage('success', 'Lớp đã có đăng ký nên được chuyển sang Đóng lớp thay vì xóa.');
            } else {
              $stmt = $db->prepare("DELETE FROM class_schedules WHERE id = ?");
              $stmt->execute([$id]);
              setFlashMessage('success', 'Xóa lớp tập thành công!');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa lớp tập. ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }
}

$stmt = $db->query("SELECT cs.*, t.full_name AS trainer_name FROM class_schedules cs LEFT JOIN trainers t ON cs.trainer_id = t.id ORDER BY cs.id DESC");
$classes = $stmt->fetchAll();

$classMembersMap = [];
$memberRowsStmt = $db->query("SELECT cr.class_id, m.full_name, m.phone, cr.registered_at, cr.status
                              FROM class_registrations cr
                              INNER JOIN members m ON m.id = cr.member_id
                              ORDER BY cr.class_id ASC, cr.registered_at DESC");
foreach ($memberRowsStmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
  $classId = (int) ($memberRow['class_id'] ?? 0);
  if ($classId <= 0) {
    continue;
  }
  if (!isset($classMembersMap[$classId])) {
    $classMembersMap[$classId] = [];
  }
  $classMembersMap[$classId][] = [
    'full_name' => (string) ($memberRow['full_name'] ?? ''),
    'phone' => (string) ($memberRow['phone'] ?? ''),
    'registered_at' => (string) ($memberRow['registered_at'] ?? ''),
    'status' => (string) ($memberRow['status'] ?? ''),
  ];
}

$stmt = $db->query("SELECT id, full_name FROM trainers WHERE status = 'hoạt động' ORDER BY full_name ASC");
$trainers = $stmt->fetchAll();

$totalClasses = count($classes);
$activeClasses = 0;
$inactiveClasses = 0;
$totalEnrolled = 0;

foreach ($classes as $class) {
    $totalEnrolled += (int) ($class['enrolled_count'] ?? 0);
    if (($class['status'] ?? '') === 'active') {
        $activeClasses++;
    } else {
        $inactiveClasses++;
    }
}

$flash = getFlashMessage();

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Quản lý lớp tập</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Lớp tập</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php renderAdminFlash($flash); ?>

      <div class="row mb-3">
        <div class="col-md-3 col-sm-6">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-dumbbell"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Tổng lớp tập</span>
              <span class="info-box-number"><?= $totalClasses ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Đang mở</span>
              <span class="info-box-number"><?= $activeClasses ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-ban"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Đóng lớp</span>
              <span class="info-box-number"><?= $inactiveClasses ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Đã đăng ký</span>
              <span class="info-box-number"><?= $totalEnrolled ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <?php include 'layout/filter-card.php'; ?>
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Danh sách lớp tập</h3>
              <div class="card-tools">
                <?php if ($canAddClass): ?>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addClassModal">
                  <i class="fas fa-plus"></i> Thêm lớp tập
                </button>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped data-table js-admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên lớp</th>
                    <th>Loại</th>
                    <th>HLV</th>
                    <th>Giá/buổi</th>
                    <th>Lịch</th>
                    <th>Sức chứa</th>
                    <th>Phòng</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($classes as $class): ?>
                  <tr>
                    <td><?= $class['id'] ?></td>
                    <td><?= htmlspecialchars($class['class_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($class['class_type']) ?></span></td>
                    <td><?= htmlspecialchars($class['trainer_name'] ?? 'Chưa gán') ?></td>
                    <td><?= number_format((float) ($class['price_per_session'] ?? 0), 0, ',', '.') ?>đ</td>
                    <td>
                      <div><strong>Ngày:</strong> <?= htmlspecialchars($class['schedule_days'] ?? '') ?></div>
                      <div><strong>Giờ:</strong> <?= htmlspecialchars(trim((string) ($class['schedule_start_time'] ?? '') . ' - ' . (string) ($class['schedule_end_time'] ?? ''))) ?></div>
                    </td>
                    <td>
                      <span class="badge badge-light"><?= (int) $class['enrolled_count'] ?>/<?= (int) $class['capacity'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($class['room'] ?? '') ?></td>
                    <td>
                      <?php if (($class['status'] ?? '') === 'active'): ?>
                        <span class="badge badge-success">Đang mở</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Đã đóng</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($canEditClass): ?>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $class['id'] ?>"
                        data-class_name="<?= htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-class_type="<?= htmlspecialchars($class['class_type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-trainer_id="<?= $class['trainer_id'] ?? '' ?>"
                        data-schedule_start_time="<?= htmlspecialchars($class['schedule_start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-schedule_end_time="<?= htmlspecialchars($class['schedule_end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-schedule_days="<?= htmlspecialchars($class['schedule_days'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-capacity="<?= (int) $class['capacity'] ?>"
                        data-price_per_session="<?= htmlspecialchars((string) ($class['price_per_session'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                        data-enrolled_count="<?= (int) $class['enrolled_count'] ?>"
                        data-room="<?= htmlspecialchars($class['room'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-status="<?= htmlspecialchars($class['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>"
                        data-toggle="modal" data-target="#editClassModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <?php endif; ?>
                      <?php if ($canDeleteClass): ?>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $class['id'] ?>"
                        data-name="<?= htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-toggle="modal" data-target="#deleteClassModal">
                        <i class="fas fa-trash"></i>
                      </button>
                      <?php endif; ?>
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

  <div class="modal fade" id="addClassModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="classes.php" novalidate>
          <input type="hidden" name="action" value="add">
          <div class="modal-header">
            <h5 class="modal-title">Thêm lớp tập mới</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Tên lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_name" data-field="class_name">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Loại lớp <span class="text-danger">*</span></label>
              <select class="form-control" name="class_type" data-field="class_type">
                <option value="">-- Chọn loại lớp --</option>
                <option value="yoga">Yoga</option>
                <option value="cardio">Cardio</option>
                <option value="boxing">Boxing</option>
                <option value="hiit">HIIT</option>
                <option value="strength">Strength</option>
                <option value="pilates">Pilates</option>
                <option value="zumba">Zumba</option>
                <option value="khác">Khác</option>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Huấn luyện viên</label>
              <select class="form-control" name="trainer_id" data-field="trainer_id">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Giờ bắt đầu</label>
              <input type="time" class="form-control" name="schedule_start_time" data-field="schedule_start_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Giờ kết thúc</label>
              <input type="time" class="form-control" name="schedule_end_time" data-field="schedule_end_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Lịch ngày</label>
              <div class="row">
                <div class="col-6"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-2" value="2"><label for="add-day-2" class="custom-control-label">Thứ 2</label></div></div>
                <div class="col-6"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-3" value="3"><label for="add-day-3" class="custom-control-label">Thứ 3</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-4" value="4"><label for="add-day-4" class="custom-control-label">Thứ 4</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-5" value="5"><label for="add-day-5" class="custom-control-label">Thứ 5</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-6" value="6"><label for="add-day-6" class="custom-control-label">Thứ 6</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-7" value="7"><label for="add-day-7" class="custom-control-label">Thứ 7</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="add-day-cn" value="cn"><label for="add-day-cn" class="custom-control-label">Chủ nhật</label></div></div>
              </div>
              <input type="hidden" class="js-day-hidden" name="schedule_days" data-field="schedule_days">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" value="20" data-field="capacity">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Giá mỗi buổi <span class="text-danger">*</span></label>
              <input type="number" min="0" step="1000" class="form-control" name="price_per_session" value="0" data-field="price_per_session">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Phòng tập</label>
              <input type="text" class="form-control" name="room" placeholder="VD: Phòng A1" data-field="room">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select class="form-control" name="status" data-field="status">
                <option value="active">Đang mở</option>
                <option value="inactive">Đóng lớp</option>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
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

  <div class="modal fade" id="editClassModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="classes.php" novalidate>
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          <div class="modal-header">
            <h5 class="modal-title">Sửa thông tin lớp tập</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Tên lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_name" id="edit-class_name" data-field="class_name">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Loại lớp <span class="text-danger">*</span></label>
              <select class="form-control" name="class_type" id="edit-class_type" data-field="class_type">
                <option value="">-- Chọn loại lớp --</option>
                <option value="yoga">Yoga</option>
                <option value="cardio">Cardio</option>
                <option value="boxing">Boxing</option>
                <option value="hiit">HIIT</option>
                <option value="strength">Strength</option>
                <option value="pilates">Pilates</option>
                <option value="zumba">Zumba</option>
                <option value="khác">Khác</option>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Huấn luyện viên</label>
              <select class="form-control" name="trainer_id" id="edit-trainer_id" data-field="trainer_id">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Giờ bắt đầu</label>
              <input type="time" class="form-control" name="schedule_start_time" id="edit-schedule_start_time" data-field="schedule_start_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Giờ kết thúc</label>
              <input type="time" class="form-control" name="schedule_end_time" id="edit-schedule_end_time" data-field="schedule_end_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Lịch ngày</label>
              <div class="row">
                <div class="col-6"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-2" value="2"><label for="edit-day-2" class="custom-control-label">Thứ 2</label></div></div>
                <div class="col-6"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-3" value="3"><label for="edit-day-3" class="custom-control-label">Thứ 3</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-4" value="4"><label for="edit-day-4" class="custom-control-label">Thứ 4</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-5" value="5"><label for="edit-day-5" class="custom-control-label">Thứ 5</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-6" value="6"><label for="edit-day-6" class="custom-control-label">Thứ 6</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-7" value="7"><label for="edit-day-7" class="custom-control-label">Thứ 7</label></div></div>
                <div class="col-6 mt-1"><div class="custom-control custom-checkbox"><input class="custom-control-input js-day-checkbox" type="checkbox" id="edit-day-cn" value="cn"><label for="edit-day-cn" class="custom-control-label">Chủ nhật</label></div></div>
              </div>
              <input type="hidden" class="js-day-hidden" name="schedule_days" id="edit-schedule_days" data-field="schedule_days">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" id="edit-capacity" data-field="capacity">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
              <small class="text-muted">Đã đăng ký hiện tại: <span id="edit-enrolled_count">0</span></small>
            </div>
            <div class="form-group">
              <label>Giá mỗi buổi <span class="text-danger">*</span></label>
              <input type="number" min="0" step="1000" class="form-control" name="price_per_session" id="edit-price_per_session" data-field="price_per_session">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Phòng tập</label>
              <input type="text" class="form-control" name="room" id="edit-room" data-field="room">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select class="form-control" name="status" id="edit-status" data-field="status">
                <option value="active">Đang mở</option>
                <option value="inactive">Đóng lớp</option>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
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

  <div class="modal fade" id="deleteClassModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="classes.php">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="delete-id">
          <div class="modal-header">
            <h5 class="modal-title">Xác nhận xóa lớp tập</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <p>Bạn có chắc chắn muốn xóa lớp <strong id="delete-name"></strong>?</p>
            <p class="text-danger"><small>Hành động này không thể hoàn tác và sẽ xóa cả dữ liệu đăng ký liên quan.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-danger">Xóa</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="classMembersModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Danh sách đăng ký lớp: <span id="classMembersTitle"></span></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Hội viên</th>
                  <th>SĐT</th>
                  <th>Ngày đăng ký</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody id="classMembersBody"></tbody>
            </table>
          </div>
          <p id="classMembersEmpty" class="text-muted mb-0" style="display:none;">Chưa có hội viên đăng ký.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
const classMembersMap = <?= json_encode($classMembersMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

$(function() {
  function normalizeDayTokenJs(value) {
    const src = String(value || '').trim().toLowerCase();
    if (src === 'chủ nhật' || src === 'chu nhat' || src === 'cn') return 'cn';
    return src.replace('thứ', 'thu').replace('thu ', '').trim();
  }

  function syncDayHidden($scope) {
    const values = [];
    $scope.find('.js-day-checkbox:checked').each(function() {
      values.push(String($(this).val()));
    });
    $scope.find('.js-day-hidden').val(values.join(','));
  }

  function setDayChecked($scope, dayString) {
    const selected = String(dayString || '').split(',').map(function (item) {
      return normalizeDayTokenJs(item);
    });
    $scope.find('.js-day-checkbox').prop('checked', false);
    $scope.find('.js-day-checkbox').each(function () {
      const current = normalizeDayTokenJs($(this).val());
      if (selected.indexOf(current) !== -1) {
        $(this).prop('checked', true);
      }
    });
    syncDayHidden($scope);
  }

  $('#addClassModal, #editClassModal').on('change', '.js-day-checkbox', function() {
    syncDayHidden($(this).closest('.form-group'));
  });

  $('#addClassModal').on('shown.bs.modal', function () {
    $(this).find('.js-day-checkbox').prop('checked', false);
    $(this).find('.js-day-hidden').val('');
  });

  $('.btn-edit').on('click', function() {
    let startTime = String($(this).data('schedule_start_time') || '');
    let endTime = String($(this).data('schedule_end_time') || '');

    $('#edit-id').val($(this).data('id'));
    $('#edit-class_name').val($(this).data('class_name'));
    $('#edit-class_type').val($(this).data('class_type'));
    $('#edit-trainer_id').val($(this).data('trainer_id'));
    $('#edit-schedule_start_time').val(startTime);
    $('#edit-schedule_end_time').val(endTime);
    setDayChecked($('#editClassModal'), $(this).data('schedule_days'));
    $('#edit-capacity').val($(this).data('capacity'));
    $('#edit-price_per_session').val($(this).data('price_per_session'));
    $('#edit-enrolled_count').text($(this).data('enrolled_count'));
    $('#edit-room').val($(this).data('room'));
    $('#edit-status').val($(this).data('status'));
  });

  $('.btn-detail').on('click', function() {
    const classId = String($(this).data('id'));
    const className = $(this).data('name') || '';
    const rows = classMembersMap[classId] || classMembersMap[Number(classId)] || [];

    $('#classMembersTitle').text(className);
    const $body = $('#classMembersBody');
    $body.empty();

    if (!rows.length) {
      $('#classMembersEmpty').show();
      return;
    }

    $('#classMembersEmpty').hide();
    rows.forEach(function (row) {
      const statusText = String(row.status || '') === 'active' ? 'Đang đăng ký' : 'Đã hủy';
      const statusClass = String(row.status || '') === 'active' ? 'success' : 'secondary';
      $body.append(
        '<tr>' +
          '<td>' + (row.full_name || '') + '</td>' +
          '<td>' + (row.phone || '') + '</td>' +
          '<td>' + (row.registered_at || '') + '</td>' +
          '<td><span class="badge badge-' + statusClass + '">' + statusText + '</span></td>' +
        '</tr>'
      );
    });
  });

  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});

(function() {
  function getMsg(field) {
    if (field === 'class_name') return 'Vui lòng nhập tên lớp';
    if (field === 'class_type') return 'Vui lòng chọn loại lớp';
    if (field === 'capacity') return 'Vui lòng nhập sức chứa hợp lệ';
    if (field === 'trainer_id') return 'Vui lòng chọn huấn luyện viên';
    if (field === 'schedule_start_time') return 'Vui lòng nhập giờ bắt đầu';
    if (field === 'schedule_end_time') return 'Vui lòng nhập giờ kết thúc';
    if (field === 'schedule_days') return 'Vui lòng nhập lịch ngày';
    if (field === 'room') return 'Vui lòng nhập phòng tập';
    if (field === 'price_per_session') return 'Vui lòng nhập giá mỗi buổi';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }

  function container(input) {
    return input.closest('.form-group')?.querySelector('small.text-danger') || null;
  }

  function show(input, message) {
    const c = container(input);
    if (c) { c.textContent = message; c.style.display = 'block'; }
    input.classList.add('is-invalid');
  }

  function clear(input) {
    const c = container(input);
    if (c) { c.textContent = ''; c.style.display = 'none'; }
    input.classList.remove('is-invalid');
  }

  function validate(input) {
    const field = input.getAttribute('data-field');
    const value = String(input.value || '').trim();
    clear(input);
    if (!field) return true;
    if (field === 'capacity') {
      if (!value || Number(value) < 1) { show(input, getMsg(field)); return false; }
      return true;
    }
    if (field === 'schedule_start_time' || field === 'schedule_end_time') {
      if (!/^\d{2}:\d{2}(?::\d{2})?$/.test(value)) { show(input, getMsg(field)); return false; }
      return true;
    }
    if (!value) { show(input, getMsg(field)); return false; }
    return true;
  }

  document.addEventListener('invalid', function(e) {
    const form = e.target.closest('form');
    if (form && form.hasAttribute('novalidate')) e.preventDefault();
  }, true);

  document.addEventListener('input', function(e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('change', function(e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('submit', function(e) {
    if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return;
    const fields = e.target.querySelectorAll('[data-field]');
    let ok = true;
    fields.forEach(function(field) { if (!validate(field)) ok = false; });
    if (!ok) e.preventDefault();
  }, true);
})();
</script>