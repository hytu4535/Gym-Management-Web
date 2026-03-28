<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

function json_response($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

function parse_training_datetime($schedule_start_time, $schedule_days)
{
    $timeString = '06:00';
    if (!empty($schedule_start_time)) {
        $normalizedTime = trim((string) $schedule_start_time);
        if (preg_match('/^(\d{1,2}:\d{2})(?::\d{2})?$/', $normalizedTime, $matches)) {
            $timeString = substr($matches[1], 0, 5);
        } elseif (preg_match('/(\d{1,2}:\d{2})/', $normalizedTime, $matches)) {
            $timeString = $matches[1];
        }
    }

    $weekdayMap = [
        'chủ nhật' => 0,
        'chu nhat' => 0,
        'thứ 2' => 1,
        'thu 2' => 1,
        'thứ 3' => 2,
        'thu 3' => 2,
        'thứ 4' => 3,
        'thu 4' => 3,
        'thứ 5' => 4,
        'thu 5' => 4,
        'thứ 6' => 5,
        'thu 6' => 5,
        'thứ 7' => 6,
        'thu 7' => 6,
    ];

    $targetWeekday = null;
    if (!empty($schedule_days)) {
        $normalized = mb_strtolower(trim($schedule_days), 'UTF-8');
        foreach ($weekdayMap as $label => $dayNum) {
            if (mb_strpos($normalized, $label, 0, 'UTF-8') !== false) {
                $targetWeekday = $dayNum;
                break;
            }
        }
    }

    $now = new DateTime();
    $candidate = new DateTime($now->format('Y-m-d') . ' ' . $timeString . ':00');

    if ($targetWeekday !== null) {
        $currentWeekday = (int) $now->format('w');
        $delta = $targetWeekday - $currentWeekday;
        if ($delta < 0) {
            $delta += 7;
        }
        $candidate->modify('+' . $delta . ' day');
    }

    if ($candidate <= $now) {
        $candidate->modify('+7 day');
    }

    return $candidate->format('Y-m-d H:i:s');
}

function supportsStructuredScheduleTime(mysqli $conn)
{
    static $supportsStructured = null;

    if ($supportsStructured !== null) {
      return $supportsStructured;
    }

    $databaseResult = $conn->query('SELECT DATABASE() AS db_name');
    $databaseRow = $databaseResult ? $databaseResult->fetch_assoc() : null;
    $databaseName = $databaseRow['db_name'] ?? '';

    if ($databaseName === '') {
        $supportsStructured = false;
        return $supportsStructured;
    }

    $databaseNameEscaped = $conn->real_escape_string($databaseName);
    $columnResult = $conn->query(
        "SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = '{$databaseNameEscaped}'
           AND TABLE_NAME = 'class_schedules'
           AND COLUMN_NAME IN ('schedule_start_time', 'schedule_end_time')"
    );
    $columnRow = $columnResult ? $columnResult->fetch_assoc() : null;

    $supportsStructured = ((int) ($columnRow['column_count'] ?? 0) === 2);
    return $supportsStructured;
}

if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Vui lòng đăng nhập.');
}

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'register';
$userId = (int) $_SESSION['user_id'];

if ($classId <= 0) {
    json_response(false, 'Thiếu thông tin lớp tập.');
}

if ($action !== 'register' && $action !== 'cancel') {
    json_response(false, 'Hành động không hợp lệ.');
}

$memberStmt = $conn->prepare('SELECT id FROM members WHERE users_id = ? LIMIT 1');
if (!$memberStmt) {
    json_response(false, 'Không thể xử lý yêu cầu.');
}
$memberStmt->bind_param('i', $userId);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();
$member = $memberResult->fetch_assoc();
$memberStmt->close();

if (!$member) {
    json_response(false, 'Không tìm thấy hồ sơ hội viên.');
}

$memberId = (int) $member['id'];

