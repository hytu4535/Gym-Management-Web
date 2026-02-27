<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Lấy order_id từ URL
// TODO: Kiểm tra order có thuộc về user không
// TODO: Load chi tiết order từ database
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Chi tiết đơn hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="order-history.php">Lịch sử</a>
                        <span>Chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Order Detail Section Begin -->
<section class="order-detail-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="order-detail-container">
                    <div class="order-detail-header">
                        <div class="row">
                            <div class="col-lg-6">
                                <h4>Đơn hàng #<?php echo $_GET['id'] ?? '0000'; ?></h4>
                                <p>Ngày đặt: <?php echo date('d/m/Y H:i'); ?></p>
                            </div>
                            <div class="col-lg-6 text-right">
                                <span class="badge badge-warning">Chờ xác nhận</span>
                            </div>
                        </div>
                    </div>

                    <div class="order-detail-body">
                        <!-- Order Status Timeline -->
                        <div class="order-timeline mb-4">
                            <h5>Trạng thái đơn hàng</h5>
                            <div class="timeline">
                                <!-- TODO: Hiển thị timeline trạng thái đơn hàng -->
                                <div class="timeline-item active">
                                    <div class="timeline-icon"><i class="fa fa-check"></i></div>
                                    <div class="timeline-content">
                                        <h6>Đơn hàng đã đặt</h6>
                                        <p>20/02/2026 10:30</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-icon"><i class="fa fa-circle-o"></i></div>
                                    <div class="timeline-content">
                                        <h6>Đã xác nhận</h6>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-icon"><i class="fa fa-circle-o"></i></div>
                                    <div class="timeline-content">
                                        <h6>Đang giao hàng</h6>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-icon"><i class="fa fa-circle-o"></i></div>
                                    <div class="timeline-content">
                                        <h6>Đã giao</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Info -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <h5>Thông tin khách hàng</h5>
                                <!-- TODO: Hiển thị thông tin khách hàng -->
                                <p><strong>Họ tên:</strong> ...</p>
                                <p><strong>Email:</strong> ...</p>
                                <p><strong>Số điện thoại:</strong> ...</p>
                            </div>
                            <div class="col-lg-6">
                                <h5>Địa chỉ giao hàng</h5>
                                <!-- TODO: Hiển thị địa chỉ -->
                                <p>...</p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="row">
                            <div class="col-lg-12">
                                <h5>Sản phẩm</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Hình ảnh</th>
                                            <th>Sản phẩm</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- TODO: Hiển thị các sản phẩm trong đơn hàng -->
                                        <tr>
                                            <td><img src="assets/img/products/product-1.jpg" width="60" alt=""></td>
                                            <td>Tên sản phẩm</td>
                                            <td>500,000 VNĐ</td>
                                            <td>1</td>
                                            <td>500,000 VNĐ</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Tạm tính:</strong></td>
                                            <td><strong>500,000 VNĐ</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Phí vận chuyển:</strong></td>
                                            <td><strong>30,000 VNĐ</strong></td>
                                        </tr>
                                        <tr class="total-row">
                                            <td colspan="4" class="text-right"><strong>Tổng cộng:</strong></td>
                                            <td><strong>530,000 VNĐ</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <h5>Phương thức thanh toán</h5>
                                <p><strong>Tiền mặt khi nhận hàng (COD)</strong></p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="row mt-4">
                            <div class="col-lg-12 text-center">
                                <a href="invoice.php?order_id=<?php echo $_GET['id'] ?? 0; ?>" class="site-btn">Xem hóa đơn</a>
                                <a href="order-history.php" class="site-btn">Quay lại</a>
                                <!-- TODO: Nút hủy đơn nếu status = pending -->
                                <button class="site-btn btn-danger" onclick="cancelOrder()">Hủy đơn hàng</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Order Detail Section End -->

<script>
function cancelOrder() {
    if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
        // TODO: AJAX call to cancel order
        console.log('Cancelling order...');
    }
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 50px;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -34px;
    top: 20px;
    width: 2px;
    height: 100%;
    background: #ddd;
}
.timeline-item:last-child:before {
    display: none;
}
.timeline-icon {
    position: absolute;
    left: -45px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
}
.timeline-item.active .timeline-icon {
    background: #f36100;
    color: white;
}
</style>

<?php include 'layout/footer.php'; ?>
