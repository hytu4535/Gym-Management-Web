<?php
$page_title = "Quản lý items trong thực đơn";
require_once '../includes/session.php';

$db = getDB();

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $plan_id = intval($_POST['nutrition_plan_id']);
        $item_id = intval($_POST['item_id']);
        $servings = !empty($_POST['servings_per_day']) ? floatval($_POST['servings_per_day']) : 1;
        $meal_time = sanitize($_POST['meal_time']);
        $note = sanitize($_POST['note']);
        try {
            $stmt = $db->prepare("INSERT INTO nutrition_plan_items (nutrition_plan_id, item_id, servings_per_day, meal_time, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$plan_id, $item_id, $servings, $meal_time, $note]);
            setFlashMessage('success', 'Thêm thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $plan_id = intval($_POST['nutrition_plan_id']);
        $item_id = intval($_POST['item_id']);
        $servings = !empty($_POST['servings_per_day']) ? floatval($_POST['servings_per_day']) : 1;
        $meal_time = sanitize($_POST['meal_time']);
        $note = sanitize($_POST['note']);
        try {
            $stmt = $db->prepare("UPDATE nutrition_plan_items SET nutrition_plan_id = ?, item_id = ?, servings_per_day = ?, meal_time = ?, note = ? WHERE id = ?");
            $stmt->execute([$plan_id, $item_id, $servings, $meal_time, $note, $id]);
            setFlashMessage('success', 'Cập nhật thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM nutrition_plan_items WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }
}

// List mappings with joins
$stmt = $db->query("SELECT npi.*, np.name AS plan_name, ni.name AS item_name, ni.calories AS item_cal FROM nutrition_plan_items npi JOIN nutrition_plans np ON np.id = npi.nutrition_plan_id JOIN nutrition_items ni ON ni.id = npi.item_id ORDER BY npi.id DESC");
$rows = $stmt->fetchAll();

// Plans and items for selects
$plans = $db->query("SELECT id, name FROM nutrition_plans WHERE status = 'hoạt động' ORDER BY name ASC")->fetchAll();
$items = $db->query("SELECT id, name, calories FROM nutrition_items WHERE status = 'hoạt động' ORDER BY name ASC")->fetchAll();

$flash = getFlashMessage();
include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Quản lý món trong thực đơn</h1></div>
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
          <h3 class="card-title">Danh sách liên kết plan ↔ items</h3>
          <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">Thêm</button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Plan</th>
                <th>Item</th>
                <th>Servings/ngày</th>
                <th>Calories/item</th>
                <th>Meal Time</th>
                <th>Ghi chú</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= $r['id'] ?></td>
                  <td><?= htmlspecialchars($r['plan_name']) ?></td>
                  <td><?= htmlspecialchars($r['item_name']) ?></td>
                  <td><?= $r['servings_per_day'] ?></td>
                  <td><?= $r['item_cal'] !== null ? number_format($r['item_cal']) : '-' ?></td>
                  <td><?= htmlspecialchars($r['meal_time'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['note'] ?? '') ?></td>
                  <td>
                    <button class="btn btn-warning btn-sm btn-edit"
                      data-id="<?= $r['id'] ?>"
                      data-plan_id="<?= $r['nutrition_plan_id'] ?>"
                      data-item_id="<?= $r['item_id'] ?>"
                      data-servings="<?= $r['servings_per_day'] ?>"
                      data-meal_time="<?= htmlspecialchars($r['meal_time'] ?? '') ?>"
                      data-note="<?= htmlspecialchars($r['note'] ?? '') ?>"
                      data-toggle="modal" data-target="#editModal"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $r['id'] ?>" data-toggle="modal" data-target="#deleteModal"><i class="fas fa-trash"></i></button>
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
        <form method="POST" action="nutrition_plan_items.php">
          <input type="hidden" name="action" value="add">
          <div class="modal-header"><h5 class="modal-title">Thêm liên kết</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group"><label>Plan</label><select name="nutrition_plan_id" class="form-control select2" required><option value="">-- Chọn --</option><?php foreach($plans as $p){ echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).'</option>'; } ?></select></div>
            <div class="form-group"><label>Item</label><select name="item_id" class="form-control select2" required><option value="">-- Chọn --</option><?php foreach($items as $it){ echo '<option value="'.$it['id'].'">'.htmlspecialchars($it['name']).' ('.($it['calories'] !== null ? $it['calories'].' kcal' : '—').')</option>'; } ?></select></div>
            <div class="form-group"><label>Servings per day</label><input class="form-control" name="servings_per_day" type="number" step="0.01" value="1"></div>
            <div class="form-group"><label>Meal time</label><input class="form-control" name="meal_time"></div>
            <div class="form-group"><label>Ghi chú</label><input class="form-control" name="note"></div>
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
        <form method="POST" action="nutrition_plan_items.php">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          <div class="modal-header"><h5 class="modal-title">Sửa liên kết</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group"><label>Plan</label><select name="nutrition_plan_id" id="edit-plan" class="form-control select2" required><option value="">-- Chọn --</option><?php foreach($plans as $p){ echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).'</option>'; } ?></select></div>
            <div class="form-group"><label>Item</label><select name="item_id" id="edit-item" class="form-control select2" required><option value="">-- Chọn --</option><?php foreach($items as $it){ echo '<option value="'.$it['id'].'">'.htmlspecialchars($it['name']).' ('.($it['calories'] !== null ? $it['calories'].' kcal' : '—').')</option>'; } ?></select></div>
            <div class="form-group"><label>Servings per day</label><input class="form-control" id="edit-servings" name="servings_per_day" type="number" step="0.01"></div>
            <div class="form-group"><label>Meal time</label><input class="form-control" id="edit-meal" name="meal_time"></div>
            <div class="form-group"><label>Ghi chú</label><input class="form-control" id="edit-note" name="note"></div>
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
        <form method="POST" action="nutrition_plan_items.php">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="delete-id">
          <div class="modal-header bg-danger text-white"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body"><p>Bạn có chắc muốn xóa mục này?</p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-danger">Xóa</button></div>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>

<script>
$(function(){
  if ($.fn.select2) $('.select2').select2({theme:'bootstrap4', placeholder:'Chọn...', allowClear:true});
  $('.btn-edit').on('click', function(){
    $('#edit-id').val($(this).data('id'));
    $('#edit-plan').val($(this).data('plan_id')).trigger('change');
    $('#edit-item').val($(this).data('item_id')).trigger('change');
    $('#edit-servings').val($(this).data('servings'));
    $('#edit-meal').val($(this).data('meal_time'));
    $('#edit-note').val($(this).data('note'));
  });
  $('.btn-delete').on('click', function(){ $('#delete-id').val($(this).data('id')); });
});
</script>
