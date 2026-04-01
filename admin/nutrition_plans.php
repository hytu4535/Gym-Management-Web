<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý chế độ dinh dưỡng";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_SERVICES_NUTRITION
checkPermission('MANAGE_SERVICES_NUTRITION');

include '../includes/functions.php';

$db = getDB();
$filterKeyword = trim((string) ($_GET['q'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

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

function normalizeBmiRangeValue($raw)
{
  $value = trim((string) $raw);
  if ($value === '') {
    return null;
  }

  if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*-\s*([0-9]+(?:\.[0-9]+)?)$/', $value, $matches)) {
    return false;
  }

  $min = (float) $matches[1];
  $max = (float) $matches[2];

  if ($min <= 0 || $max <= 0 || $min > $max) {
    return false;
  }

  return rtrim(rtrim((string) $min, '0'), '.') . ' - ' . rtrim(rtrim((string) $max, '0'), '.');
}

function bmiRangeForType($type)
{
  $normalizedType = trim(mb_strtolower((string) $type));

  $map = [
    'tăng cân' => '16 - 18.4',
    'tăng cơ' => '16 - 18.4',
    'duy trì' => '18.5 - 22.9',
    'giảm mỡ' => '23 - 24.9',
    'giảm cân' => '25 - 34.9',
  ];

  return $map[$normalizedType] ?? null;
}

// Xử lý thêm chế độ dinh dưỡng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $bmi_range = bmiRangeForType($type);
        if ($bmi_range === null) {
          $bmi_range = normalizeBmiRangeValue($_POST['bmi_range'] ?? '');
        }
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        if (trim((string) $type) === '') {
          setFlashMessage('danger', 'Vui lòng chọn loại chế độ.');
          redirect('nutrition_plans.php');
          exit;
        }

        if ($bmi_range === false) {
          setFlashMessage('danger', 'BMI phù hợp không hợp lệ. Vui lòng nhập theo định dạng: 18.5 - 24.9 và không dùng số âm.');
          redirect('nutrition_plans.php');
          exit;
        }

        try {
      $stmt = $db->prepare("INSERT INTO nutrition_plans (name, type, bmi_range, description, status) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$name, $type, $bmi_range, $description, $status]);
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
        $bmi_range = bmiRangeForType($type);
        if ($bmi_range === null) {
          $bmi_range = normalizeBmiRangeValue($_POST['bmi_range'] ?? '');
        }
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        if (trim((string) $type) === '') {
          setFlashMessage('danger', 'Vui lòng chọn loại chế độ.');
          redirect('nutrition_plans.php');
          exit;
        }

        if ($bmi_range === false) {
          setFlashMessage('danger', 'BMI phù hợp không hợp lệ. Vui lòng nhập theo định dạng: 18.5 - 24.9 và không dùng số âm.');
          redirect('nutrition_plans.php');
          exit;
        }

        try {
      $stmt = $db->prepare("UPDATE nutrition_plans SET name = ?, type = ?, bmi_range = ?, description = ?, status = ? WHERE id = ?");
      $stmt->execute([$name, $type, $bmi_range, $description, $status, $id]);
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
            // Kiểm tra xem chế độ dinh dưỡng này có đang được hội viên sử dụng không
            $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM member_nutrition_plans WHERE nutrition_plan_id = ? AND status = 'đã áp dụng'");
            $check_stmt->execute([$id]);
            $check_result = $check_stmt->fetch();
            
            if ($check_result && $check_result['count'] > 0) {
                setFlashMessage('warning', 'Không thể xóa chế độ dinh dưỡng này vì đang được ' . $check_result['count'] . ' hội viên sử dụng. Hãy cập nhật trạng thái hội viên trước!');
            } else {
                // Xóa nếu không có ai sử dụng
                $stmt = $db->prepare("DELETE FROM nutrition_plans WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Xóa chế độ dinh dưỡng thành công!');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa chế độ dinh dưỡng. ' . $e->getMessage());
        }
        redirect('nutrition_plans.php');
        exit;
    }
}

// Lấy danh sách chế độ dinh dưỡng
$planConditions = [];
$planParams = [];

if ($filterKeyword !== '') {
  $planConditions[] = "(name LIKE ? OR type LIKE ? OR description LIKE ?)";
  $like = '%' . $filterKeyword . '%';
  $planParams[] = $like;
  $planParams[] = $like;
  $planParams[] = $like;
}

$allowedTypes = ['tăng cân', 'giảm cân', 'tư vấn', 'duy trì', 'tăng cơ', 'giảm mỡ', 'khác'];
if ($filterType !== '' && in_array($filterType, $allowedTypes, true)) {
  $planConditions[] = "type = ?";
  $planParams[] = $filterType;
}

$allowedStatuses = ['hoạt động', 'không hoạt động'];
if ($filterStatus !== '' && in_array($filterStatus, $allowedStatuses, true)) {
  $planConditions[] = "status = ?";
  $planParams[] = $filterStatus;
}

$planSql = "SELECT * FROM nutrition_plans";
if (!empty($planConditions)) {
  $planSql .= " WHERE " . implode(' AND ', $planConditions);
}
$planSql .= " ORDER BY id DESC";

$stmt = $db->prepare($planSql);
$stmt->execute($planParams);
$plans = $stmt->fetchAll();

$nutritionItemsCount = (int) $db->query("SELECT COUNT(*) FROM nutrition_items")->fetchColumn();
$nutritionPlanItemsCount = (int) $db->query("SELECT COUNT(*) FROM nutrition_plan_items")->fetchColumn();
$memberNutritionPlansCount = (int) $db->query("SELECT COUNT(*) FROM member_nutrition_plans")->fetchColumn();

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
        <?php renderAdminFlash($flash); ?>

        <?php
          $filterTitle = 'Lọc nhanh chế độ dinh dưỡng';
          $filterAction = 'nutrition_plans.php';
          $filterFormId = 'nutritionPlanFilterForm';
          $filterMode = 'server';
          $filterFieldsHtml = '
            <div class="col-md-5">
              <div class="form-group mb-0">
                <label>Từ khóa</label>
                <input type="text" class="form-control" name="q" placeholder="Tìm theo tên, loại hoặc mô tả..." value="' . htmlspecialchars($filterKeyword) . '">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-0">
                <label>Loại</label>
                <select class="form-control" name="type">
                  <option value="">Tất cả loại</option>
                  <option value="tăng cân"' . ($filterType === 'tăng cân' ? ' selected' : '') . '>Tăng cân</option>
                  <option value="giảm cân"' . ($filterType === 'giảm cân' ? ' selected' : '') . '>Giảm cân</option>
                  <option value="tư vấn"' . ($filterType === 'tư vấn' ? ' selected' : '') . '>Tư vấn</option>
                  <option value="duy trì"' . ($filterType === 'duy trì' ? ' selected' : '') . '>Duy trì</option>
                  <option value="tăng cơ"' . ($filterType === 'tăng cơ' ? ' selected' : '') . '>Tăng cơ</option>
                  <option value="giảm mỡ"' . ($filterType === 'giảm mỡ' ? ' selected' : '') . '>Giảm mỡ</option>
                  <option value="khác"' . ($filterType === 'khác' ? ' selected' : '') . '>Khác</option>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Trạng thái</label>
                <select class="form-control" name="status">
                  <option value="">Tất cả</option>
                  <option value="hoạt động"' . ($filterStatus === 'hoạt động' ? ' selected' : '') . '>Hoạt động</option>
                  <option value="không hoạt động"' . ($filterStatus === 'không hoạt động' ? ' selected' : '') . '>Không hoạt động</option>
                </select>
              </div>
            </div>';
          include 'layout/filter-card.php';
        ?>

        <div class="row mb-3">
          <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="info-box h-100">
              <span class="info-box-icon bg-info"><i class="fas fa-utensils"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Món dinh dưỡng</span>
                <span class="info-box-number"><?= $nutritionItemsCount ?></span>
                <div class="mt-2">
                  <a href="nutrition_items.php" class="btn btn-sm btn-outline-info">Quản lý món</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="info-box h-100">
              <span class="info-box-icon bg-success"><i class="fas fa-list-alt"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Món trong thực đơn</span>
                <span class="info-box-number"><?= $nutritionPlanItemsCount ?></span>
                <div class="mt-2">
                  <a href="nutrition_plan_items.php" class="btn btn-sm btn-outline-success">Gán món vào plan</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="info-box h-100">
              <span class="info-box-icon bg-warning"><i class="fas fa-user-check"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Gán dinh dưỡng hội viên</span>
                <span class="info-box-number"><?= $memberNutritionPlansCount ?></span>
                <div class="mt-2">
                  <a href="member_nutrition_plans.php" class="btn btn-sm btn-outline-warning">Theo dõi hội viên</a>
                </div>
              </div>
            </div>
          </div>
        </div>

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
                    <th>Tổng calo từ món</th>
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
                    <?php $display_cal = $plan['calculated_calories']; ?>
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
                      <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $plan['id'] ?>"
                        data-name="<?= htmlspecialchars($plan['name']) ?>"
                        data-type="<?= $plan['type'] ?>"
                        data-bmi="<?= htmlspecialchars($plan['bmi_range'] ?? '') ?>"
                        data-description="<?= htmlspecialchars($plan['description'] ?? '') ?>"
                        data-status="<?= $plan['status'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="nutrition_plan_items.php?plan_id=<?= $plan['id'] ?>&open=add" class="btn btn-info btn-sm" title="Gán món vào thực đơn">
                        <i class="fas fa-list-ul"></i>
                      </a>
                      <a href="member_nutrition_plans.php?plan_id=<?= $plan['id'] ?>&open=add" class="btn btn-success btn-sm" title="Gán plan cho hội viên">
                        <i class="fas fa-user-plus"></i>
                      </a>
                      <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $plan['id'] ?>"
                        data-name="<?= htmlspecialchars($plan['name']) ?>">
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
          <form method="POST" action="nutrition_plans.php" novalidate id="addNutritionForm">
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm chế độ dinh dưỡng mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên chế độ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Nhập tên chế độ dinh dưỡng" data-field="name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" data-field="type">
                  <option value="" selected>-- Chọn loại --</option>
                  <option value="tăng cân">Tăng cân</option>
                  <option value="giảm cân">Giảm cân</option>
                  <option value="tư vấn">Tư vấn</option>
                  <option value="duy trì">Duy trì</option>
                  <option value="tăng cơ">Tăng cơ</option>
                  <option value="giảm mỡ">Giảm mỡ</option>
                  <option value="khác">Khác</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>BMI phù hợp</label>
                <input type="text" class="form-control" name="bmi_range" id="add-bmi" placeholder="VD: 18.5 - 24.9" pattern="^([0-9]+(\.[0-9]+)?)\s*-\s*([0-9]+(\.[0-9]+)?)$" data-field="bmi_range">
                <small class="text-muted d-block mt-1 js-bmi-suggestion" id="add-bmi-suggestion" style="display:none;"></small>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả chi tiết" data-field="description"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" data-field="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
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

    <!-- Modal Sửa chế độ dinh dưỡng -->
    <div class="modal fade" id="editNutritionModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="nutrition_plans.php" novalidate id="editNutritionForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa chế độ dinh dưỡng</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên chế độ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="edit-name" data-field="name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="edit-type" data-field="type">
                  <option value="">-- Chọn loại --</option>
                  <option value="tăng cân">Tăng cân</option>
                  <option value="giảm cân">Giảm cân</option>
                  <option value="tư vấn">Tư vấn</option>
                  <option value="duy trì">Duy trì</option>
                  <option value="tăng cơ">Tăng cơ</option>
                  <option value="giảm mỡ">Giảm mỡ</option>
                  <option value="khác">Khác</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>BMI phù hợp</label>
                <input type="text" class="form-control" name="bmi_range" id="edit-bmi" pattern="^([0-9]+(\.[0-9]+)?)\s*-\s*([0-9]+(\.[0-9]+)?)$" data-field="bmi_range">
                <small class="text-muted d-block mt-1 js-bmi-suggestion" id="edit-bmi-suggestion" style="display:none;"></small>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" id="edit-description" rows="3" data-field="description"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status" data-field="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
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
  function suggestBmiRange(type) {
    const t = String(type || '').toLowerCase();
    const map = {
      'tăng cân': '16 - 18.4',
      'tăng cơ': '16 - 18.4',
      'duy trì': '18.5 - 22.9',
      'giảm mỡ': '23 - 24.9',
      'giảm cân': '25 - 34.9'
    };

    return map[t] || null;
  }

  function applyBmiSuggestion($form) {
    if (!$form || !$form.length) return;

    const type = $form.find('select[name="type"]').val();
    const $bmiInput = $form.find('input[name="bmi_range"]');
    const $hint = $form.find('.js-bmi-suggestion').first();
    const suggestion = suggestBmiRange(type);
    const isAutoFilled = Boolean($bmiInput.data('autoFilled'));

    if (!type) {
      $bmiInput.prop('readonly', false);
      $bmiInput.removeData('autoFilled');
      $hint.hide().text('');
      return;
    }

    if (type === 'tư vấn') {
      $bmiInput.prop('readonly', false);
      if (isAutoFilled) {
        $bmiInput.val('');
      }
      $bmiInput.removeData('autoFilled');
      $hint.text('Loại tư vấn: nhập BMI thủ công.').show();
      return;
    }

    if (suggestion) {
      $bmiInput.val(suggestion).prop('readonly', true).data('autoFilled', true);
      $hint.text('BMI tự động theo loại chế độ: ' + suggestion).show();
      return;
    }

    $bmiInput.prop('readonly', false);
    if (isAutoFilled) {
      $bmiInput.val('');
    }
    $bmiInput.removeData('autoFilled');
    $hint.text('Không có BMI tự động cho loại này, bạn nhập thủ công.').show();
  }

  function bindBmiSuggestion(formSelector) {
    const $form = $(formSelector);
    if (!$form.length) return;

    $form.on('change', 'select[name="type"]', function() {
      applyBmiSuggestion($form);
    });
  }

  bindBmiSuggestion('#addNutritionForm');
  bindBmiSuggestion('#editNutritionForm');

  // Reset form thêm mới khi mở modal
  $('#addNutritionModal').on('show.bs.modal', function() {
    $('#addNutritionForm')[0].reset();
    $('#add-bmi').prop('readonly', false).val('');
    $('#add-bmi').removeData('autoFilled');
    $('#addNutritionForm').find('select[name="type"]').val('');
    $('#addNutritionForm').find('.js-bmi-suggestion').hide();
    // Focus vào field đầu tiên
    setTimeout(function() {
      $('#addNutritionForm').find('[data-field="name"]').focus();
    }, 100);
  });
  
  // Xử lý click nút sửa
  $(document).on('click', '.btn-edit', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const type = $(this).data('type');
    const bmi = $(this).data('bmi');
    const description = $(this).data('description');
    const status = $(this).data('status');
    
    // Load dữ liệu vào form
    $('#edit-id').val(id);
    $('#edit-name').val(name);
    $('#edit-type').val(type);
    $('#edit-bmi').val(bmi);
    $('#edit-description').val(description);
    $('#edit-status').val(status);

    applyBmiSuggestion($('#editNutritionForm'));
    
    // Mở modal
    $('#editNutritionModal').modal('show');
  });
  
  // Focus vào field name khi mở modal sửa
  $('#editNutritionModal').on('shown.bs.modal', function() {
    setTimeout(function() {
      $('#edit-name').focus();
    }, 100);
  });

  // Xử lý click nút xóa
  $(document).on('click', '.btn-delete', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
    // Mở modal xóa
    $('#deleteNutritionModal').modal('show');
  });
});

