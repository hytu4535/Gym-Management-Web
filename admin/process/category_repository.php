<?php

require_once __DIR__ . '/../../includes/database.php';

if (!function_exists('categoryDb')) {
  function categoryDb(): PDO
  {
    return getDB();
  }
}

if (!function_exists('categoryCurrentUserIsAdmin')) {
  function categoryCurrentUserIsAdmin(): bool
  {
    return !empty($_SESSION['is_admin_role']) || strtolower((string) ($_SESSION['role'] ?? '')) === 'admin';
  }
}

if (!function_exists('categoryNormalizeName')) {
  function categoryNormalizeName($name): string
  {
    return trim((string) $name);
  }
}

if (!function_exists('categoryDuplicateNameCount')) {
  function categoryDuplicateNameCount(PDO $db, string $name, int $excludeId = 0): int
  {
    // 1) Kiểm tra trùng tên không phân biệt hoa thường và bỏ khoảng trắng đầu/cuối.
    $stmt = $db->prepare(
      "SELECT COUNT(*)
       FROM categories
       WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
         AND id != ?"
    );
    $stmt->execute([$name, $excludeId]);

    return (int) $stmt->fetchColumn();
  }
}

if (!function_exists('categoryProductCount')) {
  function categoryProductCount(PDO $db, int $categoryId, bool $activeOnly = false): int
  {
    // 1) Đếm sản phẩm thuộc danh mục.
    $sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
    $params = [$categoryId];

    // 2) Nếu cần kiểm tra riêng sản phẩm đang bán thì chỉ lấy active.
    if ($activeOnly) {
      $sql .= " AND status = 'active'";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
  }
}

if (!function_exists('getActiveCategories')) {
  function getActiveCategories(): array
  {
    $db = categoryDb();

    // 1) Chỉ lấy danh mục đang hoạt động.
    $stmt = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id DESC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
  }
}

if (!function_exists('getCategoryById')) {
  function getCategoryById(int $id): ?array
  {
    $db = categoryDb();

    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    return $category ?: null;
  }
}

if (!function_exists('createCategory')) {
  function createCategory($name, $description): array
  {
    $db = categoryDb();
    $name = categoryNormalizeName($name);
    $description = trim((string) $description);

    try {
      // 1) Validate tên danh mục.
      if ($name === '') {
        return [false, 'Tên danh mục không được để trống'];
      }

      if (mb_strlen($name, 'UTF-8') < 2) {
        return [false, 'Tên danh mục phải có ít nhất 2 ký tự'];
      }

      // 2) Check trùng tên không phân biệt hoa thường.
      if (categoryDuplicateNameCount($db, $name, 0) > 0) {
        return [false, 'Tên danh mục đã tồn tại'];
      }

      // 3) INSERT luôn ở trạng thái active.
      $stmt = $db->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')");
      $stmt->execute([$name, $description !== '' ? $description : null]);

      return [true, 'Thêm danh mục thành công'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

if (!function_exists('updateCategory')) {
  function updateCategory($id, $name, $description): array
  {
    $db = categoryDb();
    $id = (int) $id;
    $name = categoryNormalizeName($name);
    $description = trim((string) $description);

    try {
      // 1) Kiểm tra danh mục tồn tại.
      $currentStmt = $db->prepare("SELECT id, name FROM categories WHERE id = ? LIMIT 1");
      $currentStmt->execute([$id]);
      $currentCategory = $currentStmt->fetch(PDO::FETCH_ASSOC);

      if (!$currentCategory) {
        return [false, 'Danh mục không tồn tại'];
      }

      // 2) Validate tên danh mục.
      if ($name === '') {
        return [false, 'Tên danh mục không được để trống'];
      }

      if (mb_strlen($name, 'UTF-8') < 2) {
        return [false, 'Tên danh mục phải có ít nhất 2 ký tự'];
      }

      // 3) Check trùng tên không phân biệt hoa thường.
      if (categoryDuplicateNameCount($db, $name, $id) > 0) {
        return [false, 'Tên danh mục đã tồn tại'];
      }

      $oldName = categoryNormalizeName($currentCategory['name'] ?? '');
      $isNameChanged = mb_strtolower($oldName, 'UTF-8') !== mb_strtolower($name, 'UTF-8');

      // 4) Nếu đã có sản phẩm thì không cho đổi tên, trừ admin.
      if ($isNameChanged) {
        $productCount = categoryProductCount($db, $id, false);
        if ($productCount > 0 && !categoryCurrentUserIsAdmin()) {
          return [false, 'Không thể đổi tên danh mục khi đã có sản phẩm, trừ tài khoản admin'];
        }
      }

      // 5) Cập nhật tên và mô tả.
      $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
      $stmt->execute([$name, $description !== '' ? $description : null, $id]);

      return [true, 'Cập nhật danh mục thành công'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

if (!function_exists('deactivateCategory')) {
  function deactivateCategory($id): array
  {
    $db = categoryDb();
    $id = (int) $id;

    try {
      // 1) Kiểm tra danh mục tồn tại.
      $currentStmt = $db->prepare("SELECT id, status FROM categories WHERE id = ? LIMIT 1");
      $currentStmt->execute([$id]);
      $currentCategory = $currentStmt->fetch(PDO::FETCH_ASSOC);

      if (!$currentCategory) {
        return [false, 'Danh mục không tồn tại'];
      }

      // 2) Không cho vô hiệu hóa nếu còn sản phẩm đang bán.
      $activeProductCount = categoryProductCount($db, $id, true);
      if ($activeProductCount > 0) {
        return [false, 'Không thể vô hiệu hóa danh mục khi còn sản phẩm đang bán'];
      }

      // 3) Soft delete: chỉ đổi sang inactive, tuyệt đối không DELETE.
      $stmt = $db->prepare("UPDATE categories SET status = 'inactive' WHERE id = ?");
      $stmt->execute([$id]);

      return [true, 'Vô hiệu hóa danh mục thành công'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}