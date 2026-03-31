<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'delete');

require_once __DIR__ . '/category_repository.php';

if (isset($_GET['id'])) {
    [$ok, $message] = deactivateCategory((int) $_GET['id']);
    echo "<script>alert(" . json_encode($message, JSON_UNESCAPED_UNICODE) . "); window.location.href='../categories.php';</script>";
}
?>