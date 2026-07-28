<?php
// Location: app/Core/BaseController.php
class BaseController {
    public function view($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if(file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "View $view not found.";
        }
    }
    
public function redirect($url) {
        header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
        exit();
    }

    /**
     * Redirect sang URL tuyệt đối bên ngoài site (VD: Cognito Hosted UI).
     * Khác với redirect() ở chỗ KHÔNG gắn thêm BASE_URL vào trước.
     */
    public function redirectExternal($url) {
        header("Location: " . $url);
        exit();
    }

    /**
     * Chặn truy cập nếu không phải request POST.
     * Dùng cho các action chỉ nên gọi qua form (xóa, khóa, reset...),
     * không cho phép gọi trực tiếp bằng cách gõ URL / click link GET.
     */
    protected function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Phương thức không được phép. Vui lòng thao tác qua giao diện.');
        }
    }
}