<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';


if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành thanh toán!'); window.location.href='login.php';</script>";
    exit;
}
$user_id = $_SESSION['user_id'];

$cart_sql = "
    SELECT ci.quantity, p.id, p.name, p.selling_price 
    FROM members m
    JOIN carts c ON m.id = c.member_id AND c.status = 'active'
    JOIN cart_items ci ON c.id = ci.cart_id AND ci.item_type = 'product'
    JOIN products p ON ci.item_id = p.id 
    WHERE m.users_id = ?
";
$stmt_cart = $conn->prepare($cart_sql);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_result = $stmt_cart->get_result();

if ($cart_result->num_rows === 0) {
    echo "<script>alert('Giỏ hàng của bạn đang trống. Hãy mua sắm thêm nhé!'); window.location.href='products.php';</script>";
    exit;
}

$cart_items = [];
$subtotal = 0;
while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += ($row['selling_price'] * $row['quantity']);
}
$stmt_cart->close();

$shipping_fee = 30000; 
$total = $subtotal + $shipping_fee;


$user_sql = "SELECT m.full_name, m.phone, m.address, u.email 
             FROM members m 
             JOIN users u ON m.users_id = u.id 
             WHERE u.id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

include 'layout/header.php'; 
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Thanh toán</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Thanh toán</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="checkout-section spad">
    <div class="container">
        <form id="checkout-form" action="checkout-process.php" method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <h4>Thông tin đặt hàng</h4>
                    
                    <div class="user-info-box mb-4" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
                        <h5>Thông tin người mua</h5>
                        <p class="mb-1"><strong>Họ tên:</strong> <span><?php echo htmlspecialchars($user_info['full_name'] ?? 'Chưa cập nhật'); ?></span></p>
                        <p class="mb-1"><strong>Email:</strong> <span><?php echo htmlspecialchars($user_info['email'] ?? 'Chưa cập nhật'); ?></span></p>
                        <p class="mb-0"><strong>Số điện thoại:</strong> <span><?php echo htmlspecialchars($user_info['phone'] ?? 'Chưa cập nhật'); ?></span></p>
                    </div>

                    <div class="checkout-form">
                        <h5>Địa chỉ giao hàng</h5>
                        <div class="form-group">
                            <label>Chọn địa chỉ có sẵn</label>
                            <select id="address-select" name="address_id" class="form-control">
                                <?php if (!empty($user_info['address'])): ?>
                                    <option value="default"><?php echo htmlspecialchars($user_info['address']); ?></option>
                                <?php else: ?>
                                    <option value="">-- Bạn chưa có địa chỉ mặc định --</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3 mt-3">
                            <input type="checkbox" class="form-check-input" id="use-new-address" name="use_new_address" value="1" <?php echo empty($user_info['address']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="use-new-address" style="font-weight: bold; cursor: pointer;">
                                Sử dụng địa chỉ mới
                            </label>
                        </div>

                        <div id="new-address-form" style="display: <?php echo empty($user_info['address']) ? 'block' : 'none'; ?>; background: #fff; padding: 15px; border: 1px solid #e1e1e1; border-radius: 5px;">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>Địa chỉ chi tiết <span>*</span></label>
                                        <input type="text" name="new_address" class="form-control" placeholder="Số nhà, tên đường...">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Thành phố <span>*</span></label>
                                        <input type="text" name="city" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Quận/Huyện <span>*</span></label>
                                        <input type="text" name="district" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <label>Ghi chú đơn hàng (tùy chọn)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về đơn hàng, ví dụ: Giao giờ hành chính..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="checkout-cart" style="background: #f5f5f5; padding: 30px; border-radius: 5px;">
                        <h5 style="border-bottom: 1px solid #e1e1e1; padding-bottom: 15px; margin-bottom: 20px;">Đơn hàng của bạn</h5>
                        <ul id="order-summary" style="list-style: none; padding: 0;">
                            <?php foreach ($cart_items as $item): ?>
                                <li style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
                                    <span style="color: #444; width: 70%;"><?php echo htmlspecialchars($item['name']); ?> <strong style="color: #e7ab3c;">x <?php echo $item['quantity']; ?></strong></span>
                                    <span style="font-weight: bold;"><?php echo number_format($item['selling_price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <ul class="total-cost mt-3" style="list-style: none; padding: 0; border-top: 1px solid #e1e1e1; padding-top: 20px;">
                            <li style="display: flex; justify-content: space-between; margin-bottom: 15px;">Tạm tính <span><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span></li>
                            <li style="display: flex; justify-content: space-between; margin-bottom: 15px;">Phí vận chuyển <span><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</span></li>
                            <li style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; color: #e7ab3c; border-top: 1px solid #e1e1e1; padding-top: 15px;">Tổng cộng <span><?php echo number_format($total, 0, ',', '.'); ?>đ</span></li>
                        </ul>

                        <div class="payment-method mt-4">
                            <h5 style="font-size: 16px; margin-bottom: 15px;">Phương thức thanh toán</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment-cash" value="cash" checked>
                                <label class="form-check-label" for="payment-cash" style="cursor: pointer;">
                                    Tiền mặt khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment-online" value="online">
                                <label class="form-check-label" for="payment-online" style="cursor: pointer;">
                                    Thanh toán trực tuyến (VNPAY/MOMO)
                                </label>
                            </div>
                            <div id="online-payment-info" style="display: none;" class="mt-3">
                                <div class="alert alert-info" style="font-size: 13px; padding: 10px;">
                                    Bạn sẽ được chuyển đến cổng thanh toán bảo mật sau khi bấm Đặt hàng.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="site-btn place-order-btn mt-4" style="width: 100%; background: #e7ab3c; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; transition: 0.3s;">ĐẶT HÀNG</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<style>
.place-order-btn:hover {
    background: #333 !important;
}
</style>

<script>
document.getElementById('use-new-address').addEventListener('change', function() {
    var newAddressForm = document.getElementById('new-address-form');
    var addressSelect = document.getElementById('address-select');
    
    if (this.checked) {
        newAddressForm.style.display = 'block';
        addressSelect.disabled = true;
    } else {
        newAddressForm.style.display = 'none';
        addressSelect.disabled = false;
    }
});

document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var onlineInfo = document.getElementById('online-payment-info');
        if (this.value === 'online') {
            onlineInfo.style.display = 'block';
        } else {
            onlineInfo.style.display = 'none';
        }
    });
});

document.getElementById('checkout-form').addEventListener('submit', function(e) {
    var useNewAddress = document.getElementById('use-new-address').checked;
    
    if (useNewAddress) {
        var newAddress = document.querySelector('input[name="new_address"]').value.trim();
        var city = document.querySelector('input[name="city"]').value.trim();
        var district = document.querySelector('input[name="district"]').value.trim();
        
        if (!newAddress || !city || !district) {
            e.preventDefault(); 
            alert('Vui lòng nhập đầy đủ: Địa chỉ, Thành phố và Quận/Huyện!');
        }
    }
});
</script>

<?php include 'layout/footer.php'; ?>