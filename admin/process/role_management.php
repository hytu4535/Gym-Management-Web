<?php
session_start();
include '../../includes/database.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action == 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        $permissions = $_POST['permissions'] ?? [];

        // Thêm role
        $sql = "INSERT INTO roles (name, description, status) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $description, $status]);
        $role_id = $db->lastInsertId();

        // Gán permissions cho role
        foreach ($permissions as $perm_id) {
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$role_id, $perm_id]);
        }

        header("Location: ../roles.php");
        exit();
    }

    if ($action == 'delete') {
        $id = $_GET['id'];
        $sql = "DELETE FROM roles WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        // Xoá luôn role_permissions liên quan
        $sql = "DELETE FROM role_permissions WHERE role_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        header("Location: ../roles.php");
        exit();
    }

    if ($action == 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        $permissions = $_POST['permissions'] ?? [];

        $sql = "UPDATE roles SET name=?, description=?, status=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $description, $status, $id]);

        // Cập nhật lại permissions
        $sql = "DELETE FROM role_permissions WHERE role_id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        foreach ($permissions as $perm_id) {
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id, $perm_id]);
        }

        header("Location: ../roles.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['validation_errors'] = ['general' => 'Không thể xóa hoặc cập nhật vai trò này vì đang có dữ liệu liên quan. Vui lòng chuyển các user sang vai trò khác trước khi thao tác.'];
    header("Location: ../roles.php");
    exit();
}
