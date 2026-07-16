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
<?php

/**
 * File: admin/includes/AdminLayout.php
 * Chuc nang: Layout shell SRTDash dung chung cho cac trang admin.
 */

require_once BASE_PATH . '/app/Core/Helpers.php';
require_once __DIR__ . '/Sidebar.php';
require_once __DIR__ . '/Header.php';
require_once __DIR__ . '/ContentWrapper.php';

if (!function_exists('admin_layout_start')) {
    function admin_layout_start($config)
    {
        $pageTitle = $config['pageTitle'] ?? 'GreenNest Admin';
        $heading = $config['heading'] ?? $pageTitle;
        $subtitle = $config['subtitle'] ?? '';
        $actionHtml = $config['actionHtml'] ?? '';
        $extraHead = $config['extraHead'] ?? '';

        // Đường dẫn chuẩn
        $assetBase = BASE_URL . '/assets/vendor/srtdash';
?>
        <!doctype html>
        <html lang="vi">

        <head>
            <meta charset="utf-8">
            <title><?php echo e($pageTitle); ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link
                href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Poppins:wght@300;400;500;600;700&display=swap"
                rel="stylesheet">

            <link rel="icon" type="image/png" href="<?= $assetBase ?>/images/icon/logo.png">

            <link rel="stylesheet" href="<?= $assetBase ?>/css/bootstrap.min.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/fontawesome.min.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/themify-icons.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/metismenujs.min.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/typography.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/default-css.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/styles.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/responsive.css">
            <link rel="stylesheet" href="<?= $assetBase ?>/css/swiper-bundle.min.css">

            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-srtdash.css">

            <?php echo $extraHead; ?>
            <script src="<?= $assetBase ?>/js/vendor/modernizr-2.8.3.min.js"></script>
            <style>
                @media (max-width: 991px) {

                    /* Ép Sidebar nổi lên lớp cao nhất tuyệt đối */
                    .sidebar-menu {
                        z-index: 99999 !important;
                    }

                    .sbar_collapsed .sidebar-menu {
                        z-index: 99999 !important;
                    }

                    /* Hạ lớp của khối nội dung chính xuống */
                    .main-content {
                        position: relative !important;
                        z-index: 1 !important;
                    }
                }
            </style>
        </head>

        <body>
            <a href="#main-content" class="skip-link">Skip to main content</a>
            <div id="preloader">
                <div class="loader"></div>
            </div>
            <div class="page-container">
                <?php admin_render_sidebar(); ?>
                <div class="main-content">
                    <?php admin_render_header($heading); ?>
                    <?php admin_render_content_start($heading, $subtitle, $actionHtml); ?>
                <?php
            }
        }

        if (!function_exists('admin_layout_end')) {
            function admin_layout_end($extraScripts = '')
            {
                $assetBase = BASE_URL . '/assets/vendor/srtdash';
                admin_render_content_end();
                ?>
                </div>
                <footer>
                    <div class="footer-area">
                        <p>Plantify Co Admin - powered by SRTDash layout.</p>
                    </div>
                </footer>
            </div>
            <script src="<?= $assetBase ?>/js/vendor/jquery-2.2.4.min.js"></script>

            <script src="<?= $assetBase ?>/js/bootstrap.bundle.min.js"></script>
            <script src="<?= $assetBase ?>/js/swiper-bundle.min.js"></script>
            <script src="<?= $assetBase ?>/js/metismenujs.min.js"></script>
            <script src="<?= $assetBase ?>/js/jquery.slimscroll.min.js"></script>
            <script src="<?= $assetBase ?>/js/jquery.slicknav.min.js"></script>

            <?php echo $extraScripts; ?>

            <script src="<?= $assetBase ?>/js/scripts.js"></script>
        </body>

        </html>
<?php
            }
        }
?>
<?php

/**
 * File: admin/includes/ContentWrapper.php
 * Chuc nang: Page title va content wrapper dung chung cho admin.
 */

if (!function_exists('admin_render_content_start')) {
    function admin_render_content_start($title, $subtitle = '', $actionHtml = '')
    {
?>
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-7">
            <div class="breadcrumbs-area clearfix">
                <h1 class="page-title float-start"><?php echo e($title); ?></h1>
                <ul class="breadcrumbs float-start">
                    <li><a href=".index">Admin</a></li>
                    <li><span><?php echo e($title); ?></span></li>
                </ul>
            </div>
            <?php if ($subtitle): ?>
            <p class="admin-page-subtitle"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($actionHtml): ?>
        <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
            <?php echo $actionHtml; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="main-content-inner" id="main-content">
    <?php
    }
}

if (!function_exists('admin_render_content_end')) {
    function admin_render_content_end()
    {
        echo '</div>';
    }
}
<?php

/**
 * File: admin/includes/Header.php
 * Chuc nang: Header/topbar admin theo SRTDash đã fix Responsive.
 */

if (!function_exists('admin_render_header')) {
    function admin_render_header($pageTitle = 'Admin')
    {
?>
<div class="header-area bg-white py-3 shadow-sm sticky-top" style="z-index:100;">
    <div class="container-fluid px-0">
        <!-- Header Content -->
        <div class="row align-items-center m-0 justify-content-between">
            <!-- Logo and Title -->
            <div class="col-8 col-md-6 d-flex align-items-center gap-3">
                <div class="nav-btn mb-0 mt-0">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="admin-header-title d-none d-sm-block text-truncate mb-0 mt-0" style="max-width: 80%;">
                    <span class="d-none d-md-inline text-muted me-1">Plantify Admin /</span>
                    <strong class="text-dark"><?php echo e($pageTitle); ?></strong>
                </div>
            </div>
            <!-- Notification Area -->
            <div class="col-4 col-md-6 d-flex justify-content-end align-items-center">
                <ul class="notification-area d-flex align-items-center justify-content-end list-unstyled mb-0 gap-3"
                    style="padding: 0; margin: 0;">
                    <li id="full-view" class="d-none d-md-block"><i class="ti-fullscreen fs-5"></i></li>
                    <li id="full-view-exit" class="d-none d-md-block"><i class="ti-zoom-out fs-5"></i></li>

                    <li>
                        <a href="<?= BASE_URL ?>" title="Xem website" aria-label="Xem website"
                            class="text-dark text-decoration-none">
                            <i class="ti-home" style="font-size: 24px;"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
    }
}
?>
<?php

/**
 * File: app/Views/admin/includes/page_editor_form.php
 * Partial dùng chung cho 4 page-editor views.
 *
 * Biến cần truyền vào (extract từ view cha):
 *   $message  string
 *   $error    string
 *   $byKey    array   — kết quả Content::getByGroup()
 *   $sections array   — cấu trúc editor sections
 *   $previewUrl string — URL "Xem trang" (tuỳ chọn)
 *   $heading  string  — tiêu đề trang
 *   $subtitle string  — mô tả trang (tuỳ chọn)
 */
?>

<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" id="pageEditorForm">
    <?= csrf_field() ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($heading ?? '') ?></h4>
            <?php if (!empty($subtitle)): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?php if (!empty($previewUrl)): ?>
            <a class="btn btn-outline-success" href="<?= htmlspecialchars($previewUrl) ?>" target="_blank">
                <i class="fa-solid fa-eye me-2"></i>Xem trang
            </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success px-4">
                <i class="fa-solid fa-floppy-disk me-2"></i>Lưu thay đổi
            </button>
        </div>
    </div>

    <?php foreach ($sections as $i => $section): ?>
    <details class="pe-editor-section" <?= $i === 0 ? 'open' : '' ?>>
        <summary>
            <div class="pe-section-title">
                <div>
                    <strong><?= htmlspecialchars($section['title']) ?></strong>
                    <span class="d-block"><?= htmlspecialchars($section['desc'] ?? '') ?></span>
                </div>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </summary>
        <div class="pe-section-body">
            <div class="row">
                <?php foreach ($section['keys'] as $key):
                        if (empty($byKey[$key])) continue;
                        $row        = $byKey[$key];
                        $isTextarea = $row['input_type'] === 'textarea';
                        $colClass   = $isTextarea ? 'col-12' : 'col-lg-6';
                    ?>
                <div class="<?= $colClass ?> mb-3">
                    <label class="form-label" for="field_<?= htmlspecialchars($key) ?>">
                        <?= htmlspecialchars($row['label']) ?>
                        <span class="d-block small text-muted"><?= htmlspecialchars($key) ?></span>
                    </label>

                    <?php if ($isTextarea): ?>
                    <textarea id="field_<?= htmlspecialchars($key) ?>" class="form-control"
                        name="content[<?= htmlspecialchars($key) ?>]"
                        rows="3"><?= htmlspecialchars($row['content_value']) ?></textarea>
                    <?php else: ?>
                    <input id="field_<?= htmlspecialchars($key) ?>" class="form-control" type="text"
                        name="content[<?= htmlspecialchars($key) ?>]"
                        value="<?= htmlspecialchars($row['content_value']) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </details>
    <?php endforeach; ?>

    <div class="text-end mt-3 mb-5">
        <button type="submit" class="btn btn-success px-5">
            <i class="fa-solid fa-floppy-disk me-2"></i>Lưu tất cả thay đổi
        </button>
    </div>
</form>

<style>
.pe-editor-section {
    border: 1px solid #e5ece6;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    margin-bottom: 12px;
}

.pe-editor-section summary {
    cursor: pointer;
    list-style: none;
    padding: 14px 18px;
    background: #f7fbf7;
}

.pe-editor-section summary::-webkit-details-marker {
    display: none;
}

.pe-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.pe-section-title strong {
    color: #1d5f35;
    font-size: 15px;
}

.pe-section-title span {
    color: #748075;
    font-size: 13px;
}

.pe-section-title i {
    color: #198754;
    transition: transform .2s;
}

.pe-editor-section[open] .pe-section-title i {
    transform: rotate(180deg);
}

.pe-section-body {
    padding: 18px;
    border-top: 1px solid #e5ece6;
}
</style>
<?php

/**
 * File: admin/includes/Sidebar.php
 * Đã cập nhật: Gộp các mục chỉnh sửa nội dung trang vào siêu mục "Sửa thông tin các trang"
 */

if (!function_exists('admin_sidebar_item')) {
    function admin_sidebar_item($route, $icon, $label, $activeMatch)
    {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $active = (strpos($currentUri, $activeMatch) !== false) ? ' class="active"' : '';
        echo '<li' . $active . '>';
        echo '<a href="' . BASE_URL . '/admin/' . ltrim($route, '/') . '"><i class="' . $icon . '"></i><span>' . $label . '</span></a>';
        echo '</li>';
    }
}

if (!function_exists('admin_render_sidebar')) {
    function admin_render_sidebar()
    {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';

        // Các route thuộc nhóm "Sửa thông tin các trang"
        $pageEditorRoutes = ['pages', 'page_home', 'page_news', 'page_faq', 'page_contact', 'shop-settings'];
        $isPageEditorActive = false;
        foreach ($pageEditorRoutes as $r) {
            if (strpos($currentUri, $r) !== false) {
                $isPageEditorActive = true;
                break;
            }
        }
?>
<div class="sidebar-menu">
    <div class="sidebar-header">
        <div class="logo">
            <a href="<?= BASE_URL ?>/admin">
                <i class="fa-solid fa-leaf admin-brand-icon"></i>
                <span>Plantify Admin</span>
            </a>
        </div>
    </div>
    <div class="main-menu">
        <div class="menu-inner">
            <nav>
                <ul class="metismenu" id="menu">

                    <?php admin_sidebar_item('', 'ti-dashboard', 'Dashboard', '/admin'); ?>

                    <!-- ===== SIÊU MỤC: Sửa thông tin các trang ===== -->
                    <li class="<?= $isPageEditorActive ? 'active' : '' ?>">
                        <a href="#pageEditorMenu" aria-expanded="<?= $isPageEditorActive ? 'true' : 'false' ?>">
                            <i class="ti-layout-media-center-alt"></i>
                            <span>Sửa thông tin các trang</span>
                            <i class="fa-solid fa-chevron-down ms-auto" style="font-size:10px;opacity:.6;"></i>
                        </a>
                        <ul class="collapse <?= $isPageEditorActive ? 'in' : '' ?>" id="pageEditorMenu">
                            <?php
                            $subItems = [
                                ['page_home',      'ti-home',             'Trang chủ',       'page_home'],
                                ['shop_settings',  'ti-shopping-cart-full','Trang cửa hàng', 'shop-settings'],
                                ['page_news',      'ti-agenda',           'Trang tin tức',   'page_news'],
                                ['page_faq',       'ti-help-alt',         'Trang FAQ',       'page_faq'],
                            ];
                            foreach ($subItems as [$route, $icon, $label, $match]):
                                $active = strpos($currentUri, $match) !== false ? 'active' : '';
                            ?>
                            <li class="<?= $active ?>">
                                <a href="<?= BASE_URL ?>/admin/<?= $route ?>">
                                    <i class="<?= $icon ?>"></i>
                                    <span><?= $label ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <!-- ===== END SIÊU MỤC ===== -->

                    <?php admin_sidebar_item('news',        'ti-agenda',           'Quản lý Tin tức',        'admin/news'); ?>
                    <?php admin_sidebar_item('comments',    'ti-comments-smiley',  'Bình luận',              'comments'); ?>
                    <?php admin_sidebar_item('faqs',        'ti-help-alt',         'FAQ',                    'admin/faqs'); ?>
                    <?php admin_sidebar_item('users',       'ti-user',             'Thành viên',             'users'); ?>
                    <?php admin_sidebar_item('products',    'ti-package',          'Quản lý Sản phẩm',       'products'); ?>
                    <?php admin_sidebar_item('orders',      'ti-shopping-cart',    'Quản lý Đơn hàng',       'orders'); ?>

                </ul>
            </nav>
        </div>
    </div>
</div>

<?php
$pageTitle  = 'Quản lý Bình luận';
$breadcrumb = 'Bình luận';
$activePage = 'comments';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle
]);
?>

