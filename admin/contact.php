<?php
session_start();

$page_title = 'Tiếp nhận liên hệ';

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_FEEDBACK');

$db = getDB();

// Đảm bảo bảng tồn tại để tránh lỗi khi hệ thống mới chưa có dữ liệu liên hệ.
$db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    member_id INT DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','closed') DEFAULT 'new',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_user (user_id),
    KEY idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $markReadId = (int) $_POST['mark_read_id'];

    try {
        $markStmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $markStmt->execute([$markReadId]);
        $message = 'Đã cập nhật trạng thái liên hệ.';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Lỗi cập nhật trạng thái: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$listStmt = $db->query("SELECT id, full_name, email, phone, message, status, created_at FROM contact_messages ORDER BY created_at DESC, id DESC");
$contacts = $listStmt->fetchAll();

$statusLabel = [
    'new' => ['Mới', 'warning'],
    'read' => ['Đã xem', 'info'],
    'closed' => ['Đóng', 'secondary'],
];

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Tiếp nhận liên hệ</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Tiếp nhận liên hệ</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
          <?= htmlspecialchars($message) ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Danh sách liên hệ từ khách hàng</h3>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Nội dung liên hệ</th>
                    <th>Thời gian gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($contacts)): ?>
                    <?php foreach ($contacts as $contact): ?>
                      <?php
                        $code = $contact['status'];
                        $label = $statusLabel[$code][0] ?? 'Không xác định';
                        $badge = $statusLabel[$code][1] ?? 'secondary';
                      ?>
                      <tr>
                        <td><?= (int) $contact['id'] ?></td>
                        <td><?= htmlspecialchars($contact['full_name']) ?></td>
                        <td><?= htmlspecialchars($contact['email']) ?></td>
                        <td><?= htmlspecialchars($contact['phone'] ?: 'N/A') ?></td>
                        <td style="max-width: 320px;"><?= nl2br(htmlspecialchars($contact['message'])) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></td>
                        <td><span class="badge badge-<?= $badge ?>"><?= $label ?></span></td>
                        <td>
                          <?php if ($code === 'new'): ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="mark_read_id" value="<?= (int) $contact['id'] ?>">
                              <button type="submit" class="btn btn-info btn-sm" title="Đánh dấu đã xem">
                                <i class="fas fa-check"></i>
                              </button>
                            </form>
                          <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                              <i class="fas fa-check"></i>
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="text-center text-muted">Chưa có liên hệ nào được gửi.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'layout/footer.php'; ?>
