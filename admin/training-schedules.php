<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý lịch tập";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_TRAINERS
checkPermission('MANAGE_TRAINERS');

include '../includes/functions.php';

$db = getDB();

$filterMemberId = trim((string) ($_GET['member_id'] ?? ''));
$filterTrainerId = trim((string) ($_GET['trainer_id'] ?? ''));
$filterFromDate = trim((string) ($_GET['from_date'] ?? ''));
$filterToDate = trim((string) ($_GET['to_date'] ?? ''));

$scheduleWhereClauses = [];
$scheduleParams = [];
if ($filterMemberId !== '') {
  $scheduleWhereClauses[] = 'ts.member_id = ?';
  $scheduleParams[] = (int) $filterMemberId;
}
if ($filterTrainerId !== '') {
  $scheduleWhereClauses[] = 'ts.trainer_id = ?';
  $scheduleParams[] = (int) $filterTrainerId;
}
if ($filterFromDate !== '') {
  $scheduleWhereClauses[] = 'DATE(ts.training_date) >= ?';
  $scheduleParams[] = $filterFromDate;
}
if ($filterToDate !== '') {
  $scheduleWhereClauses[] = 'DATE(ts.training_date) <= ?';
  $scheduleParams[] = $filterToDate;
}
$scheduleWhereSql = !empty($scheduleWhereClauses) ? ' WHERE ' . implode(' AND ', $scheduleWhereClauses) : '';

$scheduleStatusLabels = [
  'pending' => 'Đang chờ',
  'confirmed' => 'Đã xác nhận',
  'completed' => 'Đã hoàn thành',
  'canceled' => 'Đã hủy',
];

if (!function_exists('trainingScheduleStatusLabel')) {
  function trainingScheduleStatusLabel($status)
  {
    $labels = [
      'pending' => 'Đang chờ',
      'confirmed' => 'Đã xác nhận',
      'completed' => 'Đã hoàn thành',
      'canceled' => 'Đã hủy',
    ];

    return $labels[$status] ?? $labels['pending'];
  }
}

if (!function_exists('trainingScheduleStatusBadgeClass')) {
  function trainingScheduleStatusBadgeClass($status)
  {
    $classes = [
      'pending' => 'warning',
      'confirmed' => 'info',
      'completed' => 'success',
      'canceled' => 'secondary',
    ];

    return $classes[$status] ?? 'warning';
  }
}

if (!function_exists('normalizeTrainingScheduleStatus')) {
  function normalizeTrainingScheduleStatus($status)
  {
    $allowed = ['pending', 'confirmed', 'completed', 'canceled'];
    return in_array($status, $allowed, true) ? $status : 'pending';
  }
}

if (!function_exists('buildTrainingScheduleDateTime')) {
  function buildTrainingScheduleDateTime($date, $time)
  {
    return DateTime::createFromFormat('Y-m-d H:i:s', trim($date) . ' ' . trim($time) . ':00');
  }
}

