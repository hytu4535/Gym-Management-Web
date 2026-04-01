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

$token = trim($_POST['token'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($token === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin!'
    ]);
    exit();
}

if ($newPassword !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu mới và xác nhận không khớp!'
    ]);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự!'
    ]);
    exit();
}

try {
    ensurePasswordResetColumnsLocal($conn);

    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ? LIMIT 1");
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã khôi phục không hợp lệ hoặc đã hết hạn!'
        ]);
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    $expiresAt = $user['reset_token_expires_at'] ?? null;
    if (empty($expiresAt) || strtotime($expiresAt) < time()) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã khôi phục đã hết hạn!'
        ]);
        exit();
    }

    $hashedPassword = hashPassword($newPassword);
    $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
    $updateStmt->bind_param('si', $hashedPassword, $user['id']);

    if (!$updateStmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật mật khẩu mới!'
        ]);
        exit();
    }

    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Đặt lại mật khẩu thành công!'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}