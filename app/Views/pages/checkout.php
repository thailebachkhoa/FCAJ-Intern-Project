<!DOCTYPE html>
<html lang="vi">

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