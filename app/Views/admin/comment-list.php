<?php
$pageTitle  = 'Quản lý Bình luận';
$breadcrumb = 'Bình luận';
$activePage = 'comments';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle
]);
?>

<!-- ===== FLASH MESSAGE ===== -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== SEARCH BAR ===== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/admin/comments" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1 small fw-semibold">Tìm kiếm bình luận</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Nội dung, tên người dùng, tiêu đề bài viết..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm
                </button>
                <a href="<?= BASE_URL ?>/admin/comments" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ===== COMMENT TABLE ===== -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="mb-0">
                Tổng: <strong class="text-success"><?= $total ?></strong> bình luận
                <?php if ($search): ?>
                    <span class="text-muted small"> — kết quả cho "<?= htmlspecialchars($search) ?>"</span>
                <?php endif; ?>
            </h6>
            <small class="text-muted">
                <span class="badge bg-success me-1">Đã duyệt</span> hiển thị ngoài website &nbsp;|&nbsp;
                <span class="badge bg-warning text-dark me-1">Chờ duyệt</span>/<span
                    class="badge bg-secondary ms-1 me-1">Đã ẩn</span> không hiển thị
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="160">Người dùng</th>
                        <th>Nội dung</th>
                        <th width="200">Bài viết</th>
                        <th width="100">Trạng thái</th>
                        <th width="110">Ngày gửi</th>
                        <th width="140" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commentsList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-comments fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có bình luận nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commentsList as $i => $c): ?>
                            <tr>
                                <td class="text-muted small"><?= ($currentPage - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div
                                            style="width:36px;height:36px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#065f46;flex-shrink:0;">
                                            <?= mb_strtoupper(mb_substr($c['fullname'] ?: $c['username'] ?: 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:13px;">
                                                <?= htmlspecialchars($c['fullname'] ?: $c['username']) ?></div>
                                            <div class="text-muted" style="font-size:11px;">
                                                @<?= htmlspecialchars($c['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width:280px;font-size:13px;line-height:1.5;">
                                        <?= nl2br(htmlspecialchars(mb_substr($c['content'], 0, 150))) ?>
                                        <?= mb_strlen($c['content']) > 150 ? '<span class="text-muted">...</span>' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($c['news_title'])): ?>
                                        <a href="<?= BASE_URL ?>/news/detail/<?= htmlspecialchars($c['news_slug'] ?? '') ?>"
                                            target="_blank" class="text-success small fw-semibold"
                                            style="text-decoration:none;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?= htmlspecialchars($c['news_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Đã duyệt</span>
                                    <?php elseif ($c['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                <td class="text-center">
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <a href="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>"
                                            class="btn btn-warning btn-sm text-white" title="Ẩn bình luận"
                                            onclick="return confirm('Ẩn bình luận này?')">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/admin/comment_toggle/<?= $c['id'] ?>"
                                            class="btn btn-success btn-sm" title="Duyệt bình luận"
                                            onclick="return confirm('Duyệt và hiển thị bình luận này?')">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/comment_delete/<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                                        title="Xóa"
                                        onclick="return confirm('Xóa bình luận này? Hành động không thể hoàn tác!')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== PAGINATION ===== -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Phân trang">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/comments?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>">‹
                    Trước</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link"
                        href="<?= BASE_URL ?>/admin/comments?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/comments?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>">Sau
                    ›</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
admin_layout_end();
?>