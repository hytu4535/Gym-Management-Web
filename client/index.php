<?php
/**
 * Client Home Page
 * Gym Management System
 */

require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="jumbotron">
            <h1 class="display-4">🏋️ Chào mừng đến với Gym!</h1>
            <p class="lead">Trang dành cho khách hàng đang được phát triển...</p>
            <hr class="my-4">
            <p>Các chức năng sắp ra mắt:</p>
            <ul>
                <li>Đăng ký thành viên</li>
                <li>Xem gói tập</li>
                <li>Đặt lịch với huấn luyện viên</li>
                <li>Mua sản phẩm</li>
            </ul>
            <a class="btn btn-primary btn-lg" href="../index.php" role="button">
                <i class="fas fa-home"></i> Quay lại trang chủ
            </a>
        </div>
    </div>
</body>
</html>
