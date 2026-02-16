<?php
/**
 * Database Configuration
 * Gym Management System
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'thinh2014');
define('DB_NAME', 'gym_management');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Gym Management System');
define('APP_URL', 'http://localhost:8000');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');

// Security
define('SESSION_LIFETIME', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('BCRYPT_COST', 12);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (development mode)
$is_development = true; // Set to false in production
if ($is_development) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
?>
