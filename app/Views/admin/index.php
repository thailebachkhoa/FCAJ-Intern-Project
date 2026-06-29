<?php

/**
 * File: app/Views/admin/index.php
 * Chức năng: Tổng quan quản trị (Style gốc - Nội dung nâng cao)
 */

require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Tổng quan hệ thống';
$db = Database::getInstance();

// 1. LẤY DỮ LIỆU THỰC TẾ
$counts = ['users' => 0, 'products' => 0, 'orders' => 0, 'comments' => 0, 'contacts' => 0, 'pages' => 0];

try {
    $db->query("SELECT COUNT(*) as total FROM users");
    $counts['users'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM products");
    $counts['products'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM orders");
    $counts['orders'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM comments");
    $counts['comments'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM contacts WHERE is_read = 0");
    $counts['contacts'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT COUNT(*) as total FROM pages");
    $counts['pages'] = (int)($db->single()['total'] ?? 0);

    $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
    $recentOrders = $db->resultSet();
} catch (Exception $e) {
    $recentOrders = [];
}

$chartData = json_encode([
    'labels' => ['Người dùng', 'Sản phẩm', 'Đơn hàng', 'Bình luận', 'Liên hệ mới'],
    'values' => [$counts['users'], $counts['products'], $counts['orders'], $counts['comments'], $counts['contacts']]
]);

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Tổng quan hệ thống',
    'subtitle' => 'Dữ liệu trực tiếp từ Database'
]);
?>

<div class="row g-4 mb-4 mt-2">
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Người dùng</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-users fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['users'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Sản phẩm</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-leaf fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['products'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Đơn hàng</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-cart-shopping fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['orders'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold">Liên hệ mới</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-envelope fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-stone-900"><?= $counts['contacts'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 text-stone-900">Phân tích dữ liệu</h5>
                <div style="height: 320px;">
                    <canvas id="adminOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 text-stone-900">Tác vụ nhanh</h5>
                <div class="list-group list-group-flush gap-2">
                    <a href="<?= BASE_URL ?>/admin/products"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-plus text-success"></i>
                            <span class="fw-medium">Thêm sản phẩm</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/orders"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-truck text-primary"></i>
                            <span class="fw-medium">Quản lý đơn hàng</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/users"
                        class="list-group-item list-group-item-action border rounded-3 px-3 py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-user-gear text-warning"></i>
                            <span class="fw-medium">Thành viên</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4 mb-5">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="fw-bold mb-0 text-stone-900">Đơn hàng vừa đặt</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#ORD-<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['fullname']) ?></td>
                                <td class="text-success fw-bold">
                                    <?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                                <td><span
                                        class="badge rounded-pill bg-success bg-opacity-10 text-success"><?= $order['status'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>/admin/order_detail/<?= $order['id'] ?>"
                                        class="btn btn-sm btn-light border">Xem</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("adminOverviewChart").getContext("2d");
        var data = ' . $chartData . ';
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.labels,
                datasets: [{
                    label: "Số lượng",
                    data: data.values,
                    backgroundColor: [
                        "rgba(54, 162, 235, 0.7)",  // Blue (Người dùng)
                        "rgba(75, 192, 192, 0.7)",  // Green (Sản phẩm)
                        "rgba(153, 102, 255, 0.7)", // Purple (Đơn hàng)
                        "rgba(255, 159, 64, 0.7)",  // Orange (Bình luận)
                        "rgba(255, 99, 132, 0.7)"   // Red (Liên hệ mới)
                    ],
                    borderColor: [
                        "rgba(54, 162, 235, 1)",
                        "rgba(75, 192, 192, 1)",
                        "rgba(153, 102, 255, 1)",
                        "rgba(255, 159, 64, 1)",
                        "rgba(255, 99, 132, 1)"
                    ],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
</script>
';
admin_layout_end($extraScripts);
?>