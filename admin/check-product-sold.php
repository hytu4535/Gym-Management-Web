<?php 
$page_title = "Kiểm tra sản phẩm đã bán";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

// Lấy tất cả sản phẩm và kiểm tra xem đã bán hay chưa
$sql = "SELECT 
            p.id, 
            p.name, 
            p.status,
            COUNT(oi.id) as times_sold,
            SUM(oi.quantity) as total_quantity_sold
        FROM products p
        LEFT JOIN order_items oi ON oi.item_type = 'product' AND oi.item_id = p.id
        GROUP BY p.id, p.name, p.status
        ORDER BY p.id ASC";

$result = $conn->query($sql);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">🔍 Kiểm tra sản phẩm đã bán</h1>
                    <p class="text-muted">Trang này giúp bạn kiểm tra sản phẩm nào đã được bán ra để test tính năng xóa</p>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách sản phẩm và trạng thái bán hàng</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Trạng thái</th>
                                        <th>Số lần bán</th>
                                        <th>Tổng số lượng đã bán</th>
                                        <th>Kết quả khi XÓA</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($result && $result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            $statusBadge = $row['status'] == 'active' 
                                                ? '<span class="badge badge-success">Active</span>' 
                                                : '<span class="badge badge-secondary">Inactive (Đã ẩn)</span>';
                                            
                                            $soldTimes = $row['times_sold'];
                                            $soldQty = $row['total_quantity_sold'] ?? 0;
                                            
                                            if ($soldTimes > 0) {
                                                $soldBadge = '<span class="badge badge-warning">Đã bán '.$soldTimes.' lần</span>';
                                                $resultAction = '<span class="text-danger"><strong>ẨN KHỎI WEB</strong> (Soft Delete)</span>';
                                                $deleteBtn = '<a href="./process/product_delete.php?id='.$row['id'].'" 
                                                    class="btn btn-info btn-sm" 
                                                    onclick="return confirm(\'Sản phẩm này ĐÃ BÁN '.$soldTimes.' lần\\nSẽ chỉ ẨN khỏi web, không xóa database\\nContinue?\');">
                                                    🔒 Test Ẩn
                                                </a>';
                                            } else {
                                                $soldBadge = '<span class="badge badge-success">Chưa bán</span>';
                                                $resultAction = '<span class="text-success"><strong>XÓA HOÀN TOÀN</strong> (Hard Delete)</span>';
                                                $deleteBtn = '<a href="./process/product_delete.php?id='.$row['id'].'" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="return confirm(\'Sản phẩm này CHƯA BÁN\\nSẽ XÓA HOÀN TOÀN khỏi database\\nContinue?\');">
                                                    🗑️ Test Xóa
                                                </a>';
                                            }
                                            
                                            echo "<tr>";
                                            echo "  <td>{$row['id']}</td>";
                                            echo "  <td><strong>{$row['name']}</strong></td>";
                                            echo "  <td>{$statusBadge}</td>";
                                            echo "  <td class='text-center'><h5><span class='badge badge-primary'>{$soldTimes}</span></h5></td>";
                                            echo "  <td class='text-center'><span class='badge badge-info'>{$soldQty}</span></td>";
                                            echo "  <td>{$resultAction}</td>";
                                            echo "  <td>{$deleteBtn}</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>Không có sản phẩm nào</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Hướng dẫn -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">📚 Hướng dẫn test</h3>
                        </div>
                        <div class="card-body">
                            <h5>🎯 Test Case 1: Xóa sản phẩm ĐÃ BÁN (Soft Delete)</h5>
                            <ol>
                                <li>Chọn sản phẩm có <span class="badge badge-warning">Đã bán</span></li>
                                <li>Click nút <button class="btn btn-info btn-sm">🔒 Test Ẩn</button></li>
                                <li>Kết quả: 
                                    <ul>
                                        <li>✅ Status chuyển sang "Inactive"</li>
                                        <li>✅ Vẫn còn trong database</li>
                                        <li>✅ KHÔNG hiển thị trên web client</li>
                                    </ul>
                                </li>
                            </ol>
                            
                            <hr>
                            
                            <h5>🎯 Test Case 2: Xóa sản phẩm CHƯA BÁN (Hard Delete)</h5>
                            <ol>
                                <li>Chọn sản phẩm có <span class="badge badge-success">Chưa bán</span></li>
                                <li>Click nút <button class="btn btn-danger btn-sm">🗑️ Test Xóa</button></li>
                                <li>Kết quả:
                                    <ul>
                                        <li>✅ Xóa hoàn toàn khỏi database</li>
                                        <li>✅ File hình ảnh cũng bị xóa</li>
                                        <li>✅ Không thể khôi phục</li>
                                    </ul>
                                </li>
                            </ol>
                            
                            <hr>
                            
                            <div class="alert alert-warning">
                                <strong>⚠️ Lưu ý:</strong> Nếu TẤT CẢ sản phẩm đều hiển thị "Chưa bán", có thể:
                                <ul>
                                    <li>❌ Bạn chưa import lại database mới (có bảng order_items với item_type)</li>
                                    <li>❌ Bảng order_items đang trống</li>
                                    <li>💡 Giải pháp: Chạy lại file <code>gym_management.sql</code> để có dữ liệu test</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'layout/footer.php'; ?>
