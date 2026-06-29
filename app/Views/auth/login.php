<?php

/**
 * File: app/Views/pages/login.php
 * Chức năng: Trang đăng nhập đồng bộ UI
 */
$pageTitle = 'Đăng nhập | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); display: flex; align-items: center; padding: 40px 0; margin-top:50px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <!-- Khối Card Đăng Nhập -->
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                    <div class="row g-0">

                        <!-- Cột Trái: Hình ảnh minh họa -->
                        <div class="col-lg-6 d-none d-lg-block position-relative">
                            <img src="<?= BASE_URL ?>/file/render?path=uploads/images/log-img.jpeg"
                                alt="Plantify Login"
                                class="w-100 h-100 object-fit-cover"
                                style="min-height: 600px;">
                            <!-- Overlay Text -->
                            <div class="position-absolute bottom-0 start-0 w-100 p-5" style="background: linear-gradient(to top, rgba(18, 56, 42, 0.9), transparent);">
                                <h2 class="text-white fw-bold mb-2">Chào mừng trở lại!</h2>
                                <p class="text-white opacity-75 mb-0">Tiếp tục hành trình xây dựng không gian xanh của riêng bạn cùng Plantify.</p>
                            </div>
                        </div>

                        <!-- Cột Phải: Form Đăng Nhập -->
                        <div class="col-lg-6 d-flex align-items-center bg-white p-4 p-md-5">
                            <div class="w-100">
                                <div class="text-center mb-5">
                                    <div class="brand-mark mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                                        <i class="fa-solid fa-leaf"></i>
                                    </div>
                                    <h2 style="color: var(--green-900); font-weight: 820;">Đăng Nhập</h2>
                                    <p class="text-muted">Vui lòng nhập thông tin để truy cập hệ thống</p>
                                </div>

                                <!-- Thông báo lỗi/thành công từ PHP -->
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                        <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Form Đăng Nhập -->
                                <form action="<?= BASE_URL ?>/auth/login" method="POST" id="loginForm" novalidate>

                                    <!-- Nhập Username / Email -->
                                    <div class="mb-4">
                                        <label for="username" class="form-label fw-bold" style="color: var(--stone-700);">Tài khoản hoặc Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0" style="color: var(--green-700);"><i class="fa-solid fa-envelope"></i></span>
                                            <input type="text" name="username" id="username" class="form-control bg-light border-start-0 ps-0"
                                                placeholder="Nhập tên đăng nhập hoặc email"
                                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                        </div>
                                        <small id="usernameError" class="text-danger mt-1 fw-semibold" style="display: none;"></small>
                                    </div>

                                    <!-- Nhập Mật khẩu -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label for="password" class="form-label fw-bold mb-0" style="color: var(--stone-700);">Mật khẩu</label>
                                            <a href="#" class="text-success text-decoration-none small fw-semibold">Quên mật khẩu?</a>
                                        </div>
                                        <div class="input-group mt-2 position-relative">
                                            <span class="input-group-text bg-light border-end-0" style="color: var(--green-700);"><i class="fa-solid fa-lock"></i></span>
                                            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 ps-0 pe-5"
                                                placeholder="Nhập mật khẩu" required>
                                            <!-- Nút ẩn hiện mật khẩu -->
                                            <span id="togglePassword" class="position-absolute end-0 top-50 translate-middle-y pe-3"
                                                style="cursor: pointer; z-index: 10; color: var(--stone-700);">
                                                <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                            </span>
                                        </div>
                                        <small id="passwordError" class="text-danger mt-1 fw-semibold" style="display: none;"></small>
                                    </div>

                                    <!-- Nút Submit -->
                                    <div class="d-grid gap-3 mt-5">
                                        <button type="submit" class="btn btn-success btn-lg fw-bold" style="height: 52px; border-radius: 12px;">
                                            Đăng Nhập
                                        </button>
                                        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-success btn-lg fw-bold" style="height: 52px; border-radius: 12px;">
                                            Tạo Tài Khoản Mới
                                        </a>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- javascript check validation (client-side) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý Validation Form
        const loginForm = document.getElementById('loginForm');

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                let u = document.getElementById('username').value.trim();
                let p = document.getElementById('password').value.trim();
                let isValid = true;

                // DOM Errors
                let uError = document.getElementById('usernameError');
                let pError = document.getElementById('passwordError');

                // Reset errors
                uError.style.display = 'none';
                pError.style.display = 'none';

                // Validate Username
                if (!u) {
                    uError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Vui lòng nhập tên tài khoản hoặc email';
                    uError.style.display = 'block';
                    isValid = false;
                }

                // Validate Password
                if (!p) {
                    pError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Vui lòng nhập mật khẩu';
                    pError.style.display = 'block';
                    isValid = false;
                } else if (p.length < 3) {
                    pError.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Mật khẩu phải có ít nhất 3 ký tự';
                    pError.style.display = 'block';
                    isValid = false;
                }

                // Ngăn chặn submit nếu có lỗi
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }

        // Xử lý Ẩn/Hiện mật khẩu bằng icon FontAwesome
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Đổi type input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Đổi icon
                if (type === 'text') {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>