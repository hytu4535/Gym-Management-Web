<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';
require_once '../includes/discount_helper.php';


if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành thanh toán!'); window.location.href='login.php';</script>";
    exit;
}
$user_id = $_SESSION['user_id'];

$hasPhysicalProducts = false;
$hasPackageItems = false;

$cart_sql = "
    SELECT ci.item_type,
           ci.quantity,
           p.id,
           p.name,
           p.selling_price,
           mp.id AS package_id,
           mp.package_name,
           mp.price AS package_price,
           mp.duration_months,
           s.id AS service_id,
           s.name AS service_name,
           s.price AS service_price,
           s.type AS service_type
    FROM members m
    JOIN carts c ON m.id = c.member_id AND c.status = 'active'
    JOIN cart_items ci ON c.id = ci.cart_id
    LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
    LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
    LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
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
$subtotal_original = 0;
$total_discount = 0;

while ($row = $cart_result->fetch_assoc()) {
    if ($row['item_type'] === 'product') {
        $hasPhysicalProducts = true;

        $price_info = calculateDiscountedPrice($row['selling_price'], $user_id, $conn);
        $row['display_name'] = $row['name'];
        $row['final_price'] = $price_info['final_price'];
        $row['original_price'] = $price_info['original_price'];
        $row['discount_percent'] = $price_info['discount_percent'];
        $row['has_discount'] = $price_info['has_discount'];
    } elseif ($row['item_type'] === 'package') {
        $hasPackageItems = true;

        $row['display_name'] = $row['package_name'];
        $row['final_price'] = (float) $row['package_price'];
        $row['original_price'] = (float) $row['package_price'];
        $row['discount_percent'] = 0;
        $row['has_discount'] = false;
    } else {
        $row['display_name'] = $row['service_name'];
        $row['final_price'] = (float) $row['service_price'];
        $row['original_price'] = (float) $row['service_price'];
        $row['discount_percent'] = 0;
        $row['has_discount'] = false;
    }
    
    $cart_items[] = $row;
    
    $subtotal += ($row['final_price'] * $row['quantity']);
    $subtotal_original += ($row['original_price'] * $row['quantity']);
}
$stmt_cart->close();

$total_discount = $subtotal_original - $subtotal;

// Áp dụng promotion (nếu có chọn trong session)
$selected_promotion_id = isset($_SESSION['selected_promotion']) ? (int)$_SESSION['selected_promotion'] : 0;
$cart_total = calculateCartTotal($user_id, $conn, $selected_promotion_id);

