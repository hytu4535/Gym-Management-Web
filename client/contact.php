<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// Bảng lưu tiếp nhận liên hệ từ client.
$conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
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

$isLoggedIn = isset($_SESSION['user_id']);
$profile = null;
$submitMessage = '';
$submitType = 'success';

if ($isLoggedIn) {
    $profileStmt = $conn->prepare(
        "SELECT
            u.id AS user_id,
            u.email,
            u.username,
            m.id AS member_id,
            m.full_name,
            m.phone
         FROM users u
         LEFT JOIN members m ON m.users_id = u.id
         WHERE u.id = ?
         LIMIT 1"
    );
    $profileStmt->bind_param("i", $_SESSION['user_id']);
    $profileStmt->execute();
    $profile = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn || !$profile) {
        $submitMessage = 'Vui lòng đăng nhập để gửi liên hệ.';
        $submitType = 'danger';
    } else {
        $message = trim($_POST['message'] ?? '');

        if ($message === '') {
            $submitMessage = 'Vui lòng nhập nội dung liên hệ.';
            $submitType = 'danger';
        } else {
            $fullName = trim($profile['full_name'] ?? '');
            if ($fullName === '') {
                $fullName = trim($_SESSION['full_name'] ?? ($profile['username'] ?? 'Thành viên'));
            }

            $email = trim($profile['email'] ?? '');
            $phone = trim($profile['phone'] ?? '');
            $userId = (int) $profile['user_id'];
            $memberId = !empty($profile['member_id']) ? (int) $profile['member_id'] : null;

            $insertStmt = $conn->prepare(
                "INSERT INTO contact_messages (user_id, member_id, full_name, email, phone, message)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $insertStmt->bind_param("iissss", $userId, $memberId, $fullName, $email, $phone, $message);

            if ($insertStmt->execute()) {
                $submitMessage = 'Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi bạn sớm nhất.';
                $submitType = 'success';
            } else {
                $submitMessage = 'Không thể gửi liên hệ lúc này. Vui lòng thử lại.';
                $submitType = 'danger';
            }

            $insertStmt->close();
        }
    }
}

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Liên hệ</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Liên hệ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Contact Section Begin -->
<section class="contact-section spad">
    <div class="container">
        <?php if ($submitMessage !== ''): ?>
            <div class="alert alert-<?= $submitType ?> text-center">
                <?= htmlspecialchars($submitMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <div class="alert alert-warning text-center">
                Bạn cần đăng nhập để gửi liên hệ. <a href="login.php?redirect=contact.php">Đăng nhập ngay</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">
                <div class="section-title contact-title">
                    <span>Liên hệ với chúng tôi</span>
                    <h2>GỬI TIN NHẮN</h2>
                </div>
                <div class="contact-widget">
                    <div class="cw-text">
                        <i class="fa fa-map-marker"></i>
                        <p>18/16 Phan Văn Trị, P.Chợ Quán, Q.5,<br/> TP. Hồ Chí Minh</p>
                    </div>
                    <div class="cw-text">
                        <i class="fa fa-mobile"></i>
                        <ul>
                            <li>(078) 6026-878</li>
                            <li>(028) 8765-4321</li>
                        </ul>
                    </div>
                    <div class="cw-text email">
                        <i class="fa fa-envelope"></i>
                        <p>supporttrungkien@gymcenter.vn</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="leave-comment">
                    <form action="contact.php" method="POST">
                        <input type="text"
                               name="name"
                               placeholder="Tên của bạn"
                               value="<?= htmlspecialchars($profile['full_name'] ?? ($_SESSION['full_name'] ?? '')) ?>"
                               readonly
                               required>
                        <input type="email"
                               name="email"
                               placeholder="Email"
                               value="<?= htmlspecialchars($profile['email'] ?? ($_SESSION['email'] ?? '')) ?>"
                               readonly
                               required>
                        <input type="text"
                               name="phone"
                               placeholder="Số điện thoại"
                               value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                               readonly
                               required>
                        <textarea name="message" placeholder="Nội dung" required></textarea>
                        <button type="submit" class="primary-btn" <?= !$isLoggedIn ? 'disabled' : '' ?>>Gửi tin nhắn</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.518486528941!2d106.68332331533387!3d10.771354262247846!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f38f9ed887b%3A0x14aded5c4f93db44!2sPham%20Ngoc%20Thach%2C%20Dist.%203%2C%20Ho%20Chi%20Minh%20City%2C%20Vietnam!5e0!3m2!1sen!2s!4v1611314771417!5m2!1sen!2s" height="550" style="border:0;" allowfullscreen=""></iframe>
        </div>
    </div>
</section>
<!-- Contact Section End -->

<!-- Get In Touch Section Begin -->
<div class="gettouch-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="gt-text">
                    <i class="fa fa-map-marker"></i>
                    <p>18/16 Phan Văn Trị, P.Chợ Quán, Q.5,<br/> TP. Hồ Chí Minh</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gt-text">
                    <i class="fa fa-mobile"></i>
                    <ul>
                        <li>(078) 6026-878</li>
                        <li>(028) 8765-4321</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gt-text email">
                    <i class="fa fa-envelope"></i>
                    <p>supporttrungkien@gymcenter.vn</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Get In Touch Section End -->

<?php include 'layout/footer.php'; ?>
