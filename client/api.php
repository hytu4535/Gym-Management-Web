<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

function jsonResponse($success, $message = '', $data = [])
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function resolveMember(PDO $db, $memberIdInput)
{
    $memberId = intval($memberIdInput);
    if ($memberId > 0) {
        $stmt = $db->prepare('SELECT * FROM members WHERE id = ? LIMIT 1');
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($member) {
            return $member;
        }
    }

    $sessionUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    if ($sessionUserId > 0) {
        $stmt = $db->prepare('SELECT * FROM members WHERE users_id = ? LIMIT 1');
        $stmt->execute([$sessionUserId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($member) {
            return $member;
        }
    }

    $fallbackStmt = $db->query('SELECT * FROM members ORDER BY id ASC LIMIT 1');
    $fallback = $fallbackStmt->fetch(PDO::FETCH_ASSOC);

    return $fallback ?: null;
}

function calculateNutritionPlanCalories(PDO $db, int $nutritionPlanId): ?int
{
    if ($nutritionPlanId <= 0) {
        return null;
    }

    $stmt = $db->prepare(
        "SELECT SUM(ni.calories * npi.servings_per_day) AS calc
         FROM nutrition_plan_items npi
         JOIN nutrition_items ni ON ni.id = npi.item_id
         WHERE npi.nutrition_plan_id = ?"
    );
    $stmt->execute([$nutritionPlanId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($row['calc']) ? (int) $row['calc'] : null;
}

function getDashboardData(PDO $db, $member)
{
    $memberId = (int) $member['id'];
    $userId = (int) $member['users_id'];
    $today = date('Y-m-d');

    $packageStmt = $db->prepare(
        "SELECT id, package_name, duration_months, price, description
         FROM membership_packages
         WHERE status = 'active'
         ORDER BY id DESC"
    );
    $packageStmt->execute();
    $packages = $packageStmt->fetchAll(PDO::FETCH_ASSOC);

    $regStmt = $db->prepare(
        "SELECT id AS member_package_id, package_id
         FROM member_packages
         WHERE member_id = ? AND status = 'active'"
    );
    $regStmt->execute([$memberId]);
    $registered = $regStmt->fetchAll(PDO::FETCH_ASSOC);

    $registeredMap = [];
    foreach ($registered as $item) {
        $registeredMap[(int) $item['package_id']] = [
            'already_registered' => true,
            'member_package_id' => (int) $item['member_package_id'],
        ];
    }

    foreach ($packages as &$package) {
        $pid = (int) $package['id'];
        $package['already_registered'] = isset($registeredMap[$pid]);
        $package['member_package_id'] = $registeredMap[$pid]['member_package_id'] ?? null;
    }
    unset($package);

    $memberPackagesStmt = $db->prepare(
        "SELECT mp.id AS member_package_id,
                mp.package_id,
                mp.start_date,
                mp.end_date,
                mp.status,
                p.package_name
         FROM member_packages mp
         INNER JOIN membership_packages p ON p.id = mp.package_id
         WHERE mp.member_id = ?
         ORDER BY mp.id DESC"
    );
    $memberPackagesStmt->execute([$memberId]);
    $memberPackages = $memberPackagesStmt->fetchAll(PDO::FETCH_ASSOC);

    $servicesStmt = $db->query(
        "SELECT id, name, type, price, description
         FROM services
         WHERE status = 'hoạt động'
         ORDER BY id DESC"
    );
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

    $nutritionStmt = $db->query(
        "SELECT id, name, type, calories, bmi_range, description
         FROM nutrition_plans
         WHERE status = 'hoạt động'
         ORDER BY id DESC"
    );
    $nutritionPlans = $nutritionStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($nutritionPlans as &$nutritionPlan) {
        $planCalories = isset($nutritionPlan['calories']) ? (float) $nutritionPlan['calories'] : 0.0;
        if ($planCalories <= 0) {
            $computedCalories = calculateNutritionPlanCalories($db, (int) $nutritionPlan['id']);
            if ($computedCalories !== null) {
                $nutritionPlan['calories'] = $computedCalories;
            }
        }
    }
    unset($nutritionPlan);

    $promoStmt = $db->prepare(
        "SELECT id, name, discount_type, discount_value, start_date, end_date, usage_limit
         FROM tier_promotions
         WHERE tier_id = ?
           AND status = 'active'
           AND ? BETWEEN start_date AND end_date
         ORDER BY id DESC"
    );
    $promoStmt->execute([(int) $member['tier_id'], $today]);
    $promotions = $promoStmt->fetchAll(PDO::FETCH_ASSOC);

    $feedbackStmt = $db->prepare(
        "SELECT id, content, rating, status, created_at
         FROM feedback
         WHERE member_id = ?
         ORDER BY created_at DESC"
    );
    $feedbackStmt->execute([$memberId]);
    $feedbacks = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);

    $notiStmt = $db->prepare(
        "SELECT id, title, content, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $notiStmt->execute([$userId]);
    $notifications = $notiStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'member' => $member,
        'packages' => $packages,
        'member_packages' => $memberPackages,
        'services' => $services,
        'nutrition_plans' => $nutritionPlans,
        'promotions' => $promotions,
        'feedbacks' => $feedbacks,
        'notifications' => $notifications,
    ];
}

function handleRegisterPackage(PDO $db, $member, $packageId)
{
    $memberId = (int) $member['id'];
    $packageId = intval($packageId);

    if ($packageId <= 0) {
        jsonResponse(false, 'Gói tập không hợp lệ.');
    }

    $packageStmt = $db->prepare(
        "SELECT id, duration_months
         FROM membership_packages
         WHERE id = ? AND status = 'active'
         LIMIT 1"
    );
    $packageStmt->execute([$packageId]);
    $package = $packageStmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        jsonResponse(false, 'Gói tập không tồn tại hoặc đã ngừng hoạt động.');
    }

    $activeStmt = $db->prepare(
        "SELECT mp.id, p.package_name
         FROM member_packages mp
         INNER JOIN membership_packages p ON p.id = mp.package_id
         WHERE mp.member_id = ? AND mp.status = 'active' AND mp.end_date >= CURDATE()
         ORDER BY mp.end_date DESC, mp.id DESC
         LIMIT 1"
    );
    $activeStmt->execute([$memberId]);
    $activePackage = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if ($activePackage) {
        jsonResponse(false, 'Bạn đang có gói tập hoạt động. Vui lòng chờ gói cũ hết hạn hoặc hủy trước khi đăng ký gói mới.');
    }

    $checkStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM member_packages
         WHERE member_id = ? AND package_id = ? AND status = 'active'"
    );
    $checkStmt->execute([$memberId, $packageId]);

    if ((int) $checkStmt->fetchColumn() > 0) {
        jsonResponse(false, 'Bạn đã đăng ký gói tập này rồi.');
    }

    $startDate = new DateTime('today');
    $endDate = (clone $startDate)->modify('+' . intval($package['duration_months']) . ' month');

    $insertStmt = $db->prepare(
        "INSERT INTO member_packages (member_id, package_id, start_date, end_date, status)
         VALUES (?, ?, ?, ?, 'active')"
    );

    $ok = $insertStmt->execute([
        $memberId,
        $packageId,
        $startDate->format('Y-m-d'),
        $endDate->format('Y-m-d'),
    ]);

    if ($ok) {
        jsonResponse(true, 'Đăng ký gói tập thành công.');
    }

    jsonResponse(false, 'Không thể đăng ký gói tập lúc này.');
}

function handleCancelPackage(PDO $db, $member, $memberPackageId, $packageId)
{
    $memberId = (int) $member['id'];
    $memberPackageId = intval($memberPackageId);
    $packageId = intval($packageId);

    if ($memberPackageId > 0) {
        $stmt = $db->prepare(
            "UPDATE member_packages
             SET status = 'cancelled'
             WHERE id = ? AND member_id = ? AND status = 'active'"
        );
        $stmt->execute([$memberPackageId, $memberId]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Huỷ đăng ký gói tập thành công.');
        }
    }

    if ($packageId > 0) {
        $stmt = $db->prepare(
            "UPDATE member_packages
             SET status = 'cancelled'
             WHERE member_id = ? AND package_id = ? AND status = 'active'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$memberId, $packageId]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Huỷ đăng ký gói tập thành công.');
        }
    }

    jsonResponse(false, 'Không tìm thấy gói tập còn hiệu lực để huỷ.');
}

