<?php

/**
 * AdminController
 * Handles admin dashboard
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

    public function rag()
    {
        $this->view('admin/rag', [
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

     /** GET /admin/contacts — danh sách liên hệ */
    public function contacts()
    {
        $db      = Database::getInstance();
        $search  = trim($_GET['search'] ?? '');
        $statusF = $_GET['status'] ?? '';
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $where = '1=1';
        if ($search) $where .= ' AND (name LIKE :s1 OR email LIKE :s2 OR message LIKE :s3)';
        if ($statusF === 'unread') $where .= ' AND is_read = 0';
        if ($statusF === 'read')   $where .= ' AND is_read = 1';

        $db->query("SELECT COUNT(*) as total FROM contacts WHERE $where");
        if ($search) {
            $db->bind(':s1', '%' . $search . '%');
            $db->bind(':s2', '%' . $search . '%');
            $db->bind(':s3', '%' . $search . '%');
        }
        $total = (int)($db->single()['total'] ?? 0);

        $db->query("SELECT * FROM contacts WHERE $where ORDER BY is_read ASC, created_at DESC LIMIT :lim OFFSET :off");
        if ($search) {
            $db->bind(':s1', '%' . $search . '%');
            $db->bind(':s2', '%' . $search . '%');
            $db->bind(':s3', '%' . $search . '%');
        }
        $db->bind(':lim', $perPage);
        $db->bind(':off', $offset);
        $contacts = $db->resultSet();

        $success = $_SESSION['admin_success'] ?? null;
        unset($_SESSION['admin_success']);

        $this->view('admin/contacts', [
            'user'         => Auth::user(),
            'contacts'     => $contacts,
            'search'       => $search,
            'statusFilter' => $statusF,
            'currentPage'  => $page,
            'totalPages'   => $total > 0 ? (int)ceil($total / $perPage) : 1,
            'total'        => $total,
            'success'      => $success,
        ]);
    }

    /** GET /admin/contact_read/{id} — đánh dấu đã đọc */
    public function contact_read($id = null)
    {
        $db = Database::getInstance();
        $db->query("UPDATE contacts SET is_read = 1 WHERE id = :id");
        $db->bind(':id', (int)$id);
        $db->execute();
        $_SESSION['admin_success'] = 'Đã đánh dấu đã đọc!';
        $this->redirect('admin/contacts');
    }

    /** GET /admin/contact_delete/{id} — xóa liên hệ */
    public function contact_delete($id = null)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM contacts WHERE id = :id");
        $db->bind(':id', (int)$id);
        $db->execute();
        $_SESSION['admin_success'] = 'Đã xóa liên hệ!';
        $this->redirect('admin/contacts');
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

            $this->redirect('admin/orders/detail/' . $id);
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
            ['faq.hero_card_text',         'Trang FAQ', 'Nội dung thẻ hero',                   'textarea', 'Mở trợ lý AI ở góc màn hình hoặc gửi ảnh không gian để được tư vấn theo điều kiện thực tế.'],
            ['faq.sidebar_kicker',         'Trang FAQ', 'Nhãn sidebar',                        'text',     'Điểm cần biết'],
            ['faq.sidebar_title',          'Trang FAQ', 'Tiêu đề sidebar',                     'text',     'Chuẩn bị trước khi tư vấn'],
            ['faq.sidebar_description',    'Trang FAQ', 'Mô tả sidebar',                       'textarea', 'Thông tin càng rõ, phương án cây xanh càng sát nhu cầu và ngân sách.'],
            ['faq.sidebar_item_1',         'Trang FAQ', 'Gợi ý chuẩn bị 1',                   'text',     'Ảnh tổng thể và góc cần đặt cây'],
            ['faq.sidebar_item_2',         'Trang FAQ', 'Gợi ý chuẩn bị 2',                   'text',     'Thời lượng ánh sáng trong ngày'],
            ['faq.sidebar_item_3',         'Trang FAQ', 'Gợi ý chuẩn bị 3',                   'text',     'Kích thước khu vực dự kiến'],
            ['faq.sidebar_item_4',         'Trang FAQ', 'Gợi ý chuẩn bị 4',                   'text',     'Ngân sách hoặc mức ưu tiên'],
            ['faq.sidebar_cta',            'Trang FAQ', 'Nút CTA sidebar',                     'text',     'Về Plantify'],
            ['faq.chip_1',                 'Trang FAQ', 'Câu hỏi chip 1',                      'text',     'Plantify có khảo sát trực tiếp trước khi thiết kế không?'],
            ['faq.chip_1_label',           'Trang FAQ', 'Nhãn chip 1',                         'text',     'Có khảo sát không?'],
            ['faq.chip_2',                 'Trang FAQ', 'Câu hỏi chip 2',                      'text',     'Tôi có thể gửi ảnh mặt bằng để được tư vấn online không?'],
            ['faq.chip_2_label',           'Trang FAQ', 'Nhãn chip 2',                         'text',     'Gửi ảnh tư vấn?'],
            ['faq.chip_3',                 'Trang FAQ', 'Câu hỏi chip 3',                      'text',     'Cây được bảo hành sau bàn giao như thế nào?'],
            ['faq.chip_3_label',           'Trang FAQ', 'Nhãn chip 3',                         'text',     'Bảo hành cây?'],
            ['faq.steps_kicker',           'Trang FAQ', 'Nhãn section các bước',               'text',     'Sau khi có câu trả lời'],
            ['faq.steps_title',            'Trang FAQ', 'Tiêu đề section các bước',            'text',     'Quy trình tiếp theo rất gọn'],
            ['faq.step_1_title',           'Trang FAQ', 'Tiêu đề bước 1',                      'text',     'Gửi ảnh và nhu cầu'],
            ['faq.step_1_text',            'Trang FAQ', 'Nội dung bước 1',                     'textarea', 'Đính kèm ảnh hiện trạng, phong cách mong muốn và ngân sách dự kiến.'],
            ['faq.step_2_title',           'Trang FAQ', 'Tiêu đề bước 2',                      'text',     'Nhận tư vấn sơ bộ'],
            ['faq.step_2_text',            'Trang FAQ', 'Nội dung bước 2',                     'textarea', 'Plantify đề xuất nhóm cây, kích thước chậu và mức chăm sóc phù hợp.'],
            ['faq.step_3_title',           'Trang FAQ', 'Tiêu đề bước 3',                      'text',     'Chốt lịch khảo sát'],
            ['faq.step_3_text',            'Trang FAQ', 'Nội dung bước 3',                     'textarea', 'Đội ngũ kiểm tra thực tế trước khi báo giá và triển khai chính thức.'],
            ['faq.chatbot_title',          'Trang FAQ', 'Tiêu đề chatbot widget',              'text',     'Trợ lý AI Plantify'],
            ['faq.chatbot_subtitle',       'Trang FAQ', 'Mô tả chatbot widget',                'text',     'Hỏi về cây xanh, dịch vụ và FAQ'],
            ['faq.chatbot_greeting',       'Trang FAQ', 'Lời chào mở đầu chatbot',             'textarea', 'Xin chào! Tôi có thể giúp gì cho bạn về dịch vụ cây xanh hôm nay?'],
            ['faq.chatbot_placeholder',    'Trang FAQ', 'Placeholder ô nhập chatbot',          'text',     'Nhập câu hỏi...'],
        ];
 
        $sections = [
            ['title' => 'SEO',                            'desc' => 'Meta title và description.',                                              'keys' => ['faq.meta_title','faq.meta_description']],
            ['title' => 'Hero đầu trang',                 'desc' => 'Tiêu đề, mô tả, ô tìm kiếm và thẻ thông tin bên phải.',               'keys' => ['faq.hero_kicker','faq.hero_title','faq.hero_description','faq.hero_search_placeholder','faq.hero_card_title','faq.hero_card_text']],
            ['title' => 'Sidebar chuẩn bị tư vấn',       'desc' => 'Tiêu đề và danh sách gợi ý chuẩn bị trước khi liên hệ.',              'keys' => ['faq.sidebar_kicker','faq.sidebar_title','faq.sidebar_description','faq.sidebar_item_1','faq.sidebar_item_2','faq.sidebar_item_3','faq.sidebar_item_4','faq.sidebar_cta']],
            ['title' => 'Câu hỏi nhanh (chip buttons)',   'desc' => 'Nội dung câu hỏi và nhãn hiển thị của 3 chip.',                       'keys' => ['faq.chip_1_label','faq.chip_1','faq.chip_2_label','faq.chip_2','faq.chip_3_label','faq.chip_3']],
            ['title' => 'Quy trình 3 bước',               'desc' => 'Section phía dưới accordion FAQ.',                                    'keys' => ['faq.steps_kicker','faq.steps_title','faq.step_1_title','faq.step_1_text','faq.step_2_title','faq.step_2_text','faq.step_3_title','faq.step_3_text']],
            ['title' => 'Chatbot widget',                  'desc' => 'Tiêu đề, lời chào và placeholder của widget trợ lý AI.',              'keys' => ['faq.chatbot_title','faq.chatbot_subtitle','faq.chatbot_greeting','faq.chatbot_placeholder']],
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
 
    /**
     * GET/POST /admin/page_contact — nội dung trang liên hệ
     */
    public function page_contact()
    {
        $defaults = [
            ['contact.meta_title',           'Trang liên hệ', 'Meta title',                          'text',     'Liên hệ | Plantify Co'],
            ['contact.meta_description',     'Trang liên hệ', 'Meta description',                    'textarea', 'Liên hệ Plantify Co để được tư vấn về cây xanh nội thất, thiết kế decor và dịch vụ chăm sóc định kỳ.'],
            ['contact.hero_kicker',          'Trang liên hệ', 'Nhãn hero',                           'text',     'Kết nối với Plantify'],
            ['contact.hero_title',           'Trang liên hệ', 'Tiêu đề hero',                        'text',     'Luôn sẵn sàng hỗ trợ bạn'],
            ['contact.hero_description',     'Trang liên hệ', 'Mô tả hero',                          'textarea', 'Dù bạn cần tư vấn chọn cây cho văn phòng, hỏi đáp về cách chăm sóc, hay phản hồi dịch vụ, chúng tôi luôn ở đây để lắng nghe.'],
            ['contact.hero_card_title',      'Trang liên hệ', 'Tiêu đề thẻ hero',                   'text',     'Phản hồi nhanh'],
            ['contact.hero_card_text',       'Trang liên hệ', 'Nội dung thẻ hero',                   'textarea', 'Đội ngũ CSKH cam kết trả lời các yêu cầu trực tuyến trong vòng 24 giờ làm việc.'],
            ['contact.form_title',           'Trang liên hệ', 'Tiêu đề form liên hệ',               'text',     'Gửi tin nhắn cho chúng tôi'],
            ['contact.form_subtitle',        'Trang liên hệ', 'Mô tả dưới tiêu đề form',            'textarea', 'Để lại thông tin bên dưới, chuyên viên của Plantify sẽ liên hệ lại với bạn ngay.'],
            ['contact.label_name',           'Trang liên hệ', 'Nhãn trường Họ và tên',              'text',     'Họ và tên'],
            ['contact.placeholder_name',     'Trang liên hệ', 'Gợi ý trường Họ và tên',             'text',     'Ví dụ: Nguyễn Văn A'],
            ['contact.label_email',          'Trang liên hệ', 'Nhãn trường Email',                  'text',     'Email'],
            ['contact.placeholder_email',    'Trang liên hệ', 'Gợi ý trường Email',                 'text',     'example@email.com'],
            ['contact.label_subject',        'Trang liên hệ', 'Nhãn trường Chủ đề',                 'text',     'Chủ đề'],
            ['contact.subject_default',      'Trang liên hệ', 'Tùy chọn mặc định Chủ đề',          'text',     '-- Chọn chủ đề cần tư vấn --'],
            ['contact.subject_1',            'Trang liên hệ', 'Chủ đề 1',                           'text',     'Mua sắm cây xanh'],
            ['contact.subject_2',            'Trang liên hệ', 'Chủ đề 2',                           'text',     'Dịch vụ decor/setup văn phòng'],
            ['contact.subject_3',            'Trang liên hệ', 'Chủ đề 3',                           'text',     'Hỏi đáp cách chăm sóc cây'],
            ['contact.subject_4',            'Trang liên hệ', 'Chủ đề 4',                           'text',     'Khác'],
            ['contact.label_message',        'Trang liên hệ', 'Nhãn trường Nội dung',               'text',     'Nội dung'],
            ['contact.placeholder_message',  'Trang liên hệ', 'Gợi ý trường Nội dung',              'textarea', 'Nhập tin nhắn của bạn...'],
            ['contact.btn_submit',           'Trang liên hệ', 'Nhãn nút gửi form',                  'text',     'Gửi liên hệ'],
            ['contact.success_message',      'Trang liên hệ', 'Thông báo gửi thành công',           'textarea', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong vòng 24 giờ.'],
            ['contact.sidebar_title',        'Trang liên hệ', 'Tiêu đề sidebar thông tin',          'text',     'Bạn cần hỗ trợ gấp?'],
            ['contact.sidebar_description',  'Trang liên hệ', 'Mô tả sidebar',                      'textarea', 'Đừng ngại gọi cho hotline hoặc ghé trực tiếp showroom của chúng tôi để được giải đáp tức thời.'],
        ];
 
        $sections = [
            ['title' => 'SEO',               'desc' => 'Tên tab trình duyệt và mô tả tìm kiếm.',                                                     'keys' => ['contact.meta_title','contact.meta_description']],
            ['title' => 'Hero đầu trang',    'desc' => 'Tiêu đề, mô tả và thẻ thông tin bên phải.',                                                  'keys' => ['contact.hero_kicker','contact.hero_title','contact.hero_description','contact.hero_card_title','contact.hero_card_text']],
            ['title' => 'Form liên hệ',      'desc' => 'Tiêu đề form, nhãn các trường và nút gửi.',                                                  'keys' => ['contact.form_title','contact.form_subtitle','contact.label_name','contact.placeholder_name','contact.label_email','contact.placeholder_email','contact.label_subject','contact.subject_default','contact.subject_1','contact.subject_2','contact.subject_3','contact.subject_4','contact.label_message','contact.placeholder_message','contact.btn_submit']],
            ['title' => 'Thông báo',         'desc' => 'Thông báo hiển thị sau khi gửi form thành công.',                                            'keys' => ['contact.success_message']],
            ['title' => 'Sidebar thông tin', 'desc' => 'Tiêu đề và mô tả cột bên phải (SĐT, địa chỉ lấy từ nhóm Công ty).',                        'keys' => ['contact.sidebar_title','contact.sidebar_description']],
        ];
 
        $result = $this->_pageEditorHandle($defaults, 'Trang liên hệ');
 
        $this->view('admin/page_contact', [
            'user'      => Auth::user(),
            'pageTitle' => 'Nội dung Trang liên hệ',
            'message'   => $result['message'],
            'error'     => $result['error'],
            'byKey'     => $result['byKey'],
            'sections'  => $sections,
        ]);
    }
}