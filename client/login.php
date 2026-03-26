<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
include 'layout/header.php'; 
?>

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

<section class="login-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 offset-lg-3">
                <div class="login-form">
                    <h2>Đăng nhập</h2>

                    <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                        <div class="alert alert-success">Đăng ký thành công! Vui lòng đăng nhập.</div>
                    <?php endif; ?>

                    <form id="login-form">
                        <div class="form-group">
                            <label>Tên đăng nhập hoặc Email <span>*</span></label>
                            <input type="text" id="username" name="username" class="form-control" >
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu <span>*</span></label>
                            <input type="password" id="password" name="password" class="form-control" >
                        </div>
                        <div style = "padding: left;">
                            <label class="switch">
                                <input type="checkbox" id="remember_me" name="remember_me">
                                <span style="margin-left: 3px; color: white;">Ghi nhớ đăng nhập</span>
                            </label>
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

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var btnSubmit = this.querySelector('button[type="submit"]');
    var originalText = btnSubmit.innerHTML;

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

    fetch('ajax/login-process.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => { window.location.href = data.redirect || 'index.php'; }, 1000);
        } else {
            showMessage(data.message, 'error');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        showMessage('Có lỗi xảy ra kết nối!', 'error');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    });
});

function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';
}
</script>

<?php include 'layout/footer.php'; ?>
