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