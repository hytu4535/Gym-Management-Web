<?php
header('Content-Type: application/json');
require_once '../../includes/functions.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $address = $_POST['address'] ?? '';

    $errors = [];

    $fail = function ($message, $field = null) use (&$errors) {
        $response = ['success' => false, 'message' => $message];
        if (!empty($field)) {
            $errors[$field] = $message;
            $response['errors'] = $errors;
        }
        echo json_encode($response);
        exit();
    };

    // 1. Validate dữ liệu bắt buộc
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        if (empty($full_name)) $errors['full_name'] = 'Vui lòng nhập họ và tên.';
        if (empty($email)) $errors['email'] = 'Vui lòng nhập email.';
        if (empty($username)) $errors['username'] = 'Vui lòng nhập tên đăng nhập.';
        if (empty($password)) $errors['password'] = 'Vui lòng nhập mật khẩu.';
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!', 'errors' => $errors]);
        exit();
    }

    // 2. Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fail('Email không hợp lệ!', 'email');
    }

    // 3. Validate số điện thoại
    if (!empty($phone) && !preg_match('/^(?:\+84\d{9}|0\d{9,10})$/', $phone)) {
        $fail('Số điện thoại phải bắt đầu bằng 0 hoặc +84 và có 10-11 số!', 'phone');
    }

    // 4. Kiểm tra mật khẩu khớp
    if ($password !== $confirm_password) {
        $fail('Mật khẩu không khớp!', 'confirm_password');
    }

    try {
        // 5. Kiểm tra username hoặc email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            if ($result->num_rows > 0) {
                $stmt->close();
            }
            $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $checkUsername->bind_param("s", $username);
            $checkUsername->execute();
            $usernameExists = $checkUsername->get_result()->num_rows > 0;
            $checkUsername->close();

            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $emailExists = $checkEmail->get_result()->num_rows > 0;
            $checkEmail->close();

            $dupErrors = [];
            if ($usernameExists) $dupErrors['username'] = 'Tên đăng nhập đã tồn tại!';
            if ($emailExists) $dupErrors['email'] = 'Email đã tồn tại!';
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại!', 'errors' => $dupErrors]);
            exit();
        }
        $stmt->close();

        // 6. Insert vào bảng users (không truyền created_at, để DB tự thêm)
        $role_id = 6; // mặc định role user
        $status = 'active';
        $hashed_password = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $username, $full_name, $email, $phone, $hashed_password, $role_id, $status);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm user: ' . $stmt->error]);
            exit();
        }
        $user_id = $stmt->insert_id;
        $stmt->close();

        // 7. Insert vào bảng members
        $join_date = date('Y-m-d');
        $status_member = 'active';
        $height = 0;
        $weight = 0;
        $tier_id = 1;
        $total_spent = 0;

        $stmt = $conn->prepare("INSERT INTO members 
            (users_id, full_name, phone, address, join_date, status, height, weight, tier_id, total_spent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssddid", 
            $user_id, 
            $full_name, 
            $phone, 
            $address, 
            $join_date, 
            $status_member, 
            $height, 
            $weight, 
            $tier_id, 
            $total_spent
        );
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm member: ' . $stmt->error]);
            exit();
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập.']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>