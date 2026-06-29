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
                                ['pages',          'ti-info-alt',         'Trang giới thiệu','pages'],
                                ['shop_settings',  'ti-shopping-cart-full','Trang cửa hàng', 'shop-settings'],
                                ['page_news',      'ti-agenda',           'Trang tin tức',   'page_news'],
                                ['page_faq',       'ti-help-alt',         'Trang FAQ',       'page_faq'],
                                ['page_contact',   'ti-email',            'Trang liên hệ',   'page_contact'],
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

                    <?php admin_sidebar_item('contacts',    'ti-email',            'Liên hệ từ khách hàng', 'contacts'); ?>
                    <?php admin_sidebar_item('news',        'ti-agenda',           'Quản lý Tin tức',        'admin/news'); ?>
                    <?php admin_sidebar_item('comments',    'ti-comments-smiley',  'Bình luận',              'comments'); ?>
                    <?php admin_sidebar_item('faqs',        'ti-help-alt',         'FAQ',                    'admin/faqs'); ?>
                    <?php admin_sidebar_item('users',       'ti-user',             'Thành viên',             'users'); ?>
                    <?php admin_sidebar_item('rag',         'ti-comments',         'Dữ liệu bot',            'rag'); ?>
                    <?php admin_sidebar_item('products',    'ti-package',          'Quản lý Sản phẩm',       'products'); ?>
                    <?php admin_sidebar_item('orders',      'ti-shopping-cart',    'Quản lý Đơn hàng',       'orders'); ?>

                    <li>
                        <a href="<?= BASE_URL ?>">
                            <i class="ti-home"></i><span>Xem website</span>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
</div>
<?php
    }
}