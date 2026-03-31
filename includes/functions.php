<?php
/**
 * Utility Functions
 * Gym Management System
 */

require_once __DIR__ . '/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

if (!function_exists('generateToken')) {
    function generateToken($length = 32) {
        try {
            return bin2hex(random_bytes(max(16, (int) $length)));
        } catch (Exception $e) {
            return hash('sha256', uniqid((string) mt_rand(), true));
        }
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return (int) ($_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? 0);
    }
}

if (!function_exists('logActivity')) {
    function logActivity($userId, $action, $module, $recordId = null, $description = '') {
        error_log(sprintf(
            '[activity] user=%s action=%s module=%s record=%s description=%s',
            (string) ($userId ?? 0),
            (string) $action,
            (string) $module,
            (string) ($recordId ?? ''),
            (string) $description
        ));

        return true;
    }
}

function sendNotification($userId, $title, $message, $type = 'info') {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$userId, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get flash message
 */
function setFlashMessage($type, $message) {
    if (in_array((string) $type, ['danger', 'error'], true)) {
        $message = toVietnameseDbError($message, (string) $message);
    }

    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Redirect to a page
 */
function redirect($path) {
    header('Location: ' . $path);
    exit;
}

/**
 * ========== SUPPLIER FUNCTIONS ==========
 */

/**
 * Get all suppliers
 */
function getAllSuppliers() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM suppliers ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting suppliers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get supplier by ID
 */
function getSupplierById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting supplier: " . $e->getMessage());
        return null;
    }
}

/**
 * Add new supplier
 */
