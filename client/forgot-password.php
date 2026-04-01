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
                    <h2>Quên mật khẩu</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Quên mật khẩu</span>
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
                    <h2>Quên mật khẩu</h2>
                    <p class="mb-4">Nhập email hoặc tên đăng nhập để tạo link đặt lại mật khẩu.</p>

                    <form id="forgot-password-form">
                        <div class="form-group">
                            <label>Email hoặc tên đăng nhập <span>*</span></label>
                            <input type="text" id="account" name="account" class="form-control" required>
                        </div>
                        <div id="message-container" class="mt-3 mb-3"></div>
                        <button type="submit" class="site-btn w-100">Tạo link khôi phục</button>
                    </form>
                    <div class="switch-login mt-3 text-center">
                        <p>Đã nhớ lại mật khẩu? <a href="login.php">Quay lại đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    var btnSubmit = this.querySelector('button[type="submit"]');
    var originalText = btnSubmit.innerHTML;

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

    fetch('ajax/forgot-password-process.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var message = data.message || 'Đã tạo link đặt lại mật khẩu.';
            if (data.reset_link) {
                message += '<br><a href="' + data.reset_link + '" class="btn btn-sm btn-light mt-3">Mở trang đặt lại mật khẩu</a>';
            }
            showMessage(message, 'success');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        } else {
            showMessage(data.message || 'Không thể xử lý yêu cầu.', 'error');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(function(err) {
        console.error(err);
        showMessage('Có lỗi xảy ra khi tạo link khôi phục!', 'error');
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