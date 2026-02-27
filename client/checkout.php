<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Kiểm tra giỏ hàng có sản phẩm không
// TODO: Lấy danh sách địa chỉ của user
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
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
<!-- Breadcrumb Section End -->

<!-- Checkout Section Begin -->
<section class="checkout-section spad">
    <div class="container">
        <form id="checkout-form" action="checkout-process.php" method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <h4>Thông tin đặt hàng</h4>
                    
                    <!-- Thông tin người đăng nhập -->
                    <div class="user-info-box mb-4">
                        <h5>Thông tin người mua</h5>
                        <!-- TODO: Tự động hiển thị thông tin người đăng nhập -->
                        <p><strong>Họ tên:</strong> <span id="user-name">...</span></p>
                        <p><strong>Email:</strong> <span id="user-email">...</span></p>
                        <p><strong>Số điện thoại:</strong> <span id="user-phone">...</span></p>
                    </div>

                    <!-- Chọn địa chỉ giao hàng -->
                    <div class="checkout-form">
                        <h5>Địa chỉ giao hàng</h5>
                        <div class="form-group">
                            <label>Chọn địa chỉ có sẵn</label>
                            <select id="address-select" name="address_id" class="form-control">
                                <option value="">-- Chọn địa chỉ --</option>
                                <!-- TODO: Load danh sách địa chỉ từ database -->
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="use-new-address">
                            <label class="form-check-label" for="use-new-address">
                                Sử dụng địa chỉ mới
                            </label>
                        </div>

                        <div id="new-address-form" style="display: none;">
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

                        <div class="form-group">
                            <label>Ghi chú đơn hàng (tùy chọn)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về đơn hàng..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="checkout-cart">
                        <h5>Đơn hàng của bạn</h5>
                        <ul id="order-summary">
                            <!-- TODO: Hiển thị tóm tắt giỏ hàng -->
                        </ul>
                        <ul class="total-cost">
                            <li>Tạm tính <span id="subtotal">0 VNĐ</span></li>
                            <li>Phí vận chuyển <span id="shipping">30,000 VNĐ</span></li>
                            <li>Tổng cộng <span id="total">0 VNĐ</span></li>
                        </ul>

                        <div class="payment-method">
                            <h5>Phương thức thanh toán</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment-cash" value="cash" checked>
                                <label class="form-check-label" for="payment-cash">
                                    Tiền mặt khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment-online" value="online">
                                <label class="form-check-label" for="payment-online">
                                    Thanh toán trực tuyến
                                </label>
                            </div>
                            <div id="online-payment-info" style="display: none;" class="mt-3">
                                <div class="alert alert-info">
                                    Bạn sẽ được chuyển đến cổng thanh toán sau khi đặt hàng.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="site-btn place-order-btn">Đặt hàng</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<!-- Checkout Section End -->

<script>
// TODO: Load user info và cart items
document.addEventListener('DOMContentLoaded', function() {
    loadUserInfo();
    loadCartSummary();
    loadUserAddresses();
});

function loadUserInfo() {
    // TODO: Load thông tin người dùng từ session/database
    console.log('Loading user info...');
}

function loadCartSummary() {
    // TODO: Load tóm tắt giỏ hàng
    console.log('Loading cart summary...');
}

function loadUserAddresses() {
    // TODO: Load danh sách địa chỉ
    console.log('Loading addresses...');
}

// Toggle new address form
document.getElementById('use-new-address').addEventListener('change', function() {
    var newAddressForm = document.getElementById('new-address-form');
    if (this.checked) {
        newAddressForm.style.display = 'block';
        document.getElementById('address-select').disabled = true;
    } else {
        newAddressForm.style.display = 'none';
        document.getElementById('address-select').disabled = false;
    }
});

// Toggle online payment info
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

// Form validation
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var useNewAddress = document.getElementById('use-new-address').checked;
    var addressSelect = document.getElementById('address-select').value;
    
    if (!useNewAddress && !addressSelect) {
        alert('Vui lòng chọn địa chỉ giao hàng!');
        return;
    }
    
    // TODO: Submit form
    this.submit();
});
</script>

<?php include 'layout/footer.php'; ?>
