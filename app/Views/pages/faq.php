<?php

/**
 * File: app/Views/pages/faq.php
 * Tất cả văn bản tĩnh đọc từ site_content qua content_value()
 * Location: app/Views/pages/faq.php
 */

$pageTitle       = content_value('faq.meta_title',       'FAQ | Câu hỏi thường gặp về cây cảnh và decor xanh');
$pageDescription = content_value('faq.meta_description', 'Giải đáp câu hỏi về khảo sát, bảo hành, chăm sóc định kỳ, tư vấn online và dịch vụ cây xanh doanh nghiệp.');

require_once BASE_PATH . '/app/Views/partials/header.php';
?>

<!-- ===== HERO ===== -->
<section class="page-hero faq-hero modern-hero">
    <div class="container">
        <div class="row gy-5 gx-3 gx-lg-5 align-items-end">

            <div class="col-lg-8" data-aos="fade-up">
                <span class="section-kicker">
                    <?= e(content_value('faq.hero_kicker', 'FAQ & tư vấn nhanh')) ?>
                </span>
                <h1><?= e(content_value('faq.hero_title', 'Câu hỏi thường gặp về cây xanh, decor và chăm sóc định kỳ')) ?>
                </h1>
                <p><?= e(content_value('faq.hero_description', 'Tra cứu nhanh các thông tin quan trọng trước khi khảo sát, chọn cây, nhận báo giá hoặc sử dụng gói chăm sóc sau bàn giao.')) ?>
                </p>
                <div class="faq-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="faqSearchInput" type="search"
                        placeholder="<?= e(content_value('faq.hero_search_placeholder', 'Tìm nhanh: bảo hành, khảo sát, gửi ảnh, chăm sóc...')) ?>">
                </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left">
                <div class="hero-insight-card">
                    <i class="fa-solid fa-headset"></i>
                    <strong><?= e(content_value('faq.hero_card_title', 'Cần câu trả lời riêng?')) ?></strong>
                    <span>"Nhấn biểu tượng zalo để liên hệ đội ngũ tư vấn"</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== NỘI DUNG CHÍNH ===== -->
