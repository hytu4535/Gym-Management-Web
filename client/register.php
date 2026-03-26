<?php include 'layout/header.php'; ?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Đăng ký</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Đăng ký</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="register-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 offset-lg-3">
                <div class="register-form">
                    <h2>Đăng ký tài khoản</h2>
                    <form id="register-form">
                        <div class="form-group">
                            <label>Họ và tên <span>*</span></label>
                            <input type="text" id="full_name" name="full_name" >
                        </div>
                        <div class="form-group">
                            <label>Email <span>*</span></label>
                            <input id="email" name="email" >
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại <span>*</span></label>
                            <input type="text" id="phone" name="phone" >
                        </div>
                        <div class="form-group">
                            <label>Tên đăng nhập <span>*</span></label>
                            <input type="text" id="username" name="username" >
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu <span>*</span></label>
                            <input type="password" id="password" name="password" >
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu <span>*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" >
                        </div>
                        <div id="message-container"></div>
                        <button type="submit" class="site-btn">Đăng ký</button>
                    </form>
                    <div class="switch-login">
                        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var email = document.getElementById('email').value;
    var phone = document.getElementById('phone').value;
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;

    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var phoneRegex = /^[0-9]{10}$/;

    if (password !== confirmPassword) {
        showMessage('Mật khẩu không khớp!', 'error');
        return;
    }

    var formData = new FormData(this);
    fetch('ajax/register-process.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => { window.location.href = 'login.php?registered=success'; }, 2000);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showMessage('Có lỗi xảy ra!', 'error');
    });
});

function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';
}
</script>

<?php include 'layout/footer.php'; ?>
