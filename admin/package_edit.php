<?php 
$page_title = "Chỉnh sửa Gói Tập";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM membership_packages WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $pkg = $result->fetch_assoc();
} else {
    echo "<script>alert('Gói tập không tồn tại!'); window.location.href='packages.php';</script>";
    exit;
}
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1 class="m-0">Sửa Gói Tập: <?php echo $pkg['package_name']; ?></h1>
      </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Thông tin Gói Tập</h3>
                </div>
                <form action="process/package_edit_process.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tên Gói Tập <span class="text-danger">*</span></label>
                                    <input type="text" name="package_name" class="form-control" value="<?php echo $pkg['package_name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Thời Hạn (Tháng) <span class="text-danger">*</span></label>
                                    <input type="number" name="duration_months" class="form-control" value="<?php echo $pkg['duration_months']; ?>" required min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Giá Tiền (VNĐ) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control" value="<?php echo $pkg['price']; ?>" required min="0">
                                </div>
                                <div class="form-group">
                                    <label>Trạng Thái</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo ($pkg['status'] == 'active') ? 'selected' : ''; ?>>Active (Đang mở bán)</option>
                                        <option value="inactive" <?php echo ($pkg['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Ngừng bán)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mô tả chi tiết</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $pkg['description']; ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_edit_package" class="btn btn-warning">Cập nhật Gói Tập</button>
                        <a href="packages.php" class="btn btn-secondary">Hủy bỏ</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include 'layout/footer.php'; ?>