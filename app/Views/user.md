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
                            <img src="<?= BASE_URL ?>/assets/images/hero_img.jpg"
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
                            <img src="<?= BASE_URL ?>/assets/images/hero_img.jpg"
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
<?php
$pageTitle = 'Dashboard Thành Viên | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
// Location: app/Views/dashboard/index.php
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
                        <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
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
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/dashboard/order-detail.php -->
<main class="site-main page-main bg-soft" style="margin-top: 100px; min-height: 80vh;">
    <div class="container py-5">
        <div class="mb-4">
            <a href="<?= BASE_URL ?>/dashboard/orders" class="text-decoration-none text-success fw-bold">
                <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="mb-0 fw-bold text-dark">Kiện hàng gồm có</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>"
                                    width="70" class="rounded-3 me-3 border" style="object-fit: cover; height: 70px;" alt="<?= htmlspecialchars($item['name']) ?>">

                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold"><?= $item['name'] ?></h6>
                                    <small class="text-muted">Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?>đ</small>
                                    <br>
                                    <small class="text-muted">Số lượng: <strong><?= $item['quantity'] ?></strong></small>
                                </div>
                                <div class="fw-bold text-success" style="font-size: 1.1rem;">
                                    <?= number_format($item['quantity'] * $item['price'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <hr class="text-muted opacity-25">
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <span class="h5 mb-0 text-dark">Tổng thanh toán:</span>
                            <span class="h4 mb-0 text-success fw-bold"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Trạng thái đơn hàng</h5>
                        <?php
                        $badgeInfo = [
                            'pending'    => ['bg-warning text-dark', 'Chờ xử lý', 'Đơn hàng đang chờ nhân viên xác nhận.'],
                            'processing' => ['bg-info text-dark', 'Đang đóng gói', 'Kho đang chuẩn bị cây cho bạn.'],
                            'shipping'   => ['bg-primary text-white', 'Đang giao hàng', 'Đơn hàng đang trên đường đến.'],
                            'completed'  => ['bg-success text-white', 'Đã hoàn thành', 'Giao hàng thành công. Cảm ơn bạn!'],
                            'cancelled'  => ['bg-danger text-white', 'Đã hủy', 'Đơn hàng đã bị hủy.']
                        ];
                        $statusClass = $badgeInfo[$order['status']][0];
                        $statusLabel = $badgeInfo[$order['status']][1];
                        $statusDesc  = $badgeInfo[$order['status']][2];
                        ?>

                        <div class="mb-3">
                            <span class="badge rounded-pill px-3 py-2 fs-6 <?= $statusClass ?>"><?= $statusLabel ?></span>
                        </div>
                        <p class="text-muted small mb-0"><?= $statusDesc ?></p>

                        <hr>

                        <h5 class="fw-bold mb-3">Thông tin nhận hàng</h5>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-2"><i class="fa-solid fa-user me-2"></i> <strong><?= htmlspecialchars($order['fullname']) ?></strong></li>
                            <li class="mb-2"><i class="fa-solid fa-phone me-2"></i> <?= htmlspecialchars($order['phone']) ?></li>
                            <li class="mb-2"><i class="fa-solid fa-location-dot me-2"></i> <?= htmlspecialchars($order['address']) ?></li>
                            <?php if (!empty($order['note'])): ?>
                                <li class="mt-3 text-warning-emphasis bg-warning-subtle p-2 rounded">
                                    <i class="fa-solid fa-note-sticky me-1"></i> Ghi chú: <?= htmlspecialchars($order['note']) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/dashboard/orders.php -->
<main class="site-main page-main bg-soft" style="margin-top: 100px; min-height: 80vh;">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0">Lịch sử đơn hàng</h3>
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-outline-success rounded-pill px-4">Tiếp tục mua
                            sắm</a>
                    </div>

                    <?php if (empty($myOrders)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Địa chỉ</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myOrders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#ORD-<?= $order['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td class="text-truncate" style="max-width: 200px;"><?= $order['address'] ?></td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <span
                                            class="badge rounded-pill 
                                                    <?= $order['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/dashboard/order_detail/<?= $order['id'] ?>"
                                            class="btn btn-sm btn-outline-success rounded-pill">
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
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
                                <?= csrf_field() ?>
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
<?php

/**
 * File: app/Views/news/index.php
 * Tất cả văn bản tĩnh đọc từ site_content qua content_value()
 */
require BASE_PATH . '/app/Views/partials/header.php';
?>

<main class="site-main">

    <!-- ===== HERO ===== -->
    <section class="news-hero">
        <div class="container" data-aos="fade-up">
            <h1><?= e(content_value('news.hero_title', 'Tin Tức & Bài Viết')) ?></h1>
            <p><?= e(content_value('news.hero_description', 'Khám phá các bài viết về cây cảnh, phong thủy và xu hướng trang trí xanh.')) ?>
            </p>
        </div>
    </section>

    <!-- ===== SEARCH ===== -->
    <div class="container">
        <div class="news-search-bar" data-aos="fade-up" data-aos-delay="100">
            <form action="<?= BASE_URL ?>/news" method="GET">
                <input type="text" name="search"
                    placeholder="<?= e(content_value('news.search_placeholder', 'Tìm kiếm tin tức, bài viết...')) ?>"
                    value="<?= e($search ?? '') ?>">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>
                    <?= e(content_value('news.search_button', 'Tìm kiếm')) ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ===== DANH SÁCH BÀI VIẾT ===== -->
    <section class="news-list py-5">
        <div class="container">

            <?php if (!empty($search)): ?>
                <p class="mb-4 text-muted">
                    Kết quả tìm kiếm cho: <strong><?= e($search) ?></strong>
                    (<?= $total ?? 0 ?> bài viết)
                </p>
            <?php endif; ?>

            <?php if (empty($newsList)): ?>
                <div class="alert alert-info text-center py-4">
                    <?= e(content_value('news.empty_title', 'Không tìm thấy bài viết nào phù hợp!')) ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($newsList as $news): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <article class="news-card">

                                <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>" class="news-card-img">
                                    <?php
                                    $thumbPath = !empty($news['thumbnail'])
                                        ? PUBLIC_PATH . '/' . ltrim($news['thumbnail'], '/')
                                        : '';
                                    if (!empty($news['thumbnail']) && file_exists($thumbPath)):
                                    ?>
                                        <img src="<?= BASE_URL ?>/<?= ltrim($news['thumbnail'], '/') ?>"
                                            alt="<?= e($news['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="news-img-placeholder">
                                            <i class="fa-solid fa-leaf"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <div class="news-card-body">
                                    <span class="date">
                                        <i class="fa-regular fa-calendar me-2"></i>
                                        <?= date('d/m/Y', strtotime($news['created_at'])) ?>
                                    </span>
                                    <h3>
                                        <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>">
                                            <?= e($news['title']) ?>
                                        </a>
                                    </h3>
                                    <p><?= e($news['short_description'] ?? mb_substr(strip_tags($news['content']), 0, 100) . '...') ?>
                                    </p>
                                </div>

                                <div class="news-card-footer">
                                    <span class="author">
                                        <i class="fa-solid fa-pen-nib me-2"></i>
                                        <?= e($news['author'] ?? 'Admin') ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/news/detail/<?= $news['slug'] ?>" class="read-more">
                                        <?= e(content_value('news.card_readmore', 'Xem chi tiết')) ?>
                                        <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>

                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ===== PHÂN TRANG ===== -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="pagination-wrapper mt-5">
                    <div class="pagination">

                        <?php if ($currentPage > 1): ?>
                            <a
                                href="<?= BASE_URL ?>/news?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a
                                    href="<?= BASE_URL ?>/news?page=<?= $p ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a
                                href="<?= BASE_URL ?>/news?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-right"></i></span>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: app/Views/pages/cart.php
 * Chức năng: Giao diện giỏ hàng đồng bộ UI với style.css
 * Location: app/Views/pages/cart.php
 */
$pageTitle = 'Giỏ Hàng | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';
$isCartEmpty = empty($cartItems);

?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); padding: 40px 0; margin-top:50px">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-success text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Giỏ hàng của bạn</li>
            </ol>
        </nav>

        <?php if ($isCartEmpty): ?>
            <!-- GIAO DIỆN GIỎ HÀNG TRỐNG -->
            <div class="row justify-content-center mt-5" data-aos="fade-up">
                <div class="col-lg-6 text-center">
                    <div class="p-5 bg-white rounded shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px !important;">
                        <div class="mb-4 text-success" style="font-size: 5rem;">
                            <i class="fa-solid fa-basket-shopping opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-3"><?= e(content_value('cart.empty_title', 'Giỏ hàng của bạn đang trống')) ?></h2>
                        <p class="text-muted mb-4">
                            <?= e(content_value('cart.empty_text', 'Hãy tiếp tục khám phá...')) ?>
                        </p>
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i> <?= e(content_value('cart.btn_continue_shopping', 'Tiếp tục mua sắm')) ?>
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- GIAO DIỆN KHI CÓ SẢN PHẨM -->
            <div class="row g-4">
                <div class="col-lg-8" data-aos="fade-right">

                    <!-- Hiển thị thông báo xóa thành công -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                            unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white p-4 shadow-sm" style="border: 1px solid var(--stone-200); border-radius: 16px;">
                        <h3 class="border-bottom pb-3 mb-4" style="color: var(--green-900); font-weight: 800;">Chi tiết Giỏ Hàng (<?= count($cartItems) ?> sản phẩm)</h3>

                        <!-- Lặp qua DỮ LIỆU THẬT từ Controller -->
                        <?php foreach ($cartItems as $productId => $item): ?>
                            <div class="row align-items-center border-bottom py-3">
                                <div class="col-3 col-md-2">
                                    <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="img-fluid rounded" style="object-fit: cover; aspect-ratio: 1/1;">
                                </div>
                                <div class="col-9 col-md-4">
                                    <h5 class="mb-1 fw-bold"><a href="<?= BASE_URL ?>/shop/detail/<?= $productId ?>" class="text-dark text-decoration-none"><?= $item['name'] ?></a></h5>
                                    <p class="text-muted small mb-0">Phân loại: <?= $item['category'] ?></p>
                                </div>
                                <div class="col-6 col-md-3 mt-3 mt-md-0">
                                    <!-- FORM TĂNG GIẢM SỐ LƯỢNG -->
                                    <form action="<?= BASE_URL ?>/cart/update" method="POST" class="d-flex align-items-center border rounded p-1" style="max-width: 120px;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $productId ?>">

                                        <button type="submit" name="action" value="decrease" class="btn btn-sm btn-light border-0"><i class="fa-solid fa-minus"></i></button>

                                        <input type="text" class="form-control border-0 text-center px-0 bg-transparent fw-bold" value="<?= $item['quantity'] ?>" readonly>

                                        <button type="submit" name="action" value="increase" class="btn btn-sm btn-light border-0"><i class="fa-solid fa-plus"></i></button>
                                    </form>
                                </div>
                                <div class="col-4 col-md-2 text-end mt-3 mt-md-0 fw-bold" style="color: var(--green-700);">
                                    <?= number_format($item['subtotal'], 0, ',', '.') ?>đ
                                </div>
                                <div class="col-2 col-md-1 text-end mt-3 mt-md-0">
                                    <!-- NÚT XÓA -->
                                    <form action="<?= BASE_URL ?>/cart/remove/<?= $productId ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ?');"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4">
                            <a href="<?= BASE_URL ?>/shop" class="text-success text-decoration-none fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua sắm
                            </a>
                        </div>
                    </div>
                </div>


                <div class="col-lg-4" data-aos="fade-left">
                    <div class="bg-white p-4 shadow-sm position-sticky" style="border: 1px solid var(--stone-200); border-radius: 16px; top: 100px;">
                        <h4 class="border-bottom pb-3 mb-4 fw-bold" style="color: var(--stone-900);">
                            <?= e(content_value('cart.summary_title', 'Tổng Đơn Hàng')) ?>
                        </h4>

                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span><?= e(content_value('cart.label_subtotal', 'Tạm tính:')) ?></span>
                            <strong><?= number_format($totalPrice, 0, ',', '.') ?>đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span><?= e(content_value('cart.label_shipping', 'Phí vận chuyển:')) ?></span>
                            <span>Chưa tính</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4 align-items-center">
                            <span class="fw-bold" style="font-size: 1.1rem;"><?= e(content_value('cart.label_total', 'Tổng cộng:')) ?></span>
                            <span class="fw-bold" style="font-size: 1.5rem; color: var(--green-700);"><?= number_format($totalPrice, 0, ',', '.') ?>đ</span>
                        </div>

                        <button type="button" class="btn btn-success btn-lg w-100 fw-bold" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                            <?= e(content_value('cart.btn_checkout', 'Thanh Toán Ngay')) ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
    </div>
<?php endif; ?>

</div>

<!-- Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="checkoutModalLabel" style="color: var(--green-900);">Thông tin giao hàng</h5>
                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/dashboard/checkout" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Họ và tên người nhận</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required placeholder="Nhập tên người nhận">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="Nhập số điện thoại liên lạc">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Địa chỉ nhận hàng</label>
                        <textarea name="address" class="form-control" rows="2" required placeholder="Địa chỉ chi tiết (Số nhà, đường, phường/xã...)"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Ghi chú thêm (Nếu có)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Xác nhận Đặt hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<!-- Location: app/Views/pages/checkout.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - BTL Cây Cảnh</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages.css">
</head>
}

.form-row {
grid-template-columns: 1fr;
}
}
</style>
</head>

<body>
    <nav class="navbar">
        <div style="font-size: 20px; font-weight: bold;">🌿 BTL Cây Cảnh</div>
        <div>
            <a href="<?= BASE_URL ?>">Trang Chủ</a>
            <a href="<?= BASE_URL ?>/home/shop">Cửa Hàng</a>
            <a href="<?= BASE_URL ?>/news">Tin Tức</a>
            <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
            <a href="<?= BASE_URL ?>/auth/logout">Đăng Xuất</a>
        </div>
    </nav>

    <div class="container">
        <h1>💳 Thanh Toán Đơn Hàng</h1>

        <div class="checkout-container">
            <!-- Form Thanh Toán -->
            <div class="form-section">
                <h2>📍 Thông Tin Giao Hàng</h2>

                <div class="info-box">
                    <strong>👤 Khách hàng:</strong> <?= htmlspecialchars($user['fullname']) ?><br>
                    <strong>📧 Email:</strong> <?= htmlspecialchars($user['email']) ?>
                </div>

                <form id="checkoutForm" method="POST">
                    <div class="form-group">
                        <label for="phone">📱 Số Điện Thoại</label>
                        <input type="tel" id="phone" name="phone" placeholder="0123 456 789" required>
                    </div>

                    <div class="form-group">
                        <label for="address">🏠 Địa Chỉ Giao Hàng</label>
                        <textarea id="address" name="address" placeholder="Nhập địa chỉ chi tiết" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">Thành Phố</label>
                            <input type="text" id="city" name="city" placeholder="TP HCM" required>
                        </div>
                        <div class="form-group">
                            <label for="district">Quận/Huyện</label>
                            <input type="text" id="district" name="district" placeholder="Quận 1" required>
                        </div>
                    </div>

                    <h2 style="margin-top: 2rem;">💰 Hình Thức Thanh Toán</h2>

                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked>
                            💵 Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="bank">
                            🏦 Chuyển khoản ngân hàng
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="terms" required>
                            Tôi đồng ý với điều khoản và chính sách của cửa hàng
                        </label>
                    </div>

                    <button type="submit" class="btn">✅ Hoàn Tất Đơn Hàng</button>
                </form>
            </div>

            <!-- Tóm Tắt Đơn Hàng -->
            <div class="order-summary">
                <h2>📦 Tóm Tắt Đơn Hàng</h2>

                <div class="summary-item">
                    <span>Cây Hạnh Phúc (x2)</span>
                    <span>300.000 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Cây Dây Ô (x1)</span>
                    <span>120.000 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Phí vận chuyển</span>
                    <span>0 VNĐ</span>
                </div>

                <div class="summary-item">
                    <span>Tổng Cộng</span>
                    <span>420.000 VNĐ</span>
                </div>

                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem; font-size: 13px; color: #666;">
                    <p><strong>ℹ️ Lưu ý:</strong></p>
                    <ul style="margin-left: 1rem;">
                        <li>Giao hàng trong 2-3 ngày làm việc</li>
                        <li>Miễn phí vận chuyển cho đơn từ 500k</li>
                        <li>Liên hệ: 0123 456 789</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('✅ Đơn hàng của bạn đã được tiếp nhận!\n\nChúng tôi sẽ liên hệ với bạn trong vòng 24 giờ để xác nhận.');
            window.location.href = '<?= BASE_URL ?>/dashboard';
        });
    </script>
