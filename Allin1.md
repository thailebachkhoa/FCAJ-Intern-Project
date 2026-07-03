<?php

/**
 * AdminController
 * Handles admin dashboard
 * Location: app/Controllers/AdminController.php
 */
class AdminController extends BaseController
{
    public function __construct()
    {
        // Require admin role
        if (!Auth::check()) {
            $this->redirect('auth');
            exit;
        }
        if (!Auth::isAdmin()) {
            $this->redirect('dashboard');
            exit;
        }
        if (!Auth::isActive()) {
            session_destroy();
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }
    }

    /* =============================================
       USER MANAGEMENT (existing)
       ============================================= */

    public function index()
    {
        // Trang Dashboard tổng quan
        $this->view('admin/index', [
            'user' => Auth::user(),
            'pageTitle' => 'Tổng quan hệ thống'
        ]);
    }

    public function users()
    {
        $userModel = new User();

        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $users = $userModel->getPaginated($limit, $offset);
        $totalUsers = $userModel->countAll();
        $totalPages = ceil($totalUsers / $limit);

        $this->view('admin/users', [
            'user'  => Auth::user(),
            'users' => $users,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'pageTitle' => 'Quản lý thành viên'
        ]);
    }

    public function user_create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel = new User();
            $data = [
                'username' => $_POST['username'],
                'fullname' => $_POST['fullname'],
                'email'    => $_POST['email'],
                'role'     => $_POST['role'],
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'status'   => 1
            ];

