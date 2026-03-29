<?php
function checkPermission($permCode, $requiredAction = 'view') {
    $permissions = $_SESSION['permissions'] ?? [];
    $actionPermissions = $_SESSION['user_action_permissions'] ?? [];

    if (!is_array($permissions)) {
        header("Location: ../admin/no_permission.php");
        exit();
    }

    if (in_array('MANAGE_ALL', $permissions, true)) {
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

    if (is_array($actionPermissions) && isset($actionPermissions[$permCode])) {
        $actionSet = $actionPermissions[$permCode];
        $isAllowed = !empty($actionSet[$normalizedAction]);
        if (!$isAllowed) {
            header("Location: ../admin/no_permission.php");
            exit();
        }
        return;
    }

    if (!in_array($permCode, $permissions, true)) {
        header("Location: ../admin/no_permission.php");
        exit();
    }
}
