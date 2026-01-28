<?php
/**
 * AI Chatbot API - Simulated/Mock AI
 * Provides recommendations for training, nutrition, and service packages based on BMI
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Get parameters from both GET and POST
$_REQUEST = array_merge($_GET, $_POST);

switch($action) {
    case 'get_recommendation':
        getRecommendation();
        break;
    case 'chat':
        handleChat();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Calculate BMI and return classification
 */
function calculateBMI($height, $weight) {
    $heightInMeters = $height / 100;
    $bmi = $weight / ($heightInMeters * $heightInMeters);
    
    if ($bmi < 18.5) {
        $category = 'gay';
        $categoryLabel = 'Gầy';
        $status = '❌ Dưới chuẩn';
    } elseif ($bmi < 25) {
        $category = 'binh_thuong';
        $categoryLabel = 'Bình thường';
        $status = '✅ Khỏe mạnh';
    } elseif ($bmi < 30) {
        $category = 'thua_can';
        $categoryLabel = 'Thừa cân';
        $status = '⚠️ Cần cải thiện';
    } else {
        $category = 'beo_phi';
        $categoryLabel = 'Béo phì';
        $status = '🔴 Cần tập luyện';
    }
    
    return [
        'bmi' => round($bmi, 1),
        'category' => $category,
        'categoryLabel' => $categoryLabel,
        'status' => $status
    ];
}

/**
 * Get recommendation based on user profile
 */
function getRecommendation() {
    $height = isset($_REQUEST['height']) ? floatval($_REQUEST['height']) : 0;
    $weight = isset($_REQUEST['weight']) ? floatval($_REQUEST['weight']) : 0;
    $age = isset($_REQUEST['age']) ? intval($_REQUEST['age']) : 25;
    $gender = isset($_REQUEST['gender']) ? $_REQUEST['gender'] : 'male';
    $goal = isset($_REQUEST['goal']) ? $_REQUEST['goal'] : 'maintain';
    
    if ($height <= 0 || $weight <= 0) {
        echo json_encode(['error' => 'Chiều cao hoặc cân nặng không hợp lệ']);
        return;
    }
    
    $bmiInfo = calculateBMI($height, $weight);
    
    $trainingRecs = getTrainingRecommendations($bmiInfo, $age, $goal);
    $nutritionRecs = getNutritionRecommendations($bmiInfo, $weight, $age, $goal);
    $packageRecs = getServicePackageRecommendations($bmiInfo, $goal);
    
    echo json_encode([
        'success' => true,
        'bmi_info' => $bmiInfo,
        'profile' => [
            'height' => $height,
            'weight' => $weight,
            'age' => $age,
            'gender' => $gender,
            'goal' => $goal
        ],
        'recommendations' => [
            'training' => $trainingRecs,
            'nutrition' => $nutritionRecs,
            'packages' => $packageRecs
        ]
    ]);
}

/**
 * Get training recommendations
 */
