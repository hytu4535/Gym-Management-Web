<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Bạn chưa đăng nhập!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = $_SESSION['user_id'];

    // Lấy dữ liệu từ form
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $height    = $_POST['height'] ?? '';
    $weight    = $_POST['weight'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (empty($full_name) || empty($email) || empty($username) || empty($phone)) {
        echo json_encode(['success'=>false,'message'=>'Họ tên, username, email và SDT là bắt buộc!']);
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success'=>false,'message'=>'Email không hợp lệ!']);
        exit();
    }
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['success'=>false,'message'=>'Số điện thoại phải đúng 10 chữ số!']);
        exit();
    }
    if ($height !== '' && (!is_numeric($height) || $height <= 0)) {
        echo json_encode(['success'=>false,'message'=>'Chiều cao phải là số dương!']);
        exit();
    }
    if ($weight !== '' && (!is_numeric($weight) || $weight <= 0)) {
        echo json_encode(['success'=>false,'message'=>'Cân nặng phải là số dương!']);
        exit();
    }

    // Ép kiểu cho bind_param
    $height = $height === '' ? null : (float)$height;
    $weight = $weight === '' ? null : (float)$weight;

    try {
        // Update bảng users (username, email)
        $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
        if(!$stmt){ throw new Exception("SQL error (users): ".$conn->error); }
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $stmt->close();

        // Update bảng members (full_name, phone, height, weight)
        $stmt2 = $conn->prepare("UPDATE members SET full_name=?, phone=?, height=?, weight=? WHERE users_id=?");
        if(!$stmt2){ throw new Exception("SQL error (members): ".$conn->error); }
        $stmt2->bind_param("ssddi", $full_name, $phone, $height, $weight, $user_id);
        $stmt2->execute();
        $stmt2->close();


        // Cập nhật session để hiển thị ngay
        $_SESSION['full_name'] = $full_name;
        $_SESSION['username']  = $username;
        $_SESSION['email']     = $email;
        $_SESSION['phone']     = $phone;
        $_SESSION['height']    = $height;
        $_SESSION['weight']    = $weight;

        echo json_encode(['success'=>true,'message'=>'Cập nhật thông tin thành công!']);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Phương thức không hợp lệ!']);
}
