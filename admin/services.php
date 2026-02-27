<?php 
$page_title = "Quản lý dịch vụ";
require_once '../includes/session.php';

$db = getDB();

// Xử lý thêm dịch vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $price = floatval($_POST['price']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("INSERT INTO services (name, type, price, description, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $price, $description, $status]);
            setFlashMessage('success', 'Thêm dịch vụ thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('services.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $price = floatval($_POST['price']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("UPDATE services SET name = ?, type = ?, price = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $type, $price, $description, $status, $id]);
            setFlashMessage('success', 'Cập nhật dịch vụ thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('services.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa dịch vụ thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa dịch vụ. ' . $e->getMessage());
        }
        redirect('services.php');
        exit;
    }
}

// Lấy danh sách dịch vụ
$stmt = $db->query("SELECT * FROM services ORDER BY id DESC");
$services = $stmt->fetchAll();

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
            <h1 class="m-0">Quản lý dịch vụ</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dịch vụ</li>
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
                <h3 class="card-title">Danh sách dịch vụ</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Thêm dịch vụ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên dịch vụ</th>
                    <th>Loại</th>
                    <th>Giá (VNĐ)</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($services as $service): ?>
                  <tr>
                    <td><?= $service['id'] ?></td>
                    <td><?= htmlspecialchars($service['name']) ?></td>
                    <td><?= ucfirst($service['type']) ?></td>
                    <td><?= formatCurrency($service['price']) ?></td>
                    <td><?= htmlspecialchars($service['description'] ?? '') ?></td>
                    <td>
                      <?php if ($service['status'] === 'hoạt động'): ?>
                        <span class="badge badge-success">Hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Không hoạt động</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $service['id'] ?>"
                        data-name="<?= htmlspecialchars($service['name']) ?>"
                        data-type="<?= $service['type'] ?>"
                        data-price="<?= $service['price'] ?>"
                        data-description="<?= htmlspecialchars($service['description'] ?? '') ?>"
                        data-status="<?= $service['status'] ?>"
                        data-toggle="modal" data-target="#editServiceModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $service['id'] ?>"
                        data-name="<?= htmlspecialchars($service['name']) ?>"
                        data-toggle="modal" data-target="#deleteServiceModal">
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

    <!-- Modal Thêm dịch vụ -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm dịch vụ mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên dịch vụ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Nhập tên dịch vụ" required>
              </div>
              <div class="form-group">
                <label>Loại dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control" name="type" required>
                  <option value="thư giãn">Thư giãn</option>
                  <option value="xoa bóp">Xoa bóp</option>
                  <option value="hỗ trợ">Hỗ trợ</option>
                </select>
              </div>
              <div class="form-group">
                <label>Giá (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" placeholder="Nhập giá dịch vụ" min="0" step="1000" required>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả dịch vụ"></textarea>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
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

    <!-- Modal Sửa dịch vụ -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa dịch vụ</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên dịch vụ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="edit-name" required>
              </div>
              <div class="form-group">
                <label>Loại dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="edit-type" required>
                  <option value="thư giãn">Thư giãn</option>
                  <option value="xoa bóp">Xoa bóp</option>
                  <option value="hỗ trợ">Hỗ trợ</option>
                </select>
              </div>
              <div class="form-group">
                <label>Giá (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" id="edit-price" min="0" step="1000" required>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" id="edit-description" rows="3"></textarea>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
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

    <!-- Modal Xóa dịch vụ -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header">
              <h5 class="modal-title">Xác nhận xóa</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa dịch vụ <strong id="delete-name"></strong>?</p>
              <p class="text-danger"><small>Hành động này không thể hoàn tác!</small></p>
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
    $('#edit-name').val($(this).data('name'));
    $('#edit-type').val($(this).data('type'));
    $('#edit-price').val($(this).data('price'));
    $('#edit-description').val($(this).data('description'));
    $('#edit-status').val($(this).data('status'));
  });

  // Điền dữ liệu vào modal xóa
  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});
</script>
