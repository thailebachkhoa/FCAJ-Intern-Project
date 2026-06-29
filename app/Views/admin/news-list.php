<?php
$pageTitle  = 'Quản lý Tin tức';
$breadcrumb = 'Tin tức';
$activePage = 'news';
$pageAction = '<a href="' . BASE_URL . '/admin/news_create" class="btn btn-success btn-sm">
    <i class="fa-solid fa-plus"></i> Thêm bài viết mới
</a>';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'actionHtml' => $pageAction
]);
?>

<!-- ===== FLASH MESSAGES ===== -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FILTER BAR ===== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/admin/news" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1 small fw-semibold">Tìm kiếm</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Tiêu đề hoặc tag..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-semibold">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Đã đăng</option>
                    <option value="draft" <?= $statusFilter === 'draft'     ? 'selected' : '' ?>>Bản nháp</option>
                    <option value="hidden" <?= $statusFilter === 'hidden'    ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm
                </button>
                <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ===== NEWS TABLE ===== -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="mb-0">
                Tổng: <strong class="text-success"><?= $total ?></strong> bài viết
                <?php if ($search): ?><span class="text-muted small"> — kết quả cho "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="80">Ảnh</th>
                        <th>Tiêu đề</th>
                        <th width="100">Tác giả</th>
                        <th width="110">Trạng thái</th>
                        <th width="110">Ngày tạo</th>
                        <th width="160" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($newsList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-newspaper fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có bài viết nào.
                                <a href="<?= BASE_URL ?>/admin/news_create">Tạo ngay</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($newsList as $i => $n): ?>
                            <tr>
                                <td class="text-muted small"><?= ($currentPage - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($n['thumbnail']) && file_exists(__DIR__ . '/../../../../public/' . $n['thumbnail'])): ?>
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($n['thumbnail']) ?>"
                                            style="width:64px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:64px;height:48px;background:#d1fae5;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:22px;">🌿</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="max-width:320px;">
                                        <?= htmlspecialchars($n['title']) ?>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($n['slug']) ?></small>
                                    <?php if (!empty($n['tags'])): ?>
                                        <div class="mt-1">
                                            <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $n['tags']))), 0, 3) as $tag): ?>
                                                <span class="badge bg-light text-success border" style="font-size:10px;"><?= htmlspecialchars($tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($n['author'] ?? 'Admin') ?></td>
                                <td>
                                    <?php if ($n['status'] === 'published'): ?>
                                        <span class="badge bg-success">Đã đăng</span>
                                    <?php elseif ($n['status'] === 'draft'): ?>
                                        <span class="badge bg-warning text-dark">Bản nháp</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>/news/detail/<?= htmlspecialchars($n['slug']) ?>"
                                        class="btn btn-outline-info btn-sm" target="_blank" title="Xem">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/news_edit/<?= $n['id'] ?>"
                                        class="btn btn-warning btn-sm text-white" title="Sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/news_delete/<?= $n['id'] ?>"
                                        class="btn btn-danger btn-sm" title="Xóa"
                                        onclick="return confirm('Xóa bài viết \'<?= addslashes(htmlspecialchars($n['title'])) ?>\'?\nHành động này không thể hoàn tác!')">
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
                <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">‹ Trước</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>/admin/news?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">Sau ›</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
admin_layout_end();
?>