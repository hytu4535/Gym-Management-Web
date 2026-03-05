<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

function hasCorruptedText($value)
{
    $text = trim((string) $value);
    return $text !== '' && strpos($text, '?') !== false;
}

function packageNameFallbackById($packageId)
{
    $map = [
        1 => 'Gói 1 Tháng',
        2 => 'Gói 3 Tháng',
        3 => 'Gói 6 Tháng',
        4 => 'Gói 12 Tháng'
    ];

    return $map[intval($packageId)] ?? '';
}

function packageDescriptionFallbackById($packageId)
{
    $map = [
        1 => 'Tập gym không giới hạn 1 tháng',
        2 => 'Tiết kiệm hơn so với gói lẻ',
        3 => 'Ưu đãi mạnh cho hội viên lâu dài',
        4 => 'Gói năm - rẻ nhất'
    ];

    return $map[intval($packageId)] ?? '';
}

function normalizeMemberData($member)
{
    if (!is_array($member)) {
        return $member;
    }

    if (array_key_exists('full_name', $member) && hasCorruptedText($member['full_name'])) {
        $member['full_name'] = 'Hoi vien #' . intval($member['id'] ?? 0);
    }

    return $member;
}

function normalizeDashboardData($data)
{
    if (!is_array($data)) {
        return $data;
    }

    if (!empty($data['packages']) && is_array($data['packages'])) {
        foreach ($data['packages'] as &$pkg) {
            $pkgId = intval($pkg['id'] ?? 0);
            if (hasCorruptedText($pkg['package_name'] ?? '')) {
                $fallbackName = packageNameFallbackById($pkgId);
                $pkg['package_name'] = $fallbackName !== '' ? $fallbackName : ('Goi tap #' . $pkgId);
            }
            if (hasCorruptedText($pkg['description'] ?? '')) {
                $fallbackDesc = packageDescriptionFallbackById($pkgId);
                $pkg['description'] = $fallbackDesc !== '' ? $fallbackDesc : 'Mo ta dang duoc cap nhat.';
            }
        }
        unset($pkg);
    }

    if (!empty($data['member_packages']) && is_array($data['member_packages'])) {
        foreach ($data['member_packages'] as &$memberPkg) {
            $pkgId = intval($memberPkg['package_id'] ?? 0);
            if (hasCorruptedText($memberPkg['package_name'] ?? '')) {
                $fallbackName = packageNameFallbackById($pkgId);
                $memberPkg['package_name'] = $fallbackName !== '' ? $fallbackName : ('Goi tap #' . $pkgId);
            }
        }
        unset($memberPkg);
    }

    return $data;
}

function normalizeSearchData($data)
{
    if (!is_array($data)) {
        return $data;
    }

    if (!empty($data['packages']) && is_array($data['packages'])) {
        foreach ($data['packages'] as &$pkg) {
            if (hasCorruptedText($pkg['title'] ?? '')) {
                $pkg['title'] = 'Goi tap';
            }
            if (hasCorruptedText($pkg['description'] ?? '')) {
                $pkg['description'] = 'Mo ta dang duoc cap nhat.';
            }
        }
        unset($pkg);
    }

    return $data;
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
            return normalizeMemberData($member);
        }
    }

    $sessionUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    if ($sessionUserId > 0) {
        $stmt = $db->prepare("SELECT * FROM members WHERE users_id = ? LIMIT 1");
        $stmt->execute([$sessionUserId]);
        $member = $stmt->fetch();
        if ($member) {
            return normalizeMemberData($member);
        }
    }

    $fallbackStmt = $db->query("SELECT * FROM members ORDER BY id ASC LIMIT 1");
    return normalizeMemberData($fallbackStmt->fetch());
}

function getDashboardData(PDO $db, $member)
{
    $memberId = intval($member['id']);
    $userId = intval($member['users_id']);

    $packageStmt = $db->prepare(
        "SELECT mp.id, mp.package_name, mp.duration_months, mp.price, mp.description,
                CASE WHEN EXISTS (
                    SELECT 1 FROM member_packages mmp
                    WHERE mmp.package_id = mp.id AND mmp.member_id = ? AND mmp.status = 'active' AND mmp.end_date >= CURDATE()
                ) THEN 1 ELSE 0 END AS already_registered
         FROM membership_packages mp
         WHERE mp.status = 'active'
         ORDER BY mp.id DESC"
    );
    $packageStmt->execute([$memberId]);
    $packages = $packageStmt->fetchAll();

    $memberPackageStmt = $db->prepare(
        "SELECT mmp.id AS member_package_id, mmp.package_id, mp.package_name, mmp.start_date, mmp.end_date,
                CASE
                    WHEN mmp.status = 'active' AND mmp.end_date < CURDATE() THEN 'expired'
                    ELSE mmp.status
                END AS status
         FROM member_packages mmp
         INNER JOIN membership_packages mp ON mp.id = mmp.package_id
         WHERE mmp.member_id = ?
         ORDER BY mmp.id DESC"
    );
    $memberPackageStmt->execute([$memberId]);
    $memberPackages = $memberPackageStmt->fetchAll();

    $serviceStmt = $db->query("SELECT id, name, type, price, description FROM services WHERE status = 'hoạt động' ORDER BY id DESC");
    $services = $serviceStmt->fetchAll();

    $nutritionStmt = $db->query("SELECT id, name, type, calories, bmi_range, description FROM nutrition_plans WHERE status = 'hoạt động' ORDER BY id DESC");
    $nutritionPlans = $nutritionStmt->fetchAll();

    $promotionStmt = $db->prepare(
        "SELECT tp.id, tp.name, tp.discount_type, tp.discount_value, tp.start_date, tp.end_date, tp.usage_limit
         FROM tier_promotions tp
         INNER JOIN members m ON m.tier_id = tp.tier_id
         WHERE m.id = ?
           AND tp.status = 'active'
           AND CURDATE() BETWEEN tp.start_date AND tp.end_date
         ORDER BY tp.id DESC"
    );
    $promotionStmt->execute([$memberId]);
    $promotions = $promotionStmt->fetchAll();

    $feedbackStmt = $db->prepare("SELECT id, content, rating, status, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at FROM feedback WHERE member_id = ? ORDER BY id DESC");
    $feedbackStmt->execute([$memberId]);
    $feedbacks = $feedbackStmt->fetchAll();

    $notifications = [];
    if ($userId > 0) {
        $notificationStmt = $db->prepare("SELECT id, title, content, is_read, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at FROM notifications WHERE user_id = ? ORDER BY id DESC");
        $notificationStmt->execute([$userId]);
        $notifications = $notificationStmt->fetchAll();
    }

    return [
        'packages' => $packages,
        'member_packages' => $memberPackages,
        'services' => $services,
        'nutrition_plans' => $nutritionPlans,
        'promotions' => $promotions,
        'feedbacks' => $feedbacks,
        'notifications' => $notifications
    ];
}

