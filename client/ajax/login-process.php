<?php
// Xử lý chống lỗi trùng lặp session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // 1. Validate dữ liệu
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu!'
        ]);
        exit();
    }
    
    try {
        // 2. Query database: Kết hợp bảng users và members để lấy thông tin
        // Có thể đăng nhập bằng username HOẶC email
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.password, u.role_id, u.status, m.full_name 
            FROM users u
            LEFT JOIN members m ON u.id = m.users_id
            WHERE (u.username = ? OR u.email = ?)
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // 3. Kiểm tra tài khoản có tồn tại không
        if ($result->num_rows === 0) {
            throw new Exception("Tài khoản hoặc mật khẩu không chính xác!");
        }

        $user = $result->fetch_assoc();

        // 4. Kiểm tra trạng thái tài khoản
        if ($user['status'] !== 'active') {
            throw new Exception("Tài khoản của bạn đã bị khóa hoặc chưa kích hoạt!");
        }

        // 5. Verify password
        // Hỗ trợ cả chuẩn password_verify (Bcrypt) và fallback cho MD5 để dễ test
        $is_password_correct = false;
        if (password_verify($password, $user['password'])) {
            $is_password_correct = true;
        } elseif (md5($password) === $user['password']) {
            $is_password_correct = true;
        } elseif ($password === $user['password']) {
            // (Không khuyến khích) Hỗ trợ luôn cả pass chưa mã hóa nếu lỡ nhập tay vào DB
            $is_password_correct = true;
        }

        if ($is_password_correct) {
            // Đăng nhập thành công: Gán Session
            $_SESSION['user_id'] = $user['id']; // ID của bảng users
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            
            // 6. Implement remember me functionality (Lưu cookie 30 ngày)
            if ($remember_me) {
                // Tạo một token ngẫu nhiên
                $token = bin2hex(random_bytes(16));
                // Trong môi trường thực tế, token này nên được lưu vào bảng user_tokens trong DB
                setcookie('remember_token', $user['id'] . ':' . $token, time() + (86400 * 30), '/');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Đăng nhập thành công!',
                'redirect' => 'index.php'
            ]);
        } else {
            throw new Exception("Tài khoản hoặc mật khẩu không chính xác!");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
}
?>