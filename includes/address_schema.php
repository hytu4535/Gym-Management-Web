<?php
if (!function_exists('ensureAddressSchemaPdo')) {
    function ensureAddressSchemaPdo(PDO $db): void
    {
        $checkColumn = function (PDO $db, string $column): bool {
            $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'addresses' AND COLUMN_NAME = ?");
            $stmt->execute([$column]);
            return (int) $stmt->fetchColumn() > 0;
        };

        if (!$checkColumn($db, 'ward')) {
            $db->exec("ALTER TABLE addresses ADD COLUMN ward VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER district");
        }

        if (!$checkColumn($db, 'type')) {
            $db->exec("ALTER TABLE addresses ADD COLUMN type ENUM('home','work','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home' AFTER member_id");
        }
    }
}

if (!function_exists('ensureAddressSchemaMysqli')) {
    function ensureAddressSchemaMysqli(mysqli $conn): void
    {
        $checkColumn = function (mysqli $conn, string $column): bool {
            $sql = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'addresses' AND COLUMN_NAME = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return true;
            }
            $stmt->bind_param('s', $column);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return (int) ($result['total'] ?? 0) > 0;
        };

        if (!$checkColumn($conn, 'ward')) {
            $conn->query("ALTER TABLE addresses ADD COLUMN ward VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER district");
        }

        if (!$checkColumn($conn, 'type')) {
            $conn->query("ALTER TABLE addresses ADD COLUMN type ENUM('home','work','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home' AFTER member_id");
        }
    }
}