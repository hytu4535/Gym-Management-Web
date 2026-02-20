<?php 
session_start();
include 'layout/header.php'; 

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my-packages.php');
    exit();
}

// TODO: Lấy danh sách gói tập đã đăng ký từ database
/*
$sql = "SELECT mp.*, p.package_name, p.price, p.duration, p.description
        FROM member_packages mp
        JOIN packages p ON mp.package_id = p.package_id
        WHERE mp.member_id = ?
        ORDER BY mp.created_at DESC";
*/
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Gói tập của tôi</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="profile.php">Tài khoản</a>
                        <span>Gói tập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- My Packages Section Begin -->
<section class="my-packages-section spad">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <i class="fa fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="mt-3"><?php echo $_SESSION['full_name']; ?></h5>
                        <p class="text-muted"><?php echo $_SESSION['email']; ?></p>
                    </div>
                    <hr>
                    <div class="sidebar-menu">
                        <a href="profile.php" class="sidebar-item">
                            <i class="fa fa-user"></i> Thông tin cá nhân
                        </a>
                        <a href="my-packages.php" class="sidebar-item active">
                            <i class="fa fa-ticket"></i> Gói tập của tôi
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
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h4>Danh sách gói tập đã đăng ký</h4>
                        <a href="packages.php" class="btn btn-primary btn-sm float-right">
                            <i class="fa fa-plus"></i> Đăng ký gói mới
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <div class="mb-3">
                            <select id="statusFilter" class="form-control" style="width: 200px; display: inline-block;">
                                <option value="all">Tất cả trạng thái</option>
                                <option value="active">Đang hoạt động</option>
                                <option value="expired">Đã hết hạn</option>
                                <option value="pending">Chờ thanh toán</option>
                            </select>
                        </div>
                        
                        <div id="packagesContainer">
                            <!-- TODO: Load từ database, đây là dữ liệu mẫu -->
                            
                            <!-- Package Item 1 - Active -->
                            <div class="package-item active">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5>Gói 3 Tháng</h5>
                                        <p class="text-muted mb-2">
                                            <i class="fa fa-calendar"></i> 
                                            Ngày bắt đầu: <strong>01/02/2026</strong>
                                        </p>
                                        <p class="text-muted mb-2">
                                            <i class="fa fa-calendar-check-o"></i> 
                                            Ngày hết hạn: <strong>01/05/2026</strong>
                                        </p>
                                        <p class="mb-2">
                                            <span class="badge badge-success">Đang hoạt động</span>
                                            <span class="text-success ml-2">Còn 70 ngày</span>
                                        </p>
                                        <p class="mb-0">
                                            <i class="fa fa-credit-card"></i> 
                                            Thanh toán: <strong>Tiền mặt</strong> | 
                                            <i class="fa fa-money"></i> 
                                            Giá: <strong class="text-danger">1.350.000đ</strong>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button class="btn btn-info btn-sm" onclick="viewPackageDetail(1)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button class="btn btn-success btn-sm" onclick="renewPackage(1)">
                                            <i class="fa fa-refresh"></i> Gia hạn
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Package Item 2 - Expired -->
                            <div class="package-item expired">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5>Gói 1 Tháng</h5>
                                        <p class="text-muted mb-2">
                                            <i class="fa fa-calendar"></i> 
                                            Ngày bắt đầu: <strong>01/01/2026</strong>
                                        </p>
                                        <p class="text-muted mb-2">
                                            <i class="fa fa-calendar-check-o"></i> 
                                            Ngày hết hạn: <strong>01/02/2026</strong>
                                        </p>
                                        <p class="mb-2">
                                            <span class="badge badge-secondary">Đã hết hạn</span>
                                        </p>
                                        <p class="mb-0">
                                            <i class="fa fa-credit-card"></i> 
                                            Thanh toán: <strong>Chuyển khoản</strong> | 
                                            <i class="fa fa-money"></i> 
                                            Giá: <strong class="text-danger">500.000đ</strong>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button class="btn btn-info btn-sm" onclick="viewPackageDetail(2)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button class="btn btn-success btn-sm" onclick="renewPackage(2)">
                                            <i class="fa fa-refresh"></i> Đăng ký lại
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Empty State -->
                            <div id="emptyState" style="display: none;" class="text-center py-5">
                                <i class="fa fa-ticket fa-5x text-muted mb-3"></i>
                                <h5>Chưa có gói tập nào</h5>
                                <p class="text-muted">Bạn chưa đăng ký gói tập nào. Hãy chọn gói phù hợp với bạn!</p>
                                <a href="packages.php" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Đăng ký ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- My Packages Section End -->

<style>
.package-item {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 5px;
    transition: all 0.3s;
}
.package-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.package-item.active {
    border-left: 4px solid #28a745;
}
.package-item.expired {
    border-left: 4px solid #6c757d;
    opacity: 0.7;
}
.profile-sidebar {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.sidebar-item {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 5px;
}
.sidebar-item:hover,
.sidebar-item.active {
    background: #f4f4f4;
    color: #f36100;
}
</style>

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
});

function viewPackageDetail(id) {
    // TODO: Hiển thị modal chi tiết hoặc redirect
    alert('Xem chi tiết gói tập #' + id);
}

function renewPackage(id) {
    // TODO: Chuyển đến trang gia hạn/đăng ký lại
    if(confirm('Bạn muốn gia hạn/đăng ký lại gói tập này?')) {
        window.location.href = 'package-register.php?id=' + id;
    }
}

// TODO: Load packages from database via AJAX
function loadMyPackages() {
    // fetch('ajax/get-my-packages.php')
    //     .then(response => response.json())
    //     .then(data => {
    //         // Render packages
    //     });
}
</script>

<?php include 'layout/footer.php'; ?>
