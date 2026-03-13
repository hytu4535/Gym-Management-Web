<?php
// TODO: Implement tìm kiếm nâng cao
// Kết hợp nhiều điều kiện: tên sản phẩm, phân loại, khoảng giá

header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $keyword = $_REQUEST['keyword'] ?? '';
    $category_id = $_REQUEST['category'] ?? '';
    $min_price = $_REQUEST['min_price'] ?? '';
    $max_price = $_REQUEST['max_price'] ?? '';
    $page = $_REQUEST['page'] ?? 1;
    $limit = 12; // Số sản phẩm mỗi trang
    $offset = ($page - 1) * $limit;
    
    try {
        // TODO: Build query động dựa trên các điều kiện
        // $sql = "SELECT p.*, c.category_name FROM products p 
        //         LEFT JOIN categories c ON p.category_id = c.category_id 
        //         WHERE 1=1";
        
        // if (!empty($keyword)) {
        //     $sql .= " AND p.product_name LIKE ?";
        // }
        // if (!empty($category_id)) {
        //     $sql .= " AND p.category_id = ?";
        // }
        // if (!empty($min_price)) {
        //     $sql .= " AND p.price >= ?";
        // }
        // if (!empty($max_price)) {
        //     $sql .= " AND p.price <= ?";
        // }
        
        // $sql .= " ORDER BY p.product_name ASC LIMIT ? OFFSET ?";
        
        // TODO: Execute query with prepared statements
        
        $results = [
            // Sample data
        ];
        
        $total_count = 0; // TODO: Query total count for pagination
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results),
            'total' => $total_count,
            'page' => $page,
            'total_pages' => ceil($total_count / $limit)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tìm kiếm: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
