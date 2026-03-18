<?php
session_start();

$packageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$target = 'packages.php';
if ($packageId > 0) {
    $target .= '#package-' . $packageId;
}

header('Location: ' . $target);
exit();
