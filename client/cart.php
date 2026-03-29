<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';
require_once '../includes/discount_helper.php';

// Kiểm tra đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

include 'layout/header.php'; 
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Giỏ hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Giỏ hàng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="shopping-cart-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Mục mua</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th><i class="fa fa-close"></i></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <?php 
                            $has_items = false;
                            
                            if ($is_logged_in) {
                                $query = "
                                    SELECT ci.item_type,
                                           ci.quantity,
                                           ci.item_id,
                                           p.name AS product_name,
                                           p.selling_price,
                                           p.stock_quantity,
                                           mp.package_name,
                                           mp.price AS package_price,
                                           mp.duration_months,
                                           s.name AS service_name,
                                           s.price AS service_price,
                                           s.type AS service_type,
                                           cs.class_name,
                                           cs.price_per_session AS class_price,
                                           cs.schedule_days AS class_schedule_days,
                                           cs.schedule_start_time AS class_start_time,
                                           cs.schedule_end_time AS class_end_time
                                    FROM members m
                                    JOIN carts c ON m.id = c.member_id AND c.status = 'active'
                                    JOIN cart_items ci ON c.id = ci.cart_id
                                    LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
                                    LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
                                    LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
                                    LEFT JOIN class_schedules cs ON ci.item_type = 'class' AND ci.item_id = cs.id
                                    WHERE m.users_id = ?
                                    ORDER BY ci.created_at DESC, ci.id DESC
                                ";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!$stmt) {
                                    echo '<tr><td colspan="6" class="text-center text-danger">Lỗi SQL: ' . $conn->error . '</td></tr>';
                                } else {
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        $has_items = true;
                                        while ($item = $result->fetch_assoc()) {
                                            $id = (int) $item['item_id'];
                                            $itemType = $item['item_type'];
                                            $isProduct = $itemType === 'product';
                                            $isPackage = $itemType === 'package';
                                            $isService = $itemType === 'service';
                                            $isClass = $itemType === 'class';
                                            $itemName = $isProduct ? $item['product_name'] : ($isPackage ? $item['package_name'] : ($isService ? $item['service_name'] : $item['class_name']));
                                            $itemQuantity = (int) $item['quantity'];

                                            if ($isProduct) {
                                                $price_info = calculateDiscountedPrice($item['selling_price'], $user_id, $conn);
                                            } elseif ($isPackage) {
                                                $price_info = [
                                                    'original_price' => (float) $item['package_price'],
                                                    'final_price' => (float) $item['package_price'],
                                                    'discount_percent' => 0,
                                                    'has_discount' => false,
                                                ];
                                            } else {
                                                $price_info = [
                                                    'original_price' => $isService ? (float) $item['service_price'] : (float) $item['class_price'],
                                                    'final_price' => $isService ? (float) $item['service_price'] : (float) $item['class_price'],
                                                    'discount_percent' => 0,
                                                    'has_discount' => false,
                                                ];
                                            }

                                            $itemTotal = $price_info['final_price'] * $itemQuantity;
                                            
                                            if ($isProduct) {
                                                $imgPath = '../assets/uploads/products/default-product.jpg';
                                            } elseif ($isPackage) {
                                                $imgPath = 'assets/img/logo.png';
                                            } elseif ($isClass) {
                                                $imgPath = 'assets/img/classes/class-1.jpg';
                                            } else {
                                                $imgPath = 'assets/img/services/services-1.jpg';
                                            }
                                ?>
                                            <tr>
                                                <td class="cart-pic first-row">
                                                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($itemName); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                                                </td>
                                                <td class="cart-title first-row">
                                                    <h5><?php echo htmlspecialchars($itemName); ?></h5>
                                                    <?php if ($isProduct && $price_info['has_discount']): ?>
                                                        <small style="color: #ff4444; font-weight: bold;">
                                                            <i class="fa fa-tag"></i> Giảm <?php echo number_format($price_info['discount_percent'], 0); ?>%
                                                        </small>
                                                    <?php elseif ($isPackage): ?>
                                                        <small style="color: #777; font-weight: bold;">
                                                            <i class="fa fa-ticket"></i> Gói <?php echo (int) $item['duration_months']; ?> tháng
                                                        </small>
                                                    <?php elseif ($isService): ?>
                                                        <small style="color: #777; font-weight: bold;">
                                                            <i class="fa fa-heartbeat"></i> Dịch vụ <?php echo htmlspecialchars((string) $item['service_type']); ?>
                                                        </small>
                                                    <?php elseif ($isClass): ?>
                                                        <small style="color: #777; font-weight: bold;">
                                                            <i class="fa fa-calendar"></i> <?php echo htmlspecialchars((string) $item['class_schedule_days']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-price first-row">
                                                    <?php if ($price_info['has_discount']): ?>
                                                        <span style="text-decoration: line-through; color: #999; font-size: 12px; display: block;">
                                                            <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?>đ
                                                        </span>
                                                        <span style="color: #e7ab3c; font-weight: bold;">
                                                            <?php echo number_format($price_info['final_price'], 0, ',', '.'); ?>đ
                                                        </span>
                                                    <?php else: ?>
                                                        <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?>đ
                                                    <?php endif; ?>
                                                </td>
                                                <td class="qua-col first-row">
                                                    <div class="quantity">
                                                        <div class="custom-pro-qty">
                                                            <?php if ($isProduct): ?>
                                                                <input type="number" value="<?php echo $itemQuantity; ?>" min="1" max="<?php echo (int) $item['stock_quantity']; ?>" onchange="updateQuantity('product', <?php echo $id; ?>, this.value)" style="width: 70px; height: 35px; text-align: center; border: 1px solid #ebebeb; border-radius: 4px; outline: none;">
                                                            <?php else: ?>
                                                                <input type="number" value="1" min="1" max="1" readonly style="width: 70px; height: 35px; text-align: center; border: 1px solid #ebebeb; border-radius: 4px; outline: none; background: #f3f3f3; cursor: not-allowed;">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="total-price first-row" style="color: #e7ab3c;"><?php echo number_format($itemTotal, 0, ',', '.'); ?>đ</td>
                                                <td class="close-td first-row">
                                                    <i class="fa fa-close" style="cursor: pointer; font-size: 18px;" onclick="removeFromCart('<?php echo $itemType; ?>', <?php echo $id; ?>)"></i>
                                                </td>
                                            </tr>
                                <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center" style="padding: 50px; color: #ffffff;">Giỏ hàng của bạn đang trống! <br><br> <a href="products.php" class="site-btn mr-2">Mua sản phẩm</a> <a href="packages.php" class="site-btn mr-2">Chọn gói tập</a> <a href="services.php" class="site-btn">Chọn dịch vụ</a></td></tr>';
                                    }
                                    $stmt->close();
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center" style="padding: 50px; color: #ffffff;">Vui lòng <a href="login.php" style="color: #e7ab3c; font-weight: bold; text-decoration: underline;">Đăng nhập</a> để xem giỏ hàng của bạn.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row">
                    <div class="col-lg-4">
                        <?php if ($is_logged_in && $has_items): ?>
                            <?php
                            $tier_info = getMemberTierDiscount($user_id, $conn);
                            $available_promotions = getAvailablePromotions($tier_info['tier_id'], $conn);
                            $selected_promotion = isset($_SESSION['selected_promotion']) ? $_SESSION['selected_promotion'] : 0;
                            ?>
                            <?php if (!empty($available_promotions)): ?>
                            <div class="promotion-box mt-4" style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 2px dashed #e7ab3c;">
                                <h5 style="margin-bottom: 15px; color: #333;">
                                    <i class="fa fa-gift" style="color: #e7ab3c;"></i> Ưu đãi đặc biệt
                                </h5>
                                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Chọn 1 ưu đãi để áp dụng:</p>
                                
                                <?php foreach ($available_promotions as $promo): ?>
                                    <?php
                                    $checked = ($promo['id'] == $selected_promotion) ? 'checked' : '';
                                    $discount_text = '';
                                    if ($promo['discount_type'] == 'percentage') {
                                        $discount_text = 'Giảm ' . number_format($promo['discount_value'], 0) . '%';
                                    } elseif ($promo['discount_type'] == 'fixed') {
                                        $discount_text = 'Giảm ' . number_format($promo['discount_value'], 0, ',', '.') . 'đ';
                                    }

                                    $usage_text = '';
                                    if ($promo['usage_limit'] !== null) {
                                        $remaining = $promo['usage_limit'] - $promo['used_count'];
                                        $usage_text = ' | Còn ' . $remaining . ' lượt';
                                    }
                                    ?>
                                    <div class="form-check mb-3" style="background: white; padding: 12px; border-radius: 5px; border: 1px solid #e1e1e1;">
                                        <input class="form-check-input promotion-radio" type="radio" name="promotion"
                                               id="promo_<?php echo $promo['id']; ?>"
                                               value="<?php echo $promo['id']; ?>"
                                               <?php echo $checked; ?>
                                               style="margin-top: 8px; cursor: pointer;">
                                        <label class="form-check-label" for="promo_<?php echo $promo['id']; ?>" style="cursor: pointer; width: 100%;">
                                            <strong style="color: #e7ab3c;"><?php echo $discount_text; ?></strong><br>
                                            <small style="color: #666;"><?php echo $promo['name']; ?></small><br>
                                            <small style="color: #999; font-size: 11px;">
                                                HSD: <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?><?php echo $usage_text; ?>
                                            </small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="form-check" style="padding: 10px;">
                                    <input class="form-check-input promotion-radio" type="radio" name="promotion"
                                           id="no_promo" value="0" <?php echo ($selected_promotion == 0) ? 'checked' : ''; ?>
                                           style="cursor: pointer;">
                                    <label class="form-check-label" for="no_promo" style="cursor: pointer; color: #999;">
                                        Không sử dụng ưu đãi
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4 offset-lg-4">
                        <div class="proceed-checkout">
                            <ul>
                                <?php if ($is_logged_in && $has_items): ?>
                                    <?php
                                        // GỌI HÀM CALCULATE CART TOTAL (Sạch sẽ và đồng bộ logic với checkout-process.php)
                                        $selected_promotion_id = isset($_SESSION['selected_promotion']) ? (int)$_SESSION['selected_promotion'] : 0;
                                        $cart_total = calculateCartTotal($user_id, $conn, $selected_promotion_id);
                                        $tier_info = getMemberTierDiscount($user_id, $conn);
                                    ?>
                                    
                                    <li class="subtotal" style="color: #ffffff;">Giá gốc: 
                                        <span style="text-decoration: line-through; color: #999;"><?php echo number_format($cart_total['subtotal_original'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                    
                                    <?php if ($cart_total['base_discount_amount'] > 0): ?>
                                        <li class="subtotal" style="color: #ffffff;">Giảm giá hạng (<?php echo $tier_info['tier_name']; ?> - <?php echo number_format($tier_info['base_discount'], 0); ?>%)
                                            <span style="color: #28a745; font-weight: bold;">-<?php echo number_format($cart_total['base_discount_amount'], 0, ',', '.'); ?>đ</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($cart_total['has_promotion']): ?>
                                        <li class="subtotal" style="background: #e7f3ff; padding: 8px; margin: 5px -10px; border-radius: 4px;">
                                            <i class="fa fa-gift" style="color: #e7ab3c;"></i> Ưu đãi: <?php echo htmlspecialchars($cart_total['promotion_info']['name']); ?>
                                            <span style="color: #ff4444; font-weight: bold;">-<?php echo number_format($cart_total['promotion_discount'], 0, ',', '.'); ?>đ</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li class="cart-total" style="color: #ffffff;">Tạm tính: 
                                        <span style="color: #e7ab3c; font-weight: bold; font-size: 20px;"><?php echo number_format($cart_total['final_subtotal'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                <?php else: ?>
                                    <li class="cart-total" style="color: #ffffff;">Tổng cộng <span style="color: #ffffff;">0đ</span></li>
                                <?php endif; ?>
                            </ul>
                            
                            <?php if ($is_logged_in && $has_items): ?>
                                <a href="checkout.php" class="proceed-btn">Tiến hành thanh toán</a>
                            <?php else: ?>
                                <a href="#" class="proceed-btn disabled-btn" onclick="return false;">Tiến hành thanh toán</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* CSS cho nút thanh toán */
.proceed-checkout .proceed-btn {
    background: #e7ab3c !important;
    color: #ffffff !important;
    transition: all 0.3s ease;
}
.proceed-checkout .proceed-btn:hover {
    background: #333333 !important; 
    color: #ffffff !important; 
    text-decoration: none;
}
.proceed-checkout .disabled-btn {
    background: #cccccc !important;
    cursor: not-allowed;
}
.proceed-checkout .disabled-btn:hover {
    background: #cccccc !important; 
    color: #ffffff !important;
}
</style>

<script>
function refreshCartUI() {
    fetch(window.location.href)
    .then(res => res.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const currentCartSection = document.querySelector('.shopping-cart-section .container');
        const newCartSection = doc.querySelector('.shopping-cart-section .container');
        
        if(currentCartSection && newCartSection) {
            currentCartSection.innerHTML = newCartSection.innerHTML;
        }
    })
    .catch(error => console.error('Lỗi khi tải lại giao diện:', error));
}

function updateQuantity(itemType, itemId, newQuantity) {
    newQuantity = parseInt(newQuantity);

    if (isNaN(newQuantity) || newQuantity < 1) {
        alert("Số lượng phải >= 1");
        refreshCartUI(); 
        return;
    }

    var formData = new FormData();
    formData.append('item_type', itemType);
    formData.append('product_id', itemId);
    formData.append('quantity', newQuantity);

    fetch('ajax/cart-update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshCartUI();
        } else {
            alert(data.message);
            refreshCartUI();
        }
    })
    .catch(error => console.error('Error:', error));
}

function removeFromCart(itemType, itemId) {
    var label = itemType === 'package' ? 'gói tập' : (itemType === 'service' ? 'dịch vụ' : 'sản phẩm');
    if (confirm('Bạn có chắc chắn muốn xóa ' + label + ' này khỏi giỏ hàng?')) {
        var formData = new FormData();
        formData.append('item_type', itemType);
        if (itemType === 'package') {
            formData.append('package_id', itemId);
        } else if (itemType === 'service') {
            formData.append('service_id', itemId);
        } else {
            formData.append('product_id', itemId);
        }

        fetch('ajax/cart-remove.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshCartUI();
            } else {
                alert(data.message);
                refreshCartUI(); 
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('promotion-radio')) {
        const promotionId = e.target.value;
        var formData = new FormData();
        formData.append('promotion_id', promotionId);
        
        fetch('ajax/apply-promotion.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshCartUI();
            } else {
                alert(data.message);
                refreshCartUI();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra!');
        });
    }
});
</script>

<?php include 'layout/footer.php'; ?>