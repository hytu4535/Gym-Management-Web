<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'add');

require_once '../../config/db.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectWithProductAddErrors(array $errors, array $oldInput = [])
{
    $_SESSION['product_add_errors'] = $errors;
    $_SESSION['product_add_old'] = $oldInput;
    header('Location: ../products.php');
    exit();
}

if (isset($_POST['btn_add_product'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $category_id = trim((string) ($_POST['category_id'] ?? ''));
    $short_description = $conn->real_escape_string(trim($_POST['short_description'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $unit = trim((string) ($_POST['unit'] ?? ''));
    $selling_price = trim((string) ($_POST['selling_price'] ?? ''));
    $stock_quantity = trim((string) ($_POST['stock_quantity'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? ''));

    $errors = [];
    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập tên sản phẩm.';
    } elseif (mb_strlen($name) < 3) {
        $errors['name'] = 'Tên sản phẩm phải có ít nhất 3 ký tự.';
    }

    if ($category_id === '' || !ctype_digit($category_id)) {
        $errors['category_id'] = 'Vui lòng chọn một danh mục cho sản phẩm.';
    }

    if ($unit === '') {
        $errors['unit'] = 'Vui lòng nhập đơn vị tính.';
    }

    if ($selling_price === '' || !is_numeric($selling_price) || (float) $selling_price <= 0) {
        $errors['selling_price'] = 'Giá bán phải là số và lớn hơn 0.';
    }

    if ($stock_quantity === '' || !is_numeric($stock_quantity) || (int) $stock_quantity < 0) {
        $errors['stock_quantity'] = 'Vui lòng nhập số lượng tồn kho lớn hơn hoặc bằng 0.';
    }

    if ($status === '') {
        $errors['status'] = 'Vui lòng chọn trạng thái sản phẩm.';
    }

    if (!isset($_FILES['img']) || $_FILES['img']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['img'] = 'Vui lòng chọn hình ảnh cho sản phẩm.';
    } elseif ($_FILES['img']['error'] !== UPLOAD_ERR_OK) {
        $errors['img'] = 'Không thể tải hình ảnh lên. Vui lòng thử lại.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
        $max_size = 2 * 1024 * 1024;
        if (!in_array($_FILES['img']['type'], $allowed_types, true)) {
            $errors['img'] = 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF, WEBP.';
        } elseif ((int) $_FILES['img']['size'] > $max_size) {
            $errors['img'] = 'Kích thước ảnh vượt quá 2MB.';
        }
    }

    if (!empty($errors)) {
        redirectWithProductAddErrors($errors, [
            'name' => $name,
            'category_id' => $category_id,
            'short_description' => $_POST['short_description'] ?? '',
            'description' => $_POST['description'] ?? '',
            'unit' => $unit,
            'selling_price' => $selling_price,
            'stock_quantity' => $stock_quantity,
            'status' => $status,
        ]);
    }
    
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

    $hasShortDescriptionColumn = (bool) ($conn->query("SHOW COLUMNS FROM products LIKE 'short_description'")->num_rows ?? 0);
    $hasDescriptionColumn = (bool) ($conn->query("SHOW COLUMNS FROM products LIKE 'description'")->num_rows ?? 0);

    $insertColumns = ['category_id', 'name'];
    $insertValues = [(int) $category_id, "'$name'"];

    if ($hasShortDescriptionColumn) {
        $insertColumns[] = 'short_description';
        $insertValues[] = "'$short_description'";
    }
    if ($hasDescriptionColumn) {
        $insertColumns[] = 'description';
        $insertValues[] = "'" . ($description !== '' ? $description : $short_description) . "'";
    }

    $insertColumns = array_merge($insertColumns, ['img', 'unit', 'stock_quantity', 'selling_price', 'status']);
    $insertValues = array_merge($insertValues, ["'$img_name'", "'$unit'", (int) $stock_quantity, (float) $selling_price, "'$status'"]);

    $sql_insert = "INSERT INTO products (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";

    if ($conn->query($sql_insert) === TRUE) {
                unset($_SESSION['product_add_errors'], $_SESSION['product_add_old']);
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