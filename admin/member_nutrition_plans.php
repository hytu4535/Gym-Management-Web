<?php
session_start(); // luôn khởi tạo session

$page_title = "Gán dinh dưỡng cho hội viên";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_SERVICES_NUTRITION
checkPermission('MANAGE_SERVICES_NUTRITION');

include '../includes/functions.php';

$db = getDB();

function isPastDate($date)
{
  if (empty($date)) {
    return false;
  }

  try {
    $today = new DateTimeImmutable('today');
    $endDate = new DateTimeImmutable($date);
    return $endDate < $today;
  } catch (Exception $e) {
    return false;
  }
}

try {
  $db->exec("UPDATE member_nutrition_plans SET status = 'kết thúc' WHERE end_date IS NOT NULL AND end_date < CURDATE() AND status <> 'kết thúc'");
} catch (PDOException $e) {
  // Không chặn trang nếu đồng bộ trạng thái tự động gặp lỗi.
}

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

// Helper: estimate daily calories from member weight (fallback)
function estimateCaloriesFromMember($db, $member_id) {
  // try latest bmi_measurements
  $stmt = $db->prepare("SELECT weight FROM bmi_measurements WHERE member_id = ? ORDER BY measured_at DESC LIMIT 1");
  $stmt->execute([$member_id]);
  $r = $stmt->fetch();
  if ($r && !empty($r['weight'])) {
    $weight = (float)$r['weight'];
  } else {
    // fallback to members table
    $stmt = $db->prepare("SELECT weight FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $r2 = $stmt->fetch();
    $weight = ($r2 && !empty($r2['weight'])) ? (float)$r2['weight'] : null;
  }
  if (!$weight) return null;
  // rough estimate: 24 kcal per kg bodyweight (approximate BMR * activity)
  return (int) round(24 * $weight);
}

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
    checkPermission('MANAGE_SERVICES_NUTRITION', 'add');

        $member_id = intval($_POST['member_id']);
        $nutrition_plan_id = intval($_POST['nutrition_plan_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        $status = sanitize($_POST['status']);

      if ($end_date !== null && isPastDate($end_date)) {
        $status = 'kết thúc';
      }

        try {
            $stmt = $db->prepare("INSERT INTO member_nutrition_plans (member_id, nutrition_plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $nutrition_plan_id, $start_date, $end_date, $status]);
            setFlashMessage('success', 'Gán chế độ dinh dưỡng thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('member_nutrition_plans.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'edit');

        $id = intval($_POST['id']);
        $member_id = intval($_POST['member_id']);
        $nutrition_plan_id = intval($_POST['nutrition_plan_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        $status = sanitize($_POST['status']);

      $currentStmt = $db->prepare("SELECT end_date FROM member_nutrition_plans WHERE id = ? LIMIT 1");
      $currentStmt->execute([$id]);
      $currentRecord = $currentStmt->fetch(PDO::FETCH_ASSOC);

      if (!$currentRecord) {
        setFlashMessage('danger', 'Không tìm thấy bản ghi cần sửa.');
        redirect('member_nutrition_plans.php');
        exit;
      }

      if (!empty($currentRecord['end_date']) && isPastDate($currentRecord['end_date'])) {
        setFlashMessage('danger', 'Bản ghi này đã quá ngày kết thúc nên không thể sửa nữa.');
        redirect('member_nutrition_plans.php');
        exit;
      }

      if ($end_date !== null && isPastDate($end_date)) {
        $status = 'kết thúc';
      }

        try {
            $stmt = $db->prepare("UPDATE member_nutrition_plans SET member_id = ?, nutrition_plan_id = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$member_id, $nutrition_plan_id, $start_date, $end_date, $status, $id]);
            setFlashMessage('success', 'Cập nhật thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('member_nutrition_plans.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'delete');

        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM member_nutrition_plans WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('member_nutrition_plans.php');
        exit;
    }
}

// Lấy danh sách dinh dưỡng đã gán kèm tên hội viên và chế độ
$stmt = $db->query("
    SELECT mnp.*, 
           m.full_name AS member_name,
           np.name AS plan_name, np.type AS plan_type, np.calories, np.bmi_range
    FROM member_nutrition_plans mnp
    LEFT JOIN members m ON mnp.member_id = m.id
    LEFT JOIN nutrition_plans np ON mnp.nutrition_plan_id = np.id
    ORDER BY mnp.start_date DESC
");
$records = $stmt->fetchAll();

foreach ($records as &$record) {
  $record['is_expired'] = !empty($record['end_date']) && isPastDate($record['end_date']);
  if ($record['is_expired']) {
    $record['status'] = 'kết thúc';
  }
}
unset($record);

// Lấy danh sách hội viên (active)
$stmt = $db->query("SELECT id, full_name, phone FROM members WHERE status = 'active' ORDER BY full_name ASC");
$members = $stmt->fetchAll();

// Lấy danh sách chế độ dinh dưỡng (hoạt động)
$stmt = $db->query("SELECT id, name, type, calories, bmi_range FROM nutrition_plans WHERE status = 'hoạt động' ORDER BY name ASC");
$plans = $stmt->fetchAll();

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
            <h1 class="m-0">Gán dinh dưỡng cho hội viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dinh dưỡng hội viên</li>
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
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-success"><i class="fas fa-apple-alt"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tổng đã gán</span>
                <span class="info-box-number"><?= count($records) ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="fas fa-check-circle"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Đang áp dụng</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return $r['status'] === 'đã áp dụng'; })) ?>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-secondary"><i class="fas fa-flag-checkered"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Đã kết thúc</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return $r['status'] === 'kết thúc'; })) ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách dinh dưỡng đã gán</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus"></i> Gán chế độ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Chế độ dinh dưỡng</th>
                    <th>Loại</th>
                    <th>Calo/ngày</th>
                    <th>BMI phù hợp</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($records as $row): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['member_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['plan_name'] ?? 'N/A') ?></td>
                    <td>
                      <?php
                        $is_menu = calculatePlanCalories($db, $row['nutrition_plan_id']) !== null;
                      ?>
                      <?php if ($is_menu): ?>
                        <span class="badge badge-success">Thực đơn</span>
                      <?php else: ?>
                        <span class="badge badge-info"><?= htmlspecialchars($row['plan_type'] ?? 'tư vấn') ?></span>
                      <?php endif; ?>
                    </td>
                    <?php
                      $calc = calculatePlanCalories($db, $row['nutrition_plan_id']);
                      if ($calc) {
                        $cal_display = number_format($calc) . ' kcal';
                      } elseif (!empty($row['calories'])) {
                        $cal_display = number_format($row['calories']) . ' kcal';
                      } else {
                        $est = estimateCaloriesFromMember($db, $row['member_id']);
                        $cal_display = $est ? number_format($est) . ' kcal' : '<span class="text-muted">—</span>';
                      }
                    ?>
                    <td><?= $cal_display ?></td>
                    <td><?= htmlspecialchars($row['bmi_range'] ?? '—') ?></td>
                    <td><?= date('d/m/Y', strtotime($row['start_date'])) ?></td>
                    <td><?= $row['end_date'] ? date('d/m/Y', strtotime($row['end_date'])) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                      <?php if ($row['status'] === 'đã áp dụng'): ?>
                        <span class="badge badge-success">Đang áp dụng</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Đã kết thúc</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $row['id'] ?>"
                        data-member_id="<?= $row['member_id'] ?>"
                        data-nutrition_plan_id="<?= $row['nutrition_plan_id'] ?>"
                        data-start_date="<?= $row['start_date'] ?>"
                        data-end_date="<?= $row['end_date'] ?? '' ?>"
                        data-status="<?= htmlspecialchars($row['status']) ?>"
                        <?= !empty($row['is_expired']) ? 'disabled title="Bản ghi đã quá ngày kết thúc nên không thể sửa"' : 'data-toggle="modal" data-target="#editModal"' ?>>
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $row['id'] ?>"
                        data-member="<?= htmlspecialchars($row['member_name'] ?? 'N/A') ?>"
                        data-plan="<?= htmlspecialchars($row['plan_name'] ?? 'N/A') ?>"
                        data-toggle="modal" data-target="#deleteModal">
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

    <!-- Modal Thêm -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_nutrition_plans.php" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary">
              <h5 class="modal-title"><i class="fas fa-apple-alt"></i> Gán chế độ dinh dưỡng</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control select2" name="member_id" data-field="member_id" style="width: 100%;">
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($members)): ?>
                  <small class="text-danger">Chưa có hội viên nào.</small>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label>Chế độ dinh dưỡng <span class="text-danger">*</span></label>
                <select class="form-control select2" name="nutrition_plan_id" data-field="nutrition_plan_id" style="width: 100%;">
                  <option value="">-- Chọn chế độ --</option>
                  <?php foreach ($plans as $plan): ?>
                    <option value="<?= $plan['id'] ?>">
                      <?= htmlspecialchars($plan['name']) ?> (<?= $plan['type'] ?>)
                      <?= $plan['calories'] ? ' - ' . number_format($plan['calories']) . ' kcal' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($plans)): ?>
                  <small class="text-danger">Chưa có chế độ dinh dưỡng nào.</small>
                <?php endif; ?>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" data-field="start_date" value="<?= date('Y-m-d') ?>">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" data-field="end_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" data-field="status">
                  <option value="đã áp dụng">Đang áp dụng</option>
                  <option value="kết thúc">Đã kết thúc</option>
                </select>
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

    <!-- Modal Sửa -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_nutrition_plans.php" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header bg-warning">
              <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa dinh dưỡng hội viên</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control" name="member_id" id="edit-member_id" data-field="member_id">
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
                <label>Chế độ dinh dưỡng <span class="text-danger">*</span></label>
                <select class="form-control" name="nutrition_plan_id" id="edit-nutrition_plan_id" data-field="nutrition_plan_id">
                  <option value="">-- Chọn chế độ --</option>
                  <?php foreach ($plans as $plan): ?>
                    <option value="<?= $plan['id'] ?>">
                      <?= htmlspecialchars($plan['name']) ?> (<?= $plan['type'] ?>)
                      <?= $plan['calories'] ? ' - ' . number_format($plan['calories']) . ' kcal' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" id="edit-start_date" data-field="start_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" id="edit-end_date" data-field="end_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status" data-field="status">
                  <option value="đã áp dụng">Đang áp dụng</option>
                  <option value="kết thúc">Đã kết thúc</option>
                </select>
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

    <!-- Modal Xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_nutrition_plans.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header bg-danger">
              <h5 class="modal-title text-white"><i class="fas fa-trash"></i> Xác nhận xóa</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa chế độ <strong id="delete-plan"></strong> của hội viên <strong id="delete-member"></strong>?</p>
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
  if ($.fn.select2) {
    $('.select2').select2({ theme: 'bootstrap4', placeholder: 'Tìm kiếm...', allowClear: true });
  }

  $('.btn-edit').on('click', function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-member_id').val($(this).data('member_id'));
    $('#edit-nutrition_plan_id').val($(this).data('nutrition_plan_id'));
    $('#edit-start_date').val($(this).data('start_date'));
    $('#edit-end_date').val($(this).data('end_date'));
    $('#edit-status').val($(this).data('status'));
  });

  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-member').text($(this).data('member'));
    $('#delete-plan').text($(this).data('plan'));
  });
});

(function() {
  function label(field) {
    if (field === 'member_id') return 'Vui lòng chọn hội viên';
    if (field === 'nutrition_plan_id') return 'Vui lòng chọn chế độ dinh dưỡng';
    if (field === 'start_date') return 'Vui lòng chọn ngày bắt đầu';
    if (field === 'end_date') return 'Vui lòng chọn ngày kết thúc';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }
  function box(input) { return input.closest('.form-group')?.querySelector('small.text-danger') || null; }
  function show(input, message) { const b = box(input); if (b) { b.textContent = message; b.style.display = 'block'; } input.classList.add('is-invalid'); }
  function clear(input) { const b = box(input); if (b) { b.textContent = ''; b.style.display = 'none'; } input.classList.remove('is-invalid'); }
  function validate(input) { const field = input.getAttribute('data-field'); const value = String(input.value || '').trim(); clear(input); if (!field) return true; if (!value) { show(input, label(field)); return false; } return true; }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>
