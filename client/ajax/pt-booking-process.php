<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

function response_json($success, $message)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    response_json(false, 'Vui lòng đăng nhập để đăng ký lịch tập.');
}

$userId = (int) $_SESSION['user_id'];
$trainerId = isset($_POST['trainer_id']) ? (int) $_POST['trainer_id'] : 0;
$trainingDateInput = isset($_POST['training_date']) ? trim($_POST['training_date']) : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if ($trainerId <= 0) {
    response_json(false, 'Vui lòng chọn huấn luyện viên.');
}

if ($trainingDateInput === '') {
    response_json(false, 'Vui lòng chọn thời gian hẹn tập.');
}

$trainingDate = DateTime::createFromFormat('Y-m-d\TH:i', $trainingDateInput);
if (!$trainingDate) {
    response_json(false, 'Thời gian hẹn tập không hợp lệ.');
}

$now = new DateTime();
$minTime = clone $now;
$minTime->modify('+30 minutes');
if ($trainingDate < $minTime) {
    response_json(false, 'Vui lòng đặt lịch trước ít nhất 30 phút.');
}

if (mb_strlen($note, 'UTF-8') > 1000) {
    response_json(false, 'Ghi chú quá dài (tối đa 1000 ký tự).');
}

$memberStmt = $conn->prepare('SELECT id FROM members WHERE users_id = ? LIMIT 1');
$memberStmt->bind_param('i', $userId);
$memberStmt->execute();
$member = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

if (!$member) {
    response_json(false, 'Không tìm thấy hồ sơ hội viên.');
}
$memberId = (int) $member['id'];

$trainerStmt = $conn->prepare('SELECT id FROM trainers WHERE id = ? LIMIT 1');
$trainerStmt->bind_param('i', $trainerId);
$trainerStmt->execute();
$trainer = $trainerStmt->get_result()->fetch_assoc();
$trainerStmt->close();

if (!$trainer) {
    response_json(false, 'Huấn luyện viên không tồn tại.');
}

try {
    $conn->begin_transaction();

    $insertStmt = $conn->prepare(
        'INSERT INTO training_schedules (member_id, trainer_id, training_date, note)
         VALUES (?, ?, ?, ?)'
    );

    if (!$insertStmt) {
        throw new Exception('Không thể tạo lịch hẹn PT.');
    }

    $trainingDateSql = $trainingDate->format('Y-m-d H:i:s');
    $insertStmt->bind_param('iiss', $memberId, $trainerId, $trainingDateSql, $note);
    $insertStmt->execute();
    $insertStmt->close();

    $conn->commit();
    response_json(true, 'Đặt lịch PT thành công.');
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
    }

    response_json(false, 'Đặt lịch thất bại. Vui lòng thử lại.');
}
