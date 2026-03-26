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

$per_page = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Tổng số địa chỉ để phân trang
$count_sql = "SELECT COUNT(*) AS total FROM addresses WHERE member_id=?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $member_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();

$total_addresses_db = (int) ($count_result['total'] ?? 0);
$total_pages = max(1, (int) ceil($total_addresses_db / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

// Lấy danh sách địa chỉ
$sql = "SELECT * FROM addresses WHERE member_id=? ORDER BY is_default DESC, id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $member_id, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$addresses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_addresses = $total_addresses_db;
$default_addresses = array_values(array_filter($addresses, function ($addr) {
    return !empty($addr['is_default']);
}));
$default_address = $default_addresses[0] ?? null;
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
.addresses-section {
    background: transparent;
    padding-bottom: 40px;
}
.profile-sidebar {
    background: #fff;
    border-radius: 18px;
    padding: 24px 18px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .08);
    position: sticky;
    top: 20px;
    border: 1px solid #e6edf5;
}
.user-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fff2e8, #ffe1c8);
    color: #f36100;
}
.user-avatar i { font-size: 58px; }
.sidebar-item {
    display: flex;
    align-items: center;
    padding: 11px 14px;
    color: #334155;
    border-radius: 12px;
    margin-bottom: 8px;
    text-decoration: none;
    transition: all .2s ease;
    font-weight: 500;
}
.sidebar-item:hover, .sidebar-item.active {
    background: #f36100;
    color: #fff;
    text-decoration: none;
    transform: translateX(2px);
}
.sidebar-item i { margin-right: 10px; width: 18px; text-align: center; }
.address-shell {
    background: #fff;
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .08);
    border: 1px solid #e6edf5;
}
.page-hero {
    background: #fff;
    color: #0f172a;
    border-radius: 18px;
    padding: 26px 28px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    border-left: 4px solid #f36100;
}
.page-hero h4 { color: #0f172a; margin-bottom: 8px; font-weight: 700; }
.page-hero p { margin-bottom: 0; color: #64748b; }
.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px 20px;
    border: 1px solid #edf2f7;
    box-shadow: 0 10px 22px rgba(15, 23, 42, .05);
    display: flex;
    align-items: center;
    gap: 14px;
    height: 100%;
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff4ec;
    color: #f36100;
    font-size: 22px;
    flex-shrink: 0;
}
.stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.stat-value { font-size: 24px; font-weight: 700; color: #0f172a; line-height: 1.2; }
.address-form-card, .address-list-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e6edf5;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.address-card-header {
    padding: 20px 22px 0;
}
.address-card-body {
    padding: 22px;
}
.address-form-card .form-control {
    border-radius: 12px;
    border: 1px solid #d9e2ec;
    min-height: 46px;
}
.address-form-card .form-control:focus {
    border-color: #f36100;
    box-shadow: 0 0 0 3px rgba(243, 97, 0, .12);
}
.address-form-card label {
    font-weight: 600;
    color: #334155;
}
.message-wrap .alert {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, .08);
}
.address-list-item {
    border: 1px solid #e6edf5;
    border-radius: 8px;
    padding: 18px 18px 16px;
    margin-bottom: 14px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.address-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.address-pin {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #fff2e8;
    color: #f36100;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.address-pin i { font-size: 18px; }
.address-title { font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.address-meta { color: #64748b; font-size: 13px; }
.address-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}
.btn-address {
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    border: 0;
}
.btn-address.btn-edit { background: #0ea5e9; color: #fff; }
.btn-address.btn-default { background: #f59e0b; color: #fff; }
.btn-address.btn-delete { background: #ef4444; color: #fff; }
.empty-address {
    text-align: center;
    padding: 36px 20px;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    color: #64748b;
    background: #f8fafc;
}
.form-hint { color: #64748b; font-size: 13px; }
.form-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-soft {
    background: #e2e8f0;
    color: #334155;
    border: 0;
    border-radius: 12px;
}
.btn-soft:hover { background: #cbd5e1; }
.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 18px;
}
.pagination .page-link {
    color: #f36100;
    border-color: #e6edf5;
    box-shadow: none;
}
.pagination .page-item.active .page-link {
    background: #f36100;
    border-color: #f36100;
    color: #fff;
}
.pagination .page-item.disabled .page-link {
    color: #94a3b8;
    background: #fff;
}
@media (max-width: 991px) {
    .profile-sidebar { position: static; margin-bottom: 20px; }
    .address-shell { padding: 20px; }
}
</style>

<section class="addresses-section spad">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <i class="fa fa-user-circle"></i>
                        </div>
                        <h5 class="mt-2 mb-1"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#64748b;font-size:13px;margin-bottom:0;"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                    </div>
                    <hr style="border-color:#edf2f7; margin: 18px 0;">
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
            <div class="col-lg-9">
                <div class="page-hero">
                    <h4>Quản lý địa chỉ giao hàng</h4>
                    <p>Thêm, sửa, chọn địa chỉ mặc định và xóa nhanh ngay trong một màn hình.</p>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-address-book"></i></div>
                            <div>
                                <div class="stat-label">Tổng địa chỉ</div>
                                <div class="stat-value"><?php echo $total_addresses; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-star"></i></div>
                            <div>
                                <div class="stat-label">Địa chỉ mặc định</div>
                                <div class="stat-value"><?php echo $default_address ? '1' : '0'; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-truck"></i></div>
                            <div>
                                <div class="stat-label">Phục vụ giao hàng</div>
                                <div class="stat-value"><?php echo $total_addresses > 0 ? 'Có' : 'Chưa'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 mb-4">
                        <div class="address-form-card h-100">
                            <div class="address-card-header">
                                <h5 class="mb-1" style="font-weight:700;color:#0f172a;">Thêm hoặc sửa địa chỉ</h5>
                                <p class="form-hint mb-0">Điền đầy đủ thông tin để dùng cho đơn hàng và đặt mặc định.</p>
                            </div>
                            <div class="address-card-body">
                                <div id="message-container" class="message-wrap"></div>

                                <form id="address-form">
                                    <input type="hidden" name="id" id="address-id">
                                    <div class="form-group">
                                        <label for="full_address">Địa chỉ chi tiết</label>
                                        <input type="text" name="full_address" id="full_address" class="form-control" placeholder="Số nhà, tên đường, thôn/xóm...">
                                    </div>
                                    <div class="form-group">
                                        <label for="district">Quận/Huyện</label>
                                        <input type="text" name="district" id="district" class="form-control" placeholder="Quận/Huyện">
                                    </div>
                                    <div class="form-group">
                                        <label for="city">Thành phố</label>
                                        <input type="text" name="city" id="city" class="form-control" placeholder="Tỉnh/Thành phố">
                                    </div>
                                    <div class="form-actions mt-4">
                                        <button type="submit" id="submit-btn" class="btn btn-success" style="border-radius:12px;padding:10px 18px;">
                                            <i class="fa fa-save mr-1"></i> Lưu địa chỉ
                                        </button>
                                        <button type="button" id="reset-btn" class="btn btn-soft">
                                            <i class="fa fa-undo mr-1"></i> Làm mới
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="address-list-card">
                            <div class="address-card-header d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1" style="font-weight:700;color:#0f172a;">Danh sách địa chỉ</h5>
                                    <p class="form-hint mb-0">Địa chỉ mặc định sẽ được ưu tiên khi đặt hàng.</p>
                                </div>
                            </div>
                            <div class="address-card-body">
                                <?php if (empty($addresses)): ?>
                                    <div class="empty-address">
                                        <i class="fa fa-map-marker fa-2x mb-3" style="color:#f36100;"></i>
                                        <h6 class="mb-2" style="color:#0f172a; font-weight:700;">Chưa có địa chỉ nào</h6>
                                        <p class="mb-0">Thêm địa chỉ đầu tiên để dùng cho giao hàng và thanh toán.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($addresses as $addr): ?>
                                        <div class="address-list-item">
                                            <div class="address-main">
                                                <div class="address-pin"><i class="fa fa-map-marker"></i></div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                                                        <div class="address-title">#<?php echo $addr['id']; ?> <?php echo htmlspecialchars($addr['full_address']); ?></div>
                                                        <?php if ($addr['is_default']): ?>
                                                            <span class="badge badge-success" style="border-radius:999px;padding:7px 12px;">Mặc định</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="address-meta">
                                                        <?php echo htmlspecialchars($addr['district']); ?>, <?php echo htmlspecialchars($addr['city']); ?>
                                                    </div>
                                                    <div class="address-actions">
                                                        <button class="btn-address btn-edit edit-btn"
                                                                data-id="<?php echo $addr['id']; ?>"
                                                                data-full="<?php echo htmlspecialchars($addr['full_address']); ?>"
                                                                data-district="<?php echo htmlspecialchars($addr['district']); ?>"
                                                                data-city="<?php echo htmlspecialchars($addr['city']); ?>">
                                                            <i class="fa fa-pencil mr-1"></i> Sửa
                                                        </button>
                                                        <button class="btn-address btn-default default-btn"
                                                                data-id="<?php echo $addr['id']; ?>">
                                                            <i class="fa fa-star mr-1"></i> Đặt mặc định
                                                        </button>
                                                        <button class="btn-address btn-delete delete-btn"
                                                                data-id="<?php echo $addr['id']; ?>">
                                                            <i class="fa fa-trash mr-1"></i> Xóa
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($total_pages > 1): ?>
                                        <div class="pagination-wrap">
                                            <nav aria-label="Phân trang địa chỉ">
                                                <ul class="pagination mb-0">
                                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>" aria-label="Trang trước">&laquo;</a>
                                                    </li>
                                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>" aria-label="Trang sau">&raquo;</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' mb-3">' + message + '</div>';

    setTimeout(function() {
        container.innerHTML = '';
    }, 3000);
}

function resetAddressForm() {
    document.getElementById('address-id').value = '';
    document.getElementById('full_address').value = '';
    document.getElementById('district').value = '';
    document.getElementById('city').value = '';
    document.getElementById('submit-btn').innerHTML = '<i class="fa fa-save mr-1"></i> Lưu địa chỉ';
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

document.getElementById('reset-btn').addEventListener('click', function() {
    resetAddressForm();
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
        document.getElementById('submit-btn').innerHTML = '<i class="fa fa-save mr-1"></i> Cập nhật địa chỉ';

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

if (window.location.hash === '#new') {
    resetAddressForm();
}


</script>

<?php include 'layout/footer.php'; ?>