<!-- location:app/Views/admin/comment-list.php -->
<!-- ===== FLASH MESSAGE ===== -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== SEARCH BAR ===== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/admin/comments" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1 small fw-semibold">Tìm kiếm bình luận</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Nội dung, tên người dùng, tiêu đề bài viết..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm
                </button>
                <a href="<?= BASE_URL ?>/admin/comments" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ===== COMMENT TABLE ===== -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="mb-0">
                Tổng: <strong class="text-success"><?= $total ?></strong> bình luận
                <?php if ($search): ?>
                    <span class="text-muted small"> — kết quả cho "<?= htmlspecialchars($search) ?>"</span>
                <?php endif; ?>
            </h6>
            <small class="text-muted">
                <span class="badge bg-success me-1">Đã duyệt</span> hiển thị ngoài website &nbsp;|&nbsp;
                <span class="badge bg-warning text-dark me-1">Chờ duyệt</span>/<span
                    class="badge bg-secondary ms-1 me-1">Đã ẩn</span> không hiển thị
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="160">Người dùng</th>
                        <th>Nội dung</th>
                        <th width="200">Bài viết</th>
                        <th width="100">Trạng thái</th>
                        <th width="110">Ngày gửi</th>
                        <th width="140" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commentsList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-comments fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có bình luận nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commentsList as $i => $c): ?>
                            <tr>
                                <td class="text-muted small"><?= ($currentPage - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div
                                            style="width:36px;height:36px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#065f46;flex-shrink:0;">
                                            <?= mb_strtoupper(mb_substr($c['fullname'] ?: $c['username'] ?: 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:13px;">
                                                <?= htmlspecialchars($c['fullname'] ?: $c['username']) ?></div>
                                            <div class="text-muted" style="font-size:11px;">
                                                @<?= htmlspecialchars($c['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width:280px;font-size:13px;line-height:1.5;">
                                        <?= nl2br(htmlspecialchars(mb_substr($c['content'], 0, 150))) ?>
                                        <?= mb_strlen($c['content']) > 150 ? '<span class="text-muted">...</span>' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($c['news_title'])): ?>
                                        <a href="<?= BASE_URL ?>/news/detail/<?= htmlspecialchars($c['news_slug'] ?? '') ?>"
                                            target="_blank" class="text-success small fw-semibold"
                                            style="text-decoration:none;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?= htmlspecialchars($c['news_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Đã duyệt</span>
                                    <?php elseif ($c['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                <!-- Hành động -->
                                <td class="text-center">
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <form action="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-warning btn-sm text-white" title="Ẩn bình luận"
                                                onclick="return confirm('Ẩn bình luận này?')">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm" title="Duyệt bình luận"
                                                onclick="return confirm('Duyệt và hiển thị bình luận này?')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?= BASE_URL ?>/admin/comment_delete/<?= $c['id'] ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="Xóa"
                                            onclick="return confirm('Xóa bình luận này? Hành động không thể hoàn tác!')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                <!-- Hành động -->
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== PAGINATION ===== -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Phân trang">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/comments?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>">‹
                    Trước</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link"
                        href="<?= BASE_URL ?>/admin/comments?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/comments?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>">Sau
                    ›</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
admin_layout_end();
?>

<?php

/**
 * File: admin/faqs.php
 * Chuc nang: Quan ly FAQ (xem, them, sua, xoa).
 */

require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Quan ly FAQ | Plantify Admin';
$db = Database::getInstance();
$message = '';
$error = '';

if (!$db) {
    $error = 'Chưa kết nối được Database. Hãy import database/migrations/schema.sql và kiểm tra lại.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));

    if ($action === 'reorder') {
        $orderedIds = json_decode($_POST['ordered_ids'] ?? '[]', true);
        if (is_array($orderedIds) && count($orderedIds) > 0) {
            foreach (array_values($orderedIds) as $index => $faqId) {
                $db->query('UPDATE faqs SET sort_order = :sort_order WHERE id = :id');
                $db->bind(':sort_order', $index + 1);
                $db->bind(':id', (int) $faqId);
                $db->execute();
            }
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Thứ tự FAQ đã được cập nhật.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Thứ tự FAQ đã được cập nhật.';
        } else {
            $error = 'Không có dữ liệu sắp xếp FAQ.';
        }
    } elseif (($action === 'add' || $action === 'edit') && mb_strlen($question) > 255) {
        $error = 'Câu hỏi không được vượt quá 255 ký tự.';
    } elseif (($action === 'add' || $action === 'edit') && mb_strlen($answer) > 5000) {
        $error = 'Câu trả lời không được vượt quá 5000 ký tự.';
    } elseif ($action === 'add' && $question && $answer) {
        $db->query('INSERT INTO faqs (question, answer, sort_order) VALUES (:question, :answer, :sort_order)');
        $db->bind(':question', $question);
        $db->bind(':answer', $answer);
        $db->bind(':sort_order', $sortOrder);
        $db->execute();
        $message = 'FAQ đã được thêm.';
    } elseif ($action === 'edit' && $id && $question && $answer) {
        $db->query('UPDATE faqs SET question = :question, answer = :answer, sort_order = :sort_order WHERE id = :id');
        $db->bind(':question', $question);
        $db->bind(':answer', $answer);
        $db->bind(':sort_order', $sortOrder);
        $db->bind(':id', $id);
        $db->execute();
        $message = 'FAQ đã được cập nhật.';
    } elseif ($action === 'delete' && $id) {
        $db->query('DELETE FROM faqs WHERE id = :id');
        $db->bind(':id', $id);
        $db->execute();
        $message = 'FAQ đã được xóa.';
    } else {
        $error = 'Vui lòng nhập đầy đủ câu hỏi và câu trả lời.';
    }
}

$db->query('SELECT * FROM faqs ORDER BY sort_order ASC, id DESC');
$faqs = $db->resultSet() ?: [];


admin_layout_start([
    'pageTitle' => 'Quản lý FAQ',
    'heading' => 'Quản lý Câu hỏi thường gặp',
    'subtitle' => 'Cập nhật danh sách câu hỏi và câu trả lời hiển thị trên website.',
    'actionHtml' => '<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa-solid fa-plus me-2"></i>Thêm FAQ</button>',
    'extraHead' => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@10/dist/style.min.css">',
]);
?>

<!-- Thông báo -->
<?php if (!empty($message)): ?><div class="alert alert-success rounded-3"><i
        class="fa-solid fa-circle-check me-2"></i><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger rounded-3"><i
        class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Danh sách FAQ</h4>
        <span class="text-muted small"><i class="fa-solid fa-arrows-up-down me-1"></i>Kéo thả để sắp xếp</span>
    </div>

    <div class="table-responsive">
        <table id="faqTable" class="table table-hover admin-table">
            <thead>
                <tr>
                    <th width="10%">Thứ tự</th>
                    <th width="30%">Câu hỏi</th>
                    <th width="40%">Câu trả lời</th>
                    <th width="20%" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody id="faqSortTable">
                <?php foreach ($faqs as $faq): ?>
                <tr class="faq-sort-row" draggable="true" data-id="<?= e($faq['id']) ?>">
                    <td class="text-center">
                        <span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>
                        <span class="faq-order-number fw-bold"><?= e($faq['sort_order']) ?></span>
                    </td>
                    <td class="fw-bold"><?= e($faq['question']) ?></td>
                    <td class="text-muted"><?= e(mb_strimwidth($faq['answer'], 0, 100, '...')) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" type="button"
                            data-faq='<?= htmlspecialchars(json_encode($faq, JSON_UNESCAPED_UNICODE)) ?>'
                            onclick="editFaq(this)">Sửa</button>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($faq['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Xóa FAQ này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sort-save-state mt-3" id="faqSortState" hidden>
        <div class="sort-spinner me-2"></div> <strong>Đang lưu thứ tự...</strong>
    </div>
</div>

<!-- Modal Thêm -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title">Thêm FAQ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Câu hỏi</label><input type="text" name="question"
                        class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Câu trả lời</label><textarea name="answer"
                        class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Lưu FAQ</button></div>
        </form>
    </div>
</div>

<!-- Modal Sửa -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="sort_order" id="editSortOrder">
            <div class="modal-header">
                <h5 class="modal-title">Sửa FAQ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Câu hỏi</label><input type="text" name="question"
                        id="editQuestion" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Câu trả lời</label><textarea name="answer" id="editAnswer"
                        class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Cập nhật</button></div>
        </form>
    </div>
</div>

<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10/dist/umd/simple-datatables.min.js"></script>
<script>
function editFaq(button) {
    const faq = JSON.parse(button.dataset.faq);
    document.getElementById("editId").value = faq.id;
    document.getElementById("editQuestion").value = faq.question;
    document.getElementById("editAnswer").value = faq.answer;
    document.getElementById("editSortOrder").value = faq.sort_order || 0;
    new bootstrap.Modal(document.getElementById("editModal")).show();
}

document.addEventListener("DOMContentLoaded", function () {
    const faqTable = document.getElementById("faqTable");
    if (faqTable && window.simpleDatatables) {
        new simpleDatatables.DataTable(faqTable, { perPage: 10 });
    }
});

const faqSortTable = document.getElementById("faqSortTable");
const faqSortState = document.getElementById("faqSortState");
let draggedRow = null;
let saveTimer = null;

function updateFaqOrderNumbers() {
    Array.from(faqSortTable.querySelectorAll(".faq-sort-row")).forEach((row, index) => {
        row.querySelector(".faq-order-number").textContent = index + 1;
    });
}

function saveFaqOrder() {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(() => {
        const orderedIds = Array.from(faqSortTable.querySelectorAll(".faq-sort-row")).map(row => row.dataset.id);
        const form = new FormData();
        form.append("action", "reorder");
        form.append("ordered_ids", JSON.stringify(orderedIds));
        form.append("csrf_token", "' . Csrf::token() . '");
        faqSortState.hidden = false;

        fetch("' . BASE_URL . '/admin/faqs", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: form
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || "Khong luu duoc thu tu FAQ.");
                }
                faqSortState.querySelector("strong").textContent = "Da luu thu tu moi";
                window.setTimeout(() => {
                    faqSortState.hidden = true;
                    faqSortState.querySelector("strong").textContent = "Dang luu thu tu...";
                }, 900);
            })
            .catch(() => {
                faqSortState.querySelector("strong").textContent = "Luu thu tu that bai";
            });
    }, 250);
}

if (faqSortTable) {
    faqSortTable.addEventListener("dragstart", event => {
        draggedRow = event.target.closest(".faq-sort-row");
        if (!draggedRow) return;
        draggedRow.classList.add("is-dragging");
        event.dataTransfer.effectAllowed = "move";
    });

    faqSortTable.addEventListener("dragover", event => {
        event.preventDefault();
        const targetRow = event.target.closest(".faq-sort-row");
        if (!targetRow || targetRow === draggedRow) return;
        const rect = targetRow.getBoundingClientRect();
        const shouldInsertAfter = event.clientY > rect.top + rect.height / 2;
        faqSortTable.insertBefore(draggedRow, shouldInsertAfter ? targetRow.nextSibling : targetRow);
    });

    faqSortTable.addEventListener("dragend", () => {
        if (!draggedRow) return;
        draggedRow.classList.remove("is-dragging");
        draggedRow = null;
        updateFaqOrderNumbers();
        saveFaqOrder();
    });
}
</script>';

admin_layout_end($extraScripts);
?>
<?php

/**
 * File: app/Views/admin/index.php
 * Chức năng: Tổng quan quản trị (Style gốc - Nội dung nâng cao)
 */

require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Tổng quan hệ thống';
$db = Database::getInstance();

// 1. LẤY DỮ LIỆU THỰC TẾ
$counts = ['users' => 0, 'products' => 0, 'orders' => 0, 'comments' => 0, 'pages' => 0];

try {
    $db->query("SELECT COUNT(*) as total FROM users");
    $counts['users'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM products");
    $counts['products'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM orders");
    $counts['orders'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM comments");
    $counts['comments'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM pages");
    $counts['pages'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
    $recentOrders = $db->resultSet();
} catch (Exception $e) {
    $recentOrders = [];
}

$chartData = json_encode([
    'labels' => ['Người dùng', 'Sản phẩm', 'Đơn hàng', 'Bình luận'],
    'values' => [$counts['users'], $counts['products'], $counts['orders'], $counts['comments']]
]);

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Tổng quan hệ thống',
    'subtitle' => 'Dữ liệu trực tiếp từ Database'
]);
?>
<!-- Chart -->
<div class="row g-4 mb-4 mt-2">
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Người dùng</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-users fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['users'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Sản phẩm</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-leaf fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['products'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Đơn hàng</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-cart-shopping fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['orders'] ?></h3>
            </div>
        </div>
    </div>

</div>
<!-- Chart -->
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 text-stone-900">Phân tích dữ liệu</h5>
                <div style="height: 320px;">
                    <canvas id="adminOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 text-stone-900">Tác vụ nhanh</h5>
                <div class="list-group list-group-flush gap-2">
                    <a href="<?= BASE_URL ?>/admin/products"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-plus text-success"></i>
                            <span class="fw-medium">Thêm sản phẩm</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/orders"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-truck text-primary"></i>
                            <span class="fw-medium">Quản lý đơn hàng</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/users"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-user-gear text-warning"></i>
                            <span class="fw-medium">Thành viên</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Recent Orders -->
<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4 mb-5">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="fw-bold mb-0 text-stone-900">Đơn hàng vừa đặt</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#ORD-<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['fullname']) ?></td>
                                <td class="text-success fw-bold">
                                    <?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                                <td><span
                                        class="badge rounded-pill bg-success bg-opacity-10 text-success"><?= $order['status'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>/admin/order_detail/<?= $order['id'] ?>"
                                        class="btn btn-sm btn-light border">Xem</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Script -->
<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("adminOverviewChart").getContext("2d");
        var data = ' . $chartData . ';
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.labels,
                datasets: [{
                    label: "Số lượng",
                    data: data.values,
                    backgroundColor: [
                        "rgba(54, 162, 235, 0.7)",  // Blue (Người dùng)
                        "rgba(75, 192, 192, 0.7)",  // Green (Sản phẩm)
                        "rgba(153, 102, 255, 0.7)", // Purple (Đơn hàng)
                        "rgba(255, 159, 64, 0.7)",  // Orange (Bình luận)
                        "rgba(255, 99, 132, 0.7)"   // Red (Liên hệ mới)
                    ],
                    borderColor: [
                        "rgba(54, 162, 235, 1)",
                        "rgba(75, 192, 192, 1)",
                        "rgba(153, 102, 255, 1)",
                        "rgba(255, 159, 64, 1)",
                        "rgba(255, 99, 132, 1)"
                    ],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
</script>
';
admin_layout_end($extraScripts);
?>

<?php
$isEdit     = ($mode === 'edit');
$pageTitle  = $isEdit ? 'Sửa bài viết' : 'Thêm bài viết mới';
$breadcrumb = 'Tin tức';
$activePage = 'news';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Soạn thảo nội dung bài viết.'
]);

// Populate form values from $formData (either POST data or existing record)
$f = $formData ?? [];
$fTitle     = $f['title']             ?? '';
$fSlug      = $f['slug']              ?? '';
$fShortDesc = $f['short_description'] ?? '';
$fContent   = $f['content']           ?? '';
$fTags      = $f['tags']              ?? '';
$fSeoDesc   = $f['seo_desc']          ?? '';
$fAuthor    = $f['author']            ?? 'Admin';
$fStatus    = $f['status']            ?? 'draft';
$fThumb     = $f['thumbnail']         ?? '';
?>

<!-- Back button -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
    </a>
</div>

<!-- ===== ERROR MESSAGE ===== -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FORM ===== -->
<form action="<?= BASE_URL ?>/admin/<?= $isEdit ? 'news_edit/' . $news['id'] : 'news_create' ?>"
    method="POST"
    enctype="multipart/form-data"
    id="newsForm"
    novalidate>
    <?= csrf_field() ?>
    
    <div class="row g-4">

        <!-- LEFT COLUMN: main fields -->
        <div class="col-lg-8">

            <!-- Title -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">📝 Thông tin cơ bản</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="titleInput"
                            value="<?= htmlspecialchars($fTitle) ?>"
                            placeholder="Nhập tiêu đề hấp dẫn..." required>
                        <div id="titleError" class="invalid-feedback">Tiêu đề không được để trống và phải có ít nhất 5 ký tự.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug (URL) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="slug" id="slugInput"
                            value="<?= htmlspecialchars($fSlug) ?>"
                            placeholder="tu-dong-tao-tu-tieu-de">
                        <div class="form-text">Tự động tạo từ tiêu đề. Chỉ dùng chữ thường, số và dấu gạch ngang.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả ngắn <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="short_description" rows="3"
                            placeholder="Mô tả ngắn gọn, hấp dẫn người đọc (hiển thị trên trang danh sách)..."
                            required><?= htmlspecialchars($fShortDesc) ?></textarea>
                        <div id="shortDescError" class="invalid-feedback">Mô tả ngắn không được để trống.</div>
                    </div>

                </div>
            </div>

            <!-- Content -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">📄 Nội dung bài viết <span class="text-danger">*</span></div>
                <div class="card-body">
                    <textarea class="form-control font-monospace" name="content" id="contentInput" rows="18"
                        placeholder="Nhập nội dung bài viết (hỗ trợ HTML: <h2>, <p>, <strong>, <ul>, <ol>, <li>)..."
                        required><?= htmlspecialchars($fContent) ?></textarea>
                    <div class="form-text mt-1">Hỗ trợ thẻ HTML cơ bản: &lt;h2&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;</div>
                    <div id="contentError" class="text-danger small mt-1" style="display:none;">Nội dung không được để trống.</div>
                </div>
            </div>

        </div>
        <!-- END LEFT COLUMN -->

        <!-- RIGHT COLUMN: meta & publish -->
        <div class="col-lg-4">

            <!-- Publish settings -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🚀 Xuất bản</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="published" <?= $fStatus === 'published' ? 'selected' : '' ?>>✅ Đã đăng</option>
                            <option value="draft" <?= $fStatus === 'draft'     ? 'selected' : '' ?>>📝 Bản nháp</option>
                            <option value="hidden" <?= $fStatus === 'hidden'    ? 'selected' : '' ?>>🚫 Ẩn</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tác giả</label>
                        <input type="text" class="form-control" name="author"
                            value="<?= htmlspecialchars($fAuthor) ?>"
                            placeholder="Admin">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <?= $isEdit ? ' Lưu thay đổi' : ' Đăng bài viết' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark"></i> Hủy
                        </a>
                    </div>

                </div>
            </div>

            <!-- Thumbnail -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🖼️ Ảnh đại diện</div>
                <div class="card-body">
                    <input type="file" class="form-control mb-2" name="thumbnail"
                        id="thumbnailInput" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text mb-2">JPG, JPEG, PNG, WEBP — tối đa 2MB</div>
                    <div id="imgPreview" class="img-preview-wrap">
                        <?php if (!empty($fThumb) && file_exists(__DIR__ . '/../../../../public/' . $fThumb)): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($fThumb) ?>"
                                id="previewImg" alt="Ảnh hiện tại" style="max-width:100%;border-radius:8px;">
                            <input type="hidden" name="existing_thumbnail" value="<?= htmlspecialchars($fThumb) ?>">
                        <?php else: ?>
                            <div id="previewImg" style="display:none;"><img src="" style="max-width:100%;border-radius:8px;" id="previewImgEl"></div>
                        <?php endif; ?>
                    </div>
                    <div id="fileError" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>

            <!-- Tags + SEO -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🏷️ Tags & SEO</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" class="form-control" name="tags"
                            value="<?= htmlspecialchars($fTags) ?>"
                            placeholder="phong thuy, cay canh, ...">
                        <div class="form-text">Phân cách bằng dấu phẩy</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Mô tả SEO</label>
                        <textarea class="form-control" name="seo_desc" rows="2"
                            placeholder="Mô tả ngắn cho công cụ tìm kiếm (tối đa 160 ký tự)..."
                            maxlength="255"><?= htmlspecialchars($fSeoDesc) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <!-- END RIGHT COLUMN -->

    </div>
</form>

<!-- ===== JS: auto-slug + preview + validation ===== -->
<script>
    // Auto-generate slug from title
    const titleInput = document.getElementById('titleInput');
    const slugInput = document.getElementById('slugInput');

    titleInput.addEventListener('input', function() {
        const title = this.value;
        let slug = title.toLowerCase();
        // Transliterate common Vietnamese chars
        const from = 'àáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ';
        const to = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiioooooooooooooooooouuuuuuuuuuuyyyyyд';
        // Simple replacement map for slug preview
        const map = {
            'à': 'a',
            'á': 'a',
            'ả': 'a',
            'ã': 'a',
            'ạ': 'a',
            'â': 'a',
            'ầ': 'a',
            'ấ': 'a',
            'ẩ': 'a',
            'ẫ': 'a',
            'ậ': 'a',
            'ă': 'a',
            'ằ': 'a',
            'ắ': 'a',
            'ẳ': 'a',
            'ẵ': 'a',
            'ặ': 'a',
            'è': 'e',
            'é': 'e',
            'ẻ': 'e',
            'ẽ': 'e',
            'ẹ': 'e',
            'ê': 'e',
            'ề': 'e',
            'ế': 'e',
            'ể': 'e',
            'ễ': 'e',
            'ệ': 'e',
            'ì': 'i',
            'í': 'i',
            'ỉ': 'i',
            'ĩ': 'i',
            'ị': 'i',
            'ò': 'o',
            'ó': 'o',
            'ỏ': 'o',
            'õ': 'o',
            'ọ': 'o',
            'ô': 'o',
            'ồ': 'o',
            'ố': 'o',
            'ổ': 'o',
            'ỗ': 'o',
            'ộ': 'o',
            'ơ': 'o',
            'ờ': 'o',
            'ớ': 'o',
            'ở': 'o',
            'ỡ': 'o',
            'ợ': 'o',
            'ù': 'u',
            'ú': 'u',
            'ủ': 'u',
            'ũ': 'u',
            'ụ': 'u',
            'ư': 'u',
            'ừ': 'u',
            'ứ': 'u',
            'ử': 'u',
            'ữ': 'u',
            'ự': 'u',
            'ỳ': 'y',
            'ý': 'y',
            'ỷ': 'y',
            'ỹ': 'y',
            'ỵ': 'y',
            'đ': 'd'
        };
        slug = slug.replace(/[^\u0000-\u007E]/g, c => map[c] || '');
        slug = slug.replace(/[^a-z0-9\s-]/g, '');
        slug = slug.replace(/[\s-]+/g, '-').replace(/^-|-$/g, '');
        slugInput.value = slug;
    });

    // Image preview
    document.getElementById('thumbnailInput').addEventListener('change', function() {
        const file = this.files[0];
        const errBox = document.getElementById('fileError');
        const preview = document.getElementById('previewImg');

        errBox.style.display = 'none';
        if (!file) return;

        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            errBox.textContent = 'Chỉ chấp nhận file ảnh: JPG, JPEG, PNG, WEBP!';
            errBox.style.display = 'block';
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            errBox.textContent = 'Ảnh không được vượt quá 2MB!';
            errBox.style.display = 'block';
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            // If existing img element
            let img = document.getElementById('previewImgEl');
            if (!img) {
                img = document.createElement('img');
                img.style.cssText = 'max-width:100%;border-radius:8px;';
                preview.appendChild(img);
            }
            img.src = e.target.result;
            preview.style.display = '';
        };
        reader.readAsDataURL(file);
    });

    // Form validation
    document.getElementById('newsForm').addEventListener('submit', function(e) {
        let ok = true;
        const title = document.getElementById('titleInput');
        const content = document.getElementById('contentInput');
        const titleErr = document.getElementById('titleError');
        const contentErr = document.getElementById('contentError');

        titleErr.style.display = 'none';
        title.classList.remove('is-invalid');
        contentErr.style.display = 'none';

        if (!title.value.trim() || title.value.trim().length < 5) {
            title.classList.add('is-invalid');
            ok = false;
        }
        if (!content.value.trim()) {
            contentErr.style.display = 'block';
            ok = false;
        }
        if (!ok) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...';
        }
    });
</script>

<?php
// Nếu bạn có script validation riêng, hãy bỏ vào biến này
$extraScripts = '<script> ... code JS của bạn ... </script>';
admin_layout_end($extraScripts);
?>
<?php
$pageTitle  = 'Quản lý Tin tức';
$breadcrumb = 'Tin tức';
$activePage = 'news';
$pageAction = '<a href="' . BASE_URL . '/admin/news_create" class="btn btn-success btn-sm">
    <i class="fa-solid fa-plus"></i> Thêm bài viết mới
</a>';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'actionHtml' => $pageAction
]);
?>

<!-- ===== FLASH MESSAGES ===== -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FILTER BAR ===== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/admin/news" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1 small fw-semibold">Tìm kiếm</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Tiêu đề hoặc tag..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-semibold">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Đã đăng</option>
                    <option value="draft" <?= $statusFilter === 'draft'     ? 'selected' : '' ?>>Bản nháp</option>
                    <option value="hidden" <?= $statusFilter === 'hidden'    ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm
                </button>
                <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ===== NEWS TABLE ===== -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="mb-0">
                Tổng: <strong class="text-success"><?= $total ?></strong> bài viết
                <?php if ($search): ?><span class="text-muted small"> — kết quả cho "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="80">Ảnh</th>
                        <th>Tiêu đề</th>
                        <th width="100">Tác giả</th>
                        <th width="110">Trạng thái</th>
                        <th width="110">Ngày tạo</th>
                        <th width="160" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($newsList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-newspaper fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có bài viết nào.
                                <a href="<?= BASE_URL ?>/admin/news_create">Tạo ngay</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($newsList as $i => $n): ?>
                            <tr>
                                <td class="text-muted small"><?= ($currentPage - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($n['thumbnail']) && file_exists(__DIR__ . '/../../../../public/' . $n['thumbnail'])): ?>
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($n['thumbnail']) ?>"
                                            style="width:64px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:64px;height:48px;background:#d1fae5;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:22px;">🌿</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="max-width:320px;">
                                        <?= htmlspecialchars($n['title']) ?>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($n['slug']) ?></small>
                                    <?php if (!empty($n['tags'])): ?>
                                        <div class="mt-1">
                                            <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $n['tags']))), 0, 3) as $tag): ?>
                                                <span class="badge bg-light text-success border" style="font-size:10px;"><?= htmlspecialchars($tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($n['author'] ?? 'Admin') ?></td>
                                <td>
                                    <?php if ($n['status'] === 'published'): ?>
                                        <span class="badge bg-success">Đã đăng</span>
                                    <?php elseif ($n['status'] === 'draft'): ?>
                                        <span class="badge bg-warning text-dark">Bản nháp</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>/news/detail/<?= htmlspecialchars($n['slug']) ?>"
                                        class="btn btn-outline-info btn-sm" target="_blank" title="Xem">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/news_edit/<?= $n['id'] ?>"
                                        class="btn btn-warning btn-sm text-white" title="Sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="<?= BASE_URL ?>/admin/news_delete/<?= $n['id'] ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="Xóa"
                                            onclick="return confirm('Xóa bài viết \'<?= addslashes(htmlspecialchars($n['title'])) ?>\'?\nHành động này không thể hoàn tác!')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== PAGINATION ===== -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Phân trang">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">‹ Trước</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">Sau ›</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
admin_layout_end();
?>

<?php require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => 'Chi tiết Đơn hàng #' . $order['id']]); ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Sản phẩm đã đặt</h5>
            </div>
            <div class="card-body">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>"
                            width="60"
                            class="rounded-3 me-3 border"
                            alt="<?= htmlspecialchars($item['name']) ?>"
                            style="object-fit: cover; height: 60px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?= $item['name'] ?></h6>
                            <small class="text-muted">SL: <?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', '.') ?>đ</small>
                        </div>
                        <div class="fw-bold"><?= number_format($item['quantity'] * $item['price'], 0, ',', '.') ?>đ</div>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="h5">Tổng cộng:</span>
                    <span class="h5 text-success fw-bold"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Trạng thái & Giao hàng</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/admin/order_update_status/<?= $order['id'] ?>" method="POST"> <?= csrf_field() ?> <label class="form-label small fw-bold">Cập nhật trạng thái</label>
                    <select name="status" class="form-select mb-3 rounded-pill">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Đang đóng gói</option>
                        <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                    <button type="submit" class="btn btn-success w-100 rounded-pill">Lưu thay đổi</button>
                </form>
                <hr>
                <p class="mb-1"><strong>Người nhận:</strong> <?= $order['fullname'] ?></p>
                <p class="mb-1"><strong>SĐT:</strong> <?= $order['phone'] ?></p>
                <p class="mb-0"><strong>Địa chỉ:</strong> <?= $order['address'] ?></p>
            </div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

<?php require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => 'Quản lý Đơn hàng']); ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#ORD-<?= $order['id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= $order['fullname'] ?></div>
                                <small class="text-muted"><?= $order['phone'] ?></small>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td class="fw-bold text-success"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php
                                $badgeClass = [
                                    'pending' => 'bg-warning text-dark',
                                    'processing' => 'bg-info',
                                    'shipping' => 'bg-primary',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger'
                                ];
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass[$order['status']] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>/admin/order_detail/<?= $order['id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill">Chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản tĩnh hiển thị trên trang câu hỏi thường gặp.',
]);
 
