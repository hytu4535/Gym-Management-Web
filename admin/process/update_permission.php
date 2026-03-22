<?php
session_start();
include '../../includes/database.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy danh sách roles từ DB
    $roles = $db->query("SELECT * FROM roles")->fetchAll();

    // Dữ liệu từ form
    $permissionsData = $_POST['permissions'] ?? [];

    foreach ($roles as $r) {
        $role_id = $r['id'];

        // Xóa hết quyền cũ
        $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$role_id]);

        // Nếu có quyền mới được tick thì thêm lại
        if (!empty($permissionsData[$role_id])) {
            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissionsData[$role_id] as $pid) {
                $stmt->execute([$role_id, $pid]);
            }
        }

        // Nếu user hiện tại thuộc role này thì reload lại session quyền
        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role_id) {
            $stmt2 = $db->prepare("SELECT p.code 
                                   FROM role_permissions rp 
                                   JOIN permission p ON rp.permission_id = p.id 
                                   WHERE rp.role_id = ?");
            $stmt2->execute([$role_id]);
            $_SESSION['permissions'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // Flash message
    $_SESSION['flash_message'] = "Cập nhật phân quyền thành công!";
    header("Location: ../permissions.php");
    exit();
}
