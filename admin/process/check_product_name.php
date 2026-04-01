<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'add');

require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$name = trim((string) ($_POST['name'] ?? ''));

if ($name === '') {
    echo json_encode([
        'exists' => false,
        'message' => '',
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))");
$stmt->bind_param('s', $name);
$stmt->execute();
$total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

echo json_encode([
    'exists' => $total > 0,
    'message' => $total > 0 ? 'đã có sản phẩm trùng tên, vui lòng nhập tên khác' : '',
]);
