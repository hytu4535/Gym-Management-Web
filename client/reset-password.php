<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../includes/functions.php';
require_once '../config/db.php';

if (!function_exists('ensurePasswordResetColumnsLocal')) {
    function ensurePasswordResetColumnsLocal($conn) {
        $columns = [
            'reset_token_hash' => "ALTER TABLE users ADD COLUMN reset_token_hash varchar(64) DEFAULT NULL AFTER avatar",
            'reset_token_expires_at' => "ALTER TABLE users ADD COLUMN reset_token_expires_at datetime DEFAULT NULL AFTER reset_token_hash",
        ];

        foreach ($columns as $columnName => $alterSql) {
            $checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
            if (!$checkStmt) {
                throw new Exception('Không thể kiểm tra cấu trúc bảng users.');
            }

            $checkStmt->bind_param('s', $columnName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $columnExists = false;

            if ($result) {
                $row = $result->fetch_assoc();
                $columnExists = ((int) ($row['total'] ?? 0)) > 0;
            }

            $checkStmt->close();

            if (!$columnExists && !$conn->query($alterSql)) {
                throw new Exception('Không thể khởi tạo chức năng quên mật khẩu.');
            }
        }
    }
}

$token = trim($_GET['token'] ?? '');
$isTokenValid = false;

if ($token !== '') {
    try {
        ensurePasswordResetColumnsLocal($conn);

        $tokenHash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ? LIMIT 1");
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $expiresAt = $row['reset_token_expires_at'] ?? null;
            $isTokenValid = !empty($expiresAt) && strtotime($expiresAt) >= time();
        }

        $stmt->close();
    } catch (Exception $e) {
        $isTokenValid = false;
    }
}

include 'layout/header.php';
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Đặt lại mật khẩu</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Đặt lại mật khẩu</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="login-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 offset-lg-3">
                <div class="login-form">
                    <h2>Đặt lại mật khẩu</h2>

                    <?php if ($token === ''): ?>
                        <div class="alert alert-danger">Thiếu mã khôi phục mật khẩu.</div>
                    <?php elseif (!$isTokenValid): ?>
                        <div class="alert alert-danger">Mã khôi phục không hợp lệ hoặc đã hết hạn.</div>
                    <?php else: ?>
                        <p class="mb-4">Nhập mật khẩu mới cho tài khoản của bạn.</p>

                        <form id="reset-password-form">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-group">
                                <label>Mật khẩu mới <span>*</span></label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Xác nhận mật khẩu mới <span>*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <div id="message-container" class="mt-3 mb-3"></div>
                            <button type="submit" class="site-btn w-100">Cập nhật mật khẩu</button>
                        </form>
                    <?php endif; ?>

                    <div class="switch-login mt-3 text-center">
                        <p><a href="login.php">Quay lại đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($token !== '' && $isTokenValid): ?>
<script>
document.getElementById('reset-password-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    var btnSubmit = this.querySelector('button[type="submit"]');
    var originalText = btnSubmit.innerHTML;

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

    fetch('ajax/reset-password-process.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showMessage(data.message || 'Đặt lại mật khẩu thành công!', 'success');
            setTimeout(function() {
                window.location.href = 'login.php?reset=success';
            }, 1200);
        } else {
            showMessage(data.message || 'Không thể cập nhật mật khẩu.', 'error');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(function(err) {
        console.error(err);
        showMessage('Có lỗi xảy ra khi cập nhật mật khẩu!', 'error');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    });
});

function showMessage(message, type) {
    var container = document.getElementById('message-container');
    container.innerHTML = '<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>';
}
</script>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>