            if ($userModel->create($data)) {
                $this->redirect('admin/users');
                exit;
            }
        }

        $this->view('admin/user-form', [
            'user'      => Auth::user(),
            'pageTitle' => 'Thêm thành viên mới'
        ]);
    }

    /** Toggle user status (lock/unlock) */
    public function toggle_status($id)
    {
        $userModel  = new User();
        $targetUser = $userModel->findById($id);
        if ($targetUser && $targetUser['role'] !== 'admin' && $targetUser['id'] != Auth::id()) {
            $newStatus = ($targetUser['status'] === 'active') ? 'locked' : 'active';
            $userModel->updateStatus($id, $newStatus);
        }
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('admin/users');
    }

    /** Reset user password to default (123456) */
    public function reset_password($id)
    {
        $userModel  = new User();
        $targetUser = $userModel->findById($id);
        if ($targetUser && $targetUser['role'] !== 'admin' && $targetUser['id'] != Auth::id()) {
            $userModel->resetPassword($id, password_hash('123456', PASSWORD_DEFAULT));
        }
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('admin/users');
    }

    /** Delete a member account */
    public function delete_user($id)
    {
        if ($id == Auth::id()) {
            $this->redirect('admin/users');
            return;
        }
        $userModel  = new User();
        $targetUser = $userModel->findById($id);
        if ($targetUser && $targetUser['role'] !== 'admin') {
            $userModel->deleteUser($id);
        }
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('admin/users');
    }

    /* =============================================
       NEWS MANAGEMENT 
       ============================================= */

    /** GET /admin/news — list all news with search + pagination */
    public function news()
    {
        $newsModel    = new News();
        $search       = trim($_GET['search'] ?? '');
        $statusFilter = $_GET['status'] ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));

        $total      = $newsModel->countAll($search, $statusFilter);
        $newsList   = $newsModel->getAll($page, $search, $statusFilter);
        $totalPages = $total > 0 ? (int)ceil($total / $newsModel->getAdminPerPage()) : 1;

        $success = $_SESSION['admin_success'] ?? null;
        $error   = $_SESSION['admin_error']   ?? null;
        unset($_SESSION['admin_success'], $_SESSION['admin_error']);

        $this->view('admin/news-list', [
            'user'         => Auth::user(),
            'newsList'     => $newsList,
            'search'       => $search,
            'statusFilter' => $statusFilter,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'total'        => $total,
            'success'      => $success,
            'error'        => $error,
        ]);
    }

    /** GET /admin/news_create — show form
     *  POST /admin/news_create — handle creation */
    public function news_create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->processNewsForm();
            if ($result['error']) {
                $this->view('admin/news-form', [
                    'user'     => Auth::user(),
                    'mode'     => 'create',
                    'news'     => null,
                    'error'    => $result['error'],
                    'formData' => $_POST,
                ]);
                return;
            }

            $newsModel = new News();
            $newId     = $newsModel->create($result['data']);

            // Append numeric ID to slug for uniqueness
            $finalSlug = News::generateSlug($result['data']['title'], $newId);
            $newsModel->updateSlug($newId, $finalSlug);

            $_SESSION['admin_success'] = 'Bài viết đã được tạo thành công!';
            $this->redirect('admin/news');
        } else {
            $this->view('admin/news-form', [
                'user'     => Auth::user(),
                'mode'     => 'create',
                'news'     => null,
                'error'    => null,
                'formData' => null,
            ]);
        }
    }

    /** GET /admin/news_edit/{id} — show edit form
     *  POST /admin/news_edit/{id} — handle update */
    public function news_edit($id = null)
    {
        $newsModel = new News();
        $news      = $newsModel->getById((int)$id);

        if (!$news) {
            $_SESSION['admin_error'] = 'Bài viết không tồn tại!';
            $this->redirect('admin/news');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->processNewsForm($news['thumbnail']);
            if ($result['error']) {
                $this->view('admin/news-form', [
                    'user'     => Auth::user(),
                    'mode'     => 'edit',
                    'news'     => $news,
                    'error'    => $result['error'],
                    'formData' => array_merge($news, $_POST),
                ]);
                return;
            }

            $newsModel->update((int)$id, $result['data']);
            $_SESSION['admin_success'] = 'Bài viết đã được cập nhật!';
            $this->redirect('admin/news');
        } else {
            $this->view('admin/news-form', [
                'user'     => Auth::user(),
                'mode'     => 'edit',
                'news'     => $news,
                'error'    => null,
                'formData' => $news,
            ]);
        }
    }

    /** GET /admin/news_delete/{id} — delete a news article */
    public function news_delete($id = null)
    {
        $newsModel = new News();
        $news      = $newsModel->getById((int)$id);

        if ($news) {
            // Delete thumbnail file from disk
            if (!empty($news['thumbnail'])) {
                $thumbPath = __DIR__ . '/../../public/' . $news['thumbnail'];
                if (file_exists($thumbPath)) {
                    @unlink($thumbPath);
                }
            }
            $newsModel->delete((int)$id);
            $_SESSION['admin_success'] = 'Bài viết đã được xóa!';
        } else {
            $_SESSION['admin_error'] = 'Bài viết không tồn tại!';
        }

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $this->redirect('admin/news');
    }

    /* =============================================
       COMMENT MANAGEMENT (Part #4)
       ============================================= */

    /** GET /admin/comments — list all comments with search + pagination */
    public function comments()
    {
        $commentModel = new Comment();
        $search       = trim($_GET['search'] ?? '');
        $page         = max(1, (int)($_GET['page'] ?? 1));

        $total        = $commentModel->countAll($search);
        $commentsList = $commentModel->getAll($page, $search);
        $totalPages   = $total > 0 ? (int)ceil($total / $commentModel->getPerPage()) : 1;

        $success = $_SESSION['admin_success'] ?? null;
        unset($_SESSION['admin_success']);

        $this->view('admin/comment-list', [
            'user'         => Auth::user(),
            'commentsList' => $commentsList,
            'search'       => $search,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'total'        => $total,
            'success'      => $success,
        ]);
    }

    /** GET /admin/comment_toggle/{id} — toggle comment approved ↔ hidden */
    public function comment_toggle($id = null)
    {
        $commentModel = new Comment();
        $commentModel->toggleStatus((int)$id);
        $_SESSION['admin_success'] = 'Trạng thái bình luận đã được cập nhật!';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('admin/comments');
    }

    /** GET /admin/comment_delete/{id} — delete a comment */
    public function comment_delete($id = null)
    {
        $commentModel = new Comment();
        $commentModel->delete((int)$id);
        $_SESSION['admin_success'] = 'Bình luận đã được xóa!';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            // Chuyển hướng về lại đúng URL đó (giữ nguyên page và search)
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('admin/comments');
    }

    /* =============================================
       PRIVATE HELPER
       ============================================= */

    /**
     * Process and validate the news create/edit form.
     * Returns ['error' => string|null, 'data' => array|null]
     *
     * @param string|null $existingThumbnail  Current thumbnail path (for edit)
     */
    private function processNewsForm($existingThumbnail = null)
    {
        $title     = trim($_POST['title']             ?? '');
        $shortDesc = trim($_POST['short_description'] ?? '');
        $content   = trim($_POST['content']           ?? '');
        $tags      = trim($_POST['tags']              ?? '');
        $seoDesc   = trim($_POST['seo_desc']          ?? '');
        $author    = trim($_POST['author']            ?? 'Admin');
        $status    = in_array($_POST['status'] ?? '', ['published', 'draft', 'hidden'])
            ? $_POST['status'] : 'draft';

        // ---- Server-side validation ----
        if (empty($title))              return ['error' => 'Tiêu đề không được để trống!',         'data' => null];
        if (mb_strlen($title) < 5)     return ['error' => 'Tiêu đề phải có ít nhất 5 ký tự!',     'data' => null];
        if (mb_strlen($title) > 255)   return ['error' => 'Tiêu đề không được vượt quá 255 ký tự!', 'data' => null];
        if (empty($shortDesc))         return ['error' => 'Mô tả ngắn không được để trống!',       'data' => null];
        if (empty($content))           return ['error' => 'Nội dung bài viết không được để trống!', 'data' => null];

        // ---- Image upload (optional) ----
        $thumbnail = $existingThumbnail ?? '';

        if (!empty($_FILES['thumbnail']['name'])) {
            $file    = $_FILES['thumbnail'];
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file['type'], $allowed) && !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return ['error' => 'Chỉ chấp nhận file ảnh: JPG, JPEG, PNG, WEBP!', 'data' => null];
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                return ['error' => 'Ảnh không được vượt quá 2MB!', 'data' => null];
            }
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['error' => 'Lỗi khi upload ảnh (code: ' . $file['error'] . ')!', 'data' => null];
            }

            $uploadDir = __DIR__ . '/../../public/assets/uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest     = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                return ['error' => 'Không thể lưu file ảnh lên server!', 'data' => null];
            }

            // Delete old thumbnail
            if (!empty($existingThumbnail)) {
                $oldPath = __DIR__ . '/../../public/' . $existingThumbnail;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $thumbnail = 'uploads/news/' . $filename;
        }

        // ---- Build and sanitize data ----
        $slug = News::generateSlug($title);

        return [
            'error' => null,
            'data'  => [
                'title'             => htmlspecialchars($title,     ENT_QUOTES, 'UTF-8'),
                'slug'              => $slug,
                'short_description' => htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8'),
                'content'           => $content,   // Admin content — allow HTML
                'thumbnail'         => $thumbnail,
                'tags'              => htmlspecialchars($tags,      ENT_QUOTES, 'UTF-8'),
                'seo_desc'          => htmlspecialchars($seoDesc,   ENT_QUOTES, 'UTF-8'),
                'author'            => htmlspecialchars($author,    ENT_QUOTES, 'UTF-8'),
                'status'            => $status,
            ],
        ];
    }
    /* =============================================
       QUẢN LÝ NỘI DUNG, FAQ & RAG
       ============================================= */

    public function pages()
    {
        require_once BASE_PATH . '/app/Models/Content.php';
        $contentModel = new Content();

        // Lấy dữ liệu từ DB
        $contentRows = $contentModel->getAllSiteContent();
        $pages = $contentModel->getAllPages();

        // Nhóm dữ liệu để hiển thị (giống logic cũ của bạn)
        $groupedContent = [];
        foreach ($contentRows as $row) {
            $groupedContent[$row['content_group']][] = $row;
        }

        $this->view('admin/pages', [
            'user' => Auth::user(),
            'groupedContent' => $groupedContent,
            'pages' => $pages,
            'pageTitle' => 'Quản lý Nội dung',
            'message'        => $_SESSION['admin_success'] ?? '', // Truyền từ session
            'error'          => $_SESSION['admin_error'] ?? ''
        ]);
    }

    public function save_pages()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once BASE_PATH . '/app/Models/Content.php';
            $contentModel = new Content();

            foreach ($_POST['content'] as $key => $value) {
                $contentModel->updateSiteContent($key, $value);
            }
            $_SESSION['admin_success'] = "Đã lưu thay đổi!";
            $this->redirect('admin/pages');
        }
    }

    public function faqs()
    {
        $this->view('admin/faqs', [
            'user' => Auth::user()
        ]);
    }



    /* =============================================
   PRODUCT MANAGEMENT
   ============================================= */

    public function products()
    {
        $productModel = new Product();

        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $products = $productModel->getPaginated($limit, $offset);
        $totalProducts = $productModel->countAll();
        $totalPages = ceil($totalProducts / $limit);

        $this->view('admin/products', [
            'user'      => Auth::user(),
            'products'  => $products,
            'pageTitle' => 'Quản lý Sản phẩm',
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    public function product_create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productModel = new Product();

            // Xử lý upload ảnh
            $imagePath = $this->handleProductImageUpload();

            $data = [
                'name'        => $_POST['name'],
                'category'    => $_POST['category'],
                'price'       => (float)$_POST['price'],
                'description' => $_POST['description'],
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'image'       => $imagePath
            ];

            if ($productModel->create($data)) {
                $this->redirect('admin/products');
            }
        }

        $this->view('admin/product-form', [
            'user'      => Auth::user(),
            'pageTitle' => 'Thêm sản phẩm mới',
            'mode'      => 'create'
        ]);
    }


    public function product_edit($id)
    {
        $productModel = new Product();
        $product = $productModel->findById($id);

        if (!$product) {
            $this->redirect('admin/products');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagePath = $this->handleProductImageUpload($product['image']);

            $data = [
                'name'        => $_POST['name'],
                'category'    => $_POST['category'],
                'price'       => (float)$_POST['price'],
                'description' => $_POST['description'],
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'image'       => $imagePath
            ];

            if ($productModel->update($id, $data)) {
                $this->redirect('admin/products');
            }
        }

        $this->view('admin/product-form', [
            'user'      => Auth::user(),
            'product'   => $product,
            'pageTitle' => 'Chỉnh sửa sản phẩm',
            'mode'      => 'edit'
        ]);
    }

    public function shop_settings()
    {
        $contentModel = new Content();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop_content'])) {
            $contents = $_POST['content'] ?? [];

            if ($contentModel->updateMultipleSiteContent($contents)) {
                $_SESSION['admin_success'] = "Đã cập nhật các cấu hình cửa hàng thành công!";
            } else {
                $_SESSION['admin_error'] = "Có lỗi xảy ra trong quá trình cập nhật.";
            }

            $this->redirect('admin/shop-settings');
            exit;
        }

        $allSettings = $contentModel->getSiteContentByGroups(['Trang cửa hàng', 'Trang chi tiết SP', 'Trang giỏ hàng']);

        $groups = [];
        foreach ($allSettings as $item) {
            $groups[$item['content_group']][] = $item;
        }

        $this->view('admin/shop-settings', [
            'user'            => Auth::user(),
            'pageTitle'       => 'Cấu hình Cửa hàng',
            'settingsByGroup' => $groups
        ]);
    }

    /**
     * Helper: Xử lý upload ảnh sản phẩm
     */
    private function handleProductImageUpload($existingImage = null)
    {
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/public/assets/uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'prod_' . time() . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadDir . $fileName)) {
                // Xóa ảnh cũ nếu có
                if ($existingImage && file_exists(BASE_PATH . '/public/' . $existingImage)) {
                    @unlink(BASE_PATH . '/public/' . $existingImage);
                }
                return 'assets/uploads/products/' . $fileName;
            }
        }
        return $existingImage; // Giữ nguyên ảnh cũ nếu không có upload mới
    }

    public function product_delete($id)
    {
        $productModel = new Product();
        $product = $productModel->findById($id);

        if ($product) {
            // Xóa file ảnh trên server
            if (!empty($product['image']) && file_exists(BASE_PATH . '/public/' . $product['image'])) {
                @unlink(BASE_PATH . '/public/' . $product['image']);
            }
            $productModel->delete($id);
        }

        $this->redirect('admin/products');
    }

  
   
    public function orders()
    {
        require_once BASE_PATH . '/app/Models/Order.php';
        $orderModel = new Order();
        $allOrders = $orderModel->getAllOrders();

        $this->view('admin/orders', [
            'orders' => $allOrders,
            'pageTitle' => 'Quản lý Đơn hàng'
        ]);
    }

    public function order_detail($id)
    {
        require_once BASE_PATH . '/app/Models/Order.php';
        $orderModel = new Order();
        $order = $orderModel->getOrderDetail($id);

        if (!$order) {
            $this->redirect('admin/orders');
            exit;
        }

        $this->view('admin/order-detail', [
            'order' => $order,
            'pageTitle' => 'Chi tiết đơn hàng #' . $id
        ]);
    }

    public function order_update_status($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once BASE_PATH . '/app/Models/Order.php';
            $orderModel = new Order();
            $status = $_POST['status'] ?? 'pending';

            if ($orderModel->updateStatus($id, $status)) {
                $_SESSION['admin_success'] = "Đã cập nhật trạng thái đơn hàng!";
            }

            // $this->redirect('admin/orders/detail/' . $id);
            $this->redirect('admin/order_detail/' . $id);
        }
    }

     /* =============================================
       QUẢN LÝ NỘI DUNG CÁC TRANG (page editors)
       ============================================= */
 
    /* =============================================
       QUẢN LÝ NỘI DUNG CÁC TRANG (page editors)
       ============================================= */
 
    /**
     * Seed defaults + lưu POST cho một page-editor group.
     * Trả về ['message' => string, 'error' => string, 'byKey' => array]
     */
    private function _pageEditorHandle(array $defaults, string $group): array
    {
        require_once BASE_PATH . '/app/Models/Content.php';
        $contentModel = new Content();
 
        // Seed row mặc định (INSERT IGNORE logic)
        $contentModel->seedDefaults($defaults);
 
        $message = '';
        $error   = '';
 
        // Xử lý POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
            try {
                $contentModel->saveByPost($_POST['content']);
                $message = 'Đã lưu nội dung thành công!';
            } catch (Exception $e) {
                $error = 'Có lỗi khi lưu: ' . $e->getMessage();
            }
        }
 
        return [
            'message' => $message,
            'error'   => $error,
            'byKey'   => $contentModel->getByGroup($group),
        ];
    }
 
    /**
     * GET/POST /admin/page_home — nội dung trang chủ
     */
    public function page_home()
    {
        $defaults = [
            ['home.hero_kicker',        'Trang chủ', 'Nhãn hero',                          'text',     'Khởi Đầu Mới'],
            ['home.hero_title',         'Trang chủ', 'Tiêu đề hero (HTML OK)',              'textarea', 'Biến Không Gian Sống<br>Thành Vườn Xanh Bình Yên'],
            ['home.hero_description',   'Trang chủ', 'Mô tả hero',                         'textarea', 'Khám phá bộ sưu tập cây cảnh tuyển chọn giúp thanh lọc không khí, mang lại cảm giác thư thái và nguồn năng lượng tích cực cho ngôi nhà của bạn.'],
            ['home.hero_btn_primary',   'Trang chủ', 'Nút hero chính',                     'text',     'Mua Sắm Ngay'],
            ['home.hero_btn_secondary', 'Trang chủ', 'Nút hero phụ',                       'text',     'Tìm Hiểu Thêm'],
            ['home.hero_card_title',    'Trang chủ', 'Tiêu đề thẻ hero',                   'text',     '100% Cây Khỏe Mạnh'],
            ['home.hero_card_text',     'Trang chủ', 'Nội dung thẻ hero',                  'textarea', 'Được chăm sóc và kiểm tra kỹ lưỡng bởi chuyên gia thực vật trước khi giao đến tay bạn.'],
            ['home.metric_1_value',     'Trang chủ', 'Chỉ số 1',                           'text',     '500+'],
            ['home.metric_1_label',     'Trang chủ', 'Nhãn chỉ số 1',                      'text',     'Sản phẩm đa dạng'],
            ['home.metric_2_value',     'Trang chủ', 'Chỉ số 2',                           'text',     '100%'],
            ['home.metric_2_label',     'Trang chủ', 'Nhãn chỉ số 2',                      'text',     'Giao hàng an toàn'],
            ['home.metric_3_value',     'Trang chủ', 'Chỉ số 3',                           'text',     '24/7'],
            ['home.metric_3_label',     'Trang chủ', 'Nhãn chỉ số 3',                      'text',     'Hỗ trợ chăm sóc'],
            ['home.metric_4_value',     'Trang chủ', 'Chỉ số 4',                           'text',     '30 ngày'],
            ['home.metric_4_label',     'Trang chủ', 'Nhãn chỉ số 4',                      'text',     'Đồng hành cùng cây'],
            ['home.features_kicker',    'Trang chủ', 'Nhãn section về chúng tôi',          'text',     'Về Chúng Tôi'],
            ['home.features_title',     'Trang chủ', 'Tiêu đề section về chúng tôi',       'textarea', 'Chăm sóc từ tâm, xanh tươi không gian sống'],
            ['home.features_lead',      'Trang chủ', 'Mô tả dẫn đầu',                     'textarea', 'Plantify không chỉ bán cây, chúng tôi trao đi nguồn năng lượng chữa lành từ tự nhiên.'],
            ['home.feature_1',          'Trang chủ', 'Điểm mạnh 1',                        'text',     'Cây trồng hữu cơ chuẩn VietGAP'],
            ['home.feature_2',          'Trang chủ', 'Điểm mạnh 2',                        'text',     'Chậu gốm thủ công nghệ thuật'],
            ['home.feature_3',          'Trang chủ', 'Điểm mạnh 3',                        'text',     'Tư vấn phong thủy miễn phí 24/7'],
            ['home.feature_4',          'Trang chủ', 'Điểm mạnh 4',                        'text',     'Bao bì sinh học bảo vệ môi trường'],
            ['home.products_kicker',    'Trang chủ', 'Nhãn section sản phẩm',              'text',     'Bộ Sưu Tập Tuyển Chọn'],
            ['home.products_title',     'Trang chủ', 'Tiêu đề section sản phẩm',           'text',     'Sản Phẩm Nổi Bật'],
            ['home.story_kicker',       'Trang chủ', 'Nhãn câu chuyện',                    'text',     'Câu Chuyện Của Chúng Tôi'],
            ['home.story_title',        'Trang chủ', 'Tiêu đề câu chuyện',                 'textarea', 'Khát khao mang không gian xanh vào cuộc sống hiện đại'],
            ['home.story_p1',           'Trang chủ', 'Đoạn câu chuyện 1',                  'textarea', 'Plantify Co ra đời từ tình yêu với thiên nhiên. Chúng tôi tin rằng, một mầm xanh không chỉ làm đẹp căn phòng mà còn là liệu pháp tinh thần vô giá sau những giờ làm việc căng thẳng.'],
            ['home.story_p2',           'Trang chủ', 'Đoạn câu chuyện 2',                  'textarea', 'Với quy trình tuyển chọn khắt khe từ các nhà vườn uy tín, chúng tôi cam kết mỗi sản phẩm gửi đi đều đạt chất lượng cao nhất.'],
            ['home.cta_title',          'Trang chủ', 'Tiêu đề CTA',                        'textarea', 'Sẵn sàng mang thiên nhiên vào nhà?'],
            ['home.cta_text',           'Trang chủ', 'Mô tả CTA',                          'textarea', 'Đừng ngần ngại liên hệ nếu bạn cần chuyên gia của Plantify tư vấn loại cây phù hợp với không gian và mệnh của mình.'],
            ['home.cta_button',         'Trang chủ', 'Nút CTA',                             'text',     'Bắt Đầu Mua Sắm'],
        ];
 
        $sections = [
            ['title' => 'Hero đầu trang',          'desc' => 'Tiêu đề lớn, mô tả, nút bấm và thẻ thông tin.',      'keys' => ['home.hero_kicker','home.hero_title','home.hero_description','home.hero_btn_primary','home.hero_btn_secondary','home.hero_card_title','home.hero_card_text']],
            ['title' => 'Các chỉ số nổi bật',      'desc' => 'Bốn con số hiển thị ngay dưới hero.',                'keys' => ['home.metric_1_value','home.metric_1_label','home.metric_2_value','home.metric_2_label','home.metric_3_value','home.metric_3_label','home.metric_4_value','home.metric_4_label']],
            ['title' => 'Section "Về chúng tôi"',  'desc' => 'Tiêu đề, mô tả và danh sách điểm mạnh.',            'keys' => ['home.features_kicker','home.features_title','home.features_lead','home.feature_1','home.feature_2','home.feature_3','home.feature_4']],
            ['title' => 'Section Sản phẩm nổi bật','desc' => 'Nhãn và tiêu đề phần sản phẩm featured.',           'keys' => ['home.products_kicker','home.products_title']],
            ['title' => 'Câu chuyện thương hiệu',  'desc' => 'Đoạn nội dung kể về Plantify phía cuối trang.',     'keys' => ['home.story_kicker','home.story_title','home.story_p1','home.story_p2']],
            ['title' => 'CTA cuối trang',           'desc' => 'Khối kêu gọi hành động.',                          'keys' => ['home.cta_title','home.cta_text','home.cta_button']],
        ];
 
        $result = $this->_pageEditorHandle($defaults, 'Trang chủ');
 
        $this->view('admin/page_home', [
            'user'      => Auth::user(),
            'pageTitle' => 'Nội dung Trang chủ',
            'message'   => $result['message'],
            'error'     => $result['error'],
            'byKey'     => $result['byKey'],
            'sections'  => $sections,
        ]);
    }
 
    /**
     * GET/POST /admin/page_news — nội dung trang tin tức
     */
    public function page_news()
    {
        $defaults = [
            ['news.hero_title',          'Trang tin tức', 'Tiêu đề hero',                    'text',     'Tin Tức & Bài Viết'],
            ['news.hero_description',    'Trang tin tức', 'Mô tả hero',                      'textarea', 'Khám phá các bài viết về cây cảnh, phong thủy và xu hướng trang trí xanh.'],
            ['news.search_placeholder',  'Trang tin tức', 'Gợi ý ô tìm kiếm',               'text',     'Tìm kiếm tin tức, bài viết...'],
            ['news.search_button',       'Trang tin tức', 'Nhãn nút tìm kiếm',               'text',     'Tìm kiếm'],
            ['news.empty_title',         'Trang tin tức', 'Thông báo không có kết quả',      'text',     'Không tìm thấy bài viết nào phù hợp!'],
            ['news.prev_label',          'Trang tin tức', 'Nhãn nút trang trước',             'text',     'Trước'],
            ['news.next_label',          'Trang tin tức', 'Nhãn nút trang sau',              'text',     'Sau'],
            ['news.card_readmore',       'Trang tin tức', 'Nhãn nút đọc thêm',               'text',     'Xem chi tiết'],
            ['news.meta_title',          'Trang tin tức', 'Meta title',                      'text',     'Tin Tức | Plantify Co'],
            ['news.meta_description',    'Trang tin tức', 'Meta description',                'textarea', 'Khám phá bài viết về cây cảnh, phong thủy và không gian xanh từ Plantify Co.'],
        ];
 
        $sections = [
            ['title' => 'SEO',                              'desc' => 'Tên tab trình duyệt và mô tả tìm kiếm.',                      'keys' => ['news.meta_title','news.meta_description']],
            ['title' => 'Hero đầu trang',                   'desc' => 'Tiêu đề và mô tả phần banner trên cùng.',                    'keys' => ['news.hero_title','news.hero_description']],
            ['title' => 'Tìm kiếm',                         'desc' => 'Placeholder và nhãn nút tìm kiếm bài viết.',                 'keys' => ['news.search_placeholder','news.search_button']],
            ['title' => 'Thẻ bài viết & phân trang',        'desc' => 'Nhãn nút đọc thêm, trang trước/sau.',                       'keys' => ['news.card_readmore','news.prev_label','news.next_label']],
            ['title' => 'Trạng thái không có kết quả',      'desc' => 'Thông báo hiển thị khi tìm kiếm không ra bài viết.',        'keys' => ['news.empty_title']],
        ];
 
        $result = $this->_pageEditorHandle($defaults, 'Trang tin tức');
 
        $this->view('admin/page_news', [
            'user'      => Auth::user(),
            'pageTitle' => 'Nội dung Trang tin tức',
            'message'   => $result['message'],
            'error'     => $result['error'],
            'byKey'     => $result['byKey'],
            'sections'  => $sections,
        ]);
    }
 
    /**
     * GET/POST /admin/page_faq — nội dung trang FAQ
     */
    public function page_faq()
    {
        $defaults = [
            ['faq.meta_title',             'Trang FAQ', 'Meta title',                          'text',     'FAQ | Câu hỏi thường gặp về cây cảnh và decor xanh'],
            ['faq.meta_description',       'Trang FAQ', 'Meta description',                    'textarea', 'Giải đáp câu hỏi về khảo sát, bảo hành, chăm sóc định kỳ, tư vấn online và dịch vụ cây xanh doanh nghiệp.'],
            ['faq.hero_kicker',            'Trang FAQ', 'Nhãn hero',                           'text',     'FAQ & tư vấn nhanh'],
            ['faq.hero_title',             'Trang FAQ', 'Tiêu đề hero',                        'textarea', 'Câu hỏi thường gặp về cây xanh, decor và chăm sóc định kỳ'],
            ['faq.hero_description',       'Trang FAQ', 'Mô tả hero',                          'textarea', 'Tra cứu nhanh các thông tin quan trọng trước khi khảo sát, chọn cây, nhận báo giá hoặc sử dụng gói chăm sóc sau bàn giao.'],
            ['faq.hero_search_placeholder','Trang FAQ', 'Gợi ý ô tìm kiếm FAQ',               'text',     'Tìm nhanh: bảo hành, khảo sát, gửi ảnh, chăm sóc...'],
            ['faq.hero_card_title',        'Trang FAQ', 'Tiêu đề thẻ hero',                    'text',     'Cần câu trả lời riêng?'],
            ['faq.hero_card_text',         'Trang FAQ', 'Nội dung thẻ hero',                   'textarea', 'Nhấn biểu tượng zalo để liên hệ đội ngũ tư vấn'],
            ['faq.sidebar_kicker',         'Trang FAQ', 'Nhãn sidebar',                        'text',     'Điểm cần biết'],
            ['faq.sidebar_title',          'Trang FAQ', 'Tiêu đề sidebar',                     'text',     'Chuẩn bị trước khi tư vấn'],
            ['faq.sidebar_description',    'Trang FAQ', 'Mô tả sidebar',                       'textarea', 'Thông tin càng rõ, phương án cây xanh càng sát nhu cầu và ngân sách.'],
            ['faq.sidebar_item_1',         'Trang FAQ', 'Gợi ý chuẩn bị 1',                   'text',     'Ảnh tổng thể và góc cần đặt cây'],
            ['faq.sidebar_item_2',         'Trang FAQ', 'Gợi ý chuẩn bị 2',                   'text',     'Thời lượng ánh sáng trong ngày'],
            ['faq.sidebar_item_3',         'Trang FAQ', 'Gợi ý chuẩn bị 3',                   'text',     'Kích thước khu vực dự kiến'],
            ['faq.sidebar_item_4',         'Trang FAQ', 'Gợi ý chuẩn bị 4',                   'text',     'Ngân sách hoặc mức ưu tiên'],
            ['faq.sidebar_cta',            'Trang FAQ', 'Nút CTA sidebar',                     'text',     'Về Plantify'],
            ['faq.steps_kicker',           'Trang FAQ', 'Nhãn section các bước',               'text',     'Sau khi có câu trả lời'],
            ['faq.steps_title',            'Trang FAQ', 'Tiêu đề section các bước',            'text',     'Quy trình tiếp theo rất gọn'],
            ['faq.step_1_title',           'Trang FAQ', 'Tiêu đề bước 1',                      'text',     'Gửi ảnh và nhu cầu'],
            ['faq.step_1_text',            'Trang FAQ', 'Nội dung bước 1',                     'textarea', 'Đính kèm ảnh hiện trạng, phong cách mong muốn và ngân sách dự kiến.'],
            ['faq.step_2_title',           'Trang FAQ', 'Tiêu đề bước 2',                      'text',     'Nhận tư vấn sơ bộ'],
            ['faq.step_2_text',            'Trang FAQ', 'Nội dung bước 2',                     'textarea', 'Plantify đề xuất nhóm cây, kích thước chậu và mức chăm sóc phù hợp.'],
            ['faq.step_3_title',           'Trang FAQ', 'Tiêu đề bước 3',                      'text',     'Chốt lịch khảo sát'],
            ['faq.step_3_text',            'Trang FAQ', 'Nội dung bước 3',                     'textarea', 'Đội ngũ kiểm tra thực tế trước khi báo giá và triển khai chính thức.'],
        ];
 
        $sections = [
            ['title' => 'SEO',                            'desc' => 'Meta title và description.',                                              'keys' => ['faq.meta_title','faq.meta_description']],
            ['title' => 'Hero đầu trang',                 'desc' => 'Tiêu đề, mô tả, ô tìm kiếm và thẻ thông tin bên phải.',               'keys' => ['faq.hero_kicker','faq.hero_title','faq.hero_description','faq.hero_search_placeholder','faq.hero_card_title','faq.hero_card_text']],
            ['title' => 'Sidebar chuẩn bị tư vấn',       'desc' => 'Tiêu đề và danh sách gợi ý chuẩn bị trước khi liên hệ.',              'keys' => ['faq.sidebar_kicker','faq.sidebar_title','faq.sidebar_description','faq.sidebar_item_1','faq.sidebar_item_2','faq.sidebar_item_3','faq.sidebar_item_4','faq.sidebar_cta']],
            ['title' => 'Quy trình 3 bước',               'desc' => 'Section phía dưới accordion FAQ.',                                    'keys' => ['faq.steps_kicker','faq.steps_title','faq.step_1_title','faq.step_1_text','faq.step_2_title','faq.step_2_text','faq.step_3_title','faq.step_3_text']],
        ];
 
        $result = $this->_pageEditorHandle($defaults, 'Trang FAQ');
 
        $this->view('admin/page_faq', [
            'user'      => Auth::user(),
            'pageTitle' => 'Nội dung Trang FAQ',
            'message'   => $result['message'],
            'error'     => $result['error'],
            'byKey'     => $result['byKey'],
            'sections'  => $sections,
        ]);
    }
 

}

