<?php

/**
 * File: header.php
 * Chuc nang: Tao phan dau trang dung chung cho website.
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
    <header class="site-header">
        <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>"
                    aria-label="<?php echo e($companyName); ?>">
                    <span class="brand-mark"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text"><?php echo e($companyName); ?></span>
                </a>
                <div class="d-flex align-items-center gap-3 ms-auto order-lg-3">
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
                    <?php else: ?>
                    <div class="d-none d-md-flex gap-2">
                        <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Nhập</a>
                        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Ký</a>
                    </div>
                    <a href="<?= BASE_URL ?>/auth" class="text-dark d-md-none text-decoration-none">
                        <i class="fa-solid fa-circle-user fs-4 text-success"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <button class="navbar-toggler ms-2 order-lg-4" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

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
                            <a class="nav-link <?php echo is_active_page('about'); ?>" href="<?= BASE_URL ?>/about">Về
                                Chúng Tôi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('contact'); ?>"
                                href="<?= BASE_URL ?>/contact">Liên Hệ</a>
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