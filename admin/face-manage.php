<?php
session_start();

$page_title = 'Quản lý Face Profile';

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_MEMBERS');

$db = getDB();
$message = '';
$messageType = '';

$hasStatusColumn = false;
try {
    $checkColStmt = $db->query("SHOW COLUMNS FROM face_profiles LIKE 'status'");
    $hasStatusColumn = (bool) $checkColStmt->fetch(PDO::FETCH_ASSOC);

    // Auto-heal schema for old databases that do not have `status` yet.
    if (!$hasStatusColumn) {
        $db->exec("ALTER TABLE face_profiles ADD COLUMN status enum('active','inactive','deleted') DEFAULT 'active' AFTER image_path");
        $db->exec("ALTER TABLE face_profiles ADD KEY idx_status (status)");
        $hasStatusColumn = true;
    }
} catch (Exception $e) {
    // Keep page usable even if schema migration is blocked by permissions.
    $hasStatusColumn = false;
}

// Xử lý xóa face profile
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $member_id = (int)$_GET['id'];
        
        // Gọi API xóa face profile
        $face_service_url = "http://localhost:8000/unenroll";
        $payload = json_encode(["member_id" => $member_id]);
        
        $context = stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => "Content-Type: application/json\r\n",
                "content" => $payload,
                "timeout" => 5
            ]
        ]);
        
        @file_get_contents($face_service_url, false, $context);
        
        // Nếu có cột status thì soft-delete, không thì fallback hard-delete.
        $stmt = $hasStatusColumn
            ? $db->prepare("UPDATE face_profiles SET status = 'deleted' WHERE member_id = ?")
            : $db->prepare("DELETE FROM face_profiles WHERE member_id = ?");
        $stmt->execute([$member_id]);
        
        $message = "Xóa face profile thành công!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = toVietnameseDbError($e, 'Không thể cập nhật trạng thái khuôn mặt.');
        $messageType = "danger";
    }
}

// Xử lý kích hoạt/vô hiệu hóa
if (isset($_GET['action']) && in_array($_GET['action'], ['activate', 'deactivate']) && isset($_GET['id'])) {
    try {
        if (!$hasStatusColumn) {
            throw new Exception('CSDL chưa có cột status, không thể đổi trạng thái.');
        }

        $member_id = (int)$_GET['id'];
        $new_status = $_GET['action'] === 'activate' ? 'active' : 'inactive';
        
        $stmt = $db->prepare("UPDATE face_profiles SET status = ? WHERE member_id = ?");
        $stmt->execute([$new_status, $member_id]);
        
        $message = $new_status === 'active' ? "Kích hoạt thành công!" : "Vô hiệu hóa thành công!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = toVietnameseDbError($e, 'Không thể xóa hồ sơ khuôn mặt.');
        $messageType = "danger";
    }
}

// Lấy danh sách face profiles
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['active', 'inactive', 'deleted'])) {
    if ($hasStatusColumn) {
        $where[] = 'fp.status = ?';
        $params[] = $statusFilter;
    }
}

$whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

$sql = "
    SELECT
        fp.id,
        fp.member_id,
        m.full_name,
        m.phone,
        " . ($hasStatusColumn ? "fp.status" : "'active' AS status") . ",
        fp.created_at,
        fp.updated_at
    FROM face_profiles fp
    LEFT JOIN members m ON m.id = fp.member_id
    $whereSql
    ORDER BY fp.updated_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Quản lý Face Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Face Manage</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danh sách Face Profiles</h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET">
                            <div class="form-group mr-2">
                                <label for="statusFilter" class="mr-2">Trạng thái:</label>
                                <select id="statusFilter" name="status" class="form-control form-control-sm">
                                    <option value="">-- Tất cả --</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Vô hiệu hóa</option>
                                    <option value="deleted" <?php echo $statusFilter === 'deleted' ? 'selected' : ''; ?>>Đã xóa</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Lọc</button>
                        </form>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Mã HV</th>
                                <th>Tên hội viên</th>
                                <th>Điện thoại</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Cập nhật lần cuối</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($profiles)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($profiles as $profile): ?>
                                    <tr>
                                        <td><?php echo (int)$profile['id']; ?></td>
                                        <td><?php echo (int)$profile['member_id']; ?></td>
                                        <td><?php echo htmlspecialchars($profile['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($profile['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $status_badge = [
                                                'active' => 'success',
                                                'inactive' => 'warning',
                                                'deleted' => 'danger'
                                            ];
                                            $status_text = [
                                                'active' => 'Hoạt động',
                                                'inactive' => 'Vô hiệu hóa',
                                                'deleted' => 'Đã xóa'
                                            ];
                                            $status = $profile['status'] ?? 'active';
                                            $badge_class = $status_badge[$status] ?? 'secondary';
                                            $badge_text = $status_text[$status] ?? $status;
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>">
                                                <?php echo $badge_text; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($profile['created_at'])); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($profile['updated_at'])); ?></td>
                                        <td>
                                            <?php if ($profile['status'] === 'active'): ?>
                                                <a href="?action=deactivate&id=<?php echo (int)$profile['member_id']; ?>" class="btn btn-sm btn-warning" title="Vô hiệu hóa">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php elseif ($profile['status'] === 'inactive'): ?>
                                                <a href="?action=activate&id=<?php echo (int)$profile['member_id']; ?>" class="btn btn-sm btn-info" title="Kích hoạt">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($profile['status'] !== 'deleted'): ?>
                                                <a href="?action=delete&id=<?php echo (int)$profile['member_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa face profile này?')" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'layout/footer.php'; ?>
