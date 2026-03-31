
<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'add');

require_once __DIR__ . '/category_repository.php';

if (isset($_POST['btn_add_category'])) {
    [$ok, $message] = createCategory($_POST['name'] ?? '', $_POST['description'] ?? '');

    echo "<script>alert(" . json_encode($message, JSON_UNESCAPED_UNICODE) . "); window.location.href='../categories.php';</script>";
}
?>
