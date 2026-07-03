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
    }
}
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
                                <td class="text-center">
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <a href="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>"
                                            class="btn btn-warning btn-sm text-white" title="Ẩn bình luận"
                                            onclick="return confirm('Ẩn bình luận này?')">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>"
                                            class="btn btn-success btn-sm" title="Duyệt bình luận"
                                            onclick="return confirm('Duyệt và hiển thị bình luận này?')">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/comment_delete/<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                                        title="Xóa"
                                        onclick="return confirm('Xóa bình luận này? Hành động không thể hoàn tác!')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
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
                                    <a href="<?= BASE_URL ?>/admin/news_delete/<?= $n['id'] ?>"
                                        class="btn btn-danger btn-sm" title="Xóa"
                                        onclick="return confirm('Xóa bài viết \'<?= addslashes(htmlspecialchars($n['title'])) ?>\'?\nHành động này không thể hoàn tác!')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
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
                <form action="<?= BASE_URL ?>/admin/order_update_status/<?= $order['id'] ?>" method="POST">                    <label class="form-label small fw-bold">Cập nhật trạng thái</label>
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
                                        <a href="<?= BASE_URL ?>/admin/product_delete/<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm mx-1" onclick="return confirm('Xóa sản phẩm này?')">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
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
                        <option value="user">Thành viên (User)</option>
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
                                    <td>
                                        <?php if ($u['role'] != 'admin'): ?>
                                            <a href="<?= BASE_URL ?>/admin/reset_password/<?= $u['id'] ?>" class="btn btn-warning btn-sm mx-1 text-white" onclick="return confirm('Bạn có chắc muốn cấp lại mật khẩu mặc định (123456) cho tài khoản này không?')">
                                                <i class="fa-solid fa-key"></i> Reset
                                            </a>

                                            <?php if ($u['status'] == 'active'): ?>
                                                <a href="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Bạn có muốn khoá quyền truy cập của người này?')">
                                                    <i class="fa-solid fa-lock"></i> Khoá
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" class="btn btn-success btn-sm mx-1">
                                                    <i class="fa-solid fa-unlock"></i> Mở
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>/admin/delete_user/<?= $u['id'] ?>" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Xóa người dùng này? Hành động này không thể hoàn tác!')">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fa-solid fa-shield"></i> Không thể can thiệp</span>
                                        <?php endif; ?>
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

<?php
// Hàm này tự động đóng div nội dung và tự động nạp luôn bootstrap, scripts.js
admin_layout_end();
?>