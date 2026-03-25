<?php
session_start();

$page_title = "Dashboard";

include '../includes/auth.php';
include '../includes/database.php';
include 'layout/header.php';
include 'layout/sidebar.php';

$db = getDB();

function formatCurrencyVND($amount)
{
		return number_format((float) $amount, 0, ',', '.') . ' đ';
}

function calcGrowthPercent($current, $previous)
{
		$current = (float) $current;
		$previous = (float) $previous;

		if ($previous <= 0) {
				return $current > 0 ? 100 : 0;
		}

		return (($current - $previous) / $previous) * 100;
}

try {
		$today = date('Y-m-d');
		$monthStart = date('Y-m-01');
		$nextMonthStart = date('Y-m-01', strtotime('+1 month'));
		$lastMonthStart = date('Y-m-01', strtotime('-1 month'));

		$overview = $db->query(
				"SELECT
						(SELECT COUNT(*) FROM members) AS total_members,
						(SELECT COUNT(*) FROM members WHERE status = 'active') AS active_members,
						(SELECT COUNT(*) FROM member_packages WHERE status = 'active' AND end_date >= CURDATE()) AS active_member_packages,
						(SELECT COUNT(*) FROM orders) AS total_orders,
						(SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending_orders,
						(SELECT COUNT(*) FROM products WHERE status = 'active' AND stock_quantity <= 10) AS low_stock_products,
						(SELECT COUNT(*) FROM equipment WHERE status = 'bao tri') AS maintenance_equipment,
						(SELECT COUNT(*) FROM notifications WHERE is_read = 0) AS unread_notifications"
		)->fetch();

		$revenueStmt = $db->prepare(
				"SELECT
						SUM(CASE WHEN DATE(order_date) = :today AND status IN ('confirmed', 'delivered') THEN total_amount ELSE 0 END) AS revenue_today,
				SUM(CASE WHEN order_date >= :month_start_current AND order_date < :next_month_start AND status IN ('confirmed', 'delivered') THEN total_amount ELSE 0 END) AS revenue_this_month,
				SUM(CASE WHEN order_date >= :last_month_start AND order_date < :month_start_prev_end AND status IN ('confirmed', 'delivered') THEN total_amount ELSE 0 END) AS revenue_last_month
				 FROM orders"
		);
		$revenueStmt->execute([
				':today' => $today,
			':month_start_current' => $monthStart,
				':next_month_start' => $nextMonthStart,
				':last_month_start' => $lastMonthStart,
			':month_start_prev_end' => $monthStart,
		]);
		$revenue = $revenueStmt->fetch();

		$monthlyRevenueStmt = $db->query(
				"SELECT
						DATE_FORMAT(order_date, '%Y-%m') AS month_key,
						DATE_FORMAT(order_date, '%m/%Y') AS month_label,
						SUM(total_amount) AS revenue
				 FROM orders
				 WHERE status IN ('confirmed', 'delivered')
					 AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
				 GROUP BY DATE_FORMAT(order_date, '%Y-%m'), DATE_FORMAT(order_date, '%m/%Y')
				 ORDER BY month_key DESC"
		);
		$monthlyRevenue = $monthlyRevenueStmt->fetchAll();

		$orderStatusStmt = $db->query(
				"SELECT status, COUNT(*) AS total_orders, SUM(total_amount) AS total_amount
				 FROM orders
				 GROUP BY status
				 ORDER BY total_orders DESC"
		);
		$orderStatusStats = $orderStatusStmt->fetchAll();

		$topProductsStmt = $db->query(
				"SELECT
						oi.item_name,
						SUM(oi.quantity) AS total_qty,
						SUM(oi.subtotal) AS total_sales
				 FROM order_items oi
				 INNER JOIN orders o ON o.id = oi.order_id
				 WHERE oi.item_type = 'product'
					 AND o.status <> 'cancelled'
					 AND DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
				 GROUP BY oi.item_name
				 ORDER BY total_qty DESC, total_sales DESC
				 LIMIT 5"
		);
		$topProducts = $topProductsStmt->fetchAll();

		$upcomingSchedulesStmt = $db->query(
				"SELECT
						ts.id,
						ts.training_date,
						m.full_name AS member_name,
						COALESCE(t.full_name, 'Tự tập') AS trainer_name,
						ts.note
				 FROM training_schedules ts
				 INNER JOIN members m ON m.id = ts.member_id
				 LEFT JOIN trainers t ON t.id = ts.trainer_id
				 WHERE ts.training_date >= NOW()
				 ORDER BY ts.training_date ASC
				 LIMIT 8"
		);
		$upcomingSchedules = $upcomingSchedulesStmt->fetchAll();

		$recentOrdersStmt = $db->query(
				"SELECT
						o.id,
						o.order_date,
						o.total_amount,
						o.status,
						o.payment_method,
						m.full_name AS member_name
				 FROM orders o
				 LEFT JOIN members m ON m.id = o.member_id
				 ORDER BY o.order_date DESC
				 LIMIT 10"
		);
		$recentOrders = $recentOrdersStmt->fetchAll();

		$revenueToday = (float) ($revenue['revenue_today'] ?? 0);
		$revenueThisMonth = (float) ($revenue['revenue_this_month'] ?? 0);
		$revenueLastMonth = (float) ($revenue['revenue_last_month'] ?? 0);
		$monthlyGrowth = calcGrowthPercent($revenueThisMonth, $revenueLastMonth);

		$statusLabelMap = [
				'pending' => 'Chờ xử lý',
				'confirmed' => 'Đã xác nhận',
				'delivered' => 'Đã giao',
				'cancelled' => 'Đã hủy',
		];

		$statusBadgeMap = [
				'pending' => 'warning',
				'confirmed' => 'info',
				'delivered' => 'success',
				'cancelled' => 'danger',
		];

		$paymentLabelMap = [
				'cash' => 'Tiền mặt',
				'online' => 'Online',
				'bank_transfer' => 'Chuyển khoản',
		];
} catch (PDOException $e) {
		$overview = [
				'total_members' => 0,
				'active_members' => 0,
				'active_member_packages' => 0,
				'total_orders' => 0,
				'pending_orders' => 0,
				'low_stock_products' => 0,
				'maintenance_equipment' => 0,
				'unread_notifications' => 0,
		];

		$revenueToday = 0;
		$revenueThisMonth = 0;
		$revenueLastMonth = 0;
		$monthlyGrowth = 0;
		$orderStatusStats = [];
		$topProducts = [];
		$upcomingSchedules = [];
		$recentOrders = [];
		$monthlyRevenue = [];
		$statusLabelMap = [];
		$statusBadgeMap = [];
		$paymentLabelMap = [];
		$dashboardError = $e->getMessage();
}
?>

<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Dashboard quản trị</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="index.php">Home</a></li>
						<li class="breadcrumb-item active">Dashboard</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<section class="content">
		<div class="container-fluid">
			<?php if (isset($dashboardError)): ?>
				<div class="alert alert-danger">
					Không thể tải dữ liệu dashboard: <?= htmlspecialchars($dashboardError) ?>
				</div>
			<?php endif; ?>

			<div class="row">
				<div class="col-lg-3 col-6">
					<div class="small-box bg-info">
						<div class="inner">
							<h3><?= (int) $overview['active_members'] ?></h3>
							<p>Hội viên đang hoạt động</p>
						</div>
						<div class="icon"><i class="fas fa-users"></i></div>
						<a href="members.php" class="small-box-footer">Xem hội viên <i class="fas fa-arrow-circle-right"></i></a>
					</div>
				</div>

				<div class="col-lg-3 col-6">
					<div class="small-box bg-success">
						<div class="inner">
							<h3><?= (int) $overview['active_member_packages'] ?></h3>
							<p>Gói tập còn hiệu lực</p>
						</div>
						<div class="icon"><i class="fas fa-dumbbell"></i></div>
						<a href="member-packages.php" class="small-box-footer">Xem gói tập <i class="fas fa-arrow-circle-right"></i></a>
					</div>
				</div>

				<div class="col-lg-3 col-6">
					<div class="small-box bg-warning">
						<div class="inner">
							<h3><?= (int) $overview['pending_orders'] ?></h3>
							<p>Đơn hàng chờ xử lý</p>
						</div>
						<div class="icon"><i class="fas fa-shopping-cart"></i></div>
						<a href="orders.php?status=pending" class="small-box-footer">Xử lý đơn hàng <i class="fas fa-arrow-circle-right"></i></a>
					</div>
				</div>

				<div class="col-lg-3 col-6">
					<div class="small-box bg-danger">
						<div class="inner">
							<h3><?= (int) $overview['maintenance_equipment'] ?></h3>
							<p>Thiết bị cần bảo trì</p>
						</div>
						<div class="icon"><i class="fas fa-tools"></i></div>
						<a href="equipment-maintenance.php" class="small-box-footer">Xem bảo trì <i class="fas fa-arrow-circle-right"></i></a>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-4 col-md-6">
					<div class="info-box">
						<span class="info-box-icon bg-primary"><i class="fas fa-sack-dollar"></i></span>
						<div class="info-box-content">
							<span class="info-box-text">Doanh thu hôm nay</span>
							<span class="info-box-number"><?= formatCurrencyVND($revenueToday) ?></span>
						</div>
					</div>
				</div>

				<div class="col-lg-4 col-md-6">
					<div class="info-box">
						<span class="info-box-icon bg-olive"><i class="fas fa-chart-line"></i></span>
						<div class="info-box-content">
							<span class="info-box-text">Doanh thu tháng này</span>
							<span class="info-box-number"><?= formatCurrencyVND($revenueThisMonth) ?></span>
						</div>
					</div>
				</div>

				<div class="col-lg-4 col-md-12">
					<div class="info-box">
						<span class="info-box-icon bg-secondary"><i class="fas fa-signal"></i></span>
						<div class="info-box-content">
							<span class="info-box-text">Tăng trưởng so với tháng trước</span>
							<span class="info-box-number <?= $monthlyGrowth >= 0 ? 'text-success' : 'text-danger' ?>">
								<?= ($monthlyGrowth >= 0 ? '+' : '') . number_format($monthlyGrowth, 1) ?>%
							</span>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<section class="col-lg-8">
					<div class="card card-outline card-primary">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Thống kê trạng thái đơn hàng</h3>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-striped table-valign-middle mb-0">
									<thead>
										<tr>
											<th>Trạng thái</th>
											<th class="text-center">Số đơn</th>
											<th class="text-right">Tổng giá trị</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!empty($orderStatusStats)): ?>
											<?php foreach ($orderStatusStats as $row): ?>
												<?php $code = $row['status']; ?>
												<tr>
													<td>
														<span class="badge badge-<?= $statusBadgeMap[$code] ?? 'secondary' ?>">
															<?= htmlspecialchars($statusLabelMap[$code] ?? $code) ?>
														</span>
													</td>
													<td class="text-center"><?= (int) $row['total_orders'] ?></td>
													<td class="text-right"><?= formatCurrencyVND($row['total_amount'] ?? 0) ?></td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="3" class="text-center text-muted">Chưa có dữ liệu đơn hàng.</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="card card-outline card-success">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-box-open mr-1"></i> Top sản phẩm bán chạy (30 ngày)</h3>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-striped table-valign-middle mb-0">
									<thead>
										<tr>
											<th>Sản phẩm</th>
											<th class="text-center">Số lượng</th>
											<th class="text-right">Doanh số</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!empty($topProducts)): ?>
											<?php foreach ($topProducts as $product): ?>
												<tr>
													<td><?= htmlspecialchars($product['item_name']) ?></td>
													<td class="text-center"><?= (int) $product['total_qty'] ?></td>
													<td class="text-right"><?= formatCurrencyVND($product['total_sales']) ?></td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="3" class="text-center text-muted">Chưa có dữ liệu bán hàng trong 30 ngày gần đây.</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="card card-outline card-info">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-history mr-1"></i> Đơn hàng gần đây</h3>
						</div>
						<div class="card-body">
							<table class="table table-bordered table-striped data-table">
								<thead>
									<tr>
										<th>Mã đơn</th>
										<th>Khách hàng</th>
										<th>Thời gian</th>
										<th>Thanh toán</th>
										<th>Trạng thái</th>
										<th class="text-right">Tổng tiền</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($recentOrders)): ?>
										<?php foreach ($recentOrders as $order): ?>
											<?php $status = $order['status']; ?>
											<tr>
												<td><a href="order-items.php?id=<?= (int) $order['id'] ?>">#ORD<?= str_pad((string) $order['id'], 3, '0', STR_PAD_LEFT) ?></a></td>
												<td><?= htmlspecialchars($order['member_name'] ?? 'N/A') ?></td>
												<td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
												<td><?= htmlspecialchars($paymentLabelMap[$order['payment_method']] ?? $order['payment_method']) ?></td>
												<td>
													<span class="badge badge-<?= $statusBadgeMap[$status] ?? 'secondary' ?>">
														<?= htmlspecialchars($statusLabelMap[$status] ?? $status) ?>
													</span>
												</td>
												<td class="text-right"><?= formatCurrencyVND($order['total_amount']) ?></td>
											</tr>
										<?php endforeach; ?>
									<?php else: ?>
										<tr>
											<td colspan="6" class="text-center text-muted">Chưa có dữ liệu đơn hàng.</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>

				<section class="col-lg-4">
					<div class="card card-outline card-warning">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-calendar-check mr-1"></i> Lịch tập sắp tới</h3>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-sm table-striped mb-0">
									<thead>
										<tr>
											<th>Thời gian</th>
											<th>HV / HLV</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!empty($upcomingSchedules)): ?>
											<?php foreach ($upcomingSchedules as $schedule): ?>
												<tr>
													<td><?= date('d/m H:i', strtotime($schedule['training_date'])) ?></td>
													<td>
														<strong><?= htmlspecialchars($schedule['member_name']) ?></strong>
														<div class="small text-muted">HLV: <?= htmlspecialchars($schedule['trainer_name']) ?></div>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="2" class="text-center text-muted">Không có lịch tập sắp tới.</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="card card-outline card-secondary">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-chart-area mr-1"></i> Doanh thu 6 tháng</h3>
						</div>
						<div class="card-body">
							<?php if (!empty($monthlyRevenue)): ?>
								<?php $maxRevenue = max(array_map(static function ($row) { return (float) $row['revenue']; }, $monthlyRevenue)); ?>
								<?php foreach ($monthlyRevenue as $row): ?>
									<?php
										$value = (float) $row['revenue'];
										$width = $maxRevenue > 0 ? ($value / $maxRevenue) * 100 : 0;
									?>
									<div class="mb-2">
										<div class="d-flex justify-content-between">
											<span><?= htmlspecialchars($row['month_label']) ?></span>
											<span><?= formatCurrencyVND($value) ?></span>
										</div>
										<div class="progress progress-xs">
											<div class="progress-bar bg-primary" style="width: <?= number_format($width, 1, '.', '') ?>%"></div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="text-muted mb-0">Chưa có doanh thu xác nhận trong 6 tháng gần đây.</p>
							<?php endif; ?>
						</div>
					</div>

					<div class="card card-outline card-danger">
						<div class="card-header">
							<h3 class="card-title"><i class="fas fa-triangle-exclamation mr-1"></i> Cảnh báo nhanh</h3>
						</div>
						<div class="card-body p-0">
							<ul class="list-group list-group-flush">
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Sản phẩm tồn thấp (≤ 10)
									<span class="badge badge-danger badge-pill"><?= (int) $overview['low_stock_products'] ?></span>
								</li>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Thông báo chưa đọc
									<span class="badge badge-warning badge-pill"><?= (int) $overview['unread_notifications'] ?></span>
								</li>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Tổng hội viên hệ thống
									<span class="badge badge-info badge-pill"><?= (int) $overview['total_members'] ?></span>
								</li>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Tổng số đơn hàng
									<span class="badge badge-secondary badge-pill"><?= (int) $overview['total_orders'] ?></span>
								</li>
							</ul>
						</div>
					</div>
				</section>
			</div>
		</div>
	</section>
</div>

<?php include 'layout/footer.php'; ?>
