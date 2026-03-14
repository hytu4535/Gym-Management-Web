<?php
session_start();

// Xoá toàn bộ session
$_SESSION = [];
session_unset();
session_destroy();

// Chuyển hướng về trang login
header("Location: login.php");
exit();
