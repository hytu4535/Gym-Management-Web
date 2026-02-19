<?php include 'layout/header.php'; ?>

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Đăng ký</h2>
                        <div class="bt-option">
                            <a href="./index.php">Trang chủ</a>
                            <span>Đăng ký</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Register Section Begin -->
    <section class="register-section spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="register-form">
                        <h2>Đăng ký thành viên</h2>
                        
                        <div id="alert-container"></div>
                        
                        <form id="register-form">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label for="fullname">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" id="fullname" name="fullname" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" id="username" name="username" required minlength="5">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" id="phone" name="phone" required pattern="[0-9]{10,11}">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="password">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" id="password" name="password" required minlength="6">
                                    <div class="invalid-feedback"></div>
                                    <small class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                </div>
                                <div class="col-lg-6">
                                    <label for="confirm-password">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" id="confirm-password" name="confirm-password" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-12">
                                    <label for="address">Địa chỉ</label>
                                    <input type="text" id="address" name="address" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="dob">Ngày sinh</label>
                                    <input type="date" id="dob" name="dob">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <label for="gender">Giới tính</label>
                                    <select id="gender" name="gender" class="form-control">
                                        <option value="">Chọn giới tính</option>
                                        <option value="male">Nam</option>
                                        <option value="female">Nữ</option>
                                        <option value="other">Khác</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="register-agree">
                                        <label for="agree-check">
                                            Tôi đồng ý với các <a href="#" data-toggle="modal" data-target="#termsModal">Điều khoản & Điều kiện</a>
                                            <input type="checkbox" id="agree-check" required>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <button type="submit" class="register-btn" id="register-btn">
                                        <span id="btn-text">Đăng ký</span>
                                        <span id="btn-spinner" class="d-none">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="switch-login">
                            <span>Đã có tài khoản?</span>
                            <a href="./login.php" class="or-login">Đăng nhập ngay</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Register Section End -->

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Điều khoản & Điều kiện</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>1. Điều khoản chung</h6>
                <p>Bằng việc đăng ký tài khoản, bạn đồng ý tuân thủ các điều khoản và điều kiện sử dụng dịch vụ của chúng tôi.</p>
                
                <h6>2. Quyền và trách nhiệm</h6>
                <p>Người dùng có trách nhiệm bảo mật thông tin tài khoản và chịu trách nhiệm cho mọi hoạt động dưới tên tài khoản của mình.</p>
                
                <h6>3. Chính sách bảo mật</h6>
                <p>Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn và chỉ sử dụng cho mục đích cung cấp dịch vụ.</p>
                
                <h6>4. Chính sách đổi trả</h6>
                <p>Sản phẩm được đổi trả trong vòng 7 ngày kể từ ngày nhận hàng nếu có lỗi từ nhà sản xuất.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.invalid-feedback {
    display: none;
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
}

.invalid-feedback.d-block {
    display: block !important;
}

.register-form input.is-invalid,
.register-form select.is-invalid {
    border-color: #dc3545;
}

.register-form input.is-valid,
.register-form select.is-valid {
    border-color: #28a745;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-dismissible .close {
    position: relative;
    top: -2px;
    right: -21px;
    color: inherit;
}
</style>

