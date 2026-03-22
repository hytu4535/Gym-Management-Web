<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=pt-booking.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

$stmtMember = $conn->prepare('SELECT id, full_name, phone FROM members WHERE users_id = ? LIMIT 1');
$stmtMember->bind_param('i', $userId);
$stmtMember->execute();
$member = $stmtMember->get_result()->fetch_assoc();
$stmtMember->close();

if (!$member) {
    header('Location: profile.php');
    exit();
}

$trainers = [];
$sqlTrainers = "SELECT id, full_name, type, phone, status
                FROM trainers
                WHERE status IS NULL OR status IN ('active', 'hoạt động')
                ORDER BY full_name ASC";
$resultTrainers = $conn->query($sqlTrainers);
if ($resultTrainers) {
    while ($row = $resultTrainers->fetch_assoc()) {
        $trainers[] = $row;
    }
}

include 'layout/header.php';
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2><i class="fa fa-handshake-o"></i> Đặt Lịch PT 1-1</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="my-schedules.php">Lịch tập</a>
                        <span>Đặt lịch PT</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="spad">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div style="background:#111;border:1px solid #2a2a2a;border-radius:10px;padding:28px;">
                    <h4 style="color:#fff;margin-bottom:8px;">Hẹn lịch tập riêng với huấn luyện viên</h4>
                    <p style="color:#aaa;margin-bottom:22px;">Bạn chọn HLV, thời gian mong muốn và ghi chú mục tiêu tập.</p>

                    <div id="pt-booking-message" style="display:none;padding:10px 14px;border-radius:6px;margin-bottom:16px;"></div>

                    <form id="pt-booking-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color:#ddd;">Hội viên</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['full_name']); ?>" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color:#ddd;">Số điện thoại</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="color:#ddd;">Chọn huấn luyện viên</label>
                            <select class="form-control" name="trainer_id" required>
                                <option value="">-- Chọn HLV --</option>
                                <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo (int) $trainer['id']; ?>">
                                    <?php echo htmlspecialchars($trainer['full_name']); ?>
                                    <?php if (!empty($trainer['type'])): ?>
                                        - <?php echo htmlspecialchars($trainer['type']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="color:#ddd;">Thời gian hẹn tập</label>
                            <input type="datetime-local" class="form-control" name="training_date" required>
                            <small style="color:#888;">Nên đặt lịch trước ít nhất 30 phút.</small>
                        </div>

                        <div class="form-group">
                            <label style="color:#ddd;">Mục tiêu / Ghi chú buổi tập</label>
                            <textarea class="form-control" name="note" rows="4" placeholder="Ví dụ: Tập thân dưới, cải thiện sức bền, cần PT hỗ trợ kỹ thuật squat..."></textarea>
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" class="primary-btn" id="btn-submit-pt" style="border:none;cursor:pointer;">
                                <i class="fa fa-check"></i> Xác nhận đặt lịch
                            </button>
                            <a href="my-schedules.php" class="primary-btn" style="background:#333;">Xem lịch tập của tôi</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$conn->close();
include 'layout/footer.php';
?>

<script>
(function () {
    var form = document.getElementById('pt-booking-form');
    var btn = document.getElementById('btn-submit-pt');
    var messageBox = document.getElementById('pt-booking-message');

    function showMessage(text, ok) {
        messageBox.style.display = 'block';
        messageBox.textContent = text;
        messageBox.style.background = ok ? '#d4edda' : '#f8d7da';
        messageBox.style.color = ok ? '#155724' : '#721c24';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        btn.disabled = true;

        var formData = new URLSearchParams(new FormData(form));

        fetch('ajax/pt-booking-process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            showMessage(data.message, !!data.success);
            if (data.success) {
                setTimeout(function () {
                    window.location.href = 'my-schedules.php';
                }, 1200);
            } else {
                btn.disabled = false;
            }
        })
        .catch(function () {
            showMessage('Không thể kết nối đến máy chủ.', false);
            btn.disabled = false;
        });
    });
})();
</script>
