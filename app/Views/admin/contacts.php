<?php
// app/Views/admin/contacts.php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => 'Quản lý Liên hệ',
    'heading'   => 'Liên hệ từ khách hàng',
    'subtitle'  => 'Danh sách tin nhắn khách hàng gửi qua trang Contact.',
]);
?>

<!-- Flash -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/admin/contacts" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1 small fw-semibold">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên, email hoặc nội dung..."
                    value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-semibold">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="unread" <?= ($statusFilter ?? '') === 'unread' ? 'selected' : '' ?>>Chưa đọc</option>
                    <option value="read" <?= ($statusFilter ?? '') === 'read'   ? 'selected' : '' ?>>Đã đọc</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm
                </button>
                <a href="<?= BASE_URL ?>/admin/contacts" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <h6 class="mb-0">
                Tổng: <strong class="text-success"><?= $total ?? 0 ?></strong> liên hệ
                <?php if (!empty($search)): ?>
                    <span class="text-muted small"> — kết quả cho "<?= htmlspecialchars($search) ?>"</span>
                <?php endif; ?>
            </h6>
            <small class="text-muted">
                <span class="badge bg-danger me-1">Chưa đọc</span> cần xử lý
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="160">Người gửi</th>
                        <th width="160">Chủ đề</th>
                        <th>Nội dung</th>
                        <th width="100">Trạng thái</th>
                        <th width="110">Ngày gửi</th>
                        <th width="130" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-envelope fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có liên hệ nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $i => $c): ?>
                            <tr class="<?= !$c['is_read'] ? 'table-warning' : '' ?>">
                                <td class="text-muted small"><?= ($currentPage - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:13px;"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($c['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-success border" style="font-size:11px;">
                                        <?= htmlspecialchars($c['subject'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width:300px;font-size:13px;line-height:1.5;">
                                        <?= htmlspecialchars(mb_substr($c['message'], 0, 120)) ?>
                                        <?= mb_strlen($c['message']) > 120 ? '<span class="text-muted">...</span>' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!$c['is_read']): ?>
                                        <span class="badge bg-danger">Chưa đọc</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã đọc</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                <td class="text-center">
                                    <?php if (!$c['is_read']): ?>
                                        <a href="<?= BASE_URL ?>/admin/contact_read/<?= $c['id'] ?>" class="btn btn-success btn-sm"
                                            title="Đánh dấu đã đọc">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/contact_delete/<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                                        title="Xóa" onclick="return confirm('Xóa liên hệ này?')">
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

<!-- Pagination -->
<?php if (($totalPages ?? 1) > 1): ?>
    <nav class="mt-4" aria-label="Phân trang">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/contacts?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search ?? '') ?>">‹
                    Trước</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link"
                        href="<?= BASE_URL ?>/admin/contacts?page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= BASE_URL ?>/admin/contacts?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search ?? '') ?>">Sau
                    ›</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>