<script>
$(document).ready(function() {
    const form = $('#register-form');
    const registerBtn = $('#register-btn');
    const btnText = $('#btn-text');
    const btnSpinner = $('#btn-spinner');
    
    // Form validation
    form.on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.invalid-feedback').removeClass('d-block').text('');
        $('input, select').removeClass('is-invalid is-valid');
        $('#alert-container').empty();
        
        // Validate form
        if (!validateForm()) {
            return false;
        }
        
        // Get form data
        const formData = {
            fullname: $('#fullname').val().trim(),
            email: $('#email').val().trim(),
            username: $('#username').val().trim(),
            phone: $('#phone').val().trim(),
            password: $('#password').val(),
            confirm_password: $('#confirm-password').val(),
            address: $('#address').val().trim(),
            dob: $('#dob').val(),
            gender: $('#gender').val(),
            agree_terms: $('#agree-check').is(':checked')
        };
        
        // Disable button and show spinner
        registerBtn.prop('disabled', true);
        btnText.addClass('d-none');
        btnSpinner.removeClass('d-none');
        
        // Send AJAX request
        $.ajax({
            url: 'ajax/register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    form[0].reset();
                    
                    // Redirect to login after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert('danger', response.message);
                    
                    // Show field errors if any
                    if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            const input = $('#' + field);
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(message).addClass('d-block');
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại!');
                console.error('Registration error:', error);
            },
            complete: function() {
                // Re-enable button and hide spinner
                registerBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                btnSpinner.addClass('d-none');
            }
        });
    });
    
    // Real-time validation
    $('#username').on('blur', function() {
        const username = $(this).val().trim();
        if (username.length >= 5) {
            checkUsernameAvailability(username);
        }
    });
    
    $('#email').on('blur', function() {
        const email = $(this).val().trim();
        if (validateEmail(email)) {
            checkEmailAvailability(email);
        }
    });
    
    $('#confirm-password').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
                $(this).addClass('is-invalid').removeClass('is-valid');
                $(this).next('.invalid-feedback').text('Mật khẩu xác nhận không khớp').addClass('d-block');
            } else {
                $(this).addClass('is-valid').removeClass('is-invalid');
                $(this).next('.invalid-feedback').removeClass('d-block');
            }
        }
    });
    
    $('#phone').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    function validateForm() {
        let isValid = true;
        
        // Validate fullname
        const fullname = $('#fullname').val().trim();
        if (fullname.length < 2) {
            showFieldError('fullname', 'Họ tên phải có ít nhất 2 ký tự');
            isValid = false;
        }
        
        // Validate email
        const email = $('#email').val().trim();
        if (!validateEmail(email)) {
            showFieldError('email', 'Email không hợp lệ');
            isValid = false;
        }
        
        // Validate username
        const username = $('#username').val().trim();
        if (username.length < 5) {
            showFieldError('username', 'Tên đăng nhập phải có ít nhất 5 ký tự');
            isValid = false;
        }
        
        // Validate phone
        const phone = $('#phone').val().trim();
        if (phone.length < 10 || phone.length > 11) {
            showFieldError('phone', 'Số điện thoại phải có 10-11 số');
            isValid = false;
        }
        
        // Validate password
        const password = $('#password').val();
        if (password.length < 6) {
            showFieldError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
            isValid = false;
        }
        
        // Validate confirm password
        const confirmPassword = $('#confirm-password').val();
        if (password !== confirmPassword) {
            showFieldError('confirm-password', 'Mật khẩu xác nhận không khớp');
            isValid = false;
        }
        
        // Validate terms agreement
        if (!$('#agree-check').is(':checked')) {
            showAlert('danger', 'Bạn phải đồng ý với điều khoản và điều kiện');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showFieldError(fieldId, message) {
        const input = $('#' + fieldId);
        input.addClass('is-invalid');
        input.next('.invalid-feedback').text(message).addClass('d-block');
    }
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function checkUsernameAvailability(username) {
        $.ajax({
            url: 'ajax/check-username.php',
            type: 'POST',
            data: { username: username },
            dataType: 'json',
            success: function(response) {
                const input = $('#username');
                if (response.available) {
                    input.addClass('is-valid').removeClass('is-invalid');
                } else {
                    input.addClass('is-invalid').removeClass('is-valid');
                    input.next('.invalid-feedback').text('Tên đăng nhập đã tồn tại').addClass('d-block');
                }
            }
        });
    }
    
    function checkEmailAvailability(email) {
        $.ajax({
            url: 'ajax/check-email.php',
            type: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response) {
                const input = $('#email');
                if (response.available) {
                    input.addClass('is-valid').removeClass('is-invalid');
                } else {
                    input.addClass('is-invalid').removeClass('is-valid');
                    input.next('.invalid-feedback').text('Email đã được sử dụng').addClass('d-block');
                }
            }
        });
    }
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').html(alertHtml);
        
        // Scroll to alert
        $('html, body').animate({
            scrollTop: $('#alert-container').offset().top - 100
        }, 500);
    }
});
</script>

<?php include 'layout/footer.php'; ?>
