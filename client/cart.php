<?php include 'layout/header.php'; ?>

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Giỏ hàng</h2>
                        <div class="bt-option">
                            <a href="./index.php">Trang chủ</a>
                            <span>Giỏ hàng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Shopping Cart Section Begin -->
    <section class="shopping-cart spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="cart-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Tổng</th>
                                    <th><i class="fa fa-close"></i></th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <!-- Cart items will be loaded here via AJAX -->
                                <tr>
                                    <td colspan="6" class="text-center">Đang tải giỏ hàng...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="cart-buttons">
                                <a href="./products.php" class="primary-btn continue-shop">Tiếp tục mua hàng</a>
                            </div>
                        </div>
                        <div class="col-lg-4 offset-lg-4">
                            <div class="proceed-checkout">
                                <ul>
                                    <li class="subtotal">Tạm tính <span id="subtotal">0đ</span></li>
                                    <li class="cart-total">Tổng cộng <span id="total">0đ</span></li>
                                </ul>
                                <a href="./checkout.php" class="proceed-btn" id="checkout-btn">Thanh toán</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shopping Cart Section End -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadCart();

            // Load cart via AJAX
            function loadCart() {
                $.ajax({
                    url: 'ajax/get-cart.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayCart(response.cart, response.subtotal, response.total);
                        } else {
                            $('#cart-items').html('<tr><td colspan="6" class="text-center">Giỏ hàng trống</td></tr>');
                        }
                    }
                });
            }

            // Display cart
            function displayCart(items, subtotal, total) {
                if (items.length === 0) {
                    $('#cart-items').html('<tr><td colspan="6" class="text-center">Giỏ hàng trống</td></tr>');
                    $('#checkout-btn').hide();
                    return;
                }

                let html = '';
                items.forEach(function(item) {
                    html += `
                        <tr data-cart-id="${item.cart_id}">
                            <td class="cart-pic">
                                <img src="${item.image}" alt="${item.name}" style="width:100px; height:100px; object-fit:cover;">
                            </td>
                            <td class="cart-title">
                                <h5><a href="./product-detail.php?id=${item.product_id}">${item.name}</a></h5>
                            </td>
                            <td class="p-price">${formatPrice(item.price)}</td>
                            <td class="qua-col">
                                <div class="quantity">
                                    <div class="pro-qty">
                                        <span class="dec qtybtn" onclick="updateQuantity(${item.cart_id}, ${item.quantity - 1})">-</span>
                                        <input type="text" value="${item.quantity}" readonly>
                                        <span class="inc qtybtn" onclick="updateQuantity(${item.cart_id}, ${item.quantity + 1})">+</span>
                                    </div>
                                </div>
                            </td>
                            <td class="total-price">${formatPrice(item.price * item.quantity)}</td>
                            <td class="close-td">
                                <i class="fa fa-close" onclick="removeFromCart(${item.cart_id})" style="cursor:pointer;"></i>
                            </td>
                        </tr>
                    `;
                });
                $('#cart-items').html(html);
                $('#subtotal').text(formatPrice(subtotal));
                $('#total').text(formatPrice(total));
                $('#cart-count').text(items.length);
            }

            // Format price
            function formatPrice(price) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
            }
        });

        // Update quantity
        function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;
            
            $.ajax({
                url: 'ajax/update-cart.php',
                method: 'POST',
                data: { cart_id: cartId, quantity: newQuantity },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Có lỗi xảy ra');
                    }
                }
            });
        }

        // Remove from cart
        function removeFromCart(cartId) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
            
            $.ajax({
                url: 'ajax/remove-from-cart.php',
                method: 'POST',
                data: { cart_id: cartId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Có lỗi xảy ra');
                    }
                }
            });
        }
    </script>

    <style>
        .cart-table { margin-bottom: 30px; }
        .cart-table table { width: 100%; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: 15px; border: 1px solid #ddd; text-align: center; }
        .cart-table th { background: #f8f8f8; font-weight: 600; }
        .cart-pic img { width: 100px; height: 100px; object-fit: cover; }
        .cart-title h5 { margin: 0; font-size: 16px; }
        .p-price, .total-price { color: #f36100; font-weight: 600; font-size: 16px; }
        .pro-qty { display: inline-flex; align-items: center; border: 1px solid #ddd; }
        .pro-qty input { width: 50px; text-align: center; border: none; }
        .pro-qty .qtybtn { padding: 5px 10px; cursor: pointer; user-select: none; }
        .close-td i { color: #f36100; font-size: 18px; }
        .cart-buttons { margin-bottom: 20px; }
        .continue-shop { display: inline-block; padding: 12px 30px; background: #111; color: white; }
        .proceed-checkout { background: #f8f8f8; padding: 30px; }
        .proceed-checkout ul { list-style: none; padding: 0; margin-bottom: 20px; }
        .proceed-checkout li { display: flex; justify-content: space-between; padding: 10px 0; font-size: 16px; }
        .proceed-checkout .cart-total { font-weight: 700; font-size: 18px; color: #f36100; border-top: 2px solid #ddd; padding-top: 15px; }
        .proceed-btn { display: block; width: 100%; padding: 15px; background: #f36100; color: white; text-align: center; font-weight: 700; }
    </style>

<?php include 'layout/footer.php'; ?>
