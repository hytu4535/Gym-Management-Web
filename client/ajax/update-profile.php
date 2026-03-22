<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php'; // sửa lại đường dẫn

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Bạn chưa đăng nhập!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if (empty($full_name) || empty($email)) {
        echo json_encode(['success'=>false,'message'=>'Họ tên và email là bắt buộc!']);
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success'=>false,'message'=>'Email không hợp lệ!']);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
        if(!$stmt){ throw new Exception("SQL error (users): ".$conn->error); }
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE members SET full_name=?, phone=?, address=? WHERE users_id=?");
        if(!$stmt2){ throw new Exception("SQL error (members): ".$conn->error); }
        $stmt2->bind_param("sssi", $full_name, $phone, $address, $user_id);
        $stmt2->execute();

        $_SESSION['full_name'] = $full_name;
        $_SESSION['email']     = $email;
        $_SESSION['phone']     = $phone;

        echo json_encode(['success'=>true,'message'=>'Cập nhật thông tin thành công!']);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Phương thức không hợp lệ!']);
}
