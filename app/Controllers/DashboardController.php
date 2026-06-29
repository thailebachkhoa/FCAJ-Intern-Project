<?php

/**
 * Dashboard Controller
 * Handle member area, profile management and role routing
 */
class DashboardController extends BaseController
{

    public function __construct()
    {
        // Require user to be logged in
        if (!Auth::check()) {
            $this->redirect('auth');
            exit;
        }

        // Check if user is locked
        if (!Auth::isActive()) {
            session_destroy();
            header('Location: ' . BASE_URL . '/auth');
            echo 'Tài khoản của bạn đã bị khoá.';
            exit;
        }
    }

    /**
     * Dashboard Home / Profile Page
     */
    public function index()
    {
        // Redirect admin to admin panel
        if (Auth::isAdmin()) {
            $this->redirect('admin');
            return;
        }


        if (Auth::isMember()) {
            require_once BASE_PATH . '/app/Models/User.php';
            $userModel = new User();

            $currentUser = $userModel->findById(Auth::user()['id']);

            $this->view('dashboard/index', [
                'user' => Auth::user(),
                'pageTitle' => 'Bảng điều khiển'
            ]);
            return;
        }

        // Unknown role
        echo "Lỗi: Vai trò không xác định.";
        exit;
    }

    /**
     * Handle Profile Update (Update Name & Avatar)
     */
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('dashboard');
            return;
        }

        require_once BASE_PATH . '/app/Models/User.php';
        $userModel = new User();
        $userId = Auth::user()['id'];
        $fullname = trim($_POST['fullname'] ?? '');

        // Lấy lại user hiện tại để giữ lại avatar cũ nếu không upload mới
        $currentUser = $userModel->findById($userId);
        $avatarPath = $currentUser['avatar'];

        // Validate
        if (empty($fullname)) {
            $_SESSION['error'] = "Họ và tên không được để trống!";
            $this->redirect('dashboard/index');
            return;
        }

        // XỬ LÝ UPLOAD ẢNH
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['avatar']['size'] < 5000000) {
                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                // Lưu vào STORAGE_PATH thay vì public/assets
                $uploadDir = STORAGE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newFileName)) {
                    if ($avatarPath && file_exists(STORAGE_PATH . DIRECTORY_SEPARATOR . $avatarPath)) {
                        @unlink(STORAGE_PATH . DIRECTORY_SEPARATOR . $avatarPath);
                    }
                    $avatarPath = 'uploads/avatars/' . $newFileName;
                }
            } else {
                $_SESSION['error'] = "Định dạng ảnh không hợp lệ hoặc quá lớn (Max 5MB)!";
                $this->redirect('dashboard/index');
                return;
            }
        }

        // Update vào DB
        if ($userModel->updateProfile($userId, $fullname, $avatarPath)) {
            $_SESSION['user']['fullname'] = $fullname;
            $_SESSION['user']['avatar'] = $avatarPath; // Đường dẫn mới vào session

            $_SESSION['success'] = "Cập nhật hồ sơ thành công!";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra, vui lòng thử lại.";
        }

        $this->redirect('dashboard/index');
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('dashboard');
            return;
        }

        require_once BASE_PATH . '/app/Models/User.php';
        $userModel = new User();
        $userId = Auth::user()['id'];

        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $user = $userModel->findById($userId);

        if (!password_verify($currentPass, $user['password'])) {
            $_SESSION['error'] = "Mật khẩu hiện tại không đúng!";
        } elseif ($newPass !== $confirmPass) {
            $_SESSION['error'] = "Mật khẩu mới không khớp!";
        } elseif (strlen($newPass) < 6) {
            $_SESSION['error'] = "Mật khẩu mới phải từ 6 ký tự trở lên!";
        } else {
            $userModel->updatePassword($userId, password_hash($newPass, PASSWORD_DEFAULT));
            $_SESSION['success'] = "Đổi mật khẩu thành công!";
        }

        $this->redirect('dashboard');
    }

    public function checkout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::check()) {
                $this->redirect('auth');
                exit;
            }

            $cartSession = $_SESSION['cart'] ?? [];
            if (empty($cartSession)) {
                $this->redirect('cart');
                exit;
            }

            $db = Database::getInstance();
            $cartItems = [];
            $totalPrice = 0;

            // XỬ LÝ GIỎ HÀNG & LẤY GIÁ GỐC TỪ DATABASE (BẢO MẬT HƠN)
            foreach ($cartSession as $key => $value) {
                $productId = 0;
                $quantity = 0;

                // Tự động nhận diện cấu trúc Session của bạn (dù là mảng hay key=>value)
                if (is_array($value)) {
                    $productId = $value['product_id'] ?? $value['id'] ?? 0;
                    $quantity = $value['quantity'] ?? $value['qty'] ?? 1;
                } else {
                    $productId = $key;
                    $quantity = $value;
                }

                if ($productId) {
                    // Truy vấn DB để lấy giá chính xác nhất của sản phẩm
                    $db->query("SELECT id, price FROM products WHERE id = :id");
                    $db->bind(':id', $productId);
                    $product = $db->single();

                    if ($product) {
                        // Tạo mảng chuẩn bị cho OrderModel
                        $cartItems[] = [
                            'product_id' => $product['id'],
                            'quantity'   => $quantity,
                            'price'      => $product['price']
                        ];
                        // Tính tổng tiền dựa trên giá DB
                        $totalPrice += ($product['price'] * $quantity);
                    }
                }
            }

            // Nếu không có sản phẩm nào hợp lệ
            if (empty($cartItems)) {
                $_SESSION['error'] = "Dữ liệu giỏ hàng không hợp lệ.";
                $this->redirect('cart');
                exit;
            }

            require_once BASE_PATH . '/app/Models/Order.php';
            $orderModel = new Order();

            $orderData = [
                'user_id'     => Auth::id(),
                'fullname'    => $_POST['fullname'] ?? '',
                'phone'       => $_POST['phone'] ?? '',
                'address'     => $_POST['address'] ?? '',
                'note'        => $_POST['note'] ?? '',
                'total_price' => $totalPrice
            ];

            // Gửi mảng $cartItems đã chuẩn hóa vào Order
            $result = $orderModel->create($orderData, $cartItems);

            if ($result) {
                unset($_SESSION['cart']);
                $_SESSION['success'] = "Đặt hàng thành công! Chúng tôi sẽ sớm liên hệ với bạn.";
                $this->redirect('dashboard/orders');
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra trong quá trình lưu đơn hàng. Vui lòng thử lại.";
                $this->redirect('cart');
            }
        }
    }

    /**
     * Hàm hiển thị danh sách đơn hàng của User
     * URL: /dashboard/orders
     */
    public function orders()
    {
        if (!Auth::check()) {
            $this->redirect('auth');
            exit;
        }

        require_once BASE_PATH . '/app/Models/Order.php';
        $orderModel = new Order();

        $myOrders = $orderModel->getOrdersByUserId(Auth::id());

        $this->view('dashboard/orders', [
            'user'      => Auth::user(),
            'myOrders'  => $myOrders,
            'pageTitle' => 'Lịch sử đơn hàng'
        ]);
    }
    public function order_detail($id = null)
    {
        if (!Auth::check() || !$id) {
            $this->redirect('auth');
            exit;
        }

        require_once BASE_PATH . '/app/Models/Order.php';
        $orderModel = new Order();

        // Tái sử dụng hàm getOrderDetail đã tạo ở phần Admin
        $order = $orderModel->getOrderDetail($id);

        if (!$order || $order['user_id'] != Auth::id()) {
            $_SESSION['error'] = "Bạn không có quyền xem đơn hàng này.";
            $this->redirect('dashboard/orders');
            exit;
        }


        $this->view('dashboard/order-detail', [
            'user'      => Auth::user(),
            'order'     => $order,
            'pageTitle' => 'Chi tiết đơn hàng #' . $id
        ]);
    }
}