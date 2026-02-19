<?php
/**
 * Database Connection for Admin Module
 * Compatible with legacy code structure
 */

// Include main config
require_once __DIR__ . '/../includes/config.php';

// Create database connection
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

// Create connection using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset(DB_CHARSET);
?>
