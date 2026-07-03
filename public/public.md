<?php

/**
 * ============================================================================
 * ENTRY POINT - Điểm khởi đầu của ứng dụng web
 * ============================================================================
 * File này là router chính của ứng dụng, xử lý:
 * 1. Khởi tạo session và environment
 * 2. Parse URL để trích xuất controller và method
 * 3. Tự động load các class cần thiết
 * 4. Gọi controller và method tương ứng
 */

// lOcation: public/index.php

session_start();

// ========== LOAD ENVIRONMENT VARIABLES ==========
// Require file Env.php để có sẵn các phương thức load biến môi trường
require_once __DIR__ . '/../app/Core/Env.php';

Env::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/Core/Bootstrap.php';



$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';


$host = $_SERVER['HTTP_HOST'] ?? 'localhost';


$script = dirname($_SERVER['SCRIPT_NAME']);


$base_url = $protocol . '://' . $host . ($script === '\\' || $script === '/' ? '' : $script);

define('BASE_URL', rtrim($base_url, '/'));



$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// Vệ sinh dữ liệu URL để tránh các kiểu tấn công
$url = filter_var($url, FILTER_SANITIZE_URL);

$url = explode('/', $url);

// ========== TRÍCH XUẤT CONTROLLER VÀ METHOD TỪ URL ==========
// Quy ước routing:
// - Phần tử [0]: tên controller (ví dụ: "product" => "ProductController")
// - Phần tử [1]: tên method (ví dụ: "detail" => detail())
// - Phần tử [2+]: các tham số (ví dụ: "5" => $id = 5)

// Lấy tên controller từ phần tử đầu tiên của URL
// Nếu URL trống hoặc không có controller, mặc định là "HomeController"
// ucfirst() viết hoa ký tự đầu tiên (product => Product)
// Cộng thêm "Controller" để tạo tên class (Product => ProductController)
$controllerName = isset($url[0]) && $url[0] != '' ? ucfirst($url[0]) . 'Controller' : 'HomeController';

$controllerName = str_replace('-', '_', $controllerName);
// VALIDATE controller name: chỉ cho phép chữ cái, số, dấu gạch dưới
$controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);

// Lấy tên method từ phần tử thứ hai của URL
$methodName = isset($url[1]) && $url[1] != '' ? $url[1] : 'index';

$methodName = str_replace('-', '_', $methodName);

// VALIDATE method name: chỉ cho phép chữ cái, số, dấu gạch dưới
$methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);

