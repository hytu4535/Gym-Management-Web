<?php
session_start();

$page_title = 'Nhật ký quét mặt';

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_MEMBERS');

$db = getDB();

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$where = [];
$params = [];

if ($statusFilter === 'success') {
		$where[] = 'l.is_success = 1';
} elseif ($statusFilter === 'failed') {
		$where[] = 'l.is_success = 0';
}

if ($dateFrom !== '') {
		$where[] = 'DATE(l.created_at) >= ?';
		$params[] = $dateFrom;
}

if ($dateTo !== '') {
		$where[] = 'DATE(l.created_at) <= ?';
		$params[] = $dateTo;
}

$whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

$sql = "
		SELECT
				l.id,
				l.member_id,
				m.full_name,
				l.confidence,
				l.is_success,
				l.captured_image_path,
				l.note,
				l.created_at
		FROM face_checkin_logs l
		LEFT JOIN members m ON m.id = l.member_id
		$whereSql
		ORDER BY l.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Nhật ký quét mặt</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="index.php">Home</a></li>
						<li class="breadcrumb-item active">Nhật ký khuôn mặt</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<section class="content">
		<div class="container-fluid">
			<div class="card card-outline card-primary">
				<div class="card-header">
					<h3 class="card-title">Bộ lọc nhật ký</h3>
				</div>
				<div class="card-body">
					<form method="GET" class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Trạng thái</label>
								<select name="status" class="form-control">
									<option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Tất cả</option>
									<option value="success" <?php echo $statusFilter === 'success' ? 'selected' : ''; ?>>Thành công</option>
									<option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Từ ngày</label>
								<input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-control">
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Đến ngày</label>
								<input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-control">
							</div>
						</div>
						<div class="col-md-3 d-flex align-items-end">
							<button class="btn btn-primary mr-2" type="submit">Lọc</button>
							<a href="face-logs.php" class="btn btn-secondary">Reset</a>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Danh sách log xác thực</h3>
				</div>
				<div class="card-body">
					<table class="table table-bordered table-striped js-admin-table">
						<thead>
							<tr>
								<th>ID</th>
								<th>Thời gian</th>
								<th>Hội viên</th>
								<th>Độ tin cậy</th>
								<th>Trạng thái</th>
								<th>Ghi chú</th>
								<th>Ảnh lỗi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($logs as $log): ?>
								<tr>
									<td><?php echo (int) $log['id']; ?></td>
									<td><?php echo htmlspecialchars($log['created_at']); ?></td>
									<td>
										<?php if (!empty($log['member_id'])): ?>
											#<?php echo (int) $log['member_id']; ?> - <?php echo htmlspecialchars($log['full_name'] ?? 'Không rõ'); ?>
										<?php else: ?>
											Không nhận diện
										<?php endif; ?>
									</td>
									<td><?php echo isset($log['confidence']) ? number_format((float) $log['confidence'], 4) : '-'; ?></td>
									<td>
										<?php if ((int) $log['is_success'] === 1): ?>
											<span class="badge badge-success">Thành công</span>
										<?php else: ?>
											<span class="badge badge-danger">Thất bại</span>
										<?php endif; ?>
									</td>
									<td><?php echo htmlspecialchars((string) ($log['note'] ?? '')); ?></td>
									<td>
										<?php if (!empty($log['captured_image_path'])): ?>
											<?php echo htmlspecialchars(basename((string) $log['captured_image_path'])); ?>
										<?php else: ?>
											-
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
</div>

<?php include 'layout/footer.php'; ?>

