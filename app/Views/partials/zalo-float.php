<?php
/**
 * Partial: Floating Zalo button (nút Zalo nổi, hiệu ứng rung nhẹ)
 * File: app/Views/partials/zalo-float.php
 *
 * Cách dùng: include ngay trước </main> ở bất kỳ trang nào cần hiện nút.
 *   <?php require BASE_PATH . '/app/Views/partials/zalo-float.php'; ?>
 *
 * Nếu trang gọi đã khai báo sẵn biến $contact_zalo thì partial này dùng luôn,
 * không thì sẽ dùng giá trị mặc định bên dưới.
 */
$contact_zalo = $contact_zalo ?? 'https://zalo.me/0787309225';
?>
<style>
.zalo-float {
    position: fixed;
    right: 24px;
    bottom: 106px;
    z-index: 1090;
}
.zalo-float-btn {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4a7fe8, #2655c7);
    box-shadow: 0 16px 34px rgba(18, 56, 42, 0.22);
    text-decoration: none;
    transform-origin: center;
    animation: zaloShake 2.6s ease-in-out infinite;
    transition: box-shadow 0.2s ease;
}
.zalo-float-btn:hover {
    animation-play-state: paused;
    box-shadow: 0 20px 40px rgba(18, 56, 42, 0.3);
}
.zalo-float-btn span {
    color: #ffffff;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.3px;
}
@keyframes zaloShake {
    0%, 80%, 100% { transform: rotate(0deg); }
    82%  { transform: rotate(-8deg); }
    84%  { transform: rotate(8deg); }
    86%  { transform: rotate(-6deg); }
    88%  { transform: rotate(6deg); }
    90%  { transform: rotate(-3deg); }
    92%  { transform: rotate(0deg); }
}
@media (max-width: 575px) {
    .zalo-float { right: 14px; bottom: 84px; }
    .zalo-float-btn { width: 50px; height: 50px; }
}
</style>
<div class="zalo-float">
    <a href="<?= e($contact_zalo) ?>" class="zalo-float-btn" target="_blank" rel="noopener" aria-label="Chat qua Zalo">
        <span>Zalo</span>
    </a>
</div>