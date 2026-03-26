<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý HLV";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_TRAINERS
checkPermission('MANAGE_TRAINERS');

include '../includes/functions.php';

$db = getDB();

$staff_stmt = $db->query("SELECT full_name, position FROM staff WHERE status = 'active' ORDER BY full_name ASC");
$staff_members = $staff_stmt->fetchAll();

function isValidTrainerPhone($phone)
{
  return preg_match('/^[0-9]{10,11}$/', $phone) === 1;
}

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $type = $_POST['type'];
    $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
        $status = sanitize($_POST['status']);
    $full_name = $type === 'Nội bộ'
      ? sanitize($_POST['staff_full_name'] ?? '')
      : sanitize($_POST['full_name_free'] ?? '');

    if ($full_name === '') {
      setFlashMessage('danger', 'Vui lòng chọn hoặc nhập họ tên HLV.');
      redirect('trainers.php');
      exit;
    }

    if (!isValidTrainerPhone($phone)) {
      setFlashMessage('danger', 'Số điện thoại phải gồm 10-11 chữ số và chỉ được nhập số.');
      redirect('trainers.php');
      exit;
    }

        try {
            $stmt = $db->prepare("INSERT INTO trainers (full_name, type, phone, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $type, $phone, $status]);
            setFlashMessage('success', 'Thêm HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $type = $_POST['type'];
        $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
        $status = sanitize($_POST['status']);
        $full_name = $type === 'Nội bộ'
            ? sanitize($_POST['staff_full_name'] ?? '')
            : sanitize($_POST['full_name_free'] ?? '');

        if ($full_name === '') {
            setFlashMessage('danger', 'Vui lòng chọn hoặc nhập họ tên HLV.');
            redirect('trainers.php');
            exit;
        }

        if (!isValidTrainerPhone($phone)) {
            setFlashMessage('danger', 'Số điện thoại phải gồm 10-11 chữ số và chỉ được nhập số.');
            redirect('trainers.php');
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE trainers SET full_name = ?, type = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$full_name, $type, $phone, $status, $id]);
            setFlashMessage('success', 'Cập nhật HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM trainers WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa HLV thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa HLV (có thể đang có lịch tập liên kết). ' . $e->getMessage());
        }
        redirect('trainers.php');
        exit;
    }
}

// Lấy danh sách HLV
$stmt = $db->query("SELECT * FROM trainers ORDER BY id DESC");
$trainers = $stmt->fetchAll();

