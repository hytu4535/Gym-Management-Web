<?php
// Đảm bảo không có khoảng trắng hoặc dòng trống trước <?php

require_once __DIR__ . '/database.php';

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

// Chặn user đã bị khóa nếu tài khoản đổi trạng thái sau khi đã đăng nhập
try {
    $db = getDB();
    if (!empty($_SESSION['admin_user_id'])) {
        $stmt = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_user_id']]);
    } elseif (!empty($_SESSION['admin_username'])) {
        $stmt = $db->prepare("SELECT status FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_username']]);
    } else {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php");
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || ($user['status'] ?? '') !== 'active') {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?locked=1");
        exit();
    }
} catch (Exception $e) {
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php");
    exit();
}
