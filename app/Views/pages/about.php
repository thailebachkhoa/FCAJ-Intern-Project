<?php
?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>


<!-- Call to Action -->
<section class="cta-section about-cta">
    <div class="container position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2><?php echo e(content_value('about.cta_title', 'Muốn biết không gian của bạn hợp cây gì?')); ?></h2>
                <p><?php echo e(content_value('about.cta_text', 'Gửi ảnh hiện trạng, Plantify sẽ gợi ý nhóm cây, kích thước chậu và cách chăm sóc phù hợp.')); ?></p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?php echo e(asset('faq')); ?>" class="btn btn-light"><?php echo e(content_value('about.cta_button', 'Xem FAQ')); ?></a>
            </div>
        </div>
    </div>
</section>
<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>