// Lấy flash message
$flash = getFlashMessage();

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
            <h1 class="m-0">Quản lý HLV (Trainers)</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">HLV</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <!-- Thông báo -->
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <?= $flash['message'] ?>
        </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách HLV</h3>
                <div class="card-tools">
                  <button type="button" id="openAddTrainerModal" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm HLV
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Loại</th>
                    <th>SĐT</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($trainers as $trainer): ?>
                  <tr>
                    <td><?= $trainer['id'] ?></td>
                    <td><?= htmlspecialchars($trainer['full_name']) ?></td>
                    <td>
                      <?php if ($trainer['type'] === 'Nội bộ'): ?>
                        <span class="badge badge-info">Nội bộ</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Tự do</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trainer['phone']) ?></td>
                    <td>
                      <?php if ($trainer['status'] === 'hoạt động'): ?>
                        <span class="badge badge-success">Hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Nghỉ việc</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $trainer['id'] ?>"
                        data-fullname="<?= htmlspecialchars($trainer['full_name']) ?>"
                        data-type="<?= $trainer['type'] ?>"
                        data-phone="<?= htmlspecialchars($trainer['phone']) ?>"
                        data-status="<?= htmlspecialchars($trainer['status'] ?? '') ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $trainer['id'] ?>"
                        data-name="<?= htmlspecialchars($trainer['full_name']) ?>"
                        data-toggle="modal" data-target="#deleteTrainerModal">
                        <i class="fas fa-trash"></i>
                      </button>
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

    <!-- Modal Thêm HLV -->
    <div class="modal fade" id="addTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm HLV mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control trainer-type-select" name="type" data-field="type">
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-free-group d-none">
                <label>Họ tên HLV <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-name-free" name="full_name_free" placeholder="Nhập họ tên HLV" data-field="full_name_free">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-internal-group">
                <label>Chọn nhân viên <span class="text-danger">*</span></label>
                <select class="form-control trainer-name-internal" name="staff_full_name" data-field="staff_full_name">
                  <option value="">-- Chọn nhân viên --</option>
                  <?php foreach ($staff_members as $staff): ?>
                    <option value="<?= htmlspecialchars($staff['full_name']) ?>"><?= htmlspecialchars($staff['full_name']) ?><?= !empty($staff['position']) ? ' - ' . htmlspecialchars($staff['position']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-phone-input" name="phone" placeholder="Nhập SĐT" inputmode="numeric" data-field="phone">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="nghỉ việc">Nghỉ việc</option>
                </select>
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

    <!-- Modal Sửa HLV -->
    <div class="modal fade" id="editTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
              <h5 class="modal-title">Sửa thông tin HLV</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Loại <span class="text-danger">*</span></label>
                <select class="form-control trainer-type-select" name="type" id="edit-type" data-field="type">
                  <option value="Nội bộ">Nội bộ</option>
                  <option value="Tự do">Tự do</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-free-group d-none">
                <label>Họ tên HLV <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-name-free" name="full_name_free" id="edit-fullname-free" placeholder="Nhập họ tên HLV" data-field="full_name_free">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group trainer-name-internal-group">
                <label>Chọn nhân viên <span class="text-danger">*</span></label>
                <select class="form-control trainer-name-internal" name="staff_full_name" id="edit-staff-fullname" data-field="staff_full_name">
                  <option value="">-- Chọn nhân viên --</option>
                  <?php foreach ($staff_members as $staff): ?>
                    <option value="<?= htmlspecialchars($staff['full_name']) ?>"><?= htmlspecialchars($staff['full_name']) ?><?= !empty($staff['position']) ? ' - ' . htmlspecialchars($staff['position']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" class="form-control trainer-phone-input" name="phone" id="edit-phone" inputmode="numeric" data-field="phone">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="nghỉ việc">Nghỉ việc</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Xóa HLV -->
    <div class="modal fade" id="deleteTrainerModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="trainers.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header">
              <h5 class="modal-title">Xác nhận xóa</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa HLV <strong id="delete-name"></strong>?</p>
              <p class="text-danger"><small>Hành động này không thể hoàn tác! Nếu HLV đang có lịch tập sẽ không xóa được.</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-danger">Xóa</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>

<?php include 'layout/footer.php'; ?>

<!-- Script xử lý modal -->
<script>
function syncTrainerNameFields($modal) {
  if (!$modal || !$modal.length) return;

  const type = $modal.find('.trainer-type-select').val();
  const $freeGroup = $modal.find('.trainer-name-free-group');
  const $internalGroup = $modal.find('.trainer-name-internal-group');
  const $freeInput = $modal.find('.trainer-name-free');
  const $internalSelect = $modal.find('.trainer-name-internal');

  if (type === 'Tự do') {
    $internalGroup.addClass('d-none');
    $freeGroup.removeClass('d-none');
    $internalSelect.prop('disabled', true).val('');
    $freeInput.prop('disabled', false);
  } else {
    $freeGroup.addClass('d-none');
    $internalGroup.removeClass('d-none');
    $freeInput.prop('disabled', true).val('');
    $internalSelect.prop('disabled', false);
  }
}

function ensureSelectOption($select, value, text) {
  if (!$select.length || !value) return;
  const existing = $select.find('option').filter(function() {
    return String($(this).val()) === String(value);
  });
  if (!existing.length) {
    $select.prepend(new Option(text, value, true, true));
  }
}

function fillEditModal($button) {
  const $modal = $('#editTrainerModal');
  const type = $button.data('type');
  const fullName = $button.data('fullname') || '';

  $modal.find('#edit-id').val($button.data('id'));
  $modal.find('#edit-type').val(type);
  $modal.find('#edit-phone').val($button.data('phone'));
  $modal.find('#edit-status').val($button.data('status'));

  $modal.find('#edit-fullname-free').val(type === 'Tự do' ? fullName : '');
  ensureSelectOption($modal.find('#edit-staff-fullname'), fullName, fullName);
  $modal.find('#edit-staff-fullname').val(type === 'Nội bộ' ? fullName : '');

  syncTrainerNameFields($modal);
}

function resetAddModal() {
  const $modal = $('#addTrainerModal');
  $modal.find('.trainer-type-select').val('Nội bộ');
  $modal.find('.trainer-phone-input').val('');
  $modal.find('.trainer-name-free').val('');
  $modal.find('.trainer-name-internal').val('');
  syncTrainerNameFields($modal);
}

$(function() {
  // Chỉ cho phép nhập số cho SĐT HLV
  $(document).on('input', '.trainer-phone-input', function() {
    const digitsOnly = String($(this).val() || '').replace(/\D+/g, '');
    $(this).val(digitsOnly);
  });

  $(document).on('click', '#openAddTrainerModal', function() {
    resetAddModal();
    $('#addTrainerModal').modal('show');
  });

  $(document).on('change', '.trainer-type-select', function() {
    syncTrainerNameFields($(this).closest('.modal-content'));
  });

  // Điền dữ liệu vào modal sửa
  $(document).on('click', '.btn-edit', function() {
    fillEditModal($(this));
    $('#editTrainerModal').modal('show');
  });

  // Điền dữ liệu vào modal xóa
  $(document).on('click', '.btn-delete', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });

  $('#addTrainerModal, #editTrainerModal').on('show.bs.modal', function() {
    syncTrainerNameFields($(this).find('.modal-content'));
  });
});

(function() {
  function message(field) {
    if (field === 'full_name_free') return 'Vui lòng nhập họ tên HLV';
    if (field === 'staff_full_name') return 'Vui lòng chọn nhân viên';
    if (field === 'type') return 'Vui lòng chọn loại HLV';
    if (field === 'phone') return 'Vui lòng nhập 10-11 chữ số và chỉ dùng số';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }

  function getBox(input) {
    return input.closest('.form-group')?.querySelector('small.text-danger') || null;
  }

  function show(input, text) {
    const box = getBox(input);
    if (box) {
      box.textContent = text;
      box.style.display = 'block';
    }
    input.classList.add('is-invalid');
  }

  function clear(input) {
    const box = getBox(input);
    if (box) {
      box.textContent = '';
      box.style.display = 'none';
    }
    input.classList.remove('is-invalid');
  }

  function validate(input) {
    const field = input.getAttribute('data-field');
    const value = String(input.value || '').trim();
    clear(input);

    if (!field) return true;

    if (field === 'full_name_free' || field === 'staff_full_name') {
      const modal = input.closest('.modal-content');
      const typeSelect = modal ? modal.querySelector('.trainer-type-select') : null;
      const activeType = typeSelect ? typeSelect.value : '';
      const visible = field === 'full_name_free' ? activeType === 'Tự do' : activeType === 'Nội bộ';
      if (visible && !value) {
        show(input, message(field));
        return false;
      }
      return true;
    }

    if (field === 'phone') {
      if (!/^[0-9]{10,11}$/.test(value)) {
        show(input, message(field));
        return false;
      }
      return true;
    }

    if (!value) {
      show(input, message(field));
      return false;
    }

    return true;
  }

  document.addEventListener('invalid', function(e) {
    const form = e.target && e.target.closest ? e.target.closest('form') : null;
    if (form && form.hasAttribute('novalidate')) e.preventDefault();
  }, true);

  document.addEventListener('input', function(e) {
    if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('change', function(e) {
    if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target);
  }, true);

  document.addEventListener('submit', function(e) {
    if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return;
    let ok = true;
    e.target.querySelectorAll('[data-field]').forEach(function(field) {
      if (!validate(field)) ok = false;
    });
    if (!ok) e.preventDefault();
  }, true);

})();
</script>