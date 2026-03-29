<?php 
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

// 2. Lấy thông tin từ bảng users
$sql_user = "SELECT id, username, email, status, avatar FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if (!$stmt_user) {
    die("SQL error (users): " . $conn->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// 3. Lấy thông tin từ bảng members
$sql_member = "SELECT full_name, phone, height, weight 
               FROM members WHERE users_id = ?";
$stmt_member = $conn->prepare($sql_member);
if (!$stmt_member) {
    die("SQL error (members): " . $conn->error);
}
$stmt_member->bind_param("i", $user_id);
$stmt_member->execute();
$member_data = $stmt_member->get_result()->fetch_assoc();
$stmt_member->close();

// 4. Gom dữ liệu lại thành một mảng chung để dễ dùng trong giao diện
$user = array_merge($user_data ?? [], $member_data ?? []);

include 'layout/header.php'; 

$avatarPath = trim((string)($user['avatar'] ?? ''));
$avatarUrl = '';
if ($avatarPath !== '') {
    $normalizedAvatarPath = ltrim(str_replace('\\', '/', $avatarPath), '/');
    $avatarUrl = '../' . $normalizedAvatarPath;
}
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
.user-avatar { margin-bottom:15px; }
.user-avatar-image,
.user-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #f36100;
    background: #fff;
    object-fit: cover;
    margin: 0 auto;
}
.user-avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f36100;
}
</style>

<!-- Profile Section Begin -->
<section class="profile-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <?php if ($avatarUrl !== ''): ?>
                                <img id="avatar-preview" src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="user-avatar-image">
                            <?php else: ?>
                                <div id="avatar-placeholder" class="user-avatar-placeholder">
                                    <i class="fa fa-user-circle fa-5x"></i>
                                </div>
                                <img id="avatar-preview" src="" alt="Avatar" class="user-avatar-image" style="display:none;">
                            <?php endif; ?>
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
                    <h4>Thông tin tài khoản & cá nhân</h4>
                    <div id="message-container"></div>
                    <form id="profile-form" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Avatar -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Upload avatar</label>
                                    <input type="file" name="avatar" id="avatar-input" class="form-control" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif">
                                    <small class="text-muted d-block mt-2">Chỉ hỗ trợ JPG, PNG, WEBP, GIF. Tối đa 5MB.</small>
                                </div>
                            </div>
                            <!-- Họ tên -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Họ và tên</label>
                                    <input type="text" name="full_name" 
                                        value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <!-- Tên tài khoản -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Tên tài khoản</label>
                                    <input type="text" name="username" 
                                        value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <!-- Email -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" 
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <!-- Số điện thoại -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" 
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <!-- Chiều cao -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Chiều cao (cm)</label>
                                    <input type="text" name="height" 
                                        value="<?php echo htmlspecialchars($user['height'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                            <!-- Cân nặng -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Cân nặng (kg)</label>
                                    <input type="text" name="weight" 
                                        value="<?php echo htmlspecialchars($user['weight'] ?? ''); ?>" 
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                        <!-- Nút duy nhất -->
                        <button type="submit" class="site-btn">Cập nhật</button>
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
// Hàm hiển thị thông báo
function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';

    // Tự động ẩn sau 3 giây
    setTimeout(function() {
        container.innerHTML = '';
    }, 3000);
}

// Load thông tin người dùng từ database
document.addEventListener('DOMContentLoaded', function() {
    fetch('ajax/get-profile.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const u = data.data;
            document.querySelector('[name="full_name"]').value = u.full_name;
            document.querySelector('[name="email"]').value = u.email;
            document.querySelector('[name="phone"]').value = u.phone;
            if (document.querySelector('[name="height"]')) {
                document.querySelector('[name="height"]').value = u.height;
            }
            if (document.querySelector('[name="weight"]')) {
                document.querySelector('[name="weight"]').value = u.weight;
            }
            if (u.avatar_url) {
                const avatarPreview = document.getElementById('avatar-preview');
                const avatarPlaceholder = document.getElementById('avatar-placeholder');
                if (avatarPreview) {
                    avatarPreview.src = u.avatar_url;
                    avatarPreview.style.display = 'inline-block';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
            }
        }
    });

    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const avatarPreview = document.getElementById('avatar-preview');
                const avatarPlaceholder = document.getElementById('avatar-placeholder');
                if (avatarPreview) {
                    avatarPreview.src = event.target.result;
                    avatarPreview.style.display = 'inline-block';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        });
    }
});

// Cập nhật thông tin (tài khoản + cá nhân)
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    fetch('ajax/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showMessage(data.message, data.success ? 'success' : 'error');
        if (data.success && data.avatar_url) {
            const avatarPreview = document.getElementById('avatar-preview');
            const avatarPlaceholder = document.getElementById('avatar-placeholder');
            if (avatarPreview) {
                avatarPreview.src = data.avatar_url + '?v=' + Date.now();
                avatarPreview.style.display = 'inline-block';
            }
            if (avatarPlaceholder) {
                avatarPlaceholder.style.display = 'none';
            }
            const avatarInput = document.getElementById('avatar-input');
            if (avatarInput) {
                avatarInput.value = '';
            }
        }
    })
    .catch(err => {
        console.error(err);
        showMessage('Có lỗi xảy ra khi cập nhật!', 'error');
    });
});

// Đổi mật khẩu
document.getElementById('change-password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    fetch('ajax/change-password.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showMessage(data.message, data.success ? 'success' : 'error');
    })
    .catch(err => {
        console.error(err);
        showMessage('Có lỗi xảy ra khi đổi mật khẩu!', 'error');
    });
});
</script>


<?php include 'layout/footer.php'; ?>