<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'edit');

require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectWithProductEditErrors(int $productId, array $errors, array $oldInput = [])
{
    $_SESSION['product_edit_errors'] = $errors;
    $_SESSION['product_edit_old'] = $oldInput;
    header('Location: ../product_edit.php?id=' . $productId);
    exit();
}

if (isset($_POST['btn_edit_product'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $category_id = trim((string) ($_POST['category_id'] ?? ''));
    $short_description = $conn->real_escape_string(trim($_POST['short_description'] ?? ''));
    $unit = trim((string) ($_POST['unit'] ?? ''));
    $selling_price = trim((string) ($_POST['selling_price'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? ''));
    $old_img = trim((string) ($_POST['old_img'] ?? ''));
    $allowedUnits = ['cái', 'hộp', 'chai', 'gói', 'kg', 'lít'];

    $errors = [];
    if ($id <= 0) {
        $errors['general'] = 'Dữ liệu sản phẩm không hợp lệ.';
    }
    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($category_id === '' || !ctype_digit($category_id)) {
        $errors['category_id'] = 'Vui lòng chọn một danh mục cho sản phẩm.';
    }
    if ($unit === '' || !in_array($unit, $allowedUnits, true)) {
        $errors['unit'] = 'Vui lòng chọn đơn vị tính hợp lệ.';
    }
    if ($selling_price === '' || !is_numeric($selling_price) || (float) $selling_price < 0) {
        $errors['selling_price'] = 'Giá bán phải là số và lớn hơn hoặc bằng 0.';
    }
    if ($status === '') {
        $errors['status'] = 'Vui lòng chọn trạng thái sản phẩm.';
    }

    $currentStmt = $conn->prepare("SELECT name, category_id, short_description, img, unit, selling_price, stock_quantity, status FROM products WHERE id = ? LIMIT 1");
    $currentStmt->bind_param('i', $id);
    $currentStmt->execute();
    $currentProduct = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();

    if (!$currentProduct) {
        $errors['general'] = 'Không tìm thấy sản phẩm cần cập nhật.';
    }

    $transactionUsage = getProductTransactionUsage($conn, $id);
    $hasTransactions = !empty($transactionUsage['has_transactions']);

    if (!$errors && $hasTransactions && $currentProduct && $unit !== (string) ($currentProduct['unit'] ?? '')) {
        $errors['unit'] = 'Không thể thay đổi đơn vị tính vì sản phẩm đã phát sinh giao dịch';
    }

    if (empty($errors)) {
        $duplicateStmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND id <> ?");
        $duplicateStmt->bind_param('si', $name, $id);
        $duplicateStmt->execute();
        $duplicateCount = (int) ($duplicateStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $duplicateStmt->close();

        if ($duplicateCount > 0) {
            $errors['name'] = 'Tên sản phẩm đã tồn tại';
        }
    }

    if (!empty($errors)) {
        redirectWithProductEditErrors($id, $errors, [
            'name' => $name,
            'category_id' => $category_id,
            'short_description' => $_POST['short_description'] ?? '',
            'unit' => $unit,
            'selling_price' => $selling_price,
            'status' => $status,
            'old_img' => $old_img,
        ]);
    }

    $selling_price = (float) $currentProduct['selling_price'];
    $stock_quantity = (int) $currentProduct['stock_quantity'];
    
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

    $hasShortDescriptionColumn = (bool) ($conn->query("SHOW COLUMNS FROM products LIKE 'short_description'")->num_rows ?? 0);

    $updateParts = [
        "name = '$name'",
        "category_id = " . (int) $category_id,
    ];

    if ($hasShortDescriptionColumn) {
        $updateParts[] = "short_description = '$short_description'";
    }

    $updateParts = array_merge($updateParts, [
        "img = '$img_name'",
        "unit = '$unit'",
        "selling_price = " . $selling_price,
        "stock_quantity = " . $stock_quantity,
        "status = '$status'",
    ]);

    $sql = "UPDATE products SET " . implode(', ', $updateParts) . " WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        unset($_SESSION['product_edit_errors'], $_SESSION['product_edit_old']);
        echo "<script>alert('Cập nhật thành công!'); window.location.href='../products.php';</script>";
    } else {
        redirectWithProductEditErrors($id, ['general' => 'Lỗi: ' . $conn->error], [
            'name' => $name,
            'category_id' => $category_id,
            'short_description' => $_POST['short_description'] ?? '',
            'unit' => $unit,
            'selling_price' => $selling_price,
            'status' => $status,
            'old_img' => $old_img,
        ]);
    }
}
?>