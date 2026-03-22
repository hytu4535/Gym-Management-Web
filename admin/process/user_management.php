<?php
include '../../includes/database.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'add') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password']; // có thể dùng password_hash()
    $role_id = $_POST['role_id'];
    $status = 'active';

    $sql = "INSERT INTO users (username, password, email, role_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $password, $email, $role_id, $status]);

    header("Location: ../users.php");
    exit();
}

if ($action == 'delete') {
    $id = $_GET['id'];
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);

    header("Location: ../users.php");
    exit();
}

// xử lý edit (nếu bạn có form edit_user.php)
if ($action == 'edit') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $sql = "UPDATE users SET username=?, email=?, role_id=?, status=?, password=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $email, $role_id, $status, $password, $id]);
    } else {
        $sql = "UPDATE users SET username=?, email=?, role_id=?, status=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $email, $role_id, $status, $id]);
    }

    header("Location: ../users.php");
    exit();
}
?>
