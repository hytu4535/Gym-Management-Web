<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

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
        // Lấy danh sách quyền từ bảng role_permissions
        $sql = "SELECT p.code 
                FROM role_permissions rp
                JOIN permission p ON rp.permission_id = p.id
                WHERE rp.role_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Lưu vào session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id']; // thêm dòng này để lưu role_id
        $_SESSION['permissions'] = $permissions;

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
  <link rel="stylesheet" href="../assets/css/style.css">
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
