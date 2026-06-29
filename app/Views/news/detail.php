<?php

/**
 * File: app/Views/news/detail.php
 * Giao diện chi tiết bài viết - Đã đồng bộ Bootstrap 5 & style.css
 */
require BASE_PATH . '/app/Views/partials/header.php';
?>

<div class="news-detail-wrapper py-5 bg-soft">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4" data-aos="fade-up">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/news" class="text-success text-decoration-none">Tin tức</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($news['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">
            <!-- CỘT NỘI DUNG CHÍNH -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-duration="800">
                <main class="news-detail-main bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light">

                    <header class="news-detail-header mb-4">
                        <h1 class="display-6 fw-bold mb-3" style="color: var(--green-900); line-height: 1.3;"><?= e($news['title']) ?></h1>
                        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                            <span><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y H:i', strtotime($news['created_at'])) ?></span>
                            <span><i class="fa-solid fa-user-pen me-1"></i> <?= e($news['author'] ?? 'Admin') ?></span>
                            <span><i class="fa-regular fa-comment-dots me-1"></i> <?= $commentCount ?? 0 ?> bình luận</span>
                        </div>
                    </header>

                    <div class="news-detail-thumb rounded-4 overflow-hidden mb-5">
                        <?php
                        $thumbPath = !empty($news['thumbnail']) ? PUBLIC_PATH . '/' . ltrim($news['thumbnail'], '/') : '';
                        if (!empty($news['thumbnail']) && file_exists($thumbPath)): ?>
                            <img src="<?= BASE_URL ?>/<?= ltrim($news['thumbnail'], '/') ?>" alt="<?= e($news['title']) ?>" class="w-100 img-fluid object-fit-cover" style="max-height: 450px;">
                        <?php else: ?>
                            <div class="news-img-placeholder w-100 d-flex align-items-center justify-content-center" style="height: 300px; background: var(--green-100); color: var(--green-600); font-size: 5rem;">
                                <i class="fa-solid fa-leaf"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="news-detail-content lh-lg" style="font-size: 1.1rem; color: var(--stone-900);">
                        <?= $news['content'] ?>
                    </div>

                    <?php if (!empty($news['tags'])): ?>
                        <div class="news-detail-tags mt-5 pt-4 border-top d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-stone-700 me-2"><i class="fa-solid fa-tags me-1"></i> Tags:</span>
                            <?php foreach (array_filter(array_map('trim', explode(',', $news['tags']))) as $tag): ?>
                                <a href="<?= BASE_URL ?>/news?search=<?= urlencode($tag) ?>"
                                    class="badge bg-light text-success border text-decoration-none px-3 py-2 rounded-pill transition hover-bg-success">
                                    <?= e($tag) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </main>

                <div class="comments-section mt-5 bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light" id="comments" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="fw-bold mb-4" style="color: var(--green-900);">
                        💬 Bình luận <span class="text-muted fs-5">(<?= $commentCount ?? 0 ?>)</span>
                    </h3>

                    <?php if (!empty($commentError)): ?>
                        <div class="alert alert-danger rounded-3 border-0 bg-danger text-white"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= e($commentError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($commentSuccess)): ?>
                        <div class="alert alert-success rounded-3 border-0 bg-success text-white"><i class="fa-solid fa-circle-check me-2"></i> <?= e($commentSuccess) ?></div>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <div class="comment-form-box p-4 bg-light rounded-4 border mb-5">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen me-2"></i>Viết bình luận của bạn</h5>
                            <form action="<?= BASE_URL ?>/news/comment_post" method="POST" id="commentForm" novalidate>
                                <input type="hidden" name="news_id" value="<?= (int)$news['id'] ?>">
                                <input type="hidden" name="slug" value="<?= e($news['slug']) ?>">

                                <div class="mb-3">
                                    <textarea name="content" id="commentContent"
                                        class="form-control border-0 shadow-sm p-3 rounded-3" rows="4"
                                        placeholder="Chia sẻ ý kiến của bạn về bài viết này..." maxlength="1000" required></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div id="commentErrorBox" class="text-danger small fw-medium" style="display:none;"></div>
                                        <div class="char-counter text-muted small ms-auto" id="charCounter">0 / 1000 ký tự</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-medium">
                                    <i class="fa-regular fa-paper-plane me-2"></i>Gửi bình luận
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="comment-login-prompt p-4 bg-light rounded-4 border text-center mb-5">
                            <i class="fa-solid fa-lock text-muted mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-3 text-stone-700">Bạn cần đăng nhập để tham gia bình luận cùng cộng đồng.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="<?= BASE_URL ?>/auth" class="btn btn-success rounded-pill px-4">Đăng Nhập</a>
                                <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-success rounded-pill px-4">Đăng Ký</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="comments-list">
                        <?php if (empty($comments)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-regular fa-comments fs-2 mb-2"></i>
                                <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                                <div class="comment-item d-flex gap-3 mb-4">
                                    <div class="comment-avatar flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                            <?= mb_strtoupper(mb_substr($c['fullname'] ?? $c['username'] ?? 'U', 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="comment-body flex-grow-1 bg-light p-3 rounded-4 border border-white shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold text-stone-900"><?= e($c['fullname'] ?: $c['username']) ?></h6>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-0 text-stone-700" style="font-size: 0.95rem;"><?= nl2br(e($c['content'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left" data-aos-delay="300">
                <aside class="news-detail-sidebar sticky-top" style="top: 100px;">

                    <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">✍️ Về tác giả</h5>
                        <div class="d-flex align-items-center gap-3">
                            <div class="author-avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-feather-pointed"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 fs-5"><?= e($news['author'] ?? 'Admin') ?></h6>
                                <span class="badge bg-light text-success border">Biên tập viên Plantify Co</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($related)): ?>
                        <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">📚 Bài viết liên quan</h5>
                            <div class="related-list d-flex flex-column gap-3">
                                <?php foreach ($related as $r): ?>
                                    <a href="<?= BASE_URL ?>/news/detail/<?= e($r['slug']) ?>" class="related-item d-flex gap-3 text-decoration-none group">
                                        <div class="related-thumb flex-shrink-0 rounded-3 overflow-hidden" style="width: 80px; height: 60px;">
                                            <?php
                                            $rThumb = !empty($r['thumbnail']) ? PUBLIC_PATH . '/' . ltrim($r['thumbnail'], '/') : '';
                                            if (!empty($r['thumbnail']) && file_exists($rThumb)): ?>
                                                <img src="<?= BASE_URL ?>/<?= ltrim($r['thumbnail'], '/') ?>" alt="" class="w-100 h-100 object-fit-cover">
                                            <?php else: ?>
                                                <div class="w-100 h-100 bg-green-100 d-flex align-items-center justify-content-center text-success fs-4">🌿</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-info flex-grow-1">
                                            <h6 class="text-stone-900 fw-bold mb-1" style="font-size: 0.9rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= e($r['title']) ?>
                                            </h6>
                                            <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($news['tags'])): ?>
                        <div class="sidebar-widget bg-white p-4 rounded-4 shadow-sm border border-light mb-4">
                            <h5 class="fw-bold mb-4 border-bottom pb-2">🏷️ Khám phá chủ đề</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach (array_filter(array_map('trim', explode(',', $news['tags']))) as $tag): ?>
                                    <a href="<?= BASE_URL ?>/news?search=<?= urlencode($tag) ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                        <?= e($tag) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/news" class="btn btn-light border text-stone-700 rounded-pill px-4 py-2 w-100 fw-medium shadow-sm hover-bg-light">
                            <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const content = document.getElementById('commentContent');
        const counter = document.getElementById('charCounter');
        const errBox = document.getElementById('commentErrorBox');
        const form = document.getElementById('commentForm');

        if (content) {
            content.addEventListener('input', function() {
                const len = this.value.length;
                counter.textContent = len + ' / 1000 ký tự';
                if (len > 1000) {
                    counter.classList.add('text-danger');
                } else {
                    counter.classList.remove('text-danger');
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                errBox.style.display = 'none';
                const val = content.value.trim();

                if (!val) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Vui lòng nhập nội dung bình luận!';
                    errBox.style.display = 'block';
                    content.focus();
                    return;
                }
                if (val.length < 5) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Bình luận phải có ít nhất 5 ký tự!';
                    errBox.style.display = 'block';
                    content.focus();
                    return;
                }
                if (val.length > 1000) {
                    e.preventDefault();
                    errBox.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> Bình luận không được vượt quá 1000 ký tự!';
                    errBox.style.display = 'block';
                    return;
                }
            });
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>