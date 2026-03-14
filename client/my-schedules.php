<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my-schedules.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

$stmtMember = $conn->prepare("SELECT id FROM members WHERE users_id = ? LIMIT 1");
$stmtMember->bind_param('i', $user_id);
$stmtMember->execute();
$memberData = $stmtMember->get_result()->fetch_assoc();
$stmtMember->close();

if (!$memberData) {
    include 'layout/header.php';
    echo '<section class="spad"><div class="container"><div style="text-align:center;padding:40px;color:#888;">Không tìm thấy hồ sơ hội viên.</div></div></section>';
    include 'layout/footer.php';
    exit();
}

$member_id = (int) $memberData['id'];

// ── Tab 1: Lịch hẹn PT (training_schedules) ──────────────────────────────────
$sql_ts = "SELECT 
                ts.id,
                ts.training_date,
                ts.note,
                t.full_name  AS trainer_name,
                t.type       AS trainer_type,
                t.phone      AS trainer_phone
           FROM training_schedules ts
           LEFT JOIN trainers t ON ts.trainer_id = t.id
           WHERE ts.member_id = ?
           ORDER BY ts.training_date DESC";
$stmt = $conn->prepare($sql_ts);
$stmt->bind_param('i', $member_id);
$stmt->execute();
$ts_result = $stmt->get_result();
$stmt->close();

// ── Tab 2: Lịch tập cá nhân (member_training_schedules) ──────────────────────
$sql_mts = "SELECT 
                mts.id,
                mts.training_date,
                mts.duration,
                mts.activity_type,
                mts.intensity,
                mts.calories_burned,
                mts.note,
                mts.status,
                t.full_name AS trainer_name
            FROM member_training_schedules mts
            LEFT JOIN trainers t ON mts.trainer_id = t.id
            WHERE mts.member_id = ?
            ORDER BY mts.training_date DESC";
