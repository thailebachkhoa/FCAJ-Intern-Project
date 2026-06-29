<?php

/**
 * File: footer.php
 * Chuc nang: Tao phan cuoi trang dung chung cho website.
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

            <div class="col-12 col-lg-3 text-center text-lg-start mb-4 mb-lg-0">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-3 text-decoration-none"
                    href="<?= BASE_URL ?>">
                    <span class="brand-mark bg-white text-success"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text text-white"><?php echo e($companyName); ?></span>
                </a>
                <p class="footer-text opacity-75 mb-3" style="font-size: 0.95rem;">
                    <?php echo e(content_value('footer.description', 'Chúng tôi mang cây xanh vào không gian sống và làm việc bằng giải pháp tinh gọn, bền vững.')); ?>
                </p>
                <div class="social-links d-flex gap-3 justify-content-center justify-content-lg-start">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>


            <div class="col-6 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">
                    <?php echo e(content_value('footer.nav_title', 'Điều hướng')); ?></h5>
                <ul class="footer-links list-unstyled d-flex flex-column gap-2 mb-0" style="font-size: 0.9rem;">
                    <li><a href="<?= BASE_URL ?>/shop">Cửa hàng</a></li>
                    <li><a href="<?= BASE_URL ?>/news">Tin tức</a></li>
                    <li><a href="<?= BASE_URL ?>/about"><?php echo e(content_value('nav.about', 'Giới thiệu')); ?></a>
                    </li>
                    <li><a href="<?= BASE_URL ?>/faq"><?php echo e(content_value('nav.faq', 'FAQ')); ?></a></li>
                    <li><a href="<?= BASE_URL ?>/contact">Liên hệ</a></li>
                </ul>
            </div>


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
            <div class="col-12 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">Giờ mở cửa</h5>
                <p class="small text-white opacity-75 mb-0"><i
                        class="fa-solid fa-clock me-2"></i><?php echo e(content_value('company.hours', 'Thứ 2 - Thứ 7: 08:00 - 18:00')); ?>
                </p>
            </div>

        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom border-secondary my-4">
            <div class="row align-items-center">
                <p class="col-md-6 text-center text-md-start mb-2 mb-md-0">© <?= date('Y') ?>
                    <?php echo e($companyName); ?>. Đã đăng ký bản quyền.</p>
                <div class="col-md-6 text-right text-md-end">
                    <div class="d-flex gap-3 justify-content-right justify-content-md-end small">
                        <a href="#" class="text-white text-decoration-none">Chính sách bảo mật</a>
                        <a href="#" class="text-white text-decoration-none">Điều khoản sử dụng</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
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