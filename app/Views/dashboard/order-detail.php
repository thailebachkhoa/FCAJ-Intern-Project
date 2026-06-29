<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

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