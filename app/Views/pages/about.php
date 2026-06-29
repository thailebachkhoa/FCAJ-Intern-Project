<?php

/**
 * File: about.php
 * View: Trang giới thiệu Plantify Co
 */
$aboutImage = empty($page['image']) || preg_match('/^https?:\/\//', $page['image'])
    ? 'assets/images/aboutimg_1.jpg'
    : $page['image'];

$metrics = [
    ['value' => 'about.metric_1_value', 'label' => 'about.metric_1_label', 'default_value' => '120+', 'default_label' => 'không gian đã tư vấn'],
    ['value' => 'about.metric_2_value', 'label' => 'about.metric_2_label', 'default_value' => '30 ngày', 'default_label' => 'theo dõi sau bàn giao'],
    ['value' => 'about.metric_3_value', 'label' => 'about.metric_3_label', 'default_value' => '24h', 'default_label' => 'phản hồi hồ sơ online'],
    ['value' => 'about.metric_4_value', 'label' => 'about.metric_4_label', 'default_value' => '4 bước', 'default_label' => 'quy trình triển khai rõ ràng'],
];

$checks = [
    content_value('about.check_1', 'Tư vấn theo ngân sách'),
    content_value('about.check_2', 'Bố trí theo mặt bằng'),
    content_value('about.check_3', 'Chọn cây theo điều kiện sáng'),
    content_value('about.check_4', 'Theo dõi sức khỏe cây'),
];

$features = [
    ['icon' => 'fa-compass-drafting', 'title' => 'about.feature_1_title', 'text' => 'about.feature_1_text', 'default_title' => 'Thiết kế đúng không gian', 'default_text' => 'Mỗi loại cây được chọn theo ánh sáng, diện tích, luồng di chuyển và chất liệu nội thất.'],
    ['icon' => 'fa-shield-heart', 'title' => 'about.feature_2_title', 'text' => 'about.feature_2_text', 'default_title' => 'Cây khỏe, nguồn rõ', 'default_text' => 'Cây được kiểm tra rễ, lá, sâu bệnh và khả năng thích nghi trước khi bàn giao.'],
    ['icon' => 'fa-calendar-check', 'title' => 'about.feature_3_title', 'text' => 'about.feature_3_text', 'default_title' => 'Bảo dưỡng đều đặn', 'default_text' => 'Lịch chăm sóc định kỳ giúp không gian xanh luôn sạch, an toàn và giữ hình ảnh chuyên nghiệp.'],
];

$steps = [
    ['number' => '01', 'title' => 'about.process_1_title', 'text' => 'about.process_1_text', 'default_title' => 'Tiếp nhận nhu cầu', 'default_text' => 'Nhận ảnh, mặt bằng, phong cách mong muốn và mức ngân sách dự kiến.'],
    ['number' => '02', 'title' => 'about.process_2_title', 'text' => 'about.process_2_text', 'default_title' => 'Khảo sát điều kiện', 'default_text' => 'Đánh giá ánh sáng, gió, ổ cắm, lối đi, vị trí tưới và rủi ro bẩn sàn.'],
    ['number' => '03', 'title' => 'about.process_3_title', 'text' => 'about.process_3_text', 'default_title' => 'Đề xuất phương án', 'default_text' => 'Gợi ý cây, chậu, bố cục, tần suất chăm sóc và phương án thay thế khi cần.'],
    ['number' => '04', 'title' => 'about.process_4_title', 'text' => 'about.process_4_text', 'default_title' => 'Bàn giao và duy trì', 'default_text' => 'Lắp đặt gọn, hướng dẫn chăm sóc, theo dõi cây sau bàn giao và bảo dưỡng định kỳ.'],
];

$aboutTestimonials = [
    ['quote' => 'about.testimonial_1_quote', 'name' => 'about.testimonial_1_name', 'role' => 'about.testimonial_1_role', 'default_quote' => 'Plantify thiết kế mảng xanh gọn gàng, đúng tinh thần văn phòng của chúng tôi và chăm sóc cây rất đều.', 'default_name' => 'Ms. Linh Nguyễn', 'default_role' => 'Office Manager, Aster Tech'],
    ['quote' => 'about.testimonial_2_quote', 'name' => 'about.testimonial_2_name', 'role' => 'about.testimonial_2_role', 'default_quote' => 'Đội ngũ tư vấn kỹ về ánh sáng và chất liệu chậu. Không gian studio sau khi decor trông ấm hơn nhưng vẫn rất tinh tế.', 'default_name' => 'Mr. Minh Trần', 'default_role' => 'Founder, Annam Studio'],
];
?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

