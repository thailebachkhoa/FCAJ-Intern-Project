<?php
// Location: app/Core/Csrf.php

class Csrf
{
    /**
     * Lấy token hiện tại, tự sinh nếu chưa có.
     */
    public static function token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * In sẵn 1 input hidden để nhét vào form.
     */
    public static function field()
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    /**
     * Kiểm tra token gửi lên từ $_POST. Nếu sai -> chặn luôn (403).
     * Gọi hàm này ở ĐẦU mọi action xử lý POST quan trọng.
     */
    public static function verify()
    {
        $sent = $_POST['csrf_token'] ?? '';
        $real = $_SESSION['csrf_token'] ?? '';

        if ($real === '' || !hash_equals($real, $sent)) {
            http_response_code(403);
            die('Yêu cầu không hợp lệ (CSRF token sai hoặc đã hết hạn). Vui lòng tải lại trang và thử lại.');
        }
    }
}