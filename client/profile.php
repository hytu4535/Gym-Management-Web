<?php 
session_start();
// TODO: Kiểm tra đăng nhập, nếu chưa đăng nhập thì redirect về login.php
// TODO: Lấy thông tin user từ database
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Thông tin cá nhân</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
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
                    <div class="profile-avatar">
                        <!-- TODO: Hiển thị ảnh đại diện -->
                        <img src="assets/img/avatar/default-avatar.jpg" alt="">
                    </div>
                    <h4>Tên người dùng</h4>
                    <ul class="profile-menu">
                        <li><a href="profile.php" class="active">Thông tin cá nhân</a></li>
                        <li><a href="order-history.php">Lịch sử mua hàng</a></li>
                        <li><a href="addresses.php">Địa chỉ giao hàng</a></li>
                        <li><a href="logout.php">Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="profile-content">
                    <h4>Thông tin tài khoản</h4>
                    <form id="profile-form">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Họ và tên</label>
                                    <input type="text" name="full_name" value="" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" value="" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input type="date" name="birth_date" value="" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Giới tính</label>
                                    <select name="gender" class="form-control">
                                        <option value="Male">Nam</option>
                                        <option value="Female">Nữ</option>
                                        <option value="Other">Khác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>Địa chỉ</label>
                                    <textarea name="address" rows="3" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="site-btn">Cập nhật</button>
                            </div>
                        </div>
                    </form>

                    <hr class="my-5">

                    <h4>Đổi mật khẩu</h4>
                    <form id="change-password-form">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Mật khẩu hiện tại</label>
                                    <input type="password" name="current_password" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6"></div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Xác nhận mật khẩu mới</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="site-btn">Đổi mật khẩu</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Profile Section End -->

<script>
// TODO: Load thông tin người dùng từ database
// TODO: Implement cập nhật thông tin
// TODO: Implement đổi mật khẩu
</script>

<?php include 'layout/footer.php'; ?>
