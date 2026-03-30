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

require_once '../config/db.php';
require_once '../includes/functions.php';

$db = getDB();

$memberUserIdColumn = 'users_id';
$staffUserIdColumn = 'users_id';

$filterKeyword = trim((string) ($_GET['keyword'] ?? ''));
$filterReadStatus = trim((string) ($_GET['read_status'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_group'])) {
  $group   = $_POST['recipient_group'];
  $recipientIdentifier = trim($_POST['recipient_identifier'] ?? '');
  $title   = sanitize($_POST['title']);
  $content = sanitize($_POST['content']);

  // Build query based on group
  $allowedGroups = ['all', 'admin', 'staff', 'member', 'specific'];
  if (!in_array($group, $allowedGroups)) {
    echo "<script>alert('Nhóm nhận không hợp lệ!');window.location='notifications.php';</script>";
    exit;
  }

  if ($group === 'all') {
    $targetStmt = $db->query("SELECT id FROM users WHERE status = 'active'");
  } elseif ($group === 'specific') {
    if ($recipientIdentifier === '') {
      echo "<script>alert('Vui lòng nhập đúng tên/số điện thoại/gmail của người nhận!');window.location='notifications.php';</script>";
      exit;
    }

    $memberJoinSql = "LEFT JOIN members m ON m.$memberUserIdColumn = u.id";
    $staffJoinSql = "LEFT JOIN staff st ON st.$staffUserIdColumn = u.id";

    $targetStmt = $db->prepare(" 
      SELECT DISTINCT u.id
      FROM users u
      $memberJoinSql
      $staffJoinSql
      WHERE u.status = 'active'
        AND (
          u.username = ?
          OR u.email = ?
          OR m.full_name = ?
          OR st.full_name = ?
          OR m.phone = ?
        )
      LIMIT 1
    ");
    $targetStmt->execute([
      $recipientIdentifier,
      $recipientIdentifier,
      $recipientIdentifier,
      $recipientIdentifier,
      $recipientIdentifier,
    ]);
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
    if ($group === 'specific') {
      echo "<script>alert('Người dùng không tồn tại!');window.location='notifications.php';</script>";
    } else {
      echo "<script>alert('Không có người dùng nào trong nhóm này!');window.location='notifications.php';</script>";
    }
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
  $markReadStmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND is_read = 0");
  $isAjax = isset($_POST['ajax_mark_read']) && $_POST['ajax_mark_read'] === '1';

  if ($markReadStmt->execute([$notificationId])) {
    if ($isAjax) {
      echo json_encode([
        'success' => true,
        'updated' => $markReadStmt->rowCount() > 0,
      ]);
    } else {
      echo "<script>alert('Đã đánh dấu thông báo là đã đọc!');window.location='notifications.php';</script>";
    }
  } else {
    if ($isAjax) {
      echo json_encode([
        'success' => false,
      ]);
    } else {
      echo "<script>alert('Lỗi khi cập nhật trạng thái thông báo!');window.location='notifications.php';</script>";
    }
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

$notificationWhereClauses = [];
$notificationParams = [];
if ($filterKeyword !== '') {
  $notificationWhereClauses[] = '(n.title LIKE ? OR n.content LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
  $keywordValue = '%' . $filterKeyword . '%';
  $notificationParams[] = $keywordValue;
  $notificationParams[] = $keywordValue;
  $notificationParams[] = $keywordValue;
  $notificationParams[] = $keywordValue;
}
if ($filterReadStatus === 'read') {
  $notificationWhereClauses[] = 'n.is_read = 1';
} elseif ($filterReadStatus === 'unread') {
  $notificationWhereClauses[] = 'n.is_read = 0';
}

$notificationSql = "SELECT n.id, n.title, n.content, n.created_at, n.is_read, u.username, u.email FROM notifications n INNER JOIN users u ON n.user_id = u.id";
if (!empty($notificationWhereClauses)) {
  $notificationSql .= ' WHERE ' . implode(' AND ', $notificationWhereClauses);
}
$notificationSql .= ' ORDER BY n.id DESC';

$notificationsStmt = $db->prepare($notificationSql);
$notificationsStmt->execute($notificationParams);
$notifications = $notificationsStmt->fetchAll();

// layout chung
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
        <?php
          $filterMode = 'server';
          $filterAction = 'notifications.php';
          $filterFieldsHtml = '
            <div class="col-md-5"><div class="form-group mb-0"><label>Từ khóa</label><input type="text" name="keyword" class="form-control" value="' . htmlspecialchars($filterKeyword) . '" placeholder="Tiêu đề / nội dung / người nhận"></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Trạng thái đọc</label><select name="read_status" class="form-control"><option value="">-- Tất cả --</option><option value="unread" ' . ($filterReadStatus === 'unread' ? 'selected' : '') . '>Chưa đọc</option><option value="read" ' . ($filterReadStatus === 'read' ? 'selected' : '') . '>Đã đọc</option></select></div></div>
          ';
          include 'layout/filter-card.php';
        ?>
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
                <table class="table table-bordered table-striped data-table js-admin-table" id="notificationsTable">
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
                    <td><?= htmlspecialchars(mb_strimwidth($notification['title'], 0, 15, '...')) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($notification['content'], 0, 15, '...')) ?></td>
                    <td><?= htmlspecialchars($notification['username']) ?> (<?= htmlspecialchars($notification['email']) ?>)</td>
                    <td><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></td>
                    <td>
                      <?php if ((int) $notification['is_read'] === 1): ?>
                        <span class="badge badge-success notification-status-badge" data-notification-id="<?= (int) $notification['id'] ?>">Đã đọc</span>
                      <?php else: ?>
                        <span class="badge badge-warning notification-status-badge" data-notification-id="<?= (int) $notification['id'] ?>">Chưa đọc</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button
                        type="button"
                        class="btn btn-info btn-sm view-notification-btn"
                        data-id="<?= (int) $notification['id'] ?>"
                        data-title="<?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?>"
                        data-content="<?= htmlspecialchars($notification['content'], ENT_QUOTES, 'UTF-8') ?>"
                        data-recipient="<?= htmlspecialchars($notification['username'] . ' (' . $notification['email'] . ')', ENT_QUOTES, 'UTF-8') ?>"
                        data-date="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['created_at'])), ENT_QUOTES, 'UTF-8') ?>"
                        data-is-read="<?= (int) $notification['is_read'] ?>"
                        title="Xem thông báo"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
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
      <form id="addNotificationForm" method="POST" action="notifications.php" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="addNotificationModalLabel">Tạo thông báo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="recipient_group">Gửi tới</label>
            <select class="form-control" id="recipient_group" name="recipient_group">
              <option value="all">Tất cả mọi người</option>
              <option value="admin">Admin</option>
              <option value="staff">Nhân viên (Staff)</option>
              <option value="member">Thành viên (Member)</option>
              <option value="specific">Người dùng cụ thể</option>
            </select>
            <small class="text-danger d-none validation-error"></small>
          </div>

          <div class="form-group" id="specific_recipient_group" style="display:none;">
            <label for="recipient_identifier">Tên / Số điện thoại / Gmail người nhận</label>
            <input type="text" class="form-control" id="recipient_identifier" name="recipient_identifier" placeholder="Nhập đúng username, số điện thoại hoặc email">
            <small class="form-text text-muted">Hệ thống chỉ gửi khi khớp chính xác thông tin người dùng.</small>
            <small class="text-danger d-none validation-error"></small>
          </div>

          <div class="form-group">
            <label for="title">Tiêu đề</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="255">
            <small class="text-danger d-none validation-error"></small>
          </div>

          <div class="form-group">
            <label for="content">Nội dung</label>
            <textarea class="form-control" id="content" name="content" rows="4"></textarea>
            <small class="text-danger d-none validation-error"></small>
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

<div class="modal fade" id="viewNotificationModal" tabindex="-1" role="dialog" aria-labelledby="viewNotificationModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewNotificationModalLabel">Chi tiết thông báo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><strong>Tiêu đề:</strong> <span id="view_notification_title">-</span></div>
        <div class="mb-2"><strong>Người nhận:</strong> <span id="view_notification_recipient">-</span></div>
        <div class="mb-3"><strong>Ngày gửi:</strong> <span id="view_notification_date">-</span></div>
        <div>
          <strong>Nội dung:</strong>
          <div id="view_notification_content" class="border rounded p-2 mt-1" style="white-space: pre-wrap; min-height: 80px;">-</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const allowedGroups = ['all', 'admin', 'staff', 'member', 'specific'];

    function setFieldError($field, message) {
      const $error = $field.closest('.form-group').find('.validation-error').last();
      $field.addClass('is-invalid');
      $error.text(message).removeClass('d-none');
    }

    function clearFieldError($field) {
      const $error = $field.closest('.form-group').find('.validation-error').last();
      $field.removeClass('is-invalid');
      $error.text('').addClass('d-none');
    }

    function validateNotificationForm() {
      let isValid = true;
      const group = $('#recipient_group').val();
      const recipientIdentifier = $('#recipient_identifier').val().trim();
      const title = $('#title').val().trim();
      const content = $('#content').val().trim();

      $('#addNotificationForm').find('.form-control').each(function () {
        clearFieldError($(this));
      });

      if (!allowedGroups.includes(group)) {
        setFieldError($('#recipient_group'), 'Vui lòng chọn nhóm người nhận hợp lệ.');
        isValid = false;
      }

      if (group === 'specific' && !recipientIdentifier) {
        setFieldError($('#recipient_identifier'), 'Vui lòng nhập người nhận cụ thể.');
        isValid = false;
      }

      if (!title) {
        setFieldError($('#title'), 'Vui lòng nhập tiêu đề.');
        isValid = false;
      }

      if (!content) {
        setFieldError($('#content'), 'Vui lòng nhập nội dung.');
        isValid = false;
      }

      return isValid;
    }

    function toggleSpecificRecipientInput() {
      const group = $('#recipient_group').val();
      const isSpecific = group === 'specific';
      $('#specific_recipient_group').toggle(isSpecific);

      if (!isSpecific) {
        $('#recipient_identifier').val('');
        clearFieldError($('#recipient_identifier'));
      }
    }

    $('#recipient_group').on('change', toggleSpecificRecipientInput);

    $('#addNotificationForm').on('submit', function (e) {
      if (!validateNotificationForm()) {
        e.preventDefault();
      }
    });

    $('#addNotificationForm').find('.form-control').on('input change', function () {
      clearFieldError($(this));
    });

    $('#addNotificationModal').on('shown.bs.modal', function () {
      toggleSpecificRecipientInput();
    });

    $('#addNotificationModal').on('hidden.bs.modal', function () {
      $('#addNotificationForm').find('.form-control').each(function () {
        clearFieldError($(this));
      });
    });

    function setBadgeRead(notificationId) {
      const $badge = $('.notification-status-badge[data-notification-id="' + notificationId + '"]');
      if ($badge.length) {
        $badge.removeClass('badge-warning').addClass('badge-success').text('Đã đọc');
      }
    }

    function markNotificationRead(notificationId) {
      return $.ajax({
        url: 'notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
          mark_read_notification_id: notificationId,
          ajax_mark_read: '1'
        }
      });
    }

    $(document).on('click', '.view-notification-btn', function () {
      const $button = $(this);
      const notificationId = Number($button.data('id')) || 0;
      const isRead = Number($button.data('is-read')) === 1;

      $('#view_notification_title').text($button.data('title') || '-');
      $('#view_notification_recipient').text($button.data('recipient') || '-');
      $('#view_notification_date').text($button.data('date') || '-');
      $('#view_notification_content').text($button.data('content') || '-');

      $('#viewNotificationModal').modal('show');

      if (!isRead && notificationId > 0) {
        markNotificationRead(notificationId)
          .done(function (response) {
            if (response && response.success) {
              $button.attr('data-is-read', '1');
              setBadgeRead(notificationId);
            }
          });
      }
    });
  })();
</script>