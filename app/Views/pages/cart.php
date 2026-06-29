<?php

/**
 * File: app/Views/pages/cart.php
 * Chức năng: Giao diện giỏ hàng đồng bộ UI với style.css
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