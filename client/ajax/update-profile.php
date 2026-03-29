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
    $avatarRelativePath = null;
    $avatarFile = $_FILES['avatar'] ?? null;
    $uploadedAvatarFullPath = null;

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
        $stmtCurrent = $conn->prepare("SELECT avatar FROM users WHERE id=? LIMIT 1");
        if (!$stmtCurrent) { throw new Exception("SQL error (users current): ".$conn->error); }
        $stmtCurrent->bind_param("i", $user_id);
        $stmtCurrent->execute();
        $currentUser = $stmtCurrent->get_result()->fetch_assoc();
        $stmtCurrent->close();

        if ($avatarFile && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success'=>false,'message'=>'Tải avatar thất bại!']);
                exit();
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $avatarExtension = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));

            if (!in_array($avatarExtension, $allowedExtensions, true)) {
                echo json_encode(['success'=>false,'message'=>'Avatar chỉ chấp nhận JPG, PNG, WEBP, GIF!']);
                exit();
            }

            if ($avatarFile['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success'=>false,'message'=>'Avatar không được vượt quá 5MB!']);
                exit();
            }

            $imageInfo = @getimagesize($avatarFile['tmp_name']);
            if ($imageInfo === false || empty($imageInfo['mime']) || !in_array($imageInfo['mime'], $allowedMimeTypes, true)) {
                echo json_encode(['success'=>false,'message'=>'File tải lên không phải là ảnh hợp lệ!']);
                exit();
            }

            $avatarUploadDir = __DIR__ . '/../../assets/uploads/avatars/';
            if (!is_dir($avatarUploadDir)) {
                if (!mkdir($avatarUploadDir, 0777, true) && !is_dir($avatarUploadDir)) {
                    throw new Exception('Không thể tạo thư mục upload avatar!');
                }
            }

            $avatarFileName = 'avatar_' . $user_id . '_' . time() . '.' . $avatarExtension;
            $uploadedAvatarFullPath = $avatarUploadDir . $avatarFileName;
            if (!move_uploaded_file($avatarFile['tmp_name'], $uploadedAvatarFullPath)) {
                echo json_encode(['success'=>false,'message'=>'Không thể lưu avatar!']);
                exit();
            }

            $avatarRelativePath = 'assets/uploads/avatars/' . $avatarFileName;
        }

        // Update bảng users (username, email)
        if ($avatarRelativePath !== null) {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, avatar=? WHERE id=?");
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
        }
        if(!$stmt){ throw new Exception("SQL error (users): ".$conn->error); }
        if ($avatarRelativePath !== null) {
            $stmt->bind_param("sssi", $username, $email, $avatarRelativePath, $user_id);
        } else {
            $stmt->bind_param("ssi", $username, $email, $user_id);
        }
        $stmt->execute();
        $stmt->close();

        if ($avatarRelativePath !== null && !empty($currentUser['avatar'])) {
            $oldAvatarPath = __DIR__ . '/../../' . ltrim(str_replace('\\', '/', $currentUser['avatar']), '/');
            if (is_file($oldAvatarPath)) {
                @unlink($oldAvatarPath);
            }
        }

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
        if ($avatarRelativePath !== null) {
            $_SESSION['avatar'] = $avatarRelativePath;
        }

        $avatarUrl = '';
        if ($avatarRelativePath !== null) {
            $avatarUrl = '../' . $avatarRelativePath;
        } elseif (!empty($currentUser['avatar'])) {
            $avatarUrl = '../' . ltrim(str_replace('\\', '/', $currentUser['avatar']), '/');
        }

        echo json_encode(['success'=>true,'message'=>'Cập nhật thông tin thành công!','avatar_url'=>$avatarUrl]);
    } catch (Exception $e) {
        if ($uploadedAvatarFullPath !== null && is_file($uploadedAvatarFullPath)) {
            @unlink($uploadedAvatarFullPath);
        }
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Phương thức không hợp lệ!']);
}
