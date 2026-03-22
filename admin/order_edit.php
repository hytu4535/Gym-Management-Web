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
                                        <?php
                                        // Xác định các trạng thái có thể chuyển đổi
                                        $current_status = $order['status'];
                                        $all_statuses = [
                                            'pending' => '🟡 Chờ xử lý',
                                            'confirmed' => '🔵 Đã xác nhận',
                                            'delivered' => '🟢 Đã giao / Hoàn thành',
                                            'cancelled' => '🔴 Đã hủy'
                                        ];

                                        // Xác định trạng thái có thể chuyển
                                        $allowed_statuses = [];
                                        if ($current_status == 'pending') {
                                            $allowed_statuses = ['pending', 'confirmed', 'cancelled'];
                                        } elseif ($current_status == 'confirmed') {
                                            $allowed_statuses = ['confirmed', 'delivered', 'cancelled'];
                                        } elseif ($current_status == 'delivered') {
                                            $allowed_statuses = ['delivered']; // Không thể thay đổi
                                        } elseif ($current_status == 'cancelled') {
                                            $allowed_statuses = ['cancelled']; // Không thể thay đổi
                                        }

                                        foreach ($all_statuses as $status_key => $status_label) {
                                            $selected = ($current_status == $status_key) ? 'selected' : '';
                                            $disabled = !in_array($status_key, $allowed_statuses) ? 'disabled' : '';
                                            echo "<option value='$status_key' $selected $disabled>$status_label</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <?php
                                        if ($current_status == 'pending') {
                                            echo "Có thể chuyển sang: Đã xác nhận hoặc Đã hủy";
                                        } elseif ($current_status == 'confirmed') {
                                            echo "Có thể chuyển sang: Đã giao hoặc Đã hủy";
                                        } elseif ($current_status == 'delivered') {
                                            echo "Đơn hàng đã giao không thể thay đổi trạng thái";
                                        } elseif ($current_status == 'cancelled') {
                                            echo "Đơn hàng đã hủy không thể thay đổi trạng thái";
                                        }
                                        ?>
                                    </small>
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