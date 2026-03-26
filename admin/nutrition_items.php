<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý nutrition items";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_SERVICES_NUTRITION
checkPermission('MANAGE_SERVICES_NUTRITION');

include '../includes/functions.php';

$db = getDB();

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $serving = sanitize($_POST['serving_desc']);
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
        $protein = !empty($_POST['protein']) ? floatval($_POST['protein']) : null;
        $carbs = !empty($_POST['carbs']) ? floatval($_POST['carbs']) : null;
        $fat = !empty($_POST['fat']) ? floatval($_POST['fat']) : null;
        $notes = sanitize($_POST['notes']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("INSERT INTO nutrition_items (name, serving_desc, calories, protein, carbs, fat, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $serving, $calories, $protein, $carbs, $fat, $notes, $status]);
            setFlashMessage('success', 'Thêm item thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_items.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $serving = sanitize($_POST['serving_desc']);
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
        $protein = !empty($_POST['protein']) ? floatval($_POST['protein']) : null;
        $carbs = !empty($_POST['carbs']) ? floatval($_POST['carbs']) : null;
        $fat = !empty($_POST['fat']) ? floatval($_POST['fat']) : null;
        $notes = sanitize($_POST['notes']);
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("UPDATE nutrition_items SET name = ?, serving_desc = ?, calories = ?, protein = ?, carbs = ?, fat = ?, notes = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $serving, $calories, $protein, $carbs, $fat, $notes, $status, $id]);
            setFlashMessage('success', 'Cập nhật item thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_items.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM nutrition_items WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa item thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_items.php');
        exit;
    }
}

// List
$stmt = $db->query("SELECT * FROM nutrition_items ORDER BY id DESC");
$items = $stmt->fetchAll();
$flash = getFlashMessage();

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Quản lý nutrition items</h1></div>
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

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Danh sách items</h3>
          <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">Thêm item</button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Định lượng</th>
                <th>Cal</th>
                <th>Protein</th>
                <th>Carbs</th>
                <th>Fat</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= $it['id'] ?></td>
                  <td><?= htmlspecialchars($it['name']) ?></td>
                  <td><?= htmlspecialchars($it['serving_desc'] ?? '-') ?></td>
                  <td><?= $it['calories'] !== null ? number_format($it['calories']) : '-' ?></td>
                  <td><?= $it['protein'] !== null ? $it['protein'] : '-' ?></td>
                  <td><?= $it['carbs'] !== null ? $it['carbs'] : '-' ?></td>
                  <td><?= $it['fat'] !== null ? $it['fat'] : '-' ?></td>
                  <td><?= $it['status'] === 'hoạt động' ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Không hoạt động</span>' ?></td>
                  <td>
                    <button class="btn btn-warning btn-sm btn-edit" 
                      data-id="<?= $it['id'] ?>"
                      data-name="<?= htmlspecialchars($it['name']) ?>"
                      data-serving="<?= htmlspecialchars($it['serving_desc'] ?? '') ?>"
                      data-calories="<?= $it['calories'] ?? '' ?>"
                      data-protein="<?= $it['protein'] ?? '' ?>"
                      data-carbs="<?= $it['carbs'] ?? '' ?>"
                      data-fat="<?= $it['fat'] ?? '' ?>"
                      data-notes="<?= htmlspecialchars($it['notes'] ?? '') ?>"
                      data-status="<?= $it['status'] ?>"
                      data-toggle="modal" data-target="#editModal">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $it['id'] ?>" data-name="<?= htmlspecialchars($it['name']) ?>" data-toggle="modal" data-target="#deleteModal"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- Add Modal -->
  <div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="nutrition_items.php" novalidate>
          <input type="hidden" name="action" value="add">
          <div class="modal-header"><h5 class="modal-title">Thêm item</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group"><label>Tên</label><input class="form-control" name="name" data-field="name"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Định lượng</label><input class="form-control" name="serving_desc" data-field="serving_desc"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Calories</label><input type="number" class="form-control" name="calories" min="0" data-field="calories"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-row">
              <div class="form-group col-md-4"><label>Protein</label><input class="form-control" name="protein" type="number" step="0.01" data-field="protein"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
              <div class="form-group col-md-4"><label>Carbs</label><input class="form-control" name="carbs" type="number" step="0.01" data-field="carbs"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
              <div class="form-group col-md-4"><label>Fat</label><input class="form-control" name="fat" type="number" step="0.01" data-field="fat"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            </div>
            <div class="form-group"><label>Ghi chú</label><textarea class="form-control" name="notes" data-field="notes"></textarea><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Trạng thái</label><select class="form-control" name="status" data-field="status"><option value="hoạt động">Hoạt động</option><option value="không hoạt động">Không hoạt động</option></select><small class="text-danger d-block mt-2" style="display:none;"></small></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu</button></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="nutrition_items.php" novalidate>
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          <div class="modal-header"><h5 class="modal-title">Sửa item</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group"><label>Tên</label><input class="form-control" name="name" id="edit-name" data-field="name"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Định lượng</label><input class="form-control" name="serving_desc" id="edit-serving" data-field="serving_desc"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Calories</label><input type="number" class="form-control" name="calories" id="edit-calories" min="0" data-field="calories"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-row">
              <div class="form-group col-md-4"><label>Protein</label><input class="form-control" name="protein" id="edit-protein" type="number" step="0.01" data-field="protein"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
              <div class="form-group col-md-4"><label>Carbs</label><input class="form-control" name="carbs" id="edit-carbs" type="number" step="0.01" data-field="carbs"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
              <div class="form-group col-md-4"><label>Fat</label><input class="form-control" name="fat" id="edit-fat" type="number" step="0.01" data-field="fat"><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            </div>
            <div class="form-group"><label>Ghi chú</label><textarea class="form-control" name="notes" id="edit-notes" data-field="notes"></textarea><small class="text-danger d-block mt-2" style="display:none;"></small></div>
            <div class="form-group"><label>Trạng thái</label><select class="form-control" name="status" id="edit-status" data-field="status"><option value="hoạt động">Hoạt động</option><option value="không hoạt động">Không hoạt động</option></select><small class="text-danger d-block mt-2" style="display:none;"></small></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button class="btn btn-primary">Cập nhật</button></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="nutrition_items.php">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="delete-id">
          <div class="modal-header bg-danger text-white"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body"><p>Bạn có chắc muốn xóa item <strong id="delete-name"></strong>?</p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-danger">Xóa</button></div>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>

<script>
$(function(){
  $('.btn-edit').on('click', function(){
    $('#edit-id').val($(this).data('id'));
    $('#edit-name').val($(this).data('name'));
    $('#edit-serving').val($(this).data('serving'));
    $('#edit-calories').val($(this).data('calories'));
    $('#edit-protein').val($(this).data('protein'));
    $('#edit-carbs').val($(this).data('carbs'));
    $('#edit-fat').val($(this).data('fat'));
    $('#edit-notes').val($(this).data('notes'));
    $('#edit-status').val($(this).data('status'));
  });
  $('.btn-delete').on('click', function(){
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});

(function() {
  function label(field) {
    if (field === 'name') return 'Vui lòng nhập tên món';
    if (field === 'serving_desc') return 'Vui lòng nhập định lượng';
    if (field === 'calories') return 'Vui lòng nhập calories hợp lệ';
    if (field === 'protein') return 'Vui lòng nhập protein hợp lệ';
    if (field === 'carbs') return 'Vui lòng nhập carbs hợp lệ';
    if (field === 'fat') return 'Vui lòng nhập fat hợp lệ';
    if (field === 'notes') return 'Vui lòng nhập ghi chú';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }
  function box(input) { return input.closest('.form-group')?.querySelector('small.text-danger') || null; }
  function show(input, message) { const b = box(input); if (b) { b.textContent = message; b.style.display = 'block'; } input.classList.add('is-invalid'); }
  function clear(input) { const b = box(input); if (b) { b.textContent = ''; b.style.display = 'none'; } input.classList.remove('is-invalid'); }
  function validate(input) {
    const field = input.getAttribute('data-field');
    const value = String(input.value || '').trim();
    clear(input);
    if (!field) return true;
    if (['calories','protein','carbs','fat'].includes(field)) { if (!value || Number(value) < 0 || Number.isNaN(Number(value))) { show(input, label(field)); return false; } return true; }
    if (!value) { show(input, label(field)); return false; }
    return true;
  }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>
