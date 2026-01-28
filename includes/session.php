<?php
/**
 * Session Management
 * Gym Management System
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    redirect('../admin/login.php?timeout=1');
}
$_SESSION['last_activity'] = time();

/**
 * Login user
 */
function loginUser($userId, $username, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['created'] = time();
    $_SESSION['last_activity'] = time();
    
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Check authentication
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../admin/login.php');
    }
}

/**
 * Check role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die("Access denied. Insufficient permissions.");
    }
}

/**
 * Check if admin
 */
function requireAdmin() {
    requireRole('admin');
}
?>
