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
                    <form id="register-form" novalidate>
                        <div class="form-group">
                            <label>Họ và tên <span>*</span></label>
                            <input type="text" id="full_name" name="full_name" required>
                            <small class="field-error" id="full_name_error"></small>
                        </div>
                        <div class="form-group">
                            <label>Email <span>*</span></label>
                            <input type="email" id="email" name="email" required>
                            <small class="field-error" id="email_error"></small>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại <span>*</span></label>
                            <input type="text" id="phone" name="phone" required>
                            <small class="field-error" id="phone_error"></small>
                        </div>
                        <div class="form-group">
                            <label>Tên đăng nhập <span>*</span></label>
                            <input type="text" id="username" name="username" required>
                            <small class="field-error" id="username_error"></small>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu <span>*</span></label>
                            <input type="password" id="password" name="password" required>
                            <small class="field-error" id="password_error"></small>
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu <span>*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <small class="field-error" id="confirm_password_error"></small>
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
const registerForm = document.getElementById('register-form');
const fieldIds = ['full_name', 'email', 'phone', 'username', 'password', 'confirm_password'];

function setFieldError(fieldId, message) {
    const errorEl = document.getElementById(fieldId + '_error');
    const inputEl = document.getElementById(fieldId);

    if (errorEl) {
        errorEl.textContent = message || '';
        errorEl.style.display = message ? 'block' : 'none';
    }
    if (inputEl) {
        inputEl.classList.toggle('is-invalid', !!message);
    }
}

function clearFieldErrors() {
    fieldIds.forEach(function(fieldId) {
        setFieldError(fieldId, '');
    });
}

function validateRegisterForm() {
    clearFieldErrors();

    var fullName = document.getElementById('full_name').value.trim();
    var email = document.getElementById('email').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;

    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var phoneRegex = /^(?:\+84\d{9}|0\d{9,10})$/;

    var isValid = true;

    if (fullName.length < 2) {
        setFieldError('full_name', 'Họ và tên phải có ít nhất 2 ký tự.');
        isValid = false;
    }

    if (!emailRegex.test(email)) {
        setFieldError('email', 'Email không đúng định dạng.');
        isValid = false;
    }

    if (!phoneRegex.test(phone)) {
        setFieldError('phone', 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và có 10-11 số.');
        isValid = false;
    }

    if (username.length < 3) {
        setFieldError('username', 'Tên đăng nhập phải có ít nhất 3 ký tự.');
        isValid = false;
    }

    if (password.length < 6) {
        setFieldError('password', 'Mật khẩu phải có ít nhất 6 ký tự.');
        isValid = false;
    }

    if (password !== confirmPassword) {
        setFieldError('confirm_password', 'Mật khẩu không khớp.');
        isValid = false;
    }

    return isValid;
}

registerForm.addEventListener('submit', function(e) {
    e.preventDefault();
    clearFieldErrors();

    if (!validateRegisterForm()) {
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
            if (data.errors) {
                Object.keys(data.errors).forEach(function(fieldId) {
                    setFieldError(fieldId, data.errors[fieldId]);
                });
            }
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

<style>
.field-error {
    display: none;
    color: #dc3545;
    margin-top: 6px;
    font-size: 13px;
    font-weight: 500;
}

input.is-invalid {
    border-color: #dc3545;
    outline: none;
}
</style>

<?php include 'layout/footer.php'; ?>