try {
    $conn->begin_transaction();

    $structuredScheduleTime = supportsStructuredScheduleTime($conn);

    if ($structuredScheduleTime) {
        $classSql =
            'SELECT id, class_name, class_type, trainer_id, schedule_start_time, schedule_end_time, schedule_days, capacity, enrolled_count, status
             FROM class_schedules
             WHERE id = ?
             FOR UPDATE';
    } else {
        $classSql =
            'SELECT id, class_name, class_type, trainer_id, schedule_start_time, schedule_end_time, schedule_days, capacity, enrolled_count, status
             FROM class_schedules
             WHERE id = ?
             FOR UPDATE';
    }

    $classStmt = $conn->prepare($classSql);
    if (!$classStmt) {
        throw new Exception('Không thể đọc thông tin lớp.');
    }

    $classStmt->bind_param('i', $classId);
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    $class = $classResult->fetch_assoc();
    $classStmt->close();

    if (!$class || $class['status'] !== 'active') {
        throw new Exception('Lớp tập không tồn tại hoặc đã đóng.');
    }

    $registrationStmt = $conn->prepare(
        'SELECT id, status
         FROM class_registrations
         WHERE member_id = ? AND class_id = ?
         FOR UPDATE'
    );
    if (!$registrationStmt) {
        throw new Exception('Không thể kiểm tra đăng ký lớp.');
    }

    $registrationStmt->bind_param('ii', $memberId, $classId);
    $registrationStmt->execute();
    $registration = $registrationStmt->get_result()->fetch_assoc();
    $registrationStmt->close();

    $noteTag = '[CLASS_ID:' . $classId . ']';
    $scheduleNote = 'Lịch tạo tự động từ lớp ' . $class['class_name'] . ' ' . $noteTag;

    if ($action === 'register') {
        if ($registration && $registration['status'] === 'active') {
            throw new Exception('Bạn đã đăng ký lớp này rồi.');
        }

        if ((int) $class['enrolled_count'] >= (int) $class['capacity']) {
            throw new Exception('Lớp tập đã đầy.');
        }

        if ($registration) {
            $reactivateStmt = $conn->prepare(
                'UPDATE class_registrations
                 SET status = "active", registered_at = NOW()
                 WHERE id = ?'
            );
            if (!$reactivateStmt) {
                throw new Exception('Không thể cập nhật đăng ký lớp.');
            }
            $registrationId = (int) $registration['id'];
            $reactivateStmt->bind_param('i', $registrationId);
            $reactivateStmt->execute();
            $reactivateStmt->close();
        } else {
            $insertRegStmt = $conn->prepare(
                'INSERT INTO class_registrations (member_id, class_id, registered_at, status)
                 VALUES (?, ?, NOW(), "active")'
            );
            if (!$insertRegStmt) {
                throw new Exception('Không thể tạo đăng ký lớp.');
            }
            $insertRegStmt->bind_param('ii', $memberId, $classId);
            $insertRegStmt->execute();
            $insertRegStmt->close();
        }

        $increaseStmt = $conn->prepare(
            'UPDATE class_schedules
             SET enrolled_count = enrolled_count + 1
             WHERE id = ?'
        );
        if (!$increaseStmt) {
            throw new Exception('Không thể cập nhật sĩ số lớp.');
        }
        $increaseStmt->bind_param('i', $classId);
        $increaseStmt->execute();
        $increaseStmt->close();

        $trainingDate = parse_training_datetime($class['schedule_start_time'] ?? '', $class['schedule_days']);

        $findScheduleStmt = $conn->prepare(
            'SELECT id, status
             FROM member_training_schedules
             WHERE member_id = ? AND note LIKE ?
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE'
        );
        if (!$findScheduleStmt) {
            throw new Exception('Không thể kiểm tra lịch tập cá nhân.');
        }

        $noteLike = '%' . $noteTag . '%';
        $findScheduleStmt->bind_param('is', $memberId, $noteLike);
        $findScheduleStmt->execute();
        $existingSchedule = $findScheduleStmt->get_result()->fetch_assoc();
        $findScheduleStmt->close();

        if ($existingSchedule) {
            $scheduleId = (int) $existingSchedule['id'];
            $updateScheduleStmt = $conn->prepare(
                'UPDATE member_training_schedules
                 SET trainer_id = ?,
                     training_date = ?,
                     duration = 60,
                     activity_type = ?,
                     status = "dự kiến",
                     note = ?
                 WHERE id = ?'
            );
            if (!$updateScheduleStmt) {
                throw new Exception('Không thể cập nhật lịch tập cá nhân.');
            }
            $trainerId = $class['trainer_id'] !== null ? (int) $class['trainer_id'] : null;
            $activityType = (string) $class['class_type'];
            $updateScheduleStmt->bind_param('isssi', $trainerId, $trainingDate, $activityType, $scheduleNote, $scheduleId);
            $updateScheduleStmt->execute();
            $updateScheduleStmt->close();
        } else {
            $insertScheduleStmt = $conn->prepare(
                'INSERT INTO member_training_schedules
                 (member_id, trainer_id, training_date, duration, activity_type, status, note)
                 VALUES (?, ?, ?, 60, ?, "dự kiến", ?)'
            );
            if (!$insertScheduleStmt) {
                throw new Exception('Không thể tạo lịch tập cá nhân.');
            }
            $trainerId = $class['trainer_id'] !== null ? (int) $class['trainer_id'] : null;
            $activityType = (string) $class['class_type'];
            $insertScheduleStmt->bind_param('iisss', $memberId, $trainerId, $trainingDate, $activityType, $scheduleNote);
            $insertScheduleStmt->execute();
            $insertScheduleStmt->close();
        }

        $conn->commit();
        json_response(true, 'Đăng ký lớp tập thành công và đã thêm vào lịch tập cá nhân.');
    }

    if (!$registration || $registration['status'] !== 'active') {
        throw new Exception('Không tìm thấy đăng ký đang hoạt động để hủy.');
    }

    $cancelRegStmt = $conn->prepare(
        'UPDATE class_registrations
         SET status = "cancelled"
         WHERE id = ?'
    );
    if (!$cancelRegStmt) {
        throw new Exception('Không thể cập nhật trạng thái đăng ký.');
    }
    $registrationId = (int) $registration['id'];
    $cancelRegStmt->bind_param('i', $registrationId);
    $cancelRegStmt->execute();
    $cancelRegStmt->close();

    $decreaseStmt = $conn->prepare(
        'UPDATE class_schedules
         SET enrolled_count = GREATEST(0, enrolled_count - 1)
         WHERE id = ?'
    );
    if (!$decreaseStmt) {
        throw new Exception('Không thể cập nhật sĩ số lớp.');
    }
    $decreaseStmt->bind_param('i', $classId);
    $decreaseStmt->execute();
    $decreaseStmt->close();

    $cancelScheduleStmt = $conn->prepare(
        'UPDATE member_training_schedules
         SET status = "huỷ"
         WHERE member_id = ? AND note LIKE ? AND status <> "huỷ"'
    );
    if (!$cancelScheduleStmt) {
        throw new Exception('Không thể cập nhật lịch tập cá nhân.');
    }
    $noteLike = '%' . $noteTag . '%';
    $cancelScheduleStmt->bind_param('is', $memberId, $noteLike);
    $cancelScheduleStmt->execute();
    $cancelScheduleStmt->close();

    $conn->commit();
    json_response(true, 'Hủy đăng ký lớp tập thành công.');
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Ignore rollback errors to preserve original business error response.
    }

    json_response(false, $e->getMessage());
}
