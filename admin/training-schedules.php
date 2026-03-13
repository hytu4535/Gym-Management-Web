<?php 
$page_title = "Quản lý lịch tập";
require_once '../includes/session.php';

$db = getDB();

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $member_id = intval($_POST['member_id']);
        $trainer_id = !empty($_POST['trainer_id']) ? intval($_POST['trainer_id']) : null;
        $training_date = sanitize($_POST['training_date']);
        $training_time = sanitize($_POST['training_time']);
        $note = sanitize($_POST['note'] ?? '');

        // Ghép ngày + giờ
        $datetime = $training_date . ' ' . $training_time . ':00';

        try {
            $stmt = $db->prepare("INSERT INTO training_schedules (member_id, trainer_id, training_date, note) VALUES (?, ?, ?, ?)");
            $stmt->execute([$member_id, $trainer_id, $datetime, $note]);
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
        $note = sanitize($_POST['note'] ?? '');

        $datetime = $training_date . ' ' . $training_time . ':00';

        try {
            $stmt = $db->prepare("UPDATE training_schedules SET member_id = ?, trainer_id = ?, training_date = ?, note = ? WHERE id = ?");
            $stmt->execute([$member_id, $trainer_id, $datetime, $note, $id]);
            setFlashMessage('success', 'Cập nhật lịch tập thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('training-schedules.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
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
$stmt = $db->query("
    SELECT ts.*, 
           m.full_name AS member_name, 
           t.full_name AS trainer_name
    FROM training_schedules ts
    LEFT JOIN members m ON ts.member_id = m.id
    LEFT JOIN trainers t ON ts.trainer_id = t.id
    ORDER BY ts.training_date DESC
");
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
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>HLV (PT)</th>
                    <th>Ngày tập</th>
                    <th>Giờ tập</th>
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
                    <td><?= htmlspecialchars($schedule['note'] ?? '') ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $schedule['id'] ?>"
                        data-member_id="<?= $schedule['member_id'] ?>"
                        data-trainer_id="<?= $schedule['trainer_id'] ?? '' ?>"
                        data-date="<?= date('Y-m-d', strtotime($schedule['training_date'])) ?>"
                        data-time="<?= date('H:i', strtotime($schedule['training_date'])) ?>"
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
          <form method="POST" action="training-schedules.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary">
              <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Thêm lịch tập mới</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control select2" name="member_id" required style="width: 100%;">
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($members)): ?>
                  <small class="text-danger">Chưa có hội viên nào. Vui lòng thêm hội viên trước.</small>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label>HLV (PT)</label>
                <select class="form-control select2" name="trainer_id" style="width: 100%;">
                  <option value="">-- Không gán HLV --</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= $trainer['id'] ?>">
                      <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($trainers)): ?>
                  <small class="text-warning">Chưa có HLV đang hoạt động.</small>
                <?php endif; ?>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày tập <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="training_date" required value="<?= date('Y-m-d') ?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ tập <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="training_time" required value="08:00">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Ghi chú</label>
                <textarea class="form-control" name="note" rows="3" placeholder="Ghi chú về buổi tập..."></textarea>
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
          <form method="POST" action="training-schedules.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header bg-warning">
              <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa lịch tập</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control" name="member_id" id="edit-member_id" required>
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>HLV (PT)</label>
                <select class="form-control" name="trainer_id" id="edit-trainer_id">
                  <option value="">-- Không gán HLV --</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= $trainer['id'] ?>">
                      <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày tập <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="training_date" id="edit-date" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Giờ tập <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" name="training_time" id="edit-time" required>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Ghi chú</label>
                <textarea class="form-control" name="note" id="edit-note" rows="3"></textarea>
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
    $('.select2').select2({
      theme: 'bootstrap4',
      placeholder: 'Tìm kiếm...',
      allowClear: true
    });
  }

  // Điền dữ liệu vào modal sửa
  $('.btn-edit').on('click', function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-member_id').val($(this).data('member_id'));
    $('#edit-trainer_id').val($(this).data('trainer_id'));
    $('#edit-date').val($(this).data('date'));
    $('#edit-time').val($(this).data('time'));
    $('#edit-note').val($(this).data('note'));
  });

  // Điền dữ liệu vào modal xóa
  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-member').text($(this).data('member'));
    $('#delete-date').text($(this).data('date'));
  });
});
</script>
