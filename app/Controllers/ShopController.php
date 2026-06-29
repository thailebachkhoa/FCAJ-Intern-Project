<?php

/**
 * Shop Controller (Phiên bản MVC Chuẩn với Model)
 */
class ShopController extends BaseController
{
    public function index()
    {
        $productModel = new Product();
        $user = Auth::check() ? Auth::user() : null;
        $category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
        $sort     = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
        $search   = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;


        $limit = 8;
        $offset = ($page - 1) * $limit;

        // 3. Gọi Model để lấy dữ liệu
        $products   = $productModel->getFilteredProducts($limit, $offset, $category, $sort, $search);
        $totalItems = $productModel->countFilteredProducts($category, $search);
        $totalPages = ceil($totalItems / $limit);

        // 4. Đẩy dữ liệu ra View
        // Đảm bảo đường dẫn 'shop/index' khớp với thư mục view của bạn
        $this->view('pages/shop', [
            'products'      => $products,
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'currentCategory' => $category,
            'currentSort'     => $sort,
            'searchKeyword'   => $search,
            'user'          => $user
        ]);
    }

    public function detail($id = null)
    {
        if (!$id) {
            $this->redirect('shop');
            return;
        }

        require_once BASE_PATH . '/app/Models/Product.php';
        $productModel = new Product();
        $user = Auth::check() ? Auth::user() : null;

        // Gọi Model để tìm sản phẩm
        $product = $productModel->findById($id);

        if (empty($product)) {
            $this->redirect('shop');
            return;
        }

        // Gọi Model để lấy sản phẩm liên quan
        $relatedProducts = $productModel->getRelated($id, 4);
        if (!is_array($relatedProducts)) $relatedProducts = [];

        $this->view('pages/product-detail', [
            'user' => $user,
            'product' => $product,
            'relatedProducts' => $relatedProducts
        ]);
    }

    /**
     * Thêm vào giỏ hàng (Action trung gian)
     * Thường dùng để nhận POST từ trang Product Detail
     */
    public function addToCart()
    {
        // 1. Kiểm tra đăng nhập
        if (!Auth::check()) {
            $_SESSION['error'] = "Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.";
            $this->redirect('auth');
            return;
        }

        // 2. Xử lý dữ liệu
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId > 0 && $quantity > 0) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                // Nếu đã có thì tăng số lượng
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId]['quantity'] += $quantity;
                } else {
                    // Nếu chưa có thì thêm mới
                    $_SESSION['cart'][$productId] = [
                        'id' => $productId,
                        'quantity' => $quantity
                    ];
                }
                $_SESSION['success'] = "Đã thêm sản phẩm vào giỏ hàng!";
            }

            // Trở về trang chi tiết sản phẩm
            $this->redirect('shop/detail/' . $productId);
            return;
        }

        $this->redirect('shop');
    }
}