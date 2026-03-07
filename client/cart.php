<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

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
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th><i class="fa fa-close"></i></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <?php 
                            $totalAmount = 0;
                            
                            if ($is_logged_in) {
                                $query = "
                                    SELECT ci.quantity, ci.item_id as product_id, p.name, p.selling_price, p.stock_quantity 
                                    FROM members m
                                    JOIN carts c ON m.id = c.member_id AND c.status = 'active'
                                    JOIN cart_items ci ON c.id = ci.cart_id AND ci.item_type = 'product'
                                    JOIN products p ON ci.item_id = p.id
                                    WHERE m.users_id = ?
                                ";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!$stmt) {
                                    echo '<tr><td colspan="6" class="text-center text-danger">Lỗi SQL: ' . $conn->error . '</td></tr>';
                                } else {
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        while ($item = $result->fetch_assoc()) {
                                            $id = $item['product_id'];
                                            $itemTotal = $item['selling_price'] * $item['quantity'];
                                            $totalAmount += $itemTotal;
                                            
                                            $imgPath = "../assets/uploads/products/default-product.jpg";
                                ?>
                                            <tr>
                                                <td class="cart-pic first-row">
                                                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                                                </td>
                                                <td class="cart-title first-row">
                                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                                </td>
                                                <td class="p-price first-row"><?php echo number_format($item['selling_price'], 0, ',', '.'); ?>đ</td>
                                                <td class="qua-col first-row">
                                                    <div class="quantity">
                                                        <div class="custom-pro-qty">
                                                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" onchange="updateQuantity(<?php echo $id; ?>, this.value)" style="width: 70px; height: 35px; text-align: center; border: 1px solid #ebebeb; border-radius: 4px; outline: none;">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="total-price first-row" style="color: #e7ab3c;"><?php echo number_format($itemTotal, 0, ',', '.'); ?>đ</td>
                                                <td class="close-td first-row">
                                                    <i class="fa fa-close" style="cursor: pointer; font-size: 18px;" onclick="removeFromCart(<?php echo $id; ?>)"></i>
                                                </td>
                                            </tr>
                                <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center" style="padding: 50px;">Giỏ hàng của bạn đang trống! <br><br> <a href="products.php" class="site-btn">Mua sắm ngay</a></td></tr>';
                                    }
                                    $stmt->close();
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center" style="padding: 50px;">Vui lòng <a href="login.php" style="color: #e7ab3c; font-weight: bold; text-decoration: underline;">Đăng nhập</a> để xem giỏ hàng của bạn.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="cart-buttons">
                            <a href="products.php" class="site-btn" style="background: #333; color: white;">Tiếp tục mua hàng</a>
                        </div>
                    </div>
                    <div class="col-lg-4 offset-lg-4">
                        <div class="proceed-checkout">
                            <ul>
                                <li class="subtotal">Tạm tính <span><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</span></li>
                                <li class="cart-total">Tổng cộng <span><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</span></li>
                            </ul>
                            <?php if ($totalAmount > 0): ?>
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
/* Phục hồi phần CSS bị thiếu */
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
function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) return;
    
    var formData = new FormData();
    formData.append('product_id', productId);
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

function removeFromCart(productId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        var formData = new FormData();
        formData.append('product_id', productId);

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
</script>

<?php include 'layout/footer.php'; ?>