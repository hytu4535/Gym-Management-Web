<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/database.php';

function face_json_response(int $status, array $payload): void {
	http_response_code($status);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function has_face_permission(): bool {
	if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
		return false;
	}

	return in_array('MANAGE_ALL', $_SESSION['permissions'], true)
		|| in_array('MANAGE_MEMBERS', $_SESSION['permissions'], true);
}

function call_face_service(string $endpoint, array $payload): array {
	$serviceBaseUrl = rtrim(getenv('FACE_SERVICE_BASE_URL') ?: 'http://127.0.0.1:8000', '/');
	$url = $serviceBaseUrl . '/' . ltrim($endpoint, '/');

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT => 25,
	]);

	$raw = curl_exec($ch);
	$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);

	if ($raw === false) {
		return [
			'ok' => false,
			'status' => 0,
			'error' => 'Không kết nối được AI service: ' . $curlError,
			'data' => null,
		];
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		return [
			'ok' => false,
			'status' => $httpCode,
			'error' => 'AI service trả dữ liệu không hợp lệ',
			'data' => null,
		];
	}

	if ($httpCode < 200 || $httpCode >= 300) {
		return [
			'ok' => false,
			'status' => $httpCode,
			'error' => $decoded['error'] ?? ('AI service lỗi HTTP ' . $httpCode),
			'data' => $decoded,
		];
	}

	return [
		'ok' => true,
		'status' => $httpCode,
		'error' => null,
		'data' => $decoded,
	];
}

if (!isset($_SESSION['admin_logged_in'])) {
	face_json_response(401, ['success' => false, 'message' => 'Bạn chưa đăng nhập quản trị.']);
}

if (!has_face_permission()) {
	face_json_response(403, ['success' => false, 'message' => 'Bạn không có quyền thao tác quét mặt.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	face_json_response(405, ['success' => false, 'message' => 'Phương thức không hợp lệ.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
	$input = $_POST;
}

$memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;
$imageBase64 = trim((string) ($input['image_base64'] ?? ''));
$consent = isset($input['consent']) ? filter_var($input['consent'], FILTER_VALIDATE_BOOLEAN) : false;

if ($memberId <= 0 || $imageBase64 === '') {
	face_json_response(422, ['success' => false, 'message' => 'Thiếu member_id hoặc image_base64.']);
}

try {
	$db = getDB();

	$memberStmt = $db->prepare('SELECT id, full_name, status, face_consent FROM members WHERE id = ? LIMIT 1');
	$memberStmt->execute([$memberId]);
	$member = $memberStmt->fetch(PDO::FETCH_ASSOC);

	if (!$member) {
		face_json_response(404, ['success' => false, 'message' => 'Không tìm thấy hội viên.']);
	}

	if (($member['status'] ?? '') !== 'active') {
		face_json_response(422, ['success' => false, 'message' => 'Chỉ cho phép đăng ký mặt cho hội viên đang hoạt động.']);
	}

	if ((int) ($member['face_consent'] ?? 0) !== 1 && !$consent) {
		face_json_response(422, ['success' => false, 'message' => 'Bạn cần xác nhận hội viên đã đồng ý quét mặt.']);
	}

	$serviceResult = call_face_service('/enroll', [
		'member_id' => $memberId,
		'image_base64' => $imageBase64,
	]);

	if (!$serviceResult['ok']) {
		face_json_response(502, ['success' => false, 'message' => $serviceResult['error']]);
	}

	if (empty($serviceResult['data']['success'])) {
		face_json_response(422, ['success' => false, 'message' => $serviceResult['data']['error'] ?? 'Đăng ký khuôn mặt thất bại.']);
	}

	$consentStmt = $db->prepare(
		"UPDATE members
		 SET face_consent = 1,
			 face_consent_at = COALESCE(face_consent_at, NOW()),
			 face_consent_source = 'admin_face_enroll'
		 WHERE id = ?"
	);
	$consentStmt->execute([$memberId]);

	face_json_response(200, [
		'success' => true,
		'message' => 'Đăng ký khuôn mặt thành công.',
		'data' => [
			'member_id' => $memberId,
			'member_name' => $member['full_name'],
		],
	]);
} catch (Throwable $e) {
	face_json_response(500, ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

