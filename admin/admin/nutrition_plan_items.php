<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý items trong thực đơn";

include '../includes/database.php';

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $plan_id = intval($_POST['nutrition_plan_id']);
    $item_ids = [];
    if (isset($_POST['item_ids']) && is_array($_POST['item_ids'])) {
      $item_ids = array_map('intval', $_POST['item_ids']);
    } elseif (isset($_POST['item_id'])) {
      // Backward compatibility for old single-select payload.
      $item_ids = [intval($_POST['item_id'])];
    }
    $item_ids = array_values(array_filter(array_unique($item_ids), function ($id) {
      return $id > 0;
    }));
        $meal_time = limitTextLength(sanitize($_POST['meal_time'] ?? ''), 50);
        $note = limitTextLength(sanitize($_POST['note'] ?? ''), 255);
        try {
      if (empty($item_ids)) {
        setFlashMessage('danger', 'Vui lòng chọn ít nhất 1 món.');
        redirect('nutrition_plan_items.php');
        exit;
      }

      $db->beginTransaction();

      $checkStmt = $db->prepare("SELECT id FROM nutrition_plan_items WHERE nutrition_plan_id = ? AND item_id = ? LIMIT 1");
      if ($has_portion_unit) {
        $insertStmt = $db->prepare("INSERT INTO nutrition_plan_items (nutrition_plan_id, item_id, servings_per_day, portion_unit, meal_time, note) VALUES (?, ?, ?, ?, ?, ?)");
      } else {
        $insertStmt = $db->prepare("INSERT INTO nutrition_plan_items (nutrition_plan_id, item_id, servings_per_day, meal_time, note) VALUES (?, ?, ?, ?, ?)");
      }

      $inserted = 0;
      $skipped = 0;
      foreach ($item_ids as $item_id) {
        $checkStmt->execute([$plan_id, $item_id]);
        if ($checkStmt->fetch()) {
          $skipped++;
          continue;
        }

        $servings = getItemQty($item_id, 1);

        if ($has_portion_unit) {
          $insertStmt->execute([$plan_id, $item_id, $servings, 'khẩu phần', $meal_time, $note]);
        } else {
          $insertStmt->execute([$plan_id, $item_id, $servings, $meal_time, $note]);
        }
        $inserted++;
      }

      $db->commit();

      if ($inserted > 0 && $skipped > 0) {
        setFlashMessage('success', "Đã thêm $inserted món, bỏ qua $skipped món đã tồn tại trong plan.");
      } elseif ($inserted > 0) {
        setFlashMessage('success', "Đã thêm $inserted món thành công!");
      } else {
        setFlashMessage('warning', 'Không có món mới được thêm (các món đã tồn tại trong plan).');
      }
        } catch (PDOException $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      $id = intval($_POST['id']);
        $plan_id = intval($_POST['nutrition_plan_id']);
      $item_ids = [];
      if (isset($_POST['item_ids']) && is_array($_POST['item_ids'])) {
        $item_ids = array_map('intval', $_POST['item_ids']);
      } elseif (isset($_POST['item_id'])) {
        // Backward compatibility for old single-select payload.
        $item_ids = [intval($_POST['item_id'])];
      }
      $item_ids = array_values(array_filter(array_unique($item_ids), function ($v) {
        return $v > 0;
      }));
        $meal_time = limitTextLength(sanitize($_POST['meal_time'] ?? ''), 50);
        $note = limitTextLength(sanitize($_POST['note'] ?? ''), 255);
        try {
        if (empty($item_ids)) {
          setFlashMessage('danger', 'Vui lòng chọn ít nhất 1 món.');
          redirect('nutrition_plan_items.php');
          exit;
        }

        $db->beginTransaction();

        // Edit now means replacing the whole menu of selected plan.
        $deleteByPlanStmt = $db->prepare("DELETE FROM nutrition_plan_items WHERE nutrition_plan_id = ?");
        if ($has_portion_unit) {
          $insertStmt = $db->prepare("INSERT INTO nutrition_plan_items (nutrition_plan_id, item_id, servings_per_day, portion_unit, meal_time, note) VALUES (?, ?, ?, ?, ?, ?)");
        } else {
          $insertStmt = $db->prepare("INSERT INTO nutrition_plan_items (nutrition_plan_id, item_id, servings_per_day, meal_time, note) VALUES (?, ?, ?, ?, ?)");
        }

        $deleteByPlanStmt->execute([$plan_id]);

        $inserted = 0;
        foreach ($item_ids as $item_id) {
          $servings = getItemQty($item_id, 1);
          if ($has_portion_unit) {
            $insertStmt->execute([$plan_id, $item_id, $servings, 'khẩu phần', $meal_time, $note]);
          } else {
            $insertStmt->execute([$plan_id, $item_id, $servings, $meal_time, $note]);
          }
          $inserted++;
        }

        $db->commit();
        setFlashMessage('success', "Đã cập nhật thực đơn plan thành công ($inserted món).");
        } catch (PDOException $e) {
        if ($db->inTransaction()) {
          $db->rollBack();
        }
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
      $plan_id = isset($_POST['nutrition_plan_id']) ? intval($_POST['nutrition_plan_id']) : 0;
        try {
        if ($plan_id > 0) {
          $stmt = $db->prepare("DELETE FROM nutrition_plan_items WHERE nutrition_plan_id = ?");
          $stmt->execute([$plan_id]);
        } else {
          $stmt = $db->prepare("DELETE FROM nutrition_plan_items WHERE id = ?");
          $stmt->execute([$id]);
        }
            setFlashMessage('success', 'Xóa thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('nutrition_plan_items.php');
        exit;
    }
}

// List mappings grouped by plan: show all items in one row and total calories recalculated.
$portionUnitExpr = $has_portion_unit ? "COALESCE(npi.portion_unit, 'khẩu phần')" : "'khẩu phần'";

$stmt = $db->query("SELECT
  MIN(npi.id) AS id,
  npi.nutrition_plan_id,
  np.name AS plan_name,
  GROUP_CONCAT(DISTINCT ni.id ORDER BY ni.name SEPARATOR ',') AS item_ids,
  GROUP_CONCAT(CONCAT(ni.id, ':', CAST(ROUND(COALESCE(npi.servings_per_day, 1), 0) AS UNSIGNED)) ORDER BY ni.name SEPARATOR ',') AS item_qty_pairs,
  GROUP_CONCAT(DISTINCT ni.name ORDER BY ni.name SEPARATOR ', ') AS item_names,
  SUM(COALESCE(ni.calories, 0) * COALESCE(npi.servings_per_day, 1)) AS total_calories,
  SUBSTRING_INDEX(GROUP_CONCAT(npi.meal_time ORDER BY npi.id SEPARATOR '||'), '||', 1) AS sample_meal_time,
  SUBSTRING_INDEX(GROUP_CONCAT(npi.note ORDER BY npi.id SEPARATOR '||'), '||', 1) AS sample_note,
  GROUP_CONCAT(DISTINCT CONCAT(ni.name, ' x', CAST(ROUND(COALESCE(npi.servings_per_day, 1), 0) AS UNSIGNED)) ORDER BY ni.name SEPARATOR ', ') AS portion_summary,
  GROUP_CONCAT(DISTINCT npi.meal_time ORDER BY npi.meal_time SEPARATOR ', ') AS meal_times,
  GROUP_CONCAT(DISTINCT npi.note ORDER BY npi.note SEPARATOR ' | ') AS notes,
  AVG(COALESCE(npi.servings_per_day, 1)) AS avg_servings
FROM nutrition_plan_items npi
JOIN nutrition_plans np ON np.id = npi.nutrition_plan_id
JOIN nutrition_items ni ON ni.id = npi.item_id
GROUP BY npi.nutrition_plan_id, np.name
ORDER BY npi.nutrition_plan_id DESC");
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
              <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">Thêm</button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Plan</th>
                <th>Items</th>
                <th>Định lượng</th>
                <th>Tổng Calories/ngày</th>
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
                  <td><?= htmlspecialchars($r['item_names'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['portion_summary'] ?? '-') ?></td>
                  <td><?= number_format((float)($r['total_calories'] ?? 0), 0) ?></td>
                  <td><?= htmlspecialchars($r['meal_times'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                  <td>
                    <button class="btn btn-warning btn-sm btn-edit"
                      data-id="<?= $r['id'] ?>"
                      data-plan_id="<?= $r['nutrition_plan_id'] ?>"
                      data-item_ids="<?= htmlspecialchars($r['item_ids'] ?? '') ?>"
                      data-item_qtys="<?= htmlspecialchars($r['item_qty_pairs'] ?? '') ?>"
                      data-meal_time="<?= htmlspecialchars($r['sample_meal_time'] ?? '') ?>"
                      data-note="<?= htmlspecialchars($r['sample_note'] ?? '') ?>"
                      data-toggle="modal" data-target="#editModal"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $r['id'] ?>" data-plan_id="<?= $r['nutrition_plan_id'] ?>" data-toggle="modal" data-target="#deleteModal"><i class="fas fa-trash"></i></button>
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
        <form method="POST" action="nutrition_plan_items.php" novalidate>
          <input type="hidden" name="action" value="add">
          <div class="modal-header"><h5 class="modal-title">Thêm liên kết</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Plan <span class="text-danger">*</span></label>
              <select name="nutrition_plan_id" class="form-control select2" data-field="nutrition_plan_id" style="width: 100%;">
                <option value="">-- Chọn --</option>
                <?php foreach($plans as $p){ echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).'</option>'; } ?>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Items (chọn nhiều món)</label>
              <div class="dropdown item-picker" id="add-item-picker">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 item-picker-toggle" type="button" data-field="item_ids" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="picker-label text-muted">Chọn món...</span>
                </button>
                <div class="dropdown-menu">
                  <?php foreach($items as $it):
                    $itemLabel = htmlspecialchars($it['name']);
                  ?>
                    <div class="dropdown-item d-flex justify-content-between align-items-center mb-0">
                      <label class="mb-0 flex-grow-1">
                        <input type="checkbox" class="item-check" name="item_ids[]" value="<?= $it['id'] ?>" data-label="<?= $itemLabel ?>">
                        <?= $itemLabel ?>
                      </label>
                      <div class="qty-control ml-2">
                        <input type="number" class="item-qty-input" name="item_qty[<?= $it['id'] ?>]" value="1" min="1" step="1">
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <small class="text-danger d-block mt-2 item-picker-error" style="display:none;"></small>
              <small class="form-text text-muted">Bấm mũi tên để mở danh sách rồi tick các món muốn chọn.</small>
            </div>
            <div class="form-group">
              <label>Meal time <span class="text-danger">*</span></label>
              <input class="form-control" name="meal_time" data-field="meal_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
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
        <form method="POST" action="nutrition_plan_items.php" novalidate>
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          <div class="modal-header"><h5 class="modal-title">Sửa liên kết</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Plan <span class="text-danger">*</span></label>
              <select name="nutrition_plan_id" id="edit-plan" class="form-control select2" data-field="nutrition_plan_id" style="width: 100%;">
                <option value="">-- Chọn --</option>
                <?php foreach($plans as $p){ echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).'</option>'; } ?>
              </select>
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
            <div class="form-group">
              <label>Items (có thể chọn nhiều món)</label>
              <div class="dropdown item-picker" id="edit-item-picker">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 item-picker-toggle" type="button" data-field="item_ids" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="picker-label text-muted">Chọn món...</span>
                </button>
                <div class="dropdown-menu">
                  <?php foreach($items as $it):
                    $itemLabel = htmlspecialchars($it['name']);
                  ?>
                    <div class="dropdown-item d-flex justify-content-between align-items-center mb-0">
                      <label class="mb-0 flex-grow-1">
                        <input type="checkbox" class="item-check" name="item_ids[]" value="<?= $it['id'] ?>" data-label="<?= $itemLabel ?>">
                        <?= $itemLabel ?>
                      </label>
                      <div class="qty-control ml-2">
                        <input type="number" class="item-qty-input" name="item_qty[<?= $it['id'] ?>]" value="1" min="1" step="1">
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <small class="text-danger d-block mt-2 item-picker-error" style="display:none;"></small>
              <small class="form-text text-muted">Khi sửa, hệ thống sẽ cập nhật dòng hiện tại và thêm các món mới bạn chọn (nếu chưa có).</small>
            </div>
            <div class="form-group">
              <label>Meal time <span class="text-danger">*</span></label>
              <input class="form-control" id="edit-meal" name="meal_time" data-field="meal_time">
              <small class="text-danger d-block mt-2" style="display:none;"></small>
            </div>
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
          <input type="hidden" name="nutrition_plan_id" id="delete-plan-id">
          <div class="modal-header bg-danger text-white"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body"><p>Bạn có chắc muốn xóa toàn bộ thực đơn của plan này?</p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-danger">Xóa</button></div>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>

<style>
  .item-picker .dropdown-toggle {
    text-align: left;
  }
  .item-picker .dropdown-menu {
    max-height: 240px;
    overflow-y: auto;
    width: 100%;
  }
  .item-picker .dropdown-item {
    white-space: normal;
    cursor: pointer;
    padding: 0.35rem 0.5rem;
  }
  .item-picker .dropdown-item input {
    margin-right: 8px;
  }
  .item-picker .qty-control {
    display: inline-flex;
    align-items: center;
    gap: 0;
  }
  .item-picker .item-qty-input {
    width: 64px;
    height: 28px;
    text-align: center;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0 4px;
    font-size: 13px;
  }
</style>

<script>
$(function(){
  if ($.fn.select2) $('.select2').select2({theme:'bootstrap4', placeholder:'Chọn...', allowClear:true});

  function renderPickerLabel($picker){
    function fmt(v){
      var n = parseInt(v || 0, 10);
      if (!isFinite(n) || n < 1) n = 1;
      return String(n);
    }

    var selectedLabels = [];
    $picker.find('.item-check:checked').each(function(){
      var qty = $(this).closest('.dropdown-item').find('.item-qty-input').val() || 1;
      selectedLabels.push($(this).data('label') + ' x' + fmt(qty));
    });

    var text = 'Chọn món...';
    var muted = true;
    if (selectedLabels.length === 1) {
      text = selectedLabels[0];
      muted = false;
    } else if (selectedLabels.length === 2) {
      text = selectedLabels[0] + ', ' + selectedLabels[1];
      muted = false;
    } else if (selectedLabels.length > 2) {
      text = selectedLabels[0] + ', ' + selectedLabels[1] + ' +' + (selectedLabels.length - 2) + ' món';
      muted = false;
    }

    var $label = $picker.find('.picker-label');
    $label.text(text);
    $label.toggleClass('text-muted', muted);
  }

  function renderPickerError($picker, message) {
    var $error = $picker.closest('.form-group').find('.item-picker-error');
    if (!$error.length) return;
    if (message) {
      $error.text(message).show();
    } else {
      $error.text('').hide();
    }
  }

  function renderPlanError($field, message) {
    var $group = $field.closest('.form-group');
    var $error = $group.find('small.text-danger').not('.item-picker-error').first();
    if (!$error.length) return;
    if (message) {
      $error.text(message).show();
      $field.addClass('is-invalid');
    } else {
      $error.text('').hide();
      $field.removeClass('is-invalid');
    }
  }

  function renderSimpleFieldError($field, message) {
    var $group = $field.closest('.form-group');
    var $error = $group.find('small.text-danger').not('.item-picker-error').first();
    if (!$error.length) return;
    if (message) {
      $error.text(message).show();
      $field.addClass('is-invalid');
    } else {
      $error.text('').hide();
      $field.removeClass('is-invalid');
    }
  }

  function initItemPicker(selector){
    var $picker = $(selector);
    if (!$picker.length) return;

    renderPickerLabel($picker);

    $picker.on('change', '.item-check', function(){
      if ($picker.find('.item-check:checked').length > 0) {
        renderPickerError($picker, '');
      }
      renderPickerLabel($picker);
    });

    $picker.on('input change', '.item-qty-input', function(){
      var val = parseInt($(this).val() || 1, 10);
      if (!isFinite(val) || val < 1) val = 1;
      $(this).val(val);
      $(this).closest('.dropdown-item').find('.item-check').prop('checked', true);
      renderPickerError($picker, '');
      renderPickerLabel($picker);
    });

    // Keep dropdown open while ticking multiple items.
    $picker.find('.dropdown-menu').on('click', function(e){
      e.stopPropagation();
    });
  }

  function setPickerChecked(selector, ids, qtyMap){
    function fmt(v){
      var n = parseInt(v || 1, 10);
      if (!isFinite(n) || n < 1) n = 1;
      return n;
    }

    var idSet = {};
    (ids || []).forEach(function(v){ idSet[String(v)] = true; });
    qtyMap = qtyMap || {};
    var $picker = $(selector);
    $picker.find('.item-check').each(function(){
      var id = String($(this).val());
      var checked = !!idSet[id];
      $(this).prop('checked', checked);
      var $row = $(this).closest('.dropdown-item');
      var qty = fmt(qtyMap[id]);
      $row.find('.item-qty-input').val(qty);
    });
    renderPickerLabel($picker);
    renderPickerError($picker, '');
  }

  function validatePickerOnSubmit(formSelector, pickerSelector){
    $(formSelector).on('submit', function(e){
      var ok = true;
      var $plan = $(this).find('[name="nutrition_plan_id"]');
      var $mealTime = $(this).find('[name="meal_time"]');
      if (!$plan.val()) {
        renderPlanError($plan, 'Vui lòng chọn plan.');
        ok = false;
      } else {
        renderPlanError($plan, '');
      }

      if (!$mealTime.val() || !String($mealTime.val()).trim()) {
        renderSimpleFieldError($mealTime, 'Vui lòng nhập Meal time.');
        ok = false;
      } else {
        renderSimpleFieldError($mealTime, '');
      }

      var checkedCount = $(pickerSelector).find('.item-check:checked').length;
      if (checkedCount === 0) {
        renderPickerError($(pickerSelector), 'Vui lòng chọn ít nhất 1 món.');
        ok = false;
      } else {
        renderPickerError($(pickerSelector), '');
      }

      if (!ok) {
        e.preventDefault();
      }
    });
  }

  initItemPicker('#add-item-picker');
  initItemPicker('#edit-item-picker');
  validatePickerOnSubmit('#addModal form', '#add-item-picker');
  validatePickerOnSubmit('#editModal form', '#edit-item-picker');

  $('.btn-edit').on('click', function(){
    $('#edit-id').val($(this).data('id'));
    $('#edit-plan').val($(this).data('plan_id')).trigger('change');
    var itemIdsRaw = String($(this).data('item_ids') || '');
    var itemIds = itemIdsRaw ? itemIdsRaw.split(',').map(function(v){ return v.trim(); }).filter(function(v){ return v !== ''; }) : [];
    var qtyRaw = String($(this).data('item_qtys') || '');
    var qtyMap = {};
    if (qtyRaw) {
      qtyRaw.split(',').forEach(function(pair){
        var p = pair.split(':');
        if (p.length === 2) qtyMap[String(p[0]).trim()] = parseFloat(p[1]);
      });
    }
    setPickerChecked('#edit-item-picker', itemIds, qtyMap);
    $('#edit-meal').val($(this).data('meal_time'));
    $('#edit-note').val($(this).data('note'));
  });
  $('.btn-delete').on('click', function(){
    $('#delete-id').val($(this).data('id'));
    $('#delete-plan-id').val($(this).data('plan_id'));
  });

  $(document).on('input change', '#addModal [data-field], #editModal [data-field]', function() {
    var $field = $(this);
    var value = String($field.val() || '').trim();
    if ($field.attr('data-field') === 'nutrition_plan_id') {
      renderPlanError($field, value ? '' : 'Vui lòng chọn plan.');
      return;
    }
    if ($field.attr('data-field') === 'meal_time') {
      renderSimpleFieldError($field, value ? '' : 'Vui lòng nhập Meal time.');
      return;
    }
    var box = $field.closest('.form-group').find('small.text-danger').not('.item-picker-error');
    if (value) {
      box.text('').hide();
      $field.removeClass('is-invalid');
    }
  });

  $(document).on('change', '#addModal [name="nutrition_plan_id"], #editModal [name="nutrition_plan_id"]', function() {
    renderPlanError($(this), $(this).val() ? '' : 'Vui lòng chọn plan.');
  });
});
</script>