function getTrainingRecommendations($bmiInfo, $age, $goal) {
    $category = $bmiInfo['category'];
    
    $plans = [
        'gay' => [
            'title' => '💪 CHƯƠNG TRÌNH TĂNG CƠ BẮP',
            'duration' => '3-4 tháng',
            'frequency' => '4-5 ngày/tuần',
            'intensity' => 'Cao',
            'exercises' => [
                '• Tập nặng: Squat, Deadlift, Bench Press (8-10 reps)',
                '• Cardio nhẹ: 2 lần/tuần (20-30 phút)',
                '• Tập nhóm cơ lớn: Lưng, chân, ngực',
                '• Nghỉ: 48-72 giờ giữa các buổi cơ giống'
            ],
            'calories' => 'Thặng dư 300-500 calo/ngày',
            'priority' => 'Xây dựng khối lượng cơ bắp'
        ],
        'binh_thuong' => [
            'title' => '⚖️ CHƯƠNG TRÌNH DUY TRÌ SỨC KHỎE',
            'duration' => 'Dài hạn',
            'frequency' => '3-4 ngày/tuần',
            'intensity' => 'Trung bình',
            'exercises' => [
                '• Tập lực lượng: 2-3 ngày/tuần',
                '• Cardio: 2-3 ngày/tuần (chạy, bơi, đạp xe)',
                '• Yoga/Duỗi cơ: 1-2 ngày/tuần',
                '• Kết hợp các bài tập toàn thân'
            ],
            'calories' => 'Cân bằng năng lượng',
            'priority' => 'Duy trì thể lực tổng thể'
        ],
        'thua_can' => [
            'title' => '🔥 CHƯƠNG TRÌNH GIẢM MỠ',
            'duration' => '2-4 tháng',
            'frequency' => '4-5 ngày/tuần',
            'intensity' => 'Cao',
            'exercises' => [
                '• Cardio: 4-5 ngày/tuần (chạy, bộ, HIIT)',
                '• Tập lực lượng: 2-3 ngày/tuần (nhẹ-trung)',
                '• HIIT: 2-3 lần/tuần (10-20 phút)',
                '• Yoga/Duỗi cơ: 1 ngày/tuần'
            ],
            'calories' => 'Thâm hụt 300-500 calo/ngày',
            'priority' => 'Giảm mỡ, duy trì cơ'
        ],
        'beo_phi' => [
            'title' => '🎯 CHƯƠNG TRÌNH GIẢM CÂN TOÀN DIỆN',
            'duration' => '3-6 tháng',
            'frequency' => '5 ngày/tuần',
            'intensity' => 'Cao',
            'exercises' => [
                '• Cardio: 5 ngày/tuần (bộ, bơi, xe đạp)',
                '• Tập lực: 2-3 ngày/tuần (nhẹ)',
                '• HIIT: 2 lần/tuần',
                '• Yoga/Thái cực: Xả stress'
            ],
            'calories' => 'Thâm hụt 500-750 calo/ngày',
            'priority' => 'Giảm cân an toàn'
        ]
    ];
    
    $plan = $plans[$category] ?? $plans['binh_thuong'];
    
    if ($age > 50) {
        $plan['special_note'] = '⚠️ Tuổi 50+: Tăng khởi động (10-15 phút), duỗi cơ 10-15 phút sau';
    }
    
    return $plan;
}

/**
 * Get nutrition recommendations
 */
