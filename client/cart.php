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
                <div class="cart-items-wrapper">
                    <div class="cart-header-mobile">
                        <h3 style="margin: 0; color: #2c3e50;">Giỏ hàng của bạn</h3>
                    </div>
                    <!-- Desktop View - Table -->
                    <div class="cart-table cart-table-desktop">
                        <table>
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                    <th style="color: #495057; font-weight: 600; padding: 15px 10px;">Sản phẩm</th>
                                    <th style="color: #495057; font-weight: 600; padding: 15px 10px; text-align: right;">Đơn giá</th>
                                    <th style="color: #495057; font-weight: 600; padding: 15px 10px; text-align: center;">Số lượng</th>
                                    <th style="color: #495057; font-weight: 600; padding: 15px 10px; text-align: right;">Thành tiền</th>
                                    <th style="color: #495057; font-weight: 600; padding: 15px 10px; text-align: center;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                            <?php 
                            $totalAmount = 0;
                            $totalDiscount = 0;
                            $originalTotal = 0;
                            
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
                                         s.type AS service_type
                                    FROM members m
                                    JOIN carts c ON m.id = c.member_id AND c.status = 'active'
                                    JOIN cart_items ci ON c.id = ci.cart_id
                                    LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
                                    LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
                                     LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
                                    WHERE m.users_id = ?
                                    ORDER BY ci.created_at DESC, ci.id DESC
                                ";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!$stmt) {
                                    echo '<tr><td colspan="5" class="text-center text-danger" style="padding: 30px;">Lỗi SQL: ' . $conn->error . '</td></tr>';
                                } else {
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        while ($item = $result->fetch_assoc()) {
                                            $id = (int) $item['item_id'];
                                            $itemType = $item['item_type'];
                                            $isProduct = $itemType === 'product';
                                            $isPackage = $itemType === 'package';
                                            $isService = $itemType === 'service';
                                            $itemName = $isProduct ? $item['product_name'] : ($isPackage ? $item['package_name'] : $item['service_name']);
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
                                                    'original_price' => (float) $item['service_price'],
                                                    'final_price' => (float) $item['service_price'],
                                                    'discount_percent' => 0,
                                                    'has_discount' => false,
                                                ];
                                            }

                                            $itemTotal = $price_info['final_price'] * $itemQuantity;
                                            $itemOriginal = $price_info['original_price'] * $itemQuantity;
                                            
                                            $totalAmount += $itemTotal;
                                            $originalTotal += $itemOriginal;
                                            $totalDiscount += ($itemOriginal - $itemTotal);
                                            
                                            if ($isProduct) {
                                                $imgPath = '../assets/uploads/products/default-product.jpg';
                                            } elseif ($isPackage) {
                                                $imgPath = 'assets/img/logo.png';
                                            } else {
                                                $imgPath = 'assets/img/services/services-1.jpg';
                                            }
                                ?>
                                            <tr style="border-bottom: 1px solid #ecf0f1; vertical-align: middle;">
                                                <td style="padding: 16px 10px;">
                                                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                                                        <div style="flex-shrink: 0;">
                                                            <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($itemName); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ecf0f1;">
                                                        </div>
                                                        <div style="flex: 1; min-width: 0;">
                                                            <h5 style="margin: 0 0 6px 0; color: #2c3e50; font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($itemName); ?></h5>
                                                            <?php if ($isProduct && $price_info['has_discount']): ?>
                                                                <span style="display: inline-block; background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                                                    <i class="fa fa-tag"></i> Giảm <?php echo number_format($price_info['discount_percent'], 0); ?>%
                                                                </span>
                                                            <?php elseif ($isPackage): ?>
                                                                <span style="display: inline-block; background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                                                    <i class="fa fa-calendar"></i> Gói <?php echo (int) $item['duration_months']; ?> tháng
                                                                </span>
                                                            <?php elseif ($isService): ?>
                                                                <span style="display: inline-block; background: #f3e5f5; color: #6a1b9a; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                                                    <i class="fa fa-heartbeat"></i> Dịch vụ <?php echo htmlspecialchars((string) $item['service_type']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="padding: 16px 10px; text-align: right;">
                                                    <?php if ($price_info['has_discount']): ?>
                                                        <div style="color: #6c757d; font-size: 13px; text-decoration: line-through;">
                                                            <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?>đ
                                                        </div>
                                                        <div style="color: #e7ab3c; font-weight: 700; font-size: 15px;">
                                                            <?php echo number_format($price_info['final_price'], 0, ',', '.'); ?>đ
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="color: #2c3e50; font-weight: 600; font-size: 15px;">
                                                            <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?>đ
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 16px 10px; text-align: center;">
                                                    <div class="quantity" style="display: inline-flex; align-items: center; border: 1px solid #ecf0f1; border-radius: 4px; background: #f8f9fa;">
                                                        <?php if ($isProduct): ?>
                                                            <input type="number" value="<?php echo $itemQuantity; ?>" min="1" max="<?php echo (int) $item['stock_quantity']; ?>" onchange="updateQuantity('product', <?php echo $id; ?>, this.value)" style="width: 60px; height: 36px; text-align: center; border: none; background: transparent; outline: none; font-weight: 500;">
                                                        <?php else: ?>
                                                            <input type="number" value="1" min="1" max="1" readonly style="width: 60px; height: 36px; text-align: center; border: none; background: transparent; outline: none; font-weight: 500; cursor: not-allowed; color: #6c757d;">
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td style="padding: 16px 10px; text-align: right;">
                                                    <div style="color: #e7ab3c; font-weight: 700; font-size: 15px;">
                                                        <?php echo number_format($itemTotal, 0, ',', '.'); ?>đ
                                                    </div>
                                                </td>
                                                <td style="padding: 16px 10px; text-align: center;">
                                                    <button type="button" onclick="removeFromCart('<?php echo $itemType; ?>', <?php echo $id; ?>)" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 18px; padding: 4px 8px; transition: all 0.2s ease;" title="Xóa">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center" style="padding: 50px;">
                                            <div style="color: #6c757d; font-size: 16px;">Giỏ hàng của bạn đang trống!</div>
                                            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                                <a href="products.php" class="site-btn" style="background: #333; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px;">Mua sản phẩm</a> 
                                                <a href="packages.php" class="site-btn" style="background: #555; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px;">Chọn gói tập</a> 
                                                <a href="services.php" class="site-btn" style="background: #777; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px;">Chọn dịch vụ</a>
                                            </div>
                                        </td></tr>';
                                    }
                                    $stmt->close();
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center" style="padding: 50px;">
                                    <div style="color: #6c757d; font-size: 16px;">Vui lòng <a href="login.php" style="color: #e7ab3c; font-weight: bold; text-decoration: none;">Đăng nhập</a> để xem giỏ hàng của bạn.</div>
                                </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                <div class="row" style="margin-top: 30px;">
                    <div class="col-lg-5">
                        <div class="cart-buttons" style="display: flex; gap: 10px; flex-direction: column;">
                            <a href="products.php" class="site-btn" style="background: #34495e; color: white; text-align: center; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                <i class="fa fa-shopping-bag"></i> Tiếp tục mua sản phẩm
                            </a>
                            <a href="packages.php" class="site-btn" style="background: #7f8c8d; color: white; text-align: center; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                <i class="fa fa-gift"></i> Chọn gói tập
                            </a>
                        </div>
                        
                        <?php if ($is_logged_in && $totalAmount > 0): ?>
                            <?php
                            $tier_info = getMemberTierDiscount($user_id, $conn);
                            $available_promotions = getAvailablePromotions($tier_info['tier_id'], $conn);
                            $selected_promotion = isset($_SESSION['selected_promotion']) ? $_SESSION['selected_promotion'] : 0;
                            ?>
                            <?php if (!empty($available_promotions)): ?>
                            <div class="promotion-box" style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #e3f2fd; margin-top: 20px;">
                                <h5 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa fa-gift" style="color: #e7ab3c; font-size: 18px;"></i>
                                    <span style="font-weight: 600;">Ưu đãi khả dụng</span>
                                </h5>
                                <p style="font-size: 13px; color: #7f8c8d; margin: 0 0 12px 0;">Chọn khoảng 1 ưu đãi để áp dụng cho đơn hàng:</p>
                                
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
                                    <div class="promo-option" style="background: #f8f9fa; padding: 12px; border-radius: 6px; border: 2px solid transparent; margin-bottom: 10px; cursor: pointer; transition: all 0.2s ease;">
                                        <input class="form-check-input promotion-radio" type="radio" name="promotion"
                                               id="promo_<?php echo $promo['id']; ?>"
                                               value="<?php echo $promo['id']; ?>"
                                               <?php echo $checked; ?>
                                               style="cursor: pointer; margin-right: 10px;">
                                        <label class="form-check-label" for="promo_<?php echo $promo['id']; ?>" style="cursor: pointer; display: inline; flex-grow: 1;">
                                            <strong style="color: #e7ab3c; font-size: 14px;"><?php echo $discount_text; ?></strong><br>
                                            <small style="color: #34495e;"><?php echo $promo['name']; ?></small><br>
                                            <small style="color: #bdc3c7; font-size: 11px;">
                                                <i class="fa fa-clock-o"></i> Hết hạn: <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?><?php echo $usage_text; ?>
                                            </small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="promo-option" style="background: #f8f9fa; padding: 12px; border-radius: 6px; border: 2px solid transparent;">
                                    <input class="form-check-input promotion-radio" type="radio" name="promotion"
                                           id="no_promo" value="0" <?php echo ($selected_promotion == 0) ? 'checked' : ''; ?>
                                           style="cursor: pointer; margin-right: 10px;">
                                    <label class="form-check-label" for="no_promo" style="cursor: pointer; color: #7f8c8d; display: inline;">
                                        Không sử dụng ưu đãi
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-7">
                        <div class="proceed-checkout" style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #ecf0f1;">
                            <h5 style="margin: 0 0 20px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                                <i class="fa fa-calculator" style="color: #34495e;"></i>
                                <span style="font-weight: 600;">Tóm tắt đơn hàng</span>
                            </h5>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php
                                    $selected_promotion_id = isset($_SESSION['selected_promotion']) ? (int)$_SESSION['selected_promotion'] : 0;
                                    $cart_total = calculateCartTotal($user_id, $conn, $selected_promotion_id);
                                    $tier_info = getMemberTierDiscount($user_id, $conn);
                                ?>
                                <?php if ($cart_total['base_discount_amount'] > 0 || $cart_total['has_promotion']): ?>
                                    <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ecf0f1; color: #7f8c8d;">
                                        <span>Tiền hàng:</span>
                                        <span style="text-decoration: line-through;"><?php echo number_format($cart_total['subtotal_original'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                    <?php if ($cart_total['base_discount_amount'] > 0): ?>
                                    <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ecf0f1; background: #f0f8ff; padding-left: 10px; padding-right: 10px; margin: 5px -10px; border-radius: 4px;">
                                        <span style="color: #2c3e50; font-weight: 500;">Giảm giá hạng <span style="font-size: 12px; color: #7f8c8d;">(<?php echo $tier_info['tier_name']; ?> <?php echo number_format($tier_info['base_discount'], 0); ?>%)</span></span>
                                        <span style="color: #27ae60; font-weight: 600;">-<?php echo number_format($cart_total['base_discount_amount'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($cart_total['has_promotion']): ?>
                                    <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ecf0f1; background: #f9f3e6; padding-left: 10px; padding-right: 10px; margin: 5px -10px; border-radius: 4px;">
                                        <span style="color: #2c3e50; font-weight: 500;">
                                            <i class="fa fa-gift" style="color: #e7ab3c; margin-right: 5px;"></i><?php echo $cart_total['promotion_info']['name']; ?>
                                        </span>
                                        <span style="color: #e74c3c; font-weight: 600;">-<?php echo number_format($cart_total['promotion_discount'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                    <?php endif; ?>
                                    <li style="display: flex; justify-content: space-between; padding: 15px 0; border-top: 2px solid #ecf0f1; margin-top: 10px;">
                                        <span style="color: #2c3e50; font-weight: 600; font-size: 16px;">Thành tiền:</span>
                                        <span style="color: #e7ab3c; font-weight: 700; font-size: 18px;"><?php echo number_format($cart_total['final_subtotal'], 0, ',', '.'); ?>đ</span>
                                    </li>
                                <?php else: ?>
                                    <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ecf0f1; color: #7f8c8d;">
                                        <span>Tiền hàng:</span>
                                        <span style="color: #2c3e50; font-weight: 500;"><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</span>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; padding: 15px 0; border-top: 2px solid #ecf0f1; margin-top: 10px;">
                                        <span style="color: #2c3e50; font-weight: 600; font-size: 16px;">Thành tiền:</span>
                                        <span style="color: #e7ab3c; font-weight: 700; font-size: 18px;"><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($totalAmount > 0): ?>
                                <a href="checkout.php" class="proceed-btn" style="display: block; background: linear-gradient(135deg, #e7ab3c 0%, #d49830 100%); color: white; text-align: center; padding: 14px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 20px; transition: all 0.3s ease; border: none; cursor: pointer;">
                                    <i class="fa fa-check-circle"></i> Tiến hành thanh toán
                                </a>
                            <?php else: ?>
                                <a href="#" class="proceed-btn disabled-btn" onclick="return false;" style="display: block; background: #bdc3c7; color: #7f8c8d; text-align: center; padding: 14px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 20px; cursor: not-allowed; border: none;">
                                    <i class="fa fa-lock"></i> Tiến hành thanh toán
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Cart Page Styles */
.shopping-cart-section {
    background: #f8f9fa;
    padding: 40px 0;
}

.cart-items-wrapper {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.cart-header-mobile {
    display: none;
    margin-bottom: 20px;
}

.cart-table {
    width: 100%;
    overflow-x: auto;
}

.cart-table table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table tbody tr:hover {
    background: #fafbfc;
}

.cart-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.cart-buttons .site-btn {
    flex: 1;
    min-width: 150px;
    transition: all 0.3s ease;
    text-align: center;
}

.cart-buttons .site-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.promotion-box {
    margin-top: 20px;
}

.promotion-box .promo-option {
    display: flex;
    align-items: flex-start;
}

.promotion-box .promo-option:has(.promotion-radio:checked) {
    background: #e3f2fd !important;
    border-color: #2196f3 !important;
}

.promotion-box .promo-option .promotion-radio {
    accent-color: #e7ab3c;
    margin-top: 4px;
}

.proceed-checkout .proceed-btn {
    background: linear-gradient(135deg, #e7ab3c 0%, #d49830 100%) !important;
    color: white !important;
    transition: all 0.3s ease;
    display: block !important;
    width: 100% !important;
    border: none !important;
}

.proceed-checkout .proceed-btn:hover {
    background: linear-gradient(135deg, #d49830 0%, #b8860b 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(231, 171, 60, 0.4);
}

.proceed-checkout .disabled-btn {
    background: #bdc3c7 !important;
    cursor: not-allowed;
    color: #7f8c8d !important;
}

.proceed-checkout .disabled-btn:hover {
    background: #bdc3c7 !important;
    transform: none !important;
    box-shadow: none !important;
}

/* Desktop View */
@media (min-width: 992px) {
    .cart-table-desktop {
        display: block !important;
    }
    
    .cart-items-wrapper {
        padding: 25px;
    }
}

/* Tablet & Mobile Responsive */
@media (max-width: 991px) {
    .cart-header-mobile {
        display: block;
        margin-bottom: 0;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 12px;
    }
    
    .cart-items-wrapper {
        padding: 12px;
    }

    .cart-table {
        font-size: 14px;
        margin-top: 15px;
    }

    .cart-table table {
        display: block;
        width: 100%;
    }

    .cart-table table thead {
        display: none;
    }

    .cart-table table tbody {
        display: block;
        width: 100%;
    }

    .cart-table table tbody tr {
        display: block;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    }

    .cart-table table tbody tr:last-child {
        margin-bottom: 0;
    }

    .cart-table table tbody td {
        display: block;
        width: 100%;
        padding: 0 0 12px 0 !important;
        border: none !important;
        text-align: left !important;
        margin-bottom: 8px;
    }

    .cart-table table tbody td:last-child {
        margin-bottom: 0;
        padding-bottom: 0 !important;
    }

    /* Hình ảnh & Tên sản phẩm */
    .cart-table table tbody tr > td:nth-child(1) {
        padding-bottom: 0 !important;
    }

    .cart-table table tbody tr > td:nth-child(1) div {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .cart-table table tbody tr > td:nth-child(1) img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .cart-table table tbody tr > td:nth-child(1) h5 {
        font-size: 14px;
        margin: 0 0 6px 0;
    }

    /* Đơn giá */
    .cart-table table tbody tr > td:nth-child(2) {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0 !important;
    }

    .cart-table table tbody tr > td:nth-child(2)::before {
        content: "Đơn giá:";
        font-weight: 600;
        color: #495057;
    }

    /* Số lượng */
    .cart-table table tbody tr > td:nth-child(3) {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0 !important;
    }

    .cart-table table tbody tr > td:nth-child(3)::before {
        content: "Số lượng:";
        font-weight: 600;
        color: #495057;
    }

    /* Thành tiền */
    .cart-table table tbody tr > td:nth-child(4) {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0 !important;
        font-weight: 600;
    }

    .cart-table table tbody tr > td:nth-child(4)::before {
        content: "Thành tiền:";
        font-weight: 600;
        color: #495057;
    }

    /* Hành động */
    .cart-table table tbody tr > td:nth-child(5) {
        display: flex;
        justify-content: center;
        padding: 8px 0 !important;
        border-top: 1px solid #ecf0f1;
        padding-top: 12px !important;
    }

    .cart-buttons {
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
    }

    .cart-buttons .site-btn {
        width: 100%;
        padding: 11px 16px !important;
        font-size: 14px !important;
        border-radius: 6px;
    }

    .row {
        display: flex;
        flex-direction: column;
    }

    .col-lg-5,
    .col-lg-7,
    .col-lg-4,
    .col-lg-4.offset-lg-4 {
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-top: 0 !important;
    }

    .proceed-checkout {
        margin-top: 15px;
        padding: 18px !important;
    }

    .promotion-box {
        margin-top: 15px;
        margin-bottom: 0;
        padding: 15px !important;
    }

    .promotion-box h5 {
        font-size: 15px;
        margin-bottom: 12px !important;
    }

    .promotion-box p {
        font-size: 12px !important;
        margin-bottom: 10px !important;
    }

    .promo-option {
        padding: 10px !important;
        margin-bottom: 8px !important;
        font-size: 13px;
    }

    .proceed-checkout h5 {
        font-size: 15px;
        margin-bottom: 15px !important;
    }

    .proceed-checkout ul li {
        font-size: 13px;
        padding: 8px 0 !important;
    }

    .proceed-checkout .proceed-btn,
    .proceed-checkout .disabled-btn {
        padding: 12px 16px !important;
        font-size: 14px !important;
        margin-top: 15px !important;
    }
}

/* Extra small devices - Smartphone */
@media (max-width: 576px) {
    .shopping-cart-section {
        padding: 15px 0;
    }

    .cart-items-wrapper {
        padding: 10px;
        border-radius: 6px;
        box-shadow: none;
        border: 1px solid #ecf0f1;
    }

    .cart-header-mobile {
        padding: 8px 0;
        margin-bottom: 12px;
    }

    .cart-header-mobile h3 {
        font-size: 16px !important;
    }

    .cart-table {
        font-size: 12px;
        margin-top: 10px;
    }

    .cart-table table tbody tr {
        margin-bottom: 12px;
        padding: 10px;
        border-radius: 6px;
    }

    .cart-table table tbody tr > td:nth-child(1) {
        padding-bottom: 8px !important;
    }

    .cart-table table tbody tr > td:nth-child(1) div {
        gap: 10px;
    }

    .cart-table table tbody tr > td:nth-child(1) img {
        width: 60px;
        height: 60px;
    }

    .cart-table table tbody tr > td:nth-child(1) h5 {
        font-size: 13px;
    }

    .cart-table table tbody tr > td:nth-child(1) span {
        font-size: 11px !important;
    }

    .cart-table table tbody td {
        font-size: 12px;
        padding: 6px 0 !important;
        margin-bottom: 6px;
    }

    .cart-table table tbody td:last-child {
        margin-bottom: 0;
    }

    .cart-table table tbody tr > td::before {
        font-size: 12px;
        font-weight: 600;
    }

    .cart-buttons {
        gap: 6px;
    }

    .cart-buttons .site-btn {
        padding: 10px 12px !important;
        font-size: 12px !important;
        border-radius: 5px;
    }

    .cart-buttons .site-btn i {
        margin-right: 6px;
    }

    .promotion-box {
        padding: 12px !important;
        border-radius: 6px;
        margin-top: 12px;
    }

    .promotion-box h5 {
        font-size: 14px;
        margin-bottom: 8px !important;
    }

    .promotion-box p {
        font-size: 11px !important;
        margin-bottom: 8px !important;
    }

    .promo-option {
        padding: 8px !important;
        margin-bottom: 6px !important;
        font-size: 12px;
        flex-wrap: wrap;
    }

    .promo-option input {
        margin-right: 8px;
        margin-top: 2px;
    }

    .promo-option label {
        font-size: 12px;
    }

    .promo-option label strong {
        font-size: 12px !important;
    }

    .promo-option label small {
        font-size: 10px !important;
    }

    .proceed-checkout {
        padding: 15px !important;
        margin-top: 12px;
        border-radius: 6px;
    }

    .proceed-checkout h5 {
        font-size: 14px;
        margin-bottom: 12px !important;
    }

    .proceed-checkout ul {
        margin: 0;
        padding: 0;
    }

    .proceed-checkout ul li {
        font-size: 12px;
        padding: 6px 0 !important;
    }

    .proceed-checkout .proceed-btn,
    .proceed-checkout .disabled-btn {
        padding: 11px 14px !important;
        font-size: 13px !important;
        margin-top: 12px !important;
        border-radius: 5px;
    }

    .quantity input {
        width: 50px !important;
        height: 32px !important;
        font-size: 12px;
    }
}

/* Very small devices - Small phone */
@media (max-width: 360px) {
    .cart-header-mobile h3 {
        font-size: 14px !important;
    }

    .cart-table table tbody tr > td:nth-child(1) img {
        width: 50px;
        height: 50px;
    }

    .cart-table table tbody tr > td:nth-child(1) h5 {
        font-size: 12px;
    }

    .cart-buttons .site-btn {
        padding: 8px 10px !important;
        font-size: 11px !important;
    }

    .quantity input {
        width: 45px !important;
        height: 30px !important;
        font-size: 11px;
    }
}


/* Improved table styling */
.cart-table table {
    width: 100%;
}

.cart-table table tbody td {
    vertical-align: middle;
}

/* Button hover effects */
button[onclick*="removeFromCart"] {
    transition: all 0.2s ease;
}

button[onclick*="removeFromCart"]:hover {
    color: #c0392b !important;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cart-items-wrapper {
    animation: fadeIn 0.3s ease-in-out;
}
</style>

<script>
function updateQuantity(itemType, itemId, newQuantity) {
    if (newQuantity < 1) return;
    if (itemType === 'package' || itemType === 'service') return;
    
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
            window.location.reload(); 
        } else {
            alert(data.message);
            window.location.reload(); 
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
                window.location.reload(); 
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Xử lý chọn promotion
document.addEventListener('DOMContentLoaded', function() {
    const promotionRadios = document.querySelectorAll('.promotion-radio');
    
    promotionRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            const promotionId = this.value;
            
            var formData = new FormData();
            formData.append('promotion_id', promotionId);
            
            fetch('ajax/apply-promotion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload để cập nhật giá
                    window.location.reload();
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra!');
            });
        });
    });
});
</script>

<?php include 'layout/footer.php'; ?>