<?php
$page_title = "Quản lý Báo Cáo";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('VIEW_REPORTS');

include 'layout/header.php';
include 'layout/sidebar.php';

require_once '../config/db.php';

$today = date('Y-m-d');
$defaultFromDate = date('Y-m-01');

$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : $defaultFromDate;
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : $today;
$sortDir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'asc' : 'desc';

$errors = [];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $errors[] = 'Từ ngày không hợp lệ.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $errors[] = 'Đến ngày không hợp lệ.';
}
if (empty($errors) && $fromDate > $toDate) {
    $errors[] = 'Khoảng thời gian không hợp lệ: Từ ngày phải nhỏ hơn hoặc bằng Đến ngày.';
}

$stats = [];
if (empty($errors)) {
    // Bước 1: Lấy top 5 khách hàng có tổng mua cao nhất theo khoảng thời gian (loại đơn đã hủy).
    $sqlTopCustomers = "
        SELECT
            o.member_id,
            COALESCE(m.full_name, CONCAT('Khách #', o.member_id)) AS customer_name,
            COUNT(o.id) AS order_count,
            SUM(o.total_amount) AS total_purchase
        FROM orders o
        LEFT JOIN members m ON m.id = o.member_id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
          AND o.status <> 'cancelled'
        GROUP BY o.member_id, m.full_name
        ORDER BY total_purchase DESC
        LIMIT 5
    ";

    $stmtTop = $conn->prepare($sqlTopCustomers);
    $stmtTop->bind_param('ss', $fromDate, $toDate);
    $stmtTop->execute();
    $topResult = $stmtTop->get_result();

    $memberIds = [];
    while ($row = $topResult->fetch_assoc()) {
        $memberId = (int) $row['member_id'];
        $stats[$memberId] = [
            'member_id' => $memberId,
            'customer_name' => $row['customer_name'],
            'order_count' => (int) $row['order_count'],
            'total_purchase' => (float) $row['total_purchase'],
            'orders' => []
        ];
        $memberIds[] = $memberId;
    }
    $stmtTop->close();

    // Bước 2: Lấy chi tiết đơn hàng của nhóm top 5 để hiển thị link xem chi tiết từng đơn.
    if (!empty($memberIds)) {
        $idPlaceholders = implode(',', array_fill(0, count($memberIds), '?'));
        $idTypes = str_repeat('i', count($memberIds));

        $sqlOrders = "
            SELECT
                o.id,
                o.member_id,
                o.order_date,
                o.total_amount,
                o.status
            FROM orders o
            WHERE DATE(o.order_date) BETWEEN ? AND ?
              AND o.status <> 'cancelled'
              AND o.member_id IN ($idPlaceholders)
            ORDER BY o.order_date DESC, o.id DESC
        ";

        $stmtOrders = $conn->prepare($sqlOrders);
        $bindTypes = 'ss' . $idTypes;
        $bindValues = array_merge([$fromDate, $toDate], $memberIds);

        $refs = [];
        foreach ($bindValues as $k => $v) {
            $refs[$k] = &$bindValues[$k];
        }
        array_unshift($refs, $bindTypes);
        call_user_func_array([$stmtOrders, 'bind_param'], $refs);

        $stmtOrders->execute();
        $orderResult = $stmtOrders->get_result();

        while ($order = $orderResult->fetch_assoc()) {
            $mid = (int) $order['member_id'];
            if (isset($stats[$mid])) {
                $stats[$mid]['orders'][] = [
                    'id' => (int) $order['id'],
                    'order_date' => $order['order_date'],
                    'total_amount' => (float) $order['total_amount'],
                    'status' => $order['status']
                ];
            }
        }
        $stmtOrders->close();
    }

    // Bước 3: Cho phép hiển thị tăng/giảm theo tổng tiền mua.
    usort($stats, function ($a, $b) use ($sortDir) {
        if ($a['total_purchase'] == $b['total_purchase']) {
            return 0;
        }

        if ($sortDir === 'asc') {
            return ($a['total_purchase'] < $b['total_purchase']) ? -1 : 1;
        }

        return ($a['total_purchase'] > $b['total_purchase']) ? -1 : 1;
    });
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Báo cáo thống kê mua hàng</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Báo cáo</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Điều kiện thống kê</h3>
            </div>
            <div class="card-body">
              <form method="GET" action="reports.php">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="from_date">Từ ngày</label>
                      <input type="date" id="from_date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="to_date">Đến ngày</label>
                      <input type="date" id="to_date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="sort_dir">Sắp xếp theo tổng mua</label>
                      <select id="sort_dir" name="sort_dir" class="form-control">
                        <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>Giảm dần</option>
                        <option value="asc" <?= $sortDir === 'asc' ? 'selected' : '' ?>>Tăng dần</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group w-100 d-flex gap-2">
                      <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-chart-bar"></i> Xem thống kê
                      </button>
                      <a href="reports.php?from_date=1900-01-01&to_date=2099-12-31" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Xóa bộ lọc
                      </a>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-danger">
              <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                Top 5 khách hàng mua nhiều nhất
                (<?= date('d/m/Y', strtotime($fromDate)) ?> - <?= date('d/m/Y', strtotime($toDate)) ?>)
              </h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th style="width: 60px;">#</th>
                      <th>Khách hàng</th>
                      <th style="width: 140px;">Số đơn</th>
                      <th>Danh sách đơn hàng</th>
                      <th style="width: 180px;">Tổng mua</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($errors) && !empty($stats)): ?>
                      <?php $index = 1; ?>
                      <?php foreach ($stats as $row): ?>
                        <tr>
                          <td><?= $index++ ?></td>
                          <td>
                            <strong><?= htmlspecialchars($row['customer_name']) ?></strong>
                            <div class="text-muted">ID hội viên: <?= (int) $row['member_id'] ?></div>
                          </td>
                          <td><?= (int) $row['order_count'] ?></td>
                          <td>
                            <?php if (!empty($row['orders'])): ?>
                              <ul class="mb-0 pl-3">
                                <?php foreach ($row['orders'] as $order): ?>
                                  <li>
                                    <a href="order-items.php?id=<?= (int) $order['id'] ?>" target="_blank">
                                      #ORD<?= str_pad((string) $order['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </a>
                                    - <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                                    - <?= number_format($order['total_amount'], 0, ',', '.') ?>đ
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            <?php else: ?>
                              <span class="text-muted">Không có đơn hàng</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-danger font-weight-bold"><?= number_format($row['total_purchase'], 0, ',', '.') ?>đ</td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-center text-muted">Không có dữ liệu phù hợp trong khoảng thời gian đã chọn.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'layout/footer.php'; ?>
