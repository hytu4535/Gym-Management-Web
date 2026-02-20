<?php 
session_start();
// TODO: Kiểm tra nếu đã đăng nhập thì redirect về trang chủ
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Đăng nhập</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Đăng nhập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Login Section Begin -->
<section class="login-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 offset-lg-3">
                <div class="login-form">
                    <h2>Đăng nhập</h2>
                    <form id="login-form">
                        <div class="form-group">
                            <label>Tên đăng nhập hoặc Email <span>*</span></label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu <span>*</span></label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <div class="switch-wrap d-flex justify-content-between">
                                <div class="switch-login">
                                    <label class="switch">
                                        <input type="checkbox" id="remember_me" name="remember_me">
                                        <span>Ghi nhớ đăng nhập</span>
                                    </label>
                                </div>
                                <div class="forget-password">
                                    <a href="#">Quên mật khẩu?</a>
                                </div>
                            </div>
                        </div>
                        <div id="message-container"></div>
                        <button type="submit" class="site-btn">Đăng nhập</button>
                    </form>
                    <div class="switch-login">
                        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Login Section End -->

<script>
// TODO: Implement AJAX login
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    fetch('ajax/login-process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect || 'index.php';
            }, 1000);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra!', 'error');
    });
});

function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';
}
</script>

<?php include 'layout/footer.php'; ?>
