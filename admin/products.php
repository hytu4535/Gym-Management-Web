<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Sản Phẩm";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_SALES');

// layout chung
include 'layout/header.php'; 
include 'layout/sidebar.php';

require_once '../config/db.php';

$filterName = trim((string) ($_GET['name'] ?? ''));
$filterCategoryId = trim((string) ($_GET['category_id'] ?? ''));
$filterPriceMin = trim((string) ($_GET['price_min'] ?? ''));
$filterPriceMax = trim((string) ($_GET['price_max'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$categoriesFilter = $conn->query("SELECT id, name FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$whereClauses = [];
$whereParams = [];
$whereTypes = '';
if ($filterName !== '') { $whereClauses[] = 'p.name LIKE ?'; $whereParams[] = '%' . $filterName . '%'; $whereTypes .= 's'; }
if ($filterCategoryId !== '' && ctype_digit($filterCategoryId)) { $whereClauses[] = 'p.category_id = ?'; $whereParams[] = (int) $filterCategoryId; $whereTypes .= 'i'; }
if ($filterPriceMin !== '' && is_numeric($filterPriceMin)) { $whereClauses[] = 'p.selling_price >= ?'; $whereParams[] = (float) $filterPriceMin; $whereTypes .= 'd'; }
if ($filterPriceMax !== '' && is_numeric($filterPriceMax)) { $whereClauses[] = 'p.selling_price <= ?'; $whereParams[] = (float) $filterPriceMax; $whereTypes .= 'd'; }
if ($filterStatus !== '') { $whereClauses[] = 'p.status = ?'; $whereParams[] = $filterStatus; $whereTypes .= 's'; }
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Kiểm tra bảng product_reviews có tồn tại không
$checkReviewTable = $conn->query("SHOW TABLES LIKE 'product_reviews'");
$hasReviewTable = $checkReviewTable && $checkReviewTable->num_rows > 0;

if ($hasReviewTable) {
    $sql = "SELECT p.id, p.name, p.short_description, p.img, p.unit, p.stock_quantity, p.selling_price, p.status, c.name AS category_name,
             COALESCE(rs.review_count, 0) AS review_count,
             COALESCE(rs.avg_rating, 0) AS avg_rating
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN (
                SELECT product_id, COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS avg_rating
                FROM product_reviews
                WHERE status = 'approved'
                GROUP BY product_id
            ) rs ON rs.product_id = p.id
            ORDER BY p.id DESC";
} else {
    $sql = "SELECT p.id, p.name, p.short_description, p.img, p.unit, p.stock_quantity, p.selling_price, p.status, c.name AS category_name,
             0 AS review_count,
             0 AS avg_rating
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
             " . $whereSql . "
            ORDER BY p.id DESC";
}

      $stmt = $conn->prepare($sql);
      if (!empty($whereParams)) {
        $stmt->bind_param($whereTypes, ...$whereParams);
      }
      $stmt->execute();
      $result = $stmt->get_result();
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Sản Phẩm</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Sản Phẩm</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php
          $filterMode = 'server';
          $filterAction = 'products.php';
          $filterFieldsHtml = '
            <div class="col-md-2"><div class="form-group mb-0"><label>Tên SP</label><input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Tên sản phẩm"></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Danh mục</label><select name="category_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($categoriesFilter as $categoryOption) {
            $selected = $filterCategoryId !== '' && (int) $filterCategoryId === (int) $categoryOption['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $categoryOption['id'] . '" ' . $selected . '>' . htmlspecialchars($categoryOption['name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Giá từ</label><input type="number" name="price_min" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMin) . '" placeholder=">="></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Giá đến</label><input type="number" name="price_max" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMax) . '" placeholder="<="></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="active" ' . ($filterStatus === 'active' ? 'selected' : '') . '>Active</option><option value="inactive" ' . ($filterStatus === 'inactive' ? 'selected' : '') . '>Inactive</option></select></div></div>
          ';
          include 'layout/filter-card.php';
        ?>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Sản Phẩm (Whey, Nước, Phụ Kiện)</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">
                    <i class="fas fa-plus"></i> Thêm Sản Phẩm
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tên Sản Phẩm</th>
                    <th>Mô tả ngắn</th>
                    <th>Đánh giá</th>
                    <th>Danh Mục</th>
                    <th>Đơn Vị</th>
                    <th>Tồn Kho</th>
                    <th>Giá Bán (VNĐ)</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php 
                      if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            if ($row['status'] == 'active') {
                                $statusBadge = '<span class="badge badge-success">Active</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-secondary">Inactive</span>';
                            }                     
                            $formattedPrice = number_format($row['selling_price'], 0, ',', '.');   
                            $imgPath = !empty($row['img'])
                                ? "../assets/uploads/products/{$row['img']}"
                                : "../assets/uploads/products/default-product.jpg";
                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td><img src='{$imgPath}' alt='{$row['name']}' style='width: 60px; height: 60px; object-fit: cover; border-radius: 5px;'></td>";
                            echo "  <td>{$row['name']}</td>";
                            echo "  <td>" . htmlspecialchars($row['short_description'] ?? '') . "</td>";
                            $reviewCount = (int)($row['review_count'] ?? 0);
                            $avgRating = (float)($row['avg_rating'] ?? 0);
                            if ($reviewCount > 0) {
                              echo "  <td>" . number_format($avgRating, 1) . "/5 <small class='text-muted'>({$reviewCount})</small></td>";
                            } else {
                              echo "  <td><span class='text-muted'>Chưa có đánh giá</span></td>";
                            }
                            echo "  <td><span class='badge badge-info'>" . ($row['category_name'] ?? 'Chưa phân loại') . "</span></td>";
                            echo "  <td>{$row['unit']}</td>";
                            echo "  <td>{$row['stock_quantity']}</td>"; 
                            echo "  <td>{$formattedPrice}</td>";       
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                      <a href='product_edit.php?id={$row['id']}' 
                                        class='btn btn-warning btn-sm' title='Sửa'>
                                        <i class='fas fa-edit'></i>
                                      </a>
                                      <a href='./process/product_delete.php?id={$row['id']}'    
                                        class='btn btn-danger btn-sm'    
                                        onclick=\"return confirm(' XÁC NHẬN XÓA SẢN PHẨM\\n\\n Lưu ý:\\n- Nếu đã bán: Sẽ ẨN khỏi website (khách không xem được)\\n- Nếu chưa bán: Sẽ XÓA HOÀN TOÀN khỏi hệ thống\\n\\n Bạn có chắc chắn muốn tiếp tục?');\">   
                                        <i class='fas fa-trash'></i>
                                      </a>
                                    </td>";
                            echo "</tr>";
                        }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>Hiện chưa có sản phẩm nào.</td></tr>";
                        }
                  ?>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel">Thêm Sản Phẩm Mới</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addProductForm" action="process/product_add.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="name">Tên Sản Phẩm <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nhập tên sản phẩm...">
              </div>
              
<div class="form-group">
                <label for="short_description">Mô tả ngắn</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="3" placeholder="Mô tả ngắn về sản phẩm..."></textarea>
              </div>

              <div class="form-group">
                <label for="description">Mô tả chi tiết</label>
                <textarea class="form-control" id="description" name="description" rows="6" placeholder="Mô tả chi tiết về sản phẩm..." style="min-height: 140px;"></textarea>
              </div>

              <div class="form-group">
                <label for="category_id">Danh Mục</label>
                <select class="form-control" id="category_id" name="category_id">
                  <option value="">-- Chọn danh mục --</option>
                  <?php
                    $cat_sql = "SELECT id, name FROM categories";
                    $cat_result = $conn->query($cat_sql);
                    if ($cat_result && $cat_result->num_rows > 0) {
                        while($cat = $cat_result->fetch_assoc()) {
                            echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                        }
                    }
                  ?>
                </select>
              </div>
              
              <div class="form-group">
                <label for="img">Hình Ảnh Sản Phẩm</label>
                <input type="file" class="form-control-file" id="img" name="img" accept="image/*" onchange="previewImage(event)">
                <small class="form-text text-muted">Chọn ảnh định dạng JPG, PNG, GIF (tối đa 2MB)</small>
              </div>
              
              <div class="form-group text-center">
                <img id="imagePreview" src="../assets/uploads/products/default-product.jpg" alt="Preview" style="max-width: 200px; max-height: 200px; display: block; margin: 10px auto; border: 2px dashed #ddd; padding: 5px; border-radius: 5px;">
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="unit">Đơn Vị Tính</label>
                <input type="text" class="form-control" id="unit" name="unit" placeholder="hộp, chai, cái...">
              </div>

              <div class="form-group">
                <label for="selling_price">Giá Bán Lẻ (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="selling_price" name="selling_price" min="0" placeholder="Nhập giá bán...">
              </div>

              <div class="form-group">
                <label for="stock_quantity">Số Lượng Tồn Kho</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" placeholder="Nhập số lượng...">
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="status">Trạng Thái</label>
            <select class="form-control" id="status" name="status">
              <option value="active">Active (Kích hoạt)</option>
              <option value="inactive">Inactive (Ẩn)</option>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" name="btn_add_product">Lưu Sản Phẩm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Preview image before upload
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('imagePreview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

document.getElementById('addProductForm').addEventListener('submit', function(e) {
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
        showError('name', 'Vui lòng nhập tên sản phẩm.');
    } else if (nameVal.length < 3) {
        showError('name', 'Tên sản phẩm phải có ít nhất 3 ký tự.');
    }
    let categoryVal = document.getElementById('category_id').value;
    if (categoryVal === '') {
        showError('category_id', 'Vui lòng chọn một danh mục cho sản phẩm.');
    }

    let imgInput = document.getElementById('img');
    if (imgInput.files.length > 0) {
        let file = imgInput.files[0];
        let fileSizeMB = file.size / (1024 * 1024);
        let validExtensions = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!validExtensions.includes(file.type)) {
            showError('img', 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF, WEBP.');
        } else if (fileSizeMB > 2) {
            showError('img', 'Kích thước ảnh vượt quá dung lượng cho phép (Tối đa 2MB).');
        }
    } 
    else { 
      showError('img', 'Vui lòng chọn hình ảnh cho sản phẩm.'); 
    }

    let unitVal = document.getElementById('unit').value.trim();
    if (unitVal === '') {
        showError('unit', 'Vui lòng nhập đơn vị tính (vd: hộp, chai...).');
    }

    let priceVal = document.getElementById('selling_price').value;
    if (priceVal === '' || isNaN(priceVal) || parseFloat(priceVal) <= 0) {
        showError('selling_price', 'Giá bán phải là số và lớn hơn 0.');
    }

    let stockVal = document.getElementById('stock_quantity').value;
    if (stockVal === '' || isNaN(stockVal) || parseInt(stockVal) < 0) {
        showError('stock_quantity', 'Vui lòng nhập số lượng tồn kho (>= 0).');
    }

    let statusVal = document.getElementById('status').value;
    if (statusVal === '') {
        showError('status', 'Vui lòng chọn trạng thái sản phẩm.');
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
