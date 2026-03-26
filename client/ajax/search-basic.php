<?php
// Tìm kiếm cơ bản theo tên sản phẩm (tìm tương đối, LIKE %keyword%)
header('Content-Type: application/json');
require_once '../../config/db.php';


$keyword = trim($_REQUEST['keyword'] ?? '');

// Trả về rỗng nếu không có từ khóa
if ($keyword === '') {
    echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
    exit;
}

// Giới hạn độ dài từ khóa
if (mb_strlen($keyword) > 100) {
    echo json_encode(['success' => false, 'message' => 'Từ khóa quá dài']);
    exit;
}

try {
    $like = '%' . $keyword . '%';
    $stmt = $conn->prepare(
        "SELECT p.id, p.name, p.selling_price, p.img, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'active' AND p.name LIKE ?
         ORDER BY p.name ASC
         LIMIT 10"
    );
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'selling_price' => (float)$row['selling_price'],
            'img'           => $row['img'],
            'category_name' => $row['category_name'] ?? 'Chưa phân loại'
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data'    => $results,
        'count'   => count($results)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tìm kiếm'
    ]);
}
?>