<?php
// Location: app/Controllers/AuthController.php
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
<?php

/**
 * Cart Controller
 * Quản lý giỏ hàng và liên kết dữ liệu với bảng `products` trong DB
 * Location: app/Controllers/CartController.php
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
<?php

/**
 * Dashboard Controller
 * Handle member area, profile management and role routing
 * Location: app/Controllers/DashboardController.php
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
<?php
// Location: app/Controllers/FaqController.php
class FaqController extends BaseController
{
    private $db;
    private $dataModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->dataModel = new Data();
    }
    public function index()
    {

        $faqs = $this->dataModel->get_faqs();


        if (!$faqs) {
            $faqs = [];
        }

        $user = Auth::check() ? Auth::user() : null;

        // Truyền $faqs sang View
        $this->view('pages/faq', [
            'user' => $user,
            'faqs' => $faqs
        ]);
    }
}
<?php
// Location: app/Controllers/FileController.php
class FileController extends BaseController
{
    // URL truy cập sẽ là: BASE_URL/file/view?path=uploads/pages/tenanh.jpg
    public function render()
    {
        $path = $_GET['path'] ?? '';
        $filePath = STORAGE_PATH . DIRECTORY_SEPARATOR . $path;

        if (file_exists($filePath) && strpos($filePath, STORAGE_PATH) === 0) {
            $mime = mime_content_type($filePath);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
        http_response_code(404);
        echo "File không tồn tại.";
    }
}
<?php

/**
 * Home Controller
 * Handle homepage and public pages
 * Location: app/Controllers/HomeController.php
 */
class HomeController extends BaseController
{

    /**
     * Homepage - visible to all (guest & members)
     */
    public function index()
    {
        $user = Auth::check() ? Auth::user() : null;
        require_once BASE_PATH . '/app/Models/Product.php';
        $productModel = new Product();
        $featuredProducts = $productModel->getFeatured();
        $this->view('pages/home', ['user' => $user, 'featuredProducts' => $featuredProducts]);
    }
}
<?php

/**
 * NewsController
 * Handles frontend news listing, detail page, and comment submission (Part #4)
 * Location: app/Controllers/NewsController.php
 */
class NewsController extends BaseController
{
    private $newsModel;
    private $commentModel;

    public function __construct()
    {
        $this->newsModel    = new News();
        $this->commentModel = new Comment();
    }

    /**
     * GET /news  — news listing with search + pagination
     */
    public function index()
    {
        $user   = Auth::check() ? Auth::user() : null;
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $total      = $this->newsModel->countPublished($search);
        $newsList   = $this->newsModel->getPublished($page, $search);
        $perPage    = $this->newsModel->getPerPage();
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $dataModel = new Data();
        $company = $dataModel->site_content_all();

        $this->view('news/index', [
            'user'        => $user,
            'newsList'    => $newsList,
            'search'      => $search,
            'currentPage' => $page,
            'company'     => $company,
            'totalPages'  => $totalPages,
            'total'       => $total,
            'extraCss' => [
                'assets/css/news.css'
            ]
        ]);
    }

