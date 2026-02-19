<?php include 'layout/header.php'; ?>

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Đăng nhập</h2>
                        <div class="bt-option">
                            <a href="./index.php">Trang chủ</a>
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
                        <form action="#">
                            <div class="input-group">
                                <label for="username">Tên đăng nhập hoặc Email</label>
                                <input type="text" id="username" name="username">
                            </div>
                            <div class="input-group">
                                <label for="password">Mật khẩu</label>
                                <input type="password" id="password" name="password">
                            </div>
                            <div class="group-input gi-check">
                                <div class="gi-more">
                                    <label for="save-pass">
                                        Ghi nhớ đăng nhập
                                        <input type="checkbox" id="save-pass">
                                        <span class="checkmark"></span>
                                    </label>
                                    <a href="#" class="forget-pass">Quên mật khẩu?</a>
                                </div>
                            </div>
                            <button type="submit" class="site-btn login-btn">Đăng nhập</button>
                        </form>
                        <div class="switch-login">
                            <span>Chưa có tài khoản?</span>
                            <a href="./register.php" class="or-login">Đăng ký ngay</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Login Section End -->

<?php include 'layout/footer.php'; ?>
