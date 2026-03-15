<?php
// Cấu hình kết nối MySQL
$servername = "localhost";
$username   = "root";
$password   = "14092005"; // XAMPP mặc định: root không có mật khẩu
$dbname     = "gym-management-web"; // thay bằng tên database bạn đã tạo

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Bật hiển thị lỗi để dễ debug trong quá trình phát triển
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
