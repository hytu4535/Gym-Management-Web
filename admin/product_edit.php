<?php 
$page_title = "Chỉnh sửa sản phẩm";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
$sql = "SELECT * FROM products WHERE id = $id";
$product = $conn->query($sql)->fetch_assoc();
$productEditErrors = $_SESSION['product_edit_errors'] ?? [];
$productEditOld = $_SESSION['product_edit_old'] ?? [];
unset($_SESSION['product_edit_errors'], $_SESSION['product_edit_old']);

function productEditValue($key, $fallback, $oldInput) {
    return array_key_exists($key, $oldInput) ? $oldInput[$key] : $fallback;
}
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Chỉnh sửa sản phẩm: <?php echo $product['name']; ?></h3>
                </div>
                <form id="editProductForm" action="process/product_edit_process.php" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="old_img" value="<?php echo $product['img']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tên Sản Phẩm</label>
                                    <input id="name" type="text" name="name" class="form-control <?php echo isset($productEditErrors['name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars(productEditValue('name', $product['name'], $productEditOld)); ?>" required>
                                    <?php if (isset($productEditErrors['name'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['name']); ?></div><?php endif; ?>
                                </div>
                                 <div class="form-group">
                                    <label>Mô tả ngắn</label>
                                    <textarea name="short_description" class="form-control <?php echo isset($productEditErrors['short_description']) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Mô tả ngắn sản phẩm..."><?php echo htmlspecialchars(productEditValue('short_description', $product['short_description'] ?? '', $productEditOld)); ?></textarea>
                                    <?php if (isset($productEditErrors['short_description'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['short_description']); ?></div><?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Mô tả chi tiết</label>
                                    <textarea name="description" class="form-control <?php echo isset($productEditErrors['description']) ? 'is-invalid' : ''; ?>" rows="6" placeholder="Mô tả chi tiết sản phẩm..." style="min-height: 140px;"><?php echo htmlspecialchars(productEditValue('description', $product['description'] ?? '', $productEditOld)); ?></textarea>
                                    <?php if (isset($productEditErrors['description'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['description']); ?></div><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Danh Mục</label>
                                    <select id="category_id" name="category_id" class="form-control <?php echo isset($productEditErrors['category_id']) ? 'is-invalid' : ''; ?>">
                                        <option value="">-- Chưa phân loại --</option>
                                        <?php
                                        $cats = $conn->query("SELECT * FROM categories");
                                        $selectedCategoryId = productEditValue('category_id', $product['category_id'] ?? '', $productEditOld);
                                        while($cat = $cats->fetch_assoc()) {
                                            $selected = ((string) $selectedCategoryId !== '' && (int) $cat['id'] === (int) $selectedCategoryId) ? 'selected' : '';
                                            echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <?php if (isset($productEditErrors['category_id'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['category_id']); ?></div><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label>Hình Ảnh Hiện Tại</label>
                                    <?php 
                                    $imgPath = !empty($product['img'])
                                        ? "../assets/uploads/products/{$product['img']}"
                                        : "../assets/uploads/products/default-product.jpg";
                                    ?>
                                    <div class="text-center mb-3">
                                        <img id="currentImage" src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="max-width: 200px; max-height: 200px; border: 2px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Thay Đổi Hình Ảnh</label>
                                    <input id="img" type="file" class="form-control-file" name="img" accept="image/*" onchange="previewEditImage(event)">
                                    <small class="form-text text-muted">Để trống nếu không muốn thay đổi hình ảnh</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Đơn Vị Tính</label>
                                    <input id="unit" type="text" name="unit" class="form-control <?php echo isset($productEditErrors['unit']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars(productEditValue('unit', $product['unit'] ?? '', $productEditOld)); ?>">
                                    <?php if (isset($productEditErrors['unit'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['unit']); ?></div><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label>Giá Bán (VNĐ)</label>
                                    <input id="selling_price" type="number" name="selling_price" class="form-control <?php echo isset($productEditErrors['selling_price']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars(productEditValue('selling_price', $product['selling_price'] ?? '', $productEditOld)); ?>" required readonly>
                                    <small class="form-text text-muted">Giá sản phẩm được cố định, không chỉnh sửa tại form này.</small>
                                </div>
                                <div class="form-group">
                                    <label>Tồn kho</label>
                                    <input id="stock_quantity" type="number" name="stock_quantity" class="form-control <?php echo isset($productEditErrors['stock_quantity']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars(productEditValue('stock_quantity', $product['stock_quantity'] ?? '', $productEditOld)); ?>">
                                    <?php if (isset($productEditErrors['stock_quantity'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['stock_quantity']); ?></div><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label>Trạng Thái</label>
                                    <select id="status" name="status" class="form-control <?php echo isset($productEditErrors['status']) ? 'is-invalid' : ''; ?>">
                                        <?php $selectedStatus = productEditValue('status', $product['status'] ?? 'active', $productEditOld); ?>
                                        <option value="active" <?php echo ($selectedStatus == 'active') ? 'selected' : ''; ?>>Active (Kích hoạt)</option>
                                        <option value="inactive" <?php echo ($selectedStatus == 'inactive') ? 'selected' : ''; ?>>Inactive (Ẩn)</option>
                                    </select>
                                    <?php if (isset($productEditErrors['status'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($productEditErrors['status']); ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_edit_product" class="btn btn-warning">Cập nhật sản phẩm</button>
                        <a href="products.php" class="btn btn-secondary">Hủy bỏ</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
// Preview image when editing
function previewEditImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('currentImage');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    let isValid = true;
    let firstErrorElement = null; 
    
    // 1. Xóa thông báo lỗi cũ
    document.querySelectorAll('.is-invalid').forEach(function(el) {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.custom-error-text').forEach(function(el) {
        el.remove();
    });

    function showError(inputId, message) {
        let inputEl = document.getElementById(inputId);
        if(!inputEl) return; // Bỏ qua nếu không tìm thấy thẻ
        
        inputEl.classList.add('is-invalid');
        
        let errorSpan = document.createElement('div');
        errorSpan.className = 'invalid-feedback custom-error-text';
        errorSpan.style.display = 'block';
        errorSpan.innerText = message;
        
        inputEl.parentNode.appendChild(errorSpan);
        isValid = false;

        if (!firstErrorElement) {
            firstErrorElement = inputEl;
        }
    }

    let nameVal = document.getElementById('name').value.trim();
    if (nameVal === '') {
        showError('name', 'Vui lòng nhập tên sản phẩm.');
    } else if (nameVal.length < 3) {
        showError('name', 'Tên sản phẩm phải có ít nhất 3 ký tự.');
    }
    let categoryVal = document.getElementById('category_id').value;
    if (categoryVal === '') {
        showError('category_id', 'Vui lòng chọn một danh mục cho sản phẩm.');
    }
    let imgInput = document.getElementById('img');
    if (imgInput && imgInput.files.length > 0) {
        let file = imgInput.files[0];
        let fileSizeMB = file.size / (1024 * 1024);
        let validExtensions = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!validExtensions.includes(file.type)) {
            showError('img', 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF, WEBP.');
        } else if (fileSizeMB > 2) {
            showError('img', 'Kích thước ảnh vượt quá dung lượng cho phép (Tối đa 2MB).');
        }
    }
    let unitVal = document.getElementById('unit').value.trim();
    if (unitVal === '') {
        showError('unit', 'Vui lòng nhập đơn vị tính (vd: hộp, chai...).');
    }
    let priceVal = document.getElementById('selling_price').value;
    if (priceVal === '' || isNaN(priceVal) || parseFloat(priceVal) <= 0) {
        showError('selling_price', 'Giá bán phải là số và lớn hơn 0.');
    }
    let stockVal = document.getElementById('stock_quantity').value;
    if (stockVal === '' || isNaN(stockVal) || parseInt(stockVal) < 0) {
        showError('stock_quantity', 'Vui lòng nhập số lượng tồn kho (>= 0).');
    }

    let statusVal = document.getElementById('status').value;
    if (statusVal === '') {
        showError('status', 'Vui lòng chọn trạng thái sản phẩm.');
    }

    if (!isValid) {
        e.preventDefault();
        if (firstErrorElement) {
            firstErrorElement.focus();
        }
    }
});
</script>

<?php include 'layout/footer.php'; ?>