<?php 
session_start();
include 'layout/header.php'; 

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=package-register.php');
    exit();
}

// TODO: Lấy thông tin gói tập từ database
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// $sql = "SELECT * FROM packages WHERE package_id = ?";
// Sử dụng prepared statement
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Đăng ký gói tập</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="packages.php">Gói tập</a>
                        <span>Đăng ký</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Package Register Section Begin -->
<section class="package-register-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Thông tin đăng ký gói tập</h4>
                    </div>
                    <div class="card-body">
                        <form id="packageRegisterForm">
                            <input type="hidden" name="package_id" id="package_id" value="<?php echo $package_id; ?>">
                            
                            <!-- Thông tin người đăng ký -->
                            <div class="form-group">
                                <label>Họ tên</label>
                                <input type="text" class="form-control" value="<?php echo $_SESSION['full_name']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" value="<?php echo $_SESSION['email']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" class="form-control" value="<?php echo $_SESSION['phone']; ?>" readonly>
                            </div>
                            
                            <!-- Ngày bắt đầu -->
                            <div class="form-group">
                                <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="form-text text-muted">Gói tập sẽ bắt đầu từ ngày này</small>
                            </div>
                            
                            <!-- Phương thức thanh toán -->
                            <div class="form-group">
                                <label>Phương thức thanh toán <span class="text-danger">*</span></label>
                                <div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="payment_cash" name="payment_method" value="cash" checked class="custom-control-input">
                                        <label class="custom-control-label" for="payment_cash">
                                            <i class="fa fa-money"></i> Tiền mặt tại phòng gym
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="payment_transfer" name="payment_method" value="transfer" class="custom-control-input">
                                        <label class="custom-control-label" for="payment_transfer">
                                            <i class="fa fa-credit-card"></i> Chuyển khoản ngân hàng
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="payment_online" name="payment_method" value="online" class="custom-control-input">
                                        <label class="custom-control-label" for="payment_online">
                                            <i class="fa fa-cc-visa"></i> Thanh toán online (Momo, VNPay)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ghi chú -->
                            <div class="form-group">
                                <label>Ghi chú (Tùy chọn)</label>
                                <textarea class="form-control" name="note" rows="3" placeholder="Nhập ghi chú nếu có..."></textarea>
                            </div>
                            
                            <!-- Điều khoản -->
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="agree_terms" required>
                                    <label class="custom-control-label" for="agree_terms">
                                        Tôi đồng ý với <a href="#" data-toggle="modal" data-target="#termsModal">điều khoản sử dụng</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="primary-btn btn-block">
                                <i class="fa fa-check"></i> Xác nhận đăng ký
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin gói tập -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Thông tin gói tập</h5>
                    </div>
                    <div class="card-body">
                        <!-- TODO: Hiển thị thông tin gói tập từ database -->
                        <h4 id="package_name">Gói 3 Tháng</h4>
                        <hr>
                        
                        <div class="package-info">
                            <p><strong>Thời hạn:</strong> <span id="duration">3 tháng</span></p>
                            <p><strong>Giá:</strong> <span id="price" class="text-danger font-weight-bold">1.350.000đ</span></p>
                        </div>
                        
                        <hr>
                        
                        <h6>Quyền lợi:</h6>
                        <ul id="benefits" class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> Tập luyện tự do</li>
                            <li><i class="fa fa-check text-success"></i> Sử dụng thiết bị không giới hạn</li>
                            <li><i class="fa fa-check text-success"></i> Lớp học nhóm miễn phí</li>
                            <li><i class="fa fa-check text-success"></i> Phòng tắm & tủ đồ</li>
                        </ul>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <small><i class="fa fa-info-circle"></i> Gói tập sẽ tự động kích hoạt sau khi thanh toán thành công</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Package Register Section End -->

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Điều khoản sử dụng</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <h6>1. Quy định chung</h6>
                <p>- Gói tập có hiệu lực từ ngày bắt đầu đã chọn</p>
                <p>- Không hoàn lại tiền khi hủy gói tập</p>
                <p>- Không chuyển nhượng gói tập cho người khác</p>
                
                <h6>2. Quyền và nghĩa vụ</h6>
                <p>- Thành viên phải tuân thủ nội quy phòng gym</p>
                <p>- Phòng gym có quyền hủy gói tập nếu vi phạm nội quy</p>
                
                <h6>3. Chính sách gia hạn</h6>
                <p>- Có thể gia hạn trước khi gói hết hạn 7 ngày</p>
                <p>- Được giảm giá khi gia hạn sớm</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('packageRegisterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!document.getElementById('agree_terms').checked) {
        alert('Vui lòng đồng ý với điều khoản sử dụng!');
        return;
    }
    
    const formData = new FormData(this);
    
    // TODO: Gửi AJAX request đến ajax/package-register-process.php
    fetch('ajax/package-register-process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Đăng ký gói tập thành công!');
            window.location.href = 'my-packages.php';
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại!');
    });
});

// TODO: Load thông tin gói tập từ database khi trang load
window.addEventListener('DOMContentLoaded', function() {
    const packageId = document.getElementById('package_id').value;
    // Gọi AJAX để lấy thông tin gói tập
    // fetch('ajax/get-package-info.php?id=' + packageId)...
});
</script>

<?php include 'layout/footer.php'; ?>
