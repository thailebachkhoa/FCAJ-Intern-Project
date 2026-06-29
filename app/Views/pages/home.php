<?php

/**
 * File: app/Views/pages/home.php
 * View: Trang chủ Plantify Co
 * Tất cả văn bản đọc từ site_content qua content_value()
 */
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
                        <a href="<?= BASE_URL ?>/about" class="btn btn-outline-light px-4">
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

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>