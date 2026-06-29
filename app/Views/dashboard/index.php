<?php
$pageTitle = 'Dashboard Thành Viên | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';

// Xác định avatar (Nếu chưa có thì dùng ảnh mặc định)
$avatar = !empty($user['avatar'])
    ? BASE_URL . '/file/render?path=' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname']);
?>?>

<main class="site-main bg-soft" style="min-height: calc(100vh - 76px); padding: 50px 0;">
    <div class="container">

        <div class="row align-items-center mb-4 pb-3 border-bottom" data-aos="fade-up">
            <div class="col-md-6">
                <h1 style="color: var(--green-900); font-weight: 850;">Tài Khoản Của Tôi</h1>
                <p class="text-muted mb-0">Quản lý hồ sơ cá nhân và bảo mật</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/auth/logout" class="btn btn-outline-danger px-4 rounded-pill">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng Xuất
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $_SESSION['error'];
                                                                    unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mt-2">

            <!-- SIDEBAR THÔNG TIN TÓM TẮT -->
            <div class="col-lg-4" data-aos="fade-right">
                <div class="bg-white p-4 text-center shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="<?= $avatar ?>" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm border border-3 border-white" style="width: 150px; height: 150px;" id="avatarPreviewSidebar">
                        <span class="badge bg-success position-absolute bottom-0 end-0 rounded-circle p-2 border border-2 border-white" title="Tài khoản Active">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </div>

                    <h3 style="color: var(--green-900); font-weight: 800;"><?= htmlspecialchars($user['fullname']) ?></h3>
                    <p class="text-muted mb-1"><i class="fa-solid fa-envelope me-2"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-muted"><i class="fa-solid fa-user-tag me-2"></i> Thành viên Plantify</p>

                    <hr class="my-4">

                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-success fw-bold text-start"><i class="fa-solid fa-user-pen me-2 w-20px text-center"></i> Hồ sơ cá nhân</a>
                        <a href="<?= BASE_URL ?>/dashboard/orders" class="btn btn-light fw-bold text-start border"><i class="fa-solid fa-box-open me-2 w-20px text-center text-success"></i> Đơn hàng của tôi</a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT: FORM CẬP NHẬT -->
            <div class="col-lg-8" data-aos="fade-left">
                <div class="bg-white p-4 p-md-5 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                    <h4 class="mb-4 fw-bold" style="color: var(--stone-900);">Thông tin cá nhân</h4>

                    <!-- Chú ý thêm enctype="multipart/form-data" để upload được ảnh -->
                    <form action="<?= BASE_URL ?>/dashboard/updateProfile" method="POST" enctype="multipart/form-data">

                        <!-- Upload Avatar UI -->
                        <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded border">
                            <img src="<?= $avatar ?>" id="avatarPreviewForm" class="rounded-circle object-fit-cover" style="width: 80px; height: 80px;">
                            <div>
                                <label for="avatarUpload" class="btn btn-outline-success btn-sm mb-2 fw-bold">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Đổi ảnh đại diện
                                </label>
                                <input type="file" name="avatar" id="avatarUpload" class="d-none" accept="image/png, image/jpeg, image/webp">
                                <div class="small text-muted">Hỗ trợ JPG, PNG, WEBP. Tối đa 5MB.</div>
                            </div>
                        </div>

                        <!-- Fullname -->
                        <div class="mb-3">
                            <label for="fullname" class="form-label fw-bold text-muted">Họ và Tên</label>
                            <input type="text" class="form-control bg-light" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>

                        <!-- Username (Readonly) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Tên đăng nhập <span class="badge bg-secondary ms-2">Không thể đổi</span></label>
                            <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                        </div>

                        <!-- Email (Readonly) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Địa chỉ Email <span class="badge bg-secondary ms-2">Không thể đổi</span></label>
                            <input type="email" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="btn btn-success btn-lg px-5 fw-bold" style="border-radius: 10px;">
                            Lưu Thay Đổi
                        </button>
                    </form>
                </div>

            </div>
            <!-- Password -->
            <div class="bg-white p-4 p-md-5 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                <h4 class="mb-4 fw-bold text-success">Bảo mật tài khoản</h4>
                <form action="<?= BASE_URL ?>/dashboard/updatePassword" method="POST">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label text-muted">Mật khẩu hiện tại</label><input type="password" name="current_password" class="form-control bg-light" required></div>
                        <div class="col-md-6"><label class="form-label text-muted">Mật khẩu mới</label><input type="password" name="new_password" class="form-control bg-light" required></div>
                        <div class="col-md-6"><label class="form-label text-muted">Xác nhận mật khẩu</label><input type="password" name="confirm_password" class="form-control bg-light" required></div>
                    </div>
                    <button type="submit" class="btn btn-outline-success mt-4 px-5">Cập nhật mật khẩu</button>
                </form>
            </div>

        </div>
    </div>
</main>

<!-- Script xử lý Preview ảnh khi vừa chọn file -->
<script>
    document.getElementById('avatarUpload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Kiểm tra dung lượng (5MB = 5 * 1024 * 1024 bytes)
            if (file.size > 5242880) {
                alert("File quá lớn. Vui lòng chọn ảnh dưới 5MB.");
                this.value = ""; // Xóa lựa chọn
                return;
            }

            // Đọc file và hiển thị
            const reader = new FileReader();
            reader.onload = function(e) {
                // Đổi src của 2 thẻ img
                document.getElementById('avatarPreviewForm').src = e.target.result;
                document.getElementById('avatarPreviewSidebar').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>