try {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'dashboard';
    $member = resolveCurrentMember($db);

    if (!$member) {
        jsonResponse(false, 'Không tìm thấy hội viên trong hệ thống.');
    }

    $memberId = intval($member['id']);
    $userId = intval($member['users_id']);

    if ($action === 'dashboard') {
        jsonResponse(true, 'OK', normalizeDashboardData(getDashboardData($db, $member)));
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
        jsonResponse(false, 'Gói tập không tồn tại hoặc không hoạt động.');
    }

    $checkStmt = $db->prepare("SELECT COUNT(*) FROM member_packages WHERE member_id = ? AND package_id = ? AND status = 'active' AND end_date >= CURDATE()");
    $checkStmt->execute([$memberId, $packageId]);
    if ((int) $checkStmt->fetchColumn() > 0) {
        jsonResponse(false, 'Bạn đã đăng ký gói tập này rồi.');
    }

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+' . intval($pkg['duration_months']) . ' months'));

    $insertStmt = $db->prepare("INSERT INTO member_packages (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
    $ok = $insertStmt->execute([$memberId, $packageId, $startDate, $endDate]);

    if ($ok) {
        if ($userId > 0) {
            $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $notifyStmt->execute([$userId, 'Đăng ký gói tập', 'Bạn đã đăng ký thành công gói: ' . $pkg['package_name']]);
        }
        jsonResponse(true, 'Đăng ký gói tập thành công.', normalizeDashboardData(getDashboardData($db, $member)));
    }

    jsonResponse(false, 'Lỗi khi đăng ký gói tập.');
    }

    if ($action === 'cancel_package') {
    $memberPackageId = isset($_POST['member_package_id']) ? intval($_POST['member_package_id']) : 0;

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
        if ($userId > 0) {
            $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $notifyStmt->execute([$userId, 'Huỷ đăng ký gói tập', 'Bạn đã huỷ đăng ký một gói tập thành công.']);
        }
        jsonResponse(true, 'Huỷ đăng ký gói tập thành công.', normalizeDashboardData(getDashboardData($db, $member)));
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
        jsonResponse(true, 'Gửi feedback thành công.', normalizeDashboardData(getDashboardData($db, $member)));
    }

    jsonResponse(false, 'Lỗi khi gửi feedback.');
    }

    if ($action === 'mark_notification_read') {
    $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    if ($notificationId <= 0 || $userId <= 0) {
        jsonResponse(false, 'Thông báo không hợp lệ.');
    }

    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$notificationId, $userId]) && $stmt->rowCount() > 0) {
        jsonResponse(true, 'Đã đánh dấu thông báo là đã đọc.', normalizeDashboardData(getDashboardData($db, $member)));
    }

    jsonResponse(false, 'Không tìm thấy thông báo hợp lệ để cập nhật.');
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

    $promotionStmt = $db->prepare("SELECT tp.name AS title, CONCAT(tp.discount_type, ' - ', tp.discount_value) AS subtitle, '' AS description FROM tier_promotions tp INNER JOIN members m ON m.tier_id = tp.tier_id WHERE m.id = ? AND tp.status = 'active' AND CURDATE() BETWEEN tp.start_date AND tp.end_date AND (tp.name LIKE ?) ORDER BY tp.id DESC LIMIT 20");
    $promotionStmt->execute([$memberId, $like]);

    $result = [
        'packages' => $packageStmt->fetchAll(),
        'services' => $serviceStmt->fetchAll(),
        'nutrition_plans' => $nutritionStmt->fetchAll(),
        'promotions' => $promotionStmt->fetchAll()
    ];

    jsonResponse(true, 'OK', normalizeSearchData($result));
    }

    jsonResponse(false, 'Action không hợp lệ.');
} catch (Throwable $e) {
    error_log('client/api.php error: ' . $e->getMessage());
    jsonResponse(false, 'Có lỗi máy chủ khi xử lý dữ liệu user.');
}
