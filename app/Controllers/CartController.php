<?php

/**
 * Cart Controller
 * Quản lý giỏ hàng và liên kết dữ liệu với bảng `products` trong DB
 */
class CartController extends BaseController
{
    public function index()
    {
        require_once BASE_PATH . '/app/Models/Product.php';
        $productModel = new Product();

        $user = Auth::check() ? Auth::user() : null;
        $cartSession = $_SESSION['cart'] ?? [];
        $cartItems = [];
        $totalPrice = 0;

        foreach ($cartSession as $id => $item) {
            // Dùng luôn Model đã có, đừng viết lại SQL ở đây
            $product = $productModel->findById($id);

            if ($product) {
                $product['quantity'] = $item['quantity'];
                $product['subtotal'] = $product['price'] * $item['quantity'];

                $cartItems[$id] = $product;
                $totalPrice += $product['subtotal'];
            } else {
                // Nếu sản phẩm không tồn tại trong DB, xóa khỏi session
                unset($_SESSION['cart'][$id]);
            }
        }

        $this->view('pages/cart', [
            'user' => $user,
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice
        ]);
    }

    public function add()
    {
        if (!Auth::check()) {
            $_SESSION['error'] = "Vui lòng đăng nhập để mua hàng.";
            $this->redirect('auth');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId > 0 && $quantity > 0) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = [
                        'id' => $productId,
                        'quantity' => $quantity
                    ];
                }
                $_SESSION['success'] = "Đã thêm sản phẩm vào giỏ hàng!";
            }
            $this->redirect('cart');
            return;
        }
        $this->redirect('shop');
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $action = $_POST['action'] ?? '';

            if (isset($_SESSION['cart'][$productId])) {
                if ($action === 'increase') {
                    $_SESSION['cart'][$productId]['quantity']++;
                } elseif ($action === 'decrease') {
                    $_SESSION['cart'][$productId]['quantity']--;

                    if ($_SESSION['cart'][$productId]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$productId]);
                    }
                }
            }
        }
        $this->redirect('cart');
    }

    public function remove($id = null)
    {
        $id = (int)$id;
        if ($id > 0 && isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            $_SESSION['success'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
        }
        $this->redirect('cart');
    }
}