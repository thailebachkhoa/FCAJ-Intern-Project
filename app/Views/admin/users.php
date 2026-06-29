<?php
require_once __DIR__ . '/includes/AdminLayout.php';
$pageTitle = 'Quản lý Người dùng | Plantify Admin';
$actionHtml = '<a href="' . BASE_URL . '/admin/user_create" class="btn btn-primary rounded-pill px-4"><i class="fa fa-plus me-2"></i>Thêm thành viên</a>';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý Người dùng',
    'actionHtml' => $actionHtml
]);

?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên Đăng Nhập</th>
                                <th>Họ và Tên</th>
                                <th>Chức Vụ</th>
                                <th>Email</th>
                                <th>Trạng Thái</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                                    <td>
                                        <?php if ($u['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Quản trị viên</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Thành viên</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['status'] == 'active'): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Bị Khoá</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] != 'admin'): ?>
                                            <a href="<?= BASE_URL ?>/admin/reset_password/<?= $u['id'] ?>" class="btn btn-warning btn-sm mx-1 text-white" onclick="return confirm('Bạn có chắc muốn cấp lại mật khẩu mặc định (123456) cho tài khoản này không?')">
                                                <i class="fa-solid fa-key"></i> Reset
                                            </a>

                                            <?php if ($u['status'] == 'active'): ?>
                                                <a href="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Bạn có muốn khoá quyền truy cập của người này?')">
                                                    <i class="fa-solid fa-lock"></i> Khoá
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/admin/toggle_status/<?= $u['id'] ?>" class="btn btn-success btn-sm mx-1">
                                                    <i class="fa-solid fa-unlock"></i> Mở
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>/admin/delete_user/<?= $u['id'] ?>" class="btn btn-danger btn-sm mx-1" onclick="return confirm('Xóa người dùng này? Hành động này không thể hoàn tác!')">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fa-solid fa-shield"></i> Không thể can thiệp</span>
                                        <?php endif; ?>
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

<?php
// Hàm này tự động đóng div nội dung và tự động nạp luôn bootstrap, scripts.js
admin_layout_end();
?>