function handleDeleteNotification(PDO $db, $member, $notificationId)
{
    $notificationId = intval($notificationId);
    if ($notificationId <= 0) {
        jsonResponse(false, 'Thông báo không hợp lệ.');
    }

    // Only allow deleting read notifications
    $stmt = $db->prepare(
        "DELETE FROM notifications WHERE id = ? AND user_id = ? AND is_read = 1"
    );
    $stmt->execute([$notificationId, (int) $member['users_id']]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(true, 'Đã xoá thông báo.');
    }

    jsonResponse(false, 'Không thể xoá thông báo (chỉ xoá được thông báo đã đọc).');
}

function handleMarkNotificationRead(PDO $db, $member, $notificationId)
{
    $notificationId = intval($notificationId);
    if ($notificationId <= 0) {
        jsonResponse(false, 'Thông báo không hợp lệ.');
    }

    $stmt = $db->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$notificationId, (int) $member['users_id']]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(true, 'Đã đánh dấu thông báo.');
    }

    jsonResponse(false, 'Không tìm thấy thông báo để cập nhật.');
}

function handleUnreadNotificationCount(PDO $db, $member)
{
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM notifications
         WHERE user_id = ? AND is_read = 0"
    );
    $stmt->execute([(int) $member['users_id']]);
    $count = (int) $stmt->fetchColumn();

    jsonResponse(true, 'OK', [
        'unread_count' => $count,
    ]);
}

