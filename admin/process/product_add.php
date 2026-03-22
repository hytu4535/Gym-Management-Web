<?php
require_once '../../config/db.php'; 

if (isset($_POST['btn_add_product'])) {
    $name = $_POST['name'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : 'NULL';
    $unit = $_POST['unit'];
    $selling_price = $_POST['selling_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $status = $_POST['status'];
    
    // Xử lý upload hình ảnh
    $img_name = 'default-product.jpg'; // Mặc định
    
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
            
            if (!move_uploaded_file($_FILES['img']['tmp_name'], $upload_path)) {
                $img_name = 'default-product.jpg';
            }
        }
    }

    $sql_insert = "INSERT INTO products (category_id, name, img, unit, stock_quantity, selling_price, status) 
                   VALUES ($category_id, '$name', '$img_name', '$unit', $stock_quantity, $selling_price, '$status')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>
                alert('Thêm sản phẩm thành công!');
                window.location.href = '../products.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}
?>