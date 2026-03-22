<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để đăng ký gói tập!'
    ]);
    exit();
}

// Validate dữ liệu đầu vào
if(!isset($_POST['package_id']) || !isset($_POST['start_date']) || !isset($_POST['payment_method'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bắt buộc!'
    ]);
    exit();
}

$member_id = $_SESSION['user_id'];
$package_id = intval($_POST['package_id']);
$start_date = $_POST['start_date'];
$payment_method = $_POST['payment_method'];
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// Validate ngày bắt đầu
$today = date('Y-m-d');
if($start_date < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Ngày bắt đầu không được nhỏ hơn ngày hiện tại!'
    ]);
    exit();
}

// TODO: Kết nối database
// require_once '../../config/db.php';

try {
    // TODO: Bước 1 - Lấy thông tin gói tập
    /*
    $sql = "SELECT * FROM packages WHERE package_id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Gói tập không tồn tại hoặc đã ngừng hoạt động!'
        ]);
        exit();
    }
    
    $package = $result->fetch_assoc();
    $price = $package['price'];
    $duration = $package['duration']; // số tháng
    */
    
    // Giả lập dữ liệu (xóa khi đã có database)
    $price = 1350000;
    $duration = 3;
    
    // TODO: Bước 2 - Kiểm tra xem có gói tập đang hoạt động không
    /*
    $sql = "SELECT * FROM member_packages 
            WHERE member_id = ? 
            AND status = 'active' 
            AND end_date > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đang có gói tập hoạt động. Vui lòng chờ hết hạn hoặc hủy gói cũ!'
        ]);
        exit();
    }
    */
    
    // TODO: Bước 3 - Tính ngày hết hạn
    $end_date = date('Y-m-d', strtotime($start_date . " +$duration months"));
    
    // TODO: Bước 4 - Xác định trạng thái đơn hàng
    // Nếu thanh toán tiền mặt -> pending (chờ thanh toán tại quầy)
    // Nếu chuyển khoản/online -> pending (chờ xác nhận thanh toán)
    $status = 'pending';
    $payment_status = 'unpaid';
    
    // TODO: Bước 5 - INSERT vào bảng member_packages
    /*
    $sql = "INSERT INTO member_packages 
            (member_id, package_id, start_date, end_date, price, 
             payment_method, payment_status, status, note, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdssss", 
        $member_id, 
        $package_id, 
        $start_date, 
        $end_date, 
        $price,
        $payment_method,
        $payment_status,
        $status,
        $note
    );
    
    if(!$stmt->execute()) {
        throw new Exception('Không thể lưu đăng ký gói tập!');
    }
    
    $member_package_id = $conn->insert_id;
    */
    
    // Giả lập ID (xóa khi đã có database)
    $member_package_id = rand(1, 1000);
    
    // TODO: Bước 6 - Tạo thông báo cho admin
    /*
    $notification_message = "Thành viên " . $_SESSION['full_name'] . " đã đăng ký gói tập mới.";
    $sql = "INSERT INTO notifications (member_id, type, message, created_at) 
            VALUES (?, 'package_register', ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $member_id, $notification_message);
    $stmt->execute();
    */
    
    // TODO: Bước 7 - Gửi email xác nhận (nếu có)
    // sendConfirmationEmail($_SESSION['email'], $package, $start_date, $end_date);
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký gói tập thành công! Vui lòng thanh toán để kích hoạt gói.',
        'data' => [
            'member_package_id' => $member_package_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'price' => $price,
            'payment_method' => $payment_method,
            'status' => $status
        ]
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
