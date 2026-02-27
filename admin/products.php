<?php 
$page_title = "Quản lý Sản Phẩm";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$sql = "SELECT p.id, p.name, p.img, p.unit, p.stock_quantity, p.selling_price, p.status, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC";

$result = $conn->query($sql);
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
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tên Sản Phẩm</th>
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
                            $imgPath = $row['img'] ? "../assets/uploads/products/{$row['img']}" : "../assets/uploads/products/default-product.jpg";
                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td><img src='{$imgPath}' alt='{$row['name']}' style='width: 60px; height: 60px; object-fit: cover; border-radius: 5px;'></td>";
                            echo "  <td>{$row['name']}</td>";
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
                                        onclick=\"return confirm('⚠️ XÁC NHẬN XÓA SẢN PHẨM\\n\\n📌 Lưu ý:\\n- Nếu đã bán: Sẽ ẨN khỏi website (khách không xem được)\\n- Nếu chưa bán: Sẽ XÓA HOÀN TOÀN khỏi hệ thống\\n\\n❓ Bạn có chắc chắn muốn tiếp tục?');\">   
                                        <i class='fas fa-trash'></i>
                                      </a>
                                    </td>";
                            echo "</tr>";
                        }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>Hiện chưa có sản phẩm nào.</td></tr>";
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
      <form action="process/product_add.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="name">Tên Sản Phẩm <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="Nhập tên sản phẩm...">
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
                <input type="number" class="form-control" id="selling_price" name="selling_price" required min="0" value="0">
              </div>

              <div class="form-group">
                <label for="stock_quantity">Số Lượng Tồn Kho</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="0">
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
</script>

<?php include 'layout/footer.php'; ?>
