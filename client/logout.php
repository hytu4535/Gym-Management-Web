<?php
session_start();

// Chỉ xóa trạng thái đăng nhập của client, giữ nguyên session admin nếu có
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['role_id']);
unset($_SESSION['full_name']);
unset($_SESSION['remember_token']);

// Xóa cookie remember_token nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect về trang đăng nhập
header('Location: login.php?logout=success');
exit();
?>
