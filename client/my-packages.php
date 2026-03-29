<?php 
session_start();

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my-packages.php');
    exit();
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// 1. Lấy thông tin user (để đồng bộ avatar và email giống trang profile)
$sql_user = "SELECT email, avatar FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$avatarPath = trim((string)($user_data['avatar'] ?? ''));
$avatarUrl = '';
if ($avatarPath !== '') {
    $normalizedAvatarPath = ltrim(str_replace('\\', '/', $avatarPath), '/');
    $avatarUrl = '../' . $normalizedAvatarPath;
}

$packageRecords = [];

// 2. Lấy member_id
$memberStmt = $conn->prepare("SELECT id FROM members WHERE users_id = ? LIMIT 1");
$memberStmt->bind_param("i", $user_id);
$memberStmt->execute();
$member = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

$memberId = (int) ($member['id'] ?? 0);

// 3. Lấy danh sách gói tập
if ($memberId > 0) {
    $packageStmt = $conn->prepare(
        "SELECT mp.id,
                mp.start_date,
                mp.end_date,
                mp.status,
                p.package_name,
                p.price,
                p.duration_months,
                p.description
         FROM member_packages mp
         JOIN membership_packages p ON p.id = mp.package_id
         WHERE mp.member_id = ?
         ORDER BY mp.id DESC"
    );
    $packageStmt->bind_param("i", $memberId);
    $packageStmt->execute();
    $packageResult = $packageStmt->get_result();

    while ($row = $packageResult->fetch_assoc()) {
        $today = new DateTime('today');
        $endDate = new DateTime($row['end_date']);
        $daysRemaining = (int) $today->diff($endDate)->format('%r%a');

        if ($row['status'] === 'active' && $daysRemaining < 0) {
            $row['status'] = 'expired';
        }

        $row['days_remaining'] = $daysRemaining;
        $packageRecords[] = $row;
    }
    $packageStmt->close();
}

include 'layout/header.php';
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Gói tập của tôi</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="profile.php">Hồ sơ</a>
                        <span>Gói tập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
/* Đồng bộ CSS Sidebar từ trang Profile */
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; transition: 0.2s; }
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

/* CSS riêng cho khối Gói tập */
.package-item {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 5px;
    transition: all 0.3s;
    background: #fff;
}
.package-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.package-item.active {
    border-left: 4px solid #28a745;
}
.package-item.expired {
    border-left: 4px solid #6c757d;
    opacity: 0.8;
}
.package-item.cancelled {
    border-left: 4px solid #dc3545;
    opacity: 0.8;
}
</style>

<section class="profile-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <?php if ($avatarUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="user-avatar-image">
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <i class="fa fa-user-circle fa-5x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#888;font-size:13px;"><?php echo htmlspecialchars($user_data['email'] ?? $_SESSION['email'] ?? ''); ?></p>
                    </div>
                    <hr>
                    <div class="sidebar-menu">
                        <a href="profile.php" class="sidebar-item">
                            <i class="fa fa-user"></i> Thông tin cá nhân
                        </a>
                        <a href="my-membership.php" class="sidebar-item">
                            <i class="fa fa-star"></i> Thông tin hội viên
                        </a>
                        <a href="my-packages.php" class="sidebar-item active">
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
                <div class="profile-content" style="background-color: aliceblue; padding: 30px; border-radius: 8px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Danh sách gói tập đã đăng ký</h4>
                        <a href="packages.php" class="site-btn" style="padding: 10px 20px;">
                            <i class="fa fa-plus"></i> Đăng ký gói mới
                        </a>
                    </div>
                    
                    <div class="mb-4">
                        <select id="statusFilter" class="form-control" style="width: 200px; display: inline-block;">
                            <option value="all">Tất cả trạng thái</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="expired">Đã hết hạn</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    
                    <div id="packagesContainer">
                        <?php if (!empty($packageRecords)): ?>
                            <?php foreach ($packageRecords as $package): ?>
                                <?php
                                $statusClass = $package['status'];
                                $statusText = $package['status'] === 'active' ? 'Đang hoạt động' : ($package['status'] === 'expired' ? 'Đã hết hạn' : 'Đã hủy');
                                $badgeClass = $package['status'] === 'active' ? 'success' : ($package['status'] === 'expired' ? 'secondary' : 'danger');
                                ?>
                                <div class="package-item <?php echo $statusClass; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5><?php echo htmlspecialchars($package['package_name']); ?></h5>
                                            <p class="text-muted mb-2 mt-2">
                                                <i class="fa fa-calendar"></i>
                                                Ngày bắt đầu: <strong><?php echo date('d/m/Y', strtotime($package['start_date'])); ?></strong>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fa fa-calendar-check-o"></i>
                                                Ngày hết hạn: <strong><?php echo date('d/m/Y', strtotime($package['end_date'])); ?></strong>
                                            </p>
                                            <p class="mb-2">
                                                <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                                <?php if ($package['status'] === 'active'): ?>
                                                    <span class="text-success ml-2">Còn <?php echo max(0, (int) $package['days_remaining']); ?> ngày</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fa fa-clock-o"></i>
                                                Thời hạn: <strong><?php echo (int) $package['duration_months']; ?> tháng</strong>
                                            </p>
                                            <p class="mb-0">
                                                <i class="fa fa-money"></i>
                                                Giá: <strong class="text-danger"><?php echo number_format((float) $package['price'], 0, ',', '.'); ?>đ</strong>
                                            </p>
                                            <?php if (!empty($package['description'])): ?>
                                                <p class="mt-2 mb-0 text-muted"><?php echo htmlspecialchars($package['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <a href="packages.php" class="site-btn" style="padding: 10px 15px;">
                                                <i class="fa fa-refresh"></i> <?php echo $package['status'] === 'active' ? 'Mua thêm gói' : 'Đăng ký lại'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div id="emptyState" class="text-center py-5" style="background: #fff; border-radius: 8px;">
                                <i class="fa fa-ticket fa-5x text-muted mb-3"></i>
                                <h5>Chưa có gói tập nào</h5>
                                <p class="text-muted">Bạn chưa đăng ký gói tập nào. Hãy chọn gói phù hợp với bạn!</p>
                                <a href="packages.php" class="site-btn">
                                    <i class="fa fa-plus"></i> Đăng ký ngay
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
// Filter packages by status
document.getElementById('statusFilter').addEventListener('change', function() {
    const status = this.value;
    const items = document.querySelectorAll('.package-item');
    
    items.forEach(item => {
        if(status === 'all') {
            item.style.display = 'block';
        } else {
            if(item.classList.contains(status)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        }
    });

    const visibleItems = Array.from(items).some(item => item.style.display !== 'none');
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = visibleItems ? 'none' : 'block';
    }
});
</script>

<?php include 'layout/footer.php'; ?>