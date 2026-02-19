<?php 
$page_title = "Quản lý HLV";
require_once '../includes/session.php';

$db = getDB();

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $full_name = sanitize($_POST['full_name']);
        $type = $_POST['type'];
        $phone = sanitize($_POST['phone']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $db->prepare("INSERT INTO trainers (full_name, type, phone, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $type, $phone, $status]);
            setFlashMessage('success', 'Thêm HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $full_name = sanitize($_POST['full_name']);
        $type = $_POST['type'];
        $phone = sanitize($_POST['phone']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $db->prepare("UPDATE trainers SET full_name = ?, type = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$full_name, $type, $phone, $status, $id]);
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
$stmt = $db->query("SELECT * FROM trainers ORDER BY id DESC");
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
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTrainerModal">
                    <i class="fas fa-plus"></i> Thêm HLV
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
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
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $trainer['id'] ?>"
                        data-fullname="<?= htmlspecialchars($trainer['full_name']) ?>"
                        data-type="<?= $trainer['type'] ?>"
                        data-phone="<?= htmlspecialchars($trainer['phone']) ?>"
                        data-status="<?= htmlspecialchars($trainer['status'] ?? '') ?>"
                        data-toggle="modal" data-target="#editTrainerModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
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
          <form method="POST" action="trainers.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm HLV mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Họ tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" placeholder="Nhập họ tên HLV" required>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" required>
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
              </div>
              <div class="form-group">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="phone" placeholder="Nhập SĐT" required>
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
          <form method="POST" action="trainers.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa thông tin HLV</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Họ tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" id="edit-fullname" required>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="edit-type" required>
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
              </div>
              <div class="form-group">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="phone" id="edit-phone" required>
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
$(function() {
  // Điền dữ liệu vào modal sửa
  $('.btn-edit').on('click', function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-fullname').val($(this).data('fullname'));
    $('#edit-type').val($(this).data('type'));
    $('#edit-phone').val($(this).data('phone'));
    $('#edit-status').val($(this).data('status'));
  });

  // Điền dữ liệu vào modal xóa
  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});
</script>
