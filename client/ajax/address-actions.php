<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/address_schema.php';

ensureAddressSchemaMysqli($conn);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Bạn chưa đăng nhập!']); exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM members WHERE users_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$member) {
    echo json_encode(['success'=>false,'message'=>'Không tìm thấy hội viên tương ứng!']);
    exit();
}
$member_id = $member['id'];

try {
    // Thêm hoặc sửa
    if (isset($_POST['full_address'])) {
        $full = trim($_POST['full_address'] ?? '');
        $province = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $ward = trim($_POST['ward'] ?? '');
        $type = trim($_POST['type'] ?? 'home');
        $id = $_POST['id'] ?? '';
        $errors = [];

        if ($full === '') {
            $errors['full_address'] = 'Vui lòng nhập địa chỉ chi tiết.';
        }
        if ($province === '') {
            $errors['city'] = 'Vui lòng chọn Tỉnh/Thành phố.';
        }
        if ($district === '') {
            $errors['district'] = 'Vui lòng chọn Quận/Huyện.';
        }
        if ($ward === '') {
            $errors['ward'] = 'Vui lòng chọn Phường/Xã.';
        }
        if (!in_array($type, ['home', 'work', 'other'], true)) {
            $errors['type'] = 'Loại địa chỉ không hợp lệ.';
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng kiểm tra lại thông tin.', 'fieldErrors' => $errors]);
            exit();
        }

        if ($id) {
            $stmt = $conn->prepare("UPDATE addresses SET full_address=?, district=?, city=?, ward=?, type=? WHERE id=? AND member_id=?");
            $stmt->bind_param("sssssii", $full, $district, $province, $ward, $type, $id, $member_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Cập nhật địa chỉ thành công!']); exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO addresses (member_id, full_address, district, city, ward, type, is_default) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("isssss", $member_id, $full, $district, $province, $ward, $type);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Thêm địa chỉ mới thành công!']); exit();
        }
    }

    // Đặt mặc định
    if (isset($_POST['set_default'])) {
        $id = intval($_POST['set_default']);
        $stmt = $conn->prepare("SELECT id FROM addresses WHERE id=? AND member_id=?");
        $stmt->bind_param("ii", $id, $member_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            echo json_encode(['success'=>false,'message'=>'Địa chỉ không tồn tại!']); exit();
        }

        $stmt = $conn->prepare("UPDATE addresses SET is_default=0 WHERE member_id=?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE addresses SET is_default=1 WHERE id=? AND member_id=?");
        $stmt->bind_param("ii", $id, $member_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success'=>true,'message'=>'Đặt địa chỉ mặc định thành công!']); exit();
    }

    // Xóa địa chỉ
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);

        // Kiểm tra xem địa chỉ có phải mặc định không
        $stmt = $conn->prepare("SELECT is_default FROM addresses WHERE id=? AND member_id=?");
        $stmt->bind_param("ii", $id, $member_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $is_default = $result ? $result['is_default'] : 0;

        if (!$result) {
            echo json_encode(['success'=>false,'message'=>'Địa chỉ không tồn tại!']); exit();
        }

        $conn->begin_transaction();

        // Xóa địa chỉ
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id=? AND member_id=?");
        $stmt->bind_param("ii", $id, $member_id);
        $stmt->execute();
        $stmt->close();

        // Nếu địa chỉ vừa xóa là mặc định, chọn một địa chỉ khác làm mặc định
        if ($is_default == 1) {
            $stmt = $conn->prepare("SELECT id FROM addresses WHERE member_id=? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $new_default = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($new_default) {
                $stmt = $conn->prepare("UPDATE addresses SET is_default=1 WHERE id=? AND member_id=?");
                $stmt->bind_param("ii", $new_default['id'], $member_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();

        echo json_encode(['success'=>true,'message'=>'Xóa địa chỉ thành công!']); exit();
    }


    echo json_encode(['success'=>false,'message'=>'Không có hành động nào được thực hiện!']);
} catch (Exception $e) {
    @ $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
