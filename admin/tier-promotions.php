<?php
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promotion'])) {
  $name = sanitize($_POST['name']);
  $tierId = intval($_POST['tier_id']);
  $discountType = sanitize($_POST['discount_type']);
  $discountValue = floatval($_POST['discount_value']);
  $applicableItemsInput = trim($_POST['applicable_items'] ?? '');
  $startDate = sanitize($_POST['start_date']);
  $endDate = sanitize($_POST['end_date']);
  $usageLimit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? intval($_POST['usage_limit']) : null;
  $status = sanitize($_POST['status']);

  $allowedTypes = ['percentage', 'fixed', 'package'];
  $allowedStatus = ['active', 'inactive', 'expired'];

  if (!in_array($discountType, $allowedTypes, true) || !in_array($status, $allowedStatus, true)) {
    echo "<script>alert('Loại giảm hoặc trạng thái không hợp lệ!');window.location='tier-promotions.php';</script>";
    exit;
  }

  if ($discountValue <= 0) {
    echo "<script>alert('Giá trị giảm phải lớn hơn 0!');window.location='tier-promotions.php';</script>";
    exit;
  }

  if (strtotime($startDate) > strtotime($endDate)) {
    echo "<script>alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');window.location='tier-promotions.php';</script>";
    exit;
  }

  $tierCheckStmt = $db->prepare("SELECT COUNT(*) FROM member_tiers WHERE id = ?");
  $tierCheckStmt->execute([$tierId]);
  if ((int) $tierCheckStmt->fetchColumn() === 0) {
    echo "<script>alert('Hạng áp dụng không tồn tại!');window.location='tier-promotions.php';</script>";
    exit;
  }

  $items = [];
  if ($applicableItemsInput !== '') {
    $items = array_values(array_filter(array_map('trim', explode(',', $applicableItemsInput))));
  }
  $applicableItems = !empty($items) ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;

  $insertStmt = $db->prepare("INSERT INTO tier_promotions (name, tier_id, discount_type, discount_value, applicable_items, start_date, end_date, usage_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  if ($insertStmt->execute([$name, $tierId, $discountType, $discountValue, $applicableItems, $startDate, $endDate, $usageLimit, $status])) {
    echo "<script>alert('Thêm khuyến mãi theo hạng thành công!');window.location='tier-promotions.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi thêm khuyến mãi!');window.location='tier-promotions.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_promotion_id'])) {
  $promotionId = intval($_POST['edit_promotion_id']);
  $name = sanitize($_POST['edit_name']);
  $tierId = intval($_POST['edit_tier_id']);
  $discountType = sanitize($_POST['edit_discount_type']);
  $discountValue = floatval($_POST['edit_discount_value']);
  $applicableItemsInput = trim($_POST['edit_applicable_items'] ?? '');
  $startDate = sanitize($_POST['edit_start_date']);
  $endDate = sanitize($_POST['edit_end_date']);
  $usageLimit = isset($_POST['edit_usage_limit']) && $_POST['edit_usage_limit'] !== '' ? intval($_POST['edit_usage_limit']) : null;
  $status = sanitize($_POST['edit_status']);

  $allowedTypes = ['percentage', 'fixed', 'package'];
  $allowedStatus = ['active', 'inactive', 'expired'];

  if (!in_array($discountType, $allowedTypes, true) || !in_array($status, $allowedStatus, true)) {
    echo "<script>alert('Loại giảm hoặc trạng thái không hợp lệ!');window.location='tier-promotions.php';</script>";
    exit;
  }

  if ($discountValue <= 0) {
    echo "<script>alert('Giá trị giảm phải lớn hơn 0!');window.location='tier-promotions.php';</script>";
    exit;
  }

  if (strtotime($startDate) > strtotime($endDate)) {
    echo "<script>alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');window.location='tier-promotions.php';</script>";
    exit;
  }

  $items = [];
  if ($applicableItemsInput !== '') {
    $items = array_values(array_filter(array_map('trim', explode(',', $applicableItemsInput))));
  }
  $applicableItems = !empty($items) ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;

  $updateStmt = $db->prepare("UPDATE tier_promotions SET name = ?, tier_id = ?, discount_type = ?, discount_value = ?, applicable_items = ?, start_date = ?, end_date = ?, usage_limit = ?, status = ? WHERE id = ?");
  if ($updateStmt->execute([$name, $tierId, $discountType, $discountValue, $applicableItems, $startDate, $endDate, $usageLimit, $status, $promotionId])) {
    echo "<script>alert('Cập nhật khuyến mãi thành công!');window.location='tier-promotions.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật khuyến mãi!');window.location='tier-promotions.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_promotion_id'])) {
  $promotionId = intval($_POST['delete_promotion_id']);
  $deleteStmt = $db->prepare("DELETE FROM tier_promotions WHERE id = ?");

  if ($deleteStmt->execute([$promotionId])) {
    echo "<script>alert('Xóa khuyến mãi thành công!');window.location='tier-promotions.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi xóa khuyến mãi!');window.location='tier-promotions.php';</script>";
  }
  exit;
}

$tiersStmt = $db->query("SELECT id, name FROM member_tiers WHERE status = 'active' ORDER BY level ASC");
$tiers = $tiersStmt->fetchAll();

$promotionsStmt = $db->query("SELECT tp.*, mt.name AS tier_name FROM tier_promotions tp INNER JOIN member_tiers mt ON mt.id = tp.tier_id ORDER BY tp.id DESC");
$promotions = $promotionsStmt->fetchAll();

$page_title = "Quản lý Khuyến Mãi Theo Hạng";
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
            <h1 class="m-0">Quản lý Khuyến Mãi Theo Hạng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Khuyến Mãi</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Chương Trình Khuyến Mãi</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPromotionModal">
                    <i class="fas fa-plus"></i> Thêm Khuyến Mãi
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Chương Trình</th>
                    <th>Hạng Áp Dụng</th>
                    <th>Loại Giảm</th>
                    <th>Giá Trị</th>
                    <th>Thời Gian</th>
                    <th>Giới Hạn</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($promotions as $promotion): ?>
                  <?php
                    $discountTypeLabel = 'Phần trăm';
                    if ($promotion['discount_type'] === 'fixed') {
                      $discountTypeLabel = 'Cố định';
                    } elseif ($promotion['discount_type'] === 'package') {
                      $discountTypeLabel = 'Gói dịch vụ';
                    }

                    $discountValueLabel = number_format((float) $promotion['discount_value'], 0, ',', '.');
                    if ($promotion['discount_type'] === 'percentage') {
                      $discountValueLabel .= '%';
                    }
                  ?>
                  <tr>
                    <td><?= $promotion['id'] ?></td>
                    <td><?= htmlspecialchars($promotion['name']) ?></td>
                    <td><span class="badge badge-light"><?= htmlspecialchars($promotion['tier_name']) ?></span></td>
                    <td><?= $discountTypeLabel ?></td>
                    <td><?= $discountValueLabel ?></td>
                    <td><?= date('d/m/Y', strtotime($promotion['start_date'])) ?> - <?= date('d/m/Y', strtotime($promotion['end_date'])) ?></td>
                    <td><?= $promotion['usage_limit'] !== null ? ((int) $promotion['usage_limit'] . ' lượt') : 'Không giới hạn' ?></td>
                    <td>
                      <?php if ($promotion['status'] === 'active'): ?>
                        <span class="badge badge-success">Active</span>
                      <?php elseif ($promotion['status'] === 'inactive'): ?>
                        <span class="badge badge-warning">Inactive</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Expired</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm edit-promotion-btn"
                        data-id="<?= $promotion['id'] ?>"
                        data-name="<?= htmlspecialchars($promotion['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-tier-id="<?= $promotion['tier_id'] ?>"
                        data-discount-type="<?= $promotion['discount_type'] ?>"
                        data-discount-value="<?= $promotion['discount_value'] ?>"
                        data-applicable-items="<?= htmlspecialchars((string) $promotion['applicable_items'], ENT_QUOTES, 'UTF-8') ?>"
                        data-start-date="<?= $promotion['start_date'] ?>"
                        data-end-date="<?= $promotion['end_date'] ?>"
                        data-usage-limit="<?= $promotion['usage_limit'] ?>"
                        data-status="<?= $promotion['status'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <form method="POST" action="tier-promotions.php" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc muốn xóa khuyến mãi này?');">
                        <input type="hidden" name="delete_promotion_id" value="<?= $promotion['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </form>
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

<div class="modal fade" id="addPromotionModal" tabindex="-1" role="dialog" aria-labelledby="addPromotionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="tier-promotions.php">
        <input type="hidden" name="add_promotion" value="1">
        <div class="modal-header">
          <h5 class="modal-title" id="addPromotionModalLabel">Thêm Khuyến Mãi Theo Hạng</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="name">Tên Chương Trình</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label for="tier_id">Hạng Áp Dụng</label>
            <select class="form-control" id="tier_id" name="tier_id" required>
              <option value="">-- Chọn hạng --</option>
              <?php foreach ($tiers as $tier): ?>
                <option value="<?= $tier['id'] ?>"><?= htmlspecialchars($tier['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="discount_type">Loại Giảm</label>
            <select class="form-control" id="discount_type" name="discount_type" required>
              <option value="percentage">Phần trăm</option>
              <option value="fixed">Cố định</option>
              <option value="package">Gói dịch vụ</option>
            </select>
          </div>
          <div class="form-group">
            <label for="discount_value">Giá Trị</label>
            <input type="number" class="form-control" id="discount_value" name="discount_value" min="0.01" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="applicable_items">Danh mục áp dụng (cách nhau bởi dấu phẩy)</label>
            <input type="text" class="form-control" id="applicable_items" name="applicable_items" placeholder="personal_training, gym_session">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="start_date">Ngày bắt đầu</label>
              <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="form-group col-md-6">
              <label for="end_date">Ngày kết thúc</label>
              <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
          </div>
          <div class="form-group">
            <label for="usage_limit">Giới hạn lượt dùng</label>
            <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1" placeholder="Để trống = không giới hạn">
          </div>
          <div class="form-group mb-0">
            <label for="status">Trạng thái</label>
            <select class="form-control" id="status" name="status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="expired">Expired</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Thêm khuyến mãi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editPromotionModal" tabindex="-1" role="dialog" aria-labelledby="editPromotionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="tier-promotions.php">
        <input type="hidden" name="edit_promotion_id" id="edit_promotion_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editPromotionModalLabel">Sửa Khuyến Mãi Theo Hạng</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_name">Tên Chương Trình</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
          </div>
          <div class="form-group">
            <label for="edit_tier_id">Hạng Áp Dụng</label>
            <select class="form-control" id="edit_tier_id" name="edit_tier_id" required>
              <?php foreach ($tiers as $tier): ?>
                <option value="<?= $tier['id'] ?>"><?= htmlspecialchars($tier['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_discount_type">Loại Giảm</label>
            <select class="form-control" id="edit_discount_type" name="edit_discount_type" required>
              <option value="percentage">Phần trăm</option>
              <option value="fixed">Cố định</option>
              <option value="package">Gói dịch vụ</option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_discount_value">Giá Trị</label>
            <input type="number" class="form-control" id="edit_discount_value" name="edit_discount_value" min="0.01" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="edit_applicable_items">Danh mục áp dụng (cách nhau bởi dấu phẩy)</label>
            <input type="text" class="form-control" id="edit_applicable_items" name="edit_applicable_items">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="edit_start_date">Ngày bắt đầu</label>
              <input type="date" class="form-control" id="edit_start_date" name="edit_start_date" required>
            </div>
            <div class="form-group col-md-6">
              <label for="edit_end_date">Ngày kết thúc</label>
              <input type="date" class="form-control" id="edit_end_date" name="edit_end_date" required>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_usage_limit">Giới hạn lượt dùng</label>
            <input type="number" class="form-control" id="edit_usage_limit" name="edit_usage_limit" min="1" placeholder="Để trống = không giới hạn">
          </div>
          <div class="form-group mb-0">
            <label for="edit_status">Trạng thái</label>
            <select class="form-control" id="edit_status" name="edit_status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="expired">Expired</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
  $('.edit-promotion-btn').on('click', function() {
    $('#edit_promotion_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_tier_id').val($(this).data('tier-id'));
    $('#edit_discount_type').val($(this).data('discount-type'));
    $('#edit_discount_value').val($(this).data('discount-value'));

    var rawItems = $(this).data('applicable-items');
    var textItems = '';
    if (rawItems) {
      try {
        var parsed = JSON.parse(rawItems);
        if (Array.isArray(parsed)) {
          textItems = parsed.join(', ');
        } else {
          textItems = rawItems;
        }
      } catch (e) {
        textItems = rawItems;
      }
    }
    $('#edit_applicable_items').val(textItems);

    $('#edit_start_date').val($(this).data('start-date'));
    $('#edit_end_date').val($(this).data('end-date'));
    $('#edit_usage_limit').val($(this).data('usage-limit'));
    $('#edit_status').val($(this).data('status'));
    $('#editPromotionModal').modal('show');
  });
});
</script>
