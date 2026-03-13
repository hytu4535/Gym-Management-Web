<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    echo "<script>alert('Mã đơn hàng không hợp lệ!'); window.location.href='order-history.php';</script>";
    exit;
}


$sql_order = "
    SELECT o.*, 
           m.full_name, m.phone, m.address as member_address,
           u.email, 
           a.full_address, a.district, a.city 
    FROM orders o
    JOIN members m ON o.member_id = m.id
    JOIN users u ON m.users_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ? AND u.id = ?
";
$stmt = $conn->prepare($sql_order);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$res_order = $stmt->get_result();

if ($res_order->num_rows === 0) {
    echo "<script>alert('Không tìm thấy đơn hàng hoặc bạn không có quyền xem!'); window.location.href='order-history.php';</script>";
    exit;
}
$order = $res_order->fetch_assoc();
$stmt->close();

$display_address = $order['full_address'] ?? $order['member_address'] ?? 'Chưa cập nhật';
$display_district = $order['district'] ?? '';
$display_city = $order['city'] ?? '';

$sql_items = "
    SELECT oi.*, p.img 
    FROM order_items oi
    LEFT JOIN products p ON oi.item_id = p.id AND oi.item_type = 'product'
    WHERE oi.order_id = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

$order_items = [];
$total_items_cost = 0;
while($row = $res_items->fetch_assoc()) {
    $order_items[] = $row;
    $total_items_cost += $row['subtotal'];
}
$stmt_items->close();

$shipping_fee = $order['total_amount'] - $total_items_cost;

$status = $order['status'];
$is_cancelled = ($status === 'cancelled');
$step1 = true; 
$step2 = in_array($status, ['confirmed', 'delivered']);
$step3 = ($status === 'delivered');

include 'layout/header.php'; 
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Chi tiết đơn hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="order-history.php">Lịch sử</a>
                        <span>Chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="order-detail-section spad">
    <div class="container">
        <div class="order-detail-container">
            <div class="order-detail-header mb-4">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h4>Mã đơn hàng <strong style="color:#f36100;">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></h4>
                        <p class="mb-0 text-muted">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                    </div>
                    <div class="col-lg-6 text-right">
                        <?php 
                            if ($status === 'pending') echo '<span class="badge badge-warning p-2">Chờ xác nhận</span>';
                            elseif ($status === 'confirmed') echo '<span class="badge badge-primary p-2">Đang giao</span>';
                            elseif ($status === 'delivered') echo '<span class="badge badge-success p-2">Thành công</span>';
                            elseif ($status === 'cancelled') echo '<span class="badge badge-danger p-2">Đã hủy</span>';
                        ?>
                    </div>
                </div>
            </div>

            <div class="order-timeline mb-5">
                <h5 class="mb-4">Trạng thái vận chuyển</h5>
                <?php if ($is_cancelled): ?>
                    <div class="alert alert-danger"><i class="fa fa-times-circle"></i> Đơn hàng này đã bị hủy.</div>
                <?php else: ?>
                    <div class="timeline-container">
                        <div class="timeline-step <?php echo $step1 ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fa fa-shopping-basket"></i></div>
                            <div class="step-label">Đã đặt hàng</div>
                        </div>
                        <div class="timeline-step <?php echo $step2 ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fa fa-truck"></i></div>
                            <div class="step-label">Đang giao</div>
                        </div>
                        <div class="timeline-step <?php echo $step3 ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fa fa-check-circle"></i></div>
                            <div class="step-label">Hoàn thành</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row mb-5">
                <div class="col-md-6 border-right">
                    <h6>Thông tin người nhận</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['phone']); ?></p>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($order['email']); ?></p>
                </div>
                <div class="col-md-6 pl-md-4">
                    <h6>Địa chỉ giao hàng</h6>
                    <p class="mb-1"><?php echo htmlspecialchars($display_address); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($display_district . ', ' . $display_city); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-cart">
                    <thead class="border-bottom">
                        <tr>
                            <th class="pl-0">Sản phẩm</th>
                            <th class="text-right">Giá</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-right pr-0">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): 
                            $imgPath = $item['img'] ? "../assets/uploads/products/{$item['img']}" : "../assets/uploads/products/default-product.jpg";
                        ?>
                        <tr class="border-bottom">
                            <td class="pl-0 py-3 d-flex align-items-center">
                                <img src="<?php echo $imgPath; ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 15px;">
                                <span><?php echo htmlspecialchars($item['item_name']); ?></span>
                            </td>
                            <td class="text-right align-middle"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                            <td class="text-center align-middle"><?php echo $item['quantity']; ?></td>
                            <td class="text-right align-middle pr-0 font-weight-bold"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-4">
                <div class="col-lg-4">
                    <div class="summary-box">
                        <p class="d-flex justify-content-between">Tạm tính: <span><?php echo number_format($total_items_cost, 0, ',', '.'); ?>đ</span></p>
                        <p class="d-flex justify-content-between">Phí vận chuyển: <span><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</span></p>
                        <h5 class="d-flex justify-content-between mt-2 pt-2 border-top" style="color:#f36100;">Tổng cộng: <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span></h5>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="order-history.php" class="btn btn-outline-secondary px-4 mr-2">Quay lại</a>
                <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="btn btn-dark px-4 mr-2">Hóa đơn</a>
                <?php if ($status === 'pending'): ?>
                    <button class="btn btn-danger px-4" onclick="cancelOrder(<?php echo $order['id']; ?>)">Hủy đơn</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.order-detail-container { background: #fff; padding: 40px; border: 1px solid #ebebeb; border-radius: 8px; }
.timeline-container { display: flex; justify-content: space-between; position: relative; padding: 20px 0; }
.timeline-container:before { content: ''; position: absolute; top: 40px; left: 10%; right: 10%; height: 2px; background: #ddd; z-index: 1; }
.timeline-step { position: relative; z-index: 2; text-align: center; width: 33.33%; }
.step-icon { width: 40px; height: 40px; border-radius: 50%; background: #ddd; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; transition: 0.3s; }
.timeline-step.active .step-icon { background: #f36100; box-shadow: 0 0 10px rgba(243, 97, 0, 0.4); }
.step-label { font-size: 13px; font-weight: 600; color: #999; }
.timeline-step.active .step-label { color: #333; }
.summary-box p { margin-bottom: 8px; font-size: 15px; }
.table-cart td { vertical-align: middle; }
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        fetch('ajax/order-cancel.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else { alert('Lỗi: ' + data.message); }
        });
    }
}
</script>

<?php include 'layout/footer.php'; ?>