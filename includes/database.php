<?php
/**
 * Database Connection Class
 * Gym Management System
 */

require_once __DIR__ . '/config.php';

if (!class_exists('Database', false)) {
    class Database {
        private static $instance = null;
        private $connection;
        
        private function __construct() {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function getConnection() {
            return $this->connection;
        }
        
        // Prevent cloning
        private function __clone() {}
        
        // Prevent unserialization
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }
    }
}

// Helper function to get database connection
if (!function_exists('getDB')) {
    function getDB() {
        return Database::getInstance()->getConnection();
    }
}

if (!function_exists('toVietnameseDbError')) {
    function toVietnameseDbError($error, string $fallback = 'Thao tác không thành công.'): string {
        $errorCode = '';
        $errorMessage = '';

        if ($error instanceof Throwable) {
            $errorCode = (string) $error->getCode();
            $errorMessage = (string) $error->getMessage();
        } else {
            $errorMessage = (string) $error;
        }

        $normalizedMessage = strtolower($errorMessage);
        $isFkError = in_array($errorCode, ['1451', '1452', '23000'], true)
            || strpos($normalizedMessage, '1451') !== false
            || strpos($normalizedMessage, '1452') !== false
            || strpos($normalizedMessage, '23000') !== false
            || strpos($normalizedMessage, 'foreign key constraint fails') !== false
            || strpos($normalizedMessage, 'integrity constraint violation') !== false;

        if ($isFkError) {
            return 'Không thể thực hiện thao tác vì dữ liệu đang được sử dụng ở mục khác.';
        }

        $isDuplicateError = $errorCode === '1062'
            || strpos($normalizedMessage, '1062') !== false
            || strpos($normalizedMessage, 'duplicate entry') !== false;
        if ($isDuplicateError) {
            return 'Dữ liệu đã tồn tại. Vui lòng kiểm tra lại thông tin nhập.';
        }

        return $fallback;
    }
}
?>