// ========== AUTOLOADER - TỰ ĐỘNG LOAD CLASS ==========
// spl_autoload_register() đăng ký một hàm để tự động load class
// Khi dùng "new ProductController()", PHP sẽ tự động gọi hàm này
// Tránh phải require_once từng class một
spl_autoload_register(function ($class) {

    // Kiểm tra này tránh việc load lặp lại
    if (class_exists($class)) {
        return;
    }

    // Định nghĩa các thư mục nơi class có thể nằm
    // Thứ tự tìm kiếm: Controllers => Core => Models
    $paths = [
        __DIR__ . '/../app/Controllers/' . $class . '.php',    // Controller classes
        __DIR__ . '/../app/Core/' . $class . '.php',           // Core/Library classes
        __DIR__ . '/../app/Models/' . $class . '.php'          // Model/Database classes
    ];

    // Duyệt từng đường dẫn để tìm file chứa class
    foreach ($paths as $path) {

        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});


try {
    // ========== Kiểm tra Controller có tồn tại ==========
    if (!class_exists($controllerName)) {

        error_log("Controller not found: $controllerName");
        http_response_code(404);
        echo "Lỗi 404: Controller '$controllerName' không tồn tại.";
        exit;
    }

    // ========== Khởi tạo instance của Controller ==========
    $controller = new $controllerName();

    // ========== Kiểm tra Method có tồn tại trong Controller ==========
    if (!method_exists($controller, $methodName)) {
        error_log("Method not found: $methodName in $controllerName");
        http_response_code(404);
        echo "Lỗi 404: Method '$methodName' không tồn tại trong '$controllerName'.";
        exit;
    }

    // ========== EXTRACT CÁC THAM SỐ TỪ URL ==========
    // Các phần tử [0] và [1] của $url là controller và method
    // Các phần tử còn lại [2, 3, 4...] là các tham số
    unset($url[0]);
    unset($url[1]);

    // Giữ lại các phần tử còn lại làm các tham số truyền vào method
    $params = $url ? array_values($url) : [];

    // ========== GỌI CONTROLLER METHOD VỚI CÁC THAM SỐ ==========
    call_user_func_array([$controller, $methodName], $params);
} catch (Exception $e) {
    // ========== XỬ LÝ EXCEPTION ==========
    error_log($e->getMessage());
    http_response_code(500);
    echo "Lỗi 500: Đã xảy ra lỗi trên server. Vui lòng thử lại sau.";
    exit;
}
.htaccess in public folder
Options -Indexes 

php_value upload_max_filesize 512M
php_value post_max_size 600M
php_value max_execution_time 1200
php_value max_input_time 1200
php_value memory_limit 768M

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

public/assets/css,images,js,uploads,vendor,videos

/*
 * File: assets/js/main.js
 * Chức năng: Xử lý JavaScript dùng chung cho website.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 80
        });
    }

    var navbar = document.querySelector('.navbar');
    if (navbar) {
        var updateNavbar = function () {
            var scrolled = window.scrollY > 8;
            navbar.classList.toggle('shadow', scrolled);
            navbar.classList.toggle('navbar-scrolled', scrolled);
        };

        updateNavbar();
        window.addEventListener('scroll', updateNavbar);
    }

    var chatToggle = document.getElementById('faqChatToggle');
    var chatPanel = document.getElementById('faqChatPanel');
    var chatClose = document.getElementById('faqChatClose');
    var chatForm = document.getElementById('faqChatForm');
    var chatMessages = document.getElementById('faqChatMessages');
    var chatInput = document.getElementById('faqChatInput');

    if (chatToggle && chatPanel && chatClose && chatForm && chatMessages && chatInput) {
        chatToggle.addEventListener('click', function () {
            chatPanel.hidden = false;
            chatInput.focus();
        });

        chatClose.addEventListener('click', function () {
            chatPanel.hidden = true;
        });

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var text = chatInput.value.trim();
            if (!text) {
                return;
            }

            appendChatMessage(text, 'user-message');
            chatInput.value = '';
            chatInput.disabled = true;
            appendChatMessage('Đang gửi...', 'bot-message', true);

            fetch('http://127.0.0.1:1884/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ question: text })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    removeTemporaryMessage();
                    var reply = getChatReplyText(data);
                    appendChatMessage(reply || 'Xin lỗi, hiện không trả lời được. Vui lòng thử lại sau.', 'bot-message');
                })
                .catch(function () {
                    removeTemporaryMessage();
                    appendChatMessage('Lỗi kết nối tới chatbot. Vui lòng kiểm tra server RAG.', 'bot-message');
                })
                .finally(function () {
                    chatInput.disabled = false;
                    chatInput.focus();
                });
        });
    }

    initHlsVideos();
    initFaqInteractions();
});

function initHlsVideos() {
    var videos = Array.prototype.slice.call(document.querySelectorAll('video[data-hls-src]'));

    videos.forEach(function (video) {
        var source = video.dataset.hlsSrc;
        if (!source) {
            return;
        }

        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = source;
            video.play().catch(function () {});
            return;
        }

        if (window.Hls && window.Hls.isSupported()) {
            var hls = new window.Hls();
            hls.loadSource(source);
            hls.attachMedia(video);
            hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                video.play().catch(function () {});
            });
        }
    });
}

function appendChatMessage(text, className, temporary) {
    var message = document.createElement('div');
    message.className = 'faq-chat-message ' + className;
    message.innerHTML = formatText(text);
    if (temporary) {
        message.dataset.temp = 'true';
        message.style.opacity = '0.7';
    }
    var chatMessages = document.getElementById('faqChatMessages');
    chatMessages.appendChild(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function getChatReplyText(data) {
    if (!data || !data.response) {
        return '';
    }

    if (typeof data.response === 'string') {
        return data.response;
    }

    if (Array.isArray(data.response) && data.response.length > 0) {
        if (typeof data.response[0] === 'string') {
            return data.response.join('');
        }

        if (data.response[0].type === 'text' && data.response[0].text) {
            return data.response[0].text;
        }
    }

    return '';
}

function formatText(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/^\* /gm, '- ');
    return text;
}

function removeTemporaryMessage() {
    var chatMessages = document.getElementById('faqChatMessages');
    if (!chatMessages) {
        return;
    }
    var temp = chatMessages.querySelector('[data-temp="true"]');
    if (temp) {
        chatMessages.removeChild(temp);
    }
}

function initFaqInteractions() {
    var searchInput = document.getElementById('faqSearchInput');
    var filters = Array.prototype.slice.call(document.querySelectorAll('.faq-filter'));
    var items = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
    var emptyState = document.getElementById('faqEmptyState');
    var promptChips = Array.prototype.slice.call(document.querySelectorAll('.faq-prompt-chip'));

    if (items.length > 0) {
        var activeFilter = 'all';

        var applyFaqFilter = function () {
            var keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            items.forEach(function (item) {
                var category = item.dataset.category || 'all';
                var content = (item.dataset.search || item.textContent || '').toLowerCase();
                var matchesFilter = activeFilter === 'all' || category === activeFilter;
                var matchesSearch = !keyword || content.indexOf(keyword) !== -1;
                var isVisible = matchesFilter && matchesSearch;

                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (emptyState) {
                emptyState.hidden = visibleCount > 0;
            }
        };

        filters.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFilter = button.dataset.filter || 'all';
                filters.forEach(function (filter) {
                    filter.classList.toggle('active', filter === button);
                });
                applyFaqFilter();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyFaqFilter);
        }
    }

    promptChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var chatPanel = document.getElementById('faqChatPanel');
            var chatInput = document.getElementById('faqChatInput');
            var chatForm = document.getElementById('faqChatForm');

            if (!chatPanel || !chatInput || !chatForm) {
                return;
            }

            chatPanel.hidden = false;
            chatInput.value = chip.dataset.question || chip.textContent.trim();
            chatInput.focus();

            if (chatForm.requestSubmit) {
                chatForm.requestSubmit();
            }
        });
    });
}
