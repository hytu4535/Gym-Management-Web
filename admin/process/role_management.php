<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_ALL', 'view');

include '../../includes/database.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$allowedPermissionCodes = [
    'MANAGE_STAFF',
    'MANAGE_MEMBERS',
    'MANAGE_PACKAGES',
    'MANAGE_TRAINERS',
    'MANAGE_SERVICES_NUTRITION',
    'MANAGE_SALES',
    'MANAGE_INVENTORY',
    'MANAGE_EQUIPMENT',
    'MANAGE_FEEDBACK',
    'MANAGE_PROMOTIONS',
    'VIEW_REPORTS',
    'MANAGE_ALL',
];

function ensureRoleActionPermissionTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS `role_action_permissions` (
        `id` int NOT NULL AUTO_INCREMENT,
        `role_id` int NOT NULL,
        `permission_code` varchar(50) NOT NULL,
        `can_view` tinyint(1) NOT NULL DEFAULT 0,
        `can_add` tinyint(1) NOT NULL DEFAULT 0,
        `can_edit` tinyint(1) NOT NULL DEFAULT 0,
        `can_delete` tinyint(1) NOT NULL DEFAULT 0,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_role_permission_code` (`role_id`,`permission_code`),
        KEY `idx_role_permissions_role_id` (`role_id`),
        CONSTRAINT `fk_role_action_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function detectPermissionTable(PDO $db): ?string {
    $tableName = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('permission', 'permissions') ORDER BY CASE WHEN TABLE_NAME = 'permission' THEN 0 ELSE 1 END LIMIT 1")->fetchColumn();
    return $tableName ? (string) $tableName : null;
}

function detectLegacyRolePermissionTable(PDO $db): ?string {
    $tableName = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('role_permissions', 'role_permission', 'role_permisson') ORDER BY CASE WHEN TABLE_NAME = 'role_permissions' THEN 0 WHEN TABLE_NAME = 'role_permission' THEN 1 ELSE 2 END LIMIT 1")->fetchColumn();
    return $tableName ? (string) $tableName : null;
}

function reloadCurrentRolePermissions(PDO $db, int $roleId): void {
    if ((int) ($_SESSION['role_id'] ?? 0) !== $roleId) {
        return;
    }

    $permissionCodes = [];
    $actionPermissions = [];

    $rowsStmt = $db->prepare("SELECT permission_code, can_view, can_add, can_edit, can_delete FROM role_action_permissions WHERE role_id = ?");
    $rowsStmt->execute([$roleId]);
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $code = (string) ($row['permission_code'] ?? '');
        if ($code === '') {
            continue;
        }

        $actionSet = [
            'view' => (int) ($row['can_view'] ?? 0) === 1,
            'add' => (int) ($row['can_add'] ?? 0) === 1,
            'edit' => (int) ($row['can_edit'] ?? 0) === 1,
            'delete' => (int) ($row['can_delete'] ?? 0) === 1,
        ];

        $actionPermissions[$code] = $actionSet;
        if ($actionSet['view'] || $actionSet['add'] || $actionSet['edit'] || $actionSet['delete']) {
            $permissionCodes[] = $code;
        }
    }

    $_SESSION['permissions'] = array_values(array_unique($permissionCodes));
    $_SESSION['user_action_permissions'] = $actionPermissions;
}

try {
    ensureRoleActionPermissionTable($db);
    $permissionTable = detectPermissionTable($db);
    $legacyRolePermissionTable = detectLegacyRolePermissionTable($db);
    $hasLegacyRolePermissionsTable = $legacyRolePermissionTable !== null;

    if ($action == 'add') {
        processRequirePermission('MANAGE_ALL', 'add');

        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = $_POST['status'];

        // Thêm role
        $sql = "INSERT INTO roles (name, description, status) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $description, $status]);

        header("Location: ../roles.php");
        exit();
    }

    if ($action == 'delete') {
        processRequirePermission('MANAGE_ALL', 'delete');

        $id = $_GET['id'];
        $sql = "DELETE FROM roles WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        // Xoá luôn role_permissions liên quan (nếu có bảng legacy)
        if ($hasLegacyRolePermissionsTable) {
            $sql = "DELETE FROM `$legacyRolePermissionTable` WHERE role_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
        }

        header("Location: ../roles.php");
        exit();
    }

    if ($action == 'edit') {
        processRequirePermission('MANAGE_ALL', 'edit');

        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = $_POST['status'];

        $sql = "UPDATE roles SET name=?, description=?, status=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $description, $status, $id]);

        reloadCurrentRolePermissions($db, (int) $id);

        header("Location: ../roles.php");
        exit();
    }

    if ($action == 'update_permissions') {
        processRequirePermission('MANAGE_ALL', 'edit');

        $roleId = (int) ($_POST['role_id'] ?? 0);
        $postedPermissions = $_POST['permissions'] ?? [];

        if ($roleId <= 0) {
            $_SESSION['validation_errors'] = ['general' => 'Không tìm thấy vai trò để cập nhật phân quyền.'];
            header("Location: ../roles.php");
            exit();
        }

        $permissionIdMap = [];
        if ($permissionTable !== null) {
            $permissionRows = $db->query("SELECT id, code FROM `$permissionTable`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($permissionRows as $permRow) {
                $permissionIdMap[(string) $permRow['code']] = (int) $permRow['id'];
            }
        }

        $db->beginTransaction();
        try {
            $deleteActionStmt = $db->prepare("DELETE FROM role_action_permissions WHERE role_id = ?");
            $deleteActionStmt->execute([$roleId]);

            $insertActionStmt = $db->prepare("INSERT INTO role_action_permissions (role_id, permission_code, can_view, can_add, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)");

            $selectedPermissionIds = [];
            foreach ($allowedPermissionCodes as $permissionCode) {
                $moduleData = $postedPermissions[$permissionCode] ?? [];
                $canView = !empty($moduleData['view']) ? 1 : 0;
                $canAdd = !empty($moduleData['add']) ? 1 : 0;
                $canEdit = !empty($moduleData['edit']) ? 1 : 0;
                $canDelete = !empty($moduleData['delete']) ? 1 : 0;

                if ($canView || $canAdd || $canEdit || $canDelete) {
                    $insertActionStmt->execute([$roleId, $permissionCode, $canView, $canAdd, $canEdit, $canDelete]);
                    if (isset($permissionIdMap[$permissionCode])) {
                        $selectedPermissionIds[] = (int) $permissionIdMap[$permissionCode];
                    }
                }
            }

            if ($hasLegacyRolePermissionsTable) {
                $deleteLegacyStmt = $db->prepare("DELETE FROM `$legacyRolePermissionTable` WHERE role_id = ?");
                $deleteLegacyStmt->execute([$roleId]);

                if (!empty($selectedPermissionIds)) {
                    $insertLegacyStmt = $db->prepare("INSERT INTO `$legacyRolePermissionTable` (role_id, permission_id) VALUES (?, ?)");
                    foreach ($selectedPermissionIds as $permissionId) {
                        $insertLegacyStmt->execute([$roleId, $permissionId]);
                    }
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        reloadCurrentRolePermissions($db, $roleId);

        header("Location: ../roles.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['validation_errors'] = ['general' => 'Không thể xóa hoặc cập nhật vai trò này vì đang có dữ liệu liên quan. Vui lòng chuyển các user sang vai trò khác trước khi thao tác.'];
    header("Location: ../roles.php");
    exit();
}