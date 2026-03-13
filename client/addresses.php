<?php 
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Load danh sách địa chỉ của user từ database
include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
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
<!-- Breadcrumb Section End -->

<!-- Addresses Section Begin -->
<section class="addresses-section spad">
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
                        <li><a href="order-history.php">Lịch sử mua hàng</a></li>
                        <li><a href="addresses.php" class="active">Địa chỉ giao hàng</a></li>
                        <li><a href="logout.php">Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="addresses-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Địa chỉ của tôi</h4>
                        <button class="site-btn" data-toggle="modal" data-target="#addAddressModal">
                            <i class="fa fa-plus"></i> Thêm địa chỉ mới
                        </button>
                    </div>

                    <div class="row" id="addresses-list">
                        <!-- TODO: Hiển thị danh sách địa chỉ từ database -->
                        <div class="col-lg-6">
                            <div class="address-card">
                                <div class="address-header">
                                    <span class="badge badge-primary">Mặc định</span>
                                    <div class="address-actions">
                                        <button class="btn btn-sm btn-link" onclick="editAddress(1)">
                                            <i class="fa fa-edit"></i> Sửa
                                        </button>
                                        <button class="btn btn-sm btn-link text-danger" onclick="deleteAddress(1)">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </div>
                                </div>
                                <div class="address-body">
                                    <h6>Nguyễn Văn A</h6>
                                    <p class="phone">(+84) 123 456 789</p>
                                    <p class="address">123 Đường ABC, Phường XYZ, Quận 1, TP. Hồ Chí Minh</p>
                                </div>
                                <div class="address-footer">
                                    <button class="btn btn-sm btn-outline-primary" onclick="setDefaultAddress(1)">
                                        Đặt làm mặc định
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- More addresses... -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Addresses Section End -->

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm địa chỉ mới</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-address-form">
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ chi tiết</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Thành phố</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Quận/Huyện</label>
                        <input type="text" name="district" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_default" class="form-check-input" id="is-default">
                            <label class="form-check-label" for="is-default">
                                Đặt làm địa chỉ mặc định
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="addAddress()">Thêm địa chỉ</button>
            </div>
        </div>
    </div>
</div>

<script>
// TODO: Load danh sách địa chỉ
function loadAddresses() {
    console.log('Loading addresses...');
}

// TODO: Thêm địa chỉ mới (sử dụng AJAX)
function addAddress() {
    var form = document.getElementById('add-address-form');
    var formData = new FormData(form);
    
    fetch('ajax/address-add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thêm địa chỉ thành công!');
            $('#addAddressModal').modal('hide');
            loadAddresses();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// TODO: Sửa địa chỉ
function editAddress(id) {
    console.log('Editing address:', id);
}

// TODO: Xóa địa chỉ
function deleteAddress(id) {
    if (confirm('Bạn có chắc muốn xóa địa chỉ này?')) {
        console.log('Deleting address:', id);
    }
}

// TODO: Đặt làm địa chỉ mặc định
function setDefaultAddress(id) {
    console.log('Setting default address:', id);
}

document.addEventListener('DOMContentLoaded', function() {
    loadAddresses();
});
</script>

<style>
.address-card {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}
.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.address-actions button {
    padding: 5px 10px;
}
.address-body h6 {
    margin-bottom: 5px;
}
.address-body .phone {
    color: #666;
    margin-bottom: 10px;
}
.address-body .address {
    color: #333;
}
.address-footer {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}
</style>

<?php include 'layout/footer.php'; ?>
