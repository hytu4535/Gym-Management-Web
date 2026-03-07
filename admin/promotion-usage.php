<?php
require_once '../includes/functions.php';

$db = getDB();

$usageStmt = $db->query("SELECT pu.id, pu.member_id, pu.promotion_id, pu.order_id, pu.applied_amount, pu.applied_at, m.full_name AS member_name, mt.name AS tier_name, tp.name AS promotion_name FROM promotion_usage pu INNER JOIN members m ON m.id = pu.member_id LEFT JOIN member_tiers mt ON mt.id = m.tier_id INNER JOIN tier_promotions tp ON tp.id = pu.promotion_id ORDER BY pu.applied_at DESC, pu.id DESC");
$promotionUsages = $usageStmt->fetchAll();

$page_title = "Lịch Sử Sử Dụng Khuyến Mãi";
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
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Sử Dụng Khuyến Mãi</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
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
                          <button type="button" class="btn btn-info btn-sm" title="ID hội viên: <?= $usage['member_id'] ?> | ID khuyến mãi: <?= $usage['promotion_id'] ?>">
                            <i class="fas fa-eye"></i>
                          </button>
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