    /**
     * GET /news/detail/{slug}  — single news detail + comments
     */
    public function detail($slug = null)
    {
        if (!$slug) {
            $this->redirect('news');
            return;
        }

        $user = Auth::check() ? Auth::user() : null;
        $news = $this->newsModel->getBySlug($slug);
        $dataModel = new Data();
        $company = $dataModel->site_content_all();

        if (!$news) {
            // Article not found — show listing with error message
            $this->view('news/index', [
                'user'        => $user,
                'newsList'    => [],
                'search'      => '',
                'currentPage' => 1,
                'totalPages'  => 1,
                'total'       => 0,
                'pageError'   => 'Bài viết không tồn tại hoặc đã bị gỡ xuống!',
            ]);
            return;
        }

        $related      = $this->newsModel->getRelated($news['id'], $news['tags'] ?? '');
        $comments     = $this->commentModel->getByNewsId($news['id']);
        $commentCount = $this->commentModel->countByNewsId($news['id']);

        // Flash messages from session (set after redirect)
        $commentError   = $_SESSION['comment_error']   ?? null;
        $commentSuccess = $_SESSION['comment_success'] ?? null;
        unset($_SESSION['comment_error'], $_SESSION['comment_success']);

        $this->view('news/detail', [
            'user'           => $user,
            'news'           => $news,
            'related'        => $related,
            'comments'       => $comments,
            'commentCount'   => $commentCount,
            'company'        => $company,
            'commentError'   => $commentError,
            'commentSuccess' => $commentSuccess,
            'extraCss' => [
                'assets/css/news.css'
            ]
        ]);
    }

