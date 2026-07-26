<?php
// Location: app/Controllers/FileController.php
class FileController extends BaseController
{
    // URL truy cập sẽ là: BASE_URL/file/view?path=uploads/pages/tenanh.jpg
    public function render()
    {
        $path = $_GET['path'] ?? '';

        // 1. Chặn sớm các ký tự rõ ràng là traversal / null byte,
        //    không bắt buộc nhưng giúp fail nhanh, đỡ tốn 1 lượt gọi realpath().
        if ($path === '' || strpos($path, "\0") !== false || strpos($path, '..') !== false) {
            http_response_code(404);
            echo "File không tồn tại.";
            return;
        }

        // 2. STORAGE_PATH luôn phải là 1 đường dẫn thật đã tồn tại (thư mục gốc),
        //    resolve 1 lần để lấy baseline so sánh.
        $baseReal = realpath(STORAGE_PATH);
        if ($baseReal === false) {
            http_response_code(404);
            echo "File không tồn tại.";
            return;
        }

        // 3. Ghép đường dẫn rồi RESOLVE THẬT SỰ bằng realpath().
        //    Đây là điểm mấu chốt: bản gốc chỉ strpos() trên chuỗi CHƯA resolve,
        //    trong khi file_exists()/readfile() lại đọc theo đường dẫn ĐÃ resolve
        //    (tức là "../" được hệ điều hành xử lý) -> 2 bên không khớp nhau,
        //    kẻ tấn công lợi dụng khoảng hở này để đọc file ngoài STORAGE_PATH
        //    (vd: path=../.env, path=../../etc/passwd).
        $realPath = realpath($baseReal . DIRECTORY_SEPARATOR . $path);

        // 4. Chỉ chấp nhận nếu file tồn tại THẬT và nằm ĐÚNG BÊN TRONG STORAGE_PATH.
        //    So sánh phải kèm DIRECTORY_SEPARATOR ở cuối $baseReal để tránh trường hợp
        //    "/storage-evil" bị coi là con của "/storage" (chỉ vì cùng tiền tố chuỗi).
        $isInsideBase = $realPath !== false
            && strpos($realPath, $baseReal . DIRECTORY_SEPARATOR) === 0;

        if ($isInsideBase && is_file($realPath)) {
            $mime = mime_content_type($realPath) ?: 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($realPath));
            header('X-Content-Type-Options: nosniff'); // chặn trình duyệt tự "đoán" MIME khác
            readfile($realPath);
            exit;
        }

        http_response_code(404);
        echo "File không tồn tại.";
    }
}