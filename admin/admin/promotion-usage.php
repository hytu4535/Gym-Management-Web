<?php
session_start(); // luôn khởi tạo session

$page_title = "Lịch Sử Sử Dụng Khuyến Mãi";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_SALES');

// layout chung
include 'layout/header.php'; 
include 'layout/sidebar.php';

require_once '../config/db.php';
require_once '../includes/functions.php';

$db = getDB();

$filterMemberId = trim((string) ($_GET['member_id'] ?? ''));
$filterTierId = trim((string) ($_GET['tier_id'] ?? ''));
$filterPromotionId = trim((string) ($_GET['promotion_id'] ?? ''));
$filterAmountMin = trim((string) ($_GET['amount_min'] ?? ''));
$filterAmountMax = trim((string) ($_GET['amount_max'] ?? ''));
$filterFromDate = trim((string) ($_GET['from_date'] ?? ''));
$filterToDate = trim((string) ($_GET['to_date'] ?? ''));

$promotionUsageWhereClauses = [];
$promotionUsageParams = [];
if ($filterMemberId !== '') { $promotionUsageWhereClauses[] = 'pu.member_id = ?'; $promotionUsageParams[] = (int) $filterMemberId; }
if ($filterTierId !== '') { $promotionUsageWhereClauses[] = 'm.tier_id = ?'; $promotionUsageParams[] = (int) $filterTierId; }
if ($filterPromotionId !== '') { $promotionUsageWhereClauses[] = 'pu.promotion_id = ?'; $promotionUsageParams[] = (int) $filterPromotionId; }
if ($filterAmountMin !== '' && is_numeric($filterAmountMin)) { $promotionUsageWhereClauses[] = 'pu.applied_amount >= ?'; $promotionUsageParams[] = (float) $filterAmountMin; }
if ($filterAmountMax !== '' && is_numeric($filterAmountMax)) { $promotionUsageWhereClauses[] = 'pu.applied_amount <= ?'; $promotionUsageParams[] = (float) $filterAmountMax; }
if ($filterFromDate !== '') { $promotionUsageWhereClauses[] = 'DATE(pu.applied_at) >= ?'; $promotionUsageParams[] = $filterFromDate; }
if ($filterToDate !== '') { $promotionUsageWhereClauses[] = 'DATE(pu.applied_at) <= ?'; $promotionUsageParams[] = $filterToDate; }
$promotionUsageWhereSql = !empty($promotionUsageWhereClauses) ? ' WHERE ' . implode(' AND ', $promotionUsageWhereClauses) : '';

$usageSql = "SELECT pu.id, pu.member_id, pu.promotion_id, pu.order_id, pu.applied_amount, pu.applied_at, m.full_name AS member_name, mt.name AS tier_name, COALESCE(tp.name, '(Khuyến mãi đã xóa)') AS promotion_name FROM promotion_usage pu INNER JOIN members m ON m.id = pu.member_id LEFT JOIN member_tiers mt ON mt.id = m.tier_id LEFT JOIN tier_promotions tp ON tp.id = pu.promotion_id" . $promotionUsageWhereSql . " ORDER BY pu.applied_at DESC, pu.id DESC";
$usageStmt = $db->prepare($usageSql);
$usageStmt->execute($promotionUsageParams);
$promotionUsages = $usageStmt->fetchAll();

$members = $db->query("SELECT id, full_name FROM members ORDER BY full_name ASC")->fetchAll();
$tiers = $db->query("SELECT id, name FROM member_tiers ORDER BY level ASC")->fetchAll();
$promotions = $db->query("SELECT id, name FROM tier_promotions ORDER BY id DESC")->fetchAll();

?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Lịch Sử Sử Dụng Khuyến Mãi</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Lịch Sử KM</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php
          $filterMode = 'server';
          $filterAction = 'promotion-usage.php';
          $filterFieldsHtml = '
            <div class="col-md-3"><div class="form-group mb-0"><label>Hội viên</label><select name="member_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($members as $member) {
            $selected = (string) $filterMemberId === (string) $member['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $member['id'] . '" ' . $selected . '>' . htmlspecialchars($member['full_name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Hạng</label><select name="tier_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($tiers as $tier) {
            $selected = (string) $filterTierId === (string) $tier['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $tier['id'] . '" ' . $selected . '>' . htmlspecialchars($tier['name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Khuyến mãi</label><select name="promotion_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($promotions as $promotion) {
            $selected = (string) $filterPromotionId === (string) $promotion['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $promotion['id'] . '" ' . $selected . '>' . htmlspecialchars($promotion['name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Số tiền từ</label><input type="number" name="amount_min" class="form-control" min="0" value="' . htmlspecialchars($filterAmountMin) . '" placeholder=">="></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Số tiền đến</label><input type="number" name="amount_max" class="form-control" min="0" value="' . htmlspecialchars($filterAmountMax) . '" placeholder="<="></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Từ ngày</label><input type="date" name="from_date" class="form-control" value="' . htmlspecialchars($filterFromDate) . '"></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Đến ngày</label><input type="date" name="to_date" class="form-control" value="' . htmlspecialchars($filterToDate) . '"></div></div>
          ';
          include 'layout/filter-card.php';
        ?>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Sử Dụng Khuyến Mãi</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table" id="promotionUsageTable">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Hạng</th>
                    <th>Chương Trình KM</th>
                    <th>Đơn Hàng</th>
                    <th>Số Tiền Giảm</th>
                    <th>Ngày Áp Dụng</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php if (empty($promotionUsages)): ?>
                    <tr>
                      <td class="text-center">-</td>
                      <td class="text-center">-</td>
                      <td class="text-center">-</td>
                      <td class="text-center">Chưa có lịch sử sử dụng khuyến mãi.</td>
                      <td class="text-center">-</td>
                      <td class="text-center">-</td>
                      <td class="text-center">-</td>
                      <td class="text-center">-</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($promotionUsages as $usage): ?>
                      <?php
                        $tierBadgeClass = 'badge-light';
                        $tierName = $usage['tier_name'] ?: 'N/A';
                        if (stripos($tierName, 'Vàng') !== false || stripos($tierName, 'Kim') !== false) {
                          $tierBadgeClass = 'badge-warning';
                        } elseif (stripos($tierName, 'Bạc') !== false) {
                          $tierBadgeClass = 'badge-secondary';
                        }

                        $orderDisplay = $usage['order_id'] ? ('#DH' . str_pad((string) $usage['order_id'], 3, '0', STR_PAD_LEFT)) : 'N/A';
                      ?>
                      <tr>
                        <td><?= $usage['id'] ?></td>
                        <td><?= htmlspecialchars($usage['member_name']) ?></td>
                        <td><span class="badge <?= $tierBadgeClass ?>"><?= htmlspecialchars($tierName) ?></span></td>
                        <td><?= htmlspecialchars($usage['promotion_name']) ?></td>
                        <td><?= $orderDisplay ?></td>
                        <td><?= number_format((float) $usage['applied_amount'], 0, ',', '.') ?> VNĐ</td>
                        <td><?= date('d/m/Y H:i', strtotime($usage['applied_at'])) ?></td>
                        <td>
                          <?php if (!empty($usage['order_id'])): ?>
                            <a href="order-items.php?id=<?= (int) $usage['order_id'] ?>" class="btn btn-info btn-sm" title="Xem chi tiết đơn hàng #<?= (int) $usage['order_id'] ?>">
                              <i class="fas fa-eye"></i>
                            </a>
                          <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm" title="Bản ghi này không gắn với đơn hàng" disabled>
                              <i class="fas fa-eye-slash"></i>
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php include 'layout/footer.php'; ?>
