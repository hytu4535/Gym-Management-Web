<?php 
$page_title = "Quản lý chế độ dinh dưỡng";
require_once '../includes/session.php';

$db = getDB();

// Helper: calculate total calories for a plan from items
function calculatePlanCalories($db, $plan_id) {
  $stmt = $db->prepare("SELECT SUM(ni.calories * npi.servings_per_day) AS calc
    FROM nutrition_plan_items npi
    JOIN nutrition_items ni ON ni.id = npi.item_id
    WHERE npi.nutrition_plan_id = ?");
  $stmt->execute([$plan_id]);
  $row = $stmt->fetch();
  return ($row && $row['calc']) ? (int)$row['calc'] : null;
}

// Xử lý thêm chế độ dinh dưỡng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
        $bmi_range = sanitize($_POST['bmi_range']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("INSERT INTO nutrition_plans (name, type, calories, bmi_range, description, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $calories, $bmi_range, $description, $status]);
            setFlashMessage('success', 'Thêm chế độ dinh dưỡng thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plans.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
        $bmi_range = sanitize($_POST['bmi_range']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("UPDATE nutrition_plans SET name = ?, type = ?, calories = ?, bmi_range = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $type, $calories, $bmi_range, $description, $status, $id]);
            setFlashMessage('success', 'Cập nhật chế độ dinh dưỡng thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plans.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM nutrition_plans WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa chế độ dinh dưỡng thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa chế độ dinh dưỡng. ' . $e->getMessage());
        }
        redirect('nutrition_plans.php');
        exit;
    }
}

// Lấy danh sách chế độ dinh dưỡng
$stmt = $db->query("SELECT * FROM nutrition_plans ORDER BY id DESC");
$plans = $stmt->fetchAll();

// Compute calculated calories (from items) for each plan when available
foreach ($plans as &$plan) {
  $plan['calculated_calories'] = calculatePlanCalories($db, $plan['id']);
}
unset($plan);

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
            <h1 class="m-0">Quản lý chế độ dinh dưỡng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Chế độ dinh dưỡng</li>
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
                <h3 class="card-title">Danh sách chế độ dinh dưỡng</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addNutritionModal">
                    <i class="fas fa-plus"></i> Thêm chế độ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên chế độ</th>
                    <th>Loại</th>
                    <th>Calories/ngày</th>
                    <th>BMI phù hợp</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($plans as $plan): ?>
                  <tr>
                    <td><?= $plan['id'] ?></td>
                    <td><?= htmlspecialchars($plan['name']) ?></td>
                    <td><?= ucfirst($plan['type']) ?></td>
                    <?php $display_cal = $plan['calculated_calories'] ?? $plan['calories']; ?>
                    <td><?= $display_cal ? number_format($display_cal) . ' kcal' : '-' ?></td>
                    <td><?= htmlspecialchars($plan['bmi_range'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($plan['description'] ?? '') ?></td>
                    <td>
                      <?php if ($plan['status'] === 'hoạt động'): ?>
                        <span class="badge badge-success">Hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Không hoạt động</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $plan['id'] ?>"
                        data-name="<?= htmlspecialchars($plan['name']) ?>"
                        data-type="<?= $plan['type'] ?>"
                        data-calories="<?= $plan['calories'] ?? '' ?>"
                        data-bmi="<?= htmlspecialchars($plan['bmi_range'] ?? '') ?>"
                        data-description="<?= htmlspecialchars($plan['description'] ?? '') ?>"
                        data-status="<?= $plan['status'] ?>"
                        data-toggle="modal" data-target="#editNutritionModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $plan['id'] ?>"
                        data-name="<?= htmlspecialchars($plan['name']) ?>"
                        data-toggle="modal" data-target="#deleteNutritionModal">
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

    <!-- Modal Thêm chế độ dinh dưỡng -->
    <div class="modal fade" id="addNutritionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="nutrition_plans.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm chế độ dinh dưỡng mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên chế độ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Nhập tên chế độ dinh dưỡng" required>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" required>
                  <option value="tăng cân">tăng cân</option>
                  <option value="giảm cân">giảm cân</option>
                  <option value="tư vấn">tư vấn</option>
                  <option value="duy trì">duy trì</option>
                  <option value="tăng cơ">tăng cơ</option>
                  <option value="giảm mỡ">giảm mỡ</option>
                  <option value="khác">khác</option>
                </select>
              </div>
              <div class="form-group">
                <label>Calories/ngày</label>
                <input type="number" class="form-control" name="calories" placeholder="Nhập tổng calo/ngày" min="0">
              </div>
              <div class="form-group">
                <label>BMI phù hợp</label>
                <input type="text" class="form-control" name="bmi_range" placeholder="VD: 18.5 - 25">
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả chi tiết"></textarea>
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

    <!-- Modal Sửa chế độ dinh dưỡng -->
    <div class="modal fade" id="editNutritionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="nutrition_plans.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa chế độ dinh dưỡng</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên chế độ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="edit-name" required>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="edit-type" required>
                  <option value="tăng cân">tăng cân</option>
                  <option value="giảm cân">giảm cân</option>
                  <option value="tư vấn">tư vấn</option>
                  <option value="duy trì">duy trì</option>
                  <option value="tăng cơ">tăng cơ</option>
                  <option value="giảm mỡ">giảm mỡ</option>
                  <option value="khác">khác</option>
                </select>
              </div>
              <div class="form-group">
                <label>Calories/ngày</label>
                <input type="number" class="form-control" name="calories" id="edit-calories" min="0">
              </div>
              <div class="form-group">
                <label>BMI phù hợp</label>
                <input type="text" class="form-control" name="bmi_range" id="edit-bmi">
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

    <!-- Modal Xóa chế độ dinh dưỡng -->
    <div class="modal fade" id="deleteNutritionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="nutrition_plans.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header">
              <h5 class="modal-title">Xác nhận xóa</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa chế độ <strong id="delete-name"></strong>?</p>
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
    $('#edit-calories').val($(this).data('calories'));
    $('#edit-bmi').val($(this).data('bmi'));
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
