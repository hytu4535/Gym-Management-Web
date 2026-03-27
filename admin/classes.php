<?php
session_start();

$page_title = "Quản lý lớp tập";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

// Dùng cùng quyền với nhóm quản lý luyện tập hiện có.
checkPermission('MANAGE_TRAINERS');

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
        $class_name = sanitize($_POST['class_name'] ?? '');
        $class_type = sanitize($_POST['class_type'] ?? '');
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
    $schedule_start_time = sanitize($_POST['schedule_start_time'] ?? '');
    $schedule_end_time = sanitize($_POST['schedule_end_time'] ?? '');
        $schedule_days = sanitize($_POST['schedule_days'] ?? '');
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (!isValidScheduleRange($schedule_start_time, $schedule_end_time)) {
      setFlashMessage('danger', 'Vui lòng nhập giờ bắt đầu và giờ kết thúc hợp lệ, giờ bắt đầu phải nhỏ hơn giờ kết thúc.');
      redirect('classes.php');
      exit;
    }

    $schedule_start_time = normalizeScheduleTime($schedule_start_time);
    $schedule_end_time = normalizeScheduleTime($schedule_end_time);
    $structuredScheduleTime = supportsStructuredScheduleTime($db);

        try {
      if ($structuredScheduleTime) {
        $stmt = $db->prepare("INSERT INTO class_schedules (class_name, class_type, trainer_id, schedule_start_time, schedule_end_time, schedule_days, capacity, enrolled_count, room, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_start_time, $schedule_end_time, $schedule_days, $capacity, $room, $status]);
      } else {
        $stmt = $db->prepare("INSERT INTO class_schedules (class_name, class_type, trainer_id, schedule_days, capacity, enrolled_count, room, status) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_days, $capacity, $room, $status]);
      }
            setFlashMessage('success', 'Thêm lớp tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $class_name = sanitize($_POST['class_name'] ?? '');
        $class_type = sanitize($_POST['class_type'] ?? '');
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
      $schedule_start_time = sanitize($_POST['schedule_start_time'] ?? '');
      $schedule_end_time = sanitize($_POST['schedule_end_time'] ?? '');
        $schedule_days = sanitize($_POST['schedule_days'] ?? '');
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

      if (!isValidScheduleRange($schedule_start_time, $schedule_end_time)) {
        setFlashMessage('danger', 'Vui lòng nhập giờ bắt đầu và giờ kết thúc hợp lệ, giờ bắt đầu phải nhỏ hơn giờ kết thúc.');
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

            if ($structuredScheduleTime) {
              $stmt = $db->prepare("UPDATE class_schedules SET class_name = ?, class_type = ?, trainer_id = ?, schedule_start_time = ?, schedule_end_time = ?, schedule_days = ?, capacity = ?, room = ?, status = ? WHERE id = ?");
              $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_start_time, $schedule_end_time, $schedule_days, $capacity, $room, $status, $id]);
            } else {
              $stmt = $db->prepare("UPDATE class_schedules SET class_name = ?, class_type = ?, trainer_id = ?, schedule_days = ?, capacity = ?, room = ?, status = ? WHERE id = ?");
              $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_days, $capacity, $room, $status, $id]);
            }
            setFlashMessage('success', 'Cập nhật lớp tập thành công!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        try {
            $stmt = $db->prepare("DELETE FROM class_schedules WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa lớp tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa lớp tập. ' . $e->getMessage());
        }

        redirect('classes.php');
        exit;
    }
}

$stmt = $db->query("SELECT cs.*, t.full_name AS trainer_name FROM class_schedules cs LEFT JOIN trainers t ON cs.trainer_id = t.id ORDER BY cs.id DESC");
$classes = $stmt->fetchAll();

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

      <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $flash['message'] ?>
      </div>
      <?php endif; ?>

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
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addClassModal">
                  <i class="fas fa-plus"></i> Thêm lớp tập
                </button>
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
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $class['id'] ?>"
                        data-class_name="<?= htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-class_type="<?= htmlspecialchars($class['class_type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-trainer_id="<?= $class['trainer_id'] ?? '' ?>"
                        data-schedule_start_time="<?= htmlspecialchars($class['schedule_start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-schedule_end_time="<?= htmlspecialchars($class['schedule_end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-schedule_days="<?= htmlspecialchars($class['schedule_days'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-capacity="<?= (int) $class['capacity'] ?>"
                        data-enrolled_count="<?= (int) $class['enrolled_count'] ?>"
                        data-room="<?= htmlspecialchars($class['room'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-status="<?= htmlspecialchars($class['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>"
                        data-toggle="modal" data-target="#editClassModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $class['id'] ?>"
                        data-name="<?= htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-toggle="modal" data-target="#deleteClassModal">
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
              <input type="text" class="form-control" name="class_type" placeholder="VD: yoga, cardio, boxing" data-field="class_type">
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
              <input type="text" class="form-control" name="schedule_days" placeholder="VD: Thứ 2, Thứ 4, Thứ 6" data-field="schedule_days">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" value="20" data-field="capacity">
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
              <input type="text" class="form-control" name="class_type" id="edit-class_type" data-field="class_type">
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
              <input type="text" class="form-control" name="schedule_days" id="edit-schedule_days" data-field="schedule_days">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" id="edit-capacity" data-field="capacity">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
              <small class="text-muted">Đã đăng ký hiện tại: <span id="edit-enrolled_count">0</span></small>
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
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(function() {
  $('.btn-edit').on('click', function() {
    let startTime = String($(this).data('schedule_start_time') || '');
    let endTime = String($(this).data('schedule_end_time') || '');

    $('#edit-id').val($(this).data('id'));
    $('#edit-class_name').val($(this).data('class_name'));
    $('#edit-class_type').val($(this).data('class_type'));
    $('#edit-trainer_id').val($(this).data('trainer_id'));
    $('#edit-schedule_start_time').val(startTime);
    $('#edit-schedule_end_time').val(endTime);
    $('#edit-schedule_days').val($(this).data('schedule_days'));
    $('#edit-capacity').val($(this).data('capacity'));
    $('#edit-enrolled_count').text($(this).data('enrolled_count'));
    $('#edit-room').val($(this).data('room'));
    $('#edit-status').val($(this).data('status'));
  });

  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});

(function() {
  function getMsg(field) {
    if (field === 'class_name') return 'Vui lòng nhập tên lớp';
    if (field === 'class_type') return 'Vui lòng nhập loại lớp';
    if (field === 'capacity') return 'Vui lòng nhập sức chứa hợp lệ';
    if (field === 'trainer_id') return 'Vui lòng chọn huấn luyện viên';
    if (field === 'schedule_start_time') return 'Vui lòng nhập giờ bắt đầu';
    if (field === 'schedule_end_time') return 'Vui lòng nhập giờ kết thúc';
    if (field === 'schedule_days') return 'Vui lòng nhập lịch ngày';
    if (field === 'room') return 'Vui lòng nhập phòng tập';
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
      if (!/^\d{2}:\d{2}$/.test(value)) { show(input, getMsg(field)); return false; }
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