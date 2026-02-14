<?php 
$page_title = "Cập nhật Đơn Hàng";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT o.*, m.full_name 
        FROM orders o 
        LEFT JOIN members m ON o.member_id = m.id 
        WHERE o.id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href='orders.php';</script>";
    exit;
}
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1 class="m-0">Cập nhật Trạng thái Đơn hàng #ORD<?php echo str_pad($order['id'], 3, "0", STR_PAD_LEFT); ?></h1>
      </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Thông tin Đơn Hàng</h3>
                </div>
                <form action="process/order_edit_process.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Khách hàng:</strong> <?php echo $order['full_name'] ?? 'Khách vãng lai'; ?></p>
                                <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                                <p><strong>Tổng tiền:</strong> <span class="text-danger font-weight-bold"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span></p>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Trạng Thái Đơn Hàng <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" style="font-weight: bold;">
                                        <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>🟡 Chờ xử lý</option>
                                        <option value="confirmed" <?php echo ($order['status'] == 'confirmed') ? 'selected' : ''; ?>>🔵 Đã xác nhận</option>
                                        <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>🟢 Đã giao / Hoàn thành</option>
                                        <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>🔴 Đã hủy</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_update_order" class="btn btn-warning">Lưu Trạng Thái</button>
                        <a href="orders.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include 'layout/footer.php'; ?>