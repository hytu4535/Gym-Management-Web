<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_PACKAGES', 'edit');

require_once '../../config/db.php';

if (isset($_POST['btn_edit_package'])) {
    
    $id = (int)$_POST['id'];
    $package_name = trim($_POST['package_name'] ?? '');
    $duration_months = trim($_POST['duration_months'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($package_name === '' || $duration_months === '' || $price === '') {
        echo "<script>
                alert('Vui lòng nhập đầy đủ các trường bắt buộc.');
                window.history.back();
              </script>";
        exit;
    }

    if (!preg_match('/^[1-9][0-9]*$/', $duration_months)) {
        echo "<script>
                alert('Thời hạn gói tập phải là số nguyên dương và lớn hơn 0.');
                window.history.back();
              </script>";
        exit;
    }

    if (!is_numeric($price) || (float) $price <= 0) {
        echo "<script>
                alert('Giá gói tập phải lớn hơn 0.');
                window.history.back();
              </script>";
        exit;
    }

    $duration_months = (int)$duration_months;
    $price = (float) $price;
    $package_name = $conn->real_escape_string($package_name);
    $description = $conn->real_escape_string($description);

    $currentPkgSql = "SELECT duration_months, price FROM membership_packages WHERE id = $id LIMIT 1";
    $currentPkgResult = $conn->query($currentPkgSql);
    if (!$currentPkgResult || $currentPkgResult->num_rows === 0) {
        echo "<script>
                alert('Không tìm thấy gói tập cần cập nhật.');
                window.location.href = '../packages.php';
              </script>";
        exit;
    }

    $currentPkg = $currentPkgResult->fetch_assoc();
    $usageCountSql = "SELECT COUNT(*) AS total FROM member_packages WHERE package_id = $id";
    $usageCountResult = $conn->query($usageCountSql);
    $usageCount = $usageCountResult ? (int) ($usageCountResult->fetch_assoc()['total'] ?? 0) : 0;

    $durationChanged = ((int) ($currentPkg['duration_months'] ?? 0) !== $duration_months);
    $priceChanged = ((float) ($currentPkg['price'] ?? 0) != $price);

    if ($usageCount > 0 && ($durationChanged || $priceChanged)) {
        echo "<script>
                alert('Không thể thay đổi thời hạn hoặc giá của gói tập đang được sử dụng.');
                window.history.back();
              </script>";
        exit;
    }

    $sql_update = "UPDATE membership_packages SET 
                    package_name = '$package_name', 
                    duration_months = $duration_months, 
                    price = $price, 
                    status = '$status', 
                    description = '$description' 
                   WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>
                alert('Cập nhật gói tập thành công!');
                window.location.href = '../packages.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi khi cập nhật: " . $conn->error . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../packages.php");
    exit();
}
?>