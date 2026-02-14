<?php 
$page_title = "Gia hạn / Sửa Gói Tập";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT mp.*, m.full_name 
        FROM member_packages mp
        LEFT JOIN members m ON mp.member_id = m.id
        WHERE mp.id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $mp = $result->fetch_assoc();
} else {
    echo "<script>alert('Dữ liệu không tồn tại!'); window.location.href='member-packages.php';</script>";
    exit;
}
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1 class="m-0">Gia hạn / Cập nhật Gói Tập</h1>
      </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Hội viên: <strong><?php echo $mp['full_name']; ?></strong></h3>
                </div>
                <form action="process/member_package_edit_process.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $mp['id']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label>Gói tập đang chọn <span class="text-danger">*</span></label>
                                <select name="package_id" class="form-control" required>
                                    <?php
                                    $packages = $conn->query("SELECT id, package_name, duration_months FROM membership_packages");
                                    while($p = $packages->fetch_assoc()) {
                                        $selected = ($p['id'] == $mp['package_id']) ? 'selected' : '';
                                        echo "<option value='{$p['id']}' $selected>{$p['package_name']} ({$p['duration_months']} tháng)</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($mp['start_date'])); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ngày hết hạn <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control text-danger font-weight-bold" value="<?php echo date('Y-m-d', strtotime($mp['end_date'])); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo ($mp['status'] == 'active') ? 'selected' : ''; ?>>Active (Đang hoạt động)</option>
                                        <option value="expired" <?php echo ($mp['status'] == 'expired') ? 'selected' : ''; ?>>Expired (Đã hết hạn)</option>
                                        <option value="cancelled" <?php echo ($mp['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled (Đã hủy)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_edit_mp" class="btn btn-warning">Lưu thay đổi</button>
                        <a href="member-packages.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include 'layout/footer.php'; ?>