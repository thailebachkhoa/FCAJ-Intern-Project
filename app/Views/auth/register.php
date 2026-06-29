<?php

/**
 * File: app/Views/pages/register.php
 * Chức năng: Trang đăng ký tài khoản đồng bộ UI
 */
$pageTitle = 'Đăng ký Tài khoản | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); display: flex; align-items: center; padding: 40px 0; margin-top:50px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <!-- Khối Card Đăng Ký -->
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                    <div class="row g-0 flex-lg-row-reverse"> <!-- Đảo ngược cột: Form bên trái, Ảnh bên phải cho khác biệt với Login -->

                        <!-- Cột Ảnh minh họa -->
                        <div class="col-lg-5 d-none d-lg-block position-relative">
                            <img src="<?= BASE_URL ?>/file/render?path=uploads/images/reg-img.jpeg"
                                alt="Plantify Register"
                                class="w-100 h-100 object-fit-cover"
                                style="min-height: 700px;">
                            <div class="position-absolute bottom-0 start-0 w-100 p-5" style="background: linear-gradient(to top, rgba(18, 56, 42, 0.95), transparent);">
                                <h2 class="text-white fw-bold mb-2">Bắt đầu ngay hôm nay</h2>
                                <p class="text-white opacity-75 mb-0">Tạo tài khoản để quản lý đơn hàng, lưu danh sách yêu thích và nhận cẩm nang chăm sóc cây xanh độc quyền.</p>
                            </div>
                        </div>

                        <!-- Cột Form Đăng Ký -->
                        <div class="col-lg-7 d-flex align-items-center bg-white p-4 p-md-5">
                            <div class="w-100">
                                <div class="mb-4">
                                    <h2 style="color: var(--green-900); font-weight: 820;">Đăng Ký Tài Khoản</h2>
                                    <p class="text-muted">Điền thông tin dưới đây để trở thành thành viên của Plantify</p>
                                </div>

                                <!-- Thông báo lỗi/thành công -->
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

                                <!-- Form Đăng Ký -->
                                <form action="<?= BASE_URL ?>/auth/register" method="POST" id="regForm" novalidate>
                                    <div class="row g-3">

                                        <!-- Họ và Tên -->
                                        <div class="col-md-6">
                                            <label for="fullname" class="form-label fw-bold small" style="color: var(--stone-700);">Họ và Tên</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-id-card"></i></span>
                                                <input type="text" name="fullname" id="fullname" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="Nhập họ và tên đầy đủ" value="<?= htmlspecialchars($data['fullname'] ?? '') ?>" required>
                                            </div>
                                            <small id="fullnameError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Tên đăng nhập -->
                                        <div class="col-md-6">
                                            <label for="username" class="form-label fw-bold small" style="color: var(--stone-700);">Tên đăng nhập</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-user"></i></span>
                                                <input type="text" name="username" id="username" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="Viết liền không dấu" value="<?= htmlspecialchars($data['username'] ?? '') ?>" required>
                                            </div>
                                            <small id="usernameError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-12">
                                            <label for="email" class="form-label fw-bold small" style="color: var(--stone-700);">Địa chỉ Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-envelope"></i></span>
                                                <input type="email" name="email" id="email" class="form-control bg-light border-start-0 ps-0"
                                                    placeholder="example@domain.com" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
                                            </div>
                                            <small id="emailError" class="text-danger mt-1 fw-semibold d-none"></small>
                                        </div>

                                        <!-- Mật khẩu -->
                                        <div class="col-12">
                                            <label for="password" class="form-label fw-bold small" style="color: var(--stone-700);">Mật khẩu</label>
                                            <div class="input-group position-relative">
                                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-lock"></i></span>
                                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0 ps-0 pe-5"
                                                    placeholder="Nhập mật khẩu" required>
                                                <!-- Nút Ẩn/Hiện -->
                                                <span id="togglePassword" class="position-absolute end-0 top-50 translate-middle-y pe-3" style="cursor: pointer; z-index: 10; color: var(--stone-700);">
                                                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                                </span>
                                            </div>
                                            <small id="passwordError" class="text-danger mt-1 fw-semibold d-none"></small>

                                            <!-- UI Bảng điều kiện mật khẩu -->
                                            <div class="password-requirements p-3 mt-3 rounded" id="passwordReqs" style="background: var(--mint-50); border: 1px dashed var(--green-300); display: none;">
                                                <strong class="d-block mb-2" style="color: var(--green-900); font-size: 0.9rem;">Yêu cầu mật khẩu an toàn:</strong>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                                    <li id="req-length" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 6 ký tự</li>
                                                    <li id="req-upper" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ hoa (A-Z)</li>
                                                    <li id="req-lower" class="text-muted mb-1"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ thường (a-z)</li>
                                                    <li id="req-number" class="text-muted"><i class="fa-regular fa-circle-xmark me-2"></i> Ít nhất 1 chữ số (0-9)</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 mt-4 pt-2">
                                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold mb-3" id="submitBtn" style="height: 52px; border-radius: 12px;">
                                                Tạo Tài Khoản
                                            </button>
                                            <div class="text-center">
                                                <span class="text-muted">Đã có tài khoản?</span>
                                                <a href="<?= BASE_URL ?>/auth" class="text-success fw-bold text-decoration-none">Đăng nhập ngay</a>
                                            </div>
                                        </div>

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