$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản tĩnh.';
$previewUrl = BASE_URL . '/faq';
 
require __DIR__ . '/includes/page_editor_form.php';
 
admin_layout_end();

<?php
// ============================================================
// app/Views/admin/page_home.php
// ============================================================
?>
<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản hiển thị trên trang chủ website.',
]);

$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản đang hiển thị.';
$previewUrl = BASE_URL . '/';

require __DIR__ . '/includes/page_editor_form.php';

admin_layout_end();

<?php
// ============================================================
// app/Views/admin/page_news.php
// ============================================================
?>
<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản tĩnh trên trang danh sách tin tức.',
]);
 
$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản tĩnh đang hiển thị.';
$previewUrl = BASE_URL . '/news';
 
require __DIR__ . '/includes/page_editor_form.php';
 
admin_layout_end();

<?php

/**
 * File: admin/pages.php
 * Chức năng: Quản lý nội dung tĩnh, ảnh và video hero trang giới thiệu.
 */
require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Quản lý nội dung | Plantify Admin';
$db = Database::getInstance();
$message = '';
$error = '';

function admin_page_image_upload($fieldName, &$error)
{
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Upload hình ảnh thất bại. Vui lòng chọn lại file.';
        return '';
    }

    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $error = 'Hình ảnh vượt quá giới hạn 5MB.';
        return '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'Chỉ hỗ trợ định dạng JPG, PNG, WEBP hoặc GIF.';
        return '';
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($mime && !in_array($mime, $allowedMimes, true)) {
            $error = 'File upload không phải hình ảnh hợp lệ.';
            return '';
        }
    }

    $uploadDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pages';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        $error = 'Không tạo được thư mục public/assets/uploads/pages.';
        return '';
    }

    $fileName = 'about-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Không lưu được hình ảnh lên server.';
        return '';
    }

    return 'assets/uploads/pages/' . $fileName;
}

