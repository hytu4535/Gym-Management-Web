<?php
/**
 * Database Connection for Admin Module
 * Compatible with legacy code structure
 */

// Include main config
require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Create database connection
$servername = 'localhost';
$username = 'root';
$password = '123456';
$dbname = 'gym_management';

// Create connection using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset(DB_CHARSET);
?>
