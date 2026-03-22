(function () {
    var root = document.getElementById('faqBot');
    if (!root) return;

    var toggle = document.getElementById('faqBotToggle');
    var panel = document.getElementById('faqBotPanel');
    var closeBtn = document.getElementById('faqBotClose');
    var messages = document.getElementById('faqBotMessages');
    var quick = document.getElementById('faqBotQuick');
    var form = document.getElementById('faqBotForm');
    var input = document.getElementById('faqBotInput');

    var faqData = [
        {
            q: 'Giờ mở cửa phòng gym?',
            a: 'Phòng gym mở cửa từ 5:00 đến 22:00 mỗi ngày, kể cả cuối tuần và ngày lễ.',
            keywords: ['gio mo cua', 'mo cua', 'dong cua', 'giờ mở cửa', 'mở cửa', 'đóng cửa', 'lịch hoạt động']
        },
        {
            q: 'Phòng tập có mở cửa ngày lễ không?',
            a: 'Có. Phòng tập vẫn mở cửa ngày lễ, tuy nhiên một số khung giờ lớp nhóm có thể điều chỉnh theo thông báo.',
            keywords: ['ngày lễ', 'tet', 'tết', 'lễ', 'holiday']
        },
        {
            q: 'Có gói tập theo tháng không?',
            a: 'Bên mình có các gói 1 tháng, 3 tháng, 6 tháng và 12 tháng. Bạn xem tại trang Gói tập để chọn ưu đãi phù hợp.',
            keywords: ['goi tap', 'gói tập', 'tháng', 'gia goi', 'giá gói', 'membership']
        },
        {
            q: 'Gói nào phù hợp cho người mới bắt đầu?',
            a: 'Nếu bạn mới tập, nên bắt đầu với gói 1 hoặc 3 tháng để làm quen cường độ, sau đó nâng lên gói dài hạn để tiết kiệm chi phí.',
            keywords: ['người mới', 'mới tập', 'beginner', 'phù hợp', 'gói nào']
        },
        {
            q: 'Có ưu đãi cho học sinh, sinh viên không?',
            a: 'Hệ thống thường có ưu đãi theo từng đợt. Bạn theo dõi mục Khuyến mãi hoặc liên hệ quầy để được áp dụng đúng chương trình hiện tại.',
            keywords: ['sinh viên', 'học sinh', 'student', 'ưu đãi', 'khuyến mãi']
        },
        {
            q: 'Cách đăng ký lịch PT 1-1?',
            a: 'Bạn vào mục Lịch tập với PT, chọn huấn luyện viên và khung giờ mong muốn rồi xác nhận đặt lịch.',
            keywords: ['pt', 'huấn luyện viên', 'lich pt', 'lịch pt', '1-1', 'đặt lịch']
        },
        {
            q: 'Chi phí thuê PT tính như thế nào?',
            a: 'Chi phí PT phụ thuộc vào số buổi và cấp độ huấn luyện viên. Bạn vào trang Lịch tập với PT để xem mức giá cụ thể trước khi đặt.',
            keywords: ['chi phí pt', 'giá pt', 'thuê pt', 'pt price']
        },
        {
            q: 'Có lớp yoga hoặc cardio không?',
            a: 'Có. Bạn có thể xem danh sách lớp trong mục Lớp tập để chọn yoga, cardio và các lớp nhóm khác theo lịch.',
            keywords: ['yoga', 'cardio', 'lớp nhóm', 'classes', 'lớp tập']
        },
        {
            q: 'Làm sao để đăng ký lớp nhóm?',
            a: 'Bạn vào trang Lớp tập, chọn lớp còn chỗ và nhấn đăng ký. Nếu đã đăng nhập, hệ thống sẽ lưu lịch vào tài khoản của bạn.',
            keywords: ['đăng ký lớp', 'lớp nhóm', 'class register', 'ghi danh']
        },
        {
            q: 'Có thể tập thử trước khi mua gói không?',
            a: 'Có hỗ trợ buổi tập thử theo chương trình từng thời điểm. Bạn vui lòng liên hệ trang Liên hệ để được tư vấn lịch trải nghiệm gần nhất.',
            keywords: ['tập thử', 'trial', 'dùng thử', 'trải nghiệm']
        },
        {
            q: 'Có thể thanh toán online không?',
            a: 'Có. Bạn có thể thêm sản phẩm hoặc gói tập vào giỏ hàng và thanh toán trực tiếp tại trang Checkout.',
            keywords: ['thanh toán', 'online', 'checkout', 'chuyển khoản', 'quét mã']
        },
        {
            q: 'Có thanh toán khi đến quầy được không?',
            a: 'Được. Bạn có thể đăng ký online trước, sau đó hoàn tất thanh toán trực tiếp tại quầy lễ tân.',
            keywords: ['tiền mặt', 'tại quầy', 'counter', 'cash', 'thanh toán trực tiếp']
        },
        {
            q: 'Thanh toán lỗi thì làm sao?',
            a: 'Nếu thanh toán lỗi hoặc treo đơn, bạn chụp màn hình giao dịch và gửi qua trang Liên hệ để đội ngũ hỗ trợ kiểm tra nhanh.',
            keywords: ['lỗi thanh toán', 'thanh toán lỗi', 'fail payment', 'treo đơn']
        },
        {
            q: 'Làm sao xem lịch sử đơn hàng?',
            a: 'Sau khi đăng nhập, bạn vào mục Lịch sử mua hàng trong menu tài khoản để xem chi tiết đơn hàng.',
            keywords: ['lịch sử', 'đơn hàng', 'order', 'mua hàng', 'hóa đơn']
        },
        {
            q: 'Làm sao cập nhật thông tin cá nhân?',
            a: 'Bạn vào mục Thông tin cá nhân trong menu tài khoản để cập nhật số điện thoại, địa chỉ và thông tin cơ bản.',
            keywords: ['cập nhật hồ sơ', 'thông tin cá nhân', 'profile', 'đổi số điện thoại']
        },
        {
            q: 'Quên mật khẩu phải làm sao?',
            a: 'Bạn vào trang Đăng nhập và chọn chức năng quên mật khẩu (nếu có), hoặc liên hệ quản trị viên để được hỗ trợ đặt lại nhanh.',
            keywords: ['quên mật khẩu', 'reset password', 'đặt lại mật khẩu', 'không đăng nhập được']
        },
        {
            q: 'Có thể hủy gói hoặc hoàn tiền không?',
            a: 'Việc hủy gói và hoàn tiền áp dụng theo chính sách từng loại gói. Bạn gửi yêu cầu qua trang Liên hệ để bộ phận hỗ trợ kiểm tra trường hợp cụ thể.',
            keywords: ['hủy gói', 'hoàn tiền', 'refund', 'cancel']
        },
        {
            q: 'Tôi muốn tạm ngưng gói tập được không?',
            a: 'Bạn có thể gửi yêu cầu tạm ngưng trong một số trường hợp đặc biệt. Vui lòng liên hệ để được hướng dẫn hồ sơ và thời gian bảo lưu.',
            keywords: ['bảo lưu', 'tạm ngưng', 'freeze membership', 'ngưng gói']
        },
        {
            q: 'Có bán thực phẩm bổ sung không?',
            a: 'Có. Bạn có thể mua thực phẩm bổ sung và phụ kiện tập luyện tại mục Sản phẩm trên website.',
            keywords: ['thực phẩm bổ sung', 'supplement', 'whey', 'sản phẩm']
        },
        {
            q: 'Làm sao áp mã khuyến mãi?',
            a: 'Ở trang Giỏ hàng, bạn chọn ưu đãi khả dụng hoặc mã khuyến mãi rồi hệ thống sẽ tự tính lại tổng tiền.',
            keywords: ['mã giảm giá', 'khuyến mãi', 'voucher', 'promotion']
        },
        {
            q: 'Có chỗ gửi xe không?',
            a: 'Có khu vực gửi xe cho hội viên. Bạn nên đến sớm 10-15 phút vào giờ cao điểm để thuận tiện hơn.',
            keywords: ['gửi xe', 'bãi xe', 'parking']
        },
        {
            q: 'Có phòng tắm và tủ đồ không?',
            a: 'Phòng tập có khu vực thay đồ, tủ đồ và phòng tắm cơ bản để hội viên sử dụng trước và sau buổi tập.',
            keywords: ['phòng tắm', 'locker', 'tủ đồ', 'thay đồ']
        },
        {
            q: 'Có hướng dẫn lịch tập cho người mới không?',
            a: 'Có. Bạn có thể đặt lịch với PT hoặc nhờ tư vấn viên hỗ trợ lộ trình tập phù hợp mục tiêu giảm mỡ, tăng cơ hoặc cải thiện sức bền.',
            keywords: ['lịch tập', 'giáo án', 'người mới', 'tư vấn tập luyện']
        },
        {
            q: 'Có tư vấn dinh dưỡng không?',
            a: 'Có hỗ trợ tư vấn dinh dưỡng cơ bản và gợi ý thực đơn theo mục tiêu. Bạn có thể theo dõi thêm trong các gói dịch vụ phù hợp.',
            keywords: ['dinh dưỡng', 'nutrition', 'thực đơn', 'ăn kiêng']
        },
        {
            q: 'Có đo chỉ số cơ thể không?',
            a: 'Bạn có thể dùng trang Tính BMI trên website để kiểm tra nhanh, và liên hệ PT để được đánh giá chi tiết hơn tại phòng tập.',
            keywords: ['bmi', 'đo chỉ số', 'inbody', 'chỉ số cơ thể']
        },
        {
            q: 'Liên hệ hỗ trợ nhanh bằng cách nào?',
            a: 'Bạn vào trang Liên hệ để gửi yêu cầu hoặc để lại thông tin. Đội ngũ hỗ trợ sẽ phản hồi sớm nhất có thể.',
            keywords: ['liên hệ', 'hỗ trợ', 'support', 'hotline']
        }
    ];

    function normalizeText(value) {
        return (value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function appendMessage(type, text) {
        var wrapper = document.createElement('div');
        wrapper.className = 'faq-bot-msg ' + type;

        var bubble = document.createElement('div');
        bubble.className = 'faq-bot-bubble';
        bubble.textContent = text;

        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        messages.scrollTop = messages.scrollHeight;
    }

    function findAnswer(question) {
        var normalized = normalizeText(question);
        if (!normalized) return null;

        for (var i = 0; i < faqData.length; i++) {
            var item = faqData[i];
            var directQuestion = normalizeText(item.q);
            if (normalized.indexOf(directQuestion) !== -1 || directQuestion.indexOf(normalized) !== -1) {
                return item.a;
            }

            for (var j = 0; j < item.keywords.length; j++) {
                if (normalized.indexOf(normalizeText(item.keywords[j])) !== -1) {
                    return item.a;
                }
            }
        }

        return null;
    }

    function ask(question) {
        var text = (question || '').trim();
        if (!text) return;

        appendMessage('user', text);

        var answer = findAnswer(text);
        if (!answer) {
            answer = 'Mình chưa có thông tin chính xác cho câu hỏi này. Bạn có thể để lại lời nhắn ở trang Liên hệ để đội ngũ hỗ trợ chi tiết hơn.';
        }

        window.setTimeout(function () {
            appendMessage('bot', answer);
        }, 220);
    }

    function buildQuickQuestions() {
        quick.innerHTML = '';
        for (var i = 0; i < faqData.length && i < 4; i++) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'faq-bot-chip';
            button.textContent = faqData[i].q;
            button.setAttribute('data-question', faqData[i].q);
            quick.appendChild(button);
        }
    }

    function openBot() {
        root.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        input.focus();
    }

    function closeBot() {
        root.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
    }

    buildQuickQuestions();
    appendMessage('bot', 'Xin chào, mình là Trương Trung Kiên. Bạn cần tư vấn gì về phòng gym?');

    toggle.addEventListener('click', function () {
        if (root.classList.contains('is-open')) {
            closeBot();
        } else {
            openBot();
        }
    });

    closeBtn.addEventListener('click', closeBot);

    document.addEventListener('click', function (event) {
        if (!root.classList.contains('is-open')) return;
        if (!event.target.closest('#faqBot')) {
            closeBot();
        }
    });

    quick.addEventListener('click', function (event) {
        var target = event.target;
        if (!target.classList.contains('faq-bot-chip')) return;
        ask(target.getAttribute('data-question'));
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var q = input.value;
        ask(q);
        input.value = '';
        input.focus();
    });
})();