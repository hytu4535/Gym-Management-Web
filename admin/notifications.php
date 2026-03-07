<?php
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $userId = intval($_POST['user_id']);
  $title = sanitize($_POST['title']);
  $content = sanitize($_POST['content']);

  $userCheckStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
  $userCheckStmt->execute([$userId]);
  if ((int) $userCheckStmt->fetchColumn() === 0) {
    echo "<script>alert('Người nhận không tồn tại!');window.location='notifications.php';</script>";
    exit;
  }

  $insertStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
  if ($insertStmt->execute([$userId, $title, $content])) {
    echo "<script>alert('Tạo thông báo thành công!');window.location='notifications.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi tạo thông báo!');window.location='notifications.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_notification_id'])) {
  $notificationId = intval($_POST['mark_read_notification_id']);
  $markReadStmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");

  if ($markReadStmt->execute([$notificationId])) {
    echo "<script>alert('Đã đánh dấu thông báo là đã đọc!');window.location='notifications.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật trạng thái thông báo!');window.location='notifications.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification_id'])) {
  $notificationId = intval($_POST['delete_notification_id']);
  $deleteStmt = $db->prepare("DELETE FROM notifications WHERE id = ?");

  if ($deleteStmt->execute([$notificationId])) {
    echo "<script>alert('Xóa thông báo thành công!');window.location='notifications.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi xóa thông báo!');window.location='notifications.php';</script>";
  }
  exit;
}

$usersStmt = $db->query("SELECT id, username, email FROM users WHERE status = 'active' ORDER BY username ASC");
$users = $usersStmt->fetchAll();

$notificationsStmt = $db->query("SELECT n.id, n.title, n.content, n.created_at, n.is_read, u.username, u.email FROM notifications n INNER JOIN users u ON n.user_id = u.id ORDER BY n.id DESC");
$notifications = $notificationsStmt->fetchAll();

$page_title = "Quản lý Notifications";
include 'layout/header.php';
include 'layout/sidebar.php';
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Notifications</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Notifications</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Notifications</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addNotificationModal">
                    <i class="fas fa-plus"></i> Tạo thông báo
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Nội dung</th>
                    <th>Người nhận</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($notifications as $notification): ?>
                  <tr>
                    <td><?= $notification['id'] ?></td>
                    <td><?= htmlspecialchars($notification['title']) ?></td>
                    <td><?= htmlspecialchars($notification['content']) ?></td>
                    <td><?= htmlspecialchars($notification['username']) ?> (<?= htmlspecialchars($notification['email']) ?>)</td>
                    <td><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></td>
                    <td>
                      <?php if ((int) $notification['is_read'] === 1): ?>
                        <span class="badge badge-success">Đã đọc</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Chưa đọc</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ((int) $notification['is_read'] === 0): ?>
                        <form method="POST" action="notifications.php" style="display:inline-block;">
                          <input type="hidden" name="mark_read_notification_id" value="<?= $notification['id'] ?>">
                          <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                        </form>
                      <?php endif; ?>
                      <form method="POST" action="notifications.php" style="display:inline-block;">
                        <input type="hidden" name="delete_notification_id" value="<?= $notification['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php include 'layout/footer.php'; ?>

<div class="modal fade" id="addNotificationModal" tabindex="-1" role="dialog" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="notifications.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addNotificationModalLabel">Tạo thông báo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (empty($users)): ?>
            <div class="alert alert-warning mb-3">Chưa có người dùng active để gửi thông báo.</div>
          <?php endif; ?>

          <div class="form-group">
            <label for="user_id">Người nhận</label>
            <select class="form-control" id="user_id" name="user_id" required <?= empty($users) ? 'disabled' : '' ?>>
              <option value="">-- Chọn người nhận --</option>
              <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="title">Tiêu đề</label>
            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
          </div>

          <div class="form-group">
            <label for="content">Nội dung</label>
            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" <?= empty($users) ? 'disabled' : '' ?>>Gửi thông báo</button>
        </div>
      </form>
    </div>
  </div>
</div>