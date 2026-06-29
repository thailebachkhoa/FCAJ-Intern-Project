<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => $pageTitle, 'heading' => $pageTitle]);
?>
<div class="card shadow-sm border-0 rounded-4 mt-4">
    <div class="card-body p-4">
        <form action="" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Họ và Tên</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Chức vụ</label>
                    <select name="role" class="form-select">
                        <option value="user">Thành viên (User)</option>
                        <option value="admin">Quản trị (Admin)</option>
                    </select>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-success px-5">Lưu thành viên</button>
            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-light ms-2">Hủy</a>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>