function admin_delete_old_about_image($relativePath)
{
    $relativePath = str_replace('\\', '/', (string) $relativePath);
    if (!preg_match('#^assets/uploads/pages/about-[a-zA-Z0-9_.-]+\.(jpe?g|png|webp|gif)$#i', $relativePath)) {
        return;
    }

    $baseDir = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pages');
    $targetPath = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if (!$baseDir || !$targetPath || strpos($targetPath, $baseDir . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }

    if (is_file($targetPath)) {
        @unlink($targetPath);
    }
}

if (!$db) {
    $error = 'Chưa kết nối được database.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_content') {
        $content = $_POST['content'] ?? [];
        if (!is_array($content)) {
            $error = 'Dữ liệu nội dung không hợp lệ.';
        } else {
            foreach ($content as $key => $value) {
                $key = (string) $key;
                $value = trim((string) $value);

                if (!preg_match('/^[a-z0-9_.-]+$/', $key) || mb_strlen($key) > 120) {
                    $error = 'Key nội dung không hợp lệ.';
                    break;
                }
                if (mb_strlen($value) > 5000) {
                    $error = 'Giá trị nội dung không được vượt quá 5000 ký tự.';
                    break;
                }

                $db->query('UPDATE site_content SET content_value = :value WHERE content_key = :key');
                $db->bind(':value', $value);
                $db->bind(':key', $key);
                $db->execute();
            }

            if (!$error) {
                $message = 'Nội dung website đã được cập nhật.';
            }
        }
    }

    if ($action === 'save_about_image') {
        $uploadedImage = admin_page_image_upload('image_file', $error);
        if (!$error && $uploadedImage) {
            $db->query("SELECT image FROM pages WHERE slug = 'about' LIMIT 1");
            $currentAboutPage = $db->single();
            $oldImage = $currentAboutPage['image'] ?? '';

            $db->query("INSERT INTO pages (slug, title, content, image)
                VALUES ('about', 'Giới thiệu Plantify Co', 'Nội dung đang được cập nhật.', :image)
                ON DUPLICATE KEY UPDATE image = :update_image");
            $db->bind(':image', $uploadedImage);
            $db->bind(':update_image', $uploadedImage);
            $db->execute();

            if ($oldImage && $oldImage !== $uploadedImage) {
                admin_delete_old_about_image($oldImage);
            }

            $message = 'Hình ảnh trang giới thiệu đã được cập nhật.';
        } elseif (!$error) {
            $error = 'Vui lòng chọn hình ảnh cần upload.';
        }
    }
}

$contentRows = [];
$groupedContent = [];
$contentByKey = [];
$otherGroupedContent = [];
$aboutPage = null;
$heroVideoAdmin = 'assets/videos/about/about-hero.m3u8';

if ($db) {
    try {
        $aboutDefaults = [
            ['about.meta_title', 'Trang giới thiệu', 'Meta title', 'text', 'Giới thiệu | Plantify Co'],
            ['about.meta_description', 'Trang giới thiệu', 'Meta description', 'textarea', 'Tìm hiểu Plantify Co, công ty thiết kế decor cây xanh.'],
            ['about.hero_video', 'Trang giới thiệu', 'Video nền đầu trang giới thiệu', 'text', 'assets/videos/about/about-hero.m3u8'],
            ['about.hero_video_label', 'Trang giới thiệu', 'Nhãn truy cập video hero', 'text', 'Video nền giới thiệu Plantify Co'],
            ['about.hero_kicker', 'Trang giới thiệu', 'Nhãn hero', 'text', 'Về Plantify'],
            ['about.hero_title', 'Trang giới thiệu', 'Tiêu đề hero', 'textarea', 'Thiết kế mảng xanh bền vững cho không gian sống và làm việc hiện đại.'],
            ['about.hero_description', 'Trang giới thiệu', 'Mô tả hero', 'textarea', 'Plantify kết hợp tư duy thiết kế, hiểu biết cây trồng và quy trình chăm sóc định kỳ để tạo nên những không gian xanh đẹp, khỏe và dễ duy trì.'],
            ['about.hero_primary_button', 'Trang giới thiệu', 'Nút hero chính', 'text', 'Xem FAQ'],
            ['about.hero_secondary_button', 'Trang giới thiệu', 'Nút hero phụ', 'text', 'Xem câu hỏi thường gặp'],
            ['about.hero_card_title', 'Trang giới thiệu', 'Tiêu đề thẻ hero', 'text', 'Không chỉ đặt cây vào phòng'],
            ['about.hero_card_text', 'Trang giới thiệu', 'Nội dung thẻ hero', 'textarea', 'Chúng tôi tính ánh sáng, luồng di chuyển, độ ẩm, chất liệu chậu và chi phí bảo dưỡng trước khi đề xuất phương án.'],
            ['about.metric_1_value', 'Trang giới thiệu', 'Chỉ số 1', 'text', '120+'],
            ['about.metric_1_label', 'Trang giới thiệu', 'Nhãn chỉ số 1', 'text', 'không gian đã tư vấn'],
            ['about.metric_2_value', 'Trang giới thiệu', 'Chỉ số 2', 'text', '30 ngày'],
            ['about.metric_2_label', 'Trang giới thiệu', 'Nhãn chỉ số 2', 'text', 'theo dõi sau bàn giao'],
            ['about.metric_3_value', 'Trang giới thiệu', 'Chỉ số 3', 'text', '24h'],
            ['about.metric_3_label', 'Trang giới thiệu', 'Nhãn chỉ số 3', 'text', 'phản hồi hồ sơ online'],
            ['about.metric_4_value', 'Trang giới thiệu', 'Chỉ số 4', 'text', '4 bước'],
            ['about.metric_4_label', 'Trang giới thiệu', 'Nhãn chỉ số 4', 'text', 'quy trình triển khai rõ ràng'],
            ['about.image_alt', 'Trang giới thiệu', 'Alt ảnh giới thiệu', 'text', 'Chăm sóc cây xanh trong không gian nội thất'],
            ['about.image_note_title', 'Trang giới thiệu', 'Tiêu đề ghi chú ảnh', 'text', 'Khảo sát trước khi chọn cây'],
            ['about.image_note_text', 'Trang giới thiệu', 'Nội dung ghi chú ảnh', 'textarea', 'Ánh sáng, hướng gió và thói quen sử dụng quyết định 70% độ bền của mảng xanh.'],
            ['about.story_kicker', 'Trang giới thiệu', 'Nhãn câu chuyện', 'text', 'Câu chuyện'],
            ['about.story_title', 'Trang giới thiệu', 'Tiêu đề câu chuyện', 'textarea', 'Từ những chậu cây nhỏ đến giải pháp xanh cho doanh nghiệp'],
            ['about.story_paragraph_1', 'Trang giới thiệu', 'Đoạn câu chuyện 1', 'textarea', 'Chúng tôi phục vụ văn phòng, căn hộ dịch vụ, showroom, nhà hàng và không gian bán lẻ cần một hình ảnh xanh chỉn chu. Mỗi dự án bắt đầu bằng khảo sát thực tế, sau đó đội ngũ thiết kế chọn cây theo ánh sáng, độ ẩm, mật độ sử dụng và phong cách nội thất.'],
            ['about.story_paragraph_2', 'Trang giới thiệu', 'Đoạn câu chuyện 2', 'textarea', 'Plantify không chạy theo bố cục rườm rà. Chúng tôi tập trung vào cây khỏe, chậu đẹp, tỷ lệ hài hòa và quy trình chăm sóc sau bàn giao.'],
            ['about.check_1', 'Trang giới thiệu', 'Gạch đầu dòng 1', 'text', 'Tư vấn theo ngân sách'],
            ['about.check_2', 'Trang giới thiệu', 'Gạch đầu dòng 2', 'text', 'Bố trí theo mặt bằng'],
            ['about.check_3', 'Trang giới thiệu', 'Gạch đầu dòng 3', 'text', 'Chọn cây theo điều kiện sáng'],
            ['about.check_4', 'Trang giới thiệu', 'Gạch đầu dòng 4', 'text', 'Theo dõi sức khỏe cây'],
            ['about.capability_kicker', 'Trang giới thiệu', 'Nhãn năng lực', 'text', 'Năng lực cốt lõi'],
            ['about.capability_title', 'Trang giới thiệu', 'Tiêu đề năng lực', 'textarea', 'Thiết kế đẹp nhưng vẫn dễ vận hành mỗi ngày'],
            ['about.capability_text', 'Trang giới thiệu', 'Mô tả năng lực', 'textarea', 'Plantify xây dựng phương án theo cả thẩm mỹ lẫn chi phí duy trì, phù hợp cho không gian có nhiều người sử dụng.'],
            ['about.feature_1_title', 'Trang giới thiệu', 'Tiêu đề năng lực 1', 'text', 'Thiết kế đúng không gian'],
            ['about.feature_1_text', 'Trang giới thiệu', 'Nội dung năng lực 1', 'textarea', 'Mỗi loại cây được chọn theo ánh sáng, diện tích, luồng di chuyển và chất liệu nội thất.'],
            ['about.feature_2_title', 'Trang giới thiệu', 'Tiêu đề năng lực 2', 'text', 'Cây khỏe, nguồn rõ'],
            ['about.feature_2_text', 'Trang giới thiệu', 'Nội dung năng lực 2', 'textarea', 'Cây được kiểm tra rễ, lá, sâu bệnh và khả năng thích nghi trước khi bàn giao.'],
            ['about.feature_3_title', 'Trang giới thiệu', 'Tiêu đề năng lực 3', 'text', 'Bảo dưỡng đều đặn'],
            ['about.feature_3_text', 'Trang giới thiệu', 'Nội dung năng lực 3', 'textarea', 'Lịch chăm sóc định kỳ giúp không gian xanh luôn sạch, an toàn và giữ hình ảnh chuyên nghiệp.'],
            ['about.process_kicker', 'Trang giới thiệu', 'Nhãn quy trình', 'text', 'Quy trình'],
            ['about.process_title', 'Trang giới thiệu', 'Tiêu đề quy trình', 'textarea', 'Rõ từng bước để khách hàng dễ theo dõi'],
            ['about.process_text', 'Trang giới thiệu', 'Mô tả quy trình', 'textarea', 'Từ ảnh không gian ban đầu đến chăm sóc định kỳ, mỗi giai đoạn đều có đầu ra cụ thể để bạn duyệt nhanh và kiểm soát ngân sách.'],
            ['about.process_1_title', 'Trang giới thiệu', 'Tiêu đề bước 1', 'text', 'Tiếp nhận nhu cầu'],
            ['about.process_1_text', 'Trang giới thiệu', 'Nội dung bước 1', 'textarea', 'Nhận ảnh, mặt bằng, phong cách mong muốn và mức ngân sách dự kiến.'],
            ['about.process_2_title', 'Trang giới thiệu', 'Tiêu đề bước 2', 'text', 'Khảo sát điều kiện'],
            ['about.process_2_text', 'Trang giới thiệu', 'Nội dung bước 2', 'textarea', 'Đánh giá ánh sáng, gió, ổ cắm, lối đi, vị trí tưới và rủi ro bẩn sàn.'],
            ['about.process_3_title', 'Trang giới thiệu', 'Tiêu đề bước 3', 'text', 'Đề xuất phương án'],
            ['about.process_3_text', 'Trang giới thiệu', 'Nội dung bước 3', 'textarea', 'Gợi ý cây, chậu, bố cục, tần suất chăm sóc và phương án thay thế khi cần.'],
            ['about.process_4_title', 'Trang giới thiệu', 'Tiêu đề bước 4', 'text', 'Bàn giao và duy trì'],
            ['about.process_4_text', 'Trang giới thiệu', 'Nội dung bước 4', 'textarea', 'Lắp đặt gọn, hướng dẫn chăm sóc, theo dõi cây sau bàn giao và bảo dưỡng định kỳ.'],
            ['about.testimonial_kicker', 'Trang giới thiệu', 'Nhãn phản hồi', 'text', 'Khách hàng nói gì'],
            ['about.testimonial_title', 'Trang giới thiệu', 'Tiêu đề phản hồi', 'textarea', 'Phản hồi từ các dự án đã triển khai'],
            ['about.testimonial_1_quote', 'Trang giới thiệu', 'Phản hồi 1', 'textarea', 'Plantify thiết kế mảng xanh gọn gàng, đúng tinh thần văn phòng của chúng tôi và chăm sóc cây rất đều.'],
            ['about.testimonial_1_name', 'Trang giới thiệu', 'Tên khách hàng 1', 'text', 'Ms. Linh Nguyễn'],
            ['about.testimonial_1_role', 'Trang giới thiệu', 'Vai trò khách hàng 1', 'text', 'Office Manager, Aster Tech'],
            ['about.testimonial_2_quote', 'Trang giới thiệu', 'Phản hồi 2', 'textarea', 'Đội ngũ tư vấn kỹ về ánh sáng và chất liệu chậu. Không gian studio sau khi decor trông ấm hơn nhưng vẫn rất tinh tế.'],
            ['about.testimonial_2_name', 'Trang giới thiệu', 'Tên khách hàng 2', 'text', 'Mr. Minh Trần'],
            ['about.testimonial_2_role', 'Trang giới thiệu', 'Vai trò khách hàng 2', 'text', 'Founder, Annam Studio'],
            ['about.map_kicker', 'Trang giới thiệu', 'Nhãn vị trí', 'text', 'Vị trí'],
            ['about.map_title', 'Trang giới thiệu', 'Tiêu đề vị trí', 'textarea', 'Ghé Plantify để chọn cây và chậu trực tiếp'],
            ['about.map_iframe_title', 'Trang giới thiệu', 'Tiêu đề iframe bản đồ', 'text', 'Bản đồ Plantify Co'],
            ['about.cta_title', 'Trang giới thiệu', 'Tiêu đề CTA', 'textarea', 'Muốn biết không gian của bạn hợp cây gì?'],
            ['about.cta_text', 'Trang giới thiệu', 'Mô tả CTA', 'textarea', 'Gửi ảnh hiện trạng, Plantify sẽ gợi ý nhóm cây, kích thước chậu và cách chăm sóc phù hợp.'],
            ['about.cta_button', 'Trang giới thiệu', 'Nút CTA', 'text', 'Xem FAQ'],
        ];

        foreach ($aboutDefaults as $row) {
            $db->query("INSERT INTO site_content (content_key, content_group, label, input_type, content_value)
                VALUES (:content_key, :content_group, :label, :input_type, :content_value)
                ON DUPLICATE KEY UPDATE
                    content_group = VALUES(content_group),
                    label = VALUES(label),
                    input_type = VALUES(input_type),
                    content_value = IF(content_key = 'about.hero_video' AND content_value = 'assets/videos/about/about.m3u8', VALUES(content_value), content_value)");
            $db->bind(':content_key', $row[0]);
            $db->bind(':content_group', $row[1]);
            $db->bind(':label', $row[2]);
            $db->bind(':input_type', $row[3]);
            $db->bind(':content_value', $row[4]);
            $db->execute();
        }

        $db->query('SELECT * FROM site_content ORDER BY content_group, id');
        $contentRows = $db->resultSet();

        $db->query("SELECT * FROM pages WHERE slug = 'about' LIMIT 1");
        $aboutPage = $db->single();
    } catch (Exception $e) {
        $error = 'Lỗi truy vấn database: ' . $e->getMessage();
    }
}

foreach ($contentRows as $row) {
    $contentByKey[$row['content_key']] = $row;
    if (strpos($row['content_key'], 'about.') === 0) {
        $groupedContent[$row['content_group']][] = $row;
    } else {
        $otherGroupedContent[$row['content_group']][] = $row;
    }
    if ($row['content_key'] === 'about.hero_video') {
        $heroVideoAdmin = $row['content_value'];
    }
}

$aboutEditorSections = [
    [
        'title' => 'SEO & thông tin trang',
        'description' => 'Tên tab trình duyệt và mô tả tìm kiếm.',
        'keys' => ['about.meta_title', 'about.meta_description'],
    ],
    [
        'title' => 'Hero đầu trang',
        'description' => 'Tiêu đề lớn, mô tả, nút bấm và thẻ thông tin trên nền video.',
        'keys' => ['about.hero_video', 'about.hero_video_label', 'about.hero_kicker', 'about.hero_title', 'about.hero_description', 'about.hero_primary_button', 'about.hero_secondary_button', 'about.hero_card_title', 'about.hero_card_text'],
    ],
    [
        'title' => 'Các chỉ số nổi bật',
        'description' => 'Bốn con số ngay dưới phần hero.',
        'keys' => ['about.metric_1_value', 'about.metric_1_label', 'about.metric_2_value', 'about.metric_2_label', 'about.metric_3_value', 'about.metric_3_label', 'about.metric_4_value', 'about.metric_4_label'],
    ],
    [
        'title' => 'Ảnh & ghi chú ảnh',
        'description' => 'Alt ảnh và nội dung ghi chú nổi trên ảnh giới thiệu.',
        'keys' => ['about.image_alt', 'about.image_note_title', 'about.image_note_text'],
    ],
    [
        'title' => 'Câu chuyện thương hiệu',
        'description' => 'Khối nội dung kể về Plantify và bốn gạch đầu dòng.',
        'keys' => ['about.story_kicker', 'about.story_title', 'about.story_paragraph_1', 'about.story_paragraph_2', 'about.check_1', 'about.check_2', 'about.check_3', 'about.check_4'],
    ],
    [
        'title' => 'Năng lực cốt lõi',
        'description' => 'Tiêu đề phần năng lực và ba thẻ dịch vụ.',
        'keys' => ['about.capability_kicker', 'about.capability_title', 'about.capability_text', 'about.feature_1_title', 'about.feature_1_text', 'about.feature_2_title', 'about.feature_2_text', 'about.feature_3_title', 'about.feature_3_text'],
    ],
    [
        'title' => 'Quy trình triển khai',
        'description' => 'Mô tả phần quy trình và bốn bước thực hiện.',
        'keys' => ['about.process_kicker', 'about.process_title', 'about.process_text', 'about.process_1_title', 'about.process_1_text', 'about.process_2_title', 'about.process_2_text', 'about.process_3_title', 'about.process_3_text', 'about.process_4_title', 'about.process_4_text'],
    ],
    [
        'title' => 'Phản hồi khách hàng',
        'description' => 'Tiêu đề phần phản hồi và hai lời chứng thực.',
        'keys' => ['about.testimonial_kicker', 'about.testimonial_title', 'about.testimonial_1_quote', 'about.testimonial_1_name', 'about.testimonial_1_role', 'about.testimonial_2_quote', 'about.testimonial_2_name', 'about.testimonial_2_role'],
    ],
    [
        'title' => 'Bản đồ & liên hệ nhanh',
        'description' => 'Tiêu đề khu vực bản đồ trên trang giới thiệu.',
        'keys' => ['about.map_kicker', 'about.map_title', 'about.map_iframe_title'],
    ],
    [
        'title' => 'CTA cuối trang',
        'description' => 'Khối kêu gọi hành động cuối trang.',
        'keys' => ['about.cta_title', 'about.cta_text', 'about.cta_button'],
    ],
];

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý nội dung website',
    'subtitle' => 'Chỉnh văn bản tĩnh, ảnh giới thiệu và video hero đang hiển thị trên website.',
    'actionHtml' => '<a class="btn btn-outline-success" href="' . BASE_URL . '/about"><i class="fa-solid fa-eye me-2"></i>Xem trang giới thiệu</a>',
    'extraHead' => '<style>
        .about-editor-layout { display: grid; gap: 16px; }
        .about-editor-section { border: 1px solid #e5ece6; border-radius: 10px; background: #fff; overflow: hidden; }
        .about-editor-section summary { cursor: pointer; list-style: none; padding: 16px 18px; background: #f7fbf7; }
        .about-editor-section summary::-webkit-details-marker { display: none; }
        .about-editor-section-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .about-editor-section-title strong { color: #1d5f35; font-size: 16px; }
        .about-editor-section-title span { color: #748075; font-size: 13px; }
        .about-editor-section-title i { color: #198754; transition: transform .2s ease; }
        .about-editor-section[open] .about-editor-section-title i { transform: rotate(180deg); }
        .about-editor-section-body { padding: 18px; border-top: 1px solid #e5ece6; }
        .about-editor-toolbar { gap: 12px; }
        .about-current-image-preview { aspect-ratio: 1 / 1; width: min(100%, 220px); background: #f7fbf7; }
        .about-current-image-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        @media (max-width: 767px) { .about-editor-section-title { align-items: flex-start; } }
    </style>',
]);
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

<form method="post" class="admin-card mb-4">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_content">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 about-editor-toolbar">
        <div>
            <h4 class="mb-1">Nội dung trang giới thiệu</h4>
            <p class="text-muted mb-0">Mở từng mục nhỏ để chỉnh đúng khu vực đang hiển thị trên trang giới thiệu.</p>
        </div>
        <button type="submit" class="btn btn-success">Lưu thay đổi</button>
    </div>

    <div class="about-editor-layout">
        <?php foreach ($aboutEditorSections as $index => $section): ?>
            <details class="about-editor-section" <?php echo $index < 2 ? 'open' : ''; ?>>
                <summary>
                    <div class="about-editor-section-title">
                        <div>
                            <strong><?php echo e($section['title']); ?></strong>
                            <span class="d-block"><?php echo e($section['description']); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </summary>
                <div class="about-editor-section-body">
                    <div class="row">
                        <?php foreach ($section['keys'] as $key): ?>
                            <?php if (empty($contentByKey[$key])) {
                                continue;
                            } ?>
                            <?php $row = $contentByKey[$key]; ?>
                            <div class="<?php echo $row['input_type'] === 'textarea' ? 'col-lg-12' : 'col-lg-6'; ?> mb-3">
                                <label class="form-label" for="content_<?php echo e($row['id']); ?>">
                                    <?php echo e($row['label']); ?>
                                    <span class="d-block small text-muted"><?php echo e($row['content_key']); ?></span>
                                </label>
                                <?php if ($row['content_key'] === 'about.hero_video'): ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control bg-light" type="text" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>" readonly>
                                    <small class="form-text text-muted">Dùng khung upload video bên dưới để cập nhật file m3u8.</small>
                                <?php elseif ($row['input_type'] === 'textarea'): ?>
                                    <textarea id="content_<?php echo e($row['id']); ?>" class="form-control" name="content[<?php echo e($row['content_key']); ?>]" rows="3"><?php echo e($row['content_value']); ?></textarea>
                                <?php else: ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control" type="<?php echo $row['input_type'] === 'url' ? 'url' : 'text'; ?>" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($otherGroupedContent)): ?>
        <details class="about-editor-section mt-4">
            <summary>
                <div class="about-editor-section-title">
                    <div>
                        <strong>Nội dung dùng chung</strong>
                        <span class="d-block">Một số thông tin khác đang được dùng lại ở nhiều trang.</span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </summary>
            <div class="about-editor-section-body">
                <?php foreach ($otherGroupedContent as $group => $rows): ?>
                    <h5 class="text-success mt-2"><?php echo e($group); ?></h5>
                    <div class="row">
                        <?php foreach ($rows as $row): ?>
                            <div class="<?php echo $row['input_type'] === 'textarea' ? 'col-lg-12' : 'col-lg-6'; ?> mb-3">
                                <label class="form-label" for="content_<?php echo e($row['id']); ?>">
                                    <?php echo e($row['label']); ?>
                                    <span class="d-block small text-muted"><?php echo e($row['content_key']); ?></span>
                                </label>
                                <?php if ($row['input_type'] === 'textarea'): ?>
                                    <textarea id="content_<?php echo e($row['id']); ?>" class="form-control" name="content[<?php echo e($row['content_key']); ?>]" rows="3"><?php echo e($row['content_value']); ?></textarea>
                                <?php else: ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control" type="<?php echo $row['input_type'] === 'url' ? 'url' : 'text'; ?>" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</form>

<div class="admin-card video-upload-card mb-4">
    <h4>Cấu hình video hero trang giới thiệu</h4>
    <form id="heroVideoUploadForm" class="video-upload-grid" method="post" action="<?php echo e(BASE_URL); ?>/api/upload-video.php" enctype="multipart/form-data">
        <label class="video-drop-zone" for="heroVideoFile">
            <input type="file" id="heroVideoFile" name="video" accept="video/mp4,video/quicktime,video/webm" required>
            <span class="video-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
            <strong id="heroVideoFileName">Kéo thả hoặc bấm để chọn video</strong>
            <small>MP4, MOV, WEBM. Hệ thống sẽ đổi sang HLS m3u8.</small>
        </label>
        <div class="video-upload-controls">
            <div class="row g-2">
                <div class="col-6">
                    <label for="videoStartSecond">Bắt đầu(s)</label>
                    <input type="number" id="videoStartSecond" class="form-control" name="start_second" min="0" step="0.1" value="0">
                </div>
                <div class="col-6">
                    <label for="videoEndSecond">Kết thúc(s)</label>
                    <input type="number" id="videoEndSecond" class="form-control" name="end_second" min="0" max="120" step="0.1" placeholder="Mặc định 30">
                </div>
            </div>
            <div class="video-current-path"><span>File hiện tại</span><strong id="heroVideoCurrentPath"><?php echo e($heroVideoAdmin); ?></strong></div>
            <button type="submit" class="btn btn-success w-100">Upload và đổi sang m3u8</button>
        </div>
    </form>
    <div class="video-upload-progress" id="heroVideoProgress" hidden>
        <div class="sort-spinner"></div>
        <strong>Đang xử lý video...</strong>
    </div>
    <div class="video-upload-message" id="heroVideoMessage" hidden></div>
</div>

<div class="admin-card mb-4">
    <h4 class="mb-4">Hình ảnh trang giới thiệu</h4>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_about_image">
        <div class="row align-items-end">
            <div class="col-lg-8 mb-3">
                <label class="form-label fw-bold" for="aboutImageFile">Upload hình ảnh</label>
                <input type="file" name="image_file" id="aboutImageFile" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" required>
                <small class="form-text text-muted">Hỗ trợ JPG, PNG, WEBP, GIF. Giới hạn 5MB.</small>
            </div>
            <div class="col-lg-4 mb-3">
                <label class="form-label fw-bold d-block">Hình hiện tại</label>
                <div class="admin-image-preview about-current-image-preview border rounded d-flex align-items-center justify-content-center overflow-hidden">
                    <?php if (!empty($aboutPage['image'])): ?>
                        <img src="<?php echo e(media_url($aboutPage['image'])); ?>" alt="Preview">
                    <?php else: ?>
                        <span class="text-muted">Chưa có ảnh</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success px-5 mt-2">Lưu hình ảnh</button>
    </form>
</div>

<?php
$extraScripts = '
<script>
const heroVideoUploadForm = document.getElementById("heroVideoUploadForm");
const heroVideoFile = document.getElementById("heroVideoFile");
const heroVideoFileName = document.getElementById("heroVideoFileName");
const heroVideoProgress = document.getElementById("heroVideoProgress");
const heroVideoMessage = document.getElementById("heroVideoMessage");
const heroVideoCurrentPath = document.getElementById("heroVideoCurrentPath");
const maxHeroVideoSize = 512 * 1024 * 1024;

if (heroVideoFile) {
    heroVideoFile.addEventListener("change", () => {
        heroVideoFileName.textContent = heroVideoFile.files[0] ? heroVideoFile.files[0].name : "Kéo thả hoặc bấm để chọn video";
    });
}

if (heroVideoUploadForm) {
    heroVideoUploadForm.addEventListener("submit", event => {
        event.preventDefault();
        if (heroVideoFile.files[0] && heroVideoFile.files[0].size > maxHeroVideoSize) {
            heroVideoMessage.textContent = "Video vượt quá giới hạn 512MB. Hãy cắt ngắn hơn hoặc nén file trước khi upload.";
            heroVideoMessage.className = "video-upload-message is-error";
            heroVideoMessage.hidden = false;
            return;
        }

        const form = new FormData(heroVideoUploadForm);
        heroVideoProgress.hidden = false;
        heroVideoMessage.hidden = true;
        heroVideoUploadForm.classList.add("is-uploading");

        fetch("' . BASE_URL . '/api/upload-video.php", {
            method: "POST",
            body: form
        })
            .then(response => response.text().then(text => {
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    throw new Error("Server không trả về JSON. Hãy kiểm tra log Apache/PHP.");
                }
                if (!response.ok) {
                    throw new Error(data.message || "Upload video thất bại.");
                }
                return data;
            }))
            .then(data => {
                if (!data.success) {
                    throw new Error(data.detail || data.message || "Không upload được video.");
                }
                heroVideoCurrentPath.textContent = data.path;
                document.querySelectorAll(\'input[name="content[about.hero_video]"]\').forEach(input => {
                    input.value = data.path;
                });
                heroVideoMessage.textContent = data.message || "Đã cập nhật video hero.";
                heroVideoMessage.className = "video-upload-message is-success";
            })
            .catch(error => {
                heroVideoMessage.textContent = error.message || "Upload video thất bại.";
                heroVideoMessage.className = "video-upload-message is-error";
            })
            .finally(() => {
                heroVideoProgress.hidden = true;
                heroVideoMessage.hidden = false;
                heroVideoUploadForm.classList.remove("is-uploading");
            });
    });
}
</script>';
admin_layout_end($extraScripts);
?>

<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => $pageTitle
]);
$p = $product ?? [];
?>

<div class="card shadow-sm border-0 rounded-4 mt-4">
    <div class="card-body p-4">

        <form action="" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả chi tiết</label>
                        <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Danh mục</label>
                        <select name="category" class="form-select">
                            <option value="Để bàn" <?= ($p['category'] ?? '') == 'Để bàn' ? 'selected' : '' ?>>Để bàn</option>
                            <option value="Sàn nhà" <?= ($p['category'] ?? '') == 'Sàn nhà' ? 'selected' : '' ?>>Sàn nhà</option>
                            <option value="Ban công" <?= ($p['category'] ?? '') == 'Ban công' ? 'selected' : '' ?>>Ban công</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá bán (VNĐ)</label>
                        <input type="number" name="price" class="form-control" value="<?= $p['price'] ?? 0 ?>" required>
                    </div>

                    <!-- UPLOAD HÌNH ẢNH -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hình ảnh sản phẩm</label>
                        <?php if (!empty($p['image'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL . '/' . $p['image'] ?>" alt="Preview" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG, WEBP (Tối đa 5MB)</small>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="feat" <?= ($p['is_featured'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="feat">Sản phẩm nổi bật</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-2"></i> Lưu sản phẩm</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>

<?php
require_once __DIR__ . '/includes/AdminLayout.php';
$pageTitle = 'Quản lý Sản phẩm | Plantify Admin';

$actionHtml = '<a href="' . BASE_URL . '/admin/product_create" class="btn btn-primary rounded-pill px-4"><i class="fa fa-plus me-2"></i>Thêm sản phẩm</a>';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý Sản phẩm',
    'subtitle' => 'Danh sách cây cảnh và phụ kiện trong hệ thống.',
    'actionHtml' => $actionHtml
]);
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Nổi bật</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?= BASE_URL . '/' . htmlspecialchars($p['image']) ?>"
                                                alt="<?= htmlspecialchars($p['name']) ?>"
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                class="rounded shadow-sm border">
                                        <?php else: ?>
                                            <div class="bg-light d-inline-flex align-items-center justify-content-center rounded"
                                                style="width: 60px; height: 60px;">
                                                <i class="fa-solid fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td class="text-center"><span class="badge bg-info text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
                                    <td class="text-center"><?= number_format($p['price'], 0, ',', '.') ?>đ</td>
                                    <td class="text-center">
                                        <?= $p['is_featured'] ? '<span class="text-warning"><i class="fa fa-star"></i></span>' : '' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/admin/product_edit/<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm mx-1">
                                            <i class="fa-solid fa-pen-to-square"></i> Sửa
                                        </a>
                                    <form action="<?= BASE_URL ?>/admin/product_delete/<?= $p['id'] ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm mx-1" onclick="return confirm('Xóa sản phẩm này?')">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                    </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>
<?php

/**
 * File: admin/shop_settings.php
 */
require_once __DIR__ . '/includes/AdminLayout.php';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Cấu hình giao diện Cửa hàng',
    'subtitle' => 'Chỉnh sửa các tiêu đề, nút bấm và nhãn hiển thị trên trang bán hàng.'
]);
?>

<div class="row">
    <div class="col-12 mt-4">
        <?php if (isset($_SESSION['admin_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $_SESSION['admin_success'];
                                                        unset($_SESSION['admin_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> <?= $_SESSION['admin_error'];
                                                                unset($_SESSION['admin_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <?= csrf_field() ?>
            <?php foreach ($settingsByGroup as $groupName => $items): ?>
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 text-success fw-bold"><i class="ti-settings me-2"></i> <?= htmlspecialchars($groupName) ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-dark">
                                        <?= htmlspecialchars($item['label']) ?>
                                        <small class="text-muted fw-normal d-block" style="font-size: 0.7rem;">Key: <?= $item['content_key'] ?></small>
                                    </label>

                                    <?php if ($item['input_type'] === 'textarea'): ?>
                                        <textarea name="content[<?= $item['content_key'] ?>]" class="form-control" rows="3"><?= htmlspecialchars($item['content_value']) ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="content[<?= $item['content_key'] ?>]" class="form-control" value="<?= htmlspecialchars($item['content_value']) ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="settings-actions mb-5 text-end">
                <button type="submit" name="update_shop_content" class="btn btn-success btn-lg px-5 rounded-pill shadow">
                    <i class="fa-solid fa-save me-2"></i> Lưu tất cả thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>

<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => $pageTitle, 'heading' => $pageTitle]);
?>
<div class="card shadow-sm border-0 rounded-4 mt-4">
    <div class="card-body p-4">
        <form action="" method="POST">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Họ và Tên</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Chức vụ</label>
                    <select name="role" class="form-select">
                        <option value="member">Thành viên (Member)</option>
                        <option value="admin">Quản trị (Admin)</option>
                    </select>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-success px-5">Lưu thành viên</button>
            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-light ms-2">Hủy</a>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>

<?php
require_once __DIR__ . '/includes/AdminLayout.php';
$pageTitle = 'Quản lý Người dùng | Plantify Admin';
$actionHtml = '<a href="' . BASE_URL . '/admin/user_create" class="btn btn-primary rounded-pill px-4"><i class="fa fa-plus me-2"></i>Thêm thành viên</a>';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý Người dùng',
    'actionHtml' => $actionHtml
]);

?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên Đăng Nhập</th>
                                <th>Họ và Tên</th>
                                <th>Chức Vụ</th>
                                <th>Email</th>
                                <th>Trạng Thái</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                                    <td>
                                        <?php if ($u['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Quản trị viên</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Thành viên</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['status'] == 'active'): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Bị Khoá</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Hành động -->
                                    <td>
                                        <?php if ($u['role'] != 'admin'): ?>
                                            <form action="<?= BASE_URL ?>/admin/reset_password/<?= $u['id'] ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-warning btn-sm mx-1 text-white" onclick="return confirm('Bạn có chắc muốn cấp lại mật khẩu mặc định (123456) cho tài khoản này không?')">
                                                    <i class="fa-solid fa-key"></i> Reset
                                                </button>
                                            </form>

                                            <?php if ($u['status'] == 'active'): ?>
                                                <form action="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Bạn có muốn khoá quyền truy cập của người này?')">
                                                        <i class="fa-solid fa-lock"></i> Khoá
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-success btn-sm mx-1">
                                                        <i class="fa-solid fa-unlock"></i> Mở
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form action="<?= BASE_URL ?>/admin/delete_user/<?= $u['id'] ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Xóa người dùng này? Hành động này không thể hoàn tác!')">
                                                    <i class="fa-solid fa-trash"></i> Xóa
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fa-solid fa-shield"></i> Không thể can thiệp</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Hành động -->
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Hàm này tự động đóng div nội dung và tự động nạp luôn bootstrap, scripts.js
admin_layout_end();
?>
<?php

/**
 * File: app/Views/pages/login.php
 * Chức năng: Trang đăng nhập đồng bộ UI
 */
$pageTitle = 'Đăng nhập | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); display: flex; align-items: center; padding: 40px 0; margin-top:50px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <!-- Khối Card Đăng Nhập -->
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                    <div class="row g-0">

                        <!-- Cột Trái: Hình ảnh minh họa -->
                        <div class="col-lg-6 d-none d-lg-block position-relative">
                            <img src="<?= BASE_URL ?>/assets/images/hero_img.jpg"
                                alt="Plantify Login"
                                class="w-100 h-100 object-fit-cover"
                                style="min-height: 600px;">
                            <!-- Overlay Text -->
                            <div class="position-absolute bottom-0 start-0 w-100 p-5" style="background: linear-gradient(to top, rgba(18, 56, 42, 0.9), transparent);">
                                <h2 class="text-white fw-bold mb-2">Chào mừng trở lại!</h2>
                                <p class="text-white opacity-75 mb-0">Tiếp tục hành trình xây dựng không gian xanh của riêng bạn cùng Plantify.</p>
                            </div>
                        </div>

                        <!-- Cột Phải: Form Đăng Nhập -->
                        <div class="col-lg-6 d-flex align-items-center bg-white p-4 p-md-5">
                            <div class="w-100">
                                <div class="text-center mb-5">
                                    <div class="brand-mark mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                                        <i class="fa-solid fa-leaf"></i>
                                    </div>
                                    <h2 style="color: var(--green-900); font-weight: 820;">Đăng Nhập</h2>
                                    <p class="text-muted">Vui lòng nhập thông tin để truy cập hệ thống</p>
                                </div>

                                <!-- Thông báo lỗi/thành công từ PHP -->
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Form Đăng Nhập -->
                                <form action="<?= BASE_URL ?>/auth/login" method="POST" id="loginForm" novalidate>

                                    <!-- Nhập Username / Email -->
                                    <div class="mb-4">
                                        <label for="username" class="form-label fw-bold" style="color: var(--stone-700);">Tài khoản hoặc Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0" style="color: var(--green-700);"><i class="fa-solid fa-envelope"></i></span>
                                            <input type="text" name="username" id="username" class="form-control bg-light border-start-0 ps-0"
                                                placeholder="Nhập tên đăng nhập hoặc email"
                                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                        </div>
                                        <small id="usernameError" class="text-danger mt-1 fw-semibold" style="display: none;"></small>
                                    </div>

                                    <!-- Nhập Mật khẩu -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label for="password" class="form-label fw-bold mb-0" style="color: var(--stone-700);">Mật khẩu</label>
                                            <a href="#" class="text-success text-decoration-none small fw-semibold">Quên mật khẩu?</a>
                                        </div>
                                        <div class="input-group mt-2 position-relative">
                                            <span class="input-group-text bg-light border-end-0" style="color: var(--green-700);"><i class="fa-solid fa-lock"></i></span>
                                            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 ps-0 pe-5"
                                                placeholder="Nhập mật khẩu" required>
                                            <!-- Nút ẩn hiện mật khẩu -->
                                            <span id="togglePassword" class="position-absolute end-0 top-50 translate-middle-y pe-3"
                                                style="cursor: pointer; z-index: 10; color: var(--stone-700);">
                                                <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                            </span>
                                        </div>
                                        <small id="passwordError" class="text-danger mt-1 fw-semibold" style="display: none;"></small>
                                    </div>

                                    <!-- Nút Submit -->
                                    <div class="d-grid gap-3 mt-5">
                                        <button type="submit" class="btn btn-success btn-lg fw-bold" style="height: 52px; border-radius: 12px;">
                                            Đăng Nhập
                                        </button>
                                        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-success btn-lg fw-bold" style="height: 52px; border-radius: 12px;">
                                            Tạo Tài Khoản Mới
                                        </a>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- javascript check validation (client-side) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý Validation Form
        const loginForm = document.getElementById('loginForm');

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                let u = document.getElementById('username').value.trim();
                let p = document.getElementById('password').value.trim();
                let isValid = true;

                // DOM Errors
                let uError = document.getElementById('usernameError');
                let pError = document.getElementById('passwordError');

                // Reset errors
                uError.style.display = 'none';
                pError.style.display = 'none';

                // Validate Username
                if (!u) {
                    uError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Vui lòng nhập tên tài khoản hoặc email';
                    uError.style.display = 'block';
                    isValid = false;
                }

                // Validate Password
                if (!p) {
                    pError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Vui lòng nhập mật khẩu';
                    pError.style.display = 'block';
                    isValid = false;
                } else if (p.length < 3) {
                    pError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Mật khẩu phải có ít nhất 3 ký tự';
                    pError.style.display = 'block';
                    isValid = false;
                }

                // Ngăn chặn submit nếu có lỗi
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }

        // Xử lý Ẩn/Hiện mật khẩu bằng icon FontAwesome
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Đổi type input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Đổi icon
                if (type === 'text') {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>

<?php

/**
 * File: app/Views/pages/register.php
 * Chức năng: Trang đăng ký tài khoản đồng bộ UI
 */
$pageTitle = 'Đăng ký Tài khoản | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); display: flex; align-items: center; padding: 40px 0; margin-top:50px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <!-- Khối Card Đăng Ký -->
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                    <div class="row g-0 flex-lg-row-reverse"> <!-- Đảo ngược cột: Form bên trái, Ảnh bên phải cho khác biệt với Login -->

                        <!-- Cột Ảnh minh họa -->
                        <div class="col-lg-5 d-none d-lg-block position-relative">
                            <img src="<?= BASE_URL ?>/assets/images/hero_img.jpg"
                                alt="Plantify Register"
                                class="w-100 h-100 object-fit-cover"
                                style="min-height: 700px;">
                            <div class="position-absolute bottom-0 start-0 w-100 p-5" style="background: linear-gradient(to top, rgba(18, 56, 42, 0.95), transparent);">
                                <h2 class="text-white fw-bold mb-2">Bắt đầu ngay hôm nay</h2>
                                <p class="text-white opacity-75 mb-0">Tạo tài khoản để quản lý đơn hàng, lưu danh sách yêu thích và nhận cẩm nang chăm sóc cây xanh độc quyền.</p>
                            </div>
                        </div>

                        <!-- Cột Form Đăng Ký -->
                        <div class="col-lg-7 d-flex align-items-center bg-white p-4 p-md-5">
                            <div class="w-100">
                                <div class="mb-4">
                                    <h2 style="color: var(--green-900); font-weight: 820;">Đăng Ký Tài Khoản</h2>
                                    <p class="text-muted">Điền thông tin dưới đây để trở thành thành viên của Plantify</p>
                                </div>

                                <!-- Thông báo lỗi/thành công -->
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Form Đăng Ký -->
                                <form action="<?= BASE_URL ?>/auth/register" method="POST" id="regForm" novalidate>
                                    <div class="row g-3">

                                        <!-- Họ và Tên -->
                                        <div class="col-md-6">
                                            <label for="fullname" class="form-label fw-bold small" style="color: var(--stone-700);">Họ và Tên</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-id-card"></i></span>
                                                <input type="text" name="fullname" id="fullname" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="Nhập họ và tên đầy đủ" value="<?= htmlspecialchars($data['fullname'] ?? '') ?>" required>
                                            </div>
                                            <small id="fullnameError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Tên đăng nhập -->
                                        <div class="col-md-6">
                                            <label for="username" class="form-label fw-bold small" style="color: var(--stone-700);">Tên đăng nhập</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-user"></i></span>
                                                <input type="text" name="username" id="username" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="Viết liền không dấu" value="<?= htmlspecialchars($data['username'] ?? '') ?>" required>
                                            </div>
                                            <small id="usernameError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-12">
                                            <label for="email" class="form-label fw-bold small" style="color: var(--stone-700);">Địa chỉ Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-envelope"></i></span>
                                                <input type="email" name="email" id="email" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="example@domain.com" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
                                            </div>
                                            <small id="emailError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Mật khẩu -->
                                        <div class="col-12">
                                            <label for="password" class="form-label fw-bold small" style="color: var(--stone-700);">Mật khẩu</label>
                                            <div class="input-group position-relative">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-lock"></i></span>
                                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0 ps-0 pe-5"
                                                    placeholder="Nhập mật khẩu" required>
                                                <!-- Nút Ẩn/Hiện -->
                                                <span id="togglePassword" class="position-absolute end-0 top-50 translate-middle-y pe-3" style="cursor: pointer; z-index: 10; color: var(--stone-700);">
                                                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                                </span>
                                            </div>
                                            <small id="passwordError" class="text-danger mt-1 fw-semibold d-none"></small>

                                            <!-- UI Bảng điều kiện mật khẩu -->
                                            <div class="password-requirements p-3 mt-3 rounded" id="passwordReqs" style="background: var(--mint-50); border: 1px dashed var(--green-300); display: none;">
                                                <strong class="d-block mb-2" style="color: var(--green-900); font-size: 0.9rem;">Yêu cầu mật khẩu an toàn:</strong>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                                    <li id="req-length" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 6 ký tự</li>
                                                    <li id="req-upper" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ hoa (A-Z)</li>
                                                    <li id="req-lower" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ thường (a-z)</li>
                                                    <li id="req-number" class="text-muted"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ số (0-9)</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 mt-4 pt-2">
                                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold mb-3" id="submitBtn" style="height: 52px; border-radius: 12px;">
                                                Tạo Tài Khoản
                                            </button>
                                            <div class="text-center">
                                                <span class="text-muted">Đã có tài khoản?</span>
                                                <a href="<?= BASE_URL ?>/auth" class="text-success fw-bold text-decoration-none">Đăng nhập ngay</a>
                                            </div>
                                        </div>

                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JS Client Side Validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('regForm');
        const fullname = document.getElementById('fullname');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');

        // 1. Password Strength Checker (Giao diện FontAwesome)
        password.addEventListener('input', function() {
            const val = this.value;
            const reqs = document.getElementById('passwordReqs');

            // Hiện bảng điều kiện khi bắt đầu nhập
            if (val.length > 0) {
                reqs.style.display = 'block';
                reqs.style.animation = 'adminCardIn 0.3s ease forwards'; // Kế thừa animation từ style.css
            } else {
                reqs.style.display = 'none';
            }

            const hasLength = val.length >= 6;
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);

            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-number', hasNumber);
        });

        function updateReq(id, isValid) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i');

            if (isValid) {
                el.className = 'text-success mb-1 fw-semibold';
                icon.className = 'fa-solid fa-circle-check me-2';
            } else {
                el.className = 'text-muted mb-1';
                icon.className = 'fa-regular fa-circle-xmark me-2';
            }
        }

        // 2. Ẩn/Hiện mật khẩu
        const togglePassword = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                if (type === 'text') {
                    toggleIcon.className = 'fa-solid fa-eye-slash';
                } else {
                    toggleIcon.className = 'fa-solid fa-eye';
                }
            });
        }

        // 3. Form Validation Submit
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Clear errors
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('d-none');
                el.classList.remove('d-block');
            });
            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

            // Validate fullname
            if (!fullname.value.trim() || fullname.value.trim().length < 3) {
                showError('fullname', 'Họ tên phải có ít nhất 3 ký tự');
                isValid = false;
            }

            // Validate username
            if (!username.value.trim() || username.value.trim().length < 3) {
                showError('username', 'Tên đăng nhập phải có ít nhất 3 ký tự');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_-]+$/.test(username.value)) {
                showError('username', 'Chỉ dùng chữ cái, số, _, -');
                isValid = false;
            }

            // Validate email
            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                showError('email', 'Email không hợp lệ');
                isValid = false;
            }

            // Validate password
            if (!password.value || password.value.length < 6) {
                showError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
                isValid = false;
            }

            if (isValid) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang xử lý...';
            } else {
                e.preventDefault();
            }
        });

        function showError(fieldId, message) {
            const errorEl = document.getElementById(fieldId + 'Error');
            const inputEl = document.getElementById(fieldId);
            errorEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + message;
            errorEl.classList.remove('d-none');
            errorEl.classList.add('d-block');
            inputEl.classList.add('is-invalid');
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php
$pageTitle = 'Dashboard Thành Viên | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
// Location: app/Views/dashboard/index.php
// Xác định avatar (Nếu chưa có thì dùng ảnh mặc định)
$avatar = !empty($user['avatar'])
    ? BASE_URL . '/file/render?path=' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname']);
?>?>

<main class="site-main bg-soft" style="min-height: calc(100vh - 76px); padding: 50px 0;">
    <div class="container">

        <div class="row align-items-center mb-4 pb-3 border-bottom" data-aos="fade-up">
            <div class="col-md-6">
                <h1 style="color: var(--green-900); font-weight: 850;">Tài Khoản Của Tôi</h1>
                <p class="text-muted mb-0">Quản lý hồ sơ cá nhân và bảo mật</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/auth/logout" class="btn btn-outline-danger px-4 rounded-pill">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng Xuất
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $_SESSION['error'];
                                                                    unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mt-2">

            <!-- SIDEBAR THÔNG TIN TÓM TẮT -->
            <div class="col-lg-4" data-aos="fade-right">
                <div class="bg-white p-4 text-center shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="<?= $avatar ?>" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm border border-3 border-white" style="width: 150px; height: 150px;" id="avatarPreviewSidebar">
                        <span class="badge bg-success position-absolute bottom-0 end-0 rounded-circle p-2 border border-2 border-white" title="Tài khoản Active">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </div>

                    <h3 style="color: var(--green-900); font-weight: 800;"><?= htmlspecialchars($user['fullname']) ?></h3>
                    <p class="text-muted mb-1"><i class="fa-solid fa-envelope me-2"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-muted"><i class="fa-solid fa-user-tag me-2"></i> Thành viên Plantify</p>

                    <hr class="my-4">

                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-success fw-bold text-start"><i class="fa-solid fa-user-pen me-2 w-20px text-center"></i> Hồ sơ cá nhân</a>
                        <a href="<?= BASE_URL ?>/dashboard/orders" class="btn btn-light fw-bold text-start border"><i class="fa-solid fa-box-open me-2 w-20px text-center text-success"></i> Đơn hàng của tôi</a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT: FORM CẬP NHẬT -->
            <div class="col-lg-8" data-aos="fade-left">
                <div class="bg-white p-4 p-md-5 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                    <h4 class="mb-4 fw-bold" style="color: var(--stone-900);">Thông tin cá nhân</h4>

                    <!-- Chú ý thêm enctype="multipart/form-data" để upload được ảnh -->
                    <form action="<?= BASE_URL ?>/dashboard/updateProfile" method="POST" enctype="multipart/form-data">

                        <!-- Upload Avatar UI -->
                        <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded border">
                            <img src="<?= $avatar ?>" id="avatarPreviewForm" class="rounded-circle object-fit-cover" style="width: 80px; height: 80px;">
                            <div>
                                <label for="avatarUpload" class="btn btn-outline-success btn-sm mb-2 fw-bold">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Đổi ảnh đại diện
                                </label>
                                <input type="file" name="avatar" id="avatarUpload" class="d-none" accept="image/png, image/jpeg, image/webp">
                                <div class="small text-muted">Hỗ trợ JPG, PNG, WEBP. Tối đa 5MB.</div>
                            </div>
                        </div>

                        <!-- Fullname -->
                        <div class="mb-3">
                            <label for="fullname" class="form-label fw-bold text-muted">Họ và Tên</label>
                            <input type="text" class="form-control bg-light" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>

                        <!-- Username (Readonly) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Tên đăng nhập <span class="badge bg-secondary ms-2">Không thể đổi</span></label>
                            <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                        </div>

                        <!-- Email (Readonly) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Địa chỉ Email <span class="badge bg-secondary ms-2">Không thể đổi</span></label>
                            <input type="email" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="btn btn-success btn-lg px-5 fw-bold" style="border-radius: 10px;">
                            Lưu Thay Đổi
                        </button>
                    </form>
                </div>

            </div>
            <!-- Password -->
            <div class="bg-white p-4 p-md-5 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                <h4 class="mb-4 fw-bold text-success">Bảo mật tài khoản</h4>
                <form action="<?= BASE_URL ?>/dashboard/updatePassword" method="POST">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label text-muted">Mật khẩu hiện tại</label><input type="password" name="current_password" class="form-control bg-light" required></div>
                        <div class="col-md-6"><label class="form-label text-muted">Mật khẩu mới</label><input type="password" name="new_password" class="form-control bg-light" required></div>
                        <div class="col-md-6"><label class="form-label text-muted">Xác nhận mật khẩu</label><input type="password" name="confirm_password" class="form-control bg-light" required></div>
                    </div>
                    <button type="submit" class="btn btn-outline-success mt-4 px-5">Cập nhật mật khẩu</button>
                </form>
            </div>

        </div>
    </div>
</main>

<!-- Script xử lý Preview ảnh khi vừa chọn file -->
<script>
    document.getElementById('avatarUpload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Kiểm tra dung lượng (5MB = 5 * 1024 * 1024 bytes)
            if (file.size > 5242880) {
                alert("File quá lớn. Vui lòng chọn ảnh dưới 5MB.");
                this.value = ""; // Xóa lựa chọn
                return;
            }

            // Đọc file và hiển thị
            const reader = new FileReader();
            reader.onload = function(e) {
                // Đổi src của 2 thẻ img
                document.getElementById('avatarPreviewForm').src = e.target.result;
                document.getElementById('avatarPreviewSidebar').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/dashboard/order-detail.php -->
<main class="site-main page-main bg-soft" style="margin-top: 100px; min-height: 80vh;">
    <div class="container py-5">
        <div class="mb-4">
            <a href="<?= BASE_URL ?>/dashboard/orders" class="text-decoration-none text-success fw-bold">
                <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="mb-0 fw-bold text-dark">Kiện hàng gồm có</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>"
                                    width="70" class="rounded-3 me-3 border" style="object-fit: cover; height: 70px;" alt="<?= htmlspecialchars($item['name']) ?>">

                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold"><?= $item['name'] ?></h6>
                                    <small class="text-muted">Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?>đ</small>
                                    <br>
                                    <small class="text-muted">Số lượng: <strong><?= $item['quantity'] ?></strong></small>
                                </div>
                                <div class="fw-bold text-success" style="font-size: 1.1rem;">
                                    <?= number_format($item['quantity'] * $item['price'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <hr class="text-muted opacity-25">
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <span class="h5 mb-0 text-dark">Tổng thanh toán:</span>
                            <span class="h4 mb-0 text-success fw-bold"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Trạng thái đơn hàng</h5>
                        <?php
                        $badgeInfo = [
                            'pending'    => ['bg-warning text-dark', 'Chờ xử lý', 'Đơn hàng đang chờ nhân viên xác nhận.'],
                            'processing' => ['bg-info text-dark', 'Đang đóng gói', 'Kho đang chuẩn bị cây cho bạn.'],
                            'shipping'   => ['bg-primary text-white', 'Đang giao hàng', 'Đơn hàng đang trên đường đến.'],
                            'completed'  => ['bg-success text-white', 'Đã hoàn thành', 'Giao hàng thành công. Cảm ơn bạn!'],
                            'cancelled'  => ['bg-danger text-white', 'Đã hủy', 'Đơn hàng đã bị hủy.']
                        ];
                        $statusClass = $badgeInfo[$order['status']][0];
                        $statusLabel = $badgeInfo[$order['status']][1];
                        $statusDesc  = $badgeInfo[$order['status']][2];
                        ?>

                        <div class="mb-3">
                            <span class="badge rounded-pill px-3 py-2 fs-6 <?= $statusClass ?>"><?= $statusLabel ?></span>
                        </div>
                        <p class="text-muted small mb-0"><?= $statusDesc ?></p>

                        <hr>

                        <h5 class="fw-bold mb-3">Thông tin nhận hàng</h5>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-2"><i class="fa-solid fa-user me-2"></i> <strong><?= htmlspecialchars($order['fullname']) ?></strong></li>
                            <li class="mb-2"><i class="fa-solid fa-phone me-2"></i> <?= htmlspecialchars($order['phone']) ?></li>
                            <li class="mb-2"><i class="fa-solid fa-location-dot me-2"></i> <?= htmlspecialchars($order['address']) ?></li>
                            <?php if (!empty($order['note'])): ?>
                                <li class="mt-3 text-warning-emphasis bg-warning-subtle p-2 rounded">
                                    <i class="fa-solid fa-note-sticky me-1"></i> Ghi chú: <?= htmlspecialchars($order['note']) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/dashboard/orders.php -->
<main class="site-main page-main bg-soft" style="margin-top: 100px; min-height: 80vh;">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0">Lịch sử đơn hàng</h3>
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-outline-success rounded-pill px-4">Tiếp tục mua
                            sắm</a>
                    </div>

                    <?php if (empty($myOrders)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Địa chỉ</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myOrders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#ORD-<?= $order['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td class="text-truncate" style="max-width: 200px;"><?= $order['address'] ?></td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <span
                                            class="badge rounded-pill 
                                                    <?= $order['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/dashboard/order_detail/<?= $order['id'] ?>"
                                            class="btn btn-sm btn-outline-success rounded-pill">
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>

<?php

/**
 * File: app/Views/news/detail.php
 * Giao diện chi tiết bài viết - Đã đồng bộ Bootstrap 5 & style.css
 */
require BASE_PATH . '/app/Views/partials/header.php';
?>

<div class="news-detail-wrapper py-5 bg-soft">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4" data-aos="fade-up">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/news" class="text-success text-decoration-none">Tin tức</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($news['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">
            <!-- CỘT NỘI DUNG CHÍNH -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-duration="800">
                <main class="news-detail-main bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light">

                    <header class="news-detail-header mb-4">
                        <h1 class="display-6 fw-bold mb-3" style="color: var(--green-900); line-height: 1.3;"><?= e($news['title']) ?></h1>
                        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                            <span><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y H:i', strtotime($news['created_at'])) ?></span>
                            <span><i class="fa-solid fa-user-pen me-1"></i> <?= e($news['author'] ?? 'Admin') ?></span>
                            <span><i class="fa-regular fa-comment-dots me-1"></i> <?= $commentCount ?? 0 ?> bình luận</span>
                        </div>
                    </header>

                    <div class="news-detail-thumb rounded-4 overflow-hidden mb-5">
                        <?php
                        $thumbPath = !empty($news['thumbnail']) ? PUBLIC_PATH . '/' . ltrim($news['thumbnail'], '/') : '';
                        if (!empty($news['thumbnail']) && file_exists($thumbPath)): ?>
                            <img src="<?= BASE_URL ?>/<?= ltrim($news['thumbnail'], '/') ?>" alt="<?= e($news['title']) ?>" class="w-100 img-fluid object-fit-cover" style="max-height: 450px;">
                        <?php else: ?>
                            <div class="news-img-placeholder w-100 d-flex align-items-center justify-content-center" style="height: 300px; background: var(--green-100); color: var(--green-600); font-size: 5rem;">
                                <i class="fa-solid fa-leaf"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="news-detail-content lh-lg" style="font-size: 1.1rem; color: var(--stone-900);">
                        <?= $news['content'] ?>
                    </div>

                    <?php if (!empty($news['tags'])): ?>
                        <div class="news-detail-tags mt-5 pt-4 border-top d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-stone-700 me-2"><i class="fa-solid fa-tags me-1"></i> Tags:</span>
                            <?php foreach (array_filter(array_map('trim', explode(',', $news['tags']))) as $tag): ?>
                                <a href="<?= BASE_URL ?>/news?search=<?= urlencode($tag) ?>"
                                    class="badge bg-light text-success border text-decoration-none px-3 py-2 rounded-pill transition hover-bg-success">
                                    <?= e($tag) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </main>

                <div class="comments-section mt-5 bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light" id="comments" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="fw-bold mb-4" style="color: var(--green-900);">
                        💬 Bình luận <span class="text-muted fs-5">(<?= $commentCount ?? 0 ?>)</span>
                    </h3>

                    <?php if (!empty($commentError)): ?>
                        <div class="alert alert-danger rounded-3 border-0 bg-danger text-white"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= e($commentError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($commentSuccess)): ?>
                        <div class="alert alert-success rounded-3 border-0 bg-success text-white"><i class="fa-solid fa-circle-check me-2"></i> <?= e($commentSuccess) ?></div>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <div class="comment-form-box p-4 bg-light rounded-4 border mb-5">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen me-2"></i>Viết bình luận của bạn</h5>
                            <form action="<?= BASE_URL ?>/news/comment_post" method="POST" id="commentForm" novalidate>
                                <input type="hidden" name="news_id" value="<?= (int)$news['id'] ?>">
                                <input type="hidden" name="slug" value="<?= e($news['slug']) ?>">

                                <div class="mb-3">
                                    <textarea name="content" id="commentContent"
                                        class="form-control border-0 shadow-sm p-3 rounded-3" rows="4"
                                        placeholder="Chia sẻ ý kiến của bạn về bài viết này..." maxlength="1000" required></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div id="commentErrorBox" class="text-danger small fw-medium" style="display:none;"></div>
                                        <div class="char-counter text-muted small ms-auto" id="charCounter">0 / 1000 ký tự</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-medium">
                                    <i class="fa-regular fa-paper-plane me-2"></i>Gửi bình luận
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="comment-login-prompt p-4 bg-light rounded-4 border text-center mb-5">
                            <i class="fa-solid fa-lock text-muted mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-3 text-stone-700">Bạn cần đăng nhập để tham gia bình luận cùng cộng đồng.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="<?= BASE_URL ?>/auth" class="btn btn-success rounded-pill px-4">Đăng Nhập</a>
                                <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-success rounded-pill px-4">Đăng Ký</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="comments-list">
                        <?php if (empty($comments)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-regular fa-comments fs-2 mb-2"></i>
                                <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                                <div class="comment-item d-flex gap-3 mb-4">
                                    <div class="comment-avatar flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                            <?= mb_strtoupper(mb_substr($c['fullname'] ?? $c['username'] ?? 'U', 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="comment-body flex-grow-1 bg-light p-3 rounded-4 border border-white shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold text-stone-900"><?= e($c['fullname'] ?: $c['username']) ?></h6>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-0 text-stone-700" style="font-size: 0.95rem;"><?= nl2br(e($c['content'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left" data-aos-delay="300">
                <aside class="news-detail-sidebar sticky-top" style="top: 100px;">

                    <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">✍️ Về tác giả</h5>
                        <div class="d-flex align-items-center gap-3">
                            <div class="author-avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-feather-pointed"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 fs-5"><?= e($news['author'] ?? 'Admin') ?></h6>
                                <span class="badge bg-light text-success border">Biên tập viên Plantify Co</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($related)): ?>
                        <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">📚 Bài viết liên quan</h5>
                            <div class="related-list d-flex flex-column gap-3">
                                <?php foreach ($related as $r): ?>
                                    <a href="<?= BASE_URL ?>/news/detail/<?= e($r['slug']) ?>" class="related-item d-flex gap-3 text-decoration-none group">
                                        <div class="related-thumb flex-shrink-0 rounded-3 overflow-hidden" style="width: 80px; height: 60px;">
                                            <?php
                                            $rThumb = !empty($r['thumbnail']) ? PUBLIC_PATH . '/' . ltrim($r['thumbnail'], '/') : '';
                                            if (!empty($r['thumbnail']) && file_exists($rThumb)): ?>
                                                <img src="<?= BASE_URL ?>/<?= ltrim($r['thumbnail'], '/') ?>" alt="" class="w-100 h-100 object-fit-cover">
                                            <?php else: ?>
                                                <div class="w-100 h-100 bg-green-100 d-flex align-items-center justify-content-center text-success fs-4">🌿</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-info flex-grow-1">
                                            <h6 class="text-stone-900 fw-bold mb-1" style="font-size: 0.9rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= e($r['title']) ?>
                                            </h6>
                                            <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($news['tags'])): ?>
                        <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">🏷️ Khám phá chủ đề</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach (array_filter(array_map('trim', explode(',', $news['tags']))) as $tag): ?>
                                    <a href="<?= BASE_URL ?>/news?search=<?= urlencode($tag) ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                        <?= e($tag) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/news" class="btn btn-light border text-stone-700 rounded-pill px-4 py-2 w-100 fw-medium shadow-sm hover-bg-light">
                            <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const content = document.getElementById('commentContent');
        const counter = document.getElementById('charCounter');
        const errBox = document.getElementById('commentErrorBox');
        const form = document.getElementById('commentForm');

        if (content) {
            content.addEventListener('input', function() {
                const len = this.value.length;
                counter.textContent = len + ' / 1000 ký tự';
                if (len > 1000) {
                    counter.classList.add('text-danger');
                } else {
                    counter.classList.remove('text-danger');
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                errBox.style.display = 'none';
                const val = content.value.trim();

                if (!val) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Vui lòng nhập nội dung bình luận!';
                    errBox.style.display = 'block';
                    content.focus();
                    return;
                }
                if (val.length < 5) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Bình luận phải có ít nhất 5 ký tự!';
                    errBox.style.display = 'block';
                    content.focus();
                    return;
                }
                if (val.length > 1000) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Bình luận không được vượt quá 1000 ký tự!';
                    errBox.style.display = 'block';
                    return;
                }
            });
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: app/Views/news/index.php
 * Tất cả văn bản tĩnh đọc từ site_content qua content_value()
 */
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main">

    <!-- ===== HERO ===== -->
    <section class="news-hero">
        <div class="container" data-aos="fade-up">
            <h1><?= e(content_value('news.hero_title', 'Tin Tức & Bài Viết')) ?></h1>
            <p><?= e(content_value('news.hero_description', 'Khám phá các bài viết về cây cảnh, phong thủy và xu hướng trang trí xanh.')) ?>
            </p>
        </div>
    </section>

    <!-- ===== SEARCH ===== -->
    <div class="container">
        <div class="news-search-bar" data-aos="fade-up" data-aos-delay="100">
            <form action="<?= BASE_URL ?>/news" method="GET">
                <input type="text" name="search"
                    placeholder="<?= e(content_value('news.search_placeholder', 'Tìm kiếm tin tức, bài viết...')) ?>"
                    value="<?= e($search ?? '') ?>">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>
                    <?= e(content_value('news.search_button', 'Tìm kiếm')) ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ===== DANH SÁCH BÀI VIẾT ===== -->
    <section class="news-list py-5">
        <div class="container">

            <?php if (!empty($search)): ?>
                <p class="mb-4 text-muted">
                    Kết quả tìm kiếm cho: <strong><?= e($search) ?></strong>
                    (<?= $total ?? 0 ?> bài viết)
                </p>
            <?php endif; ?>

            <?php if (empty($newsList)): ?>
                <div class="alert alert-info text-center py-4">
                    <?= e(content_value('news.empty_title', 'Không tìm thấy bài viết nào phù hợp!')) ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($newsList as $news): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <article class="news-card">

                                <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>" class="news-card-img">
                                    <?php
                                    $thumbPath = !empty($news['thumbnail'])
                                        ? PUBLIC_PATH . '/' . ltrim($news['thumbnail'], '/')
                                        : '';
                                    if (!empty($news['thumbnail']) && file_exists($thumbPath)):
                                    ?>
                                        <img src="<?= BASE_URL ?>/<?= ltrim($news['thumbnail'], '/') ?>"
                                            alt="<?= e($news['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="news-img-placeholder">
                                            <i class="fa-solid fa-leaf"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <div class="news-card-body">
                                    <span class="date">
                                        <i class="fa-regular fa-calendar me-2"></i>
                                        <?= date('d/m/Y', strtotime($news['created_at'])) ?>
                                    </span>
                                    <h3>
                                        <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>">
                                            <?= e($news['title']) ?>
                                        </a>
                                    </h3>
                                    <p><?= e($news['short_description'] ?? mb_substr(strip_tags($news['content']), 0, 100) . '...') ?>
                                    </p>
                                </div>

                                <div class="news-card-footer">
                                    <span class="author">
                                        <i class="fa-solid fa-pen-nib me-2"></i>
                                        <?= e($news['author'] ?? 'Admin') ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>" class="read-more">
                                        <?= e(content_value('news.card_readmore', 'Xem chi tiết')) ?>
                                        <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>

                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ===== PHÂN TRANG ===== -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="pagination-wrapper mt-5">
                    <div class="pagination">

                        <?php if ($currentPage > 1): ?>
                            <a
                                href="<?= BASE_URL ?>/news?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a
                                    href="<?= BASE_URL ?>/news?page=<?= $p ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a
                                href="<?= BASE_URL ?>/news?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-right"></i></span>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: app/Views/pages/cart.php
 * Chức năng: Giao diện giỏ hàng đồng bộ UI với style.css
 * Location: app/Views/pages/cart.php
 */
$pageTitle = 'Giỏ Hàng | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
$isCartEmpty = empty($cartItems);

?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); padding: 40px 0; margin-top:50px">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-success text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Giỏ hàng của bạn</li>
            </ol>
        </nav>

        <?php if ($isCartEmpty): ?>
            <!-- GIAO DIỆN GIỎ HÀNG TRỐNG -->
            <div class="row justify-content-center mt-5" data-aos="fade-up">
                <div class="col-lg-6 text-center">
                    <div class="p-5 bg-white rounded shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px !important;">
                        <div class="mb-4 text-success" style="font-size: 5rem;">
                            <i class="fa-solid fa-basket-shopping opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-3"><?= e(content_value('cart.empty_title', 'Giỏ hàng của bạn đang trống')) ?></h2>
                        <p class="text-muted mb-4">
                            <?= e(content_value('cart.empty_text', 'Hãy tiếp tục khám phá...')) ?>
                        </p>
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i> <?= e(content_value('cart.btn_continue_shopping', 'Tiếp tục mua sắm')) ?>
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- GIAO DIỆN KHI CÓ SẢN PHẨM -->
            <div class="row g-4">
                <div class="col-lg-8" data-aos="fade-right">

                    <!-- Hiển thị thông báo xóa thành công -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                            unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white p-4 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                        <h3 class="border-bottom pb-3 mb-4" style="color: var(--green-900); font-weight: 800;">Chi tiết Giỏ Hàng (<?= count($cartItems) ?> sản phẩm)</h3>

                        <!-- Lặp qua DỮ LIỆU THẬT từ Controller -->
                        <?php foreach ($cartItems as $productId => $item): ?>
                            <div class="row align-items-center border-bottom py-3">
                                <div class="col-3 col-md-2">
                                    <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="img-fluid rounded" style="object-fit: cover; aspect-ratio: 1/1;">
                                </div>
                                <div class="col-9 col-md-4">
                                    <h5 class="mb-1 fw-bold"><a href="<?= BASE_URL ?>/shop/detail/<?= $productId ?>" class="text-dark text-decoration-none"><?= $item['name'] ?></a></h5>
                                    <p class="text-muted small mb-0">Phân loại: <?= $item['category'] ?></p>
                                </div>
                                <div class="col-6 col-md-3 mt-3 mt-md-0">
                                    <!-- FORM TĂNG GIẢM SỐ LƯỢNG -->
                                    <form action="<?= BASE_URL ?>/cart/update" method="POST" class="d-flex align-items-center border rounded p-1" style="max-width: 120px;">
                                        <input type="hidden" name="product_id" value="<?= $productId ?>">

                                        <button type="submit" name="action" value="decrease" class="btn btn-sm btn-light border-0"><i class="fa-solid fa-minus"></i></button>

                                        <input type="text" class="form-control border-0 text-center px-0 bg-transparent fw-bold" value="<?= $item['quantity'] ?>" readonly>

                                        <button type="submit" name="action" value="increase" class="btn btn-sm btn-light border-0"><i class="fa-solid fa-plus"></i></button>
                                    </form>
                                </div>
                                <div class="col-4 col-md-2 text-end mt-3 mt-md-0 fw-bold" style="color: var(--green-700);">
                                    <?= number_format($item['subtotal'], 0, ',', '.') ?>đ
                                </div>
                                <div class="col-2 col-md-1 text-end mt-3 mt-md-0">
                                    <!-- NÚT XÓA -->
                                    <a href="<?= BASE_URL ?>/cart/remove/<?= $productId ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ?');"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4">
                            <a href="<?= BASE_URL ?>/shop" class="text-success text-decoration-none fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua sắm
                            </a>
                        </div>
                    </div>
                </div>


                <div class="col-lg-4" data-aos="fade-left">
                    <div class="bg-white p-4 shadow-sm position-sticky" style="border: 1px solid var(--stone-200); border-radius: 16px; top: 100px;">
                        <h4 class="border-bottom pb-3 mb-4 fw-bold" style="color: var(--stone-900);">
                            <?= e(content_value('cart.summary_title', 'Tổng Đơn Hàng')) ?>
                        </h4>

                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span><?= e(content_value('cart.label_subtotal', 'Tạm tính:')) ?></span>
                            <strong><?= number_format($totalPrice, 0, ',', '.') ?>đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span><?= e(content_value('cart.label_shipping', 'Phí vận chuyển:')) ?></span>
                            <span>Chưa tính</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4 align-items-center">
                            <span class="fw-bold" style="font-size: 1.1rem;"><?= e(content_value('cart.label_total', 'Tổng cộng:')) ?></span>
                            <span class="fw-bold" style="font-size: 1.5rem; color: var(--green-700);"><?= number_format($totalPrice, 0, ',', '.') ?>đ</span>
                        </div>

                        <button type="button" class="btn btn-success btn-lg w-100 fw-bold" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                            <?= e(content_value('cart.btn_checkout', 'Thanh Toán Ngay')) ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
    </div>
<?php endif; ?>

</div>

<!-- Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="checkoutModalLabel" style="color: var(--green-900);">Thông tin giao hàng</h5>
                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/dashboard/checkout" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Họ và tên người nhận</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required placeholder="Nhập tên người nhận">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="Nhập số điện thoại liên lạc">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Địa chỉ nhận hàng</label>
                        <textarea name="address" class="form-control" rows="2" required placeholder="Địa chỉ chi tiết (Số nhà, đường, phường/xã...)"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Ghi chú thêm (Nếu có)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Xác nhận Đặt hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<!-- Location: app/Views/pages/checkout.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - BTL Cây Cảnh</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages.css">
</head>
}

.form-row {
grid-template-columns: 1fr;
}
}
</style>
</head>

<body>
    <nav class="navbar">
        <div style="font-size: 20px; font-weight: bold;">🌿 BTL Cây Cảnh</div>
        <div>
            <a href="<?= BASE_URL ?>">Trang Chủ</a>
            <a href="<?= BASE_URL ?>/home/shop">Cửa Hàng</a>
            <a href="<?= BASE_URL ?>/news">Tin Tức</a>
            <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
            <a href="<?= BASE_URL ?>/auth/logout">Đăng Xuất</a>
        </div>
    </nav>

    <div class="container">
        <h1>💳 Thanh Toán Đơn Hàng</h1>

        <div class="checkout-container">
            <!-- Form Thanh Toán -->
            <div class="form-section">
                <h2>📍 Thông Tin Giao Hàng</h2>

                <div class="info-box">
                    <strong>👤 Khách hàng:</strong> <?= htmlspecialchars($user['fullname']) ?><br>
                    <strong>📧 Email:</strong> <?= htmlspecialchars($user['email']) ?>
                </div>

                <form id="checkoutForm" method="POST">
                    <div class="form-group">
                        <label for="phone">📱 Số Điện Thoại</label>
                        <input type="tel" id="phone" name="phone" placeholder="0123 456 789" required>
                    </div>

                    <div class="form-group">
                        <label for="address">🏠 Địa Chỉ Giao Hàng</label>
                        <textarea id="address" name="address" placeholder="Nhập địa chỉ chi tiết" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">Thành Phố</label>
                            <input type="text" id="city" name="city" placeholder="TP HCM" required>
                        </div>
                        <div class="form-group">
                            <label for="district">Quận/Huyện</label>
                            <input type="text" id="district" name="district" placeholder="Quận 1" required>
                        </div>
                    </div>

                    <h2 style="margin-top: 2rem;">💰 Hình Thức Thanh Toán</h2>

                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked>
                            💵 Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="bank">
                            🏦 Chuyển khoản ngân hàng
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="terms" required>
                            Tôi đồng ý với điều khoản và chính sách của cửa hàng
                        </label>
                    </div>

                    <button type="submit" class="btn">✅ Hoàn Tất Đơn Hàng</button>
                </form>
            </div>

            <!-- Tóm Tắt Đơn Hàng -->
            <div class="order-summary">
                <h2>📦 Tóm Tắt Đơn Hàng</h2>

                <div class="summary-item">
                    <span>Cây Hạnh Phúc (x2)</span>
                    <span>300.000 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Cây Dây Ô (x1)</span>
                    <span>120.000 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Phí vận chuyển</span>
                    <span>0 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Tổng Cộng</span>
                    <span>420.000 VNĐ</span>
                </div>

                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem; font-size: 13px; color: #666;">
                    <p><strong>ℹ️ Lưu ý:</strong></p>
                    <ul style="margin-left: 1rem;">
                        <li>Giao hàng trong 2-3 ngày làm việc</li>
                        <li>Miễn phí vận chuyển cho đơn từ 500k</li>
                        <li>Liên hệ: 0123 456 789</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('✅ Đơn hàng của bạn đã được tiếp nhận!\n\nChúng tôi sẽ liên hệ với bạn trong vòng 24 giờ để xác nhận.');
            window.location.href = '<?= BASE_URL ?>/dashboard';
        });
    </script>
</body>

</html>
<?php

/**
 * File: app/Views/pages/faq.php
 * Tất cả văn bản tĩnh đọc từ site_content qua content_value()
 * Location: app/Views/pages/faq.php
 */

$pageTitle       = content_value('faq.meta_title',       'FAQ | Câu hỏi thường gặp về cây cảnh và decor xanh');
$pageDescription = content_value('faq.meta_description', 'Giải đáp câu hỏi về khảo sát, bảo hành, chăm sóc định kỳ, tư vấn online và dịch vụ cây xanh doanh nghiệp.');

require_once BASE_PATH . '/app/Views/partials/header.php';
?>

<!-- ===== HERO ===== -->
<section class="page-hero faq-hero modern-hero">
    <div class="container">
        <div class="row g-5 align-items-end">

            <div class="col-lg-8" data-aos="fade-up">
                <span class="section-kicker">
                    <?= e(content_value('faq.hero_kicker', 'FAQ & tư vấn nhanh')) ?>
                </span>
                <h1><?= e(content_value('faq.hero_title', 'Câu hỏi thường gặp về cây xanh, decor và chăm sóc định kỳ')) ?>
                </h1>
                <p><?= e(content_value('faq.hero_description', 'Tra cứu nhanh các thông tin quan trọng trước khi khảo sát, chọn cây, nhận báo giá hoặc sử dụng gói chăm sóc sau bàn giao.')) ?>
                </p>
                <div class="faq-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="faqSearchInput" type="search"
                        placeholder="<?= e(content_value('faq.hero_search_placeholder', 'Tìm nhanh: bảo hành, khảo sát, gửi ảnh, chăm sóc...')) ?>">
                </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left">
                <div class="hero-insight-card">
                    <i class="fa-solid fa-headset"></i>
                    <strong><?= e(content_value('faq.hero_card_title', 'Cần câu trả lời riêng?')) ?></strong>
                    <span>"Nhấn biểu tượng zalo để liên hệ đội ngũ tư vấn"</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== NỘI DUNG CHÍNH ===== -->
<section class="section-padding faq-modern-section">
    <div class="container">
        <div class="row g-5">

            <!-- SIDEBAR -->
            <div class="col-lg-4" data-aos="fade-right">
                <aside class="faq-side faq-dashboard">
                    <span class="section-kicker">
                        <?= e(content_value('faq.sidebar_kicker', 'Điểm cần biết')) ?>
                    </span>
                    <h2><?= e(content_value('faq.sidebar_title', 'Chuẩn bị trước khi tư vấn')) ?></h2>
                    <p><?= e(content_value('faq.sidebar_description', 'Thông tin càng rõ, phương án cây xanh càng sát nhu cầu và ngân sách.')) ?>
                    </p>

                    <div class="faq-prep-list">
                        <div>
                            <i class="fa-solid fa-camera"></i>
                            <span><?= e(content_value('faq.sidebar_item_1', 'Ảnh tổng thể và góc cần đặt cây')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-sun"></i>
                            <span><?= e(content_value('faq.sidebar_item_2', 'Thời lượng ánh sáng trong ngày')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-ruler-combined"></i>
                            <span><?= e(content_value('faq.sidebar_item_3', 'Kích thước khu vực dự kiến')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-wallet"></i>
                            <span><?= e(content_value('faq.sidebar_item_4', 'Ngân sách hoặc mức ưu tiên')) ?></span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success info-cta" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                        <?= e(content_value('faq.sidebar_cta', 'Tìm hiểu thêm')) ?>
                    </button>
                </aside>

            </div>

            <!-- ACCORDION FAQ -->
            <div class="col-lg-8" data-aos="fade-left">
                <div class="faq-tabs" aria-label="Lọc câu hỏi FAQ">
                    <button type="button" class="faq-filter active" data-filter="all">Tất cả</button>
                    <button type="button" class="faq-filter" data-filter="survey">Khảo sát</button>
                    <button type="button" class="faq-filter" data-filter="care">Chăm sóc</button>
                    <button type="button" class="faq-filter" data-filter="warranty">Bảo hành</button>
                    <button type="button" class="faq-filter" data-filter="online">Online</button>
                </div>

                <div class="accordion custom-accordion faq-accordion-modern" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <?php
                        $question = $faq['question'] ?? '';
                        $answer   = $faq['answer']   ?? '';
                        $haystack = $question . ' ' . $answer;
                        $category = 'all';
                        if (preg_match('/khảo sát|khao sat/iu', $haystack))          $category = 'survey';
                        elseif (preg_match('/bảo hành|bao hanh|thay thế/iu', $haystack)) $category = 'warranty';
                        elseif (preg_match('/online|ảnh|anh/iu', $haystack))          $category = 'online';
                        elseif (preg_match('/chăm sóc|cham soc/iu', $haystack))       $category = 'care';
                        ?>
                    <div class="accordion-item faq-item" data-category="<?= e($category) ?>"
                        data-search="<?= e($haystack) ?>">

                        <h2 class="accordion-header" id="heading<?= $index ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                aria-controls="collapse<?= $index ?>">
                                <span class="faq-number">
                                    <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                                <?= e($faq['question']) ?>
                            </button>
                        </h2>

                        <div id="collapse<?= $index ?>"
                            class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                            aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= e($faq['answer']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="faqEmptyState" class="faq-empty-state" hidden>
                    <i class="fa-regular fa-circle-question"></i>
                    <strong>Chưa tìm thấy câu hỏi phù hợp</strong>
                    <span>Thử từ khóa khác hoặc hỏi trực tiếp đội ngũ chăm sóc ở góc màn hình.</span>
                </div>
            </div>

        </div>
    </div>

        <!-- Modal: Đang cập nhật -->
    <div class="modal fade" id="comingSoonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background:#ffffff;">
                <div class="modal-body text-center p-5">
                    <div class="mb-3 text-success" style="font-size: 3rem;">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Trang đang được cập nhật</h5>
                    <p class="text-muted mb-4">Nội dung này sẽ sớm ra mắt. Cảm ơn bạn đã ghé thăm Plantify!</p>
                    <button type="button" class="btn btn-success rounded-pill px-4" data-bs-dismiss="modal">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ===== 3 BƯỚC SAU FAQ ===== -->
<section class="section-padding bg-soft">
    <div class="container">
        <div class="section-heading text-center" data-aos="fade-up">
            <span class="section-kicker">
                <?= e(content_value('faq.steps_kicker', 'Sau khi có câu trả lời')) ?>
            </span>
            <h2><?= e(content_value('faq.steps_title', 'Quy trình tiếp theo rất gọn')) ?></h2>
        </div>
        <div class="row g-4">

            <div class="col-md-4" data-aos="fade-up">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_1_icon', 'fa-paperclip')) ?>"></i>
                    <h3><?= e(content_value('faq.step_1_title', 'Gửi ảnh và nhu cầu')) ?></h3>
                    <p><?= e(content_value('faq.step_1_text', 'Đính kèm ảnh hiện trạng, phong cách mong muốn và ngân sách dự kiến.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_2_icon', 'fa-comments')) ?>"></i>
                    <h3><?= e(content_value('faq.step_2_title', 'Nhận tư vấn sơ bộ')) ?></h3>
                    <p><?= e(content_value('faq.step_2_text', 'Plantify đề xuất nhóm cây, kích thước chậu và mức chăm sóc phù hợp.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_3_icon', 'fa-calendar-days')) ?>"></i>
                    <h3><?= e(content_value('faq.step_3_title', 'Chốt lịch khảo sát')) ?></h3>
                    <p><?= e(content_value('faq.step_3_text', 'Đội ngũ kiểm tra thực tế trước khi báo giá và triển khai chính thức.')) ?>
                    </p>
                </article>
            </div>

        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>
<?php require_once BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: app/Views/pages/home.php
 * View: Trang chủ Plantify Co
 * Tất cả văn bản đọc từ site_content qua content_value()
 */

$steps = [
    ['number' => '01', 'title' => 'about.process_1_title', 'text' => 'about.process_1_text', 'default_title' => 'Tiếp nhận nhu cầu', 'default_text' => 'Nhận ảnh, mặt bằng, phong cách mong muốn và mức ngân sách dự kiến.'],
    ['number' => '02', 'title' => 'about.process_2_title', 'text' => 'about.process_2_text', 'default_title' => 'Khảo sát điều kiện', 'default_text' => 'Đánh giá ánh sáng, gió, ổ cắm, lối đi, vị trí tưới và rủi ro bẩn sàn.'],
    ['number' => '03', 'title' => 'about.process_3_title', 'text' => 'about.process_3_text', 'default_title' => 'Đề xuất phương án', 'default_text' => 'Gợi ý cây, chậu, bố cục, tần suất chăm sóc và phương án thay thế khi cần.'],
    ['number' => '04', 'title' => 'about.process_4_title', 'text' => 'about.process_4_text', 'default_title' => 'Bàn giao và duy trì', 'default_text' => 'Lắp đặt gọn, hướng dẫn chăm sóc, theo dõi cây sau bàn giao và bảo dưỡng định kỳ.'],
];

?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

<main class="site-main">

    <!-- ===== HERO SECTION ===== -->
    <section class="page-hero modern-hero"
        style="background: linear-gradient(135deg, rgba(18, 56, 42, 0.86), rgba(31, 111, 77, 0.62)), url('<?= BASE_URL ?>/assets/images/hero_img.jpg') center/cover;">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row g-5 align-items-end">

                <div class="col-lg-8" data-aos="fade-up">
                    <span class="section-kicker text-white opacity-75">
                        <?= e(content_value('home.hero_kicker', 'Khởi Đầu Mới')) ?>
                    </span>
                    <h1><?= content_value('home.hero_title', 'Biến Không Gian Sống<br>Thành Vườn Xanh Bình Yên') ?></h1>
                    <p class="text-white opacity-75" style="max-width: 600px;">
                        <?= e(content_value('home.hero_description', 'Khám phá bộ sưu tập cây cảnh tuyển chọn giúp thanh lọc không khí, mang lại cảm giác thư thái và nguồn năng lượng tích cực cho ngôi nhà của bạn.')) ?>
                    </p>
                    <div class="hero-actions">
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-success px-4">
                            <i class="fa-solid fa-bag-shopping me-2"></i>
                            <?= e(content_value('home.hero_btn_primary', 'Mua Sắm Ngay')) ?>
                        </a>
                    <a href="#" class="btn btn-outline-light px-4" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                        <?= e(content_value('home.hero_btn_secondary', 'Tìm Hiểu Thêm')) ?>
                    </a>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-left" data-aos-delay="100">
                    <div class="hero-insight-card">
                        <i class="fa-solid fa-leaf"></i>
                        <strong><?= e(content_value('home.hero_card_title', '100% Cây Khỏe Mạnh')) ?></strong>
                        <span><?= e(content_value('home.hero_card_text', 'Được chăm sóc và kiểm tra kỹ lưỡng bởi chuyên gia thực vật trước khi giao đến tay bạn.')) ?></span>
                    </div>
                </div>

            </div>

            <div class="hero-metrics" data-aos="fade-up" data-aos-delay="200">
                <div>
                    <strong><?= e(content_value('home.metric_1_value', '500+')) ?></strong>
                    <span><?= e(content_value('home.metric_1_label', 'Sản phẩm đa dạng')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_2_value', '100%')) ?></strong>
                    <span><?= e(content_value('home.metric_2_label', 'Giao hàng an toàn')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_3_value', '24/7')) ?></strong>
                    <span><?= e(content_value('home.metric_3_label', 'Hỗ trợ chăm sóc')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_4_value', '30 ngày')) ?></strong>
                    <span><?= e(content_value('home.metric_4_label', 'Đồng hành cùng cây')) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES / ABOUT SECTION ===== -->
    <section class="section-padding bg-soft">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-image-stack position-relative">
                        <img src="<?= BASE_URL ?>/assets/images/home_feature_img.jpeg" class="rounded-4 shadow-lg w-100"
                            alt="Plantify Concept">
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="ps-lg-4">
                        <span class="section-kicker" style="color: var(--green-600);">
                            <?= e(content_value('home.features_kicker', 'Về Chúng Tôi')) ?>
                        </span>
                        <h2 class="display-6 mb-4" style="color: var(--green-900); font-weight: 800;">
                            <?= e(content_value('home.features_title', 'Chăm sóc từ tâm, xanh tươi không gian sống')) ?>
                        </h2>
                        <p class="lead text-muted mb-4">
                            <?= e(content_value('home.features_lead', 'Plantify không chỉ bán cây, chúng tôi trao đi nguồn năng lượng chữa lành từ tự nhiên.')) ?>
                        </p>

                        <div class="about-check-grid mt-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-seedling text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_1', 'Cây trồng hữu cơ chuẩn VietGAP')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-paint-roller text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_2', 'Chậu gốm thủ công nghệ thuật')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-headset text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_3', 'Tư vấn phong thủy miễn phí 24/7')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-truck-fast text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_4', 'Bao bì sinh học bảo vệ môi trường')) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== PRODUCTS SECTION ===== -->
    <section class="section-padding bg-white">
        <div class="container">
            <div class="section-heading text-center mb-5" data-aos="fade-up">
                <span class="section-kicker" style="color:var(--green-600);">
                    <?= e(content_value('home.products_kicker', 'Bộ Sưu Tập Tuyển Chọn')) ?>
                </span>
                <h2 style="color:var(--green-900);font-weight:800;">
                    <?= e(content_value('home.products_title', 'Sản Phẩm Nổi Bật')) ?>
                </h2>
            </div>

            <div class="row g-4">
                <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="product-card h-100 bg-white border-0 shadow-sm"
                        style="border-radius:20px;overflow:hidden;">
                        <div class="position-relative">
                            <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>">
                                <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>"
                                    alt="<?= e($product['name']) ?>" class="w-100 object-fit-cover"
                                    style="height:280px;">
                            </a>
                            <div class="position-absolute top-0 end-0 p-3">
                                <span class="badge bg-white text-success shadow-sm rounded-pill px-3 py-2">Nổi
                                    bật</span>
                            </div>
                        </div>
                        <div class="product-body p-4 text-center">
                            <span class="text-muted small text-uppercase fw-bold"
                                style="letter-spacing:1px;"><?= e($product['category']) ?></span>
                            <h3 class="mt-2 mb-3 fs-5">
                                <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="text-decoration-none"
                                    style="color:var(--green-900);font-weight:700;">
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold" style="color:var(--green-700);font-size:1.1rem;">
                                    <?= number_format($product['price'], 0, ',', '.') ?>đ
                                </span>
                                <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>"
                                    class="btn btn-outline-success btn-sm rounded-pill px-3">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-center text-muted">Đang cập nhật sản phẩm nổi bật...</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5">
                <a href="<?= BASE_URL ?>/shop" class="btn btn-success btn-lg px-5 rounded-pill shadow-sm fw-bold">
                    Xem tất cả cửa hàng <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== STORY SECTION ===== -->
    <section class="section-padding about-story-section bg-soft">
        <div class="container">
            <div class="row g-5 align-items-center">

                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-image-stack">
                        <img src="<?= BASE_URL ?>/assets/images/home_bottom_img.jpeg" alt="Câu chuyện Plantify"
                            class="img-fluid rounded-image">
                        <div class="image-note">
                            <strong>Đồng hành cùng sự phát triển</strong>
                            <span>Chúng tôi cung cấp kiến thức để bất kỳ ai cũng có thể làm vườn.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <span class="section-kicker">
                        <?= e(content_value('home.story_kicker', 'Câu Chuyện Của Chúng Tôi')) ?>
                    </span>
                    <h2 class="section-title">
                        <?= e(content_value('home.story_title', 'Khát khao mang không gian xanh vào cuộc sống hiện đại')) ?>
                    </h2>
                    <p><?= e(content_value('home.story_p1', 'Plantify Co ra đời từ tình yêu với thiên nhiên. Chúng tôi tin rằng, một mầm xanh không chỉ làm đẹp căn phòng mà còn là liệu pháp tinh thần vô giá sau những giờ làm việc căng thẳng.')) ?>
                    </p>
                    <p><?= e(content_value('home.story_p2', 'Với quy trình tuyển chọn khắt khe từ các nhà vườn uy tín, chúng tôi cam kết mỗi sản phẩm gửi đi đều đạt chất lượng cao nhất. Chúng tôi không chỉ bán cây, mà còn trao đi nguồn năng lượng chữa lành từ tự nhiên.')) ?>
                    </p>
                    <div class="about-check-grid mt-4">
                        <span><i class="fa-solid fa-check"></i> Cây trồng hữu cơ</span>
                        <span><i class="fa-solid fa-check"></i> Chậu gốm thủ công</span>
                        <span><i class="fa-solid fa-check"></i> Tư vấn miễn phí</span>
                        <span><i class="fa-solid fa-check"></i> Bao bì thân thiện</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    
    <!-- About Process -->
    <section class="section-padding">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5" data-aos="fade-right">
                    <span class="section-kicker"><?php echo e(content_value('about.process_kicker', 'Quy trình')); ?></span>
                    <h2 class="section-title"><?php echo e(content_value('about.process_title', 'Rõ từng bước để khách hàng dễ theo dõi')); ?></h2>
                    <p class="text-muted"><?php echo e(content_value('about.process_text', 'Từ ảnh không gian ban đầu đến chăm sóc định kỳ, mỗi giai đoạn đều có đầu ra cụ thể để bạn duyệt nhanh và kiểm soát ngân sách.')); ?></p>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="timeline-list">
                        <?php foreach ($steps as $step): ?>
                            <article>
                                <span><?php echo e($step['number']); ?></span>
                                <div>
                                    <h3><?php echo e(content_value($step['title'], $step['default_title'])); ?></h3>
                                    <p><?php echo e(content_value($step['text'], $step['default_text'])); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Map Section -->
    <section class="section-padding map-section" id="plantifyMap">
        <div class="container">
            <div class="map-layout-row">
                <div class="map-copy-panel" data-aos="fade-right">
                    <div>
                        <span class="section-kicker"><?php echo e(content_value('about.map_kicker', 'Vị trí')); ?></span>
                        <h2 class="section-title"><?php echo e(content_value('about.map_title', 'Ghé Plantify để chọn cây và chậu trực tiếp')); ?></h2>
                        <p class="text-muted"><?php echo e(content_value('company.address', '')); ?></p>
                        <p class="text-muted"><a href="https://maps.app.goo.gl/8ynWGgQHBHb7Ez1E8" target="_blank" rel="noopener noreferrer">Xem bản đồ</a></p>
                    </div>
                    <div class="map-contact-list">
                        <span><i class="fa-solid fa-phone"></i><?php echo e(content_value('company.phone', '')); ?></span>
                        <span><i class="fa-solid fa-clock"></i><?php echo e(content_value('company.hours', '')); ?></span>
                    </div>
                </div>
                <div class="map-embed-wrap" data-aos="fade-left" style="width:100%; max-width:100%; min-height:720px;">
                    <iframe
                        title="<?php echo e(content_value('about.map_iframe_title', 'Bản đồ Plantify Co')); ?>"
                        src="https://www.google.com/maps?q=<?php echo rawurlencode(content_value('company.address', '')); ?>&output=embed"
                        width="100%"
                        height="720"
                        style="width:100%; height:720px; min-height:72vh; display:block; border:0;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>


    <!-- ===== CTA SECTION ===== -->
    <section class="cta-section">
        <div class="container position-relative">
            <div class="row align-items-center g-4 text-center text-lg-start">
                <div class="col-lg-8">
                    <h2><?= e(content_value('home.cta_title', 'Sẵn sàng mang thiên nhiên vào nhà?')) ?></h2>
                    <p class="mb-0">
                        <?= e(content_value('home.cta_text', 'Đừng ngần ngại liên hệ nếu bạn cần chuyên gia của Plantify tư vấn loại cây phù hợp với không gian và mệnh của mình.')) ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/shop"
                        class="btn btn-light btn-lg text-success fw-bold px-4 rounded-pill cta-button">
                        <?= e(content_value('home.cta_button', 'Bắt Đầu Mua Sắm')) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Modal Đang cập nhật -->
<div class="modal fade" id="comingSoonModal" tabindex="-1" aria-labelledby="comingSoonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-info-circle fs-1 text-primary mb-3 d-block"></i>
                <p class="mb-0 fs-5">Đang cập nhập thông tin</p>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/pages/product-detail.php -->
<main class="site-main page-main" style="margin-top: 50px;">
    <div class="container py-4">
        <!-- Thông báo (Success/Error) -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-success text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop" class="text-success text-decoration-none">Cửa hàng</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $product['name'] ?></li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Cột Trái: Ảnh Sản Phẩm -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="position-relative">
                    <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>" alt="<?= $product['name'] ?>" class="rounded-image w-100" style="min-height: 500px; object-fit: cover;">
                </div>
            </div>

            <!-- Cột Phải: Thông tin & Giỏ hàng -->
            <div class="col-lg-6" data-aos="fade-left">
                <span class="section-kicker"><?= $product['category'] ?></span>
                <h1 class="mb-3" style="color: var(--green-900); font-weight: 850; font-size: 2.5rem;"><?= $product['name'] ?></h1>
                <h2 class="mb-4" style="color: var(--green-700); font-weight: 800; font-size: 2rem;">
                    <?= number_format($product['price'], 0, ',', '.') ?> VNĐ
                </h2>

                <p class="text-muted" style="font-size: 1.05rem; line-height: 1.8;">
                    <?= $product['description'] ?>
                </p>



                <hr class="my-4 text-muted">

                <!-- Form Thêm vào giỏ hàng -->
                <?php if ($user): ?>
                    <form action="<?= BASE_URL ?>/shop/addToCart" method="POST" class="d-flex align-items-center gap-3">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="d-flex align-items-center border rounded p-1" style="border-color: var(--stone-200);">
                            <label for="qty" class="me-2 ms-2 text-muted fw-bold">SL:</label>
                            <input type="number" id="qty" name="quantity" value="1" min="1" class="form-control border-0 text-center" style="width: 70px; box-shadow: none;">
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-outline-success btn-lg px-4 rounded-pill">
                            <i class="fa-solid fa-cart-plus me-2"></i> <?= e(content_value('product.btn_add_to_cart', 'Thêm vào giỏ')) ?>
                        </button>
                        <button type="submit" name="buy_now" class="btn btn-success btn-lg px-4 rounded-pill">
                            <?= e(content_value('product.btn_buy_now', 'Mua ngay')) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 rounded" style="background: var(--mint-50); border: 1px dashed var(--green-300);">
                        <p class="mb-3 text-center text-muted"><i class="fa-solid fa-lock text-success mb-2 fs-3 d-block"></i> Bạn cần đăng nhập để mua hàng.</p>
                        <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success w-100">Đăng Nhập Ngay</a>
                    </div>
                <?php endif; ?>

                <!-- Cam kết -->
                <div class="product-trust-badges mt-4 pt-4 border-top">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-truck-fast text-success me-1"></i> <?= e(content_value('product.trust_badge_1', 'Giao hàng nhanh')) ?></div>
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i> <?= e(content_value('product.trust_badge_2', 'Thanh toán an toàn')) ?></div>
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-arrows-rotate text-success me-1"></i> <?= e(content_value('product.trust_badge_3', '1 đổi 1 trong 3 ngày')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SẢN PHẨM LIÊN QUAN -->
    <section class="section-padding bg-soft mt-5">
        <div class="container">
            <div class="section-heading text-center mb-5">
                <h2><?= e(content_value('product.related_title', 'Có thể bạn cũng thích')) ?></h2>
            </div>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $item): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="product-card h-100 bg-white">
                            <a href="<?= BASE_URL ?>/shop/detail/<?= $item['id'] ?>">
                                <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>" alt="<?= $item['name'] ?>" class="w-100 object-fit-cover" style="height: 220px;"></a>
                            <div class="product-body">
                                <span><?= $item['category'] ?></span>
                                <h3 class="mt-1 mb-2 fs-5"><a href="<?= BASE_URL ?>/shop/detail/<?= $item['id'] ?>" class="text-dark"><?= $item['name'] ?></a></h3>
                                <strong><?= number_format($item['price'], 0, ',', '.') ?>đ</strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- location: app/Views/pages/shop.php -->
<?php
// Lấy các tham số từ URL để duy trì trạng thái lọc
$currentCategory = $_GET['category'] ?? 'all';
$currentSort = $_GET['sort'] ?? 'newest';
$searchKeyword = $_GET['search'] ?? '';

// Hàm tạo URL để không làm mất các tham số khác khi nhấn vào lọc/phân trang
function buildUrl($overrides = [])
{
    $params = array_merge($_GET, $overrides);
    return "?" . http_build_query($params);
}
?>

<main class="site-main" style="padding-top: 0;">

    <section class="page-hero" style="padding: 120px 0 60px 0; background: linear-gradient(135deg, rgba(18, 56, 42, 0.9), rgba(45, 138, 95, 0.8)), url('<?= BASE_URL ?>/file/render?path=uploads/images/shop-hero-img.jpg') center/cover;">
        <div class="container text-center" data-aos="fade-up">
            <h1 class="display-4 fw-bold text-white"><?= e(content_value('shop.hero_title', 'Cửa Hàng Xanh')) ?></h1>
            <p class="mx-auto text-white opacity-75" style="max-width: 600px;">
                <?= e(content_value('shop.hero_description', 'Khám phá bộ sưu tập cây xanh...')) ?>
            </p>

            <!-- <div class="mt-4 mx-auto" style="max-width: 500px;">
                <form action="" method="GET" class="input-group shadow-lg rounded-pill overflow-hidden">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">

                    <input type="text" name="search" class="form-control border-0 ps-4 py-3"
                        placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây bạn yêu thích...')) ?>"
                        value="<?= htmlspecialchars($searchKeyword) ?>">
                    <button class="btn btn-success px-4" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div> -->
        </div>
    </section>

    <section id="product-list" class="section-padding bg-soft">
        <div class="container">

            <div class="row mb-4 align-items-center">
                <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/shop?category=all" class="btn <?= $currentCategory === 'all' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Tất cả</a>
                        <a href="<?= BASE_URL ?>/shop?category=Để bàn" class="btn <?= $currentCategory === 'Để bàn' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Để bàn</a>
                        <a href="<?= BASE_URL ?>/shop?category=Sàn nhà" class="btn <?= $currentCategory === 'Sàn nhà' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Sàn nhà</a>
                        <a href="<?= BASE_URL ?>/shop?category=Phụ kiện" class="btn <?= $currentCategory === 'Phụ kiện' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Phụ kiện</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-flex border border-success rounded-pill overflow-hidden bg-white">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($currentSort !== 'newest'): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
                        <?php endif; ?>

                        <input type="text" name="search" class="form-control border-0 ps-3 py-2 shadow-none text-sm"
                            placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây...')) ?>"
                            value="<?= htmlspecialchars($searchKeyword) ?>">
                        <button type="submit" class="btn btn-success px-3"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="col-lg-3 col-md-6 text-md-end">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-inline-block w-100">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($searchKeyword !== ''): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchKeyword) ?>">
                        <?php endif; ?>

                        <span class="text-muted d-none d-xxl-inline me-2"><?= e(content_value('shop.sort_label', 'Sắp xếp:')) ?></span>
                        <select name="sort" class="form-select d-inline-block w-auto border-success rounded-pill" onchange="this.form.submit()">
                            <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $currentSort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= $currentSort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        </select>
                    </form>
                </div>
            </div>

            <?php if ($searchKeyword): ?>
                <div class="mb-4" data-aos="fade-in">
                    <h5 class="text-muted">
                        Kết quả tìm kiếm cho: <span class="text-success">"<?= htmlspecialchars($searchKeyword) ?>"</span>
                        <a href="?" class="ms-2 btn btn-sm btn-light rounded-pill">Xóa tìm kiếm</a>
                    </h5>
                </div>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
                            <div class="product-card h-100 bg-white border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="position-relative">
                                    <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>">
                                        <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>"
                                            alt="<?= $product['name'] ?>"
                                            class="w-100 object-fit-cover"
                                            style="height: 240px;">
                                    </a>
                                </div>

                                <div class="p-3 text-center">
                                    <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;"><?= $product['category'] ?></span>
                                    <h3 class="fs-6 mt-1 mb-2">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="text-decoration-none fw-bold" style="color: var(--green-900);">
                                            <?= $product['name'] ?>
                                        </a>
                                    </h3>

                                    <div class="fw-bold mb-3" style="color: var(--green-700); font-size: 1.1rem;">
                                        <?= isset($product['price']) ? number_format($product['price'], 0, ',', '.') . 'đ' : 'Liên hệ' ?>
                                    </div>

                                    <div class="d-grid">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                            <i class="fa-solid fa-eye me-1"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-5" data-aos="fade-up">
                        <nav aria-label="Điều hướng phân trang">
                            <ul class="pagination pagination-modern mb-0">
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage - 1]) ?>" aria-label="Trang trước">
                                        <i class="fa-solid fa-chevron-left fa-sm"></i>
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage + 1]) ?>" aria-label="Trang sau">
                                        <i class="fa-solid fa-chevron-right fa-sm"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <img src="<?= BASE_URL ?>/file/render?path=uploads/images/shop-search.png" width="100" alt="Not found" class="opacity-50 mb-3">
                    <h3 class="text-muted"><?= e(content_value('shop.empty_title', 'Không tìm thấy cây nào phù hợp')) ?></h3>
                    <p><?= e(content_value('shop.empty_text', 'Vui lòng thử từ khóa khác hoặc xóa bộ lọc.')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .pagination-rounded .page-link {
        border-radius: 50% !important;
        margin: 0 3px;
        border: none;
        color: #198754;
    }

    .pagination-rounded .page-item.active .page-link {
        background-color: #198754;
        color: white;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.location.search) {
            const productList = document.getElementById('product-list');
            if (productList) {
                const headerOffset = 0;
                const elementPosition = productList.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                // setTimeout nhẹ để đảm bảo DOM đã render xong hoàn toàn
                setTimeout(() => {
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: "smooth"
                    });
                }, 100);
            }
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: footer.php
 * Chuc nang: Tao phan cuoi trang dung chung cho website.
 * Location: app/Views/partials/footer.php
 */

// Đặt giá trị mặc định cho toàn bộ thông tin footer
$c_name    = content_value('company.name', 'Plantify Co');
$c_tagline = content_value('company.tagline', 'Mang thiên nhiên vào không gian của bạn');
$c_phone   = content_value('company.phone', '0908 246 135');
$c_email   = content_value('company.email', 'info@plantify.com');
$c_hours   = content_value('company.hours', '8:00 - 17:00');
$c_address = content_value('company.address', '268, Lý Thường Kiệt, Phường 14, Quận 10, TP. Hồ Chí Minh');
?>
</main>
<footer class="site-footer pt-5 pb-4">
    <div class="container">
        <div class="row g-3 g-lg-4">

            <!-- Logo & Mô tả công ty -->
            <div class="col-12 col-lg-3 text-center text-lg-start mb-4 mb-lg-0">
                <!-- Logo -->
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-3 text-decoration-none"
                    href="<?= BASE_URL ?>">
                    <span class="brand-mark bg-white text-success"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text text-white"><?php echo e($companyName); ?></span> 
                </a>
                <!-- Mô tả công ty -->
                <p class="footer-text opacity-75 mb-3" style="font-size: 0.95rem;">
                    <?php echo e(content_value('footer.description', 'Chúng tôi mang cây xanh vào không gian sống và làm việc bằng giải pháp tinh gọn, bền vững.')); ?>
                </p>
                <!-- Liên kết mạng xã hội -->
                <div class="social-links d-flex gap-3 justify-content-center justify-content-lg-start">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Điều hướng -->
            <div class="col-6 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">
                    <?php echo e(content_value('footer.nav_title', 'Điều hướng')); ?></h5>
                <ul class="footer-links list-unstyled d-flex flex-column gap-2 mb-0" style="font-size: 0.9rem;">
                    <li><a href="<?= BASE_URL ?>/shop">Cửa hàng</a></li>
                    <li><a href="<?= BASE_URL ?>/news">Tin tức</a></li>
                    <li><a href="<?= BASE_URL ?>/faq"><?php echo e(content_value('nav.faq', 'FAQ')); ?></a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="col-6 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">
                    <?php echo e(content_value('footer.info_title', 'Thông tin')); ?></h5>
                <ul class="footer-list list-unstyled d-flex flex-column gap-2 mb-0" style="font-size: 0.9rem;">
                    <li><i class="fa-solid fa-location-dot"></i>
                        <?php echo e(content_value('company.address', '123 Đường Cây Xanh, TP HCM')); ?></li>
                    <li><i class="fa-solid fa-phone"></i>
                        <?php echo e(content_value('company.phone', '0908 246 135')); ?></li>
                    <li><i class="fa-solid fa-envelope"></i>
                        <?php echo e(content_value('company.email', 'info@plantify.com')); ?></li>
                </ul>
            </div>

            <!-- Giờ mở cửa -->
            <div class="col-12 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">Giờ mở cửa</h5>
                <p class="small text-white opacity-75 mb-0"><i
                        class="fa-solid fa-clock me-2"></i><?php echo e(content_value('company.hours', 'Thứ 2 - Thứ 7: 08:00 - 18:00')); ?>
                </p>
            </div>

        </div>
    </div>
</footer>


<!-- // Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
<script src="<?php echo asset('assets/js/main.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        once: true,
        offset: 50
    });
});
</script>
</body>

</html>
<?php

/**
 * File: header.php
 * Chuc nang: Tao phan dau trang dung chung cho website.
 * Location: app/Views/partials/header.php
 */


$companyName = $company['name'] ?? 'Plantify Co';

$pageTitle = $pageTitle ?? $companyName;
$pageDescription = $pageDescription ?? content_value('site.default_description', 'Website công ty cây cảnh, cây xanh và decor thiên nhiên cho văn phòng, showroom.');
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}
$fullname = isset($user['fullname']) ? $user['fullname'] : 'Khách';
$avatar = !empty($user['avatar'])
    ? BASE_URL . '/file/render?path=' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullname);
?>
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/leaf-solid-full.svg">
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="keywords" content="cây cảnh, cây xanh, decor cây xanh, cây nội thất, thiết kế cảnh quan">
    <meta name="author" content="<?php echo e($companyName); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
    <?php foreach ($extraCss as $cssFile): ?>
    <?php $cssPath = strpos($cssFile, 'http') === 0 ? $cssFile : BASE_URL . '/' . ltrim($cssFile, '/'); ?>
    <link href="<?= $cssPath ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <!-- Header -->
    <header class="site-header">
        <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-white shadow-sm">
            <div class="container">

                <!-- Brand -->
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>"
                    aria-label="<?php echo e($companyName); ?>">
                    <span class="brand-mark"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text"><?php echo e($companyName); ?></span>
                </a>

                <!-- Navbar Toggler -->
                <div class="d-flex align-items-center gap-3 ms-auto order-lg-3">
                    <!-- Cart -->
                    <a href="<?= BASE_URL ?>/cart"
                        class="btn btn-light position-relative rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; color: var(--green-900); overflow: visible !important;">

                        <i class="fa-solid fa-cart-shopping"></i>

                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem; z-index: 1000; min-width: 18px; height: 18px; padding: 4px; line-height: 10px;">
                            <?= $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Account -->
                    <?php if (!empty($user)): ?>
                    <div class="dropdown">
                        <a href="#" class="text-decoration-none text-dark fw-bold d-flex align-items-center gap-2"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($user['avatar'])): ?>
                            <!-- Hiển thị Avatar từ DB -->
                            <img src="<?= $avatar ?>" class="rounded-circle object-fit-cover shadow-sm"
                                style="width: 32px; height: 32px; border: 1px solid #ddd;">
                            <?php else: ?>
                            <!-- Hiển thị Icon nếu chưa có Avatar -->
                            <span class="brand-mark bg-light text-success"
                                style="width: 32px; height: 32px; font-size: 0.9rem;">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <?php endif; ?>
                            <span
                                class="d-none d-md-inline"><?= htmlspecialchars($user['fullname'] ?? 'Tài khoản') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2"
                            style="border-radius: 12px;">
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/dashboard"><i
                                        class="fa-solid fa-chart-simple text-success me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/dashboard/orders"><i
                                        class="fa-solid fa-box text-success me-2"></i> Đơn hàng của tôi</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/auth/logout"><i
                                        class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>

                    <!-- Auth Buttons -->
                    <?php else: ?>
                    <div class="d-none d-md-flex gap-2">
                        <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Nhập</a>
                        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Ký</a>
                    </div>

                    <!-- Auth Button (Mobile) -->
                    <a href="<?= BASE_URL ?>/auth" class="text-dark d-md-none text-decoration-none">
                        <i class="fa-solid fa-circle-user fs-4 text-success"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Navbar Toggler -->
                <button class="navbar-toggler ms-2 order-lg-4" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Main Navigation -->
                <div class="collapse navbar-collapse order-lg-2" id="mainNavbar">
                    <ul class="navbar-nav mx-auto align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page(''); ?>" href="<?= BASE_URL ?>">Trang Chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('shop'); ?>" href="<?= BASE_URL ?>/shop">Cửa
                                Hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('news'); ?>" href="<?= BASE_URL ?>/news">Tin
                                Tức</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('faq'); ?>"
                                href="<?= BASE_URL ?>/faq"><?php echo e(content_value('nav.faq', 'FAQ')); ?></a>
                        </li>
                    </ul>



                </div>

            </div>
        </nav>
    </header>
    <main class="site-main">



