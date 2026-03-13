<?php 
$page_title = "Lịch Sử Đo BMI";
require_once '../includes/database.php';

// Xử lý các hành động
$db = getDB();
$message = '';
$messageType = '';

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM bmi_measurements WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Xóa kết quả đo BMI thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Xử lý thêm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $device_id = $_POST['device_id'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    
    // Tính BMI
    $heightInMeters = $height / 100;
    $bmi = $weight / ($heightInMeters * $heightInMeters);
    $bmi = round($bmi, 2);
    
    // Phân loại thể trạng
    if ($bmi < 18.5) {
        $body_type = 'gay';
    } elseif ($bmi < 25) {
        $body_type = 'binh thuong';
    } elseif ($bmi < 30) {
        $body_type = 'thua can';
    } else {
        $body_type = 'beo phi';
    }
    
    try {
        // Thêm kết quả đo BMI
        $stmt = $db->prepare("INSERT INTO bmi_measurements (member_id, device_id, height, weight, bmi, body_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$member_id, $device_id, $height, $weight, $bmi, $body_type]);
        
        // Cập nhật chiều cao và cân nặng cho hội viên
        $stmt = $db->prepare("UPDATE members SET height = ?, weight = ? WHERE id = ?");
        $stmt->execute([$height, $weight, $member_id]);
        
        $message = "Thêm kết quả đo BMI thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Lấy danh sách đo BMI
$stmt = $db->query("SELECT bm.*, m.full_name, d.device_code, d.location 
                    FROM bmi_measurements bm 
                    LEFT JOIN members m ON bm.member_id = m.id 
                    LEFT JOIN bmi_devices d ON bm.device_id = d.id 
                    ORDER BY bm.measured_at DESC");
$measurements = $stmt->fetchAll();

// Lấy danh sách members và devices cho form
$members = $db->query("SELECT id, full_name FROM members ORDER BY full_name")->fetchAll();
$devices = $db->query("SELECT id, device_code, location FROM bmi_devices WHERE status = 'active' ORDER BY device_code")->fetchAll();

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
            <h1 class="m-0">Lịch Sử Đo BMI</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Đo BMI</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
          <?php echo $message; ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Kết Quả Đo BMI</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#bmiModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Kết Quả
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table id="bmiTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Chiều Cao (cm)</th>
                    <th>Cân Nặng (kg)</th>
                    <th>BMI</th>
                    <th>Thể Trạng</th>
                    <th>Máy Đo</th>
                    <th>Ngày Đo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($measurements as $measure): ?>
                  <tr>
                    <td><?php echo $measure['id']; ?></td>
                    <td><?php echo htmlspecialchars($measure['full_name']); ?></td>
                    <td><?php echo $measure['height']; ?></td>
                    <td><?php echo $measure['weight']; ?></td>
                    <td><?php echo $measure['bmi']; ?></td>
                    <td>
                      <?php 
                      $badgeClass = ['gay' => 'warning', 'binh thuong' => 'success', 'thua can' => 'info', 'beo phi' => 'danger'];
                      $bodyText = ['gay' => 'Gầy', 'binh thuong' => 'Bình thường', 'thua can' => 'Thừa cân', 'beo phi' => 'Béo phì'];
                      $class = $badgeClass[$measure['body_type']] ?? 'secondary';
                      $text = $bodyText[$measure['body_type']] ?? $measure['body_type'];
                      ?>
                      <span class="badge badge-<?php echo $class; ?>"><?php echo $text; ?></span>
                    </td>
                    <td><?php echo $measure['device_code'] ? htmlspecialchars($measure['device_code']) : 'N/A'; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($measure['measured_at'])); ?></td>
                    <td>
                      <button class="btn btn-info btn-sm" onclick='viewDetail(<?php echo json_encode($measure); ?>)' data-toggle="modal" data-target="#viewModal">
                        <i class="fas fa-eye"></i>
                      </button>
                      <a href="?action=delete&id=<?php echo $measure['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">
                        <i class="fas fa-trash"></i>
                      </a>
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

<!-- Modal Thêm BMI -->
<div class="modal fade" id="bmiModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Thêm Kết Quả Đo BMI</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="form-group">
            <label>Hội viên</label>
            <select name="member_id" id="member_id" class="form-control" required>
              <option value="">--- Chọn hội viên ---</option>
              <?php foreach ($members as $member): ?>
              <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['full_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Máy đo</label>
            <select name="device_id" id="device_id" class="form-control">
              <option value="">--- Chọn máy đo ---</option>
              <?php foreach ($devices as $device): ?>
              <option value="<?php echo $device['id']; ?>">
                <?php echo htmlspecialchars($device['device_code']) . " - " . htmlspecialchars($device['location']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Chiều cao (cm)</label>
                <input type="number" step="0.01" name="height" id="height" class="form-control" required min="50" max="250" onchange="calculateBMI()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Cân nặng (kg)</label>
                <input type="number" step="0.01" name="weight" id="weight" class="form-control" required min="20" max="300" onchange="calculateBMI()">
              </div>
            </div>
          </div>
          <div class="alert alert-info" id="bmiPreview" style="display:none;">
            <strong>Kết quả dự kiến:</strong><br>
            BMI: <span id="bmiValue"></span><br>
            Thể trạng: <span id="bodyType"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Xem Chi Tiết -->
<div class="modal fade" id="viewModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Chi Tiết Kết Quả Đo BMI</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <tr>
            <th width="40%">Hội viên:</th>
            <td id="view_member"></td>
          </tr>
          <tr>
            <th>Chiều cao:</th>
            <td id="view_height"></td>
          </tr>
          <tr>
            <th>Cân nặng:</th>
            <td id="view_weight"></td>
          </tr>
          <tr>
            <th>BMI:</th>
            <td id="view_bmi"></td>
          </tr>
          <tr>
            <th>Thể trạng:</th>
            <td id="view_body_type"></td>
          </tr>
          <tr>
            <th>Máy đo:</th>
            <td id="view_device"></td>
          </tr>
          <tr>
            <th>Ngày đo:</th>
            <td id="view_date"></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('member_id').value = '';
  document.getElementById('device_id').value = '';
  document.getElementById('height').value = '';
  document.getElementById('weight').value = '';
  document.getElementById('bmiPreview').style.display = 'none';
}

function calculateBMI() {
  var height = parseFloat(document.getElementById('height').value);
  var weight = parseFloat(document.getElementById('weight').value);
  
  if (height && weight) {
    var heightInMeters = height / 100;
    var bmi = weight / (heightInMeters * heightInMeters);
    bmi = Math.round(bmi * 100) / 100;
    
    var bodyType = '';
    if (bmi < 18.5) {
      bodyType = 'Gầy';
    } else if (bmi < 25) {
      bodyType = 'Bình thường';
    } else if (bmi < 30) {
      bodyType = 'Thừa cân';
    } else {
      bodyType = 'Béo phì';
    }
    
    document.getElementById('bmiValue').innerText = bmi;
    document.getElementById('bodyType').innerText = bodyType;
    document.getElementById('bmiPreview').style.display = 'block';
  } else {
    document.getElementById('bmiPreview').style.display = 'none';
  }
}

function viewDetail(measure) {
  var bodyText = {'gay': 'Gầy', 'binh thuong': 'Bình thường', 'thua can': 'Thừa cân', 'beo phi': 'Béo phì'};
  
  document.getElementById('view_member').innerText = measure.full_name;
  document.getElementById('view_height').innerText = measure.height + ' cm';
  document.getElementById('view_weight').innerText = measure.weight + ' kg';
  document.getElementById('view_bmi').innerText = measure.bmi;
  document.getElementById('view_body_type').innerText = bodyText[measure.body_type] || measure.body_type;
  document.getElementById('view_device').innerText = measure.device_code || 'N/A';
  document.getElementById('view_date').innerText = new Date(measure.measured_at).toLocaleString('vi-VN');
}
</script>

<?php include 'layout/footer.php'; ?>