function getNutritionRecommendations($bmiInfo, $weight, $age, $goal) {
    $category = $bmiInfo['category'];
    
    // Calculate daily calories (simple BMR)
    $bmr = 10 * $weight + 625 - 5 * $age + 5; // Simplified
    $tdee = $bmr * 1.4; // Moderate activity
    
    $plans = [
        'gay' => [
            'title' => '🍗 CHẾ ĐỘ ĂN TĂNG CƠ BẮP',
            'daily_calories' => round($tdee + 300) . ' calo',
            'macros' => [
                '🥩 Protein: ' . round($weight * 2.0) . 'g/ngày (35-40%)',
                '🍚 Carbs: ' . round($weight * 4.0) . 'g/ngày (45-50%)',
                '🥑 Fat: ' . round($weight * 0.8) . 'g/ngày (20-25%)'
            ],
            'meal_plan' => [
                '🌅 Sáng (6-7h): Trứng, cơm, rau',
                '🥗 Trưa: Thịt gà/cá + cơm + rau',
                '🍌 Chiều: Snack (sữa chua, hạt, chuối)',
                '🍖 Tối: Thịt nạc + khoai lang + rau',
                '🥛 Supper: Sữa, pho mát, hạt'
            ],
            'tips' => 'Ưu tiên: Whey protein, creatine, vitamin D'
        ],
        'binh_thuong' => [
            'title' => '⚖️ CHẾ ĐỘ ĂN CÂN BẰNG',
            'daily_calories' => round($tdee) . ' calo',
            'macros' => [
                '🥩 Protein: ' . round($weight * 1.6) . 'g/ngày (25-30%)',
                '🍚 Carbs: ' . round($weight * 3.0) . 'g/ngày (40-45%)',
                '🥑 Fat: ' . round($weight * 0.7) . 'g/ngày (25-30%)'
            ],
            'meal_plan' => [
                '🌅 Sáng: Bánh mỳ nguyên cây + trứng + rau',
                '🥗 Trưa: Cơm + đạm (30%) + rau',
                '🍌 Chiều: Hoa quả, yogurt',
                '🍖 Tối: Cơm + thịt/cá + rau'
            ],
            'tips' => 'Ưu tiên: Vitamin tổng hợp, omega-3'
        ],
        'thua_can' => [
            'title' => '🔥 CHẾ ĐỘ ĂN GIẢM MỠ',
            'daily_calories' => round($tdee - 400) . ' calo',
            'macros' => [
                '🥩 Protein: ' . round($weight * 1.8) . 'g/ngày (35-40%)',
                '🍚 Carbs: ' . round($weight * 2.0) . 'g/ngày (35-40%)',
                '🥑 Fat: ' . round($weight * 0.5) . 'g/ngày (20-25%)'
            ],
            'meal_plan' => [
                '🌅 Sáng: Trứng trắng + ngũ cốc nguyên cây',
                '🥗 Trưa: Thịt gà/cá nạc + rau lá xanh',
                '🍌 Chiều: Protein shake + quả',
                '🍖 Tối: Cá/tôm + rau không tinh bột'
            ],
            'tips' => 'Ưu tiên: L-carnitine, trà xanh, cardio'
        ],
        'beo_phi' => [
            'title' => '🎯 CHẾ ĐỘ ĂN GIẢM CÂN',
            'daily_calories' => round($tdee - 500) . ' calo',
            'macros' => [
                '🥩 Protein: ' . round($weight * 2.0) . 'g/ngày (40%)',
                '🍚 Carbs: ' . round($weight * 1.5) . 'g/ngày (30%)',
                '🥑 Fat: ' . round($weight * 0.4) . 'g/ngày (25%)'
            ],
            'meal_plan' => [
                '🌅 Sáng: Trứng + rau, cháo yến mạch',
                '🥗 Trưa: Súp rau + thịt gà',
                '🍌 Chiều: Quả, sữa chua không đường',
                '🍖 Tối: Rau lá xanh + cá/tôm'
            ],
            'tips' => 'Tránh: Đồ ăn nhanh, nước ngọt, dầu mỡ'
        ]
    ];
    
    $plan = $plans[$category] ?? $plans['binh_thuong'];
    $plan['general_tips'] = [
        '💧 Uống 2.5-3.5 lít nước/ngày',
        '🍽️ Ăn từ từ, nhai kỹ (20-30 phút/bữa)',
        '🔔 Ăn 4-5 bữa nhỏ/ngày',
        '⏰ Tránh ăn muộn (sau 7 giờ tối)',
        '🥬 Ăn nhiều rau, quả, thực phẩm nguyên chất'
    ];
    
    return $plan;
}

/**
 * Get service package recommendations
 */
