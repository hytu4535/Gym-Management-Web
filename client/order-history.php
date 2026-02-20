<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Lấy danh sách đơn hàng của user từ database
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Lịch sử mua hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Lịch sử</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Order History Section Begin -->
<section class="order-history-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <img src="assets/img/avatar/default-avatar.jpg" alt="">
                    </div>
                    <h4>Tên người dùng</h4>
                    <ul class="profile-menu">
                        <li><a href="profile.php">Thông tin cá nhân</a></li>
                        <li><a href="order-history.php" class="active">Lịch sử mua hàng</a></li>
                        <li><a href="addresses.php">Địa chỉ giao hàng</a></li>
                        <li><a href="logout.php">Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="order-history-content">
                    <h4>Lịch sử đơn hàng</h4>
                    
                    <!-- Filter -->
                    <div class="order-filter mb-4">
                        <div class="row">
                            <div class="col-lg-3">
                                <select class="form-control" id="filter-status">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="pending">Chờ xác nhận</option>
                                    <option value="confirmed">Đã xác nhận</option>
                                    <option value="shipping">Đang giao</option>
                                    <option value="completed">Hoàn thành</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <input type="date" class="form-control" id="filter-from-date" placeholder="Từ ngày">
                            </div>
                            <div class="col-lg-3">
                                <input type="date" class="form-control" id="filter-to-date" placeholder="Đến ngày">
                            </div>
                            <div class="col-lg-3">
                                <button class="btn btn-primary btn-block" onclick="filterOrders()">Lọc</button>
                            </div>
                        </div>
                    </div>

                    <div class="order-list">
                        <!-- TODO: Hiển thị danh sách đơn hàng từ database -->
                        <div class="order-item">
                            <div class="order-header">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <p><strong>Mã đơn hàng:</strong> #0001</p>
                                    </div>
                                    <div class="col-lg-4">
                                        <p><strong>Ngày đặt:</strong> 20/02/2026</p>
                                    </div>
                                    <div class="col-lg-4 text-right">
                                        <span class="badge badge-warning">Chờ xác nhận</span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <p><strong>Sản phẩm:</strong> 3 sản phẩm</p>
                                        <p><strong>Tổng tiền:</strong> 1,530,000 VNĐ</p>
                                    </div>
                                    <div class="col-lg-4 text-right">
                                        <a href="order-detail.php?id=1" class="btn btn-sm btn-primary">Xem chi tiết</a>
                                        <a href="invoice.php?order_id=1" class="btn btn-sm btn-secondary">Xem hóa đơn</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- More orders... -->
                    </div>

                    <!-- Pagination -->
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="pagination-wrap">
                                <a href="#" class="active">1</a>
                                <a href="#">2</a>
                                <a href="#">3</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Order History Section End -->

<script>
// TODO: Load danh sách đơn hàng từ database
function loadOrders(page = 1) {
    console.log('Loading orders page:', page);
}

function filterOrders() {
    var status = document.getElementById('filter-status').value;
    var fromDate = document.getElementById('filter-from-date').value;
    var toDate = document.getElementById('filter-to-date').value;
    
    console.log('Filtering orders:', {status, fromDate, toDate});
    // TODO: Implement filter
}

document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
});
</script>

<style>
.order-item {
    border: 1px solid #ddd;
    margin-bottom: 20px;
    border-radius: 5px;
}
.order-header {
    background-color: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #ddd;
}
.order-body {
    padding: 15px;
}
</style>

<?php include 'layout/footer.php'; ?>
