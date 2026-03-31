<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'edit');

require_once __DIR__ . '/category_repository.php';

if (isset($_POST['btn_edit_category'])) {
    [$ok, $message] = updateCategory($_POST['id'] ?? 0, $_POST['name'] ?? '', $_POST['description'] ?? '');

    if ($ok) {
        echo "<script>alert(" . json_encode($message, JSON_UNESCAPED_UNICODE) . "); window.location.href='../categories.php';</script>";
    } else {
        echo "<script>alert(" . json_encode($message, JSON_UNESCAPED_UNICODE) . "); window.history.back();</script>";
    }
} else {
    header("Location: ../categories.php");
}
?>