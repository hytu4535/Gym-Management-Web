<?php
session_start();

// TODO: Implement logout logic
// Xóa tất cả session variables
session_unset();

// Destroy session
session_destroy();

// Redirect về trang chủ
header('Location: index.php');
exit();
?>
