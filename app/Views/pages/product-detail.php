<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

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