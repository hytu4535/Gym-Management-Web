<?php
/**
 * File helper xử lý giảm giá theo tier và promotions
 */

/**
 * Lấy thông tin tier và giảm giá của member
 * @param int $user_id - ID của user
 * @param object $conn - Database connection
 * @return array - Thông tin tier và giảm giá
 */
function getMemberTierDiscount($user_id, $conn) {
    $sql = "SELECT m.id as member_id, m.tier_id, mt.name as tier_name, mt.base_discount, mt.level
            FROM members m
            JOIN member_tiers mt ON m.tier_id = mt.id
            WHERE m.users_id = ? AND m.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [
        'member_id' => null,
        'tier_id' => 1,
        'tier_name' => 'Đồng',
        'base_discount' => 0,
        'level' => 1
    ];
}

/**
 * Lấy danh sách promotion có thể sử dụng cho tier
 * @param int $tier_id - ID của tier
 * @param object $conn - Database connection
 * @return array - Danh sách promotions
 */
function getAvailablePromotions($tier_id, $conn) {
    $today = date('Y-m-d');
    
    $sql = "SELECT tp.id, tp.name, tp.discount_type, tp.discount_value, tp.usage_limit, 
                   tp.start_date, tp.end_date,
                   COUNT(pu.id) as used_count
            FROM tier_promotions tp
            LEFT JOIN promotion_usage pu ON tp.id = pu.promotion_id
            WHERE tp.tier_id = ? 
            AND tp.status = 'active' 
            AND tp.start_date <= ? 
            AND tp.end_date >= ?
            GROUP BY tp.id
            HAVING (tp.usage_limit IS NULL OR used_count < tp.usage_limit)
            ORDER BY tp.discount_value DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $tier_id, $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $promotions = [];
    while ($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }
    
    return $promotions;
}

/**
 * Lấy thông tin promotion theo ID
 * @param int $promotion_id - ID của promotion
 * @param object $conn - Database connection
 * @return array|null - Thông tin promotion hoặc null
 */
function getPromotionById($promotion_id, $conn) {
    $today = date('Y-m-d');
    
    $sql = "SELECT tp.id, tp.name, tp.tier_id, tp.discount_type, tp.discount_value, tp.usage_limit,
                   COUNT(pu.id) as used_count
            FROM tier_promotions tp
            LEFT JOIN promotion_usage pu ON tp.id = pu.promotion_id
            WHERE tp.id = ? 
            AND tp.status = 'active' 
            AND tp.start_date <= ? 
            AND tp.end_date >= ?
            GROUP BY tp.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $promotion_id, $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $promo = $result->fetch_assoc();
        
        // Kiểm tra usage_limit
        if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
            return null; // Promotion đã hết lượt sử dụng
        }
        
        return $promo;
    }
    
    return null;
}

/**
 * Tính giá sau khi giảm cho sản phẩm (chỉ base_discount)
 * @param float $original_price - Giá gốc
 * @param int $user_id - ID của user
 * @param object $conn - Database connection
 * @return array - Thông tin giá và giảm giá
 */
function calculateDiscountedPrice($original_price, $user_id, $conn) {
    // Nếu chưa đăng nhập, trả về giá gốc
    if ($user_id == 0) {
        return [
            'original_price' => $original_price,
            'final_price' => $original_price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tier_name' => null,
            'has_discount' => false
        ];
    }
    
    // Lấy thông tin tier - CHỈ dùng base_discount
    $tier_info = getMemberTierDiscount($user_id, $conn);
    $base_discount = $tier_info['base_discount'];
    
    // Tính giá sau giảm (CHỈ base_discount, KHÔNG tự động áp promotion)
    $discount_amount = ($original_price * $base_discount) / 100;
    $final_price = $original_price - $discount_amount;
    
    return [
        'original_price' => $original_price,
        'final_price' => round($final_price, 0),
        'discount_percent' => $base_discount,
        'discount_amount' => round($discount_amount, 0),
        'tier_name' => $tier_info['tier_name'],
        'tier_level' => $tier_info['level'],
        'has_discount' => $base_discount > 0
    ];
}

/**
 * Format hiển thị giá với tag HTML
 * @param array $price_info - Thông tin giá từ calculateDiscountedPrice
 * @return string - HTML hiển thị giá
 */