function handleSubmitFeedback(PDO $db, $member, $rating, $content)
{
    $rating = intval($rating);
    $content = trim((string) $content);

    if ($rating < 1 || $rating > 5) {
        jsonResponse(false, 'Mức đánh giá không hợp lệ.');
    }

    if ($content === '') {
        jsonResponse(false, 'Nội dung feedback không được để trống.');
    }

    $stmt = $db->prepare(
        "INSERT INTO feedback (member_id, content, rating, status)
         VALUES (?, ?, ?, 'new')"
    );

    $ok = $stmt->execute([(int) $member['id'], $content, $rating]);
    if ($ok) {
        // Gửi thông báo đến tất cả tài khoản admin đang hoạt động.
        $adminStmt = $db->query(
            "SELECT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'admin' AND u.status = 'active'"
        );
        $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($adminIds)) {
            $title = 'Feedback mới từ hội viên';
            $contentText = 'Hội viên ' . ($member['full_name'] ?? ('#' . (int) $member['id']))
                . ' vừa gửi feedback mới. Vui lòng kiểm tra mục Phản hồi.';

            $notifyStmt = $db->prepare(
                "INSERT INTO notifications (user_id, title, content, is_read)
                 VALUES (?, ?, ?, 0)"
            );

            foreach ($adminIds as $adminId) {
                $notifyStmt->execute([(int) $adminId, $title, $contentText]);
            }
        }

        jsonResponse(true, 'Gửi feedback thành công.');
    }

    jsonResponse(false, 'Không thể gửi feedback lúc này.');
}

function handleSearch(PDO $db, $member, $keyword)
{
    $keyword = trim((string) $keyword);
    if ($keyword === '') {
        jsonResponse(true, 'OK', [
            'packages' => [],
            'services' => [],
            'nutrition_plans' => [],
            'promotions' => [],
        ]);
    }

    $like = '%' . $keyword . '%';
    $today = date('Y-m-d');

    $packageStmt = $db->prepare(
        "SELECT id, package_name, description
         FROM membership_packages
         WHERE status = 'active' AND (package_name LIKE ? OR description LIKE ?)
         ORDER BY id DESC
         LIMIT 20"
    );
    $packageStmt->execute([$like, $like]);
    $packages = $packageStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($packages as &$item) {
        $item['title'] = $item['package_name'];
        $item['subtitle'] = $item['description'] ?? '';
    }
    unset($item);

    $serviceStmt = $db->prepare(
        "SELECT id, name, description
         FROM services
         WHERE status = 'hoạt động' AND (name LIKE ? OR description LIKE ?)
         ORDER BY id DESC
         LIMIT 20"
    );
    $serviceStmt->execute([$like, $like]);
    $services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as &$item) {
        $item['title'] = $item['name'];
        $item['subtitle'] = $item['description'] ?? '';
    }
    unset($item);

    $nutritionStmt = $db->prepare(
        "SELECT id, name, description
         FROM nutrition_plans
         WHERE status = 'hoạt động' AND (name LIKE ? OR description LIKE ?)
         ORDER BY id DESC
         LIMIT 20"
    );
    $nutritionStmt->execute([$like, $like]);
    $nutritionPlans = $nutritionStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($nutritionPlans as &$item) {
        $item['title'] = $item['name'];
        $item['subtitle'] = $item['description'] ?? '';
    }
    unset($item);

    $promoStmt = $db->prepare(
        "SELECT id, name
         FROM tier_promotions
         WHERE tier_id = ?
           AND status = 'active'
           AND ? BETWEEN start_date AND end_date
           AND name LIKE ?
         ORDER BY id DESC
         LIMIT 20"
    );
    $promoStmt->execute([(int) $member['tier_id'], $today, $like]);
    $promotions = $promoStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($promotions as &$item) {
        $item['title'] = $item['name'];
        $item['subtitle'] = 'Ưu đãi dành cho hạng thành viên của bạn';
    }
    unset($item);

    jsonResponse(true, 'OK', [
        'packages' => $packages,
        'services' => $services,
        'nutrition_plans' => $nutritionPlans,
        'promotions' => $promotions,
    ]);
}

