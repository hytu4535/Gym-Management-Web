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

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo "<script>alert('Mã đơn hàng không hợp lệ!'); window.location.href='index.php';</script>";
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
    echo "<script>alert('Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này!'); window.location.href='index.php';</script>";
    exit;
}
$order = $res_order->fetch_assoc();
$stmt->close();

$display_address = $order['full_address'] ?? $order['member_address'] ?? 'Chưa cập nhật';
$display_district = $order['district'] ?? '';
$display_city = $order['city'] ?? '';

$sql_items = "SELECT item_name, price, quantity FROM order_items WHERE order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

$order_items = [];
$total_items_cost = 0;
while($row = $res_items->fetch_assoc()) {
    $subtotal = $row['price'] * $row['quantity'];
    $row['subtotal'] = $subtotal;
    $order_items[] = $row;
    $total_items_cost += $subtotal;
}
$stmt_items->close();

$shipping_fee = $order['total_amount'] - $total_items_cost;

include 'layout/header.php'; 
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Hóa đơn</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Hóa đơn</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="invoice-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="invoice-container" style="background: #fff; padding: 40px; border-radius: 5px; box-shadow: 0px 0px 15px rgba(0,0,0,0.1);">
                    <div class="invoice-header mb-5">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="invoice-logo">
                                    <h4 style="color: #e7ab3c; font-weight: bold;">Gym Management System</h4>
                                </div>
                            </div>
                            <div class="col-lg-6 text-right">
                                <div class="invoice-number">
                                    <h4>HÓA ĐƠN</h4>
                                    <p>Mã đơn hàng: <strong style="color: #e7ab3c;">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></p>
                                    <p>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                                    <p>Trạng thái: 
                                        <?php 
                                            if ($order['status'] === 'pending') echo '<span class="badge badge-warning" style="padding: 5px 10px;">Chờ xử lý</span>';
                                            elseif ($order['status'] === 'confirmed') echo '<span class="badge badge-primary" style="padding: 5px 10px;">Đã xác nhận</span>';
                                            elseif ($order['status'] === 'delivered') echo '<span class="badge badge-success" style="padding: 5px 10px;">Đã giao</span>';
                                            elseif ($order['status'] === 'cancelled') echo '<span class="badge badge-danger" style="padding: 5px 10px;">Đã hủy</span>';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-body">
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <h5 style="border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; margin-bottom: 15px;">Thông tin khách hàng</h5>
                                <p class="mb-1"><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p class="mb-0"><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>
                            <div class="col-lg-6">
                                <h5 style="border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; margin-bottom: 15px;">Địa chỉ giao hàng</h5>
                                <p class="mb-1"><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($display_address); ?></p>
                                <?php if($display_district): ?>
                                    <p class="mb-1"><strong>Quận/Huyện:</strong> <?php echo htmlspecialchars($display_district); ?></p>
                                <?php endif; ?>
                                <?php if($display_city): ?>
                                    <p class="mb-0"><strong>Thành phố:</strong> <?php echo htmlspecialchars($display_city); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <table class="table table-bordered">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th class="text-center" width="5%">STT</th>
                                            <th width="45%">Sản phẩm / Dịch vụ</th>
                                            <th class="text-right" width="20%">Đơn giá</th>
                                            <th class="text-center" width="10%">Số lượng</th>
                                            <th class="text-right" width="20%">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $stt = 1;
                                        foreach ($order_items as $item): 
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle"><?php echo $stt++; ?></td>
                                            <td class="align-middle"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td class="text-right align-middle"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                                            <td class="text-center align-middle"><?php echo $item['quantity']; ?></td>
                                            <td class="text-right align-middle" style="font-weight: bold;"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Tạm tính:</strong></td>
                                            <td class="text-right"><strong><?php echo number_format($total_items_cost, 0, ',', '.'); ?>đ</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Phí vận chuyển:</strong></td>
                                            <td class="text-right"><strong><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</strong></td>
                                        </tr>
                                        <tr class="total-row" style="background: #fdfaf3;">
                                            <td colspan="4" class="text-right"><strong style="color: #e7ab3c; font-size: 1.2rem;">Tổng cộng:</strong></td>
                                            <td class="text-right"><strong style="color: #e7ab3c; font-size: 1.2rem;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <h5 style="margin-bottom: 10px;">Phương thức thanh toán</h5>
                                <p style="font-size: 15px;"><strong>
                                    <?php 
                                        if ($order['payment_method'] === 'cash') {
                                            echo 'Thanh toán tiền mặt khi nhận hàng (COD)';
                                        } else {
                                            echo 'Thanh toán trực tuyến (VNPAY/MOMO)';
                                        }
                                    ?>
                                </strong></p>
                                
                                <div class="alert alert-success mt-3" style="border-radius: 0; border-left: 4px solid #28a745;">
                                    <i class="fa fa-check-circle" style="font-size: 18px; margin-right: 5px;"></i> Đơn hàng của bạn đã được đặt thành công! Chúng tôi sẽ liên hệ để giao hàng trong thời gian sớm nhất.
                                </div>
                            </div>
                        </div>
                    </div>

                   <div class="invoice-footer mt-5 pt-4" style="border-top: 1px dashed #ddd;">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="invoice-actions">
                                    <button onclick="window.print()" class="btn-invoice btn-print">
                                        <i class="fa fa-print"></i> In hóa đơn
                                    </button>
                                    <a href="products.php" class="btn-invoice btn-shop">
                                        <i class="fa fa-shopping-cart"></i> Tiếp tục mua sắm
                                    </a>
                                    <a href="index.php" class="btn-invoice btn-home">
                                        <i class="fa fa-home"></i> Về trang chủ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>

.invoice-actions {
    display: flex;
    justify-content: center;
    gap: 15px; 
    flex-wrap: wrap;
}

.btn-invoice {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 25px;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    border: none;
    border-radius: 4px;
    transition: all 0.3s;
    cursor: pointer;
    min-width: 180px; 
    color: #fff !important;
    text-decoration: none !important;
}

.btn-invoice i {
    margin-right: 8px;
    font-size: 16px;
}

.btn-print {
    background: #333333;
}
.btn-print:hover {
    background: #000000;
}

.btn-shop {
    background: #e7ab3c;
}
.btn-shop:hover {
    background: #d4982f;
}

.btn-home {
    background: #6c757d;
}
.btn-home:hover {
    background: #5a6268;
}

@media print {
    body { background: #fff; }
    .header-section, .breadcrumb-section, .footer-section, .invoice-footer, .btn-invoice { 
        display: none !important; 
    }
    .invoice-section { padding: 0; }
    .invoice-container { border: none !important; box-shadow: none !important; padding: 0 !important; }
}

@media (max-width: 768px) {
    .btn-invoice {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<?php include 'layout/footer.php'; ?>