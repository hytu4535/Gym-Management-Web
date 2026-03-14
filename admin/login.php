<?php
session_start();
include '../includes/database.php'; // dùng class Database bạn đã có

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $db = getDB();

    // JOIN users với roles để lấy permission_id
    $sql = "SELECT u.*, r.permission_id, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = ? AND r.permission_id = 1
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // So sánh mật khẩu plain text
        if ($password === $user['password']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_permission'] = $user['permission_id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Không tìm thấy tài khoản có quyền Admin!";
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
    }
    .login-container input {
      width: 100%;
      margin-bottom: 15px;
      padding: 10px;
    }
    .login-container button {
      width: 100%;
      padding: 10px;
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
