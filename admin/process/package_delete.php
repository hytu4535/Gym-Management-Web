<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || (int) ($_SESSION['admin_user_id'] ?? 0) <= 0) {
    header("Location: ../login.php");
    exit();
}

$hasManageAll = in_array('MANAGE_ALL', $_SESSION['permissions'] ?? [], true);
$packageActionSet = $_SESSION['user_action_permissions']['MANAGE_PACKAGES'] ?? [];
$canDeletePackage = $hasManageAll || !empty($packageActionSet['delete']);
if (!$canDeletePackage) {
    header("Location: ../no_permission.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM membership_packages WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa gói tập thành công!'); window.location.href='../packages.php';</script>";
    } else {
        echo "<script>alert('Không thể xóa! Gói tập này đã được bán cho hội viên.'); window.location.href='../packages.php';</script>";
    }
}
?>