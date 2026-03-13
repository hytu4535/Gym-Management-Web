<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

<<<<<<< HEAD
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$member_id = null;

if ($user_id) {
    $stmtMember = $conn->prepare("SELECT id FROM members WHERE users_id = ? LIMIT 1");
    $stmtMember->bind_param('i', $user_id);
    $stmtMember->execute();
    $memberData = $stmtMember->get_result()->fetch_assoc();
    $stmtMember->close();

    if ($memberData) {
        $member_id = (int) $memberData['id'];
    }
}
=======
$member_id = $_SESSION['user_id'] ?? null;
>>>>>>> b0e7d9c41fd8046e09ddc5ff4563e0a4c8d1bfef

// Lấy danh sách lớp tập nhóm đang mở
$sql = "SELECT 
            cs.id,
            cs.class_name,
            cs.class_type,
            cs.schedule_time,
            cs.schedule_days,
            cs.capacity,
            cs.enrolled_count,
            cs.room,
            t.full_name AS trainer_name,
            t.type      AS trainer_type
        FROM class_schedules cs
        LEFT JOIN trainers t ON cs.trainer_id = t.id
        WHERE cs.status = 'active'
        ORDER BY cs.id ASC";
$classes_result = $conn->query($sql);

// Lấy danh sách lớp member đã đăng ký (active)
$registered_ids = [];
if ($member_id) {
    $stmt = $conn->prepare("SELECT class_id FROM class_registrations WHERE member_id = ? AND status = 'active'");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $reg_result = $stmt->get_result();
    while ($row = $reg_result->fetch_assoc()) {
        $registered_ids[] = $row['class_id'];
    }
    $stmt->close();
}

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Lớp tập</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Lớp tập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Classes Section Begin -->
<section class="classes-section classes-page spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Lịch tập sắp tới</span>
                    <h2>LỊCH TRÌNH TẬP LUYỆN</h2>
                </div>
            </div>
        </div>

        <div class="row">
            <?php
            $type_icons = [
                'cardio'   => 'fa-heartbeat',
                'yoga'     => 'fa-leaf',
                'strength' => 'fa-bold',
                'hiit'     => 'fa-fire',
                'boxing'   => 'fa-hand-rock-o',
            ];
            $img_counter = 1;
            if ($classes_result && $classes_result->num_rows > 0):
                while ($class = $classes_result->fetch_assoc()):
                    $img_num  = (($img_counter - 1) % 5) + 1;
                    $img_path = "assets/img/classes/class-{$img_num}.jpg";
                    $is_full  = $class['enrolled_count'] >= $class['capacity'];
                    $is_registered = in_array($class['id'], $registered_ids);
                    $icon = $type_icons[strtolower($class['class_type'] ?? '')] ?? 'fa-dumbbell';
                    $img_counter++;
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="class-item">
                    <div class="ci-pic">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                    </div>
                    <div class="ci-text">
                        <span><i class="fa <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($class['class_type'] ?? ''); ?></span>
                        <h5><?php echo htmlspecialchars($class['class_name']); ?></h5>

                        <?php if (!empty($class['schedule_days'])): ?>
                        <p><i class="fa fa-calendar"></i> <?php echo htmlspecialchars($class['schedule_days']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($class['schedule_time'])): ?>
                        <p><i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($class['schedule_time']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($class['room'])): ?>
                        <p><i class="fa fa-map-marker"></i> Phòng: <?php echo htmlspecialchars($class['room']); ?></p>
                        <?php endif; ?>

                        <p><i class="fa fa-user"></i> HLV: <?php echo htmlspecialchars($class['trainer_name'] ?? 'Chưa có'); ?></p>

                        <p>
                            <i class="fa fa-users"></i>
                            Sĩ số: <?php echo $class['enrolled_count']; ?>/<?php echo $class['capacity']; ?>
                            <?php if ($is_full): ?>
                            <span style="color:#e74c3c;font-weight:bold;"> — Đã đầy</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <!-- Button nằm ngoài ci-text để không bị overflow -->
                    <div style="padding:10px 20px 20px;">
                        <?php if ($member_id): ?>
                            <?php if ($is_registered): ?>
                            <button class="primary-btn btn-cancel-class"
                                    data-id="<?php echo $class['id']; ?>"
                                    style="width:100%;background:#e74c3c;border:none;cursor:pointer;padding:10px;">
                                <i class="fa fa-times"></i> Hủy đăng ký
                            </button>
                            <?php elseif (!$is_full): ?>
                            <button class="primary-btn btn-register-class"
                                    data-id="<?php echo $class['id']; ?>"
                                    style="width:100%;display:block;text-align:center;padding:10px;border:none;cursor:pointer;">
                                <i class="fa fa-check"></i> Đăng ký ngay
                            </button>
                            <?php else: ?>
                            <button class="primary-btn" disabled
                                    style="width:100%;opacity:0.5;cursor:not-allowed;padding:10px;">
                                <i class="fa fa-ban"></i> Lớp đã đầy
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                        <a href="login.php?redirect=classes.php" class="primary-btn"
                           style="display:block;text-align:center;padding:10px;">
                            <i class="fa fa-sign-in"></i> Đăng nhập để đăng ký
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
            else:
            ?>
            <div class="col-lg-12 text-center">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Hiện tại chưa có lớp tập nào đang mở.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Classes Section End -->

<!-- Banner Section Begin -->
<section class="banner-section set-bg" data-setbg="assets/img/banner-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="bs-text">
                    <h2>Đăng ký lịch tập ngay hôm nay</h2>
                    <div class="bt-tips">Huấn luyện viên cá nhân chuyên nghiệp.</div>
                    <a href="contact.php" class="primary-btn btn-normal">Liên hệ ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End -->

<?php
$conn->close();
include 'layout/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function handleClass(btn, url, successMsg) {
        const classId = btn.dataset.id;
        btn.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'class_id=' + classId
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
            else btn.disabled = false;
        })
        .catch(() => { alert('Lỗi kết nối!'); btn.disabled = false; });
    }

    document.querySelectorAll('.btn-cancel-class').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Bạn có chắc muốn hủy đăng ký lớp này?'))
                handleClass(btn, 'ajax/class-cancel.php', 'Hủy thành công!');
        });
    });

    document.querySelectorAll('.btn-register-class').forEach(btn => {
        btn.addEventListener('click', () => {
<<<<<<< HEAD
            handleClass(btn, 'ajax/class-register-process.php', 'Đăng ký thành công!');
=======
            handleClass(btn, 'class-register.php', 'Đăng ký thành công!');
>>>>>>> b0e7d9c41fd8046e09ddc5ff4563e0a4c8d1bfef
        });
    });
});
</script>
