<?php
function denyPermissionAccess(): void {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền này',
        ]);
        exit();
    }

    echo "<script>alert('Bạn không có quyền này');if (window.history.length > 1) { window.history.back(); } else { window.location.href='../admin/no_permission.php'; }</script>";
    exit();
}

function checkPermission($permCode, $requiredAction = 'view') {
    $permissions = $_SESSION['permissions'] ?? [];
    $actionPermissions = $_SESSION['user_action_permissions'] ?? [];
    $isAdminRole = !empty($_SESSION['is_admin_role']) || strtolower((string) ($_SESSION['role'] ?? '')) === 'admin';

    if (!is_array($permissions)) {
        denyPermissionAccess();
    }

    if ($isAdminRole) {
        return;
    }

    $actionKeyMap = [
        'view' => 'view',
        'add' => 'add',
        'create' => 'add',
        'edit' => 'edit',
        'update' => 'edit',
        'delete' => 'delete',
        'remove' => 'delete',
    ];

    $normalizedAction = strtolower(trim((string) $requiredAction));
    $normalizedAction = $actionKeyMap[$normalizedAction] ?? 'view';

    $hasActionPermissionModel = is_array($actionPermissions) && !empty($actionPermissions);

    if ($hasActionPermissionModel) {
        if (isset($actionPermissions[$permCode]) && is_array($actionPermissions[$permCode])) {
            if (empty($actionPermissions[$permCode][$normalizedAction])) {
                denyPermissionAccess();
            }
            return;
        }

        if ($normalizedAction === 'view' && in_array($permCode, $permissions, true)) {
            return;
        }

        denyPermissionAccess();
    }

    if (is_array($actionPermissions) && isset($actionPermissions[$permCode])) {
        $actionSet = $actionPermissions[$permCode];
        $isAllowed = !empty($actionSet[$normalizedAction]);
        if (!$isAllowed) {
            denyPermissionAccess();
        }
        return;
    }

    if (!in_array($permCode, $permissions, true)) {
        denyPermissionAccess();
    }

    // Legacy mode: module-level permission only grants view access.
    if ($normalizedAction !== 'view') {
        denyPermissionAccess();
    }
}

function hasActionPermission($permCode, $requiredAction = 'view'): bool {
    $permissions = $_SESSION['permissions'] ?? [];
    $actionPermissions = $_SESSION['user_action_permissions'] ?? [];
    $isAdminRole = !empty($_SESSION['is_admin_role']) || strtolower((string) ($_SESSION['role'] ?? '')) === 'admin';

    if (!is_array($permissions)) {
        return false;
    }

    if ($isAdminRole) {
        return true;
    }

    $actionKeyMap = [
        'view' => 'view',
        'add' => 'add',
        'create' => 'add',
        'edit' => 'edit',
        'update' => 'edit',
        'delete' => 'delete',
        'remove' => 'delete',
    ];

    $normalizedAction = strtolower(trim((string) $requiredAction));
    $normalizedAction = $actionKeyMap[$normalizedAction] ?? 'view';

    $hasActionPermissionModel = is_array($actionPermissions) && !empty($actionPermissions);
    if ($hasActionPermissionModel) {
        if (isset($actionPermissions[$permCode]) && is_array($actionPermissions[$permCode])) {
            return !empty($actionPermissions[$permCode][$normalizedAction]);
        }

        return $normalizedAction === 'view' && in_array($permCode, $permissions, true);
    }

    if (is_array($actionPermissions) && isset($actionPermissions[$permCode])) {
        return !empty($actionPermissions[$permCode][$normalizedAction]);
    }

    // Legacy mode: module-level permission only grants view access.
    return $normalizedAction === 'view' && in_array($permCode, $permissions, true);
}
