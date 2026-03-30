<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_MEMBERS', 'delete');

require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM member_packages WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa dữ liệu thành công!'); window.location.href='../member-packages.php';</script>";
    } else {
        $friendly = processFriendlyDbError($conn->error, 'Không thể xóa gói tập hội viên.');
        echo "<script>alert('" . addslashes($friendly) . "'); window.location.href='../member-packages.php';</script>";
    }
}
?>