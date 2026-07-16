<?php
// Location: public/api/upload-video.php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/Core/Env.php';
Env::load(__DIR__ . '/../../.env');
require_once __DIR__ . '/../../app/Core/Bootstrap.php';

function video_json_response($statusCode, $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function video_shell_arg($value)
{
    $value = (string) $value;
    if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    return escapeshellarg($value);
}

function video_delete_old_hls_bundle($relativePath)
{
    $relativePath = str_replace('\\', '/', (string) $relativePath);
    if (!preg_match('#^assets/videos/about/(about-hero-[0-9_]+)\.m3u8$#', $relativePath, $matches)) {
        return;
    }

    $videoDir = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'about');
    if (!$videoDir) {
        return;
    }

    $playlistPath = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if ($playlistPath && strpos($playlistPath, $videoDir . DIRECTORY_SEPARATOR) === 0 && is_file($playlistPath)) {
        @unlink($playlistPath);
    }

    $prefix = $matches[1] . '_';
    foreach (glob($videoDir . DIRECTORY_SEPARATOR . $prefix . '*.ts') ?: [] as $segmentPath) {
        $segmentRealPath = realpath($segmentPath);
        if ($segmentRealPath && strpos($segmentRealPath, $videoDir . DIRECTORY_SEPARATOR) === 0 && is_file($segmentRealPath)) {
            @unlink($segmentRealPath);
        }
    }
}

if (!Auth::check() || !Auth::isAdmin()) {
    video_json_response(403, [
        'success' => false,
        'message' => 'Bạn cần đăng nhập bằng tài khoản admin để upload video.',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    video_json_response(405, [
        'success' => false,
        'message' => 'Phương thức không hợp lệ.',
    ]);
}
// Kiểm tra CSRF token — tự trả JSON thay vì dùng Csrf::verify() để giữ đúng
// định dạng phản hồi mà JS phía client (admin/pages.php) đang mong đợi.
$sentCsrfToken = $_POST['csrf_token'] ?? '';
$realCsrfToken = $_SESSION['csrf_token'] ?? '';
if ($realCsrfToken === '' || !hash_equals($realCsrfToken, $sentCsrfToken)) {
    video_json_response(403, [
        'success' => false,
        'message' => 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.',
    ]);
}
if (empty($_FILES['video']) || ($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    video_json_response(400, [
        'success' => false,
        'message' => 'Vui lòng chọn video cần upload.',
    ]);
}

$file = $_FILES['video'];
if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
    video_json_response(400, [
        'success' => false,
        'message' => 'Upload video thất bại.',
        'detail' => 'Upload error code: ' . (int) $file['error'],
    ]);
}

$maxBytes = 512 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    video_json_response(413, [
        'success' => false,
        'message' => 'Video vượt quá giới hạn 512MB.',
    ]);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['mp4', 'mov', 'webm'];
if (!in_array($extension, $allowedExtensions, true)) {
    video_json_response(400, [
        'success' => false,
        'message' => 'Chỉ hỗ trợ video MP4, MOV hoặc WEBM.',
    ]);
}

$allowedMimes = ['video/mp4', 'video/quicktime', 'video/webm', 'application/octet-stream'];
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($mime && !in_array($mime, $allowedMimes, true)) {
        video_json_response(400, [
            'success' => false,
            'message' => 'File upload không phải video hợp lệ.',
            'detail' => 'MIME: ' . $mime,
        ]);
    }
}

$startSecond = max(0, (float) ($_POST['start_second'] ?? 0));
$endSecond = isset($_POST['end_second']) && $_POST['end_second'] !== '' ? max(0, (float) $_POST['end_second']) : 30.0;
if ($endSecond <= $startSecond) {
    $endSecond = $startSecond + 30.0;
}
$duration = min(120.0, $endSecond - $startSecond);

$videoDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'about';
if (!is_dir($videoDir) && !mkdir($videoDir, 0775, true)) {
    video_json_response(500, [
        'success' => false,
        'message' => 'Không tạo được thư mục public/assets/videos/about.',
    ]);
}

$stamp = date('Ymd_His');
$baseName = 'about-hero-' . $stamp;
$sourcePath = $videoDir . DIRECTORY_SEPARATOR . $baseName . '.' . $extension;
$playlistPath = $videoDir . DIRECTORY_SEPARATOR . $baseName . '.m3u8';
$segmentPattern = $videoDir . DIRECTORY_SEPARATOR . $baseName . '_%03d.ts';

if (!move_uploaded_file($file['tmp_name'], $sourcePath)) {
    video_json_response(500, [
        'success' => false,
        'message' => 'Không lưu được video lên server.',
    ]);
}

$localFfmpeg = STORAGE_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe';
$ffmpeg = is_file($localFfmpeg) ? $localFfmpeg : 'ffmpeg';
$command = sprintf(
    '%s -y -ss %s -t %s -i %s -vf %s -c:v libx264 -preset veryfast -crf 24 -c:a aac -b:a 128k -hls_time 4 -hls_playlist_type vod -hls_segment_filename %s %s 2>&1',
    video_shell_arg($ffmpeg),
    video_shell_arg((string) $startSecond),
    video_shell_arg((string) $duration),
    video_shell_arg($sourcePath),
    video_shell_arg('scale=1920:-2'),
    video_shell_arg($segmentPattern),
    video_shell_arg($playlistPath)
);

$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);

if ($exitCode !== 0 || !is_file($playlistPath)) {
    @unlink($sourcePath);
    video_json_response(500, [
        'success' => false,
        'message' => 'Không chuyển đổi được video. Hãy kiểm tra storage/bin/ffmpeg.exe có tồn tại và PHP có quyền chạy file này.',
        'detail' => implode("\n", array_slice($output, -12)),
    ]);
}

@unlink($sourcePath);

$relativePath = 'assets/videos/about/' . $baseName . '.m3u8';

try {
    $db = Database::getInstance();
    $db->query("SELECT content_value FROM site_content WHERE content_key = 'about.hero_video' LIMIT 1");
    $currentVideoRow = $db->single();
    $oldVideo = $currentVideoRow['content_value'] ?? '';

    $db->query("INSERT INTO site_content (content_key, content_group, label, input_type, content_value)
        VALUES ('about.hero_video', 'Trang giới thiệu', 'Video nền đầu trang giới thiệu', 'text', :path)
        ON DUPLICATE KEY UPDATE content_value = :update_path");
    $db->bind(':path', $relativePath);
    $db->bind(':update_path', $relativePath);
    $db->execute();

    if ($oldVideo && $oldVideo !== $relativePath) {
        video_delete_old_hls_bundle($oldVideo);
    }
} catch (Exception $exception) {
    video_json_response(500, [
        'success' => false,
        'message' => 'Video đã tạo xong nhưng không cập nhật được database.',
        'detail' => $exception->getMessage(),
    ]);
}

video_json_response(200, [
    'success' => true,
    'message' => 'Đã upload và cập nhật video hero.',
    'path' => $relativePath,
]);

.htacess
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

// ========== CẤU HÌNH SESSION COOKIE AN TOÀN (phải đặt TRƯỚC session_start()) ==========
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,   // true khi chạy HTTPS, false khi dev localhost http
    'httponly' => true,       // JS không đọc được cookie session -> chống XSS lấy session
    'samesite' => 'Lax',      // trình duyệt không gửi cookie này khi request đến từ site khác
]);

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