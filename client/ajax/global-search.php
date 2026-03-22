<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) > 100) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

// Products
$stmt = $conn->prepare(
    "SELECT id, name, selling_price FROM products
     WHERE status = 'active' AND name LIKE ? ORDER BY name ASC LIMIT 5"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $results[] = [
        'group' => 'Sản phẩm',
        'icon'  => 'fa-shopping-bag',
        'name'  => $row['name'],
        'sub'   => number_format((float)$row['selling_price'], 0, ',', '.') . ' VNĐ',
        'url'   => 'product-detail.php?id=' . (int)$row['id'],
    ];
}
$stmt->close();

// Membership packages
$stmt = $conn->prepare(
    "SELECT id, package_name, price FROM membership_packages
     WHERE status = 'active' AND package_name LIKE ? ORDER BY package_name ASC LIMIT 4"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $results[] = [
        'group' => 'Gói tập',
        'icon'  => 'fa-ticket',
        'name'  => $row['package_name'],
        'sub'   => number_format((float)$row['price'], 0, ',', '.') . ' VNĐ',
        'url'   => 'packages.php',
    ];
}
$stmt->close();

// Trainers
$stmt = $conn->prepare(
    "SELECT id, full_name FROM trainers
     WHERE status = 'hoạt động' AND full_name LIKE ? ORDER BY full_name ASC LIMIT 4"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $results[] = [
        'group' => 'Huấn luyện viên',
        'icon'  => 'fa-user',
        'name'  => $row['full_name'],
        'sub'   => 'Xem hồ sơ',
        'url'   => 'trainers.php',
    ];
}
$stmt->close();

// Services
$stmt = $conn->prepare(
    "SELECT id, name, price FROM services
     WHERE status = 'hoạt động' AND name LIKE ? ORDER BY name ASC LIMIT 4"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $results[] = [
        'group' => 'Dịch vụ',
        'icon'  => 'fa-star-o',
        'name'  => $row['name'],
        'sub'   => number_format((float)$row['price'], 0, ',', '.') . ' VNĐ',
        'url'   => 'services.php',
    ];
}
$stmt->close();

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
?>
