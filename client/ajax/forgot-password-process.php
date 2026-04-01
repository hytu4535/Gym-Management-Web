<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../../includes/functions.php';
require_once '../../config/db.php';

if (!function_exists('ensurePasswordResetColumnsLocal')) {
    function ensurePasswordResetColumnsLocal($conn) {
        $columns = [
            'reset_token_hash' => "ALTER TABLE users ADD COLUMN reset_token_hash varchar(64) DEFAULT NULL AFTER avatar",
            'reset_token_expires_at' => "ALTER TABLE users ADD COLUMN reset_token_expires_at datetime DEFAULT NULL AFTER reset_token_hash",
        ];

        foreach ($columns as $columnName => $alterSql) {
            $checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
            if (!$checkStmt) {
                throw new Exception('Không thể kiểm tra cấu trúc bảng users.');
            }

            $checkStmt->bind_param('s', $columnName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $columnExists = false;

            if ($result) {
                $row = $result->fetch_assoc();
                $columnExists = ((int) ($row['total'] ?? 0)) > 0;
            }

            $checkStmt->close();

            if (!$columnExists && !$conn->query($alterSql)) {
                throw new Exception('Không thể khởi tạo chức năng quên mật khẩu.');
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
    exit();
}

$account = trim($_POST['account'] ?? '');

if ($account === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập email hoặc tên đăng nhập!'
    ]);
    exit();
}

try {
    ensurePasswordResetColumnsLocal($conn);

    $stmt = $conn->prepare("SELECT id, username, email, status FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param('ss', $account, $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản phù hợp!'
        ]);
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (($user['status'] ?? '') !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản của bạn đang bị khóa hoặc chưa kích hoạt!'
        ]);
        exit();
    }

    $token = generateToken(32);
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $updateStmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
    $updateStmt->bind_param('ssi', $tokenHash, $expiresAt, $user['id']);

    if (!$updateStmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo link khôi phục mật khẩu!'
        ]);
        exit();
    }

    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo link đặt lại mật khẩu. Vì môi trường này chưa cấu hình mail, hãy mở link bên dưới để tiếp tục.',
        'reset_link' => 'reset-password.php?token=' . urlencode($token)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}