<section class="page-hero about-hero modern-hero">
    <video
        class="hero-bg-video"
        data-hls-src="<?php echo e(asset($heroVideo)); ?>"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
        aria-label="<?php echo e(content_value('about.hero_video_label', 'Video nền giới thiệu Plantify Co')); ?>"></video>
    <div class="container">
        <div class="row g-5 align-items-end">
            <div class="col-lg-8" data-aos="fade-up">
                <span class="section-kicker"><?php echo e(content_value('about.hero_kicker', 'Về Plantify')); ?></span>
                <h1><?php echo e(content_value('about.hero_title', 'Thiết kế mảng xanh bền vững cho không gian sống và làm việc hiện đại.')); ?></h1>
                <p><?php echo e(content_value('about.hero_description', 'Plantify kết hợp tư duy thiết kế, hiểu biết cây trồng và quy trình chăm sóc định kỳ để tạo nên những không gian xanh đẹp, khỏe và dễ duy trì.')); ?></p>
                <div class="hero-actions">
                    <a href="<?php echo e(asset('faq')); ?>" class="btn btn-success px-4"><?php echo e(content_value('about.hero_primary_button', 'Xem FAQ')); ?></a>
                    <a href="<?php echo e(asset('faq')); ?>" class="btn btn-outline-light px-4"><?php echo e(content_value('about.hero_secondary_button', 'Xem câu hỏi thường gặp')); ?></a>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-left">
                <div class="hero-insight-card">
                    <i class="fa-solid fa-sprout"></i>
                    <strong><?php echo e(content_value('about.hero_card_title', 'Không chỉ đặt cây vào phòng')); ?></strong>
                    <span><?php echo e(content_value('about.hero_card_text', 'Chúng tôi tính ánh sáng, luồng di chuyển, độ ẩm, chất liệu chậu và chi phí bảo dưỡng trước khi đề xuất phương án.')); ?></span>
                </div>
            </div>
        </div>
        <div class="hero-metrics" data-aos="fade-up" data-aos-delay="120">
            <?php foreach ($metrics as $metric): ?>
                <div>
                    <strong><?php echo e(content_value($metric['value'], $metric['default_value'])); ?></strong>
                    <span><?php echo e(content_value($metric['label'], $metric['default_label'])); ?></span>
                </div>
                <?php endforeach; ?>img
        </div>
    </div>
</section>

