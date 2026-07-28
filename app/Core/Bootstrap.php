<?php
// Location: app/Core/Bootstrap.php
define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Composer autoload (cần cho firebase/php-jwt dùng để verify id_token Cognito)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once BASE_PATH . '/app/Core/Env.php';
require_once BASE_PATH . '/app/Core/Auth.php';

// require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Core/Helpers.php';
require_once BASE_PATH . '/app/Core/BaseController.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Models/Data.php';
require_once BASE_PATH . '/app/Core/Csrf.php';   // <-- thêm dòng này