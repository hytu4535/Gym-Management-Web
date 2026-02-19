<?php 
$page_title = "Sửa Danh Mục";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM categories WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cat = $result->fetch_assoc();
} else {
    echo "<script>alert('Danh mục không tồn tại!'); window.location.href='categories.php';</script>";
    exit;
}
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1 class="m-0">Chỉnh sửa Danh Mục</h1>
      </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Thông tin Danh Mục: <strong><?php echo $cat['name']; ?></strong></h3>
                </div>
                <form action="process/category_edit_process.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo $cat['name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $cat['description']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="active" <?php echo ($cat['status'] == 'active') ? 'selected' : ''; ?>>Active (Đang hoạt động)</option>
                                <option value="inactive" <?php echo ($cat['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Tạm ẩn)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_edit_category" class="btn btn-warning">Lưu thay đổi</button>
                        <a href="categories.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include 'layout/footer.php'; ?>