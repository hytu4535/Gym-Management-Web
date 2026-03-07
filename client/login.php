<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu đã đăng nhập thì redirect về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

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
                    
                    <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                        <div class="alert alert-success" style="padding: 15px; margin-bottom: 20px; border-radius: 4px; background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: #28a745;">
                            Đăng xuất thành công!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                        <div class="alert alert-success" style="padding: 15px; margin-bottom: 20px; border-radius: 4px; background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: #28a745;">
                            Đăng ký thành công! Vui lòng đăng nhập.
                        </div>
                    <?php endif; ?>
                    
                    <form id="login-form">
                        <div class="form-group">
                            <label>Tên đăng nhập hoặc Email <span>*</span></label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu <span>*</span></label>
                            <input type="password" id="password" name="password" class="form-control" required>
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
                        <div id="message-container" class="mt-3 mb-3"></div>
                        <button type="submit" class="site-btn w-100">Đăng nhập</button>
                    </form>
                    <div class="switch-login mt-3 text-center">
                        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Login Section End -->

<script>
// AJAX login với loading state
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var btnSubmit = this.querySelector('button[type="submit"]');
    var originalText = btnSubmit.innerHTML;
    
    // Disable button và hiển thị loading
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
    
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
            // Enable lại button
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra kết nối với máy chủ! Vui lòng thử lại.', 'error');
        // Enable lại button
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    });
});

function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';
    
    // Tự động ẩn thông báo sau 5 giây
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Tự động ẩn thông báo logout/register sau 5 giây
setTimeout(() => {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(() => {
            alert.remove();
        }, 500);
    });
}, 5000);
</script>

<?php include 'layout/footer.php'; ?>