if (!function_exists('findTrainingScheduleOverlap')) {
  function findTrainingScheduleOverlap(PDO $db, $column, $value, DateTime $startTime, DateTime $endTime, $excludeId = null)
  {
    if (!in_array($column, ['member_id', 'trainer_id'], true)) {
      return false;
    }

    $sql = 'SELECT id FROM training_schedules WHERE ' . $column . ' = ? AND status <> ? AND NOT (COALESCE(end_time, DATE_ADD(training_date, INTERVAL 60 MINUTE)) <= ? OR training_date >= ?)';
    $params = [$value, 'canceled', $startTime->format('Y-m-d H:i:s'), $endTime->format('Y-m-d H:i:s')];

    if ($excludeId !== null) {
      $sql .= ' AND id <> ?';
      $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $member_id = intval($_POST['member_id']);
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
        $training_date = sanitize($_POST['training_date']);
        $training_time = sanitize($_POST['training_time']);
    $end_time = sanitize($_POST['end_time'] ?? '');
        $note = sanitize($_POST['note'] ?? '');
    $status = normalizeTrainingScheduleStatus($_POST['status'] ?? 'pending');

    $startDateTime = buildTrainingScheduleDateTime($training_date, $training_time);
    $endDateTime = buildTrainingScheduleDateTime($training_date, $end_time);

    if (!$startDateTime || !$endDateTime) {
      setFlashMessage('danger', 'Thời gian bắt đầu hoặc kết thúc không hợp lệ.');
      redirect('training-schedules.php');
      exit;
    }

    if ($endDateTime <= $startDateTime) {
      setFlashMessage('danger', 'Giờ kết thúc phải lớn hơn giờ bắt đầu.');
      redirect('training-schedules.php');
      exit;
    }

    if (findTrainingScheduleOverlap($db, 'member_id', $member_id, $startDateTime, $endDateTime)) {
      setFlashMessage('danger', 'Hội viên này đã có lịch tập trùng thời gian.');
      redirect('training-schedules.php');
      exit;
    }

    if ($trainer_id && findTrainingScheduleOverlap($db, 'trainer_id', $trainer_id, $startDateTime, $endDateTime)) {
      setFlashMessage('danger', 'Huấn luyện viên này đã có lịch dạy trùng thời gian.');
      redirect('training-schedules.php');
      exit;
    }

        try {
      $stmt = $db->prepare("INSERT INTO training_schedules (member_id, trainer_id, training_date, end_time, note, status) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$member_id, $trainer_id, $startDateTime->format('Y-m-d H:i:s'), $endDateTime->format('Y-m-d H:i:s'), $note, $status]);
            setFlashMessage('success', 'Thêm lịch tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('training-schedules.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $member_id = intval($_POST['member_id']);
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
        $training_date = sanitize($_POST['training_date']);
        $training_time = sanitize($_POST['training_time']);
        $end_time = sanitize($_POST['end_time'] ?? '');
        $note = sanitize($_POST['note'] ?? '');
        $status = normalizeTrainingScheduleStatus($_POST['status'] ?? 'pending');

        $startDateTime = buildTrainingScheduleDateTime($training_date, $training_time);
        $endDateTime = buildTrainingScheduleDateTime($training_date, $end_time);

        if (!$startDateTime || !$endDateTime) {
          setFlashMessage('danger', 'Thời gian bắt đầu hoặc kết thúc không hợp lệ.');
          redirect('training-schedules.php');
          exit;
        }

        if ($endDateTime <= $startDateTime) {
          setFlashMessage('danger', 'Giờ kết thúc phải lớn hơn giờ bắt đầu.');
          redirect('training-schedules.php');
          exit;
        }

        $currentStmt = $db->prepare('SELECT id, trainer_id, training_date, end_time, status FROM training_schedules WHERE id = ? LIMIT 1');
        $currentStmt->execute([$id]);
        $currentSchedule = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentSchedule) {
          setFlashMessage('danger', 'Không tìm thấy lịch tập cần cập nhật.');
          redirect('training-schedules.php');
          exit;
        }

        if (findTrainingScheduleOverlap($db, 'member_id', $member_id, $startDateTime, $endDateTime, $id)) {
            setFlashMessage('danger', 'Hội viên này đã có lịch tập trùng thời gian.');
            redirect('training-schedules.php');
            exit;
        }

        if ($trainer_id && findTrainingScheduleOverlap($db, 'trainer_id', $trainer_id, $startDateTime, $endDateTime, $id)) {
            setFlashMessage('danger', 'Huấn luyện viên này đã có lịch dạy trùng thời gian.');
            redirect('training-schedules.php');
            exit;
        }

        $currentStatus = normalizeTrainingScheduleStatus($currentSchedule['status'] ?? 'pending');
        $allowTrainerChange = strtotime($currentSchedule['training_date']) > time() || in_array($currentStatus, ['pending', 'confirmed'], true);
        if (!$allowTrainerChange) {
          $trainer_id = $currentSchedule['trainer_id'] !== null ? (int) $currentSchedule['trainer_id'] : null;
        }

        try {
            $stmt = $db->prepare("UPDATE training_schedules SET member_id = ?, trainer_id = ?, training_date = ?, end_time = ?, note = ?, status = ? WHERE id = ?");
            $stmt->execute([$member_id, $trainer_id, $startDateTime->format('Y-m-d H:i:s'), $endDateTime->format('Y-m-d H:i:s'), $note, $status, $id]);
            setFlashMessage('success', 'Cập nhật lịch tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('training-schedules.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
      $currentStmt = $db->prepare('SELECT training_date, status FROM training_schedules WHERE id = ? LIMIT 1');
      $currentStmt->execute([$id]);
      $currentSchedule = $currentStmt->fetch(PDO::FETCH_ASSOC);

      if (!$currentSchedule) {
        setFlashMessage('danger', 'Không tìm thấy lịch tập cần xóa.');
        redirect('training-schedules.php');
        exit;
      }

      $currentStatus = normalizeTrainingScheduleStatus($currentSchedule['status'] ?? 'pending');
      if (strtotime($currentSchedule['training_date']) <= time() && $currentStatus !== 'pending') {
        setFlashMessage('danger', 'Chỉ được xóa khi lịch còn ở tương lai hoặc đang chờ.');
        redirect('training-schedules.php');
        exit;
      }

        try {
            $stmt = $db->prepare("DELETE FROM training_schedules WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa lịch tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('training-schedules.php');
        exit;
    }
}

// Lấy danh sách lịch tập kèm tên hội viên và HLV
$stmt = $db->prepare(" 
  SELECT ts.*, 
       m.full_name AS member_name, 
       t.full_name AS trainer_name
  FROM training_schedules ts
  LEFT JOIN members m ON ts.member_id = m.id
  LEFT JOIN trainers t ON ts.trainer_id = t.id" . $scheduleWhereSql . "
  ORDER BY ts.training_date DESC
");
$stmt->execute($scheduleParams);
$schedules = $stmt->fetchAll();

// Lấy danh sách hội viên (active) cho dropdown
$stmt = $db->query("SELECT id, full_name, phone FROM members WHERE status = 'active' ORDER BY full_name ASC");
$members = $stmt->fetchAll();

// Lấy danh sách HLV (hoạt động) cho dropdown
$stmt = $db->query("SELECT id, full_name, phone FROM trainers WHERE status = 'hoạt động' ORDER BY full_name ASC");
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
            <h1 class="m-0">Quản lý lịch tập</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Lịch tập</li>
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
          $filterAction = 'training-schedules.php';
          $filterFieldsHtml = '
            <div class="col-md-3"><div class="form-group mb-0"><label>Hội viên</label><select name="member_id" class="form-control select2" style="width: 100%;"><option value="">-- Tất cả --</option>';
          foreach ($members as $member) {
            $selected = (string) $filterMemberId === (string) $member['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $member['id'] . '" ' . $selected . '>' . htmlspecialchars($member['full_name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>HLV</label><select name="trainer_id" class="form-control select2" style="width: 100%;"><option value="">-- Tất cả --</option>';
          foreach ($trainers as $trainer) {
            $selected = (string) $filterTrainerId === (string) $trainer['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $trainer['id'] . '" ' . $selected . '>' . htmlspecialchars($trainer['full_name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Từ ngày</label><input type="date" name="from_date" class="form-control" value="' . htmlspecialchars($filterFromDate) . '"></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Đến ngày</label><input type="date" name="to_date" class="form-control" value="' . htmlspecialchars($filterToDate) . '"></div></div>
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

        <!-- Thống kê nhanh -->
        <div class="row mb-3">
          <div class="col-md-3 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tổng lịch tập</span>
                <span class="info-box-number"><?= count($schedules) ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-success"><i class="fas fa-calendar-check"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Hôm nay</span>
                <span class="info-box-number">
                  <?= count(array_filter($schedules, function($s) { return date('Y-m-d', strtotime($s['training_date'])) === date('Y-m-d'); })) ?>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Hội viên</span>
                <span class="info-box-number"><?= count($members) ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-danger"><i class="fas fa-user-tie"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">HLV hoạt động</span>
                <span class="info-box-number"><?= count($trainers) ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách lịch tập</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addScheduleModal">
                    <i class="fas fa-plus"></i> Thêm lịch tập
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>HLV (PT)</th>
                    <th>Ngày tập</th>
                    <th>Giờ tập</th>
                    <th>Giờ kết thúc</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($schedules as $schedule): ?>
                  <tr>
                    <td><?= $schedule['id'] ?></td>
                    <td><?= htmlspecialchars($schedule['member_name'] ?? 'N/A') ?></td>
                    <td>
                      <?php if ($schedule['trainer_name']): ?>
                        <span class="badge badge-info"><?= htmlspecialchars($schedule['trainer_name']) ?></span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Chưa gán</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($schedule['training_date'])) ?></td>
                    <td><?= date('H:i', strtotime($schedule['training_date'])) ?></td>
                    <td><?= !empty($schedule['end_time']) ? date('H:i', strtotime($schedule['end_time'])) : '-' ?></td>
                    <td>
                      <?php $scheduleStatus = normalizeTrainingScheduleStatus($schedule['status'] ?? 'pending'); ?>
                      <span class="badge badge-<?= trainingScheduleStatusBadgeClass($scheduleStatus) ?>"><?= htmlspecialchars(trainingScheduleStatusLabel($scheduleStatus)) ?></span>
                    </td>
                    <td><?= htmlspecialchars($schedule['note'] ?? '') ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $schedule['id'] ?>"
                        data-member_id="<?= $schedule['member_id'] ?>"
                        data-trainer_id="<?= $schedule['trainer_id'] ?? '' ?>"
                        data-date="<?= date('Y-m-d', strtotime($schedule['training_date'])) ?>"
                        data-time="<?= date('H:i', strtotime($schedule['training_date'])) ?>"
                        data-end_time="<?= !empty($schedule['end_time']) ? date('H:i', strtotime($schedule['end_time'])) : '' ?>"
                        data-status="<?= htmlspecialchars(normalizeTrainingScheduleStatus($schedule['status'] ?? 'pending')) ?>"
                        data-note="<?= htmlspecialchars($schedule['note'] ?? '') ?>"
                        data-toggle="modal" data-target="#editScheduleModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $schedule['id'] ?>"
                        data-member="<?= htmlspecialchars($schedule['member_name'] ?? 'N/A') ?>"
                        data-date="<?= date('d/m/Y H:i', strtotime($schedule['training_date'])) ?>"
                        data-toggle="modal" data-target="#deleteScheduleModal">
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

    <!-- Modal Thêm lịch tập -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="training-schedules.php" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary">
              <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Thêm lịch tập mới</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control select2" name="member_id" data-field="member_id" data-required="1" style="width: 100%;">
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($members)): ?>
                  <small class="text-danger">Chưa có hội viên nào. Vui lòng thêm hội viên trước.</small>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label>HLV (PT)</label>
                <select class="form-control select2" name="trainer_id" data-field="trainer_id" style="width: 100%;">
                  <option value="">-- Không gán HLV --</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= $trainer['id'] ?>">
                      <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($trainers)): ?>
                  <small class="text-warning">Chưa có HLV đang hoạt động.</small>
                <?php endif; ?>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ bắt đầu <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="training_time" data-field="training_time" data-required="1" value="08:00">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ kết thúc <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="end_time" data-field="end_time" data-required="1" value="09:00">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái <span class="text-danger">*</span></label>
                <select class="form-control" name="status" data-field="status" data-required="1">
                  <?php foreach ($scheduleStatusLabels as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $value === 'pending' ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ngày tập <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="training_date" data-field="training_date" data-required="1" value="<?= date('Y-m-d') ?>">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ghi chú</label>
                <textarea class="form-control" name="note" rows="3" placeholder="Ghi chú về buổi tập..." data-field="note"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Sửa lịch tập -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="training-schedules.php" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header bg-warning">
              <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa lịch tập</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control select2" name="member_id" id="edit-member_id" data-field="member_id" data-required="1" style="width: 100%;">
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>HLV (PT)</label>
                <select class="form-control select2" name="trainer_id" id="edit-trainer_id" data-field="trainer_id" style="width: 100%;">
                  <option value="">-- Không gán HLV --</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= $trainer['id'] ?>">
                      <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ bắt đầu <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="training_time" id="edit-time" data-field="training_time" data-required="1">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ kết thúc <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="end_time" id="edit-end-time" data-field="end_time" data-required="1">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái <span class="text-danger">*</span></label>
                <select class="form-control" name="status" id="edit-status" data-field="status" data-required="1">
                  <?php foreach ($scheduleStatusLabels as $value => $label): ?>
                    <option value="<?= $value ?>"><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-muted d-block mt-2">Có thể đổi HLV khi lịch đang chờ, đã xác nhận hoặc thời gian bắt đầu còn ở tương lai.</small>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ngày tập <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="training_date" id="edit-date" data-field="training_date" data-required="1">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ghi chú</label>
                <textarea class="form-control" name="note" id="edit-note" rows="3" data-field="note"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Cập nhật</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Xóa lịch tập -->
    <div class="modal fade" id="deleteScheduleModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="training-schedules.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header bg-danger">
              <h5 class="modal-title text-white"><i class="fas fa-trash"></i> Xác nhận xóa</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa lịch tập của <strong id="delete-member"></strong> vào ngày <strong id="delete-date"></strong>?</p>
              <p class="text-danger"><small>Hành động này không thể hoàn tác!</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Xóa</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>

<?php include 'layout/footer.php'; ?>

<!-- Script xử lý modal -->
<script>
$(function() {
  // Khởi tạo Select2 cho dropdown (nếu có plugin)
  if ($.fn.select2) {
    $('.select2').not('.select2-hidden-accessible').each(function() {
      $(this).select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm kiếm...',
        allowClear: true,
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body)
      });
    });
  }

  // Điền dữ liệu vào modal sửa
  $('.btn-edit').on('click', function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-member_id').val($(this).data('member_id')).trigger('change');
    $('#edit-trainer_id').val($(this).data('trainer_id')).trigger('change');
    $('#edit-date').val($(this).data('date'));
    $('#edit-time').val($(this).data('time'));
    $('#edit-end-time').val($(this).data('end_time') || '');
    $('#edit-status').val($(this).data('status') || 'pending');
    $('#edit-note').val($(this).data('note'));
  });

  // Điền dữ liệu vào modal xóa
  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-member').text($(this).data('member'));
    $('#delete-date').text($(this).data('date'));
  });
});

(function() {
  function label(field) {
    if (field === 'member_id') return 'Vui lòng chọn hội viên';
    if (field === 'training_date') return 'Vui lòng chọn ngày tập';
    if (field === 'training_time') return 'Vui lòng chọn giờ bắt đầu';
    if (field === 'end_time') return 'Vui lòng chọn giờ kết thúc';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }
  function box(input) { return input.closest('.form-group')?.querySelector('small.text-danger') || null; }
  function show(input, message) { const b = box(input); if (b) { b.textContent = message; b.style.display = 'block'; } input.classList.add('is-invalid'); }
  function clear(input) { const b = box(input); if (b) { b.textContent = ''; b.style.display = 'none'; } input.classList.remove('is-invalid'); }
  function validate(input) { const field = input.getAttribute('data-field'); const value = String(input.value || '').trim(); const required = input.getAttribute('data-required') === '1'; clear(input); if (!field) return true; if (required && !value) { show(input, label(field)); return false; } return true; }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>