</body>

</html>
<?php

/**
 * File: app/Views/pages/faq.php
 * Tất cả văn bản tĩnh đọc từ site_content qua content_value()
 * Location: app/Views/pages/faq.php
 */

$pageTitle       = content_value('faq.meta_title',       'FAQ | Câu hỏi thường gặp về cây cảnh và decor xanh');
$pageDescription = content_value('faq.meta_description', 'Giải đáp câu hỏi về khảo sát, bảo hành, chăm sóc định kỳ, tư vấn online và dịch vụ cây xanh doanh nghiệp.');

require_once BASE_PATH . '/app/Views/partials/header.php';
?>

<!-- ===== HERO ===== -->
<section class="page-hero faq-hero modern-hero">
    <div class="container">
        <div class="row g-5 align-items-end">

            <div class="col-lg-8" data-aos="fade-up">
                <span class="section-kicker">
                    <?= e(content_value('faq.hero_kicker', 'FAQ & tư vấn nhanh')) ?>
                </span>
                <h1><?= e(content_value('faq.hero_title', 'Câu hỏi thường gặp về cây xanh, decor và chăm sóc định kỳ')) ?>
                </h1>
                <p><?= e(content_value('faq.hero_description', 'Tra cứu nhanh các thông tin quan trọng trước khi khảo sát, chọn cây, nhận báo giá hoặc sử dụng gói chăm sóc sau bàn giao.')) ?>
                </p>
                <div class="faq-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="faqSearchInput" type="search"
                        placeholder="<?= e(content_value('faq.hero_search_placeholder', 'Tìm nhanh: bảo hành, khảo sát, gửi ảnh, chăm sóc...')) ?>">
                </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left">
                <div class="hero-insight-card">
                    <i class="fa-solid fa-headset"></i>
                    <strong><?= e(content_value('faq.hero_card_title', 'Cần câu trả lời riêng?')) ?></strong>
                    <span>"Nhấn biểu tượng zalo để liên hệ đội ngũ tư vấn"</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== NỘI DUNG CHÍNH ===== -->
