<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_PACKAGES', 'add');

require_once '../../config/db.php';

if (isset($_POST['btn_add_package'])) {
  $package_name = trim($_POST['package_name'] ?? '');
  $duration_months = trim($_POST['duration_months'] ?? '');
  $price = trim($_POST['price'] ?? '');
  $description = trim((string) ($_POST['description'] ?? ''));
  $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

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

  $package_name = $conn->real_escape_string($package_name);
  $description = $conn->real_escape_string($description);
  $durationValue = (int) $duration_months;
  $priceValue = (float) $price;

    $sql_insert = "INSERT INTO membership_packages (package_name, duration_months, price, description, status) 
                   VALUES ('$package_name', $durationValue, $priceValue, '$description', '$status')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>
                alert('Thêm gói tập thành công!');
                window.location.href = '../packages.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}
?>