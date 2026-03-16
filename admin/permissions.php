<?php
session_start();
$page_title = "Phân quyền";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
include '../includes/functions.php';

checkPermission('MANAGE_ALL');

$db = getDB();

// Lấy danh sách roles
$roles = $db->query("SELECT * FROM roles")->fetchAll();

// Lấy tất cả quyền
$permissions = $db->query("SELECT * FROM permission ORDER BY id ASC")->fetchAll();

// tên tiếng Việt
$permissionNames = [
    'MANAGE_ALL'               => 'Quản trị toàn hệ thống (Tài khoản)',
    'MANAGE_STAFF'             => 'Quản lí nhân viên',
    'MANAGE_MEMBERS'           => 'Quản lí hội viên',
    'MANAGE_PACKAGES'          => 'Quản lý gói tập',
    'MANAGE_TRAINERS'          => 'Quản lý huấn luyện viên',
    'MANAGE_SERVICES_NUTRITION'=> 'Quản lý dịch vụ & dinh dưỡng',
    'MANAGE_SALES'             => 'Quản lý bán hàng',
    'MANAGE_INVENTORY'         => 'Quản lý kho',
    'MANAGE_EQUIPMENT'         => 'Quản lý thiết bị',
    'MANAGE_FEEDBACK'          => 'Phản hồi & Thông báo',
    'MANAGE_PROMOTIONS'        => 'Quản lý ưu đãi',
    'VIEW_REPORTS'             => 'Báo cáo thống kê',
];

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="content-wrapper">
  <?php if(isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-success">
      <?= $_SESSION['flash_message'] ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
  <?php endif; ?>

  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Phân quyền cho Vai trò</h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <form action="process/update_permission.php" method="POST">
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead style = "text-align: center; font-size: 20px; background-color: cyan">
              <tr>
                <th>Quyền</th>
                <?php foreach($roles as $r): ?>
                  <th><?= $r['name'] ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($permissions as $p): ?>
                <tr>
                  <td><strong><?= $permissionNames[$p['code']] ?? $p['code'] ?></strong></td>
                  <?php foreach($roles as $r): ?>
                    <?php
                    $stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
                    $stmt->execute([$r['id']]);
                    $currentPerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                    <td style = "text-align: center">
                      <input type="checkbox" name="permissions[<?= $r['id'] ?>][]" value="<?= $p['id'] ?>"
                        <?= in_array($p['id'], $currentPerms) ? 'checked' : '' ?>>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Cập nhật quyền</button>
      </form>
    </div>
  </section>
</div>

<?php include 'layout/footer.php'; ?>
