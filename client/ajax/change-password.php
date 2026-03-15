<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn chưa đăng nhập!'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id          = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validate dữ liệu
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ thông tin!'
        ]);
        exit();
    }
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới và xác nhận không khớp!'
        ]);
        exit();
    }

    try {
        // 2. Lấy mật khẩu hiện tại từ DB
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản!'
            ]);
            exit();
        }

        $user = $result->fetch_assoc();

        // 3. Kiểm tra mật khẩu hiện tại
        $is_correct = false;
        if (password_verify($current_password, $user['password'])) {
            $is_correct = true;
        } elseif (md5($current_password) === $user['password']) {
            // fallback cho DB cũ
            $is_correct = true;
        } elseif ($current_password === $user['password']) {
            $is_correct = true;
        }

        if (!$is_correct) {
            echo json_encode([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng!'
            ]);
            exit();
        }

        // 4. Hash mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // 5. Update DB
        $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt2->bind_param("si", $hashed_password, $user_id);
        if ($stmt2->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đổi mật khẩu thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đổi mật khẩu!'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
}
