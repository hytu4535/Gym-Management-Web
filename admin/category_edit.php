<?php 
$page_title = "Sửa Danh Mục";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM categories WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cat = $result->fetch_assoc();
} else {
    echo "<script>alert('Danh mục không tồn tại!'); window.location.href='categories.php';</script>";
    exit;
}
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1 class="m-0">Chỉnh sửa Danh Mục</h1>
      </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">Thông tin Danh Mục: <strong><?php echo $cat['name']; ?></strong></h3>
                </div>
                <form id="editCategoryForm" action="process/category_edit_process.php" method="POST" novalidate>
                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $cat['name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Mô tả <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo $cat['description']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="status">Trạng thái <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo ($cat['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                <option value="inactive" <?php echo ($cat['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_edit_category" class="btn btn-warning">Lưu thay đổi</button>
                        <a href="categories.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
    let isValid = true;
    let firstErrorElement = null; 
    
    document.querySelectorAll('.is-invalid').forEach(function(el) {
        el.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.custom-error-text').forEach(function(el) {
        el.remove();
    });

    function showError(inputId, message) {
        let inputEl = document.getElementById(inputId);
        if(!inputEl) return;
        
        inputEl.classList.add('is-invalid');
        
        let errorSpan = document.createElement('div');
        errorSpan.className = 'invalid-feedback custom-error-text';
        errorSpan.style.display = 'block';
        errorSpan.innerText = message;
        
        inputEl.parentNode.appendChild(errorSpan);
        isValid = false;

        if (!firstErrorElement) {
            firstErrorElement = inputEl;
        }
    }

    let nameVal = document.getElementById('name').value.trim();
    if (nameVal === '') {
        showError('name', 'Vui lòng nhập tên danh mục.');
    } else if (nameVal.length < 2) {
        showError('name', 'Tên danh mục phải có ít nhất 2 ký tự.');
    }

    let descriptionVal = document.getElementById('description').value.trim();
    if (descriptionVal === '') {
        showError('description', 'Vui lòng nhập mô tả.');
    } else if (descriptionVal.length < 10) {
        showError('description', 'Mô tả phải có ít nhất 10 ký tự.');
    }

    let statusVal = document.getElementById('status').value;
    if (statusVal === '') {
        showError('status', 'Vui lòng chọn trạng thái.');
    }

    if (!isValid) {
        e.preventDefault();
        if (firstErrorElement) {
            firstErrorElement.focus();
        }
    }
});
</script>

<?php include 'layout/footer.php'; ?>