<?php 
session_start();
// TODO: Kiểm tra đăng nhập, nếu chưa đăng nhập thì redirect về login.php
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

// TODO: Lấy thông tin user từ database
    require_once __DIR__ . '/../config/db.php';


    $user_id = $_SESSION['user_id'];
    $sql = "SELECT u.email, m.full_name, m.phone, m.address
            FROM members m
            JOIN users u ON m.users_id = u.id
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt){
        die("SQL error: ". $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

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

<style>
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; }
.sidebar-item:hover, .sidebar-item.active { background:#f36100; color:#fff; text-decoration:none; }
.sidebar-item i { margin-right:8px; width:16px; }
.profile-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:20px; }
.user-avatar { color:#f36100; margin-bottom:15px; }
</style>

<!-- Profile Section Begin -->
<section class="profile-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <i class="fa fa-user-circle fa-5x" style="color:#f36100;"></i>
                        </div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#888;font-size:13px;"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                    </div>
                    <hr>
                    <div class="sidebar-menu">
                        <a href="profile.php" class="sidebar-item active">
                            <i class="fa fa-user"></i> Thông tin cá nhân
                        </a>
                        <a href="my-membership.php" class="sidebar-item">
                            <i class="fa fa-star"></i> Thông tin hội viên
                        </a>
                        <a href="my-packages.php" class="sidebar-item">
                            <i class="fa fa-ticket"></i> Gói tập của tôi
                        </a>
                        <a href="my-schedules.php" class="sidebar-item">
                            <i class="fa fa-calendar"></i> Lịch tập của tôi
                        </a>
                        <a href="order-history.php" class="sidebar-item">
                            <i class="fa fa-shopping-bag"></i> Lịch sử mua hàng
                        </a>
                        <a href="addresses.php" class="sidebar-item">
                            <i class="fa fa-map-marker"></i> Địa chỉ
                        </a>
                        <a href="logout.php" class="sidebar-item text-danger">
                            <i class="fa fa-sign-out"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="profile-content" style="background-color: aliceblue; padding: 30px">
                    <h4>Thông tin tài khoản</h4>
                    <form id="profile-form">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Họ và tên</label>
                                    <input type="text" name="full_name" 
                                        value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" 
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" 
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input type="date" name="birth_date" 
                                        value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Giới tính</label>
                                    <select name="gender" class="form-control">
                                        <option value="Male" <?php if(($user['gender'] ?? '')=='Male') echo 'selected'; ?>>Nam</option>
                                        <option value="Female" <?php if(($user['gender'] ?? '')=='Female') echo 'selected'; ?>>Nữ</option>
                                        <option value="Other" <?php if(($user['gender'] ?? '')=='Other') echo 'selected'; ?>>Khác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>Địa chỉ</label>
                                    <textarea name="address" rows="3" class="form-control"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
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
    document.addEventListener('DOMContentLoaded', function() {
        fetch('ajax/get-profile.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const u = data.data;
                document.querySelector('[name="full_name"]').value = u.full_name;
                document.querySelector('[name="email"]').value = u.email;
                document.querySelector('[name="phone"]').value = u.phone;
                document.querySelector('[name="address"]').value = u.address;
            }
        });
    });

// TODO: Implement cập nhật thông tin
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('ajax/update-profile.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(txt => {
            console.log(txt); // xem nội dung thực tế
            try {
                const data = JSON.parse(txt);
                alert(data.message);
                if (data.success) location.reload();
            } catch (e) {
                alert("Server không trả về JSON: " + txt);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi cập nhật!');
        });

    });
// TODO: Implement đổi mật khẩu
    document.getElementById('change-password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('ajax/change-password.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
        })
        .catch(err => {
            alert('Có lỗi xảy ra!');
            console.error(err);
        });
    });

</script>

<?php include 'layout/footer.php'; ?>