    /**
     * POST /news/comment_post  — submit a comment (requires login)
     */
    public function comment_post()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('news');
            return;
        }

        $slug   = trim($_POST['slug']    ?? '');
        $newsId = (int)($_POST['news_id'] ?? 0);

        // Must be logged in
        if (!Auth::check()) {
            $_SESSION['comment_error'] = 'Bạn cần đăng nhập để bình luận!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }

        $content = trim($_POST['content'] ?? '');

        // Server-side validation
        if (empty($content)) {
            $_SESSION['comment_error'] = 'Nội dung bình luận không được để trống!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }
        if (mb_strlen($content) < 5) {
            $_SESSION['comment_error'] = 'Bình luận phải có ít nhất 5 ký tự!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }
        if (mb_strlen($content) > 1000) {
            $_SESSION['comment_error'] = 'Bình luận không được vượt quá 1000 ký tự!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }

        // XSS protection — strip any HTML tags, encode special chars
        $content = htmlspecialchars(strip_tags($content), ENT_QUOTES, 'UTF-8');

        $ok = $this->commentModel->create([
            'user_id'   => Auth::id(),
            'target_id' => $newsId,
            'content'   => $content,
        ]);

        if ($ok) {
            $_SESSION['comment_success'] = 'Bình luận của bạn đã được gửi và đang chờ duyệt. Cảm ơn bạn!';
        } else {
            $_SESSION['comment_error'] = 'Có lỗi xảy ra, vui lòng thử lại!';
        }

        $this->redirect('news/detail/' . $slug . '#comments');
    }
}
<?php

/**
 * Shop Controller (Phiên bản MVC Chuẩn với Model)
 * Location: app/Controllers/ShopController.php
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
<?php
/**
 * Comment Model
 * Handles all database operations for comments (Part #4)
 * Location: app/Models/Comment.php
 */
class Comment
{
    private $db;
    private $perPage = 10;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get approved comments for a news article (with user info)
     */
    public function getByNewsId($newsId)
    {
        $this->db->query("SELECT c.*, u.username, u.fullname, u.avatar
                          FROM comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.target_id = :nid AND c.target_type = 'news' AND c.status = 'approved'
                          ORDER BY c.created_at ASC");
        $this->db->bind(':nid', (int)$newsId);
        return $this->db->resultSet();
    }

    /**
     * Count approved comments for a news article
     */
    public function countByNewsId($newsId)
    {
        $this->db->query("SELECT COUNT(*) as total FROM comments
                          WHERE target_id = :nid AND target_type = 'news' AND status = 'approved'");
        $this->db->bind(':nid', (int)$newsId);
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Submit a new comment (defaults to 'pending', awaits admin approval)
     */
    public function create($data)
    {
        $this->db->query("INSERT INTO comments (user_id, target_id, target_type, content, status)
                          VALUES (:uid, :tid, 'news', :content, 'pending')");
        $this->db->bind(':uid',     (int)$data['user_id']);
        $this->db->bind(':tid',     (int)$data['target_id']);
        $this->db->bind(':content', $data['content']);
        return $this->db->execute();
    }

    /* ==========================================
       ADMIN METHODS
       ========================================== */

    /**
     * Get all comments (news only) with user + news info, paginated
     */
    public function getAll($page = 1, $search = '')
    {
        $offset = ($page - 1) * $this->perPage;
        if ($search) {
            $this->db->query("SELECT c.*, u.username, u.fullname, n.title as news_title, n.slug as news_slug
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                                AND (c.content LIKE :s1 OR u.username LIKE :s2 OR u.fullname LIKE :s3 OR n.title LIKE :s4)
                              ORDER BY c.created_at DESC LIMIT :lim OFFSET :off");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
            $this->db->bind(':s3', '%' . $search . '%');
            $this->db->bind(':s4', '%' . $search . '%');
        } else {
            $this->db->query("SELECT c.*, u.username, u.fullname, n.title as news_title, n.slug as news_slug
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                              ORDER BY c.created_at DESC LIMIT :lim OFFSET :off");
        }
        $this->db->bind(':lim', $this->perPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count all news comments (for admin pagination)
     */
    public function countAll($search = '')
    {
        if ($search) {
            $this->db->query("SELECT COUNT(*) as total
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                                AND (c.content LIKE :s1 OR u.username LIKE :s2 OR u.fullname LIKE :s3 OR n.title LIKE :s4)");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
            $this->db->bind(':s3', '%' . $search . '%');
            $this->db->bind(':s4', '%' . $search . '%');
        } else {
            $this->db->query("SELECT COUNT(*) as total FROM comments WHERE target_type = 'news'");
        }
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single comment by ID
     */
    public function getById($id)
    {
        $this->db->query("SELECT c.*, u.username, u.fullname FROM comments c
                          JOIN users u ON c.user_id = u.id WHERE c.id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Toggle comment status: approved ↔ hidden
     * Also promotes 'pending' to 'approved' on first toggle
     */
    public function toggleStatus($id)
    {
        $this->db->query("SELECT status FROM comments WHERE id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        $current = $this->db->single();
        if (!$current) return false;

        $newStatus = ($current['status'] === 'approved') ? 'hidden' : 'approved';

        $this->db->query("UPDATE comments SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $this->db->bind(':status', $newStatus);
        $this->db->bind(':id',     (int)$id);
        return $this->db->execute();
    }

    /**
     * Delete a comment by ID
     */
    public function delete($id)
    {
        $this->db->query("DELETE FROM comments WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function getPerPage()
    {
        return $this->perPage;
    }
}
<?php
// Location: app/Models/Content.php
class Content
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllSiteContent()
    {
        $this->db->query("SELECT * FROM site_content ORDER BY content_group, id");

        return $this->db->resultSet();
    }

    public function getAllPages()
    {
        $this->db->query("SELECT * FROM pages ORDER BY slug");
        return $this->db->resultSet();
    }

    public function updateSiteContent($key, $value)
    {
        $this->db->query("UPDATE site_content SET content_value = :val WHERE content_key = :key");
        $this->db->bind(':val', $value);
        $this->db->bind(':key', $key);
        return $this->db->execute();
    }
    public function updateMultipleSiteContent(array $data)
    {
        foreach ($data as $key => $value) {
            $this->updateSiteContent($key, $value);
        }
        return true;
    }

    public function getSiteContentByGroups(array $groups)
    {

        $placeholders = implode(',', array_fill(0, count($groups), '?'));

        $sql = "SELECT * FROM site_content 
                WHERE content_group IN ($placeholders) 
                ORDER BY FIELD(content_group, 'Trang cửa hàng', 'Trang chi tiết SP', 'Trang giỏ hàng'), id ASC";

        $this->db->query($sql);

        foreach ($groups as $index => $group) {
            $this->db->bind($index + 1, $group);
        }

        return $this->db->resultSet();
    }

    public function seedDefaults(array $defaults): void
    {
        foreach ($defaults as $row) {
            $this->db->query(
                "INSERT INTO site_content (content_key, content_group, label, input_type, content_value)
                 VALUES (:k, :g, :l, :t, :v)
                 ON DUPLICATE KEY UPDATE
                     content_group = VALUES(content_group),
                     label         = VALUES(label),
                     input_type    = VALUES(input_type)"
            );
            $this->db->bind(':k', $row[0]);
            $this->db->bind(':g', $row[1]);
            $this->db->bind(':l', $row[2]);
            $this->db->bind(':t', $row[3]);
            $this->db->bind(':v', $row[4]);
            $this->db->execute();
        }
    }
 
    public function getByGroup(string $group): array
    {
        $this->db->query(
            "SELECT * FROM site_content WHERE content_group = :g ORDER BY id"
        );
        $this->db->bind(':g', $group);
        $rows   = $this->db->resultSet();
        $byKey  = [];
        foreach ($rows as $r) {
            $byKey[$r['content_key']] = $r;
        }
        return $byKey;
    }
 
    public function saveByPost(array $postContent): void
    {
        foreach ($postContent as $key => $value) {
            $this->db->query(
                "UPDATE site_content SET content_value = :v WHERE content_key = :k"
            );
            $this->db->bind(':v', trim((string) $value));
            $this->db->bind(':k', (string) $key);
            $this->db->execute();
        }
    }
}
<?php

/**
 * File: includes/data.php
 * Chuc nang: Nap du lieu hien thi tu MySQL, kem fallback de website van chay
 * duoc khi database chua san sang.
 * Location: app/Models/Data.php
 */

class Data
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function fetch_table_rows($table, $orderBy = 'id')
    {
        try {
            $this->db->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
            return $this->db->resultSet(); // Trả về kết quả thông qua resultSet()
        } catch (PDOException $exception) {
            return null;
        }
    }
    public function site_content_all()
    {
        static $content = null;

        if ($content !== null) {
            return $content;
        }

        $content = [];
        $db = $this->db;
        if (!$db) {
            return $content;
        }

        try {
            $db->query('SELECT content_key, content_value FROM site_content');
            $rows = $db->resultSet();

            foreach ($rows as $row) {
                $content[$row['content_key']] = $row['content_value'];
            }
        } catch (PDOException $exception) {
            $content = [];
        }

        return $content;
    }

    public function content_value($key, $default = '')
    {
        $content = $this->site_content_all();
        return array_key_exists($key, $content) && $content[$key] !== '' ? $content[$key] : $default;
    }

    public function get_company()
    {
        $fallback = [
            'name' => 'GreenNest Landscape',
            'tagline' => 'Cây xanh tinh tế cho không gian sống và làm việc',
            'phone' => '0908 246 135',
            'email' => 'hello@greennest.vn',
            'address' => '128 Nguyễn Văn Hưởng, Thảo Điền, TP. Thủ Đức, TP. Hồ Chí Minh',
            'hours' => 'Thứ 2 - Thứ 7: 08:00 - 18:00',
        ];

        return [
            'name' => $this->content_value('company.name', $fallback['name']),
            'tagline' => $this->content_value('company.tagline', $fallback['tagline']),
            'phone' => $this->content_value('company.phone', $fallback['phone']),
            'email' => $this->content_value('company.email', $fallback['email']),
            'address' => $this->content_value('company.address', $fallback['address']),
            'hours' => $this->content_value('company.hours', $fallback['hours']),
        ];
    }

    public function get_services()
    {
        $fallback = [
            [
                'icon' => 'fa-seedling',
                'title' => 'Thiết kế decor cây xanh',
                'description' => 'Khảo sát mặt bằng, tư vấn concept và bố trí cây cảnh phù hợp với văn phòng, nhà mẫu, showroom và căn hộ cao cấp.',
            ],
            [
                'icon' => 'fa-leaf',
                'title' => 'Cung cấp cây nội thất',
                'description' => 'Tuyển chọn cây khỏe, dáng đẹp, chậu phù hợp với phong cách hiện đại, tối giản và sang trọng.',
            ],
            [
                'icon' => 'fa-hand-holding-droplet',
                'title' => 'Chăm sóc định kỳ',
                'description' => 'Bảo dưỡng cây, cắt tỉa, bổ sung dinh dưỡng, xử lý sâu bệnh và thay thế cây theo gói dịch vụ doanh nghiệp.',
            ],
            [
                'icon' => 'fa-tree-city',
                'title' => 'Cảnh quan ban công và sân vườn',
                'description' => 'Thiết kế mảng xanh cho ban công, sân thượng, sân vườn nhỏ với giải pháp tưới và thoát nước an toàn.',
            ],
        ];

        $dbServices = $this->fetch_table_rows('services', 'id');
        return $dbServices ?? $fallback;
    }

    public function get_products()
    {
        $fallback = [
            [
                'name' => 'Bàng Singapore',
                'category' => 'Cây nội thất cao cấp',
                'price' => '1.250.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Tán lá lớn, dáng cây sang, phù hợp sảnh lễ tân, phòng họp và góc sofa.',
            ],
            [
                'name' => 'Monstera Deliciosa',
                'category' => 'Cây decor hiện đại',
                'price' => '780.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Lá xẻ độc đáo, tạo điểm nhấn xanh cho studio, căn hộ và không gian sáng tạo.',
            ],
            [
                'name' => 'Kim Tiền chậu gốm',
                'category' => 'Cây phong thủy',
                'price' => '520.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Dễ chăm sóc, phù hợp bàn làm việc, quầy tiếp tân và quà tặng doanh nghiệp.',
            ],
        ];

        $dbProducts = $this->fetch_table_rows('products', 'is_featured DESC, id');
        if ($dbProducts) {
            return array_map(function ($product) {
                $product['price'] = number_format((float) $product['price'], 0, ',', '.') . ' VND';
                return $product;
            }, $dbProducts);
        }
        return $fallback;
    }

    public function get_faqs()
    {
        $fallback = [
            [
                'question' => 'Plantify có khảo sát trực tiếp trước khi thiết kế không?',
                'answer' => 'Có. Đội ngũ tư vấn sẽ khảo sát ánh sáng, diện tích, luồng gió và phong cách nội thất để đề xuất loại cây, chậu và vị trí phù hợp.',
            ],
            [
                'question' => 'Cây có được bảo hành sau khi bàn giao không?',
                'answer' => 'Tất cả cây trong gói decor doanh nghiệp được theo dõi sức khỏe trong 30 ngày đầu. Gói chăm sóc định kỳ có chính sách thay thế theo hợp đồng.',
            ],
            [
                'question' => 'Tôi có thể gửi ảnh mặt bằng để được tư vấn online không?',
                'answer' => 'Có. Bạn có thể chuẩn bị ảnh tổng thể, kích thước khu vực và điều kiện ánh sáng để đội ngũ tư vấn phân tích phương án phù hợp.',
            ],
            [
                'question' => 'Website có hỗ trợ quản lý sản phẩm bằng MySQL không?',
                'answer' => 'Có. Cấu trúc database có sẵn bảng products, services, faqs và pages để nâng cấp thành hệ thống quản trị nội dung đầy đủ.',
            ],
        ];

        $dbFaqs = $this->fetch_table_rows('faqs', 'sort_order, id');
        return $dbFaqs ?? $fallback;
    }

    public function get_testimonials()
    {
        return [
            [
                'name' => 'Ms. Linh Nguyễn',
                'role' => 'Office Manager, Aster Tech',
                'quote' => 'Plantify thiết kế mảng xanh gọn gàng, đúng tinh thần văn phòng của chúng tôi và chăm sóc cây rất đều.',
            ],
            [
                'name' => 'Mr. Minh Trần',
                'role' => 'Founder, Annam Studio',
                'quote' => 'Đội ngũ tư vấn kỹ về ánh sáng và chất liệu chậu. Không gian studio sau khi decor trông ấm hơn nhưng vẫn rất tinh tế.',
            ],
        ];
    }
}
<?php
/**
 * News Model
 * Handles all database operations for news/articles (Part #4)
 * Location: app/Models/News.php
 */
class News
{
    private $db;
    private $perPage = 9;
    private $adminPerPage = 10;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a URL-friendly slug from a Vietnamese title
     */
    public static function generateSlug($title, $suffix = '')
    {
        $from = ['à','á','ả','ã','ạ','â','ầ','ấ','ẩ','ẫ','ậ','ă','ằ','ắ','ẳ','ẵ','ặ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ','đ','À','Á','Ả','Ã','Ạ','Â','Ầ','Ấ','Ẩ','Ẫ','Ậ','Ă','Ằ','Ắ','Ẳ','Ẵ','Ặ','È','É','Ẻ','Ẽ','Ẹ','Ê','Ề','Ế','Ể','Ễ','Ệ','Ì','Í','Ỉ','Ĩ','Ị','Ò','Ó','Ỏ','Õ','Ọ','Ô','Ồ','Ố','Ổ','Ỗ','Ộ','Ơ','Ờ','Ớ','Ở','Ỡ','Ợ','Ù','Ú','Ủ','Ũ','Ụ','Ư','Ừ','Ứ','Ử','Ữ','Ự','Ỳ','Ý','Ỷ','Ỹ','Ỵ','Đ'];
        $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];

        $slug = str_replace($from, $to, $title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($suffix) $slug .= '-' . $suffix;
        return $slug ?: 'bai-viet-' . time();
    }

    /* ==========================================
       FRONTEND METHODS
       ========================================== */

    /**
     * Get published news with pagination and optional search
     */
    public function getPublished($page = 1, $search = '')
    {
        $offset = ($page - 1) * $this->perPage;
        if ($search) {
            $this->db->query("SELECT * FROM news WHERE status = 'published'
                              AND (title LIKE :s1 OR tags LIKE :s2)
                              ORDER BY created_at DESC LIMIT :lim OFFSET :off");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        } else {
            $this->db->query("SELECT * FROM news WHERE status = 'published'
                              ORDER BY created_at DESC LIMIT :lim OFFSET :off");
        }
        $this->db->bind(':lim', $this->perPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count published news (for pagination)
     */
    public function countPublished($search = '')
    {
        if ($search) {
            $this->db->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'
                              AND (title LIKE :s1 OR tags LIKE :s2)");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        } else {
            $this->db->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'");
        }
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single published news by slug
     */
    public function getBySlug($slug)
    {
        $this->db->query("SELECT * FROM news WHERE slug = :slug AND status = 'published' LIMIT 1");
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    /**
     * Get related news by tags, excluding current article
     */
    public function getRelated($newsId, $tags = '', $limit = 3)
    {
        $tagList = array_filter(array_map('trim', explode(',', $tags ?? '')));
        if (!empty($tagList)) {
            $conditions = implode(' OR ', array_map(function ($tag) {
                return "tags LIKE '%" . str_replace("'", "''", trim($tag)) . "%'";
            }, $tagList));
            $this->db->query("SELECT * FROM news WHERE id != :id AND status = 'published'
                              AND ($conditions) ORDER BY created_at DESC LIMIT :lim");
        } else {
            $this->db->query("SELECT * FROM news WHERE id != :id AND status = 'published'
                              ORDER BY created_at DESC LIMIT :lim");
        }
        $this->db->bind(':id', $newsId);
        $this->db->bind(':lim', $limit);
        return $this->db->resultSet();
    }

    /* ==========================================
       ADMIN METHODS
       ========================================== */

    /**
     * Get all news for admin with pagination, search, and status filter
     */
    public function getAll($page = 1, $search = '', $status = '')
    {
        $offset = ($page - 1) * $this->adminPerPage;
        $where = '1=1';
        if ($search) $where .= ' AND (title LIKE :s1 OR tags LIKE :s2)';
        if ($status) $where .= ' AND status = :status';

        $this->db->query("SELECT * FROM news WHERE $where ORDER BY created_at DESC LIMIT :lim OFFSET :off");
        if ($search) {
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        }
        if ($status) $this->db->bind(':status', $status);
        $this->db->bind(':lim', $this->adminPerPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count all news (for admin pagination)
     */
    public function countAll($search = '', $status = '')
    {
        $where = '1=1';
        if ($search) $where .= ' AND (title LIKE :s1 OR tags LIKE :s2)';
        if ($status) $where .= ' AND status = :status';

        $this->db->query("SELECT COUNT(*) as total FROM news WHERE $where");
        if ($search) {
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        }
        if ($status) $this->db->bind(':status', $status);
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single news by ID (admin)
     */
    public function getById($id)
    {
        $this->db->query("SELECT * FROM news WHERE id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Create a new news article
     * Returns the new ID on success
     */
    public function create($data)
    {
        $this->db->query("INSERT INTO news (title, slug, short_description, content, thumbnail, tags, seo_desc, author, status)
                          VALUES (:title, :slug, :short_desc, :content, :thumbnail, :tags, :seo_desc, :author, :status)");
        $this->db->bind(':title',      $data['title']);
        $this->db->bind(':slug',       $data['slug']);
        $this->db->bind(':short_desc', $data['short_description']);
        $this->db->bind(':content',    $data['content']);
        $this->db->bind(':thumbnail',  $data['thumbnail']);
        $this->db->bind(':tags',       $data['tags']);
        $this->db->bind(':seo_desc',   $data['seo_desc']);
        $this->db->bind(':author',     $data['author']);
        $this->db->bind(':status',     $data['status']);
        $this->db->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update slug after creation (to include the new ID)
     */
    public function updateSlug($id, $slug)
    {
        $this->db->query("UPDATE news SET slug = :slug WHERE id = :id");
        $this->db->bind(':slug', $slug);
        $this->db->bind(':id',   (int)$id);
        return $this->db->execute();
    }

    /**
     * Update an existing news article
     */
    public function update($id, $data)
    {
        $this->db->query("UPDATE news SET title=:title, slug=:slug, short_description=:short_desc,
                          content=:content, thumbnail=:thumbnail, tags=:tags, seo_desc=:seo_desc,
                          author=:author, status=:status, updated_at=CURRENT_TIMESTAMP WHERE id=:id");
        $this->db->bind(':title',      $data['title']);
        $this->db->bind(':slug',       $data['slug']);
        $this->db->bind(':short_desc', $data['short_description']);
        $this->db->bind(':content',    $data['content']);
        $this->db->bind(':thumbnail',  $data['thumbnail']);
        $this->db->bind(':tags',       $data['tags']);
        $this->db->bind(':seo_desc',   $data['seo_desc']);
        $this->db->bind(':author',     $data['author']);
        $this->db->bind(':status',     $data['status']);
        $this->db->bind(':id',         (int)$id);
        return $this->db->execute();
    }

    /**
     * Delete a news article by ID
     */
    public function delete($id)
    {
        $this->db->query("DELETE FROM news WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Get admin per-page count
     */
    public function getAdminPerPage()
    {
        return $this->adminPerPage;
    }

    /**
     * Get frontend per-page count
     */
    public function getPerPage()
    {
        return $this->perPage;
    }
}
<?php
// Location: app/Models/Order.php
class Order
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create($orderData, $cartItems)
    {
        try {
            // Bắt đầu Transaction
            $this->db->beginTransaction();

            // 1. Chèn vào bảng orders
            $this->db->query("INSERT INTO orders (user_id, fullname, phone, address, note, total_price) 
                              VALUES (:user_id, :fullname, :phone, :address, :note, :total_price)");
            $this->db->bind(':user_id', $orderData['user_id']);
            $this->db->bind(':fullname', $orderData['fullname']);
            $this->db->bind(':phone', $orderData['phone']);
            $this->db->bind(':address', $orderData['address']);
            $this->db->bind(':note', $orderData['note']);
            $this->db->bind(':total_price', $orderData['total_price']);
            $this->db->execute();

            $orderId = $this->db->lastInsertId();

            // 2. Chèn từng món vào order_items
            foreach ($cartItems as $item) {
                $this->db->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (:order_id, :product_id, :quantity, :price)");
                $this->db->bind(':order_id', $orderId);
                $this->db->bind(':product_id', $item['product_id']);
                $this->db->bind(':quantity', $item['quantity']);
                $this->db->bind(':price', $item['price']);
                $this->db->execute();
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            // TẠM THỜI IN LỖI RA MÀN HÌNH ĐỂ DEBUG:
            die("Lỗi đặt hàng: " . $e->getMessage());
            // return false;
        }
    }

    public function getOrdersByUserId($userId)
    {
        $this->db->query("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getAllOrders()
    {
        $this->db->query("SELECT o.*, u.fullname as user_name 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
        return $this->db->resultSet();
    }

    public function getOrderDetail($orderId)
    {
        $this->db->query("SELECT o.*, u.email as user_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = :id");
        $this->db->bind(':id', $orderId);
        $order = $this->db->single();

        if ($order) {

            $this->db->query("SELECT oi.*, p.name, p.image 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = :oid");
            $this->db->bind(':oid', $orderId);
            $order['items'] = $this->db->resultSet();
        }
        return $order;
    }

    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE orders SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
<?php
// Location: app/Models/Product.php
class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById($id)
    {
        $this->db->query("SELECT * FROM products WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getPaginated($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $this->db->query("SELECT * FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset");
        return $this->db->resultSet();
    }

    public function getFilteredProducts($limit, $offset, $category = 'all', $sort = 'newest', $search = '')
    {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];

        // 1. Xử lý tìm kiếm (Tìm theo tên hoặc mô tả)
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // 2. Xử lý lọc theo danh mục
        if ($category !== 'all') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        // 3. Xử lý sắp xếp
        if ($sort === 'price_asc') {
            $sql .= " ORDER BY price ASC";
        } elseif ($sort === 'price_desc') {
            $sql .= " ORDER BY price DESC";
        } else {
            $sql .= " ORDER BY id DESC"; // Mới nhất
        }

        // 4. Phân trang
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $this->db->query($sql);

        // Bind các tham số an toàn (Tránh SQL Injection)
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số sản phẩm sau khi lọc (Dùng để chia số trang)
     */
    public function countFilteredProducts($category = 'all', $search = '')
    {
        $sql = "SELECT COUNT(id) as total FROM products WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($category !== 'all') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $this->db->query($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    public function countAll()
    {
        $this->db->query("SELECT COUNT(id) as total FROM products");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    public function getRelated($exclude_id, $limit = 4)
    {
        $limit = (int)$limit;
        $this->db->query("SELECT * FROM products WHERE id != :id ORDER BY RAND() LIMIT $limit");
        $this->db->bind(':id', $exclude_id);
        return $this->db->resultSet();
    }

    public function getFeatured($limit = 4)
    {
        $limit = (int)$limit;
        $this->db->query("SELECT * FROM products WHERE is_featured = 1 ORDER BY id DESC LIMIT $limit");
        return $this->db->resultSet();
    }

    public function getAllProducts()
    {
        $this->db->query("SELECT * FROM products ORDER BY id DESC");
        return $this->db->resultSet();
    }

    public function create($data)
    {
        $this->db->query("INSERT INTO products (name, category, price, image, description, is_featured) 
                          VALUES (:name, :category, :price, :image, :description, :is_featured)");

        $this->db->bind(':name', $data['name']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':is_featured', $data['is_featured'] ?? 0);

        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $this->db->query("UPDATE products 
                          SET name = :name, category = :category, price = :price, 
                              image = :image, description = :description, is_featured = :is_featured 
                          WHERE id = :id");

        $this->db->bind(':id', $id);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':is_featured', $data['is_featured'] ?? 0);

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM products WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
<?php
// Location: app/Models/User.php
class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername($username)
    {
        $this->db->query("SELECT * FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $username);
        return $this->db->single();
    }

    public function findByEmail($email)
    {
        $this->db->query("SELECT * FROM users WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    /**
     * Find user by username or email (for login)
     */
    public function findByUsernameOrEmail($username_or_email)
    {
        $this->db->query("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
        $this->db->bind(':username', $username_or_email);
        $this->db->bind(':email', $username_or_email);
        return $this->db->single();
    }

    public function findById($id)
    {
        $this->db->query("SELECT * FROM users WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function register($data)
    {
        $this->db->query("INSERT INTO users (username, password, email, fullname, role) VALUES (:username, :password, :email, :fullname, :role)");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':fullname', $data['fullname']);
        $this->db->bind(':role', 'member');

        return $this->db->execute();
    }

    // Lấy toàn bộ người dùng
    public function getAllUsers()
    {
        $this->db->query("SELECT * FROM users ORDER BY id DESC");
        return $this->db->resultSet();
    }

    // Khoá / Mở khoá người dùng
    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE users SET status = :status WHERE id = :id AND role != 'admin'");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Reset mật khẩu về 123456
    public function resetPassword($id, $newPasswordHash)
    {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $newPasswordHash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Xóa người dùng
    public function deleteUser($id)
    {
        $this->db->query("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Cập nhật thông tin profile (Tên và Avatar)
     **/
    public function updateProfile($id, $fullname, $avatar)
    {
        $this->db->query("UPDATE users SET fullname = :fullname, avatar = :avatar WHERE id = :id");
        $this->db->bind(':fullname', $fullname);
        $this->db->bind(':avatar', $avatar);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function updatePassword($id, $newPasswordHash)
    {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $newPasswordHash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getPaginated($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $this->db->query("SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset");
        return $this->db->resultSet();
    }
    public function countAll()
    {
        $this->db->query("SELECT COUNT(id) as total FROM users");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }
    public function create($data)
    {
        $this->db->query("INSERT INTO users (username, fullname, email, password, role, status) 
                          VALUES (:username, :fullname, :email, :password, :role, :status)");

        $this->db->bind(':username', $data['username']);
        $this->db->bind(':fullname', $data['fullname']);
        $this->db->bind(':email',    $data['email']);
        $this->db->bind(':password', $data['password']); // Lưu ý: Password đã được mã hóa (hash) ở Controller
        $this->db->bind(':role',     $data['role']);
        $this->db->bind(':status',   $data['status'] ?? 1);

        return $this->db->execute();
    }
}
CREATE DATABASE IF NOT EXISTS plantify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plantify;
-- Location: database/migrations/schema.sql
-- Xóa bảng cũ trước (đúng thứ tự để tránh lỗi khóa ngoại)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS faqs;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS site_content;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    status ENUM('active', 'locked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(120) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  description TEXT NOT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    total_price DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    short_description TEXT,
    content TEXT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    seo_desc VARCHAR(255) DEFAULT NULL,
    author VARCHAR(100) DEFAULT 'Admin',
    status ENUM('published', 'draft', 'hidden') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_id INT NOT NULL,
    target_type ENUM('product', 'news') NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'hidden') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  icon VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_content (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_key VARCHAR(120) NOT NULL UNIQUE,
  content_group VARCHAR(80) NOT NULL DEFAULT 'general',
  label VARCHAR(180) NOT NULL,
  input_type ENUM('text','textarea','url') NOT NULL DEFAULT 'text',
  content_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO services (title, icon, description) VALUES
('Thiết kế decor cây xanh', 'fa-seedling', 'Khảo sát mặt bằng, tư vấn concept và bố trí cây cảnh cho văn phòng, nhà mẫu, showroom.'),
('Cung cấp cây nội thất', 'fa-leaf', 'Tuyển chọn cây khỏe, dáng đẹp, chậu phù hợp với phong cách hiện đại.'),
('Chăm sóc định kỳ', 'fa-hand-holding-droplet', 'Bảo dưỡng cây, cắt tỉa, bổ sung dinh dưỡng và xử lý sâu bệnh theo lịch.');

INSERT INTO `products` (`id`, `name`, `category`, `price`, `image`, `description`, `is_featured`, `created_at`) VALUES
(1, 'Bàng Singapore', 'Sàn nhà', 1250000.00, 'assets/uploads/products/prod_1778752748_6a059cec10240.jpeg', 'Tán lá lớn, dáng cây sang, phù hợp sảnh lễ tân, phòng họp và góc sofa.', 1, '2026-05-12 14:28:15'),
(2, 'Monstera Deliciosa', 'Để bàn', 780000.00, 'assets/uploads/products/prod_1778748874_6a058dca2eff3.jpeg', 'Lá xẻ độc đáo, tạo điểm nhấn xanh cho studio, căn hộ và không gian sáng tạo.', 1, '2026-05-12 14:28:15'),
(3, 'Kim Tiền chậu gốm', 'Để bàn', 520000.00, 'assets/uploads/products/prod_1778752677_6a059ca5ce344.jpeg', 'Dễ chăm sóc, phù hợp bàn làm việc, quầy tiếp tân và quà tặng doanh nghiệp.', 1, '2026-05-12 14:28:15'),
(4, 'Cây Lưỡi Hổ', 'Để bàn', 150000.00, 'assets/uploads/products/prod_1778752628_6a059c74a02dc.jpeg', 'Thanh lọc không khí tuyệt vời, đặc biệt vào ban đêm. Rất dễ chăm sóc.', 1, '2026-05-14 05:58:06'),
(5, 'Cây Bàng Cẩm Thạch', 'Sàn nhà', 950000.00, 'assets/uploads/products/prod_1778752710_6a059cc647122.jpeg', 'Lá có vân trắng xanh đẹp mắt, mang lại vẻ đẹp thanh lịch cho không gian.', 0, '2026-05-14 05:58:06'),
(6, 'Cây Lan Ý', 'Để bàn', 180000.00, 'assets/uploads/products/prod_1778752648_6a059c88a0cf1.jpeg', 'Hoa trắng tinh khôi, hút tia bức xạ từ máy tính hiệu quả.', 1, '2026-05-14 05:58:06'),
(7, 'Thiết Mộc Lan', 'Sàn nhà', 650000.00, 'assets/uploads/products/prod_1778752421_6a059ba5b45d8.jpeg', 'Biểu tượng của sự may mắn, phát tài. Thích hợp đặt ở góc phòng khách.', 1, '2026-05-14 05:58:06'),
(8, 'Sen Đá Mix Chậu Đất Nung', 'Để bàn', 120000.00, 'assets/uploads/products/prod_1778752382_6a059b7ea3bd1.jpeg', 'Tổng hợp các loại sen đá nhỏ xinh, thích hợp trang trí bàn học, bàn làm việc.', 0, '2026-05-14 05:58:06'),
(9, 'Trầu Bà Đế Vương Đỏ', 'Để bàn', 220000.00, 'assets/uploads/products/prod_1778752591_6a059c4f7c2a2.jpeg', 'Sắc đỏ tía quyền lực, mang lại uy phong cho nhà quản lý, lãnh đạo.', 1, '2026-05-14 05:58:06'),
(10, 'Cây Hạnh Phúc', 'Sàn nhà', 1100000.00, 'assets/uploads/products/prod_1778752581_6a059c4553c6e.jpeg', 'Lá xanh mướt, dáng cây cao ráo, mang ý nghĩa gia đình đầm ấm, hạnh phúc.', 1, '2026-05-14 05:58:06'),
(11, 'Dây Cúc Tần Ấn Độ', 'Ban công', 80000.00, 'assets/uploads/products/prod_1778752574_6a059c3e6c316.jpeg', 'Loài cây rủ che nắng ban công cực tốt, tạo bức rèm xanh mát mắt.', 0, '2026-05-14 05:58:06'),
(12, 'Nha Đam Mini', 'Để bàn', 95000.00, 'assets/uploads/products/prod_1778752565_6a059c35cb854.jpeg', 'Vừa làm kiểng vừa có thể dùng để làm đẹp, thanh lọc không khí.', 0, '2026-05-14 05:58:06'),
(13, 'Phát Tài Núi', 'Sàn nhà', 1450000.00, 'assets/uploads/products/prod_1778752558_6a059c2e73f15.jpeg', 'Dáng dấp uốn lượn tự nhiên, tạo điểm nhấn nghệ thuật cho không gian rộng.', 1, '2026-05-14 05:58:06'),
(14, 'Cây Kim Ngân', 'Để bàn', 250000.00, 'assets/uploads/products/prod_1778752550_6a059c26482b4.jpeg', 'Thân bím đuôi sam độc đáo, thu hút tài lộc cho gia chủ.', 1, '2026-05-14 05:58:06'),
(15, 'Dạ Yến Thảo', 'Ban công', 150000.00, 'assets/uploads/products/prod_1778752396_6a059b8c802d0.jpeg', 'Hoa nở quanh năm với nhiều màu sắc rực rỡ, thích hợp treo ban công.', 1, '2026-05-14 05:58:06'),
(16, 'Thường Xuân', 'Ban công', 130000.00, 'assets/uploads/products/prod_1778752362_6a059b6a4310d.jpeg', 'Sức sống mãnh liệt, lọc khí độc tốt, phù hợp treo ban công hoặc cửa sổ.', 0, '2026-05-14 05:58:06'),
(17, 'Bạch Mã Hoàng Tử', 'Sàn nhà', 580000.00, 'assets/uploads/products/prod_1778752353_6a059b61b99b7.jpeg', 'Gân lá màu trắng nổi bật, mang lại sự sang trọng và thanh thoát.', 0, '2026-05-14 05:58:06'),
(18, 'Xương Rồng Tai Thỏ', 'Để bàn', 110000.00, 'assets/uploads/products/prod_1778752343_6a059b5723fdf.jpeg', 'Hình dáng đáng yêu, chịu hạn tốt, phù hợp với người bận rộn.', 0, '2026-05-14 05:58:06');

INSERT INTO faqs (question, answer, sort_order) VALUES
('Plantify có khảo sát trực tiếp trước khi thiết kế không?', 'Có. Đội ngũ tư vấn sẽ khảo sát ánh sáng, diện tích, luồng gió và phong cách nội thất để đề xuất loại cây, chậu và vị trí phù hợp.', 1),
('Cây có được bảo hành sau khi bàn giao không?', 'Tất cả cây trong gói decor doanh nghiệp được theo dõi sức khỏe trong 30 ngày đầu. Gói chăm sóc định kỳ có chính sách thay thế theo hợp đồng.', 2),
('Tôi có thể gửi ảnh mặt bằng để được tư vấn online không?', 'Có. Bạn có thể chuẩn bị ảnh tổng thể, kích thước khu vực và điều kiện ánh sáng để đội ngũ tư vấn phân tích phương án phù hợp.', 3),
('Plantify có dịch vụ chăm sóc cây định kỳ hàng tháng không?', 'Có, chúng tôi cung cấp gói bảo dưỡng định kỳ bao gồm tưới nước, bón phân, lau lá, cắt tỉa và phòng trừ sâu bệnh để không gian xanh của bạn luôn tươi tốt mà không tốn thời gian chăm sóc.', 4),
('Tôi là người bận rộn và không rành về cây, sợ mua về sẽ bị chết?', 'Đừng lo lắng! Khi bàn giao, Plantify sẽ ưu tiên tư vấn các dòng cây dễ sống, bền bỉ trong môi trường máy lạnh. Mỗi cây đều có thẻ hướng dẫn chi tiết và chúng tôi luôn hỗ trợ giải đáp online 24/7.', 5),
('Công ty có xuất hóa đơn VAT cho khách hàng doanh nghiệp không?', 'Có. Plantify cung cấp đầy đủ hợp đồng, báo giá minh bạch và xuất hóa đơn VAT điện tử hợp lệ, nhanh chóng cho các đối tác doanh nghiệp.', 6),
('Bao lâu thì Plantify hoàn thiện việc setup decor cây xanh?', 'Với văn phòng hoặc căn hộ vừa và nhỏ, thời gian thi công thường chỉ từ 2-4 ngày sau khi chốt phương án. Các dự án lớn hơn sẽ có bảng tiến độ triển khai chi tiết đi kèm.', 7),
('Tôi nuôi chó/mèo trong nhà, Plantify có tư vấn cây an toàn không?', 'Chắc chắn rồi. Bạn chỉ cần báo trước về việc không gian có thú cưng hoặc trẻ nhỏ, chúng tôi sẽ chọn lọc những dòng cây hoàn toàn không có độc tính (như đuôi công, dương xỉ, lan ý...) để đảm bảo an toàn tuyệt đối.', 8),
('Plantify có dịch vụ cho thuê cây xanh văn phòng không?', 'Có. Với gói thuê cây, doanh nghiệp không cần lo chi phí đầu tư ban đầu hay rủi ro cây héo úa. Plantify sẽ đến chăm sóc hàng tuần và luân phiên đổi cây mới để duy trì hình ảnh chuyên nghiệp cho văn phòng.', 9),
('Tôi có thể chọn loại chậu khác không, hay phải lấy chậu như mẫu?', 'Bạn hoàn toàn có quyền thay đổi! Chúng tôi có kho chậu đa dạng chất liệu (đá mài, gốm sứ, composite...). Nhân viên sẽ hỗ trợ bạn phối cây vào chậu sao cho hợp với tone màu nội thất nhất.', 10),
('Phí giao hàng và lắp đặt tận nơi được tính như thế nào?', 'Plantify miễn phí vận chuyển và setup tận nơi cho đơn hàng từ 1.500.000đ trong nội thành TP.HCM. Với các khu vực ngoại thành hoặc đơn hàng nhỏ hơn, phí ship sẽ được tính sát giá thực tế của dịch vụ giao hàng an toàn.', 11);

INSERT INTO pages (slug, title, content, image) VALUES
('about', 'Giới thiệu Plantify Co', 'Plantify Co là công ty chuyên thiết kế và cung cấp giải pháp cây xanh cho không gian doanh nghiệp. Chúng tôi kết hợp thẩm mỹ, khoa học và dịch vụ để mang thiên nhiên vào văn phòng, showroom và căn hộ cao cấp.', 'assets/uploads/pages/about-20260514-063927-1ffd56a6.jpeg')
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), image = VALUES(image);

INSERT INTO site_content (content_key, content_group, label, input_type, content_value) VALUES
('company.name', 'Công ty', 'Tên thương hiệu', 'text', 'Plantify Co'),
('company.tagline', 'Công ty', 'Khẩu hiệu', 'text', 'Cây xanh tinh tế cho không gian sống và làm việc'),
('company.phone', 'Công ty', 'Số điện thoại', 'text', '0787 309 225'),
('company.email', 'Công ty', 'Email', 'text', 'thai.lebachkhoa@hcmut.edu.vn'),
('company.address', 'Công ty', 'Địa chỉ', 'text', '268, Lý Thường Kiệt, Phường 14, Quận 10, TP. Hồ Chí Minh'),
('company.hours', 'Công ty', 'Giờ làm việc', 'text', 'Thứ 2 - Thứ 7: 08:00 - 18:00'),
('site.default_description', 'SEO', 'Mô tả mặc định', 'textarea', 'Website giới thiệu công ty cây cảnh, cây xanh và decor thiên nhiên cho văn phòng, showroom.'),
('about.hero_video', 'Trang giới thiệu', 'Video nền đầu trang giới thiệu', 'text', 'assets/videos/about/about-hero-20260514_063453.m3u8'),
('nav.about', 'Điều hướng', 'Menu giới thiệu', 'text', 'Giới thiệu'),
('nav.faq', 'Điều hướng', 'Menu FAQ', 'text', 'FAQ'),
('nav.toggle', 'Điều hướng', 'Nhãn mở menu mobile', 'text', 'Mở menu'),
('footer.description', 'Footer', 'Mô tả footer', 'textarea', 'Chúng tôi mang cây xanh vào không gian sống và làm việc bằng giải pháp tinh gọn, bền vững.'),
('footer.info_title', 'Footer', 'Tiêu đề thông tin', 'text', 'Thông tin'),
('footer.nav_title', 'Footer', 'Tiêu đề điều hướng', 'text', 'Điều hướng')
ON DUPLICATE KEY UPDATE
  content_group = VALUES(content_group),
  label = VALUES(label),
  input_type = VALUES(input_type),
  content_value = VALUES(content_value);

INSERT INTO site_content (content_key, content_group, label, input_type, content_value) VALUES
('product.btn_add_to_cart', 'Trang chi tiết SP', 'Nút thêm vào giỏ', 'text', 'Thêm vào giỏ'),
('product.btn_buy_now', 'Trang chi tiết SP', 'Nút mua ngay', 'text', 'Mua ngay'),
('product.trust_badge_1', 'Trang chi tiết SP', 'Cam kết 1', 'text', 'Giao hàng nhanh 2H'),
('product.trust_badge_2', 'Trang chi tiết SP', 'Cam kết 2', 'text', 'Thanh toán an toàn'),
('product.trust_badge_3', 'Trang chi tiết SP', 'Cam kết 3', 'text', '1 đổi 1 trong 3 ngày'),
('product.related_title', 'Trang chi tiết SP', 'Tiêu đề SP liên quan', 'text', 'Có thể bạn cũng thích'),
('shop.hero_title', 'Trang cửa hàng', 'Tiêu đề Hero', 'text', 'Cửa Hàng Xanh'),
('shop.hero_description', 'Trang cửa hàng', 'Mô tả Hero', 'textarea', 'Khám phá bộ sưu tập cây xanh được tuyển chọn để làm mới không gian sống của bạn.'),
('shop.search_placeholder', 'Trang cửa hàng', 'Gợi ý tìm kiếm', 'text', 'Tìm kiếm cây bạn yêu thích...'),
('shop.sort_label', 'Trang cửa hàng', 'Nhãn sắp xếp', 'text', 'Sắp xếp:'),
('shop.empty_title', 'Trang cửa hàng', 'Tiêu đề khi không có hàng', 'text', 'Không tìm thấy cây nào phù hợp'),
('shop.empty_text', 'Trang cửa hàng', 'Mô tả khi không có hàng', 'text', 'Vui lòng thử từ khóa khác hoặc xóa bộ lọc.'),

ON DUPLICATE KEY UPDATE
  content_group = VALUES(content_group),
  label = VALUES(label),
  input_type = VALUES(input_type),
  content_value = VALUES(content_value);

--

CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT
);


-- Tài khoản mặc định (password: 123456)
INSERT IGNORE INTO users (username, password, email, fullname, role) VALUES 
('admin', '$2y$10$85RR.k4boZvRpouPtxFkY.yURRPvHwoZe5F/8JrzQehuqyqllBZwS', 'admin@localhost.com', 'Admin System', 'admin'),
('thanhvien', '$2y$10$85RR.k4boZvRpouPtxFkY.yURRPvHwoZe5F/8JrzQehuqyqllBZwS', 'member@localhost.com', 'Thành Viên Demo', 'member');

-- Dữ liệu mẫu bài viết 
INSERT IGNORE INTO news (title, slug, short_description, content, thumbnail, tags, seo_desc, author, status) VALUES
(
    'Top 5 Cây Cảnh Phong Thủy Mang Lại May Mắn',
    'top-5-cay-canh-phong-thuy-may-man-1',
    'Khám phá 5 loại cây cảnh được các chuyên gia phong thủy khuyên dùng để thu hút tài lộc và may mắn cho gia đình bạn.',
    '<p>Cây cảnh phong thủy không chỉ tô điểm không gian sống mà còn mang ý nghĩa tâm linh sâu sắc theo quan niệm phương Đông.</p><h2>1. Cây Kim Tiền</h2><p>Cây kim tiền (Crassula ovata) tượng trưng cho tiền bạc và thịnh vượng. Đặt cây ở góc đông nam của ngôi nhà để kích hoạt năng lượng tài lộc.</p><h2>2. Cây Trầu Bà</h2><p>Trầu bà có khả năng lọc không khí tuyệt vời, đồng thời mang lại sinh khí và sức sống cho không gian sống.</p><h2>3. Cây Phát Tài</h2><p>Với thân cây xoắn đặc trưng, cây phát tài (Pachira aquatica) được coi là biểu tượng của may mắn và thịnh vượng.</p>',
    NULL,
    'phong thủy,may mắn,cây cảnh,tài lộc',
    'Top 5 cây cảnh phong thủy giúp thu hút may mắn và tài lộc cho gia đình',
    'Admin',
    'published'
),
(
    'Cách Chăm Sóc Cây Cảnh Trong Nhà Đúng Cách',
    'cach-cham-soc-cay-canh-trong-nha-dung-cach-2',
    'Hướng dẫn chi tiết cách tưới nước, bón phân và đặt vị trí phù hợp để cây cảnh trong nhà luôn xanh tốt.',
    '<p>Chăm sóc cây cảnh trong nhà đòi hỏi sự kiên nhẫn và kiến thức cơ bản về nhu cầu của từng loại cây.</p><h2>Tưới nước đúng cách</h2><p>Kiểm tra độ ẩm đất trước khi tưới bằng cách cắm ngón tay vào đất khoảng 2-3cm. Nếu đất còn ẩm, chưa cần tưới.</p><h2>Ánh sáng</h2><p>Hầu hết cây cảnh trong nhà cần ánh sáng gián tiếp. Đặt cây gần cửa sổ nhưng tránh ánh nắng trực tiếp có thể làm cháy lá.</p><h2>Bón phân</h2><p>Bón phân 2 tuần/lần trong mùa sinh trưởng (xuân-hè) bằng phân hòa tan loãng.</p>',
    NULL,
    'chăm sóc cây,tưới nước,bón phân,cây trong nhà',
    'Hướng dẫn chăm sóc cây cảnh trong nhà đúng cách để cây luôn xanh tốt',
    'Admin',
    'published'
),
(
    'Xu Hướng Cây Cảnh 2026: Mini Garden Trong Căn Hộ',
    'xu-huong-cay-canh-2026-mini-garden-trong-can-ho-3',
    'Mini garden đang trở thành xu hướng hot nhất năm 2026, giúp người thành thị kết nối với thiên nhiên ngay tại căn hộ.',
    '<p>Trong nhịp sống đô thị hối hả, mini garden mang đến không gian xanh mát ngay tại nhà cho người yêu thiên nhiên.</p><h2>Mini garden là gì?</h2><p>Mini garden là vườn thu nhỏ được thiết kế trong không gian nhỏ như ban công, góc phòng hoặc windowsill.</p><h2>Các loại cây phù hợp</h2><p>Succulent, xương rồng mini, cỏ nhật, dương xỉ nhỏ và các loại herb như húng quế, bạc hà rất phù hợp cho mini garden.</p>',
    NULL,
    'mini garden,xu hướng 2026,căn hộ,không gian xanh',
    'Xu hướng mini garden 2026 - tạo không gian xanh trong căn hộ nhỏ',
    'Admin',
    'published'
),
(
    'Gợi Ý 10 Loại Cây Lọc Không Khí Tốt Nhất',
    'goi-y-10-loai-cay-loc-khong-khi-tot-nhat-4',
    'NASA đã nghiên cứu và chứng minh 10 loại cây này có khả năng lọc các chất độc hại trong không khí nhà bạn.',
    '<p>Nghiên cứu của NASA đã chỉ ra rằng một số loại cây cảnh có khả năng lọc các chất độc hại như formaldehyde, benzene và carbon monoxide.</p><h2>Top cây lọc không khí</h2><ul><li><strong>Cây lưỡi hổ</strong> - Lọc formaldehyde và trichloroethylene</li><li><strong>Trầu bà</strong> - Hiệu quả với benzene và CO</li><li><strong>Cây hòa bình</strong> - Loại bỏ nhiều chất độc hại</li><li><strong>Dracaena</strong> - Lọc xylene và toluene</li></ul>',
    NULL,
    'lọc không khí,NASA,cây xanh,sức khỏe',
    '10 loại cây lọc không khí tốt nhất được NASA nghiên cứu và khuyên dùng',
    'Admin',
    'published'
),
(
    'Cây Cảnh Văn Phòng: Tăng Năng Suất Làm Việc',
    'cay-canh-van-phong-tang-nang-suat-lam-viec-5',
    'Nghiên cứu khoa học chứng minh đặt cây xanh trong văn phòng giúp tăng năng suất lên 15% và giảm stress hiệu quả.',
    '<p>Môi trường làm việc xanh không chỉ đẹp mắt mà còn tác động tích cực đến hiệu suất và tâm lý nhân viên.</p><h2>Lợi ích của cây văn phòng</h2><p>Theo nghiên cứu của Đại học Exeter, cây xanh trong văn phòng giúp tăng năng suất lên 15%, tăng sự sáng tạo và giảm mức độ stress đáng kể.</p><h2>Cây phù hợp cho văn phòng</h2><p>Lựa chọn các loại cây chịu ánh sáng yếu, ít cần chăm sóc như lưỡi hổ, cactus mini, pothos và ZZ plant.</p>',
    NULL,
    'cây văn phòng,năng suất,stress,làm việc',
    'Cây cảnh văn phòng giúp tăng năng suất và giảm stress cho nhân viên',
    'Admin',
    'published'
);
-- fix bug
<?php

/**
 * ============================================================================
 * ENTRY POINT - Điểm khởi đầu của ứng dụng web
 * ============================================================================
 * File này là router chính của ứng dụng, xử lý:
 * 1. Khởi tạo session và environment
 * 2. Parse URL để trích xuất controller và method
 * 3. Tự động load các class cần thiết
 * 4. Gọi controller và method tương ứng
 */

// lOcation: public/index.php

session_start();

// ========== LOAD ENVIRONMENT VARIABLES ==========
// Require file Env.php để có sẵn các phương thức load biến môi trường
require_once __DIR__ . '/../app/Core/Env.php';

Env::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/Core/Bootstrap.php';



$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';


$host = $_SERVER['HTTP_HOST'] ?? 'localhost';


$script = dirname($_SERVER['SCRIPT_NAME']);


$base_url = $protocol . '://' . $host . ($script === '\\' || $script === '/' ? '' : $script);

define('BASE_URL', rtrim($base_url, '/'));



$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// Vệ sinh dữ liệu URL để tránh các kiểu tấn công
$url = filter_var($url, FILTER_SANITIZE_URL);

$url = explode('/', $url);

// ========== TRÍCH XUẤT CONTROLLER VÀ METHOD TỪ URL ==========
// Quy ước routing:
// - Phần tử [0]: tên controller (ví dụ: "product" => "ProductController")
// - Phần tử [1]: tên method (ví dụ: "detail" => detail())
// - Phần tử [2+]: các tham số (ví dụ: "5" => $id = 5)

// Lấy tên controller từ phần tử đầu tiên của URL
// Nếu URL trống hoặc không có controller, mặc định là "HomeController"
// ucfirst() viết hoa ký tự đầu tiên (product => Product)
// Cộng thêm "Controller" để tạo tên class (Product => ProductController)
$controllerName = isset($url[0]) && $url[0] != '' ? ucfirst($url[0]) . 'Controller' : 'HomeController';

$controllerName = str_replace('-', '_', $controllerName);
// VALIDATE controller name: chỉ cho phép chữ cái, số, dấu gạch dưới
$controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);

// Lấy tên method từ phần tử thứ hai của URL
$methodName = isset($url[1]) && $url[1] != '' ? $url[1] : 'index';

$methodName = str_replace('-', '_', $methodName);

// VALIDATE method name: chỉ cho phép chữ cái, số, dấu gạch dưới
$methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);

// ========== AUTOLOADER - TỰ ĐỘNG LOAD CLASS ==========
// spl_autoload_register() đăng ký một hàm để tự động load class
// Khi dùng "new ProductController()", PHP sẽ tự động gọi hàm này
// Tránh phải require_once từng class một
spl_autoload_register(function ($class) {

    // Kiểm tra này tránh việc load lặp lại
    if (class_exists($class)) {
        return;
    }

    // Định nghĩa các thư mục nơi class có thể nằm
    // Thứ tự tìm kiếm: Controllers => Core => Models
    $paths = [
        __DIR__ . '/../app/Controllers/' . $class . '.php',    // Controller classes
        __DIR__ . '/../app/Core/' . $class . '.php',           // Core/Library classes
        __DIR__ . '/../app/Models/' . $class . '.php'          // Model/Database classes
    ];

    // Duyệt từng đường dẫn để tìm file chứa class
    foreach ($paths as $path) {

        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});


try {
    // ========== Kiểm tra Controller có tồn tại ==========
    if (!class_exists($controllerName)) {

        error_log("Controller not found: $controllerName");
        http_response_code(404);
        echo "Lỗi 404: Controller '$controllerName' không tồn tại.";
        exit;
    }

    // ========== Khởi tạo instance của Controller ==========
    $controller = new $controllerName();

    // ========== Kiểm tra Method có tồn tại trong Controller ==========
    if (!method_exists($controller, $methodName)) {
        error_log("Method not found: $methodName in $controllerName");
        http_response_code(404);
        echo "Lỗi 404: Method '$methodName' không tồn tại trong '$controllerName'.";
        exit;
    }

    // ========== EXTRACT CÁC THAM SỐ TỪ URL ==========
    // Các phần tử [0] và [1] của $url là controller và method
    // Các phần tử còn lại [2, 3, 4...] là các tham số
    unset($url[0]);
    unset($url[1]);

    // Giữ lại các phần tử còn lại làm các tham số truyền vào method
    $params = $url ? array_values($url) : [];

    // ========== GỌI CONTROLLER METHOD VỚI CÁC THAM SỐ ==========
    call_user_func_array([$controller, $methodName], $params);
} catch (Exception $e) {
    // ========== XỬ LÝ EXCEPTION ==========
    error_log($e->getMessage());
    http_response_code(500);
    echo "Lỗi 500: Đã xảy ra lỗi trên server. Vui lòng thử lại sau.";
    exit;
}
.htaccess in public folder
Options -Indexes 

php_value upload_max_filesize 512M
php_value post_max_size 600M
php_value max_execution_time 1200
php_value max_input_time 1200
php_value memory_limit 768M

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

public/assets/css,images,js,uploads,vendor,videos

/*
 * File: assets/js/main.js
 * Chức năng: Xử lý JavaScript dùng chung cho website.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 80
        });
    }

    var navbar = document.querySelector('.navbar');
    if (navbar) {
        var updateNavbar = function () {
            var scrolled = window.scrollY > 8;
            navbar.classList.toggle('shadow', scrolled);
            navbar.classList.toggle('navbar-scrolled', scrolled);
        };

        updateNavbar();
        window.addEventListener('scroll', updateNavbar);
    }

    var chatToggle = document.getElementById('faqChatToggle');
    var chatPanel = document.getElementById('faqChatPanel');
    var chatClose = document.getElementById('faqChatClose');
    var chatForm = document.getElementById('faqChatForm');
    var chatMessages = document.getElementById('faqChatMessages');
    var chatInput = document.getElementById('faqChatInput');

    if (chatToggle && chatPanel && chatClose && chatForm && chatMessages && chatInput) {
        chatToggle.addEventListener('click', function () {
            chatPanel.hidden = false;
            chatInput.focus();
        });

        chatClose.addEventListener('click', function () {
            chatPanel.hidden = true;
        });

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var text = chatInput.value.trim();
            if (!text) {
                return;
            }

            appendChatMessage(text, 'user-message');
            chatInput.value = '';
            chatInput.disabled = true;
            appendChatMessage('Đang gửi...', 'bot-message', true);

            fetch('http://127.0.0.1:1884/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ question: text })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    removeTemporaryMessage();
                    var reply = getChatReplyText(data);
                    appendChatMessage(reply || 'Xin lỗi, hiện không trả lời được. Vui lòng thử lại sau.', 'bot-message');
                })
                .catch(function () {
                    removeTemporaryMessage();
                    appendChatMessage('Lỗi kết nối tới chatbot. Vui lòng kiểm tra server RAG.', 'bot-message');
                })
                .finally(function () {
                    chatInput.disabled = false;
                    chatInput.focus();
                });
        });
    }

    initHlsVideos();
    initFaqInteractions();
});

function initHlsVideos() {
    var videos = Array.prototype.slice.call(document.querySelectorAll('video[data-hls-src]'));

    videos.forEach(function (video) {
        var source = video.dataset.hlsSrc;
        if (!source) {
            return;
        }

        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = source;
            video.play().catch(function () {});
            return;
        }

        if (window.Hls && window.Hls.isSupported()) {
            var hls = new window.Hls();
            hls.loadSource(source);
            hls.attachMedia(video);
            hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                video.play().catch(function () {});
            });
        }
    });
}

function appendChatMessage(text, className, temporary) {
    var message = document.createElement('div');
    message.className = 'faq-chat-message ' + className;
    message.innerHTML = formatText(text);
    if (temporary) {
        message.dataset.temp = 'true';
        message.style.opacity = '0.7';
    }
    var chatMessages = document.getElementById('faqChatMessages');
    chatMessages.appendChild(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function getChatReplyText(data) {
    if (!data || !data.response) {
        return '';
    }

    if (typeof data.response === 'string') {
        return data.response;
    }

    if (Array.isArray(data.response) && data.response.length > 0) {
        if (typeof data.response[0] === 'string') {
            return data.response.join('');
        }

        if (data.response[0].type === 'text' && data.response[0].text) {
            return data.response[0].text;
        }
    }

    return '';
}

function formatText(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/^\* /gm, '- ');
    return text;
}

function removeTemporaryMessage() {
    var chatMessages = document.getElementById('faqChatMessages');
    if (!chatMessages) {
        return;
    }
    var temp = chatMessages.querySelector('[data-temp="true"]');
    if (temp) {
        chatMessages.removeChild(temp);
    }
}

function initFaqInteractions() {
    var searchInput = document.getElementById('faqSearchInput');
    var filters = Array.prototype.slice.call(document.querySelectorAll('.faq-filter'));
    var items = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
    var emptyState = document.getElementById('faqEmptyState');
    var promptChips = Array.prototype.slice.call(document.querySelectorAll('.faq-prompt-chip'));

    if (items.length > 0) {
        var activeFilter = 'all';

        var applyFaqFilter = function () {
            var keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            items.forEach(function (item) {
                var category = item.dataset.category || 'all';
                var content = (item.dataset.search || item.textContent || '').toLowerCase();
                var matchesFilter = activeFilter === 'all' || category === activeFilter;
                var matchesSearch = !keyword || content.indexOf(keyword) !== -1;
                var isVisible = matchesFilter && matchesSearch;

                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (emptyState) {
                emptyState.hidden = visibleCount > 0;
            }
        };

        filters.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFilter = button.dataset.filter || 'all';
                filters.forEach(function (filter) {
                    filter.classList.toggle('active', filter === button);
                });
                applyFaqFilter();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyFaqFilter);
        }
    }

    promptChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var chatPanel = document.getElementById('faqChatPanel');
            var chatInput = document.getElementById('faqChatInput');
            var chatForm = document.getElementById('faqChatForm');

            if (!chatPanel || !chatInput || !chatForm) {
                return;
            }

            chatPanel.hidden = false;
            chatInput.value = chip.dataset.question || chip.textContent.trim();
            chatInput.focus();

            if (chatForm.requestSubmit) {
                chatForm.requestSubmit();
            }
        });
    });
}
