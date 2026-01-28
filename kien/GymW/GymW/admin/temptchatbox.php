<?php
/**
 * AI Chatbox Page for Gym Management System
 */
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym AI Assistant - Cố vấn tập luyện & dinh dưỡng</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 0;
        }

        .main-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .info-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .info-panel h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section h3 {
            color: #764ba2;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            border-left: 4px solid #667eea;
            padding-left: 10px;
        }

        .info-section p, .info-section li {
            color: #555;
            line-height: 1.8;
            font-size: 14px;
        }

        .info-section ul {
            margin-left: 20px;
            list-style: none;
        }

        .info-section ul li {
            margin-bottom: 8px;
        }

        .info-section ul li:before {
            content: "✓ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 8px;
        }

        .feature-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .feature-box h4 {
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feature-box p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        /* Chatbot Container */
        .chatbot-wrapper {
            position: sticky;
            top: 20px;
        }

        .chatbot-container {
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 750px;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid rgba(255, 255, 255, 0.1);
        }

        .chatbot-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 700;
        }

        .chatbot-header p {
            font-size: 12px;
            opacity: 0.9;
            margin: 0;
        }

        .bmi-input-section {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .bmi-input-section h4 {
            font-size: 12px;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-size: 11px;
            color: #495057;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .input-group input,
        .input-group select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .get-rec-btn {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
        }

        .get-rec-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .chat-area {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: white;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            display: flex;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 85%;
            padding: 10px 13px;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 3px;
        }

        .bot .message-content {
            background: #f0f2f5;
            color: #333;
            border-bottom-left-radius: 3px;
        }

        .message-content strong {
            color: inherit;
        }

        .bot .message-content strong {
            color: #667eea;
        }

        .message-time {
            font-size: 10px;
            color: #999;
            margin-top: 3px;
            padding: 0 5px;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 10px 13px;
            background: #f0f2f5;
            border-radius: 12px;
            width: fit-content;
        }

        .typing-indicator span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #999;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-6px);
                opacity: 1;
            }
        }

        .input-area {
            padding: 12px;
            border-top: 1px solid #e9ecef;
            background: white;
            display: flex;
            gap: 8px;
        }

        .input-area input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .input-area input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .chat-area::-webkit-scrollbar {
            width: 5px;
        }

        .chat-area::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-area::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 3px;
        }

        .chat-area::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .chatbot-wrapper {
                position: relative;
                top: 0;
            }

            .chatbot-container {
                height: 600px;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 28px;
            }

            .info-panel {
                padding: 20px;
            }

            .chatbot-container {
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <h1>🤖 Gym AI Assistant</h1>
        <p>Cố vấn tập luyện & dinh dưỡng thông minh, gợi ý gói dịch vụ dựa trên BMI</p>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Information Panel -->
        <div class="info-panel">
            <h2><i class="fas fa-lightbulb"></i> Hướng dẫn sử dụng</h2>

            <div class="info-section">
                <h3><i class="fas fa-book"></i> Về AI Assistant</h3>
                <p>Trợ lý AI của chúng tôi được thiết kế để giúp bạn đạt được mục tiêu fitness một cách hiệu quả. Hệ thống phân tích BMI của bạn và đưa ra các gợi ý cá nhân hóa về:</p>
                <ul>
                    <li>Chương trình tập luyện phù hợp</li>
                    <li>Chế độ dinh dưỡng cân bằng</li>
                    <li>Gói dịch vụ tối ưu</li>
                </ul>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-play-circle"></i> Cách bắt đầu</h3>
                <div class="feature-box">
                    <h4>Bước 1: Nhập thông tin cơ bản</h4>
                    <p>Điền chiều cao (cm), cân nặng (kg), tuổi và giới tính vào ô bên cạnh</p>
                </div>
                <div class="feature-box">
                    <h4>Bước 2: Nhận gợi ý</h4>
                    <p>Nhấn nút "Nhận gợi ý" để AI phân tích BMI và đưa ra khuyến nghị chi tiết</p>
                </div>
                <div class="feature-box">
                    <h4>Bước 3: Chat với AI</h4>
                    <p>Hỏi câu hỏi cụ thể về tập luyện, dinh dưỡng hoặc gói dịch vụ. AI sẽ trả lời dựa trên thông tin của bạn</p>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-star"></i> Tính năng chính</h3>
                <div class="feature-box">
                    <h4>💪 Gợi ý tập luyện</h4>
                    <p>Nhận chương trình tập chi tiết dựa trên BMI: tần suất, cường độ, các bài tập phù hợp</p>
                </div>
                <div class="feature-box">
                    <h4>🥗 Tư vấn dinh dưỡng</h4>
                    <p>Chế độ ăn cá nhân hóa với lượng calo, protein, carbs và fat cần thiết mỗi ngày</p>
                </div>
                <div class="feature-box">
                    <h4>📊 Phân tích BMI</h4>
                    <p>Xác định phân loại thể trạng (gầy, bình thường, thừa cân, béo phì) và tình trạng sức khỏe</p>
                </div>
                <div class="feature-box">
                    <h4>🎁 Đề xuất gói dịch vụ</h4>
                    <p>Gợi ý gói membership phù hợp nhất với mục tiêu và tình trạng hiện tại của bạn</p>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-comment"></i> Ví dụ câu hỏi</h3>
                <p><strong>Hỏi về tập luyện:</strong></p>
                <ul>
                    <li>"Tôi nên tập bao nhiêu buổi một tuần?"</li>
                    <li>"Làm thế nào để giảm cân hiệu quả?"</li>
                    <li>"Bài tập chân nào tốt nhất?"</li>
                </ul>
                <p style="margin-top: 12px;"><strong>Hỏi về dinh dưỡng:</strong></p>
                <ul>
                    <li>"Tôi cần bao nhiêu protein mỗi ngày?"</li>
                    <li>"Nên ăn gì để tăng cơ bắp?"</li>
                    <li>"Chế độ ăn như thế nào?"</li>
                </ul>
                <p style="margin-top: 12px;"><strong>Hỏi về gói dịch vụ:</strong></p>
                <ul>
                    <li>"Gói nào phù hợp với tôi?"</li>
                    <li>"Các gói dịch vụ là gì?"</li>
                    <li>"Giá bao nhiêu?"</li>
                </ul>
            </div>
        </div>

        <!-- Chatbot Container -->
        <div class="chatbot-wrapper">
            <div class="chatbot-container">
                <!-- Header -->
                <div class="chatbot-header">
                    <h2>🤖 Gym AI Assistant</h2>
                    <p>Cố vấn tập luyện & dinh dưỡng thông minh</p>
                </div>

                <!-- BMI Input Section -->
                <div class="bmi-input-section">
                    <h4>📊 Thông tin của bạn</h4>
                    <div class="input-row">
                        <div class="input-group">
                            <label>Chiều cao (cm)</label>
                            <input type="number" id="heightInput" placeholder="170" min="100" max="250">
                        </div>
                        <div class="input-group">
                            <label>Cân nặng (kg)</label>
                            <input type="number" id="weightInput" placeholder="70" min="20" max="200">
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group">
                            <label>Tuổi</label>
                            <input type="number" id="ageInput" placeholder="25" min="15" max="100">
                        </div>
                        <div class="input-group">
                            <label>Giới tính</label>
                            <select id="genderInput">
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                            </select>
                        </div>
                    </div>
                    <button class="get-rec-btn" onclick="getRecommendation()">📋 Nhận gợi ý</button>
                </div>

                <!-- Chat Area -->
                <div class="chat-area" id="chatArea">
                    <div class="message bot">
                        <div>
                            <div class="message-content">
                                👋 <strong>Xin chào!</strong> Tôi là AI Gym Assistant<br><br>
                                ✨ Gợi ý chương trình tập<br>
                                🥗 Tư vấn chế độ ăn<br>
                                💪 Phân tích BMI<br>
                                🎯 Đề xuất gói dịch vụ<br><br>
                                Nhập chiều cao & cân nặng rồi hỏi tôi! 😊
                            </div>
                            <div class="message-time">Vừa xong</div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="input-area">
                    <input type="text" id="messageInput" placeholder="Hỏi về tập, ăn, gói dịch vụ..." 
                           onkeypress="if(event.key==='Enter') sendMessage()">
                    <button class="send-btn" onclick="sendMessage()">Gửi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let userProfile = {
            height: 0,
            weight: 0,
            age: 25,
            gender: 'male'
        };

        function getRecommendation() {
            const height = parseFloat(document.getElementById('heightInput').value);
            const weight = parseFloat(document.getElementById('weightInput').value);
            const age = parseInt(document.getElementById('ageInput').value) || 25;
            const gender = document.getElementById('genderInput').value;

            if (!height || !weight || height < 100 || height > 250 || weight < 20 || weight > 200) {
                alert('Vui lòng nhập chiều cao (100-250cm) và cân nặng (20-200kg) hợp lệ!');
                return;
            }

            userProfile = { height, weight, age, gender };

            const chatArea = document.getElementById('chatArea');
            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'message bot';
            loadingMsg.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            chatArea.appendChild(loadingMsg);
            chatArea.scrollTop = chatArea.scrollHeight;

            fetch('ai_chatbot.php?action=get_recommendation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    height: height,
                    weight: weight,
                    age: age,
                    gender: gender,
                    goal: 'maintain'
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingMsg.remove();
                if (data.success) {
                    displayRecommendation(data);
                } else {
                    addBotMessage('❌ Có lỗi xảy ra. Vui lòng thử lại!');
                }
            })
            .catch(error => {
                loadingMsg.remove();
                addBotMessage('❌ Không thể kết nối server!');
                console.error('Error:', error);
            });
        }

        function displayRecommendation(data) {
            const bmi = data.bmi_info;
            const training = data.recommendations.training;
            const nutrition = data.recommendations.nutrition;
            const packages = data.recommendations.packages;

            let message = `📊 <strong>Phân tích BMI của bạn:</strong>\n\n`;
            message += `<strong>BMI: ${bmi.bmi}</strong> ${bmi.status}\n`;
            message += `Thể trạng: <strong>${bmi.categoryLabel}</strong>\n`;
            message += `H: ${data.profile.height}cm | C: ${data.profile.weight}kg\n\n`;
            
            message += `<strong>💪 ${training.title}</strong>\n`;
            message += `Tần suất: ${training.frequency}\n`;
            message += `Cấp độ: ${training.intensity}\n`;
            message += `Calorie: ${training.calories}\n\n`;

            message += `<strong>🥗 ${nutrition.title}</strong>\n`;
            message += `${nutrition.daily_calories}\n`;
            message += nutrition.macros.join('\n') + '\n\n';

            message += `<strong>🎁 ${packages.message}</strong>`;

            addBotMessage(message);
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (!message) return;

            addUserMessage(message);
            input.value = '';

            const chatArea = document.getElementById('chatArea');
            const typingMsg = document.createElement('div');
            typingMsg.className = 'message bot';
            typingMsg.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            chatArea.appendChild(typingMsg);
            chatArea.scrollTop = chatArea.scrollHeight;

            fetch('ai_chatbot.php?action=chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    message: message,
                    height: userProfile.height,
                    weight: userProfile.weight
                })
            })
            .then(response => response.json())
            .then(data => {
                typingMsg.remove();
                if (data.success) {
                    addBotMessage(data.reply, data.timestamp);
                } else {
                    addBotMessage('❌ Có lỗi!');
                }
            })
            .catch(error => {
                typingMsg.remove();
                addBotMessage('❌ Không thể kết nối server!');
            });
        }

        function addUserMessage(text) {
            const chatArea = document.getElementById('chatArea');
            const msgEl = document.createElement('div');
            msgEl.className = 'message user';
            msgEl.innerHTML = `
                <div>
                    <div class="message-content">${text}</div>
                    <div class="message-time">${new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            `;
            chatArea.appendChild(msgEl);
            chatArea.scrollTop = chatArea.scrollHeight;
        }

        function addBotMessage(text, time = null) {
            const chatArea = document.getElementById('chatArea');
            const msgEl = document.createElement('div');
            msgEl.className = 'message bot';
            
            const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                       .replace(/\n/g, '<br>');
            
            msgEl.innerHTML = `
                <div>
                    <div class="message-content">${formattedText}</div>
                    <div class="message-time">${time || 'Vừa xong'}</div>
                </div>
            `;
            chatArea.appendChild(msgEl);
            chatArea.scrollTop = chatArea.scrollHeight;
        }
    </script>
</body>
</html>
