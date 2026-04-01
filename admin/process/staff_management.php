<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth_permission.php';

checkPermission('MANAGE_STAFF');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$hasDepartmentIdColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'department_id' LIMIT 1")->fetchColumn();
$staffUserIdColumn = 'users_id';
$hasUserFullNameColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'full_name' LIMIT 1")->fetchColumn();
$memberRoleId = (int) ($db->query("SELECT id FROM roles WHERE name = 'Hội viên' LIMIT 1")->fetchColumn() ?: 0);
if ($memberRoleId <= 0) {
    $memberRoleId = (int) ($db->query("SELECT id FROM roles WHERE name = 'member' LIMIT 1")->fetchColumn() ?: 0);
}
$userProfileSql = $hasUserFullNameColumn
    ? 'SELECT u.full_name, u.phone, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.role_id <> ' . (int) $memberRoleId . ' LIMIT 1'
    : 'SELECT u.username AS full_name, u.phone, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.role_id <> ' . (int) $memberRoleId . ' LIMIT 1';

function failAndGoBack($message)
{
    echo "<script>alert('" . addslashes($message) . "'); window.history.back();</script>";
    exit();
}

function getRoleIdByPosition(PDO $db, $position)
{
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
    $stmt->execute([$position]);
    return (int) $stmt->fetchColumn();
}

