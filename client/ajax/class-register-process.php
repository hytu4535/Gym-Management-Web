<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('class-register.php?id=' . intval($_GET['id'] ?? 0)));
    exit();
}

$member_id = intval($_SESSION['user_id']);
$class_id  = intval($_GET['id'] ?? 0);

if ($class_id <= 0) {
    header('Location: classes.php');
    exit();
}

// Lấy thông tin lớp
$stmt = $conn->prepare("
    SELECT cs.*, t.full_name AS trainer_name, t.type AS trainer_type, t.phone AS trainer_phone
    FROM class_schedules cs
    LEFT JOIN trainers t ON cs.trainer_id = t.id
    WHERE cs.id = ? AND cs.status = 'active'
");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    header('Location: classes.php');
    exit();
}

// Kiểm tra trạng thái đăng ký
$stmt = $conn->prepare("SELECT id, status FROM class_registrations WHERE member_id = ? AND class_id = ?");
$stmt->bind_param('ii', $member_id, $class_id);
$stmt->execute();
$existing_reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_registered = $existing_reg && $existing_reg['status'] === 'active';
$is_full       = $class['enrolled_count'] >= $class['capacity'];

$conn->close();

include 'layout/header.php';

$type_icons = [
    'cardio'   => 'fa-heartbeat',
    'yoga'     => 'fa-leaf',
    'strength' => 'fa-bold',
    'hiit'     => 'fa-fire',
    'boxing'   => 'fa-hand-rock-o',
];
$icon = $type_icons[strtolower($class['class_type'] ?? '')] ?? 'fa-calendar-check-o';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Đăng ký lớp tập</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="classes.php">Lớp tập</a>
                        <span><?php echo htmlspecialchars($class['class_name']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<style>
.register-wrapper { max-width: 760px; margin: 0 auto; padding: 60px 15px; }
.class-detail-card { background: #1a1a1a; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.3); }
.class-detail-header { background: #f36100; padding: 24px 30px; }
.class-detail-header h3 { color: #fff; margin: 0; font-size: 24px; font-weight: 700; text-transform: uppercase; }
.class-detail-header span { color: rgba(255,255,255,.85); font-size: 14px; }
.class-detail-body { padding: 30px; }
.info-row { display: flex; flex-wrap: wrap; gap: 0; border-bottom: 1px solid #2a2a2a; }
.info-row:last-of-type { border-bottom: none; }
.info-item { flex: 1; min-width: 220px; padding: 14px 0; color: #ccc; font-size: 15px; }
.info-item i { color: #f36100; width: 20px; margin-right: 8px; }
.info-item strong { color: #fff; }
.capacity-bar { height: 8px; background: #333; border-radius: 4px; margin-top: 8px; overflow: hidden; }
.capacity-fill { height: 100%; border-radius: 4px; background: #f36100; transition: width .4s; }
.capacity-fill.full { background: #e74c3c; }
.status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-top: 4px; }
.status-available   { background: #d4edda; color: #155724; }
.status-registered  { background: #cce5ff; color: #004085; }
.status-full        { background: #f8d7da; color: #721c24; }
.action-area { background: #111; padding: 24px 30px; border-top: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.btn-register-confirm { background: #f36100; color: #fff; border: none; padding: 12px 36px; border-radius: 5px; font-size: 15px; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: background .2s; }
.btn-register-confirm:hover { background: #d45500; }
.btn-cancel-confirm { background: transparent; color: #e74c3c; border: 2px solid #e74c3c; padding: 12px 36px; border-radius: 5px; font-size: 15px; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: .2s; }
.btn-cancel-confirm:hover { background: #e74c3c; color: #fff; }
.btn-back { color: #aaa; text-decoration: none; font-size: 14px; }
.btn-back:hover { color: #fff; }
#msg-box { padding: 12px 20px; border-radius: 5px; display: none; font-weight: 600; }
#msg-box.success { background: #d4edda; color: #155724; display: block; }
#msg-box.error   { background: #f8d7da; color: #721c24; display: block; }
</style>

<section>
    <div class="register-wrapper">

        <div id="msg-box"></div>

        <div class="class-detail-card">

            <!-- Header -->
            <div class="class-detail-header">
                <h3><i class="fa <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($class['class_name']); ?></h3>
                <span><?php echo htmlspecialchars(strtoupper($class['class_type'] ?? '')); ?></span>
            </div>

            <!-- Body -->
            <div class="class-detail-body">

                <div class="info-row">
                    <?php if (!empty($class['schedule_days'])): ?>
                    <div class="info-item">
                        <i class="fa fa-calendar"></i>
                        Lịch tập: <strong><?php echo htmlspecialchars($class['schedule_days']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($class['schedule_time'])): ?>
                    <div class="info-item">
                        <i class="fa fa-clock-o"></i>
                        Giờ tập: <strong><?php echo htmlspecialchars($class['schedule_time']); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="info-row">
                    <?php if (!empty($class['room'])): ?>
                    <div class="info-item">
                        <i class="fa fa-map-marker"></i>
                        Phòng: <strong><?php echo htmlspecialchars($class['room']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <i class="fa fa-user"></i>
                        HLV: <strong><?php echo htmlspecialchars($class['trainer_name'] ?? 'Chưa có'); ?></strong>
                        <?php if (!empty($class['trainer_type'])): ?>
                        <span style="color:#888;font-size:13px;"> (<?php echo htmlspecialchars($class['trainer_type']); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($class['trainer_phone'])): ?>
                <div class="info-row">
                    <div class="info-item">
                        <i class="fa fa-phone"></i>
                        Liên hệ HLV: <strong><?php echo htmlspecialchars($class['trainer_phone']); ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sĩ số -->
                <div style="margin-top: 20px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="color:#ccc;">
                            <i class="fa fa-users" style="color:#f36100;"></i>
                            Sĩ số: <strong style="color:#fff;"><?php echo $class['enrolled_count']; ?>/<?php echo $class['capacity']; ?></strong>
                        </span>
                        <?php if ($is_registered): ?>
                            <span class="status-badge status-registered"><i class="fa fa-check-circle"></i> Đã đăng ký</span>
                        <?php elseif ($is_full): ?>
                            <span class="status-badge status-full"><i class="fa fa-ban"></i> Đã đầy</span>
                        <?php else: ?>
                            <span class="status-badge status-available"><i class="fa fa-circle"></i> Còn chỗ</span>
                        <?php endif; ?>
                    </div>
                    <?php $pct = $class['capacity'] > 0 ? round($class['enrolled_count'] / $class['capacity'] * 100) : 0; ?>
                    <div class="capacity-bar">
                        <div class="capacity-fill <?php echo $is_full ? 'full' : ''; ?>" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <p style="color:#888;font-size:12px;margin-top:4px;text-align:right;">
                        Còn <?php echo max(0, $class['capacity'] - $class['enrolled_count']); ?> chỗ trống
                    </p>
                </div>

            </div><!-- /body -->

            <!-- Action -->
            <div class="action-area">
                <a href="classes.php" class="btn-back"><i class="fa fa-arrow-left"></i> Quay lại danh sách</a>

                <?php if ($is_registered): ?>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="my-schedules.php" class="btn-register-confirm" style="text-decoration:none;display:inline-block;line-height:1;">
                        <i class="fa fa-calendar"></i> Xem lịch tập của tôi
                    </a>
                    <button class="btn-cancel-confirm" id="btn-cancel" data-id="<?php echo $class_id; ?>">
                        <i class="fa fa-times"></i> Hủy đăng ký
                    </button>
                </div>

                <?php elseif (!$is_full): ?>
                <button class="btn-register-confirm" id="btn-register" data-id="<?php echo $class_id; ?>">
                    <i class="fa fa-check"></i> Xác nhận đăng ký
                </button>

                <?php else: ?>
                <button class="btn-register-confirm" disabled style="opacity:.5;cursor:not-allowed;">
                    <i class="fa fa-ban"></i> Lớp đã đầy
                </button>
                <?php endif; ?>
            </div>

        </div><!-- /card -->

    </div>
</section>

<?php include 'layout/footer.php'; ?>

<script>
function showMsg(msg, type) {
    var box = document.getElementById('msg-box');
    box.textContent = msg;
    box.className = type;
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function sendRequest(url, classId, btn) {
    btn.disabled = true;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'class_id=' + classId
    })
    .then(r => r.json())
    .then(data => {
        showMsg(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            setTimeout(() => location.reload(), 1200);
        } else {
            btn.disabled = false;
        }
    })
    .catch(() => {
        showMsg('Lỗi kết nối!', 'error');
        btn.disabled = false;
    });
}

var btnReg    = document.getElementById('btn-register');
var btnCancel = document.getElementById('btn-cancel');

if (btnReg) {
    btnReg.addEventListener('click', function() {
        sendRequest('ajax/class-register-process.php', this.dataset.id, this);
    });
}

if (btnCancel) {
    btnCancel.addEventListener('click', function() {
        if (confirm('Bạn có chắc muốn hủy đăng ký lớp này?')) {
            sendRequest('ajax/class-cancel.php', this.dataset.id, this);
        }
    });
}
</script>
