<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/database.php';

function face_verify_response(int $status, array $payload): void {
	http_response_code($status);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function can_verify_face(): bool {
	if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
		return false;
	}

	return in_array('MANAGE_ALL', $_SESSION['permissions'], true)
		|| in_array('MANAGE_MEMBERS', $_SESSION['permissions'], true);
}

function request_face_service_verify(array $payload): array {
	$serviceBaseUrl = rtrim(getenv('FACE_SERVICE_BASE_URL') ?: 'http://127.0.0.1:8000', '/');
	$ch = curl_init($serviceBaseUrl . '/verify');
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

function evaluate_member_package_status(PDO $db, int $memberId): array {
	$stmt = $db->prepare(
		"SELECT mp.id, mp.start_date, mp.end_date, mp.status, pkg.package_name
		 FROM member_packages mp
		 LEFT JOIN membership_packages pkg ON pkg.id = mp.package_id
		 WHERE mp.member_id = ? AND mp.status <> 'cancelled'
		 ORDER BY mp.end_date DESC, mp.id DESC
		 LIMIT 1"
	);
	$stmt->execute([$memberId]);
	$pkg = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$pkg) {
		return [
			'has_package' => false,
			'blocked' => false,
			'warning_message' => null,
			'package_name' => null,
			'end_date' => null,
			'days_left' => null,
		];
	}

	$today = new DateTimeImmutable('today');
	$endDate = DateTimeImmutable::createFromFormat('Y-m-d', (string) $pkg['end_date']) ?: $today;
	$daysLeft = (int) $today->diff($endDate)->format('%r%a');
	$isExpired = $daysLeft < 0;
	$isExpiringSoon = !$isExpired && $daysLeft <= 1;

	$warningMessage = null;
	if ($isExpiringSoon) {
		$warningMessage = $daysLeft === 1
			? 'Gói tập của hội viên sẽ hết hạn sau 1 ngày. Vui lòng nhắc gia hạn.'
			: 'Gói tập của hội viên sẽ hết hạn hôm nay. Vui lòng nhắc gia hạn.';
	}

	return [
		'has_package' => true,
		'blocked' => $isExpired,
		'warning_message' => $warningMessage,
		'package_name' => $pkg['package_name'] ?? null,
		'end_date' => $pkg['end_date'] ?? null,
		'days_left' => $daysLeft,
	];
}

if (!isset($_SESSION['admin_logged_in'])) {
	face_verify_response(401, ['success' => false, 'message' => 'Bạn chưa đăng nhập quản trị.']);
}

if (!can_verify_face()) {
	face_verify_response(403, ['success' => false, 'message' => 'Bạn không có quyền thao tác quét mặt.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	face_verify_response(405, ['success' => false, 'message' => 'Phương thức không hợp lệ.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
	$input = $_POST;
}

$imageBase64 = trim((string) ($input['image_base64'] ?? ''));
$threshold = isset($input['threshold']) ? (float) $input['threshold'] : 0.85;

if ($imageBase64 === '') {
	face_verify_response(422, ['success' => false, 'message' => 'Thiếu image_base64.']);
}

if ($threshold <= 0 || $threshold > 1) {
	$threshold = 0.85;
}

try {
	$serviceResult = request_face_service_verify([
		'image_base64' => $imageBase64,
		'threshold' => $threshold,
	]);

	if (!$serviceResult['ok']) {
		face_verify_response(502, ['success' => false, 'message' => $serviceResult['error']]);
	}

	$data = $serviceResult['data'];
	$verified = !empty($data['verified']);
	$memberId = isset($data['member_id']) ? (int) $data['member_id'] : null;
	$confidence = isset($data['confidence']) ? (float) $data['confidence'] : 0.0;

	$memberName = null;
	$packageMeta = [
		'has_package' => false,
		'blocked' => false,
		'warning_message' => null,
		'package_name' => null,
		'end_date' => null,
		'days_left' => null,
	];
	if ($verified && $memberId) {
		$db = getDB();
		$stmt = $db->prepare('SELECT id, full_name, status FROM members WHERE id = ? LIMIT 1');
		$stmt->execute([$memberId]);
		$member = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$member || ($member['status'] ?? '') !== 'active') {
			face_verify_response(200, [
				'success' => true,
				'verified' => false,
				'message' => 'Nhận diện được nhưng hội viên không hoạt động.',
				'data' => [
					'member_id' => $memberId,
					'confidence' => round($confidence, 4),
					'threshold' => $threshold,
				],
			]);
		}

		$memberName = $member['full_name'];
		$packageMeta = evaluate_member_package_status($db, $memberId);

		if (!empty($packageMeta['blocked'])) {
			face_verify_response(200, [
				'success' => true,
				'verified' => false,
				'blocked_due_to_package' => true,
				'message' => 'Gói tập của bạn đã hết hạn, vui lòng gia hạn để tiếp tục điểm danh.',
				'data' => [
					'member_id' => $memberId,
					'member_name' => $memberName,
					'confidence' => round($confidence, 4),
					'threshold' => $threshold,
					'package_name' => $packageMeta['package_name'],
					'package_end_date' => $packageMeta['end_date'],
					'package_days_left' => $packageMeta['days_left'],
				],
			]);
		}
	}

	face_verify_response(200, [
		'success' => true,
		'verified' => $verified,
		'blocked_due_to_package' => false,
		'message' => $verified ? 'Xác thực thành công.' : 'Không nhận diện được khuôn mặt phù hợp.',
		'data' => [
			'member_id' => $verified ? $memberId : null,
			'member_name' => $verified ? $memberName : null,
			'confidence' => round($confidence, 4),
			'threshold' => $threshold,
			'package_name' => $verified ? $packageMeta['package_name'] : null,
			'package_end_date' => $verified ? $packageMeta['end_date'] : null,
			'package_days_left' => $verified ? $packageMeta['days_left'] : null,
			'package_warning' => $verified ? $packageMeta['warning_message'] : null,
			'reason' => $data['reason'] ?? null,
		],
	]);
} catch (Throwable $e) {
	face_verify_response(500, ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

