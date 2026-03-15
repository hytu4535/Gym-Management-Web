<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
include 'layout/header.php'; 
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

<section class="addresses-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <!-- sidebar -->
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

                    <div class="row" id="addresses-list" style="background-color: antiquewhite;">
                        <?php
                        $member_id = $_SESSION['user_id']; // giả sử user_id = member_id
                        $stmt = $conn->prepare("SELECT id, full_address, city, district, is_default 
                                                FROM addresses WHERE member_id=?");
                        if(!$stmt){ die("SQL error: ".$conn->error); }
                        $stmt->bind_param("i", $member_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <div class="col-lg-6">
                                <div class="address-card" style="background-color: white; margin: 15px; border: 2px solid orange">
                                    <div class="address-header">
                                        <?php if ($row['is_default']) { ?>
                                            <span class="badge badge-primary">Mặc định</span>
                                        <?php } ?>
                                        <div class="address-actions">
                                            <button class="btn btn-sm btn-link" onclick="editAddress(<?php echo $row['id']; ?>)">
                                                <i class="fa fa-edit"></i> Sửa
                                            </button>
                                            <button class="btn btn-sm btn-link text-danger" onclick="deleteAddress(<?php echo $row['id']; ?>)">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </div>
                                    </div>
                                    <div class="address-body">
                                        <p class="address">
                                            <?php echo htmlspecialchars($row['full_address'] . ', ' . $row['district'] . ', ' . $row['city']); ?>
                                        </p>
                                    </div>
                                    <div class="address-footer">
                                        <button class="btn btn-sm btn-outline-primary" onclick="setDefaultAddress(<?php echo $row['id']; ?>)">
                                            Đặt làm mặc định
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
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
    <!-- Modal thêm địa chỉ giữ nguyên như bạn đã viết -->
</script>

<?php include 'layout/footer.php'; ?>
