<?php
class AuthController extends BaseController
{
    public function index()
    {
        // If user is already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/login');
    }

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

            // Verify password using secure hash
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 'locked') {
                    $this->view('auth/login', ['error' => 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ quản trị viên!']);
                    return;
                }

                // Do not save password in session
                unset($user['password']);

                // Set user session
                Auth::setUser($user);

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    $this->redirect('admin');
                } else {
                    $this->redirect('');
                }
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

    public function logout()
    {
        Auth::logout();
        $this->redirect('auth');
    }
}