<section class="section-padding faq-modern-section">
    <div class="container">
        <div class="row gy-5 gx-3 gx-lg-5">

            <!-- SIDEBAR -->
            <div class="col-lg-4" data-aos="fade-right">
                <aside class="faq-side faq-dashboard">
                    <span class="section-kicker">
                        <?= e(content_value('faq.sidebar_kicker', 'Điểm cần biết')) ?>
                    </span>
                    <h2><?= e(content_value('faq.sidebar_title', 'Chuẩn bị trước khi tư vấn')) ?></h2>
                    <p><?= e(content_value('faq.sidebar_description', 'Thông tin càng rõ, phương án cây xanh càng sát nhu cầu và ngân sách.')) ?>
                    </p>

                    <div class="faq-prep-list">
                        <div>
                            <i class="fa-solid fa-camera"></i>
                            <span><?= e(content_value('faq.sidebar_item_1', 'Ảnh tổng thể và góc cần đặt cây')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-sun"></i>
                            <span><?= e(content_value('faq.sidebar_item_2', 'Thời lượng ánh sáng trong ngày')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-ruler-combined"></i>
                            <span><?= e(content_value('faq.sidebar_item_3', 'Kích thước khu vực dự kiến')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-wallet"></i>
                            <span><?= e(content_value('faq.sidebar_item_4', 'Ngân sách hoặc mức ưu tiên')) ?></span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success info-cta" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                        <?= e(content_value('faq.sidebar_cta', 'Tìm hiểu thêm')) ?>
                    </button>
                </aside>

            </div>

            <!-- ACCORDION FAQ -->
            <div class="col-lg-8" data-aos="fade-left">
                <div class="faq-tabs" aria-label="Lọc câu hỏi FAQ">
                    <button type="button" class="faq-filter active" data-filter="all">Tất cả</button>
                    <button type="button" class="faq-filter" data-filter="survey">Khảo sát</button>
                    <button type="button" class="faq-filter" data-filter="care">Chăm sóc</button>
                    <button type="button" class="faq-filter" data-filter="warranty">Bảo hành</button>
                    <button type="button" class="faq-filter" data-filter="online">Online</button>
                </div>

                <div class="accordion custom-accordion faq-accordion-modern" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <?php
                        $question = $faq['question'] ?? '';
                        $answer   = $faq['answer']   ?? '';
                        $haystack = $question . ' ' . $answer;
                        $category = 'all';
                        if (preg_match('/khảo sát|khao sat/iu', $haystack))          $category = 'survey';
                        elseif (preg_match('/bảo hành|bao hanh|thay thế/iu', $haystack)) $category = 'warranty';
                        elseif (preg_match('/online|ảnh|anh/iu', $haystack))          $category = 'online';
                        elseif (preg_match('/chăm sóc|cham soc/iu', $haystack))       $category = 'care';
                        ?>
                    <div class="accordion-item faq-item" data-category="<?= e($category) ?>"
                        data-search="<?= e($haystack) ?>">

                        <h2 class="accordion-header" id="heading<?= $index ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                aria-controls="collapse<?= $index ?>">
                                <span class="faq-number">
                                    <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                                <?= e($faq['question']) ?>
                            </button>
                        </h2>

                        <div id="collapse<?= $index ?>"
                            class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                            aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= e($faq['answer']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="faqEmptyState" class="faq-empty-state" hidden>
                    <i class="fa-regular fa-circle-question"></i>
                    <strong>Chưa tìm thấy câu hỏi phù hợp</strong>
                    <span>Thử từ khóa khác hoặc hỏi trực tiếp đội ngũ chăm sóc ở góc màn hình.</span>
                </div>
            </div>

        </div>
    </div>

        <!-- Modal: Đang cập nhật -->
    <div class="modal fade" id="comingSoonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background:#ffffff;">
                <div class="modal-body text-center p-5">
                    <div class="mb-3 text-success" style="font-size: 3rem;">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Trang đang được cập nhật</h5>
                    <p class="text-muted mb-4">Nội dung này sẽ sớm ra mắt. Cảm ơn bạn đã ghé thăm Plantify!</p>
                    <button type="button" class="btn btn-success rounded-pill px-4" data-bs-dismiss="modal">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ===== 3 BƯỚC SAU FAQ ===== -->
<section class="section-padding bg-soft">
    <div class="container">
        <div class="section-heading text-center" data-aos="fade-up">
            <span class="section-kicker">
                <?= e(content_value('faq.steps_kicker', 'Sau khi có câu trả lời')) ?>
            </span>
            <h2><?= e(content_value('faq.steps_title', 'Quy trình tiếp theo rất gọn')) ?></h2>
        </div>
        <div class="row g-4">

            <div class="col-md-4" data-aos="fade-up">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_1_icon', 'fa-paperclip')) ?>"></i>
                    <h3><?= e(content_value('faq.step_1_title', 'Gửi ảnh và nhu cầu')) ?></h3>
                    <p><?= e(content_value('faq.step_1_text', 'Đính kèm ảnh hiện trạng, phong cách mong muốn và ngân sách dự kiến.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_2_icon', 'fa-comments')) ?>"></i>
                    <h3><?= e(content_value('faq.step_2_title', 'Nhận tư vấn sơ bộ')) ?></h3>
                    <p><?= e(content_value('faq.step_2_text', 'Plantify đề xuất nhóm cây, kích thước chậu và mức chăm sóc phù hợp.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_3_icon', 'fa-calendar-days')) ?>"></i>
                    <h3><?= e(content_value('faq.step_3_title', 'Chốt lịch khảo sát')) ?></h3>
                    <p><?= e(content_value('faq.step_3_text', 'Đội ngũ kiểm tra thực tế trước khi báo giá và triển khai chính thức.')) ?>
                    </p>
                </article>
            </div>

        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>
<?php require_once BASE_PATH . '/app/Views/partials/footer.php'; ?>