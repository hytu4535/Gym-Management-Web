<?php
// Tìm kiếm nâng cao: tên sản phẩm + danh mục + khoảng giá + phân trang
header('Content-Type: application/json');
require_once '../../config/db.php';

$keyword     = trim($_REQUEST['keyword'] ?? '');
$category_id = (isset($_REQUEST['category']) && ctype_digit((string)$_REQUEST['category']))
               ? (int)$_REQUEST['category'] : 0;
$min_price   = (isset($_REQUEST['min_price']) && is_numeric($_REQUEST['min_price']))
               ? (float)$_REQUEST['min_price'] : null;
$max_price   = (isset($_REQUEST['max_price']) && is_numeric($_REQUEST['max_price']))
               ? (float)$_REQUEST['max_price'] : null;
$page        = max(1, (int)($_REQUEST['page'] ?? 1));
$limit       = 12;
$offset      = ($page - 1) * $limit;

if (mb_strlen($keyword) > 100) {
    echo json_encode(['success' => false, 'message' => 'Từ khóa quá dài']);
    exit;
}

try {
    // Xây dựng điều kiện động
    $conditions = ["p.status = 'active'"];
    $params     = [];
    $types      = '';

    if ($keyword !== '') {
        $conditions[] = 'p.name LIKE ?';
        $params[]     = '%' . $keyword . '%';
        $types       .= 's';
    }
    if ($category_id > 0) {
        $conditions[] = 'p.category_id = ?';
        $params[]     = $category_id;
        $types       .= 'i';
    }
    if ($min_price !== null) {
        $conditions[] = 'p.selling_price >= ?';
        $params[]     = $min_price;
        $types       .= 'd';
    }
    if ($max_price !== null) {
        $conditions[] = 'p.selling_price <= ?';
        $params[]     = $max_price;
        $types       .= 'd';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Đếm tổng số kết quả
    $count_sql  = "SELECT COUNT(*) AS total FROM products p LEFT JOIN categories c ON p.category_id = c.id $where";
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_count = (int)$count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    // Truy vấn dữ liệu với phân trang
    $sql = "SELECT p.id, p.name, p.selling_price, p.img, p.stock_quantity, p.unit,
                   c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            $where
            ORDER BY p.name ASC
            LIMIT ? OFFSET ?";

    $params[] = $limit;  $types .= 'i';
    $params[] = $offset; $types .= 'i';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id'             => (int)$row['id'],
            'name'           => $row['name'],
            'selling_price'  => (float)$row['selling_price'],
            'img'            => $row['img'],
            'stock_quantity' => (int)$row['stock_quantity'],
            'unit'           => $row['unit'],
            'category_name'  => $row['category_name'] ?? 'Chưa phân loại'
        ];
    }
    $stmt->close();

    echo json_encode([
        'success'     => true,
        'data'        => $results,
        'count'       => count($results),
        'total'       => $total_count,
        'page'        => $page,
        'total_pages' => (int)ceil($total_count / $limit)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tìm kiếm'
    ]);
}
?>
