<?php
require_once '../../config/db.php';

if (isset($_POST['btn_edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : 'NULL';
    $unit = $_POST['unit'];
    $selling_price = $_POST['selling_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $status = $_POST['status'];
    $old_img = $_POST['old_img'];
    
    // Xử lý upload hình ảnh mới (nếu có)
    $img_name = $old_img; // Giữ nguyên hình cũ
    
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['img']['type'], $allowed_types) && $_FILES['img']['size'] <= $max_size) {
            $upload_dir = '../../assets/uploads/products/';
            
            // Tạo thư mục nếu chưa có
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Tạo tên file unique
            $file_extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
            $img_name = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $img_name;
            
            if (move_uploaded_file($_FILES['img']['tmp_name'], $upload_path)) {
                // Xóa hình cũ nếu không phải default
                if ($old_img && $old_img != 'default-product.jpg' && file_exists($upload_dir . $old_img)) {
                    unlink($upload_dir . $old_img);
                }
            } else {
                $img_name = $old_img; // Giữ lại hình cũ nếu upload thất bại
            }
        }
    }

    $sql = "UPDATE products SET 
            name = '$name', 
            category_id = $category_id, 
            img = '$img_name',
            unit = '$unit',
            selling_price = $selling_price, 
            stock_quantity = $stock_quantity,
            status = '$status'
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Cập nhật thành công!'); window.location.href='../products.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>