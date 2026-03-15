<?php
function checkPermission($permCode) {
    // Nếu chưa đăng nhập hoặc chưa có danh sách quyền
    if (!isset($_SESSION['permissions'])) {
        header("Location: ../admin/no_permission.php");
        exit();
    }

    // Nếu user có quyền MANAGE_ALL thì cho phép vào tất cả
    if (in_array('MANAGE_ALL', $_SESSION['permissions'])) {
        return;
    }

    // Nếu không có quyền cụ thể thì chặn
    if (!in_array($permCode, $_SESSION['permissions'])) {
        header("Location: ../admin/no_permission.php");
        exit();
    }
}
