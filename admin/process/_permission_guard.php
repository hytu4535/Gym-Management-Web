<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function processDenyPermission(): void {
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

    echo "<script>alert('Bạn không có quyền này');if (window.history.length > 1) { window.history.back(); } else { window.location.href='../no_permission.php'; }</script>";
    exit();
}

function processRequirePermission(string $permCode, string $requiredAction = 'view'): void {
    if (!isset($_SESSION['admin_logged_in']) || (int) ($_SESSION['admin_user_id'] ?? 0) <= 0) {
        header('Location: ../login.php');
        exit();
    }

    $permissions = $_SESSION['permissions'] ?? [];
    $isAdminRole = !empty($_SESSION['is_admin_role']) || strtolower((string) ($_SESSION['role'] ?? '')) === 'admin';
    if (!is_array($permissions)) {
        processDenyPermission();
    }

    if ($isAdminRole) {
        return;
    }

    $actionPermissions = $_SESSION['user_action_permissions'] ?? [];
    $actionKeyMap = [
        'view' => 'view',
        'add' => 'add',
        'create' => 'add',
        'edit' => 'edit',
        'update' => 'edit',
        'delete' => 'delete',
        'remove' => 'delete',
    ];

    $normalizedAction = strtolower(trim($requiredAction));
    $normalizedAction = $actionKeyMap[$normalizedAction] ?? 'view';

    $hasActionPermissionModel = is_array($actionPermissions) && !empty($actionPermissions);

    if ($hasActionPermissionModel) {
        if (isset($actionPermissions[$permCode]) && is_array($actionPermissions[$permCode])) {
            if (empty($actionPermissions[$permCode][$normalizedAction])) {
                processDenyPermission();
            }
            return;
        }

        if ($normalizedAction === 'view' && in_array($permCode, $permissions, true)) {
            return;
        }

        processDenyPermission();
    }

    if (is_array($actionPermissions) && isset($actionPermissions[$permCode]) && is_array($actionPermissions[$permCode])) {
        if (empty($actionPermissions[$permCode][$normalizedAction])) {
            processDenyPermission();
        }
        return;
    }

    // Fallback cho dữ liệu quyền cũ chưa tách action.
    if (!in_array($permCode, $permissions, true)) {
        processDenyPermission();
    }

    if ($normalizedAction !== 'view') {
        processDenyPermission();
    }
}