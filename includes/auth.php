<?php
// Đảm bảo không có khoảng trắng hoặc dòng trống trước <?php

// Chỉ gọi session_start() nếu chưa có session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra trạng thái đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    // Chuyển hướng về trang login nếu chưa đăng nhập
    header("Location: ../admin/login.php");
    exit();
}
