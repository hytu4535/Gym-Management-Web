<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $address = $_POST['address'] ?? '';

    // 1. Validate dữ liệu bắt buộc
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!']);
        exit();
    }

    // 2. Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ!']);
        exit();
    }

    // 3. Validate số điện thoại
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại phải có 10 chữ số!']);
        exit();
    }

    // 4. Kiểm tra mật khẩu khớp
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu không khớp!']);
        exit();
    }

    try {
        // 5. Kiểm tra username hoặc email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại!']);
            exit();
        }
        $stmt->close();

        // 6. Insert vào bảng users (không truyền created_at, để DB tự thêm)
        $role_id = 6; // mặc định role user
        $status = 'active';
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $username, $email, $password, $role_id, $status);
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
