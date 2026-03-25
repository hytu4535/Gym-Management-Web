<?php
include '../../includes/database.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'add') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role_id = $_POST['role_id'];
    $status = 'active';

    // Kiểm tra password khớp
    if ($password !== $password_confirm) {
        header("Location: ../users.php?error=password_mismatch");
        exit();
    }

    // Kiểm tra phone column có tồn tại không
    $checkColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();
    
    if (!empty($checkColumn)) {
        $sql = "INSERT INTO users (username, password, email, phone, role_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $password, $email, $phone, $role_id, $status]);
    } else {
        $sql = "INSERT INTO users (username, password, email, role_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $password, $email, $role_id, $status]);
    }

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

// xử lý edit
if ($action == 'edit') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Kiểm tra password khớp nếu nhập mật khẩu
    if (!empty($password) && $password !== $password_confirm) {
        header("Location: ../users.php?error=password_mismatch");
        exit();
    }

    // Kiểm tra phone column có tồn tại không
    $checkColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();

    if (!empty($checkColumn)) {
        if (!empty($password)) {
            $sql = "UPDATE users SET username=?, email=?, phone=?, role_id=?, status=?, password=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $email, $phone, $role_id, $status, $password, $id]);
        } else {
            $sql = "UPDATE users SET username=?, email=?, phone=?, role_id=?, status=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $email, $phone, $role_id, $status, $id]);
        }
    } else {
        if (!empty($password)) {
            $sql = "UPDATE users SET username=?, email=?, role_id=?, status=?, password=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $email, $role_id, $status, $password, $id]);
        } else {
            $sql = "UPDATE users SET username=?, email=?, role_id=?, status=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $email, $role_id, $status, $id]);
        }
    }

    header("Location: ../users.php");
    exit();
}
?>
