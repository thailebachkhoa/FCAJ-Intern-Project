<?php

/**
 * Auth Middleware Class
 * Handles session verification and role-based access control
 * Location: app/Core/Auth.php
 */
class Auth
{
    /**
     * Check if user is logged in
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Get current logged-in user
     * @return array|null
     */
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get user ID
     * @return int|null
     */
    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Get user role
     * @return string|null
     */
    public static function role()
    {
        return $_SESSION['user']['role'] ?? null;
    }

    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public static function hasRole($role)
    {
        return self::role() === $role;
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin()
    {
        return self::hasRole('admin');
    }

    /**
     * Check if user is member
     * @return bool
     */
    public static function isMember()
    {
        return self::hasRole('member');
    }

    /**
     * Check if user is guest
     * @return bool
     */
    public static function isGuest()
    {
        return !self::check();
    }

    /**
     * Get user status
     * @return string|null
     */
    public static function status()
    {
        return $_SESSION['user']['status'] ?? null;
    }

    /**
     * Check if user is active
     * @return bool
     */
    public static function isActive()
    {
        return self::status() === 'active';
    }

    /**
     * Verify if user is locked
     * @return bool
     */
    public static function isLocked()
    {
        return self::status() === 'locked';
    }

    /**
     * Set user session
     * @param array $user
     */
    public static function setUser($user)
    {
        $_SESSION['user'] = $user;
    }

    /**
     * Logout current user
     */
    public static function logout()
    {
        session_destroy();
    }
}


//////////////////
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
<?php
// Location: app/Core/Bootstrap.php
define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once BASE_PATH . '/app/Core/Env.php';
require_once BASE_PATH . '/app/Core/Auth.php';

// require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Core/Helpers.php';
require_once BASE_PATH . '/app/Core/BaseController.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Models/Data.php';
require_once BASE_PATH . '/app/Core/Csrf.php';   // <-- thêm dòng này

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

<?php
// Location: app/Core/Database.php
class Database
{
    private static $instance = null;
    private $pdo;
    private $stmt;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $db_name = $_ENV['DB_DATABASE'] ?? 'plantify';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("<h3 style='color:red;'>Database Connection Error:</h3> " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }


    public function query($sql)
    {
        $this->stmt = $this->pdo->prepare($sql);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}
<?php
// Location: app/Core/Env.php
class Env {
    public static function load($filePath) {
        if (!file_exists($filePath)) return;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

<?php
// Location: app/Core/Helpers.php
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

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return Csrf::field();
    }
}

