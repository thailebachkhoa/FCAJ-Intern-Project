<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>
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