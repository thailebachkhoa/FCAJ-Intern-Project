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