function getServicePackageRecommendations($bmiInfo, $goal) {
    $category = $bmiInfo['category'];
    
    $packages = [
        'gay' => [
            [
                'icon' => '💎',
                'name' => 'Gói Premium Xây Dựng Cơ',
                'duration' => '3 tháng',
                'price' => '6.000.000 VNĐ',
                'includes' => [
                    '✓ HLV riêng 3 buổi/tuần',
                    '✓ Chế độ ăn cá nhân hóa',
                    '✓ Kiểm tra cơ thể hàng tuần',
                    '✓ Hỗ trợ supplement'
                ]
            ],
            [
                'icon' => '🥇',
                'name' => 'Gói VIP Tập + HLV',
                'duration' => '1 tháng',
                'price' => '2.500.000 VNĐ',
                'includes' => [
                    '✓ Tập nhóm 5 buổi/tuần',
                    '✓ HLV riêng 2 buổi',
                    '✓ Tư vấn dinh dưỡng'
                ]
            ]
        ],
        'binh_thuong' => [
            [
                'icon' => '🎯',
                'name' => 'Gói Tiêu Chuẩn',
                'duration' => '1 tháng',
                'price' => '1.500.000 VNĐ',
                'includes' => [
                    '✓ Tập nhóm không giới hạn',
                    '✓ Hỗ trợ HLV cơ bản',
                    '✓ Phòng xông hơi'
                ]
            ],
            [
                'icon' => '🥇',
                'name' => 'Gói VIP Toàn Diện',
                'duration' => '3 tháng',
                'price' => '4.500.000 VNĐ',
                'includes' => [
                    '✓ HLV riêng 2 buổi/tuần',
                    '✓ Tập nhóm không giới hạn',
                    '✓ Kiểm tra cơ thể hàng tuần'
                ]
            ]
        ],
        'thua_can' => [
            [
                'icon' => '🔥',
                'name' => 'Gói Giảm Mỡ Cao Cấp',
                'duration' => '2 tháng',
                'price' => '3.500.000 VNĐ',
                'includes' => [
                    '✓ HLV riêng 4 buổi/tuần',
                    '✓ Chế độ ăn giảm mỡ',
                    '✓ Theo dõi BMI 2 tuần/lần',
                    '✓ Support supplement'
                ]
            ],
            [
                'icon' => '🧘',
                'name' => 'Gói Cardio + Yoga',
                'duration' => '1 tháng',
                'price' => '2.000.000 VNĐ',
                'includes' => [
                    '✓ Cardio 5 buổi/tuần',
                    '✓ Yoga 2 buổi/tuần',
                    '✓ Tư vấn ăn uống'
                ]
            ]
        ],
        'beo_phi' => [
            [
                'icon' => '💎',
                'name' => 'Gói Giảm Cân Toàn Diện',
                'duration' => '3 tháng',
                'price' => '7.000.000 VNĐ',
                'includes' => [
                    '✓ HLV riêng 5 buổi/tuần',
                    '✓ Chế độ ăn cá nhân hóa',
                    '✓ Kiểm tra cơ thể hàng tuần',
                    '✓ Tư vấn y tế',
                    '✓ Hỗ trợ supplement giảm cân'
                ]
            ],
            [
                'icon' => '🎯',
                'name' => 'Gói Trải Nghiệm 1 Tháng',
                'duration' => '1 tháng',
                'price' => '1.500.000 VNĐ',
                'includes' => [
                    '✓ Tập nhóm không giới hạn',
                    '✓ HLV hướng dẫn 2 buổi',
                    '✓ Tư vấn dinh dưỡng'
                ]
            ]
        ]
    ];
    
    $recommended = $packages[$category] ?? $packages['binh_thuong'];
    
    return [
        'packages' => $recommended,
        'message' => '📦 Dựa vào BMI (' . $bmiInfo['categoryLabel'] . '), đây là gói phù hợp nhất với bạn'
    ];
}

/**
 * Handle chat messages - Simulated AI responses
 */
function handleChat() {
    $message = isset($_REQUEST['message']) ? trim($_REQUEST['message']) : '';
    $height = isset($_REQUEST['height']) ? floatval($_REQUEST['height']) : 0;
    $weight = isset($_REQUEST['weight']) ? floatval($_REQUEST['weight']) : 0;
    
    if (empty($message)) {
        echo json_encode(['error' => 'Tin nhắn rỗng']);
        return;
    }
    
    $response = generateChatResponse($message, $height, $weight);
    
    echo json_encode([
        'success' => true,
        'reply' => $response,
        'timestamp' => date('H:i')
    ]);
}

/**
 * Generate simulated AI response
 */