<section class="section-padding about-story-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-image-stack">
                    <img src="<?php echo e(media_url($aboutImage)); ?>" alt="<?php echo e(content_value('about.image_alt', 'Chăm sóc cây xanh trong không gian nội thất')); ?>" class="img-fluid rounded-image">
                    <div class="image-note">
                        <strong><?php echo e(content_value('about.image_note_title', 'Khảo sát trước khi chọn cây')); ?></strong>
                        <span><?php echo e(content_value('about.image_note_text', 'Ánh sáng, hướng gió và thói quen sử dụng quyết định 70% độ bền của mảng xanh.')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="section-kicker"><?php echo e(content_value('about.story_kicker', 'Câu chuyện')); ?></span>
                <h2 class="section-title"><?php echo e(content_value('about.story_title', 'Từ những chậu cây nhỏ đến giải pháp xanh cho doanh nghiệp')); ?></h2>
                <p><?php echo e(content_value('about.story_paragraph_1', 'Chúng tôi phục vụ văn phòng, căn hộ dịch vụ, showroom, nhà hàng và không gian bán lẻ cần một hình ảnh xanh chỉn chu. Mỗi dự án bắt đầu bằng khảo sát thực tế, sau đó đội ngũ thiết kế chọn cây theo ánh sáng, độ ẩm, mật độ sử dụng và phong cách nội thất.')); ?></p>
                <p><?php echo e(content_value('about.story_paragraph_2', 'Plantify không chạy theo bố cục rườm rà. Chúng tôi tập trung vào cây khỏe, chậu đẹp, tỷ lệ hài hòa và quy trình chăm sóc sau bàn giao.')); ?></p>
                <div class="about-check-grid">
                    <?php foreach ($checks as $check): ?>
                        <span><i class="fa-solid fa-check"></i> <?php echo e($check); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-soft">
    <div class="container">
        <div class="section-heading text-center" data-aos="fade-up">
            <span class="section-kicker"><?php echo e(content_value('about.capability_kicker', 'Năng lực cốt lõi')); ?></span>
            <h2><?php echo e(content_value('about.capability_title', 'Thiết kế đẹp nhưng vẫn dễ vận hành mỗi ngày')); ?></h2>
            <p><?php echo e(content_value('about.capability_text', 'Plantify xây dựng phương án theo cả thẩm mỹ lẫn chi phí duy trì, phù hợp cho không gian có nhiều người sử dụng.')); ?></p>
        </div>
        <div class="row g-4">
            <?php foreach ($features as $index => $feature): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo e((string) ($index * 80)); ?>">
                    <div class="value-card feature-card h-100">
                        <i class="fa-solid <?php echo e($feature['icon']); ?>"></i>
                        <h3><?php echo e(content_value($feature['title'], $feature['default_title'])); ?></h3>
                        <p><?php echo e(content_value($feature['text'], $feature['default_text'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5" data-aos="fade-right">
                <span class="section-kicker"><?php echo e(content_value('about.process_kicker', 'Quy trình')); ?></span>
                <h2 class="section-title"><?php echo e(content_value('about.process_title', 'Rõ từng bước để khách hàng dễ theo dõi')); ?></h2>
                <p class="text-muted"><?php echo e(content_value('about.process_text', 'Từ ảnh không gian ban đầu đến chăm sóc định kỳ, mỗi giai đoạn đều có đầu ra cụ thể để bạn duyệt nhanh và kiểm soát ngân sách.')); ?></p>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="timeline-list">
                    <?php foreach ($steps as $step): ?>
                        <article>
                            <span><?php echo e($step['number']); ?></span>
                            <div>
                                <h3><?php echo e(content_value($step['title'], $step['default_title'])); ?></h3>
                                <p><?php echo e(content_value($step['text'], $step['default_text'])); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding testimonial-section">
    <div class="container">
        <div class="section-heading text-center" data-aos="fade-up">
            <span class="section-kicker"><?php echo e(content_value('about.testimonial_kicker', 'Khách hàng nói gì')); ?></span>
            <h2><?php echo e(content_value('about.testimonial_title', 'Phản hồi từ các dự án đã triển khai')); ?></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($aboutTestimonials as $testimonial): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <article class="testimonial-card h-100">
                        <i class="fa-solid fa-quote-left"></i>
                        <p><?php echo e(content_value($testimonial['quote'], $testimonial['default_quote'])); ?></p>
                        <strong><?php echo e(content_value($testimonial['name'], $testimonial['default_name'])); ?></strong>
                        <span><?php echo e(content_value($testimonial['role'], $testimonial['default_role'])); ?></span>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding map-section" id="plantifyMap">
    <div class="container">
        <div class="map-layout-row">
            <div class="map-copy-panel" data-aos="fade-right">
                <div>
                    <span class="section-kicker"><?php echo e(content_value('about.map_kicker', 'Vị trí')); ?></span>
                    <h2 class="section-title"><?php echo e(content_value('about.map_title', 'Ghé Plantify để chọn cây và chậu trực tiếp')); ?></h2>
                    <p class="text-muted"><?php echo e(content_value('company.address', '')); ?></p>
                </div>
                <div class="map-contact-list">
                    <span><i class="fa-solid fa-phone"></i><?php echo e(content_value('company.phone', '')); ?></span>
                    <span><i class="fa-solid fa-clock"></i><?php echo e(content_value('company.hours', '')); ?></span>
                </div>
            </div>
            <div class="map-embed-wrap" data-aos="fade-left" style="width:100%; max-width:100%; min-height:720px;">
                <iframe
                    title="<?php echo e(content_value('about.map_iframe_title', 'Bản đồ Plantify Co')); ?>"
                    src="https://www.google.com/maps?q=<?php echo rawurlencode(content_value('company.address', '')); ?>&output=embed"
                    width="100%"
                    height="720"
                    style="width:100%; height:720px; min-height:72vh; display:block; border:0;"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>
            </div>
        </div>
    </div>
</section>

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