function addSupplier($data) {
    try {
        $db = getDB();

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? 'active')));

        if ($name === '') {
            return ['success' => false, 'message' => 'Tên nhà cung cấp không được để trống'];
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $duplicateStmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE name = ?");
        $duplicateStmt->execute([$name]);
        if ((int) $duplicateStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Tên nhà cung cấp đã tồn tại'];
        }

        $stmt = $db->prepare("
            INSERT INTO suppliers (name, phone, address, status)
            VALUES (?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            sanitize($name),
            $phone !== '' ? sanitize($phone) : null,
            $address !== '' ? sanitize($address) : null,
            $status
        ]);

        if ($result) {
            $supplierId = $db->lastInsertId();
            logActivity(getCurrentUserId(), 'CREATE', 'suppliers', $supplierId, 'Added new supplier: ' . $name);
            return ['success' => true, 'id' => $supplierId, 'message' => 'Thêm nhà cung cấp thành công'];
        }

        return ['success' => false, 'message' => 'Failed to add supplier'];
    } catch (Exception $e) {
        error_log("Error adding supplier: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update supplier
 */
function updateSupplier($id, $data) {
    try {
        $db = getDB();
        $supplier = getSupplierById($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Nhà cung cấp không tồn tại'];
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? 'active')));

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $stmt = $db->prepare("
            UPDATE suppliers 
            SET phone = ?, address = ?, status = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $phone !== '' ? sanitize($phone) : null,
            $address !== '' ? sanitize($address) : null,
            $status,
            $id
        ]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'suppliers', $id, 'Updated supplier: ' . ($supplier['name'] ?? 'Unknown'));
            return ['success' => true, 'message' => 'Cập nhật nhà cung cấp thành công'];
        }
        
        return ['success' => false, 'message' => 'Failed to update supplier'];
    } catch (Exception $e) {
        error_log("Error updating supplier: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete supplier
 */
function deleteSupplier($id) {
    try {
        $db = getDB();
        $supplier = getSupplierById($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Nhà cung cấp không tồn tại'];
        }

        $importStmt = $db->prepare("SELECT COUNT(*) FROM import_slips WHERE supplier_id = ?");
        $importStmt->execute([$id]);
        $importCount = (int) $importStmt->fetchColumn();

        // Có phát sinh phiếu nhập: chỉ xóa mềm để đảm bảo toàn vẹn dữ liệu.
        if ($importCount > 0) {
            if (($supplier['status'] ?? 'active') === 'inactive') {
                return ['success' => true, 'message' => 'Nhà cung cấp đã có phiếu nhập và đang ở trạng thái không hoạt động'];
            }

            $stmt = $db->prepare("UPDATE suppliers SET status = 'inactive' WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                logActivity(getCurrentUserId(), 'DELETE', 'suppliers', $id, 'Set supplier inactive: ' . ($supplier['name'] ?? 'Unknown'));
                return ['success' => true, 'message' => 'Nhà cung cấp đã có phiếu nhập, hệ thống chuyển sang không hoạt động (xóa mềm)'];
            }

            return ['success' => false, 'message' => 'Không thể cập nhật trạng thái nhà cung cấp'];
        }

        // Chưa phát sinh phiếu nhập: cho phép xóa cứng.
        $deleteStmt = $db->prepare("DELETE FROM suppliers WHERE id = ?");
        $deleted = $deleteStmt->execute([$id]);

        if ($deleted) {
            logActivity(getCurrentUserId(), 'DELETE', 'suppliers', $id, 'Hard delete supplier: ' . ($supplier['name'] ?? 'Unknown'));
            return ['success' => true, 'message' => 'Nhà cung cấp chưa có phiếu nhập đã được xóa vĩnh viễn'];
        }

        return ['success' => false, 'message' => 'Không thể xóa nhà cung cấp'];
    } catch (Exception $e) {
        error_log("Error deleting supplier: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Search suppliers
 */
function searchSuppliers($keyword) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM suppliers 
            WHERE name LIKE ? OR phone LIKE ? OR address LIKE ?
            ORDER BY created_at DESC
        ");
        
        $searchTerm = '%' . $keyword . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error searching suppliers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get supplier statistics
 */
function getSupplierStats($supplierId) {
    try {
        $db = getDB();

        $importStmt = $db->prepare("SELECT COUNT(*) FROM import_slips WHERE supplier_id = ?");
        $importStmt->execute([$supplierId]);
        $imports = (int) $importStmt->fetchColumn();

        $valueStmt = $db->prepare("
            SELECT COALESCE(SUM(total_amount), 0)
            FROM import_slips
            WHERE supplier_id = ?
        ");
        $valueStmt->execute([$supplierId]);
        $totalValue = (float) $valueStmt->fetchColumn();

        return [
            'imports' => $imports,
            'total_value' => $totalValue
        ];
    } catch (Exception $e) {
        error_log("Error getting supplier stats: " . $e->getMessage());
        return ['imports' => 0, 'total_value' => 0];
    }
}

/**
 * ========== EQUIPMENT FUNCTIONS ==========
 */

/**
 * Get all equipment
 */
function getAllEquipment() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM equipment ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting equipment: " . $e->getMessage());
        return [];
    }
}

/**
 * Get equipment by ID
 */
function getEquipmentById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting equipment: " . $e->getMessage());
        return null;
    }
}

/**
 * Add new equipment
 */
function addEquipment($data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO equipment (name, quantity, status)
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            sanitize($data['name']),
            intval($data['quantity']) ?? 1,
            $data['status'] ?? 'dang su dung'
        ]);
        
        if ($result) {
            $equipmentId = $db->lastInsertId();
            logActivity(getCurrentUserId(), 'CREATE', 'equipment', $equipmentId, 'Added new equipment: ' . $data['name']);
            return ['success' => true, 'id' => $equipmentId, 'message' => 'Thêm thiết bị thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể thêm thiết bị'];
    } catch (Exception $e) {
        error_log("Error adding equipment: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update equipment
 */
function updateEquipment($id, $data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE equipment 
            SET name = ?, quantity = ?, status = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            sanitize($data['name']),
            intval($data['quantity']) ?? 1,
            $data['status'] ?? 'dang su dung',
            $id
        ]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'equipment', $id, 'Updated equipment: ' . $data['name']);
            return ['success' => true, 'message' => 'Cập nhật thiết bị thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể cập nhật thiết bị'];
    } catch (Exception $e) {
        error_log("Error updating equipment: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete equipment
 */
function deleteEquipment($id) {
    try {
        $db = getDB();
        $equipment = getEquipmentById($id);
        
        $stmt = $db->prepare("DELETE FROM equipment WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'DELETE', 'equipment', $id, 'Deleted equipment: ' . ($equipment['name'] ?? 'Unknown'));
            return ['success' => true, 'message' => 'Xóa thiết bị thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể xóa thiết bị'];
    } catch (Exception $e) {
        error_log("Error deleting equipment: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get equipment by status
 */
function getEquipmentByStatus($status) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM equipment WHERE status = ? ORDER BY name");
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting equipment by status: " . $e->getMessage());
        return [];
    }
}

/**
 * ========== EQUIPMENT MAINTENANCE FUNCTIONS ==========
 */

/**
 * Get all maintenance records
 */
function getAllMaintenanceRecords() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT em.*, e.name as equipment_name
            FROM equipment_maintenance em
            JOIN equipment e ON e.id = em.equipment_id
            ORDER BY em.maintenance_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting maintenance records: " . $e->getMessage());
        return [];
    }
}

/**
 * Get maintenance record by ID
 */
function getMaintenanceRecordById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT em.*, e.name as equipment_name
            FROM equipment_maintenance em
            JOIN equipment e ON e.id = em.equipment_id
            WHERE em.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting maintenance record: " . $e->getMessage());
        return null;
    }
}

/**
 * Add maintenance record
 */
function addMaintenanceRecord($data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO equipment_maintenance (equipment_id, maintenance_date, description)
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            intval($data['equipment_id']),
            $data['maintenance_date'],
            sanitize($data['description']) ?? null
        ]);
        
        if ($result) {
            $recordId = $db->lastInsertId();
            // Update equipment status to maintenance
            $updateStmt = $db->prepare("UPDATE equipment SET status = 'bao tri' WHERE id = ?");
            $updateStmt->execute([$data['equipment_id']]);
            
            logActivity(getCurrentUserId(), 'CREATE', 'equipment_maintenance', $recordId, 'Added maintenance record');
            return ['success' => true, 'id' => $recordId, 'message' => 'Thêm bản ghi bảo trì thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể thêm bản ghi bảo trì'];
    } catch (Exception $e) {
        error_log("Error adding maintenance record: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update maintenance record
 */
function updateMaintenanceRecord($id, $data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE equipment_maintenance 
            SET equipment_id = ?, maintenance_date = ?, description = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            intval($data['equipment_id']),
            $data['maintenance_date'],
            sanitize($data['description']) ?? null,
            $id
        ]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'equipment_maintenance', $id, 'Updated maintenance record');
            return ['success' => true, 'message' => 'Cập nhật bản ghi bảo trì thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể cập nhật bản ghi bảo trì'];
    } catch (Exception $e) {
        error_log("Error updating maintenance record: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete maintenance record
 */
function deleteMaintenanceRecord($id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM equipment_maintenance WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'DELETE', 'equipment_maintenance', $id, 'Deleted maintenance record');
            return ['success' => true, 'message' => 'Xóa bản ghi bảo trì thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể xóa bản ghi bảo trì'];
    } catch (Exception $e) {
        error_log("Error deleting maintenance record: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get maintenance records by equipment
 */
function getMaintenanceByEquipment($equipmentId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM equipment_maintenance
            WHERE equipment_id = ?
            ORDER BY maintenance_date DESC
        ");
        $stmt->execute([$equipmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting maintenance records: " . $e->getMessage());
        return [];
    }
}

/**
 * ========== IMPORT SLIPS FUNCTIONS ==========
 */

/**
 * Get all import slips
 */
function getAllImportSlips() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT imp.*, s.name as supplier_name, st.name as staff_name
            FROM import_slips imp
            JOIN suppliers s ON s.id = imp.supplier_id
            JOIN staff st ON st.id = imp.staff_id
            ORDER BY imp.import_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting import slips: " . $e->getMessage());
        return [];
    }
}

/**
 * Get import slip by ID
 */
function getImportSlipById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT imp.*, s.name as supplier_name, st.name as staff_name
            FROM import_slips imp
            JOIN suppliers s ON s.id = imp.supplier_id
            JOIN staff st ON st.id = imp.staff_id
            WHERE imp.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting import slip: " . $e->getMessage());
        return null;
    }
}

