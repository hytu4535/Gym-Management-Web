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
                <form action="process/product_edit_process.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
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
                                        <?php
                                        $cats = $conn->query("SELECT * FROM categories");
                                        while($cat = $cats->fetch_assoc()) {
                                            $selected = ($cat['id'] == $product['category_id']) ? 'selected' : '';
                                            echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Giá Bán (VNĐ)</label>
                                    <input type="number" name="selling_price" class="form-control" value="<?php echo $product['selling_price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Tồn kho</label>
                                    <input type="number" name="stock_quantity" class="form-control" value="<?php echo $product['stock_quantity']; ?>">
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
<?php include 'layout/footer.php'; ?>