function generateChatResponse($message, $height, $weight) {
    $msg_lower = strtolower($message);
    
    // BMI-related questions
    if (preg_match('/bmi|chỉ số|cân nặng|chiều cao/i', $msg_lower)) {
        if ($height > 0 && $weight > 0) {
            $bmi = calculateBMI($height, $weight);
            return "📊 **Thông tin BMI của bạn:**\n\n" .
                   "BMI: **{$bmi['bmi']}** {$bmi['status']}\n" .
                   "Phân loại: **{$bmi['categoryLabel']}**\n" .
                   "Chiều cao: **{$height}cm** | Cân nặng: **{$weight}kg**\n\n" .
                   "Hãy hỏi tôi về chương trình tập hoặc chế độ ăn phù hợp! 💪";
        } else {
            return "❓ Bạn chưa cập nhật thông tin BMI.\n\n" .
                   "Vui lòng nhập:\n" .
                   "• Chiều cao (cm)\n" .
                   "• Cân nặng (kg)\n\n" .
                   "Sau đó hỏi tôi để nhận khuyến nghị! 🎯";
        }
    }
    
    // Training questions
    if (preg_match('/tập|training|ngực|chân|lưng|vai|tay/i', $msg_lower)) {
        if (preg_match('/chân|leg|squat/i', $msg_lower)) {
            return "🦵 **Bài tập chân hiệu quả:**\n\n" .
                   "**Chính:**\n" .
                   "• Squat: 4 set x 8-10 reps\n" .
                   "• Leg Press: 3 set x 10-12 reps\n" .
                   "• Leg Curl: 3 set x 12 reps\n\n" .
                   "**Phụ:**\n" .
                   "• Calf Raises: 3 set x 15 reps\n" .
                   "• Lunges: 3 set x 10 reps\n\n" .
                   "⏰ Tập 1 lần/tuần, nghỉ 48 giờ! 💪";
        } else if (preg_match('/ngực|chest|bench/i', $msg_lower)) {
            return "💪 **Bài tập ngực:**\n\n" .
                   "• Bench Press: 4 set x 8-10 reps\n" .
                   "• Incline DB Press: 3 set x 10 reps\n" .
                   "• Cable Fly: 3 set x 12 reps\n" .
                   "• Push-ups: 3 set x max reps\n\n" .
                   "Tập 1-2 lần/tuần, nghỉ 48-72 giờ! 🔥";
        } else {
            return "💪 **Để tập hiệu quả, bạn cần:**\n\n" .
                   "1. Cung cấp chiều cao & cân nặng\n" .
                   "2. Nói mục tiêu (giảm cân/tăng cơ/duy trì)\n" .
                   "3. Hỏi cụ thể về bài tập hoặc nhóm cơ\n\n" .
                   "Rồi tôi sẽ gợi ý chi tiết! 🎯";
        }
    }
    
    // Nutrition questions
    if (preg_match('/ăn|nutrition|diet|protein|carb|mỡ|calo/i', $msg_lower)) {
        if (preg_match('/protein/i', $msg_lower)) {
            return "🥩 **Nguồn Protein tốt:**\n\n" .
                   "**Cao nhất:**\n" .
                   "• Thịt gà nạc: 31g/100g\n" .
                   "• Cá hồi: 25g/100g\n" .
                   "• Trứng: 13g/1 quả\n" .
                   "• Tôm: 24g/100g\n\n" .
                   "**Vừa phải:**\n" .
                   "• Sữa chua: 3-10g/100ml\n" .
                   "• Đậu: 8-20g/100g\n\n" .
                   "Hãy cho tôi cân nặng để tính protein cần mỗi ngày! 📊";
        } else {
            return "🥗 **Để có chế độ ăn tốt:**\n\n" .
                   "Vui lòng cung cấp:\n" .
                   "• Chiều cao (cm)\n" .
                   "• Cân nặng (kg)\n" .
                   "• Tuổi\n" .
                   "• Mục tiêu (tăng/giảm/duy trì)\n\n" .
                   "Tôi sẽ tính chi tiết calo & macro cho bạn! 🎯";
        }
    }
    
    // Package/Service questions
    if (preg_match('/gói|package|membership|dịch vụ|đăng ký/i', $msg_lower)) {
        return "📦 **Các gói dịch vụ của chúng tôi:**\n\n" .
               "💎 **Premium:** HLV riêng + chế độ ăn\n" .
               "   💰 6M - 7M VNĐ (3 tháng)\n\n" .
               "🥇 **VIP:** HLV + Tập nhóm + Tư vấn\n" .
               "   💰 4.5M - 5.5M VNĐ (3 tháng)\n\n" .
               "🎯 **Tiêu Chuẩn:** Tập nhóm + Hỗ trợ cơ bản\n" .
               "   💰 1.5M - 2.5M VNĐ (1 tháng)\n\n" .
               "Hãy cung cấp BMI để tôi gợi ý gói phù hợp! 🎁";
    }
    
    // BMI category specific advice
    if (preg_match('/gầy|giảm cân|béo|thừa cân/i', $msg_lower)) {
        if ($height > 0 && $weight > 0) {
            $bmi = calculateBMI($height, $weight);
            
            if ($bmi['category'] === 'gay') {
                return "💪 **Bạn gầy - Cần TĂNG CƠ BẮP:**\n\n" .
                       "**Tập:**\n" .
                       "• 4-5 buổi/tuần\n" .
                       "• Tập nặng (Squat, Bench, Deadlift)\n" .
                       "• Cardio nhẹ 2 lần/tuần\n\n" .
                       "**Ăn:**\n" .
                       "• Thặng dư 300-500 calo/ngày\n" .
                       "• Protein cao: {$weight}*2 = " . ($weight * 2) . "g/ngày\n" .
                       "• Ăn 5 bữa/ngày\n\n" .
                       "⏱️ Kỳ vọng: 2-3 tháng để thấy kết quả! 🎯";
            } elseif ($bmi['category'] === 'thua_can') {
                return "🔥 **Bạn thừa cân - Cần GIẢM MỠ:**\n\n" .
                       "**Tập:**\n" .
                       "• 4-5 buổi/tuần\n" .
                       "• Cardio 4-5 lần/tuần\n" .
                       "• HIIT 2-3 lần/tuần\n" .
                       "• Tập lực 2-3 lần\n\n" .
                       "**Ăn:**\n" .
                       "• Thâm hụt 300-500 calo/ngày\n" .
                       "• Protein: " . ($weight * 1.8) . "g/ngày\n" .
                       "• Tránh đồ ăn nhanh\n\n" .
                       "⏱️ Kỳ vọng: 1-2kg/tuần! 💪";
            } elseif ($bmi['category'] === 'beo_phi') {
                return "🎯 **Bạn béo phì - CẦN GIẢM CÂN TOÀN DIỆN:**\n\n" .
                       "**Tập:**\n" .
                       "• 5 buổi/tuần\n" .
                       "• Cardio (bộ, bơi, xe đạp)\n" .
                       "• HIIT 2 lần/tuần\n" .
                       "• Tập lực nhẹ 2-3 lần\n\n" .
                       "**Ăn:**\n" .
                       "• Thâm hụt 500-750 calo/ngày\n" .
                       "• Protein: " . ($weight * 2) . "g/ngày\n" .
                       "• Uống 4-5 lít nước/ngày\n\n" .
                       "⚠️ Nên tư vấn bác sĩ trước! 👨‍⚕️";
            } else {
                return "✅ **Bạn bình thường - DUY TRÌ SỨC KHỎE:**\n\n" .
                       "**Tập:**\n" .
                       "• 3-4 buổi/tuần\n" .
                       "• Cardio + Tập lực + Yoga\n" .
                       "• Kết hợp đẹp cân đối\n\n" .
                       "**Ăn:**\n" .
                       "• Cân bằng calo\n" .
                       "• Protein: " . ($weight * 1.6) . "g/ngày\n" .
                       "• Ăn đa dạng, cân bằng\n\n" .
                       "💡 Tiếp tục duy trì thói quen tốt! 👍";
            }
        } else {
            return "❓ Bạn chưa cập nhật thông tin BMI.\n\n" .
                   "Vui lòng nhập chiều cao & cân nặng\n" .
                   "để tôi phân tích tình trạng của bạn! 📊";
        }
    }
    
    // Default welcome response
    if (preg_match('/hello|hi|xin chào|mày là ai|bạn là ai/i', $msg_lower)) {
        return "👋 **Xin chào! Tôi là AI Gym Assistant** 🤖\n\n" .
               "Tôi có thể giúp bạn:\n" .
               "✨ Gợi ý **chương trình tập luyện**\n" .
               "🥗 Tư vấn **chế độ ăn** cá nhân hóa\n" .
               "💪 Phân tích **BMI & thể trạng**\n" .
               "🎯 Đề xuất **gói dịch vụ** phù hợp\n\n" .
               "Hãy bắt đầu bằng cách nhập:\n" .
               "• Chiều cao (cm)\n" .
               "• Cân nặng (kg)\n\n" .
               "Rồi hỏi tôi bất cứ điều gì! 😊";
    }
    
    // Default helpful response
    return "😊 **Tôi không hiểu rõ câu hỏi của bạn.**\n\n" .
           "Bạn có thể hỏi về:\n" .
           "💪 **Tập luyện** - Bài tập, chương trình, số buổi\n" .
           "🥗 **Dinh dưỡng** - Chế độ ăn, calo, protein\n" .
           "📊 **BMI** - Phân tích cân nặng & chiều cao\n" .
           "🎁 **Gói dịch vụ** - Membership, giá cả\n\n" .
           "Hoặc nhập chiều cao & cân nặng để bắt đầu! 📏⚖️";
}

?>
