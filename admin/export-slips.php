<?php
session_start();

$page_title = "Phiếu xuất kho";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('MANAGE_SALES');

include 'layout/header.php';
include 'layout/sidebar.php';

require_once '../config/db.php';

$filter_export_status = isset($_GET['export_status']) ? trim($_GET['export_status']) : 'valid';
$filter_from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$filter_to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$allowedExportStatus = ['valid', 'confirmed', 'delivered', 'all_non_cancelled', 'all'];
if (!in_array($filter_export_status, $allowedExportStatus, true)) {
    $filter_export_status = 'valid';
}

$sql = "SELECT
            o.id,
            o.order_date,
            o.total_amount,
            o.status,
            o.payment_method,
            COALESCE(m.full_name, 'Khách vãng lai') AS customer_name,
            CONCAT_WS(', ', a.full_address, a.district, a.city) AS shipping_address,
            COALESCE(SUM(oi.quantity), 0) AS total_qty,
            COUNT(oi.id) AS total_lines
        FROM orders o
        LEFT JOIN members m ON o.member_id = m.id
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE 1 = 1";

if ($filter_export_status === 'valid') {
    $sql .= " AND o.status IN ('confirmed', 'delivered')";
} elseif ($filter_export_status === 'confirmed') {
    $sql .= " AND o.status = 'confirmed'";
} elseif ($filter_export_status === 'delivered') {
    $sql .= " AND o.status = 'delivered'";
} elseif ($filter_export_status === 'all_non_cancelled') {
    $sql .= " AND o.status <> 'cancelled'";
}

if ($filter_from_date !== '') {
    $safeFromDate = $conn->real_escape_string($filter_from_date);
    $sql .= " AND DATE(o.order_date) >= '{$safeFromDate}'";
}

if ($filter_to_date !== '') {
    $safeToDate = $conn->real_escape_string($filter_to_date);
    $sql .= " AND DATE(o.order_date) <= '{$safeToDate}'";
}

if ($keyword !== '') {
    $safeKeyword = $conn->real_escape_string($keyword);
    $sql .= " AND (
                o.id LIKE '%{$safeKeyword}%' OR
                m.full_name LIKE '%{$safeKeyword}%' OR
                a.full_address LIKE '%{$safeKeyword}%' OR
                a.city LIKE '%{$safeKeyword}%' OR
                a.district LIKE '%{$safeKeyword}%'
              )";
}

$sql .= " GROUP BY o.id, o.order_date, o.total_amount, o.status, o.payment_method, customer_name, shipping_address
          ORDER BY o.id DESC";

$result = $conn->query($sql);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Quản lý phiếu xuất kho</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Phiếu xuất kho</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-12">
          <div class="card card-primary collapsed-card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc phiếu xuất</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <form method="GET" action="export-slips.php">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Trạng thái xuất</label>
                      <select name="export_status" class="form-control">
                        <option value="valid" <?php echo ($filter_export_status === 'valid') ? 'selected' : ''; ?>>Hợp lệ (Đã xác nhận + Đã giao)</option>
                        <option value="confirmed" <?php echo ($filter_export_status === 'confirmed') ? 'selected' : ''; ?>>Chỉ Đã xác nhận</option>
                        <option value="delivered" <?php echo ($filter_export_status === 'delivered') ? 'selected' : ''; ?>>Chỉ Đã giao</option>
                        <option value="all_non_cancelled" <?php echo ($filter_export_status === 'all_non_cancelled') ? 'selected' : ''; ?>>Tất cả trừ Đã hủy</option>
                        <option value="all" <?php echo ($filter_export_status === 'all') ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Từ ngày</label>
                      <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($filter_from_date); ?>">
                    </div>
                  </div>

                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Đến ngày</label>
                      <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($filter_to_date); ?>">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Từ khóa</label>
                      <input type="text" name="keyword" class="form-control" placeholder="Mã đơn, khách hàng, địa chỉ..." value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                  </div>

                  <div class="col-md-1">
                    <div class="form-group">
                      <label>&nbsp;</label>
                      <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <a href="export-slips.php" class="btn btn-secondary btn-sm">
                      <i class="fas fa-redo"></i> Xóa bộ lọc
                    </a>
                    <?php
                      $totalResults = $result ? $result->num_rows : 0;
                      echo "<span class='ml-3 text-muted'>Tìm thấy: <strong>{$totalResults}</strong> phiếu</span>";
                    ?>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Danh sách phiếu xuất</h3>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped data-table">
                <thead>
                  <tr>
                    <th>Mã PX</th>
                    <th>Đơn tham chiếu</th>
                    <th>Khách hàng</th>
                    <th>Ngày xuất</th>
                    <th>SL hàng</th>
                    <th>Giá trị xuất</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                      $orderId = (int)$row['id'];
                      $exportCode = '#PX' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
                      $orderCode = '#ORD' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
                      $exportDate = date('d/m/Y H:i', strtotime($row['order_date']));
                      $totalAmount = number_format((float)$row['total_amount'], 0, ',', '.') . 'đ';
                      $totalQty = (int)$row['total_qty'];

                      if ($row['status'] === 'delivered') {
                        $statusBadge = '<span class="badge badge-success">Đã giao</span>';
                      } elseif ($row['status'] === 'confirmed') {
                        $statusBadge = '<span class="badge badge-info">Đã xác nhận</span>';
                      } elseif ($row['status'] === 'pending') {
                        $statusBadge = '<span class="badge badge-warning">Chờ xử lý</span>';
                      } else {
                        $statusBadge = '<span class="badge badge-danger">Đã hủy</span>';
                      }
                    ?>
                    <tr>
                      <td class="font-weight-bold text-primary"><?php echo $exportCode; ?></td>
                      <td><?php echo $orderCode; ?></td>
                      <td>
                        <div class="font-weight-bold"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($row['shipping_address'] ?: 'Không có địa chỉ giao hàng'); ?></small>
                      </td>
                      <td><?php echo $exportDate; ?></td>
                      <td><?php echo $totalQty; ?></td>
                      <td class="text-danger font-weight-bold"><?php echo $totalAmount; ?></td>
                      <td><?php echo $statusBadge; ?></td>
                      <td>
                        <a href="order-items.php?id=<?php echo $orderId; ?>" class="btn btn-info btn-sm" title="Xem chi tiết dòng hàng">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="export-slip-print.php?order_id=<?php echo $orderId; ?>" class="btn btn-primary btn-sm" title="In phiếu xuất" target="_blank">
                          <i class="fas fa-print"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">Không có dữ liệu phiếu xuất theo bộ lọc đã chọn.</td>
                  </tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'layout/footer.php'; ?>
