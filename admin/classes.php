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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $class_name = sanitize($_POST['class_name'] ?? '');
        $class_type = sanitize($_POST['class_type'] ?? '');
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
        $schedule_time = sanitize($_POST['schedule_time'] ?? '');
        $schedule_days = sanitize($_POST['schedule_days'] ?? '');
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        try {
            $stmt = $db->prepare("INSERT INTO class_schedules (class_name, class_type, trainer_id, schedule_time, schedule_days, capacity, enrolled_count, room, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)");
            $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_time, $schedule_days, $capacity, $room, $status]);
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
        $schedule_time = sanitize($_POST['schedule_time'] ?? '');
        $schedule_days = sanitize($_POST['schedule_days'] ?? '');
        $capacity = max(1, intval($_POST['capacity'] ?? 1));
        $room = sanitize($_POST['room'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

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

            $stmt = $db->prepare("UPDATE class_schedules SET class_name = ?, class_type = ?, trainer_id = ?, schedule_time = ?, schedule_days = ?, capacity = ?, room = ?, status = ? WHERE id = ?");
            $stmt->execute([$class_name, $class_type, $trainer_id, $schedule_time, $schedule_days, $capacity, $room, $status, $id]);
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
              <table class="table table-bordered table-striped data-table">
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
                      <div><strong>Giờ:</strong> <?= htmlspecialchars($class['schedule_time'] ?? '') ?></div>
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
                        data-schedule_time="<?= htmlspecialchars($class['schedule_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
        <form method="POST" action="classes.php">
          <input type="hidden" name="action" value="add">
          <div class="modal-header">
            <h5 class="modal-title">Thêm lớp tập mới</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Tên lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_name" required>
            </div>
            <div class="form-group">
              <label>Loại lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_type" placeholder="VD: yoga, cardio, boxing" required>
            </div>
            <div class="form-group">
              <label>Huấn luyện viên</label>
              <select class="form-control" name="trainer_id">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Khung giờ</label>
              <input type="text" class="form-control" name="schedule_time" placeholder="VD: 06:00 - 07:30">
            </div>
            <div class="form-group">
              <label>Lịch ngày</label>
              <input type="text" class="form-control" name="schedule_days" placeholder="VD: Thứ 2, Thứ 4, Thứ 6">
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" value="20" required>
            </div>
            <div class="form-group">
              <label>Phòng tập</label>
              <input type="text" class="form-control" name="room" placeholder="VD: Phòng A1">
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select class="form-control" name="status">
                <option value="active">Đang mở</option>
                <option value="inactive">Đóng lớp</option>
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

  <div class="modal fade" id="editClassModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="classes.php">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          <div class="modal-header">
            <h5 class="modal-title">Sửa thông tin lớp tập</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Tên lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_name" id="edit-class_name" required>
            </div>
            <div class="form-group">
              <label>Loại lớp <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="class_type" id="edit-class_type" required>
            </div>
            <div class="form-group">
              <label>Huấn luyện viên</label>
              <select class="form-control" name="trainer_id" id="edit-trainer_id">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Khung giờ</label>
              <input type="text" class="form-control" name="schedule_time" id="edit-schedule_time">
            </div>
            <div class="form-group">
              <label>Lịch ngày</label>
              <input type="text" class="form-control" name="schedule_days" id="edit-schedule_days">
            </div>
            <div class="form-group">
              <label>Sức chứa <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" name="capacity" id="edit-capacity" required>
              <small class="text-muted">Đã đăng ký hiện tại: <span id="edit-enrolled_count">0</span></small>
            </div>
            <div class="form-group">
              <label>Phòng tập</label>
              <input type="text" class="form-control" name="room" id="edit-room">
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select class="form-control" name="status" id="edit-status">
                <option value="active">Đang mở</option>
                <option value="inactive">Đóng lớp</option>
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
    $('#edit-id').val($(this).data('id'));
    $('#edit-class_name').val($(this).data('class_name'));
    $('#edit-class_type').val($(this).data('class_type'));
    $('#edit-trainer_id').val($(this).data('trainer_id'));
    $('#edit-schedule_time').val($(this).data('schedule_time'));
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
</script>
