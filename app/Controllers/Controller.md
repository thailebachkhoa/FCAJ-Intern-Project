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

        // Chặn CSRF cho mọi request POST vào khu vực admin.
        // Đặt ở đây để không phải nhắc lại ở từng method.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verify();
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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
        $this->requirePost(); // Chặn truy cập nếu không phải POST
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

                // Đổi session ID mới sau khi xác thực thành công -> chống session fixation
                session_regenerate_id(true);

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