$subtotal = $cart_total['final_subtotal']; // Tổng sau base + promotion
$shipping_fee = $hasPhysicalProducts ? 30000 : 0;
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
    <style>
    .checkout-section { background: #f5f8fb; padding: 40px 0; }
    .checkout-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 28px rgba(0,0,0,0.08); border: 1px solid #e8ecf1; padding: 25px; }
    .checkout-card h4, .checkout-card h5 { color: #1f2937; }
    .user-info-box {background: #f8fafc; border: 1px solid #dbe6ef; border-radius: 10px; padding: 18px; margin-bottom: 20px;}
    .user-info-box h5 { font-weight: 600; color: #0f172a; }
    .user-info-box p { margin: 4px 0; color: #334155; }
    .checkout-form .form-group label { font-weight: 600; color: #334155; }
    .checkout-form .form-control { border: 1px solid #d1d5db; background: #fff; border-radius: 8px; }
    .checkout-form .form-control:focus { border-color: #60a5fa; box-shadow: 0 0 0 4px rgba(96,165,250,0.12); }
    .checkout-cart { background: #fff; border: 1px solid #dbe6ef; border-radius: 12px; padding: 22px; margin-top: 0; }
    .checkout-cart h5 { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 18px; }
    #order-summary li { margin-bottom: 10px; }
    .total-cost li span { font-weight: 600; }
    .site-btn.place-order-btn { width: 100%; background: linear-gradient(135deg, #0ea5e9, #0d9488); color: #fff; border: 0; border-radius: 8px; padding: 12px 16px; font-weight: 700; text-transform: uppercase; }
    .site-btn.place-order-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(14,165,233,0.3); }
    .form-check .form-check-input { accent-color: #0ea5e9; }
    .alert-info { background: #ecfeff; border-color: #7dd3fc; color: #0c4a6e; }
    @media (max-width: 991px) {
        .checkout-card { padding: 18px; }
        .col-lg-8, .col-lg-4 { max-width: 100%; flex: 0 0 100%; }
        .checkout-cart { margin-top: 20px; }
    }
    </style>
    <div class="container">
        <form id="checkout-form" action="checkout-process.php" method="POST">
            <div class="row checkout-card">
                <div class="col-lg-8">
                    <h4 style="color:#111827; font-weight: 700; margin-bottom: 16px;">Thông tin đặt hàng</h4>
                    
                    <div class="user-info-box mb-4" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
                        <h5>Thông tin người mua</h5>
                        <p class="mb-1"><strong>Họ tên:</strong> <span><?php echo htmlspecialchars($user_info['full_name'] ?? 'Chưa cập nhật'); ?></span></p>
                        <p class="mb-1"><strong>Email:</strong> <span><?php echo htmlspecialchars($user_info['email'] ?? 'Chưa cập nhật'); ?></span></p>
                        <p class="mb-0"><strong>Số điện thoại:</strong> <span><?php echo htmlspecialchars($user_info['phone'] ?? 'Chưa cập nhật'); ?></span></p>
                    </div>

                    <div class="checkout-form">
                        <?php if ($hasPhysicalProducts): ?>
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
                        <?php else: ?>
                        <div class="alert alert-info mb-4">
                            Đơn hàng này chỉ gồm gói tập/dịch vụ, nên không cần địa chỉ giao hàng.
                        </div>
                        <?php endif; ?>

                        <div class="form-group mt-4">
                            <label>Ghi chú đơn hàng (tùy chọn)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về đơn hàng, ví dụ: Giao giờ hành chính..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="checkout-cart" style="background: #f5f5f5; padding: 30px; border-radius: 5px;">
                        <h5 style="border-bottom: 1px solid #e1e1e1; padding-bottom: 15px; margin-bottom: 20px;">Đơn hàng của bạn</h5>
                        
                        <?php 
                        $tier_info = getMemberTierDiscount($user_id, $conn);
                        $total_saved = $cart_total['base_discount_amount'] + $cart_total['promotion_discount'];
                        
                        if ($total_saved > 0): 
                        ?>
                        <div class="alert alert-success" style="padding: 10px 15px; font-size: 13px; margin-bottom: 20px;">
                            <i class="fa fa-gift"></i> <strong>Hạng <?php echo $tier_info['tier_name']; ?></strong><br>
                            Tổng tiết kiệm: <strong><?php echo number_format($total_saved, 0, ',', '.'); ?>đ</strong>
                        </div>
                        <?php endif; ?>
                        
                        <ul id="order-summary" style="list-style: none; padding: 0;">
                            <?php foreach ($cart_items as $item): ?>
                                <li style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
                                    <span style="color: #444; width: 70%;">
                                        <?php echo htmlspecialchars($item['display_name']); ?> 
                                        <strong style="color: #e7ab3c;">x <?php echo $item['quantity']; ?></strong>
                                        <?php if ($item['has_discount']): ?>
                                            <br><small style="color: #28a745; font-weight: bold;">-<?php echo number_format($item['discount_percent'], 0); ?>%</small>
                                        <?php elseif ($item['item_type'] === 'package'): ?>
                                            <br><small style="color: #777; font-weight: bold;">Gói <?php echo (int) $item['duration_months']; ?> tháng</small>
                                        <?php elseif ($item['item_type'] === 'service'): ?>
                                            <br><small style="color: #777; font-weight: bold;">Dịch vụ <?php echo htmlspecialchars((string) $item['service_type']); ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <span style="font-weight: bold;"><?php echo number_format($item['final_price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <ul class="total-cost mt-3" style="list-style: none; padding: 0; border-top: 1px solid #e1e1e1; padding-top: 20px;">
                            <li style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                                Giá gốc 
                                <span style="text-decoration: line-through; color: #999;"><?php echo number_format($cart_total['subtotal_original'], 0, ',', '.'); ?>đ</span>
                            </li>
                            <?php if ($cart_total['base_discount_amount'] > 0): ?>
                            <li style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #28a745;">
                                Giảm hạng (<?php echo $tier_info['tier_name']; ?> <?php echo number_format($tier_info['base_discount'], 0); ?>%)
                                <span>-<?php echo number_format($cart_total['base_discount_amount'], 0, ',', '.'); ?>đ</span>
                            </li>
                            <?php endif; ?>
                            <?php if ($cart_total['has_promotion']): ?>
                            <li style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #ff4444; font-weight: bold; background: #fff3cd; padding: 8px; border-radius: 4px;">
                                <span><i class="fa fa-gift"></i> <?php echo $cart_total['promotion_info']['name']; ?></span>
                                <span>-<?php echo number_format($cart_total['promotion_discount'], 0, ',', '.'); ?>đ</span>
                            </li>
                            <?php endif; ?>
                            <li style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold;">
                                Tạm tính 
                                <span><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                            </li>
                            <li style="display: flex; justify-content: space-between; margin-bottom: 15px;"><?php echo $hasPhysicalProducts ? 'Phí vận chuyển' : 'Phí giao hàng'; ?> <span><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</span></li>
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

                        <button type="submit" class="site-btn place-order-btn mt-4">ĐẶT HÀNG</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
var useNewAddress = document.getElementById('use-new-address');
if (useNewAddress) {
    useNewAddress.addEventListener('change', function() {
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
}

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