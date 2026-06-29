<?php

/**
 * Chuc nang: Chua cac ham dung chung cho toan bo website.
 * Cach hoat dong: Moi trang include file nay de escape du lieu, gan active menu
 * va tao duong dan asset on dinh.
 */

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_active_page')) {
    function is_active_page($page)
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $current = basename($path);
        return $current === $page ? 'active' : '';
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return app_url(trim($path, '/'));
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url()
    {
        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/');
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $projectBase = '/' . basename(BASE_PATH);
        return strpos($uri, $projectBase) === 0 ? $projectBase : '';
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        $path = trim((string) $path, '/');
        $base = app_base_url();
        if ($path === '') {
            return $base ?: '/';
        }

        return ($base ? $base . '/' : '/') . $path;
    }
}

if (!function_exists('media_url')) {
    function media_url($path)
    {
        $path = (string) $path;
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return asset($path);
    }
}


if (!function_exists('content_value')) {
    function content_value($key, $default = '')
    {

        static $dataModel = null;
        if ($dataModel === null) {
            $dataModel = new Data();
        }

        return $dataModel->content_value($key, $default);
    }
}
