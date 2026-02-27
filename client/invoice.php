<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Lấy order_id từ URL
// TODO: Kiểm tra order có thuộc về user hiện tại không
// TODO: Load thông tin order từ database
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Hóa đơn</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Hóa đơn</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Invoice Section Begin -->
<section class="invoice-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="invoice-container">
                    <div class="invoice-header">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="invoice-logo">
                                    <img src="assets/img/logo.png" alt="">
                                    <h4>Gym Management System</h4>
                                </div>
                            </div>
                            <div class="col-lg-6 text-right">
                                <div class="invoice-number">
                                    <h4>HÓA ĐƠN</h4>
                                    <p>Mã đơn hàng: #<?php echo $_GET['order_id'] ?? '0000'; ?></p>
                                    <p>Ngày: <?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-body">
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <h5>Thông tin khách hàng</h5>
                                <!-- TODO: Hiển thị thông tin khách hàng từ database -->
                                <p><strong>Họ tên:</strong> ...</p>
                                <p><strong>Email:</strong> ...</p>
                                <p><strong>Số điện thoại:</strong> ...</p>
                            </div>
                            <div class="col-lg-6">
                                <h5>Địa chỉ giao hàng</h5>
                                <!-- TODO: Hiển thị địa chỉ giao hàng -->
                                <p>...</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Sản phẩm</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- TODO: Hiển thị các sản phẩm trong đơn hàng -->
                                        <tr>
                                            <td>1</td>
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

                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <h5>Phương thức thanh toán</h5>
                                <!-- TODO: Hiển thị phương thức thanh toán -->
                                <p><strong>Tiền mặt khi nhận hàng (COD)</strong></p>
                                
                                <div class="alert alert-success mt-3">
                                    <i class="fa fa-check-circle"></i> Đơn hàng của bạn đã được đặt thành công! 
                                    Chúng tôi sẽ liên hệ với bạn sớm nhất.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-footer">
                        <div class="row">
                            <div class="col-lg-12 text-center">
                                <button onclick="window.print()" class="site-btn">In hóa đơn</button>
                                <a href="order-history.php" class="site-btn">Xem lịch sử đơn hàng</a>
                                <a href="index.php" class="site-btn">Về trang chủ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Invoice Section End -->

<style>
@media print {
    .breadcrumb-section,
    .footer-section,
    .invoice-footer {
        display: none;
    }
    .invoice-container {
        border: none;
        box-shadow: none;
    }
}
</style>

<?php include 'layout/footer.php'; ?>
