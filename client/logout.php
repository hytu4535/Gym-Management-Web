<?php
session_start();

// Xóa tất cả session variables
session_unset();

// Destroy session
session_destroy();

// Xóa cookie remember_token nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect về trang đăng nhập
header('Location: login.php?logout=success');
exit();
?>