<section class="section-padding faq-modern-section">
    <div class="container">
        <div class="row g-5">

            <!-- SIDEBAR -->
            <div class="col-lg-4" data-aos="fade-right">
                <aside class="faq-side faq-dashboard">
                    <span class="section-kicker">
                        <?= e(content_value('faq.sidebar_kicker', 'Điểm cần biết')) ?>
                    </span>
                    <h2><?= e(content_value('faq.sidebar_title', 'Chuẩn bị trước khi tư vấn')) ?></h2>
                    <p><?= e(content_value('faq.sidebar_description', 'Thông tin càng rõ, phương án cây xanh càng sát nhu cầu và ngân sách.')) ?>
                    </p>

                    <div class="faq-prep-list">
                        <div>
                            <i class="fa-solid fa-camera"></i>
                            <span><?= e(content_value('faq.sidebar_item_1', 'Ảnh tổng thể và góc cần đặt cây')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-sun"></i>
                            <span><?= e(content_value('faq.sidebar_item_2', 'Thời lượng ánh sáng trong ngày')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-ruler-combined"></i>
                            <span><?= e(content_value('faq.sidebar_item_3', 'Kích thước khu vực dự kiến')) ?></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-wallet"></i>
                            <span><?= e(content_value('faq.sidebar_item_4', 'Ngân sách hoặc mức ưu tiên')) ?></span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success info-cta" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                        <?= e(content_value('faq.sidebar_cta', 'Tìm hiểu thêm')) ?>
                    </button>
                </aside>

            </div>

            <!-- ACCORDION FAQ -->
            <div class="col-lg-8" data-aos="fade-left">
                <div class="faq-tabs" aria-label="Lọc câu hỏi FAQ">
                    <button type="button" class="faq-filter active" data-filter="all">Tất cả</button>
                    <button type="button" class="faq-filter" data-filter="survey">Khảo sát</button>
                    <button type="button" class="faq-filter" data-filter="care">Chăm sóc</button>
                    <button type="button" class="faq-filter" data-filter="warranty">Bảo hành</button>
                    <button type="button" class="faq-filter" data-filter="online">Online</button>
                </div>

                <div class="accordion custom-accordion faq-accordion-modern" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <?php
                        $question = $faq['question'] ?? '';
                        $answer   = $faq['answer']   ?? '';
                        $haystack = $question . ' ' . $answer;
                        $category = 'all';
                        if (preg_match('/khảo sát|khao sat/iu', $haystack))          $category = 'survey';
                        elseif (preg_match('/bảo hành|bao hanh|thay thế/iu', $haystack)) $category = 'warranty';
                        elseif (preg_match('/online|ảnh|anh/iu', $haystack))          $category = 'online';
                        elseif (preg_match('/chăm sóc|cham soc/iu', $haystack))       $category = 'care';
                        ?>
                    <div class="accordion-item faq-item" data-category="<?= e($category) ?>"
                        data-search="<?= e($haystack) ?>">

                        <h2 class="accordion-header" id="heading<?= $index ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                aria-controls="collapse<?= $index ?>">
                                <span class="faq-number">
                                    <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                                <?= e($faq['question']) ?>
                            </button>
                        </h2>

                        <div id="collapse<?= $index ?>"
                            class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                            aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= e($faq['answer']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="faqEmptyState" class="faq-empty-state" hidden>
                    <i class="fa-regular fa-circle-question"></i>
                    <strong>Chưa tìm thấy câu hỏi phù hợp</strong>
                    <span>Thử từ khóa khác hoặc hỏi trực tiếp đội ngũ chăm sóc ở góc màn hình.</span>
                </div>
            </div>

        </div>
    </div>

        <!-- Modal: Đang cập nhật -->
    <div class="modal fade" id="comingSoonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background:#ffffff;">
                <div class="modal-body text-center p-5">
                    <div class="mb-3 text-success" style="font-size: 3rem;">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Trang đang được cập nhật</h5>
                    <p class="text-muted mb-4">Nội dung này sẽ sớm ra mắt. Cảm ơn bạn đã ghé thăm Plantify!</p>
                    <button type="button" class="btn btn-success rounded-pill px-4" data-bs-dismiss="modal">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ===== 3 BƯỚC SAU FAQ ===== -->
<section class="section-padding bg-soft">
    <div class="container">
        <div class="section-heading text-center" data-aos="fade-up">
            <span class="section-kicker">
                <?= e(content_value('faq.steps_kicker', 'Sau khi có câu trả lời')) ?>
            </span>
            <h2><?= e(content_value('faq.steps_title', 'Quy trình tiếp theo rất gọn')) ?></h2>
        </div>
        <div class="row g-4">

            <div class="col-md-4" data-aos="fade-up">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_1_icon', 'fa-paperclip')) ?>"></i>
                    <h3><?= e(content_value('faq.step_1_title', 'Gửi ảnh và nhu cầu')) ?></h3>
                    <p><?= e(content_value('faq.step_1_text', 'Đính kèm ảnh hiện trạng, phong cách mong muốn và ngân sách dự kiến.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_2_icon', 'fa-comments')) ?>"></i>
                    <h3><?= e(content_value('faq.step_2_title', 'Nhận tư vấn sơ bộ')) ?></h3>
                    <p><?= e(content_value('faq.step_2_text', 'Plantify đề xuất nhóm cây, kích thước chậu và mức chăm sóc phù hợp.')) ?>
                    </p>
                </article>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
                <article class="faq-step-card h-100">
                    <i class="fa-solid <?= e(content_value('faq.step_3_icon', 'fa-calendar-days')) ?>"></i>
                    <h3><?= e(content_value('faq.step_3_title', 'Chốt lịch khảo sát')) ?></h3>
                    <p><?= e(content_value('faq.step_3_text', 'Đội ngũ kiểm tra thực tế trước khi báo giá và triển khai chính thức.')) ?>
                    </p>
                </article>
            </div>

        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>
<?php require_once BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: app/Views/pages/home.php
 * View: Trang chủ Plantify Co
 * Tất cả văn bản đọc từ site_content qua content_value()
 */

$steps = [
    ['number' => '01', 'title' => 'about.process_1_title', 'text' => 'about.process_1_text', 'default_title' => 'Tiếp nhận nhu cầu', 'default_text' => 'Nhận ảnh, mặt bằng, phong cách mong muốn và mức ngân sách dự kiến.'],
    ['number' => '02', 'title' => 'about.process_2_title', 'text' => 'about.process_2_text', 'default_title' => 'Khảo sát điều kiện', 'default_text' => 'Đánh giá ánh sáng, gió, ổ cắm, lối đi, vị trí tưới và rủi ro bẩn sàn.'],
    ['number' => '03', 'title' => 'about.process_3_title', 'text' => 'about.process_3_text', 'default_title' => 'Đề xuất phương án', 'default_text' => 'Gợi ý cây, chậu, bố cục, tần suất chăm sóc và phương án thay thế khi cần.'],
    ['number' => '04', 'title' => 'about.process_4_title', 'text' => 'about.process_4_text', 'default_title' => 'Bàn giao và duy trì', 'default_text' => 'Lắp đặt gọn, hướng dẫn chăm sóc, theo dõi cây sau bàn giao và bảo dưỡng định kỳ.'],
];

?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

<main class="site-main">

    <!-- ===== HERO SECTION ===== -->
    <section class="page-hero modern-hero"
        style="background: linear-gradient(135deg, rgba(18, 56, 42, 0.86), rgba(31, 111, 77, 0.62)), url('<?= BASE_URL ?>/assets/images/hero_img.jpg') center/cover;">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row g-5 align-items-end">

                <div class="col-lg-8" data-aos="fade-up">
                    <span class="section-kicker text-white opacity-75">
                        <?= e(content_value('home.hero_kicker', 'Khởi Đầu Mới')) ?>
                    </span>
                    <h1><?= content_value('home.hero_title', 'Biến Không Gian Sống<br>Thành Vườn Xanh Bình Yên') ?></h1>
                    <p class="text-white opacity-75" style="max-width: 600px;">
                        <?= e(content_value('home.hero_description', 'Khám phá bộ sưu tập cây cảnh tuyển chọn giúp thanh lọc không khí, mang lại cảm giác thư thái và nguồn năng lượng tích cực cho ngôi nhà của bạn.')) ?>
                    </p>
                    <div class="hero-actions">
                        <a href="<?= BASE_URL ?>/shop" class="btn btn-success px-4">
                            <i class="fa-solid fa-bag-shopping me-2"></i>
                            <?= e(content_value('home.hero_btn_primary', 'Mua Sắm Ngay')) ?>
                        </a>
                    <a href="#" class="btn btn-outline-light px-4" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                        <?= e(content_value('home.hero_btn_secondary', 'Tìm Hiểu Thêm')) ?>
                    </a>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-left" data-aos-delay="100">
                    <div class="hero-insight-card">
                        <i class="fa-solid fa-leaf"></i>
                        <strong><?= e(content_value('home.hero_card_title', '100% Cây Khỏe Mạnh')) ?></strong>
                        <span><?= e(content_value('home.hero_card_text', 'Được chăm sóc và kiểm tra kỹ lưỡng bởi chuyên gia thực vật trước khi giao đến tay bạn.')) ?></span>
                    </div>
                </div>

            </div>

            <div class="hero-metrics" data-aos="fade-up" data-aos-delay="200">
                <div>
                    <strong><?= e(content_value('home.metric_1_value', '500+')) ?></strong>
                    <span><?= e(content_value('home.metric_1_label', 'Sản phẩm đa dạng')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_2_value', '100%')) ?></strong>
                    <span><?= e(content_value('home.metric_2_label', 'Giao hàng an toàn')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_3_value', '24/7')) ?></strong>
                    <span><?= e(content_value('home.metric_3_label', 'Hỗ trợ chăm sóc')) ?></span>
                </div>
                <div>
                    <strong><?= e(content_value('home.metric_4_value', '30 ngày')) ?></strong>
                    <span><?= e(content_value('home.metric_4_label', 'Đồng hành cùng cây')) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES / ABOUT SECTION ===== -->
    <section class="section-padding bg-soft">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-image-stack position-relative">
                        <img src="<?= BASE_URL ?>/assets/images/home_feature_img.jpeg" class="rounded-4 shadow-lg w-100"
                            alt="Plantify Concept">
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="ps-lg-4">
                        <span class="section-kicker" style="color: var(--green-600);">
                            <?= e(content_value('home.features_kicker', 'Về Chúng Tôi')) ?>
                        </span>
                        <h2 class="display-6 mb-4" style="color: var(--green-900); font-weight: 800;">
                            <?= e(content_value('home.features_title', 'Chăm sóc từ tâm, xanh tươi không gian sống')) ?>
                        </h2>
                        <p class="lead text-muted mb-4">
                            <?= e(content_value('home.features_lead', 'Plantify không chỉ bán cây, chúng tôi trao đi nguồn năng lượng chữa lành từ tự nhiên.')) ?>
                        </p>

                        <div class="about-check-grid mt-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-seedling text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_1', 'Cây trồng hữu cơ chuẩn VietGAP')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-paint-roller text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_2', 'Chậu gốm thủ công nghệ thuật')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-headset text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_3', 'Tư vấn phong thủy miễn phí 24/7')) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3 bg-white shadow-sm d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;border-radius:12px;">
                                    <i class="fa-solid fa-truck-fast text-success"></i>
                                </div>
                                <span class="fw-bold" style="color:var(--green-900);">
                                    <?= e(content_value('home.feature_4', 'Bao bì sinh học bảo vệ môi trường')) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== PRODUCTS SECTION ===== -->
    <section class="section-padding bg-white">
        <div class="container">
            <div class="section-heading text-center mb-5" data-aos="fade-up">
                <span class="section-kicker" style="color:var(--green-600);">
                    <?= e(content_value('home.products_kicker', 'Bộ Sưu Tập Tuyển Chọn')) ?>
                </span>
                <h2 style="color:var(--green-900);font-weight:800;">
                    <?= e(content_value('home.products_title', 'Sản Phẩm Nổi Bật')) ?>
                </h2>
            </div>

            <div class="row g-4">
                <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="product-card h-100 bg-white border-0 shadow-sm"
                        style="border-radius:20px;overflow:hidden;">
                        <div class="position-relative">
                            <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>">
                                <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>"
                                    alt="<?= e($product['name']) ?>" class="w-100 object-fit-cover"
                                    style="height:280px;">
                            </a>
                            <div class="position-absolute top-0 end-0 p-3">
                                <span class="badge bg-white text-success shadow-sm rounded-pill px-3 py-2">Nổi
                                    bật</span>
                            </div>
                        </div>
                        <div class="product-body p-4 text-center">
                            <span class="text-muted small text-uppercase fw-bold"
                                style="letter-spacing:1px;"><?= e($product['category']) ?></span>
                            <h3 class="mt-2 mb-3 fs-5">
                                <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="text-decoration-none"
                                    style="color:var(--green-900);font-weight:700;">
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold" style="color:var(--green-700);font-size:1.1rem;">
                                    <?= number_format($product['price'], 0, ',', '.') ?>đ
                                </span>
                                <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>"
                                    class="btn btn-outline-success btn-sm rounded-pill px-3">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-center text-muted">Đang cập nhật sản phẩm nổi bật...</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5">
                <a href="<?= BASE_URL ?>/shop" class="btn btn-success btn-lg px-5 rounded-pill shadow-sm fw-bold">
                    Xem tất cả cửa hàng <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== STORY SECTION ===== -->
    <section class="section-padding about-story-section bg-soft">
        <div class="container">
            <div class="row g-5 align-items-center">

                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-image-stack">
                        <img src="<?= BASE_URL ?>/assets/images/home_bottom_img.jpeg" alt="Câu chuyện Plantify"
                            class="img-fluid rounded-image">
                        <div class="image-note">
                            <strong>Đồng hành cùng sự phát triển</strong>
                            <span>Chúng tôi cung cấp kiến thức để bất kỳ ai cũng có thể làm vườn.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <span class="section-kicker">
                        <?= e(content_value('home.story_kicker', 'Câu Chuyện Của Chúng Tôi')) ?>
                    </span>
                    <h2 class="section-title">
                        <?= e(content_value('home.story_title', 'Khát khao mang không gian xanh vào cuộc sống hiện đại')) ?>
                    </h2>
                    <p><?= e(content_value('home.story_p1', 'Plantify Co ra đời từ tình yêu với thiên nhiên. Chúng tôi tin rằng, một mầm xanh không chỉ làm đẹp căn phòng mà còn là liệu pháp tinh thần vô giá sau những giờ làm việc căng thẳng.')) ?>
                    </p>
                    <p><?= e(content_value('home.story_p2', 'Với quy trình tuyển chọn khắt khe từ các nhà vườn uy tín, chúng tôi cam kết mỗi sản phẩm gửi đi đều đạt chất lượng cao nhất. Chúng tôi không chỉ bán cây, mà còn trao đi nguồn năng lượng chữa lành từ tự nhiên.')) ?>
                    </p>
                    <div class="about-check-grid mt-4">
                        <span><i class="fa-solid fa-check"></i> Cây trồng hữu cơ</span>
                        <span><i class="fa-solid fa-check"></i> Chậu gốm thủ công</span>
                        <span><i class="fa-solid fa-check"></i> Tư vấn miễn phí</span>
                        <span><i class="fa-solid fa-check"></i> Bao bì thân thiện</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    
    <!-- About Process -->
    <section class="section-padding">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5" data-aos="fade-right">
                    <span class="section-kicker"><?php echo e(content_value('about.process_kicker', 'Quy trình')); ?></span>
                    <h2 class="section-title"><?php echo e(content_value('about.process_title', 'Rõ từng bước để khách hàng dễ theo dõi')); ?></h2>
                    <p class="text-muted"><?php echo e(content_value('about.process_text', 'Từ ảnh không gian ban đầu đến chăm sóc định kỳ, mỗi giai đoạn đều có đầu ra cụ thể để bạn duyệt nhanh và kiểm soát ngân sách.')); ?></p>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="timeline-list">
                        <?php foreach ($steps as $step): ?>
                            <article>
                                <span><?php echo e($step['number']); ?></span>
                                <div>
                                    <h3><?php echo e(content_value($step['title'], $step['default_title'])); ?></h3>
                                    <p><?php echo e(content_value($step['text'], $step['default_text'])); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Map Section -->
    <section class="section-padding map-section" id="plantifyMap">
        <div class="container">
            <div class="map-layout-row">
                <div class="map-copy-panel" data-aos="fade-right">
                    <div>
                        <span class="section-kicker"><?php echo e(content_value('about.map_kicker', 'Vị trí')); ?></span>
                        <h2 class="section-title"><?php echo e(content_value('about.map_title', 'Ghé Plantify để chọn cây và chậu trực tiếp')); ?></h2>
                        <p class="text-muted"><?php echo e(content_value('company.address', '')); ?></p>
                        <p class="text-muted"><a href="https://maps.app.goo.gl/8ynWGgQHBHb7Ez1E8" target="_blank" rel="noopener noreferrer">Xem bản đồ</a></p>
                    </div>
                    <div class="map-contact-list">
                        <span><i class="fa-solid fa-phone"></i><?php echo e(content_value('company.phone', '')); ?></span>
                        <span><i class="fa-solid fa-clock"></i><?php echo e(content_value('company.hours', '')); ?></span>
                    </div>
                </div>
                <div class="map-embed-wrap" data-aos="fade-left" style="width:100%; max-width:100%; min-height:720px;">
                    <iframe
                        title="<?php echo e(content_value('about.map_iframe_title', 'Bản đồ Plantify Co')); ?>"
                        src="https://www.google.com/maps?q=<?php echo rawurlencode(content_value('company.address', '')); ?>&output=embed"
                        width="100%"
                        height="720"
                        style="width:100%; height:720px; min-height:72vh; display:block; border:0;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>


    <!-- ===== CTA SECTION ===== -->
    <section class="cta-section">
        <div class="container position-relative">
            <div class="row align-items-center g-4 text-center text-lg-start">
                <div class="col-lg-8">
                    <h2><?= e(content_value('home.cta_title', 'Sẵn sàng mang thiên nhiên vào nhà?')) ?></h2>
                    <p class="mb-0">
                        <?= e(content_value('home.cta_text', 'Đừng ngần ngại liên hệ nếu bạn cần chuyên gia của Plantify tư vấn loại cây phù hợp với không gian và mệnh của mình.')) ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/shop"
                        class="btn btn-light btn-lg text-success fw-bold px-4 rounded-pill cta-button">
                        <?= e(content_value('home.cta_button', 'Bắt Đầu Mua Sắm')) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Modal Đang cập nhật -->
<div class="modal fade" id="comingSoonModal" tabindex="-1" aria-labelledby="comingSoonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-info-circle fs-1 text-primary mb-3 d-block"></i>
                <p class="mb-0 fs-5">Đang cập nhập thông tin</p>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- Location: app/Views/pages/product-detail.php -->
<main class="site-main page-main" style="margin-top: 50px;">
    <div class="container py-4">
        <!-- Thông báo (Success/Error) -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-success text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop" class="text-success text-decoration-none">Cửa hàng</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $product['name'] ?></li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Cột Trái: Ảnh Sản Phẩm -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="position-relative">
                    <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>" alt="<?= $product['name'] ?>" class="rounded-image w-100" style="min-height: 500px; object-fit: cover;">
                </div>
            </div>

            <!-- Cột Phải: Thông tin & Giỏ hàng -->
            <div class="col-lg-6" data-aos="fade-left">
                <span class="section-kicker"><?= $product['category'] ?></span>
                <h1 class="mb-3" style="color: var(--green-900); font-weight: 850; font-size: 2.5rem;"><?= $product['name'] ?></h1>
                <h2 class="mb-4" style="color: var(--green-700); font-weight: 800; font-size: 2rem;">
                    <?= number_format($product['price'], 0, ',', '.') ?> VNĐ
                </h2>

                <p class="text-muted" style="font-size: 1.05rem; line-height: 1.8;">
                    <?= $product['description'] ?>
                </p>



                <hr class="my-4 text-muted">

                <!-- Form Thêm vào giỏ hàng -->
                <?php if ($user): ?>
                    <form action="<?= BASE_URL ?>/shop/addToCart" method="POST" class="d-flex align-items-center gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="d-flex align-items-center border rounded p-1" style="border-color: var(--stone-200);">
                            <label for="qty" class="me-2 ms-2 text-muted fw-bold">SL:</label>
                            <input type="number" id="qty" name="quantity" value="1" min="1" class="form-control border-0 text-center" style="width: 70px; box-shadow: none;">
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-outline-success btn-lg px-4 rounded-pill">
                            <i class="fa-solid fa-cart-plus me-2"></i> <?= e(content_value('product.btn_add_to_cart', 'Thêm vào giỏ')) ?>
                        </button>
                        <button type="submit" name="buy_now" class="btn btn-success btn-lg px-4 rounded-pill">
                            <?= e(content_value('product.btn_buy_now', 'Mua ngay')) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 rounded" style="background: var(--mint-50); border: 1px dashed var(--green-300);">
                        <p class="mb-3 text-center text-muted"><i class="fa-solid fa-lock text-success mb-2 fs-3 d-block"></i> Bạn cần đăng nhập để mua hàng.</p>
                        <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success w-100">Đăng Nhập Ngay</a>
                    </div>
                <?php endif; ?>

                <!-- Cam kết -->
                <div class="product-trust-badges mt-4 pt-4 border-top">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-truck-fast text-success me-1"></i> <?= e(content_value('product.trust_badge_1', 'Giao hàng nhanh')) ?></div>
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i> <?= e(content_value('product.trust_badge_2', 'Thanh toán an toàn')) ?></div>
                        <div class="col-6 col-md-4 small text-muted"><i class="fa-solid fa-arrows-rotate text-success me-1"></i> <?= e(content_value('product.trust_badge_3', '1 đổi 1 trong 3 ngày')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SẢN PHẨM LIÊN QUAN -->
    <section class="section-padding bg-soft mt-5">
        <div class="container">
            <div class="section-heading text-center mb-5">
                <h2><?= e(content_value('product.related_title', 'Có thể bạn cũng thích')) ?></h2>
            </div>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $item): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="product-card h-100 bg-white">
                            <a href="<?= BASE_URL ?>/shop/detail/<?= $item['id'] ?>">
                                <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>" alt="<?= $item['name'] ?>" class="w-100 object-fit-cover" style="height: 220px;"></a>
                            <div class="product-body">
                                <span><?= $item['category'] ?></span>
                                <h3 class="mt-1 mb-2 fs-5"><a href="<?= BASE_URL ?>/shop/detail/<?= $item['id'] ?>" class="text-dark"><?= $item['name'] ?></a></h3>
                                <strong><?= number_format($item['price'], 0, ',', '.') ?>đ</strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
<!-- location: app/Views/pages/shop.php -->
<?php
// Lấy các tham số từ URL để duy trì trạng thái lọc
$currentCategory = $_GET['category'] ?? 'all';
$currentSort = $_GET['sort'] ?? 'newest';
$searchKeyword = $_GET['search'] ?? '';

// Hàm tạo URL để không làm mất các tham số khác khi nhấn vào lọc/phân trang
function buildUrl($overrides = [])
{
    $params = array_merge($_GET, $overrides);
    return "?" . http_build_query($params);
}
?>

<main class="site-main" style="padding-top: 0;">

    <section class="page-hero" style="padding: 120px 0 60px 0; background: linear-gradient(135deg, rgba(18, 56, 42, 0.9), rgba(45, 138, 95, 0.8)), url('<?= BASE_URL ?>/file/render?path=uploads/images/shop-hero-img.jpg') center/cover;">
        <div class="container text-center" data-aos="fade-up">
            <h1 class="display-4 fw-bold text-white"><?= e(content_value('shop.hero_title', 'Cửa Hàng Xanh')) ?></h1>
            <p class="mx-auto text-white opacity-75" style="max-width: 600px;">
                <?= e(content_value('shop.hero_description', 'Khám phá bộ sưu tập cây xanh...')) ?>
            </p>

            <!-- <div class="mt-4 mx-auto" style="max-width: 500px;">
                <form action="" method="GET" class="input-group shadow-lg rounded-pill overflow-hidden">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">

                    <input type="text" name="search" class="form-control border-0 ps-4 py-3"
                        placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây bạn yêu thích...')) ?>"
                        value="<?= htmlspecialchars($searchKeyword) ?>">
                    <button class="btn btn-success px-4" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div> -->
        </div>
    </section>

    <section id="product-list" class="section-padding bg-soft">
        <div class="container">

            <div class="row mb-4 align-items-center">
                <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/shop?category=all" class="btn <?= $currentCategory === 'all' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Tất cả</a>
                        <a href="<?= BASE_URL ?>/shop?category=Để bàn" class="btn <?= $currentCategory === 'Để bàn' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Để bàn</a>
                        <a href="<?= BASE_URL ?>/shop?category=Sàn nhà" class="btn <?= $currentCategory === 'Sàn nhà' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Sàn nhà</a>
                        <a href="<?= BASE_URL ?>/shop?category=Phụ kiện" class="btn <?= $currentCategory === 'Phụ kiện' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Phụ kiện</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-flex border border-success rounded-pill overflow-hidden bg-white">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($currentSort !== 'newest'): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
                        <?php endif; ?>

                        <input type="text" name="search" class="form-control border-0 ps-3 py-2 shadow-none text-sm"
                            placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây...')) ?>"
                            value="<?= htmlspecialchars($searchKeyword) ?>">
                        <button type="submit" class="btn btn-success px-3"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="col-lg-3 col-md-6 text-md-end">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-inline-block w-100">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($searchKeyword !== ''): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchKeyword) ?>">
                        <?php endif; ?>

                        <span class="text-muted d-none d-xxl-inline me-2"><?= e(content_value('shop.sort_label', 'Sắp xếp:')) ?></span>
                        <select name="sort" class="form-select d-inline-block w-auto border-success rounded-pill" onchange="this.form.submit()">
                            <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $currentSort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= $currentSort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        </select>
                    </form>
                </div>
            </div>

            <?php if ($searchKeyword): ?>
                <div class="mb-4" data-aos="fade-in">
                    <h5 class="text-muted">
                        Kết quả tìm kiếm cho: <span class="text-success">"<?= htmlspecialchars($searchKeyword) ?>"</span>
                        <a href="?" class="ms-2 btn btn-sm btn-light rounded-pill">Xóa tìm kiếm</a>
                    </h5>
                </div>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
                            <div class="product-card h-100 bg-white border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="position-relative">
                                    <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>">
                                        <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>"
                                            alt="<?= $product['name'] ?>"
                                            class="w-100 object-fit-cover"
                                            style="height: 240px;">
                                    </a>
                                </div>

                                <div class="p-3 text-center">
                                    <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;"><?= $product['category'] ?></span>
                                    <h3 class="fs-6 mt-1 mb-2">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="text-decoration-none fw-bold" style="color: var(--green-900);">
                                            <?= $product['name'] ?>
                                        </a>
                                    </h3>

                                    <div class="fw-bold mb-3" style="color: var(--green-700); font-size: 1.1rem;">
                                        <?= isset($product['price']) ? number_format($product['price'], 0, ',', '.') . 'đ' : 'Liên hệ' ?>
                                    </div>

                                    <div class="d-grid">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                            <i class="fa-solid fa-eye me-1"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-5" data-aos="fade-up">
                        <nav aria-label="Điều hướng phân trang">
                            <ul class="pagination pagination-modern mb-0">
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage - 1]) ?>" aria-label="Trang trước">
                                        <i class="fa-solid fa-chevron-left fa-sm"></i>
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage + 1]) ?>" aria-label="Trang sau">
                                        <i class="fa-solid fa-chevron-right fa-sm"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <img src="<?= BASE_URL ?>/file/render?path=uploads/images/shop-search.png" width="100" alt="Not found" class="opacity-50 mb-3">
                    <h3 class="text-muted"><?= e(content_value('shop.empty_title', 'Không tìm thấy cây nào phù hợp')) ?></h3>
                    <p><?= e(content_value('shop.empty_text', 'Vui lòng thử từ khóa khác hoặc xóa bộ lọc.')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .pagination-rounded .page-link {
        border-radius: 50% !important;
        margin: 0 3px;
        border: none;
        color: #198754;
    }

    .pagination-rounded .page-item.active .page-link {
        background-color: #198754;
        color: white;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.location.search) {
            const productList = document.getElementById('product-list');
            if (productList) {
                const headerOffset = 0;
                const elementPosition = productList.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                // setTimeout nhẹ để đảm bảo DOM đã render xong hoàn toàn
                setTimeout(() => {
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: "smooth"
                    });
                }, 100);
            }
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
<?php

/**
 * File: footer.php
 * Chuc nang: Tao phan cuoi trang dung chung cho website.
 * Location: app/Views/partials/footer.php
 */

// Đặt giá trị mặc định cho toàn bộ thông tin footer
$c_name    = content_value('company.name', 'Plantify Co');
$c_tagline = content_value('company.tagline', 'Mang thiên nhiên vào không gian của bạn');
$c_phone   = content_value('company.phone', '0908 246 135');
$c_email   = content_value('company.email', 'info@plantify.com');
$c_hours   = content_value('company.hours', '8:00 - 17:00');
$c_address = content_value('company.address', '268, Lý Thường Kiệt, Phường 14, Quận 10, TP. Hồ Chí Minh');
?>
</main>
<footer class="site-footer pt-5 pb-4">
    <div class="container">
        <div class="row g-3 g-lg-4">

            <!-- Logo & Mô tả công ty -->
            <div class="col-12 col-lg-3 text-center text-lg-start mb-4 mb-lg-0">
                <!-- Logo -->
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-3 text-decoration-none"
                    href="<?= BASE_URL ?>">
                    <span class="brand-mark bg-white text-success"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text text-white"><?php echo e($companyName); ?></span> 
                </a>
                <!-- Mô tả công ty -->
                <p class="footer-text opacity-75 mb-3" style="font-size: 0.95rem;">
                    <?php echo e(content_value('footer.description', 'Chúng tôi mang cây xanh vào không gian sống và làm việc bằng giải pháp tinh gọn, bền vững.')); ?>
                </p>
                <!-- Liên kết mạng xã hội -->
                <div class="social-links d-flex gap-3 justify-content-center justify-content-lg-start">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Điều hướng -->
            <div class="col-6 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">
                    <?php echo e(content_value('footer.nav_title', 'Điều hướng')); ?></h5>
                <ul class="footer-links list-unstyled d-flex flex-column gap-2 mb-0" style="font-size: 0.9rem;">
                    <li><a href="<?= BASE_URL ?>/shop">Cửa hàng</a></li>
                    <li><a href="<?= BASE_URL ?>/news">Tin tức</a></li>
                    <li><a href="<?= BASE_URL ?>/faq"><?php echo e(content_value('nav.faq', 'FAQ')); ?></a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="col-6 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">
                    <?php echo e(content_value('footer.info_title', 'Thông tin')); ?></h5>
                <ul class="footer-list list-unstyled d-flex flex-column gap-2 mb-0" style="font-size: 0.9rem;">
                    <li><i class="fa-solid fa-location-dot"></i>
                        <?php echo e(content_value('company.address', '123 Đường Cây Xanh, TP HCM')); ?></li>
                    <li><i class="fa-solid fa-phone"></i>
                        <?php echo e(content_value('company.phone', '0908 246 135')); ?></li>
                    <li><i class="fa-solid fa-envelope"></i>
                        <?php echo e(content_value('company.email', 'info@plantify.com')); ?></li>
                </ul>
            </div>

            <!-- Giờ mở cửa -->
            <div class="col-12 col-md-4 col-lg-3">
                <h5 class="footer-title text-success mb-3 fs-6 fw-bold text-uppercase">Giờ mở cửa</h5>
                <p class="small text-white opacity-75 mb-0"><i
                        class="fa-solid fa-clock me-2"></i><?php echo e(content_value('company.hours', 'Thứ 2 - Thứ 7: 08:00 - 18:00')); ?>
                </p>
            </div>

        </div>
    </div>
</footer>


<!-- // Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
<script src="<?php echo asset('assets/js/main.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        once: true,
        offset: 50
    });
});
</script>
</body>

