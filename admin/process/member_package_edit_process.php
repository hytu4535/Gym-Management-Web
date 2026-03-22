<?php
require_once '../../config/db.php';

if (isset($_POST['btn_edit_mp'])) {
    $id = (int)$_POST['id'];
    $package_id = (int)$_POST['package_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $sql_update = "UPDATE member_packages SET 
                    package_id = $package_id, 
                    start_date = '$start_date', 
                    end_date = '$end_date', 
                    status = '$status' 
                   WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>
                alert('Cập nhật dữ liệu thành công!');
                window.location.href = '../member-packages.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi khi cập nhật: " . $conn->error . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../member-packages.php");
    exit();
}
?>