(function() {
  function label(field) {
    if (field === 'name') return 'Vui lòng nhập tên chế độ';
    if (field === 'type') return 'Vui lòng chọn loại chế độ';
    if (field === 'bmi_range') return 'Vui lòng nhập BMI phù hợp';
    if (field === 'description') return 'Vui lòng nhập mô tả';
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
    if (field === 'bmi_range') {
      if (!value) return true;
      const match = value.match(/^([0-9]+(?:\.[0-9]+)?)\s*-\s*([0-9]+(?:\.[0-9]+)?)$/);
      if (!match) { show(input, 'BMI phải theo định dạng: 18.5 - 24.9'); return false; }
      const min = Number(match[1]);
      const max = Number(match[2]);
      if (!(min > 0 && max > 0 && min <= max)) {
        show(input, 'BMI không được âm và giá trị đầu phải nhỏ hơn hoặc bằng giá trị cuối.');
        return false;
      }
      return true;
    }
    if (!value) { show(input, label(field)); return false; }
    return true;
  }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ 
    if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; 
    let ok = true;
    let firstInvalidField = null;
    e.target.querySelectorAll('[data-field]').forEach(function(field){ 
      if (!validate(field)) {
        if (!firstInvalidField) firstInvalidField = field;
        ok = false; 
      }
    }); 
    if (!ok) {
      e.preventDefault();
      if (firstInvalidField) {
        setTimeout(function() { firstInvalidField.focus(); }, 100);
      }
    }
  }, true);
})();
</script>