</html>
<?php

/**
 * File: header.php
 * Chuc nang: Tao phan dau trang dung chung cho website.
 * Location: app/Views/partials/header.php
 */


$companyName = $company['name'] ?? 'Plantify Co';

$pageTitle = $pageTitle ?? $companyName;
$pageDescription = $pageDescription ?? content_value('site.default_description', 'Website công ty cây cảnh, cây xanh và decor thiên nhiên cho văn phòng, showroom.');
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}
$fullname = isset($user['fullname']) ? $user['fullname'] : 'Khách';
$avatar = !empty($user['avatar'])
    ? BASE_URL . '/file/render?path=' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullname);
?>
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/leaf-solid-full.svg">
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="keywords" content="cây cảnh, cây xanh, decor cây xanh, cây nội thất, thiết kế cảnh quan">
    <meta name="author" content="<?php echo e($companyName); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
    <?php foreach ($extraCss as $cssFile): ?>
    <?php $cssPath = strpos($cssFile, 'http') === 0 ? $cssFile : BASE_URL . '/' . ltrim($cssFile, '/'); ?>
    <link href="<?= $cssPath ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <!-- Header -->
    <header class="site-header">
        <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-white shadow-sm">
            <div class="container">

                <!-- Brand -->
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>"
                    aria-label="<?php echo e($companyName); ?>">
                    <span class="brand-mark"><i class="fa-solid fa-leaf"></i></span>
                    <span class="brand-text"><?php echo e($companyName); ?></span>
                </a>

                <!-- Navbar Toggler -->
                <div class="d-flex align-items-center gap-3 ms-auto order-lg-3">
                    <!-- Cart -->
                    <a href="<?= BASE_URL ?>/cart"
                        class="btn btn-light position-relative rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; color: var(--green-900); overflow: visible !important;">

                        <i class="fa-solid fa-cart-shopping"></i>

                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem; z-index: 1000; min-width: 18px; height: 18px; padding: 4px; line-height: 10px;">
                            <?= $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Account -->
                    <?php if (!empty($user)): ?>
                    <div class="dropdown">
                        <a href="#" class="text-decoration-none text-dark fw-bold d-flex align-items-center gap-2"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($user['avatar'])): ?>
                            <!-- Hiển thị Avatar từ DB -->
                            <img src="<?= $avatar ?>" class="rounded-circle object-fit-cover shadow-sm"
                                style="width: 32px; height: 32px; border: 1px solid #ddd;">
                            <?php else: ?>
                            <!-- Hiển thị Icon nếu chưa có Avatar -->
                            <span class="brand-mark bg-light text-success"
                                style="width: 32px; height: 32px; font-size: 0.9rem;">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <?php endif; ?>
                            <span
                                class="d-none d-md-inline"><?= htmlspecialchars($user['fullname'] ?? 'Tài khoản') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2"
                            style="border-radius: 12px;">
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/dashboard"><i
                                        class="fa-solid fa-chart-simple text-success me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/dashboard/orders"><i
                                        class="fa-solid fa-box text-success me-2"></i> Đơn hàng của tôi</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/auth/logout"><i
                                        class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>

                    <!-- Auth Buttons -->
                    <?php else: ?>
                    <div class="d-none d-md-flex gap-2">
                        <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Nhập</a>
                        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-success fw-bold px-3"
                            style="border-radius: 8px;">Đăng Ký</a>
                    </div>

                    <!-- Auth Button (Mobile) -->
                    <a href="<?= BASE_URL ?>/auth" class="text-dark d-md-none text-decoration-none">
                        <i class="fa-solid fa-circle-user fs-4 text-success"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Navbar Toggler -->
                <button class="navbar-toggler ms-2 order-lg-4" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Main Navigation -->
                <div class="collapse navbar-collapse order-lg-2" id="mainNavbar">
                    <ul class="navbar-nav mx-auto align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page(''); ?>" href="<?= BASE_URL ?>">Trang Chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('shop'); ?>" href="<?= BASE_URL ?>/shop">Cửa
                                Hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('news'); ?>" href="<?= BASE_URL ?>/news">Tin
                                Tức</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo is_active_page('faq'); ?>"
                                href="<?= BASE_URL ?>/faq"><?php echo e(content_value('nav.faq', 'FAQ')); ?></a>
                        </li>
                    </ul>



                </div>

            </div>
        </nav>
    </header>
    <main class="site-main">