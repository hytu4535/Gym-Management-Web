<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$stmt = $db->query("SELECT DISTINCT unit FROM products ORDER BY unit ASC");
$units = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($units, JSON_UNESCAPED_UNICODE) . PHP_EOL;