$stmt = $conn->prepare($sql_mts);
$stmt->bind_param('i', $member_id);
$stmt->execute();
$mts_result = $stmt->get_result();
$stmt->close();

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2><i class="fa fa-calendar-check-o"></i> Lịch tập của tôi</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="profile.php">Tài khoản</a>
                        <span>Lịch tập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<style>
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; }
.sidebar-item:hover, .sidebar-item.active { background:#f36100; color:#fff; text-decoration:none; }
.sidebar-item i { margin-right:8px; width:16px; }
.profile-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:20px; }
.user-avatar { color:#f36100; }
.tab-nav { display:flex; gap:10px; margin-bottom:20px; }
.tab-btn { padding:10px 24px; border:2px solid #f36100; background:transparent; color:#f36100; border-radius:5px; cursor:pointer; font-weight:600; transition:.2s; }
.tab-btn.active { background:#f36100; color:#fff; }
.tab-content { display:none; }
.tab-content.active { display:block; }
.schedule-card { background:#fff; border-radius:8px; padding:20px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,.07); border-left:4px solid #f36100; }
.schedule-card.past { border-left-color:#aaa; opacity:.8; }
.schedule-card .meta { display:flex; flex-wrap:wrap; gap:15px; margin-top:10px; font-size:14px; color:#555; }
.schedule-card .meta span i { margin-right:4px; color:#f36100; }
.badge-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.badge-status.du-kien   { background:#fff3cd; color:#856404; }
.badge-status.dang-tap  { background:#cce5ff; color:#004085; }
.badge-status.hoan-thanh{ background:#d4edda; color:#155724; }
.badge-status.huy       { background:#f8d7da; color:#721c24; }
.intensity-bar { display:inline-block; width:60px; height:6px; border-radius:3px; vertical-align:middle; margin-left:4px; }
.intensity-thap       { background:#28a745; width:25%; }
.intensity-trung-binh { background:#ffc107; width:50%; }
.intensity-cao        { background:#fd7e14; width:75%; }
.intensity-rat-cao    { background:#dc3545; width:100%; }
</style>

<!-- My Schedules Section Begin -->
<section class="spad">
    <div class="container">
        <div class="row">

            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <i class="fa fa-user-circle fa-5x"></i>
                        </div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#888;font-size:13px;"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                    </div>
                    <hr>
                    <div class="sidebar-menu">
                        <a href="profile.php" class="sidebar-item"><i class="fa fa-user"></i> Thông tin cá nhân</a>
                        <a href="my-packages.php" class="sidebar-item"><i class="fa fa-ticket"></i> Gói tập của tôi</a>
                        <a href="my-schedules.php" class="sidebar-item active"><i class="fa fa-calendar"></i> Lịch tập của tôi</a>
                        <a href="order-history.php" class="sidebar-item"><i class="fa fa-shopping-bag"></i> Lịch sử mua hàng</a>
                        <a href="addresses.php" class="sidebar-item"><i class="fa fa-map-marker"></i> Địa chỉ</a>
                        <a href="logout.php" class="sidebar-item text-danger"><i class="fa fa-sign-out"></i> Đăng xuất</a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">

                <!-- Tab navigation -->
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="tab-pt">
                        <i class="fa fa-handshake-o"></i> Lịch hẹn PT
                    </button>
                    <button class="tab-btn" data-tab="tab-personal">
                        <i class="fa fa-list-alt"></i> Lịch tập cá nhân
                    </button>
                </div>

                <!-- ── TAB 1: Lịch hẹn PT ─────────────────────────────── -->
                <div id="tab-pt" class="tab-content active">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                        <h5 style="margin:0;">Lịch hẹn với huấn luyện viên</h5>
                    </div>

                    <?php if ($ts_result && $ts_result->num_rows > 0): ?>
                        <?php while ($ts = $ts_result->fetch_assoc()):
                            $dt      = new DateTime($ts['training_date']);
                            $is_past = $dt < new DateTime();
                            $day_vi  = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
                            $weekday = $day_vi[$dt->format('w')];
                        ?>
                        <div class="schedule-card <?php echo $is_past ? 'past' : ''; ?>">
                            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <strong style="font-size:16px;">
                                        <?php echo $weekday; ?>, <?php echo $dt->format('d/m/Y'); ?>
                                    </strong>
                                    <?php if ($is_past): ?>
                                    <span class="badge-status hoan-thanh" style="margin-left:8px;">Đã qua</span>
                                    <?php else: ?>
                                    <span class="badge-status du-kien" style="margin-left:8px;">Sắp tới</span>
                                    <?php endif; ?>
                                </div>
                                <strong style="color:#f36100;font-size:18px;">
                                    <i class="fa fa-clock-o"></i> <?php echo $dt->format('H:i'); ?>
                                </strong>
                            </div>
                            <div class="meta">
                                <?php if (!empty($ts['trainer_name'])): ?>
                                <span><i class="fa fa-user"></i> HLV: <?php echo htmlspecialchars($ts['trainer_name']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($ts['trainer_type'])): ?>
                                <span><i class="fa fa-briefcase"></i> <?php echo htmlspecialchars($ts['trainer_type']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($ts['trainer_phone'])): ?>
                                <span><i class="fa fa-phone"></i> <?php echo htmlspecialchars($ts['trainer_phone']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($ts['note'])): ?>
                                <span><i class="fa fa-sticky-note-o"></i> <?php echo htmlspecialchars($ts['note']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:#888;">
                        <i class="fa fa-calendar-times-o fa-3x" style="color:#ddd;"></i>
                        <p style="margin-top:15px;">Chưa có lịch hẹn nào với PT.</p>
                        <a href="contact.php" class="primary-btn" style="display:inline-block;padding:10px 24px;margin-top:10px;">
                            <i class="fa fa-phone"></i> Liên hệ đặt lịch
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── TAB 2: Lịch tập cá nhân ───────────────────────── -->
                <div id="tab-personal" class="tab-content">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                        <h5 style="margin:0;">Lịch tập cá nhân</h5>
                    </div>

                    <?php
                    $intensity_map = [
                        'thấp'      => ['css' => 'intensity-thap',       'label' => 'Thấp'],
                        'trung bình'=> ['css' => 'intensity-trung-binh', 'label' => 'Trung bình'],
                        'cao'       => ['css' => 'intensity-cao',        'label' => 'Cao'],
                        'rất cao'   => ['css' => 'intensity-rat-cao',    'label' => 'Rất cao'],
                    ];
                    $status_map = [
                        'dự kiến'   => 'du-kien',
                        'đang tập'  => 'dang-tap',
                        'hoàn thành'=> 'hoan-thanh',
                        'huỷ'       => 'huy',
                    ];
                    ?>

                    <?php if ($mts_result && $mts_result->num_rows > 0): ?>
                        <?php while ($mts = $mts_result->fetch_assoc()):
                            $dt      = new DateTime($mts['training_date']);
                            $day_vi  = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
                            $weekday = $day_vi[$dt->format('w')];
                            $intensity_css   = $intensity_map[$mts['intensity'] ?? '']['css']   ?? '';
                            $intensity_label = $intensity_map[$mts['intensity'] ?? '']['label'] ?? $mts['intensity'];
                            $status_css      = $status_map[$mts['status'] ?? ''] ?? 'du-kien';
                            $display_note    = $mts['note'] ?? '';
                            if (strpos($display_note, '[CLASS_ID:') !== false) {
                                $display_note = '';
                            }
                        ?>
                        <div class="schedule-card <?php echo ($mts['status'] === 'huỷ') ? 'past' : ''; ?>">
                            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <strong style="font-size:16px;">
                                        <?php echo $weekday; ?>, <?php echo $dt->format('d/m/Y'); ?>
                                        — <?php echo $dt->format('H:i'); ?>
                                    </strong>
                                    <span class="badge-status <?php echo $status_css; ?>" style="margin-left:8px;">
                                        <?php echo htmlspecialchars($mts['status'] ?? ''); ?>
                                    </span>
                                </div>
                                <?php if (!empty($mts['activity_type'])): ?>
                                <span style="color:#f36100;font-weight:600;text-transform:uppercase;font-size:13px;">
                                    <?php echo htmlspecialchars($mts['activity_type']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="meta">
                                <?php if (!empty($mts['duration'])): ?>
                                <span><i class="fa fa-clock-o"></i> <?php echo $mts['duration']; ?> phút</span>
                                <?php endif; ?>

                                <?php if (!empty($mts['intensity'])): ?>
                                <span>
                                    <i class="fa fa-tachometer"></i> Cường độ: <?php echo $intensity_label; ?>
                                    <span class="intensity-bar <?php echo $intensity_css; ?>"></span>
                                </span>
                                <?php endif; ?>

                                <?php if (!empty($mts['calories_burned'])): ?>
                                <span><i class="fa fa-fire"></i> <?php echo number_format($mts['calories_burned']); ?> kcal</span>
                                <?php endif; ?>

                                <?php if (!empty($mts['trainer_name'])): ?>
                                <span><i class="fa fa-user"></i> HLV: <?php echo htmlspecialchars($mts['trainer_name']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($display_note)): ?>
                                <span><i class="fa fa-sticky-note-o"></i> <?php echo htmlspecialchars($display_note); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:#888;">
                        <i class="fa fa-calendar-times-o fa-3x" style="color:#ddd;"></i>
                        <p style="margin-top:15px;">Chưa có lịch tập cá nhân nào.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</section>
<!-- My Schedules Section End -->

<?php
$conn->close();
include 'layout/footer.php';
?>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});
</script>