function handleNutritionItems(PDO $db)
{
    $stmt = $db->query(
        "SELECT id, name, serving_desc, calories, protein, carbs, fat, notes
         FROM nutrition_items
         WHERE status = 'hoạt động'
         ORDER BY id DESC"
    );
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'OK', [
        'items' => $items,
    ]);
}

function handlePlanMeals(PDO $db, $nutritionPlanId)
{
    $nutritionPlanId = intval($nutritionPlanId);
    if ($nutritionPlanId <= 0) {
        jsonResponse(false, 'Kế hoạch dinh dưỡng không hợp lệ.');
    }

    $planStmt = $db->prepare(
        "SELECT id, name, type, calories, bmi_range, description
         FROM nutrition_plans
         WHERE id = ?
         LIMIT 1"
    );
    $planStmt->execute([$nutritionPlanId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        jsonResponse(false, 'Không tìm thấy kế hoạch dinh dưỡng.');
    }

    $planCalories = isset($plan['calories']) ? (float) $plan['calories'] : 0.0;
    if ($planCalories <= 0) {
        $computedCalories = calculateNutritionPlanCalories($db, $nutritionPlanId);
        if ($computedCalories !== null) {
            $plan['calories'] = $computedCalories;
        }
    }

    $itemsStmt = $db->prepare(
        "SELECT npi.id,
                npi.nutrition_plan_id,
                npi.item_id,
                npi.servings_per_day,
                npi.meal_time,
                npi.note,
                ni.name,
                ni.serving_desc,
                ni.calories,
                ni.protein,
                ni.carbs,
                ni.fat,
                ni.notes
         FROM nutrition_plan_items npi
         INNER JOIN nutrition_items ni ON ni.id = npi.item_id
         WHERE npi.nutrition_plan_id = ?
         ORDER BY npi.id ASC"
    );
    $itemsStmt->execute([$nutritionPlanId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'OK', [
        'plan' => $plan,
        'items' => $items,
    ]);
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $member = resolveMember($db, $_POST['member_id'] ?? $_GET['member_id'] ?? 0);

    if (!$member) {
        jsonResponse(false, 'Không tìm thấy hội viên để xử lý yêu cầu.');
    }

    switch ($action) {
        case 'dashboard':
            jsonResponse(true, 'OK', getDashboardData($db, $member));
            break;

        case 'register_package':
            handleRegisterPackage($db, $member, $_POST['package_id'] ?? 0);
            break;

        case 'cancel_package':
            handleCancelPackage($db, $member, $_POST['member_package_id'] ?? 0, $_POST['package_id'] ?? 0);
            break;

        case 'mark_notification_read':
            handleMarkNotificationRead($db, $member, $_POST['notification_id'] ?? 0);
            break;

        case 'unread_notification_count':
            handleUnreadNotificationCount($db, $member);
            break;

        case 'delete_notification':
            handleDeleteNotification($db, $member, $_POST['notification_id'] ?? 0);
            break;

        case 'submit_feedback':
            handleSubmitFeedback($db, $member, $_POST['rating'] ?? 0, $_POST['content'] ?? '');
            break;

        case 'search':
            handleSearch($db, $member, $_GET['q'] ?? '');
            break;

        case 'nutrition_items':
            handleNutritionItems($db);
            break;

        case 'nutrition_plan_items':
            handlePlanMeals($db, $_GET['nutrition_plan_id'] ?? $_POST['nutrition_plan_id'] ?? 0);
            break;

        default:
            jsonResponse(false, 'Action không hợp lệ.');
    }
} catch (Throwable $e) {
    jsonResponse(false, 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage());
}