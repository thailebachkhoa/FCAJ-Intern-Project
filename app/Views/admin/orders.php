<?php require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start(['pageTitle' => 'Quản lý Đơn hàng']); ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#ORD-<?= $order['id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= $order['fullname'] ?></div>
                                <small class="text-muted"><?= $order['phone'] ?></small>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td class="fw-bold text-success"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php
                                $badgeClass = [
                                    'pending' => 'bg-warning text-dark',
                                    'processing' => 'bg-info',
                                    'shipping' => 'bg-primary',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger'
                                ];
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass[$order['status']] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>/admin/order_detail/<?= $order['id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill">Chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>