function formatPriceDisplay($price_info) {
    $html = '';
    
    if ($price_info['has_discount']) {
        // Hiển thị giá gốc gạch ngang
        $html .= '<span style="text-decoration: line-through; color: #999; font-size: 14px; margin-right: 8px;">';
        $html .= number_format($price_info['original_price'], 0, ',', '.') . ' VNĐ';
        $html .= '</span>';
        
        // Hiển thị giá sau giảm
        $html .= '<span style="color: #e7ab3c; font-weight: bold; font-size: 18px;">';
        $html .= number_format($price_info['final_price'], 0, ',', '.') . ' VNĐ';
        $html .= '</span>';
        
        // Badge giảm giá
        $html .= ' <span style="background: #ff4444; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 5px;">';
        $html .= '-' . number_format($price_info['discount_percent'], 0) . '%';
        $html .= '</span>';
    } else {
        // Không có giảm giá, hiển thị giá bình thường
        $html .= '<span style="color: #e7ab3c; font-weight: bold; font-size: 18px;">';
        $html .= number_format($price_info['original_price'], 0, ',', '.') . ' VNĐ';
        $html .= '</span>';
    }
    
    return $html;
}

function calculateCartTotal($user_id, $conn, $promotion_id = 0) {
    // Lấy các mục trong giỏ
    $cart_sql = "
        SELECT ci.item_type, ci.quantity,
               p.selling_price,
               mp.price AS package_price,
               s.price AS service_price,
               cs.price_per_session AS class_price
        FROM members m
        JOIN carts c ON m.id = c.member_id AND c.status = 'active'
        JOIN cart_items ci ON c.id = ci.cart_id
        LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
        LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
        LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
        LEFT JOIN class_schedules cs ON ci.item_type = 'class' AND ci.item_id = cs.id
        WHERE m.users_id = ?
    ";
    
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subtotal_original = 0;
    $subtotal_after_base = 0; // Sau khi giảm base_discount
    
    while ($item = $result->fetch_assoc()) {
        if ($item['item_type'] === 'product') {
            $original_price = (float) $item['selling_price'];
            $item_total = $original_price * $item['quantity'];
            $subtotal_original += $item_total;

            // Tính giá sau base_discount
            $price_info = calculateDiscountedPrice($original_price, $user_id, $conn);
            $subtotal_after_base += ($price_info['final_price'] * $item['quantity']);
            continue;
        }

        if ($item['item_type'] === 'service') {
            $original_price = (float) $item['service_price'];
        } elseif ($item['item_type'] === 'class') {
            $original_price = (float) $item['class_price'];
        } else {
            $original_price = (float) $item['package_price'];
        }
        $item_total = $original_price * $item['quantity'];
        $subtotal_original += $item_total;
        $subtotal_after_base += $item_total;
    }
    
    $base_discount_amount = $subtotal_original - $subtotal_after_base;
    
    // Lấy thông tin tier
    $tier_info = getMemberTierDiscount($user_id, $conn);
    
    // Áp dụng promotion (nếu có)
    $promotion_discount = 0;
    $promotion_info = null;
    $final_subtotal = $subtotal_after_base;
    
    if ($promotion_id > 0) {
        $promotion_info = getPromotionById($promotion_id, $conn);
        
        if ($promotion_info && $promotion_info['tier_id'] == $tier_info['tier_id']) {
            if ($promotion_info['discount_type'] == 'percentage') {
                // Giảm % trên tổng giỏ hàng (sau base_discount)
                $promotion_discount = ($subtotal_after_base * $promotion_info['discount_value']) / 100;
            } elseif ($promotion_info['discount_type'] == 'fixed') {
                // Giảm số tiền cố định
                $promotion_discount = $promotion_info['discount_value'];
            }
            
            $final_subtotal = $subtotal_after_base - $promotion_discount;
            if ($final_subtotal < 0) $final_subtotal = 0;
        }
    }
    
    return [
        'subtotal_original' => $subtotal_original,
        'subtotal_after_base' => $subtotal_after_base,
        'base_discount_amount' => $base_discount_amount,
        'base_discount_percent' => $tier_info['base_discount'],
        'promotion_discount' => round($promotion_discount, 0),
        'promotion_info' => $promotion_info,
        'final_subtotal' => round($final_subtotal, 0),
        'tier_name' => $tier_info['tier_name'],
        'tier_id' => $tier_info['tier_id'],
        'has_promotion' => $promotion_discount > 0
    ];
}