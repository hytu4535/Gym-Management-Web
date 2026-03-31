<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

function migrateLegacyRolePermissionsToActionModel(PDO $db, int $roleId): void {
    $hasLegacyRolePermissionsTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' LIMIT 1")->fetchColumn();
    $hasPermissionTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('permission', 'permissions') LIMIT 1")->fetchColumn();
    if (!$hasLegacyRolePermissionsTable || !$hasPermissionTable) {
      return;
    }

    $existingCountStmt = $db->prepare("SELECT COUNT(*) FROM role_action_permissions WHERE role_id = ?");
    $existingCountStmt->execute([$roleId]);
    if ((int) $existingCountStmt->fetchColumn() > 0) {
      return;
    }

    $permissionTable = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('permission', 'permissions') ORDER BY CASE WHEN TABLE_NAME = 'permission' THEN 0 ELSE 1 END LIMIT 1")->fetchColumn();
    if (!$permissionTable) {
      return;
    }

    $legacyRowsStmt = $db->prepare("SELECT DISTINCT p.code FROM role_permissions rp JOIN `$permissionTable` p ON rp.permission_id = p.id WHERE rp.role_id = ?");
    $legacyRowsStmt->execute([$roleId]);
    $codes = $legacyRowsStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($codes)) {
      return;
    }

    $insertStmt = $db->prepare("INSERT INTO role_action_permissions (role_id, permission_code, can_view, can_add, can_edit, can_delete) VALUES (?, ?, 1, 0, 0, 0)");
    foreach ($codes as $code) {
      $permissionCode = trim((string) $code);
      if ($permissionCode === '') {
        continue;
      }
      $insertStmt->execute([$roleId, $permissionCode]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $db = getDB();

    // Lấy thông tin user + role
    $sql = "SELECT u.id, u.username, u.password, u.status, r.id AS role_id, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = ?
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $isPasswordValid = $user ? password_verify($password, $user['password']) : false;
    $isLegacyPasswordValid = $user && !$isPasswordValid && $password === $user['password'];

    // Xác thực bằng bcrypt, vẫn hỗ trợ dữ liệu cũ chưa hash
    if ($user && $user['status'] === 'active' && ($isPasswordValid || $isLegacyPasswordValid)) {
      $permissions = [];
      $userActionPermissions = [];

      $roleName = strtolower(trim((string) ($user['role_name'] ?? '')));
      $isAdminRole = ((int) ($user['role_id'] ?? 0) === 4)
        || $roleName === 'admin'
        || $roleName === 'quản trị viên'
        || $roleName === 'quan tri vien';
      if ($isAdminRole) {
        $permissions = ['MANAGE_ALL'];
        $userActionPermissions['MANAGE_ALL'] = [
          'view' => true,
          'add' => true,
          'edit' => true,
          'delete' => true,
        ];
      } else {
        migrateLegacyRolePermissionsToActionModel($db, (int) $user['role_id']);
        $hasRoleActionPermissionTable = true;

        if ($hasRoleActionPermissionTable) {
          $stmt = $db->prepare("SELECT permission_code, can_view, can_add, can_edit, can_delete FROM role_action_permissions WHERE role_id = ?");
          $stmt->execute([$user['role_id']]);
          $permissionRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

          foreach ($permissionRows as $row) {
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

            $userActionPermissions[$code] = $actionSet;
            if ($actionSet['view'] || $actionSet['add'] || $actionSet['edit'] || $actionSet['delete']) {
              $permissions[] = $code;
            }
          }
        }

        // Fallback cuối cùng cho dữ liệu cũ trong trường hợp migration bất thường.
        $hasRolePermissionsTable = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' LIMIT 1")->fetchColumn();
        $permissionTable = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('permission', 'permissions') ORDER BY CASE WHEN TABLE_NAME = 'permission' THEN 0 ELSE 1 END LIMIT 1")->fetchColumn();
        if (empty($permissions) && $hasRolePermissionsTable && $permissionTable) {
          $sql = "SELECT p.code 
              FROM role_permissions rp
              JOIN `$permissionTable` p ON rp.permission_id = p.id
              WHERE rp.role_id = ?";
          $stmt = $db->prepare($sql);
          $stmt->execute([$user['role_id']]);
          $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
      }

        // Lưu vào session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id']; // thêm dòng này để lưu role_id
        $_SESSION['is_admin_role'] = $isAdminRole;
        $_SESSION['permissions'] = $permissions;
      $_SESSION['user_action_permissions'] = $userActionPermissions;

        header("Location: index.php");
        exit();
      } elseif ($user && $user['status'] !== 'active') {
        $error = "Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.";
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../client/assets/css/style.css">
  <style>
    body {
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
      width: 350px;
    }
    .login-container h2 {
      text-align: center;
      margin-bottom: 20px;
      color: orangered;
    }
    .login-container input {
      width: 95%;
      margin-bottom: 15px;
      padding: 10px;
      border: none;
      border-bottom: 3px solid orangered ;
    }
    .login-container button {
      width: 100%;
      padding: 10px;
      border-radius: 10px;
      background-color: limegreen;
    }
    .login-container button:hover {
      background-color: greenyellow;
    }
    .login-container button:active {
      background-color: green
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Đăng nhập Admin</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST" action="">
      <label>Tên đăng nhập</label>
      <input type="text" name="username" required>
      <label>Mật khẩu</label>
      <input type="password" name="password" required>
      <button type="submit">Đăng nhập</button>
    </form>
  </div>
</body>
</html>