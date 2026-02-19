<?php include 'layout/header.php'; ?>

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Tài khoản của tôi</h2>
                        <div class="bt-option">
                            <a href="./index.php">Trang chủ</a>
                            <span>Hồ sơ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Profile Section Begin -->
    <section class="profile-section spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="profile-sidebar">
                        <div class="profile-img">
                            <img src="assets/img/profile-default.jpg" alt="Profile">
                            <h5>Nguyễn Văn A</h5>
                            <span>Thành viên VIP</span>
                        </div>
                        <ul class="profile-menu">
                            <li class="active"><a href="#"><i class="fa fa-user"></i> Thông tin cá nhân</a></li>
                            <li><a href="#"><i class="fa fa-calendar"></i> Lịch tập của tôi</a></li>
                            <li><a href="#"><i class="fa fa-credit-card"></i> Gói tập của tôi</a></li>
                            <li><a href="#"><i class="fa fa-shopping-cart"></i> Đơn hàng</a></li>
                            <li><a href="#"><i class="fa fa-lock"></i> Đổi mật khẩu</a></li>
                            <li><a href="./login.php"><i class="fa fa-sign-out"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="profile-content">
                        <h3>Thông tin cá nhân</h3>
                        <form action="#">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Họ và tên</label>
                                        <input type="text" value="Nguyễn Văn A">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" value="nguyenvana@gmail.com">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Số điện thoại</label>
                                        <input type="text" value="0123456789">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Ngày sinh</label>
                                        <input type="date" value="1990-01-01">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Giới tính</label>
                                        <select>
                                            <option value="male">Nam</option>
                                            <option value="female">Nữ</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Chiều cao (cm)</label>
                                        <input type="text" value="175">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Cân nặng (kg)</label>
                                        <input type="text" value="70">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>Địa chỉ</label>
                                        <input type="text" value="123 Đường ABC, Quận 1, TP. HCM">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit" class="site-btn">Cập nhật</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Profile Section End -->

<?php include 'layout/footer.php'; ?>
