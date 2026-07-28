<?php

/**
 * File: app/Views/auth/totp.php
 * Chức năng: Bước xác thực 2 lớp (TOTP) bắt buộc cho Admin,
 * sau khi đã đăng nhập Google (hoặc username/password) thành công.
 */
$pageTitle = 'Xác thực 2 bước | Plantify Co';
require BASE_PATH . '/app/Views/partials/header.php';

$setup = $setup ?? false;
$qrImg = $setup && !empty($qrUri)
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrUri)
    : null;
?>

<main class="site-main page-main bg-soft" style="min-height: calc(100vh - 76px); display: flex; align-items: center; padding: 40px 0; margin-top:50px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card border-0 shadow-lg" style="border-radius: 24px;">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="brand-mark mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <h2 style="color: var(--green-900); font-weight: 820;">Xác thực 2 bước</h2>
                            <p class="text-muted mb-0">
                                Tài khoản Admin bắt buộc dùng Google Authenticator.
                            </p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($setup): ?>
                            <div class="mb-4">
                                <p class="fw-bold mb-2" style="color: var(--stone-700);">Bước 1 · Quét mã QR</p>
                                <p class="text-muted small mb-3">
                                    Mở app Google Authenticator (hoặc Authy, Microsoft Authenticator) →
                                    chọn "Thêm tài khoản" → quét mã QR bên dưới.
                                </p>
                                <?php if ($qrImg): ?>
                                    <div class="text-center mb-3">
                                        <img src="<?= htmlspecialchars($qrImg) ?>" alt="QR code thiết lập Google Authenticator" width="220" height="220" style="border-radius: 12px; border: 1px solid #eee;">
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted small mb-1">Không quét được QR? Nhập tay mã này vào app:</p>
                                <div class="bg-light p-2 text-center mb-1" style="border-radius: 10px; letter-spacing: 2px; font-family: monospace; word-break: break-all;">
                                    <?= htmlspecialchars($secret ?? '') ?>
                                </div>
                            </div>
                            <p class="fw-bold mb-2" style="color: var(--stone-700);">Bước 2 · Nhập mã 6 số hiện trên app</p>
                        <?php else: ?>
                            <p class="fw-bold mb-2" style="color: var(--stone-700);">Nhập mã 6 số từ Google Authenticator</p>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>/auth/totp" method="POST" novalidate>
                            <?= csrf_field() ?>
                            <div class="mb-4">
                                <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code"
                                    maxlength="6" pattern="\d{6}"
                                    class="form-control bg-light text-center fw-bold"
                                    style="letter-spacing: 8px; font-size: 1.5rem; height: 60px;"
                                    placeholder="000000" required autofocus>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-bold" style="height: 52px; border-radius: 12px;">
                                    Xác nhận
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <a href="<?= BASE_URL ?>/auth/logout" class="text-muted small text-decoration-none">
                                <i class="fa-solid fa-arrow-left me-1"></i> Huỷ, quay lại đăng nhập
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>