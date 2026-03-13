<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Lấy thông tin sản phẩm
    $product_sql = "SELECT name FROM products WHERE id = $id";
    $product_result = $conn->query($product_sql);
    
    if ($product_result->num_rows == 0) {
        echo "<script>alert('Sản phẩm không tồn tại!'); window.location.href='../products.php';</script>";
        exit;
    }
    
    $product = $product_result->fetch_assoc();
    $product_name = $product['name'];
    
    // Kiểm tra xem sản phẩm đã được bán ra chưa (có trong order_items)
    $check_sql = "SELECT COUNT(*) as total FROM order_items WHERE item_type = 'product' AND item_id = $id";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        // ========================================
        // CASE 1: SẢN PHẨM ĐÃ ĐƯỢC BÁN RA
        // → CHỈ ẨN KHỎI WEBSITE (SOFT DELETE)
        // ========================================
        $sql = "UPDATE products SET status = 'inactive' WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    alert('⚠️ SẢN PHẨM ĐÃ ĐƯỢC BÁN RA!\\n\\n📦 Sản phẩm: {$product_name}\\n✅ Đã ẨN khỏi trang web (Website)\\n✅ Khách hàng không thể xem được nữa\\n\\n💡 Sản phẩm vẫn lưu trong hệ thống để tra cứu lịch sử đơn hàng.');
                    window.location.href='../products.php';
                  </script>";
        } else {
            echo "<script>alert('Lỗi khi ẩn sản phẩm: " . $conn->error . "'); window.location.href='../products.php';</script>";
        }
    } else {
        // ========================================
        // CASE 2: SẢN PHẨM CHƯA ĐƯỢC BÁN RA
        // → XÓA HOÀN TOÀN (HARD DELETE)
        // ========================================
        
        // Lấy thông tin hình ảnh để xóa file
        $img_sql = "SELECT img FROM products WHERE id = $id";
        $img_result = $conn->query($img_sql);
        $img_row = $img_result->fetch_assoc();
        
        $sql = "DELETE FROM products WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            // Xóa file hình ảnh (nếu không phải default)
            if ($img_row && $img_row['img'] && $img_row['img'] != 'default-product.jpg') {
                $img_path = '../../assets/uploads/products/' . $img_row['img'];
                if (file_exists($img_path)) {
                    unlink($img_path);
                }
            }
            
            echo "<script>
                    alert('✅ XÓA THÀNH CÔNG!\\n\\n📦 Sản phẩm: {$product_name}\\n🗑️ Đã xóa HOÀN TOÀN khỏi hệ thống\\n🖼️ Hình ảnh cũng đã được xóa');
                    window.location.href='../products.php';
                  </script>";
        } else {
            echo "<script>alert('Lỗi khi xóa sản phẩm: " . $conn->error . "'); window.location.href='../products.php';</script>";
        }
    }
} else {
    header("Location: ../products.php");
}
?>