<!-- JS Client Side Validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('regForm');
        const fullname = document.getElementById('fullname');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');

        // 1. Password Strength Checker (Giao diện FontAwesome)
        password.addEventListener('input', function() {
            const val = this.value;
            const reqs = document.getElementById('passwordReqs');

            // Hiện bảng điều kiện khi bắt đầu nhập
            if (val.length > 0) {
                reqs.style.display = 'block';
                reqs.style.animation = 'adminCardIn 0.3s ease forwards'; // Kế thừa animation từ style.css
            } else {
                reqs.style.display = 'none';
            }

            const hasLength = val.length >= 6;
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);

            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-number', hasNumber);
        });

        function updateReq(id, isValid) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i');

            if (isValid) {
                el.className = 'text-success mb-1 fw-semibold';
                icon.className = 'fa-solid fa-circle-check me-2';
            } else {
                el.className = 'text-muted mb-1';
                icon.className = 'fa-regular fa-circle-xmark me-2';
            }
        }

        // 2. Ẩn/Hiện mật khẩu
        const togglePassword = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                if (type === 'text') {
                    toggleIcon.className = 'fa-solid fa-eye-slash';
                } else {
                    toggleIcon.className = 'fa-solid fa-eye';
                }
            });
        }

        // 3. Form Validation Submit
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Clear errors
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('d-none');
                el.classList.remove('d-block');
            });
            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

            // Validate fullname
            if (!fullname.value.trim() || fullname.value.trim().length < 3) {
                showError('fullname', 'Họ tên phải có ít nhất 3 ký tự');
                isValid = false;
            }

            // Validate username
            if (!username.value.trim() || username.value.trim().length < 3) {
                showError('username', 'Tên đăng nhập phải có ít nhất 3 ký tự');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_-]+$/.test(username.value)) {
                showError('username', 'Chỉ dùng chữ cái, số, _, -');
                isValid = false;
            }

            // Validate email
            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                showError('email', 'Email không hợp lệ');
                isValid = false;
            }

            // Validate password
            if (!password.value || password.value.length < 6) {
                showError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
                isValid = false;
            }

            if (isValid) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang xử lý...';
            } else {
                e.preventDefault();
            }
        });

        function showError(fieldId, message) {
            const errorEl = document.getElementById(fieldId + 'Error');
            const inputEl = document.getElementById(fieldId);
            errorEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + message;
            errorEl.classList.remove('d-none');
            errorEl.classList.add('d-block');
            inputEl.classList.add('is-invalid');
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>