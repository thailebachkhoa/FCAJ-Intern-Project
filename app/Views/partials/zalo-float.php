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
    right: clamp(14px, 3.2vw, 26px);
    bottom: clamp(20px, 4vw, 36px);   /* đã hạ thấp xuống */
    z-index: 1090;
}
.zalo-float-btn {
    width: clamp(58px, 7vw, 80px);
    height: clamp(58px, 7vw, 80px);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4a7fe8, #2655c7);
    box-shadow: 0 16px 34px rgba(18, 56, 42, 0.25);
    text-decoration: none;
    transform-origin: center;
    animation: zaloShake 2.2s ease-in-out infinite;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.zalo-float-btn:hover {
    animation-play-state: paused;
    box-shadow: 0 20px 42px rgba(18, 56, 42, 0.35);
    transform: scale(1.05);
}
.zalo-float-btn span {
    color: #ffffff;
    font-weight: 800;
    font-size: clamp(13px, 2vw, 18px);
    letter-spacing: 0.3px;
}
@keyframes zaloShake {
    0%, 76%, 100% { transform: rotate(0deg) scale(1); }
    78%  { transform: rotate(-16deg) scale(1.06); }
    80%  { transform: rotate(16deg) scale(1.08); }
    82%  { transform: rotate(-14deg) scale(1.1); }
    84%  { transform: rotate(14deg) scale(1.1); }
    86%  { transform: rotate(-10deg) scale(1.06); }
    88%  { transform: rotate(10deg) scale(1.04); }
    90%  { transform: rotate(-6deg) scale(1.02); }
    92%  { transform: rotate(6deg) scale(1); }
    94%  { transform: rotate(0deg) scale(1); }
}

/* Đảm bảo không tràn khung trên màn hình cực nhỏ (≤360px) */
@media (max-width: 360px) {
    .zalo-float { right: 10px; bottom: 80px; }
    .zalo-float-btn { width: 54px; height: 54px; }
    .zalo-float-btn span { font-size: 12px; }
}
</style>
<div class="zalo-float">
    <a href="<?= e($contact_zalo) ?>" class="zalo-float-btn" target="_blank" rel="noopener" aria-label="Chat qua Zalo">
        <span>Zalo</span>
    </a>
</div>