/**
 * Get import details
 */
function getImportDetails($importId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id.*, e.name as equipment_name, p.name as product_name
            FROM import_details id
            LEFT JOIN equipment e ON e.id = id.equipment_id
            LEFT JOIN products p ON p.id = id.product_id
            WHERE id.import_id = ?
        ");
        $stmt->execute([$importId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting import details: " . $e->getMessage());
        return [];
    }
}

/**
 * Add import slip
 */
function addImportSlip($data) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO import_slips (staff_id, supplier_id, total_amount, import_date, note, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            intval($data['staff_id'] ?? 1),
            intval($data['supplier_id']),
            floatval($data['total_amount']) ?? 0,
            $data['import_date'] ?? date('Y-m-d H:i:s'),
            sanitize($data['note']) ?? null,
            $data['status'] ?? 'Đang chờ duyệt'
        ]);
        
        if ($result) {
            $importId = $db->lastInsertId();
            
            // Add import details
            if (!empty($data['details'])) {
                $detailStmt = $db->prepare("
                    INSERT INTO import_details (import_id, equipment_id, product_id, quantity, import_price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($data['details'] as $detail) {
                    $detailStmt->execute([
                        $importId,
                        $detail['equipment_id'] ?? null,
                        $detail['product_id'] ?? null,
                        intval($detail['quantity']),
                        floatval($detail['import_price'])
                    ]);
                }
            }
            
            $db->commit();
            logActivity(getCurrentUserId(), 'CREATE', 'import_slips', $importId, 'Added new import slip');
            return ['success' => true, 'id' => $importId, 'message' => 'Thêm phiếu nhập thành công'];
        }
        
        $db->rollBack();
        return ['success' => false, 'message' => 'Không thể thêm phiếu nhập'];
    } catch (Exception $e) {
        error_log("Error adding import slip: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update import slip
 */
function updateImportSlip($id, $data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE import_slips
            SET total_amount = ?, note = ?, status = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            floatval($data['total_amount']) ?? 0,
            sanitize($data['note']) ?? null,
            $data['status'] ?? 'Đang chờ duyệt',
            $id
        ]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'import_slips', $id, 'Updated import slip');
            return ['success' => true, 'message' => 'Cập nhật phiếu nhập thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể cập nhật phiếu nhập'];
    } catch (Exception $e) {
        error_log("Error updating import slip: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete import slip
 */
function deleteImportSlip($id) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Delete import details first
        $detailStmt = $db->prepare("DELETE FROM import_details WHERE import_id = ?");
        $detailStmt->execute([$id]);
        
        // Delete import slip
        $stmt = $db->prepare("DELETE FROM import_slips WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $db->commit();
            logActivity(getCurrentUserId(), 'DELETE', 'import_slips', $id, 'Deleted import slip');
            return ['success' => true, 'message' => 'Xóa phiếu nhập thành công'];
        }
        
        $db->rollBack();
        return ['success' => false, 'message' => 'Không thể xóa phiếu nhập'];
    } catch (Exception $e) {
        error_log("Error deleting import slip: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * ========== FEEDBACK FUNCTIONS ==========
 */

/**
 * Get all feedback
 */
function getAllFeedback($status = null) {
    try {
        $db = getDB();
        
        if ($status) {
            $stmt = $db->prepare("
                SELECT f.*, m.users_id as member_user_id, COALESCE(NULLIF(m.full_name,''), mu.username) as member_name, ru.username as responded_by_name
                FROM feedback f
                JOIN members m ON m.id = f.member_id
                JOIN users mu ON mu.id = m.users_id
                LEFT JOIN users ru ON ru.id = f.responded_by
                WHERE f.status = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $db->query("
                SELECT f.*, m.users_id as member_user_id, COALESCE(NULLIF(m.full_name,''), mu.username) as member_name, ru.username as responded_by_name
                FROM feedback f
                JOIN members m ON m.id = f.member_id
                JOIN users mu ON mu.id = m.users_id
                LEFT JOIN users ru ON ru.id = f.responded_by
                ORDER BY f.created_at DESC
            ");
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting feedback: " . $e->getMessage());
        return [];
    }
}

/**
 * Get feedback by ID
 */
function getFeedbackById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT f.*, m.users_id as member_user_id, COALESCE(NULLIF(m.full_name,''), mu.username) as member_name, ru.username as responded_by_name
            FROM feedback f
            JOIN members m ON m.id = f.member_id
            JOIN users mu ON mu.id = m.users_id
            LEFT JOIN users ru ON ru.id = f.responded_by
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting feedback: " . $e->getMessage());
        return null;
    }
}

/**
 * Add feedback response
 */
function addFeedbackResponse($feedbackId, $userId, $response) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE feedback
            SET responded_by = ?, status = 'processed'
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$userId, $feedbackId]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'feedback', $feedbackId, 'Added response to feedback');
            return ['success' => true, 'message' => 'Thêm phản hồi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể thêm phản hồi'];
    } catch (Exception $e) {
        error_log("Error adding feedback response: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update feedback status
 */
function updateFeedbackStatus($feedbackId, $status, $userId = null) {
    try {
        $db = getDB();
        
        if ($userId) {
            $stmt = $db->prepare("
                UPDATE feedback
                SET status = ?, responded_by = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $userId, $feedbackId]);
        } else {
            $stmt = $db->prepare("
                UPDATE feedback
                SET status = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $feedbackId]);
        }
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'feedback', $feedbackId, 'Updated feedback status to: ' . $status);
            return ['success' => true, 'message' => 'Cập nhật trạng thái phản hồi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể cập nhật trạng thái'];
    } catch (Exception $e) {
        error_log("Error updating feedback status: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete feedback
 */
function deleteFeedback($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM feedback WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'DELETE', 'feedback', $id, 'Deleted feedback');
            return ['success' => true, 'message' => 'Xóa phản hồi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể xóa phản hồi'];
    } catch (Exception $e) {
        error_log("Error deleting feedback: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * ========== NOTIFICATION FUNCTIONS ==========
 */

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 20) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting unread count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
        ");
        return $stmt->execute([$notificationId]);
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * ========== TIER PROMOTIONS FUNCTIONS ==========
 */

/**
 * Get all tier promotions
 */
function getAllTierPromotions() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT tp.*, mt.name as tier_name
            FROM tier_promotions tp
            JOIN member_tiers mt ON mt.id = tp.tier_id
            ORDER BY tp.start_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting tier promotions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get tier promotion by ID
 */
function getTierPromotionById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT tp.*, mt.name as tier_name
            FROM tier_promotions tp
            JOIN member_tiers mt ON mt.id = tp.tier_id
            WHERE tp.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting tier promotion: " . $e->getMessage());
        return null;
    }
}

/**
 * Add tier promotion
 */
function addTierPromotion($data) {
    try {
        $db = getDB();
        
        $applicableItems = isset($data['applicable_items']) ? json_encode($data['applicable_items']) : null;
        
        $stmt = $db->prepare("
            INSERT INTO tier_promotions (name, tier_id, discount_type, discount_value, applicable_items, start_date, end_date, usage_limit, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            sanitize($data['name']),
            intval($data['tier_id']),
            $data['discount_type'] ?? 'percentage',
            floatval($data['discount_value']),
            $applicableItems,
            $data['start_date'],
            $data['end_date'],
            intval($data['usage_limit']) ?? null,
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            $promotionId = $db->lastInsertId();
            logActivity(getCurrentUserId(), 'CREATE', 'tier_promotions', $promotionId, 'Added new tier promotion: ' . $data['name']);
            return ['success' => true, 'id' => $promotionId, 'message' => 'Thêm ưu đãi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể thêm ưu đãi'];
    } catch (Exception $e) {
        error_log("Error adding tier promotion: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update tier promotion
 */
function updateTierPromotion($id, $data) {
    try {
        $db = getDB();
        
        $applicableItems = isset($data['applicable_items']) ? json_encode($data['applicable_items']) : null;
        
        $stmt = $db->prepare("
            UPDATE tier_promotions
            SET name = ?, tier_id = ?, discount_type = ?, discount_value = ?, applicable_items = ?, start_date = ?, end_date = ?, usage_limit = ?, status = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            sanitize($data['name']),
            intval($data['tier_id']),
            $data['discount_type'] ?? 'percentage',
            floatval($data['discount_value']),
            $applicableItems,
            $data['start_date'],
            $data['end_date'],
            intval($data['usage_limit']) ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'UPDATE', 'tier_promotions', $id, 'Updated tier promotion: ' . $data['name']);
            return ['success' => true, 'message' => 'Cập nhật ưu đãi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể cập nhật ưu đãi'];
    } catch (Exception $e) {
        error_log("Error updating tier promotion: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete tier promotion
 */
function deleteTierPromotion($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM tier_promotions WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logActivity(getCurrentUserId(), 'DELETE', 'tier_promotions', $id, 'Deleted tier promotion');
            return ['success' => true, 'message' => 'Xóa ưu đãi thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể xóa ưu đãi'];
    } catch (Exception $e) {
        error_log("Error deleting tier promotion: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get active promotions for tier
 */
function getActivePromotionsForTier($tierId) {
    try {
        $db = getDB();
        $today = date('Y-m-d');
        
        $stmt = $db->prepare("
            SELECT * FROM tier_promotions
            WHERE tier_id = ? AND status = 'active'
            AND start_date <= ? AND end_date >= ?
            ORDER BY start_date DESC
        ");
        
        $stmt->execute([$tierId, $today, $today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting promotions for tier: " . $e->getMessage());
        return [];
    }
}
?>