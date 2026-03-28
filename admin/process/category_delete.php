<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE category_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    $productCount = (int) ($row['total'] ?? 0);
    $checkStmt->close();

    if ($productCount > 0) {
        echo "<script>alert('Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục. Vui lòng chuyển hoặc xóa sản phẩm trước khi xóa danh mục.'); window.location.href='../categories.php';</script>";
        exit();
    }

    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Đã xóa danh mục thành công!'); window.location.href='../categories.php';</script>";
    } else {
        echo "<script>alert('Không thể xóa danh mục!'); window.location.href='../categories.php';</script>";
    }

    $stmt->close();
}
?>