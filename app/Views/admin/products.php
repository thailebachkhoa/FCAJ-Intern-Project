<?php
require_once __DIR__ . '/includes/AdminLayout.php';
$pageTitle = 'Quản lý Sản phẩm | Plantify Admin';

$actionHtml = '<a href="' . BASE_URL . '/admin/product_create" class="btn btn-primary rounded-pill px-4"><i class="fa fa-plus me-2"></i>Thêm sản phẩm</a>';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý Sản phẩm',
    'subtitle' => 'Danh sách cây cảnh và phụ kiện trong hệ thống.',
    'actionHtml' => $actionHtml
]);
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Nổi bật</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?= BASE_URL . '/' . htmlspecialchars($p['image']) ?>"
                                                alt="<?= htmlspecialchars($p['name']) ?>"
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                class="rounded shadow-sm border">
                                        <?php else: ?>
                                            <div class="bg-light d-inline-flex align-items-center justify-content-center rounded"
                                                style="width: 60px; height: 60px;">
                                                <i class="fa-solid fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td class="text-center"><span class="badge bg-info text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
                                    <td class="text-center"><?= number_format($p['price'], 0, ',', '.') ?>đ</td>
                                    <td class="text-center">
                                        <?= $p['is_featured'] ? '<span class="text-warning"><i class="fa fa-star"></i></span>' : '' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/admin/product_edit/<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm mx-1">
                                            <i class="fa-solid fa-pen-to-square"></i> Sửa
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/product_delete/<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm mx-1" onclick="return confirm('Xóa sản phẩm này?')">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>