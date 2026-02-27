<?php
// TODO: Implement tìm kiếm cơ bản theo tên sản phẩm
// Tìm tương đối, sử dụng LIKE %keyword%

header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $keyword = $_REQUEST['keyword'] ?? '';
    
    // TODO: Validate dữ liệu đầu vào
    
    try {
        // TODO: Query database
        // SELECT * FROM products WHERE product_name LIKE ? ORDER BY product_name ASC
        
        $results = [
            // Sample data structure
            [
                'product_id' => 1,
                'product_name' => 'Sản phẩm 1',
                'price' => 500000,
                'image' => 'product-1.jpg',
                'category_name' => 'Danh mục 1'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
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
