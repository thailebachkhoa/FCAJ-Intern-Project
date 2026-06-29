<?php
class FileController extends BaseController
{
    // URL truy cập sẽ là: BASE_URL/file/view?path=uploads/pages/tenanh.jpg
    public function render()
    {
        $path = $_GET['path'] ?? '';
        $filePath = STORAGE_PATH . DIRECTORY_SEPARATOR . $path;

        if (file_exists($filePath) && strpos($filePath, STORAGE_PATH) === 0) {
            $mime = mime_content_type($filePath);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
        http_response_code(404);
        echo "File không tồn tại.";
    }
}