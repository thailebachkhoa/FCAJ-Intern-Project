<?php
// Location: app/Controllers/AuthController.php
class AuthController extends BaseController
{
    public function __construct()
    {
        // Chặn CSRF cho mọi request POST (đăng nhập, đăng ký, xác thực TOTP)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verify();
        }
    }
    public function index()
    {
        // If user is already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/login');
    }

    /**
     * Đăng nhập kiểu cũ bằng username/password (giữ lại làm phương án dự phòng).
     * Nếu tài khoản có role = admin thì KHÔNG cấp session ngay, mà bắt qua
     * bước xác thực TOTP giống hệt luồng đăng nhập Google, để đảm bảo admin
     * luôn phải có Google Authenticator dù đăng nhập bằng đường nào.
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $this->view('auth/login', ['error' => 'Vui lòng nhập đầy đủ thông tin!']);
                return;
            }

            $userModel = new User();
            // Support login by username or email
            $user = $userModel->findByUsernameOrEmail($username);

            // Tài khoản chỉ tạo qua Google sẽ có password = NULL -> không cho login kiểu cũ
            if ($user && $user['password'] && password_verify($password, $user['password'])) {
                if ($user['status'] == 'locked') {
                    $this->view('auth/login', ['error' => 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ quản trị viên!']);
                    return;
                }

                // Đổi session ID mới sau khi xác thực thành công -> chống session fixation
                session_regenerate_id(true);

                // Do not save password in session
                unset($user['password']);

                if ($user['role'] == 'admin') {
                    // Chưa cấp session admin ngay, chuyển sang bước nhập mã Authenticator
                    $_SESSION['pending_admin'] = $user;
                    $this->redirect('auth/totp');
                    return;
                }

                Auth::setUser($user);
                $this->redirect('');
            } else {
                $this->view('auth/login', ['error' => 'Tên đăng nhập, email hoặc mật khẩu không chính xác!']);
            }
        } else {
            $this->redirect('auth');
        }
    }

    public function register()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'fullname' => trim($_POST['fullname'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
            ];

            // PHP Server-side Validation
            if (empty($data['fullname']) || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                $this->view('auth/register', ['error' => 'Vui lòng điền đầy đủ dữ liệu!', 'data' => $data]);
                return;
            }

            // Validate fullname length
            if (strlen($data['fullname']) < 3) {
                $this->view('auth/register', ['error' => 'Họ và tên phải có ít nhất 3 ký tự!', 'data' => $data]);
                return;
            }

            // Validate username format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
                $this->view('auth/register', ['error' => 'Tên đăng nhập chỉ được chứa chữ cái, số, gạch dưới và gạch ngang!', 'data' => $data]);
                return;
            }

            // Validate username length
            if (strlen($data['username']) < 3) {
                $this->view('auth/register', ['error' => 'Tên đăng nhập phải có ít nhất 3 ký tự!', 'data' => $data]);
                return;
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->view('auth/register', ['error' => 'Email không hợp lệ!', 'data' => $data]);
                return;
            }

            // Validate password length
            if (strlen($data['password']) < 6) {
                $this->view('auth/register', ['error' => 'Mật khẩu phải có ít nhất 6 ký tự!', 'data' => $data]);
                return;
            }

            $userModel = new User();

            // Check if username already exists
            if ($userModel->findByUsername($data['username'])) {
                $this->view('auth/register', ['error' => 'Tên đăng nhập đã tồn tại!', 'data' => $data]);
                return;
            }

            // Check if email already exists
            if ($userModel->findByEmail($data['email'])) {
                $this->view('auth/register', ['error' => 'Email đã tồn tại!', 'data' => $data]);
                return;
            }

            // Hash password using PASSWORD_DEFAULT (PHP's secure default)
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Register user
            if ($userModel->register($data)) {
                $this->view('auth/login', ['success' => 'Đăng ký thành công! Hãy đăng nhập với tài khoản vừa tạo.']);
            } else {
                $this->view('auth/register', ['error' => 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại!', 'data' => $data]);
            }
        } else {
            $this->view('auth/register');
        }
    }

    /**
     * Bấm nút "Đăng nhập với Google" -> đưa thẳng sang Google
     * (qua Cognito Hosted UI, identity_provider=Google).
     */
    public function google()
    {
        $this->redirectExternal(Cognito::loginUrl());
    }

    /**
     * Cognito redirect người dùng về đây kèm ?code=... sau khi đăng nhập
     * Google thành công. Đổi code lấy id_token, verify, rồi tạo/khớp user.
     */
    public function callback()
    {
        $code    = $_GET['code'] ?? '';
        $state   = $_GET['state'] ?? '';
        $oauthErr = $_GET['error_description'] ?? $_GET['error'] ?? '';

        if ($oauthErr !== '') {
            $this->view('auth/login', ['error' => 'Đăng nhập Google bị huỷ hoặc thất bại: ' . $oauthErr]);
            return;
        }

        if ($code === '' || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $this->view('auth/login', ['error' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.']);
            return;
        }
        unset($_SESSION['oauth_state']);

        try {
            $tokens  = Cognito::exchangeCodeForTokens($code);
            $payload = Cognito::verifyIdToken($tokens['id_token']);
        } catch (Exception $e) {
            error_log('Cognito auth error: ' . $e->getMessage());
            $this->view('auth/login', ['error' => 'Không xác thực được với Cognito. Vui lòng thử lại sau.']);
            return;
        }

        $sub      = $payload['sub'] ?? '';
        $email    = $payload['email'] ?? '';
        $fullname = $payload['name'] ?? ($payload['given_name'] ?? $email);
        $isAdmin  = Cognito::isAdminGroup($payload);

        if ($sub === '' || $email === '') {
            $this->view('auth/login', ['error' => 'Không lấy được thông tin tài khoản Google.']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByCognitoSub($sub);

        if (!$user) {
            // Email đã có tài khoản kiểu cũ (local) -> gắn cognito_sub vào luôn, khỏi tạo trùng
            $existing = $userModel->findByEmail($email);
            if ($existing) {
                $userModel->attachCognitoSub($existing['id'], $sub);
                $user = $userModel->findById($existing['id']);
            } else {
                $newId = $userModel->createFromCognito($sub, $email, $fullname);
                $user  = $userModel->findById($newId);
            }
        }

        if ($user['status'] === 'locked') {
            $this->view('auth/login', ['error' => 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ quản trị viên!']);
            return;
        }

        // Đồng bộ role theo Cognito Group hiện tại (đề phòng admin bị gỡ nhóm)
        $userModel->syncRole($user['id'], $isAdmin);
        $user['role'] = $isAdmin ? 'admin' : 'member';
        unset($user['password']);

        session_regenerate_id(true);

        if (!$isAdmin) {
            Auth::setUser($user);
            $this->redirect('');
            return;
        }

        // Admin -> chưa cấp session thật, bắt qua bước nhập mã Authenticator
        $_SESSION['pending_admin'] = $user;
        $this->redirect('auth/totp');
    }

    /**
     * Bước xác thực 2 (chỉ Admin đi qua đây). Lần đầu: hiện QR để quét vào
     * Google Authenticator. Các lần sau: chỉ hỏi mã 6 số.
     */
    public function totp()
    {
        $pending = $_SESSION['pending_admin'] ?? null;
        if (!$pending) {
            $this->redirect('auth');
            return;
        }

        $isSetup = empty($pending['totp_secret']);

        // Đảm bảo luôn có sẵn 1 secret trong session khi đang ở bước setup lần đầu
        if ($isSetup && empty($_SESSION['pending_totp_secret'])) {
            $_SESSION['pending_totp_secret'] = Totp::generateSecret();
        }
        $secret = $isSetup ? $_SESSION['pending_totp_secret'] : $pending['totp_secret'];
        $issuer = $_ENV['ADMIN_TOTP_ISSUER'] ?? 'Plantify Co';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');

            if (Totp::verify($secret, $code)) {
                if ($isSetup) {
                    $userModel = new User();
                    $userModel->setTotpSecret($pending['id'], $secret);
                    $pending['totp_secret'] = $secret;
                }

                unset($_SESSION['pending_totp_secret'], $_SESSION['pending_admin']);
                session_regenerate_id(true);

                Auth::setUser($pending);
                $this->redirect('admin');
                return;
            }

            $this->view('auth/totp', [
                'error'  => 'Mã xác thực không đúng hoặc đã hết hạn. Vui lòng thử lại.',
                'setup'  => $isSetup,
                'secret' => $isSetup ? $secret : null,
                'qrUri'  => $isSetup ? Totp::provisioningUri($secret, $pending['email'], $issuer) : null,
            ]);
            return;
        }

        $this->view('auth/totp', [
            'setup'  => $isSetup,
            'secret' => $isSetup ? $secret : null,
            'qrUri'  => $isSetup ? Totp::provisioningUri($secret, $pending['email'], $issuer) : null,
        ]);
    }

    public function logout()
    {
        Auth::logout();
        $this->redirect('auth');
    }
}