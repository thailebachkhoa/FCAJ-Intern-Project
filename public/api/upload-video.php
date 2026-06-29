<?php

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