<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Notifications";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_FEEDBACK');

// layout chung
include 'layout/header.php'; 
include 'layout/sidebar.php';

require_once '../config/db.php';
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_group'])) {
  $group   = $_POST['recipient_group'];
  $title   = sanitize($_POST['title']);
  $content = sanitize($_POST['content']);

  // Build query based on group
  $allowedGroups = ['all', 'admin', 'staff', 'member'];
  if (!in_array($group, $allowedGroups)) {
    echo "<script>alert('Nhóm nhận không hợp lệ!');window.location='notifications.php';</script>";
    exit;
  }

  if ($group === 'all') {
    $targetStmt = $db->query("SELECT id FROM users WHERE status = 'active'");
  } else {
    $targetStmt = $db->prepare("
      SELECT u.id FROM users u
      JOIN roles r ON r.id = u.role_id
      WHERE u.status = 'active' AND r.name = ?
    ");
    $targetStmt->execute([$group]);
  }
  $targets = $targetStmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($targets)) {
    echo "<script>alert('Không có người dùng nào trong nhóm này!');window.location='notifications.php';</script>";
    exit;
  }

  $insertStmt = $db->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
  $db->beginTransaction();
  try {
    foreach ($targets as $uid) {
      $insertStmt->execute([$uid, $title, $content]);
    }
    $db->commit();
    $count = count($targets);
    echo "<script>alert('Đã gửi thông báo cho $count người!');window.location='notifications.php';</script>";
  } catch (Exception $e) {
    $db->rollBack();
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

$notificationsStmt = $db->query("SELECT n.id, n.title, n.content, n.created_at, n.is_read, u.username, u.email FROM notifications n INNER JOIN users u ON n.user_id = u.id ORDER BY n.id DESC");
$notifications = $notificationsStmt->fetchAll();

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
          <div class="form-group">
            <label for="recipient_group">Gửi tới</label>
            <select class="form-control" id="recipient_group" name="recipient_group" required>
              <option value="all">Tất cả mọi người</option>
              <option value="admin">Admin</option>
              <option value="staff">Nhân viên (Staff)</option>
              <option value="member">Thành viên (Member)</option>
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
          <button type="submit" class="btn btn-primary">Gửi thông báo</button>
        </div>
      </form>
    </div>
  </div>
</div>