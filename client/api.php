<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

function getHardcodedFallbackData($member)
{
    $memberId = intval($member['id']);
    $today = date('d/m/Y H:i');

    if (!isset($_SESSION['demo_registered_packages'])) {
        $_SESSION['demo_registered_packages'] = [];
    }

    if (!isset($_SESSION['demo_read_notifications'])) {
        $_SESSION['demo_read_notifications'] = [];
    }

    $demoPackages = [
        [
            'id' => 9001,
            'package_name' => 'Gói Cơ Bản 1 tháng',
            'duration_months' => 1,
            'price' => 500000,
            'description' => 'Tập không giới hạn khung giờ hành chính',
            'already_registered' => in_array(9001, $_SESSION['demo_registered_packages'], true) ? 1 : 0
        ],
        [
            'id' => 9002,
            'package_name' => 'Gói Nâng Cao 3 tháng',
            'duration_months' => 3,
            'price' => 1350000,
            'description' => 'Bao gồm 2 buổi PT/tuần',
            'already_registered' => in_array(9002, $_SESSION['demo_registered_packages'], true) ? 1 : 0
        ],
        [
            'id' => 9003,
            'package_name' => 'Gói Premium 12 tháng',
            'duration_months' => 12,
            'price' => 4200000,
            'description' => 'Ưu tiên đặt lịch, miễn phí đo BMI hàng tháng',
            'already_registered' => in_array(9003, $_SESSION['demo_registered_packages'], true) ? 1 : 0
        ]
    ];

    $registeredDemo = [];
    foreach ($demoPackages as $demoPkg) {
        if (in_array($demoPkg['id'], $_SESSION['demo_registered_packages'], true)) {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+' . intval($demoPkg['duration_months']) . ' months'));
            $registeredDemo[] = [
                'member_package_id' => $demoPkg['id'],
                'package_id' => $demoPkg['id'],
                'package_name' => $demoPkg['package_name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active'
            ];
        }
    }

    $demoNotifications = [
        [
            'id' => 9901,
            'title' => 'Thông báo hệ thống',
            'content' => 'Chào mừng bạn đến với cổng dịch vụ hội viên.',
            'is_read' => in_array(9901, $_SESSION['demo_read_notifications'], true) ? 1 : 0,
            'created_at' => $today
        ],
        [
            'id' => 9902,
            'title' => 'Ưu đãi tháng này',
            'content' => 'Giảm 10% cho gói tập 3 tháng khi đăng ký online.',
            'is_read' => in_array(9902, $_SESSION['demo_read_notifications'], true) ? 1 : 0,
            'created_at' => $today
        ]
    ];

    return [
        'packages' => $demoPackages,
        'member_packages' => $registeredDemo,
        'services' => [
            ['id' => 9101, 'name' => 'Xông hơi thư giãn', 'type' => 'thư giãn', 'price' => 120000, 'description' => 'Thư giãn cơ sau buổi tập'],
            ['id' => 9102, 'name' => 'Massage thể thao', 'type' => 'xoa bóp', 'price' => 180000, 'description' => 'Giảm mỏi cơ chuyên sâu'],
            ['id' => 9103, 'name' => 'Tư vấn kỹ thuật squat', 'type' => 'hỗ trợ', 'price' => 90000, 'description' => 'Sửa form và phòng tránh chấn thương']
        ],
        'nutrition_plans' => [
            ['id' => 9201, 'name' => 'Thực đơn giảm mỡ 1500', 'type' => 'thực đơn', 'calories' => 1500, 'bmi_range' => '24-30', 'price' => 350000, 'description' => 'Cân bằng đạm-carb-fat trong 4 tuần'],
            ['id' => 9202, 'name' => 'Thực đơn tăng cơ 2500', 'type' => 'thực đơn', 'calories' => 2500, 'bmi_range' => '18-24', 'price' => 420000, 'description' => 'Tăng khối lượng cơ nạc'],
            ['id' => 9203, 'name' => 'Tư vấn dinh dưỡng 1:1', 'type' => 'tư vấn', 'calories' => null, 'bmi_range' => 'Mọi mức', 'price' => 500000, 'description' => 'Lộ trình cá nhân hóa 30 ngày']
        ],
        'promotions' => [
            ['id' => 9301, 'name' => 'Ưu đãi hội viên mới', 'discount_type' => 'percentage', 'discount_value' => '10%', 'start_date' => '2026-03-01', 'end_date' => '2026-03-31', 'usage_limit' => 1],
            ['id' => 9302, 'name' => 'Tặng buổi PT', 'discount_type' => 'package', 'discount_value' => '1 buổi', 'start_date' => '2026-03-01', 'end_date' => '2026-04-15', 'usage_limit' => 1]
        ],
        'feedbacks' => [
            ['id' => 9401, 'content' => 'Phòng tập sạch sẽ, máy chạy tốt.', 'rating' => 5, 'status' => 'processed', 'created_at' => $today],
            ['id' => 9402, 'content' => 'Mong mở thêm lớp yoga buổi tối.', 'rating' => 4, 'status' => 'new', 'created_at' => $today]
        ],
        'notifications' => $demoNotifications,
        'demo_member_id' => $memberId
    ];
}

function applyFallbackData($realData, $fallbackData)
{
    foreach (['packages', 'member_packages', 'services', 'nutrition_plans', 'promotions', 'feedbacks', 'notifications'] as $key) {
        if (empty($realData[$key])) {
            $realData[$key] = $fallbackData[$key];
        }
    }
    return $realData;
}

function jsonResponse($success, $message = '', $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function resolveCurrentMember(PDO $db)
{
    $memberId = isset($_REQUEST['member_id']) ? intval($_REQUEST['member_id']) : 0;
    if ($memberId > 0) {
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        if ($member) {
            return $member;
        }
    }

    $sessionUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    if ($sessionUserId > 0) {
        $stmt = $db->prepare("SELECT * FROM members WHERE users_id = ? LIMIT 1");
        $stmt->execute([$sessionUserId]);
        $member = $stmt->fetch();
        if ($member) {
            return $member;
        }
    }

    $fallbackStmt = $db->query("SELECT * FROM members ORDER BY id ASC LIMIT 1");
    return $fallbackStmt->fetch();
}

function getDashboardData(PDO $db, $member)
{
    $memberId = intval($member['id']);
    $userId = intval($member['users_id']);

    $packageStmt = $db->prepare(
        "SELECT mp.id, mp.package_name, mp.duration_months, mp.price, mp.description,
                CASE WHEN EXISTS (
                    SELECT 1 FROM member_packages mmp
                    WHERE mmp.package_id = mp.id AND mmp.member_id = ? AND mmp.status = 'active'
                ) THEN 1 ELSE 0 END AS already_registered
         FROM membership_packages mp
         WHERE mp.status = 'active'
         ORDER BY mp.id DESC"
    );
    $packageStmt->execute([$memberId]);
    $packages = $packageStmt->fetchAll();

    $memberPackageStmt = $db->prepare(
        "SELECT mmp.id AS member_package_id, mmp.package_id, mp.package_name, mmp.start_date, mmp.end_date, mmp.status
         FROM member_packages mmp
         INNER JOIN membership_packages mp ON mp.id = mmp.package_id
         WHERE mmp.member_id = ?
         ORDER BY mmp.id DESC"
    );
    $memberPackageStmt->execute([$memberId]);
    $memberPackages = $memberPackageStmt->fetchAll();

    $serviceStmt = $db->query("SELECT id, name, type, price, description FROM services WHERE status = 'hoạt động' ORDER BY id DESC");
    $services = $serviceStmt->fetchAll();

    $nutritionStmt = $db->query("SELECT id, name, type, calories, bmi_range, price, description FROM nutrition_plans WHERE status = 'hoạt động' ORDER BY id DESC");
    $nutritionPlans = $nutritionStmt->fetchAll();

    $promotionStmt = $db->prepare(
        "SELECT tp.id, tp.name, tp.discount_type, tp.discount_value, tp.start_date, tp.end_date, tp.usage_limit
         FROM tier_promotions tp
         INNER JOIN members m ON m.tier_id = tp.tier_id
         WHERE m.id = ? AND tp.status = 'active'
         ORDER BY tp.id DESC"
    );
    $promotionStmt->execute([$memberId]);
    $promotions = $promotionStmt->fetchAll();

    $feedbackStmt = $db->prepare("SELECT id, content, rating, status, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at FROM feedback WHERE member_id = ? ORDER BY id DESC");
    $feedbackStmt->execute([$memberId]);
    $feedbacks = $feedbackStmt->fetchAll();

    $notificationStmt = $db->prepare("SELECT id, title, content, is_read, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at FROM notifications WHERE user_id = ? ORDER BY id DESC");
    $notificationStmt->execute([$userId]);
    $notifications = $notificationStmt->fetchAll();

    $realData = [
        'packages' => $packages,
        'member_packages' => $memberPackages,
        'services' => $services,
        'nutrition_plans' => $nutritionPlans,
        'promotions' => $promotions,
        'feedbacks' => $feedbacks,
        'notifications' => $notifications
    ];

    $fallback = getHardcodedFallbackData($member);
    return applyFallbackData($realData, $fallback);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'dashboard';
$member = resolveCurrentMember($db);

if (!$member) {
    jsonResponse(false, 'Không tìm thấy hội viên trong hệ thống.');
}

$memberId = intval($member['id']);
$userId = intval($member['users_id']);

if ($action === 'dashboard') {
    jsonResponse(true, 'OK', getDashboardData($db, $member));
}

if ($action === 'register_package') {
    $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;

    if ($packageId <= 0) {
        jsonResponse(false, 'Gói tập không hợp lệ.');
    }

    $packageStmt = $db->prepare("SELECT id, package_name, duration_months, status FROM membership_packages WHERE id = ? LIMIT 1");
    $packageStmt->execute([$packageId]);
    $pkg = $packageStmt->fetch();

    if (!$pkg || $pkg['status'] !== 'active') {
        $fallback = getHardcodedFallbackData($member);
        $fallbackPackage = null;
        foreach ($fallback['packages'] as $item) {
            if (intval($item['id']) === $packageId) {
                $fallbackPackage = $item;
                break;
            }
        }

        if (!$fallbackPackage) {
            jsonResponse(false, 'Gói tập không tồn tại hoặc không hoạt động.');
        }

        if (!in_array($packageId, $_SESSION['demo_registered_packages'], true)) {
            $_SESSION['demo_registered_packages'][] = $packageId;
        }

        jsonResponse(true, 'Đăng ký gói tập demo thành công (dữ liệu tạm thời).', getDashboardData($db, $member));
    }

    $checkStmt = $db->prepare("SELECT COUNT(*) FROM member_packages WHERE member_id = ? AND package_id = ? AND status = 'active'");
    $checkStmt->execute([$memberId, $packageId]);
    if ((int) $checkStmt->fetchColumn() > 0) {
        jsonResponse(false, 'Bạn đã đăng ký gói tập này rồi.');
    }

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+' . intval($pkg['duration_months']) . ' months'));

    $insertStmt = $db->prepare("INSERT INTO member_packages (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
    $ok = $insertStmt->execute([$memberId, $packageId, $startDate, $endDate]);

    if ($ok) {
        $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
        $notifyStmt->execute([$userId, 'Đăng ký gói tập', 'Bạn đã đăng ký thành công gói: ' . $pkg['package_name']]);
        jsonResponse(true, 'Đăng ký gói tập thành công.', getDashboardData($db, $member));
    }

    jsonResponse(false, 'Lỗi khi đăng ký gói tập.');
}

if ($action === 'cancel_package') {
    $memberPackageId = isset($_POST['member_package_id']) ? intval($_POST['member_package_id']) : 0;
    $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;

    if ($packageId >= 9000) {
        $idx = array_search($packageId, $_SESSION['demo_registered_packages'], true);
        if ($idx !== false) {
            unset($_SESSION['demo_registered_packages'][$idx]);
            $_SESSION['demo_registered_packages'] = array_values($_SESSION['demo_registered_packages']);
            jsonResponse(true, 'Huỷ đăng ký gói tập demo thành công.', getDashboardData($db, $member));
        }
        jsonResponse(false, 'Gói demo chưa được đăng ký hoặc đã huỷ trước đó.');
    }

    if ($memberPackageId <= 0) {
        jsonResponse(false, 'Thông tin đăng ký gói tập không hợp lệ.');
    }

    $checkStmt = $db->prepare("SELECT id FROM member_packages WHERE id = ? AND member_id = ? AND status = 'active' LIMIT 1");
    $checkStmt->execute([$memberPackageId, $memberId]);
    $row = $checkStmt->fetch();

    if (!$row) {
        jsonResponse(false, 'Không tìm thấy gói tập đang hoạt động để huỷ.');
    }

    $updateStmt = $db->prepare("UPDATE member_packages SET status = 'cancelled' WHERE id = ? AND member_id = ?");
    if ($updateStmt->execute([$memberPackageId, $memberId])) {
        $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
        $notifyStmt->execute([$userId, 'Huỷ đăng ký gói tập', 'Bạn đã huỷ đăng ký một gói tập thành công.']);
        jsonResponse(true, 'Huỷ đăng ký gói tập thành công.', getDashboardData($db, $member));
    }

    jsonResponse(false, 'Lỗi khi huỷ đăng ký gói tập.');
}

if ($action === 'submit_feedback') {
    $content = isset($_POST['content']) ? sanitize($_POST['content']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if ($content === '') {
        jsonResponse(false, 'Vui lòng nhập nội dung feedback.');
    }

    if ($rating < 1 || $rating > 5) {
        jsonResponse(false, 'Rating phải từ 1 đến 5.');
    }

    $stmt = $db->prepare("INSERT INTO feedback (member_id, content, rating, status) VALUES (?, ?, ?, 'new')");
    if ($stmt->execute([$memberId, $content, $rating])) {
        jsonResponse(true, 'Gửi feedback thành công.', getDashboardData($db, $member));
    }

    jsonResponse(false, 'Lỗi khi gửi feedback.');
}

if ($action === 'mark_notification_read') {
    $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    if ($notificationId <= 0) {
        jsonResponse(false, 'Thông báo không hợp lệ.');
    }

    if ($notificationId >= 9900) {
        if (!in_array($notificationId, $_SESSION['demo_read_notifications'], true)) {
            $_SESSION['demo_read_notifications'][] = $notificationId;
        }
        jsonResponse(true, 'Đã đánh dấu thông báo demo là đã đọc.', getDashboardData($db, $member));
    }

    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$notificationId, $userId])) {
        jsonResponse(true, 'Đã đánh dấu thông báo là đã đọc.', getDashboardData($db, $member));
    }

    jsonResponse(false, 'Lỗi khi cập nhật thông báo.');
}

if ($action === 'search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') {
        jsonResponse(true, 'OK', [
            'packages' => [],
            'services' => [],
            'nutrition_plans' => [],
            'promotions' => []
        ]);
    }

    $like = '%' . $q . '%';

    $packageStmt = $db->prepare("SELECT package_name AS title, CONCAT(duration_months, ' tháng - ', FORMAT(price, 0), ' VNĐ') AS subtitle, description FROM membership_packages WHERE status = 'active' AND (package_name LIKE ? OR description LIKE ?) ORDER BY id DESC LIMIT 20");
    $packageStmt->execute([$like, $like]);

    $serviceStmt = $db->prepare("SELECT name AS title, CONCAT(type, ' - ', FORMAT(price, 0), ' VNĐ') AS subtitle, description FROM services WHERE status = 'hoạt động' AND (name LIKE ? OR description LIKE ?) ORDER BY id DESC LIMIT 20");
    $serviceStmt->execute([$like, $like]);

    $nutritionStmt = $db->prepare("SELECT name AS title, CONCAT(type, IFNULL(CONCAT(' - ', calories, ' calo'), '')) AS subtitle, description FROM nutrition_plans WHERE status = 'hoạt động' AND (name LIKE ? OR description LIKE ?) ORDER BY id DESC LIMIT 20");
    $nutritionStmt->execute([$like, $like]);

    $promotionStmt = $db->prepare("SELECT tp.name AS title, CONCAT(tp.discount_type, ' - ', tp.discount_value) AS subtitle, '' AS description FROM tier_promotions tp INNER JOIN members m ON m.tier_id = tp.tier_id WHERE m.id = ? AND tp.status = 'active' AND (tp.name LIKE ?) ORDER BY tp.id DESC LIMIT 20");
    $promotionStmt->execute([$memberId, $like]);

    $result = [
        'packages' => $packageStmt->fetchAll(),
        'services' => $serviceStmt->fetchAll(),
        'nutrition_plans' => $nutritionStmt->fetchAll(),
        'promotions' => $promotionStmt->fetchAll()
    ];

    $fallback = getHardcodedFallbackData($member);

    if (empty($result['packages'])) {
        $result['packages'] = array_values(array_filter(array_map(function ($item) {
            return [
                'title' => $item['package_name'],
                'subtitle' => $item['duration_months'] . ' tháng - ' . number_format($item['price'], 0, ',', '.') . ' VNĐ',
                'description' => $item['description']
            ];
        }, $fallback['packages']), function ($row) use ($q) {
            $text = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['description'] ?? ''));
            return mb_strpos($text, mb_strtolower($q)) !== false;
        }));
    }

    if (empty($result['services'])) {
        $result['services'] = array_values(array_filter(array_map(function ($item) {
            return [
                'title' => $item['name'],
                'subtitle' => $item['type'] . ' - ' . number_format($item['price'], 0, ',', '.') . ' VNĐ',
                'description' => $item['description']
            ];
        }, $fallback['services']), function ($row) use ($q) {
            $text = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['description'] ?? ''));
            return mb_strpos($text, mb_strtolower($q)) !== false;
        }));
    }

    if (empty($result['nutrition_plans'])) {
        $result['nutrition_plans'] = array_values(array_filter(array_map(function ($item) {
            return [
                'title' => $item['name'],
                'subtitle' => $item['type'] . ($item['calories'] ? (' - ' . $item['calories'] . ' calo') : ''),
                'description' => $item['description']
            ];
        }, $fallback['nutrition_plans']), function ($row) use ($q) {
            $text = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['description'] ?? ''));
            return mb_strpos($text, mb_strtolower($q)) !== false;
        }));
    }

    if (empty($result['promotions'])) {
        $result['promotions'] = array_values(array_filter(array_map(function ($item) {
            return [
                'title' => $item['name'],
                'subtitle' => $item['discount_type'] . ' - ' . $item['discount_value'],
                'description' => ''
            ];
        }, $fallback['promotions']), function ($row) use ($q) {
            $text = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['subtitle'] ?? ''));
            return mb_strpos($text, mb_strtolower($q)) !== false;
        }));
    }

    jsonResponse(true, 'OK', $result);
}

jsonResponse(false, 'Action không hợp lệ.');
