<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Lấy thông tin giỏ hàng từ database hoặc session
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
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
<!-- Breadcrumb Section End -->

<!-- Shopping Cart Section Begin -->
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
                            <!-- TODO: Hiển thị các sản phẩm trong giỏ hàng từ database -->
                            <tr>
                                <td class="cart-pic first-row">
                                    <img src="assets/img/products/product-1.jpg" alt="">
                                </td>
                                <td class="cart-title first-row">
                                    <h5>Tên sản phẩm</h5>
                                </td>
                                <td class="p-price first-row">500,000 VNĐ</td>
                                <td class="qua-col first-row">
                                    <div class="quantity">
                                        <div class="pro-qty">
                                            <span class="dec qtybtn" onclick="updateQuantity(1, 'decrease')">-</span>
                                            <input type="number" value="1" min="1" data-product-id="1">
                                            <span class="inc qtybtn" onclick="updateQuantity(1, 'increase')">+</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="total-price first-row">500,000 VNĐ</td>
                                <td class="close-td first-row">
                                    <i class="fa fa-close" onclick="removeFromCart(1)"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="cart-buttons">
                            <a href="products.php" class="primary-btn continue-shop">Tiếp tục mua hàng</a>
                        </div>
                    </div>
                    <div class="col-lg-4 offset-lg-4">
                        <div class="proceed-checkout">
                            <ul>
                                <li class="subtotal">Tạm tính <span id="subtotal">0 VNĐ</span></li>
                                <li class="cart-total">Tổng cộng <span id="total">0 VNĐ</span></li>
                            </ul>
                            <a href="checkout.php" class="proceed-btn">Thanh toán</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Shopping Cart Section End -->

<script>
// TODO: Load cart items từ database
function loadCart() {
    // AJAX call to load cart items
    console.log('Loading cart...');
}

// TODO: Update số lượng sản phẩm trong giỏ hàng
function updateQuantity(productId, action) {
    // AJAX call to ajax/cart-update.php
    console.log('Updating quantity:', productId, action);
}

// TODO: Xóa sản phẩm khỏi giỏ hàng
function removeFromCart(productId) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        // AJAX call to ajax/cart-remove.php
        console.log('Removing from cart:', productId);
    }
}

// Load cart on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});
</script>

<?php include 'layout/footer.php'; ?>
