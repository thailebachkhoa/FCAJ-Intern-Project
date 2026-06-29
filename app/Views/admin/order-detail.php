<?php require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => 'Chi tiết Đơn hàng #' . $order['id']]); ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Sản phẩm đã đặt</h5>
            </div>
            <div class="card-body">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= strpos($item['image'], 'http') === 0 ? $item['image'] : BASE_URL . '/' . ltrim($item['image'], '/') ?>"
                            width="60"
                            class="rounded-3 me-3 border"
                            alt="<?= htmlspecialchars($item['name']) ?>"
                            style="object-fit: cover; height: 60px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?= $item['name'] ?></h6>
                            <small class="text-muted">SL: <?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', '.') ?>đ</small>
                        </div>
                        <div class="fw-bold"><?= number_format($item['quantity'] * $item['price'], 0, ',', '.') ?>đ</div>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="h5">Tổng cộng:</span>
                    <span class="h5 text-success fw-bold"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Trạng thái & Giao hàng</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/admin/orders/update_status/<?= $order['id'] ?>" method="POST">
                    <label class="form-label small fw-bold">Cập nhật trạng thái</label>
                    <select name="status" class="form-select mb-3 rounded-pill">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Đang đóng gói</option>
                        <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                    <button type="submit" class="btn btn-success w-100 rounded-pill">Lưu thay đổi</button>
                </form>
                <hr>
                <p class="mb-1"><strong>Người nhận:</strong> <?= $order['fullname'] ?></p>
                <p class="mb-1"><strong>SĐT:</strong> <?= $order['phone'] ?></p>
                <p class="mb-0"><strong>Địa chỉ:</strong> <?= $order['address'] ?></p>
            </div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>