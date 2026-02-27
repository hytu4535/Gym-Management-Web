<?php 
$page_title = "Chỉnh sửa sản phẩm";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id = $id";
$product = $conn->query($sql)->fetch_assoc();
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Chỉnh sửa sản phẩm: <?php echo $product['name']; ?></h3>
                </div>
                <form action="process/product_edit_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="old_img" value="<?php echo $product['img']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tên Sản Phẩm</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo $product['name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Danh Mục</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">-- Chưa phân loại --</option>
                                        <?php
                                        $cats = $conn->query("SELECT * FROM categories");
                                        while($cat = $cats->fetch_assoc()) {
                                            $selected = ($cat['id'] == $product['category_id']) ? 'selected' : '';
                                            echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Hình Ảnh Hiện Tại</label>
                                    <?php 
                                    $imgPath = $product['img'] ? "../assets/uploads/products/{$product['img']}" : "../assets/uploads/products/default-product.jpg";
                                    ?>
                                    <div class="text-center mb-3">
                                        <img id="currentImage" src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="max-width: 200px; max-height: 200px; border: 2px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Thay Đổi Hình Ảnh</label>
                                    <input type="file" class="form-control-file" name="img" accept="image/*" onchange="previewEditImage(event)">
                                    <small class="form-text text-muted">Để trống nếu không muốn thay đổi hình ảnh</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Đơn Vị Tính</label>
                                    <input type="text" name="unit" class="form-control" value="<?php echo $product['unit']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Giá Bán (VNĐ)</label>
                                    <input type="number" name="selling_price" class="form-control" value="<?php echo $product['selling_price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Tồn kho</label>
                                    <input type="number" name="stock_quantity" class="form-control" value="<?php echo $product['stock_quantity']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Trạng Thái</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active (Kích hoạt)</option>
                                        <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Ẩn)</option>
                                    </select>
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
</script>

<?php include 'layout/footer.php'; ?>