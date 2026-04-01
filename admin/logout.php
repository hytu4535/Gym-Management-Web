<?php
session_start();

// Chỉ xoá trạng thái đăng nhập của admin, giữ nguyên session client nếu có
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_user_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['role']);
unset($_SESSION['role_id']);
unset($_SESSION['is_admin_role']);
unset($_SESSION['permissions']);
unset($_SESSION['user_action_permissions']);

// Chuyển hướng về trang login
header("Location: login.php");
exit();
