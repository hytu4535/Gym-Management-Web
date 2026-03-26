<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
include 'layout/header.php'; 

$user_id = $_SESSION['user_id'];

// Lấy member_id từ bảng members
$stmt = $conn->prepare("SELECT id FROM members WHERE users_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

$member_id = $member['id'];

// Lấy danh sách địa chỉ
$sql = "SELECT * FROM addresses WHERE member_id=? ORDER BY is_default DESC, id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$addresses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Địa chỉ giao hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Địa chỉ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; }
.sidebar-item:hover, .sidebar-item.active { background:#f36100; color:#fff; text-decoration:none; }
.sidebar-item i { margin-right:8px; width:16px; }
.profile-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:20px; }
.user-avatar { color:#f36100; margin-bottom:15px; }
</style>

<section class="addresses-section spad">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
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
                        <a href="profile.php" class="sidebar-item"><i class="fa fa-user"></i> Thông tin cá nhân</a>
                        <a href="my-membership.php" class="sidebar-item"><i class="fa fa-star"></i> Thông tin hội viên</a>
                        <a href="my-packages.php" class="sidebar-item"><i class="fa fa-ticket"></i> Gói tập của tôi</a>
                        <a href="my-schedules.php" class="sidebar-item"><i class="fa fa-calendar"></i> Lịch tập của tôi</a>
                        <a href="order-history.php" class="sidebar-item"><i class="fa fa-shopping-bag"></i> Lịch sử mua hàng</a>
                        <a href="addresses.php" class="sidebar-item active"><i class="fa fa-map-marker"></i> Địa chỉ</a>
                        <a href="logout.php" class="sidebar-item text-danger"><i class="fa fa-sign-out"></i> Đăng xuất</a>
                    </div>
                </div>
            </div>

            <!-- Nội dung quản lý địa chỉ -->
            <div class="col-lg-9" style="background-color: aliceblue; padding: 30px; border-radius: 10px;">
                <h4>Quản lý địa chỉ</h4>
                <br>

                <!-- Form thêm địa chỉ -->
                <form id="address-form" class="mb-4" style="background-color: #00e3f3; padding: 20px; border-radius: 10px;">
                    <input type="hidden" name="id" id="address-id">
                    <div class="mb-3">
        
                        <label>Địa chỉ mới</label>
                        <input type="text" name="full_address" id="full_address" class="form-control" >
                    </div>
                    <div class="mb-3">
                        <label>Quận/Huyện</label>
                        <input type="text" name="district" id="district" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Thành phố</label>
                        <input type="text" name="city" id="city" class="form-control">
                    </div>
                    <button type="submit" id="submit-btn" class="btn btn-success">Lưu địa chỉ mới</button>
                </form>

                <div id="message-container"></div>

                <!-- Danh sách địa chỉ -->
                <h4 style ="margin-bottom: 20px;">Danh sách địa chỉ</h4>
                <?php if (empty($addresses)): ?>
                    <p>Chưa có địa chỉ nào được lưu.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($addresses as $addr): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span >
                                    <strong style="margin-right: 10px;">#<?php echo $addr['id']; ?> | </strong> 
                                    <?php echo htmlspecialchars($addr['full_address']); ?>,
                                    <?php echo htmlspecialchars($addr['district']); ?>,
                                    <?php echo htmlspecialchars($addr['city']); ?>
                                </span>
                                <?php if ($addr['is_default']): ?>
                                    <span class="badge bg-success">Mặc định</span>
                                <?php endif; ?>
                               <div>
                                    <button class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?php echo $addr['id']; ?>"
                                            data-full="<?php echo htmlspecialchars($addr['full_address']); ?>"
                                            data-district="<?php echo htmlspecialchars($addr['district']); ?>"
                                            data-city="<?php echo htmlspecialchars($addr['city']); ?>">
                                        Sửa
                                    </button>
                                    <button class="btn btn-sm btn-warning default-btn"
                                            data-id="<?php echo $addr['id']; ?>">
                                        Đặt mặc định
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn"
                                            data-id="<?php echo $addr['id']; ?>">
                                        Xóa
                                    </button>
                                </div>
                            </li>

                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

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

document.getElementById('address-form').addEventListener('submit', function(e){
    e.preventDefault();

    let full = document.getElementById('full_address').value.trim();
    let district = document.getElementById('district').value.trim();
    let city = document.getElementById('city').value.trim();

    // Kiểm tra dữ liệu đầu vào
    if (!full || !district || !city) {
        showMessage("Vui lòng nhập đầy đủ thông tin địa chỉ!", "error");
        return;
    }

    let formData = new FormData(this);
    fetch('ajax/address-actions.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
      .then(data => {
        showMessage(data.message, data.success ? 'success' : 'error');
        if(data.success) {
            setTimeout(() => location.reload(), 1500);
        }
      });
});


// Nút sửa: load dữ liệu vào form
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        // Đổ dữ liệu vào form
        document.getElementById('address-id').value = this.dataset.id;
        document.getElementById('full_address').value = this.dataset.full;
        document.getElementById('district').value = this.dataset.district;
        document.getElementById('city').value = this.dataset.city;

        // Đổi nút submit thành "Cập nhật địa chỉ"
        document.getElementById('submit-btn').textContent = "Cập nhật địa chỉ";

        // Hiển thị thông báo cho người dùng
        showMessage("Bạn đang chỉnh sửa địa chỉ #" + this.dataset.id, "success");
    });
});

// Nút đặt mặc định
document.querySelectorAll('.default-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        let formData = new FormData();
        formData.append('set_default', this.dataset.id);
        fetch('ajax/address-actions.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
          .then(data => {
            showMessage(data.message, data.success ? 'success' : 'error');
            if(data.success) {
                setTimeout(() => location.reload(), 1500);
            }
          });
    });
});

// Nút xóa
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        if (!confirm("Bạn có chắc muốn xóa địa chỉ #" + this.dataset.id + " không?")) return;

        let formData = new FormData();
        formData.append('delete_id', this.dataset.id);

        fetch('ajax/address-actions.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
          .then(data => {
            showMessage(data.message, data.success ? 'success' : 'error');
            if(data.success) {
                setTimeout(() => location.reload(), 1500);
            }
          });
    });
});


</script>

<?php include 'layout/footer.php'; ?>