try {
    if ($staffUserIdColumn === null) {
        failAndGoBack('Không tìm thấy cột liên kết tài khoản trong bảng staff (users_id hoặc user_id).');
    }

    if ($action === 'add') {
        checkPermission('MANAGE_STAFF', 'add');

        $usersId = isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0;
        $position = trim((string) ($_POST['position'] ?? ''));
        $departmentId = isset($_POST['department_id']) ? (int) $_POST['department_id'] : 0;
        $status = trim((string) ($_POST['status'] ?? 'active'));

        if ($usersId <= 0) {
            failAndGoBack('Vui lòng chọn tài khoản / email hợp lệ.');
        }

        $userStmt = $db->prepare($userProfileSql);
        $userStmt->execute([$usersId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            failAndGoBack('Role không hợp lệ.');
        }

        $staffDuplicateStmt = $db->prepare("SELECT COUNT(*) FROM staff WHERE $staffUserIdColumn = ?");
        $staffDuplicateStmt->execute([$usersId]);
        $memberDuplicateStmt = $db->prepare('SELECT COUNT(*) FROM members WHERE users_id = ?');
        $memberDuplicateStmt->execute([$usersId]);
        if ((int) $staffDuplicateStmt->fetchColumn() > 0 || (int) $memberDuplicateStmt->fetchColumn() > 0) {
            failAndGoBack('Tài khoản đã được gán vai trò.');
        }

        $fullName = trim((string) ($user['full_name'] ?? ''));
        $phone = trim((string) ($user['phone'] ?? ''));
        if ($fullName === '') {
            failAndGoBack('Tài khoản đã chọn chưa có họ tên.');
        }

        if (!in_array($status, ['active', 'inactive', 'on_leave'], true)) {
            $status = 'active';
        }

        if ($position === '') {
            failAndGoBack('Vui lòng chọn chức vụ.');
        }

        $roleId = getRoleIdByPosition($db, $position);
        if ($roleId <= 0) {
            failAndGoBack('Chức vụ đã chọn không hợp lệ.');
        }

        if ($hasDepartmentIdColumn && $departmentId <= 0) {
            failAndGoBack('Vui lòng chọn phòng ban.');
        }

        $db->beginTransaction();

        if ($hasDepartmentIdColumn) {
            $stmt = $db->prepare(
                "INSERT INTO staff ($staffUserIdColumn, full_name, position, department_id, status) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$usersId, $fullName, $position, $departmentId, $status]);
        } else {
            $stmt = $db->prepare(
                "INSERT INTO staff ($staffUserIdColumn, full_name, position, status) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$usersId, $fullName, $position, $status]);
        }

        $syncUserRoleStmt = $db->prepare('UPDATE users SET role_id = ? WHERE id = ?');
        $syncUserRoleStmt->execute([$roleId, $usersId]);

        if ((int) ($_SESSION['admin_user_id'] ?? 0) === $usersId) {
            $_SESSION['role_id'] = $roleId;
            $_SESSION['role'] = $position;
        }

        $db->commit();

        echo "<script>alert('Thêm staff thành công!'); window.location.href='../staff.php';</script>";
        exit();
    }

    if ($action === 'edit') {
        checkPermission('MANAGE_STAFF', 'edit');

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $submittedUsersId = isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0;
        $position = trim((string) ($_POST['position'] ?? ''));
        $departmentId = isset($_POST['department_id']) ? (int) $_POST['department_id'] : 0;
        $status = trim((string) ($_POST['status'] ?? 'active'));

        if ($id <= 0) {
            failAndGoBack('Thiếu thông tin staff cần cập nhật.');
        }

        $currentStmt = $db->prepare("SELECT $staffUserIdColumn FROM staff WHERE id = ?");
        $currentStmt->execute([$id]);
        $currentUsersId = (int) $currentStmt->fetchColumn();

        if ($currentUsersId <= 0) {
            failAndGoBack('Không tìm thấy staff cần cập nhật.');
        }

        if ($submittedUsersId > 0 && $submittedUsersId !== $currentUsersId) {
            failAndGoBack('Không thể thay đổi tài khoản / email đã liên kết.');
        }

        $userStmt = $db->prepare($userProfileSql);
        $userStmt->execute([$currentUsersId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            failAndGoBack('Role không hợp lệ.');
        }

        $staffDuplicateStmt = $db->prepare("SELECT COUNT(*) FROM staff WHERE $staffUserIdColumn = ? AND id <> ?");
        $staffDuplicateStmt->execute([$currentUsersId, $id]);
        $memberDuplicateStmt = $db->prepare('SELECT COUNT(*) FROM members WHERE users_id = ?');
        $memberDuplicateStmt->execute([$currentUsersId]);
        if ((int) $staffDuplicateStmt->fetchColumn() > 0 || (int) $memberDuplicateStmt->fetchColumn() > 0) {
            failAndGoBack('Tài khoản đã được gán vai trò.');
        }

        if ($position === '') {
            failAndGoBack('Vui lòng chọn chức vụ.');
        }

        $roleId = getRoleIdByPosition($db, $position);
        if ($roleId <= 0) {
            failAndGoBack('Chức vụ đã chọn không hợp lệ.');
        }

        if ($hasDepartmentIdColumn && $departmentId <= 0) {
            failAndGoBack('Vui lòng chọn phòng ban.');
        }

        if (!in_array($status, ['active', 'inactive', 'on_leave'], true)) {
            $status = 'active';
        }

        $fullName = trim((string) ($user['full_name'] ?? ''));
        if ($fullName === '') {
            failAndGoBack('Tài khoản liên kết chưa có họ tên.');
        }

        $db->beginTransaction();

        if ($hasDepartmentIdColumn) {
            $stmt = $db->prepare(
                'UPDATE staff SET full_name = ?, position = ?, department_id = ?, status = ? WHERE id = ?'
            );
            $stmt->execute([$fullName, $position, $departmentId, $status, $id]);
        } else {
            $stmt = $db->prepare(
                'UPDATE staff SET full_name = ?, position = ?, status = ? WHERE id = ?'
            );
            $stmt->execute([$fullName, $position, $status, $id]);
        }

        $syncUserRoleStmt = $db->prepare('UPDATE users SET role_id = ? WHERE id = ?');
        $syncUserRoleStmt->execute([$roleId, $currentUsersId]);

        if ((int) ($_SESSION['admin_user_id'] ?? 0) === $currentUsersId) {
            $_SESSION['role_id'] = $roleId;
            $_SESSION['role'] = $position;
        }

        $db->commit();

        echo "<script>alert('Cập nhật staff thành công!'); window.location.href='../staff.php';</script>";
        exit();
    }

    if ($action === 'delete') {
        checkPermission('MANAGE_STAFF', 'delete');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            failAndGoBack('Thiếu staff cần xóa.');
        }

        $userStmt = $db->prepare("SELECT $staffUserIdColumn FROM staff WHERE id = ?");
        $userStmt->execute([$id]);
        $linkedUserId = (int)$userStmt->fetchColumn();

        if ($linkedUserId > 0) {
            $checkOrderStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE handled_by = ?");
            $checkOrderStmt->execute([$linkedUserId]);
            if ((int)$checkOrderStmt->fetchColumn() > 0) {
                failAndGoBack('Không thể xóa nhân viên này vì họ đã tham gia duyệt/xử lý đơn hàng.');
            }
        }

        $importRefStmt = $db->prepare('SELECT COUNT(*) FROM import_slips WHERE staff_id = ?');
        $importRefStmt->execute([$id]);
        $importRefCount = (int) $importRefStmt->fetchColumn();
        if ($importRefCount > 0) {
            failAndGoBack('Không thể xóa nhân viên đã phát sinh phiếu nhập.');
        }


        $hasOrderStaffIdColumn = (bool) $db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'staff_id' LIMIT 1")->fetchColumn();
        if ($hasOrderStaffIdColumn) {
            $orderRefStmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE staff_id = ?');
            $orderRefStmt->execute([$id]);
            if ((int) $orderRefStmt->fetchColumn() > 0) {
                failAndGoBack('Không thể xóa nhân viên đã phát sinh đơn hàng.');
            }
        }


        $stmt = $db->prepare('DELETE FROM staff WHERE id = ?');
        $stmt->execute([$id]);

        echo "<script>alert('Đã xóa staff thành công!'); window.location.href='../staff.php';</script>";
        exit();
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($e instanceof PDOException) {
        $errorMessage = strtolower((string) $e->getMessage());
        if ((int) $e->getCode() === 23000 || strpos($errorMessage, 'duplicate entry') !== false) {
            failAndGoBack('Tài khoản đã được gán vai trò.');
        }
    }
    failAndGoBack('Lỗi: ' . $e->getMessage());
}