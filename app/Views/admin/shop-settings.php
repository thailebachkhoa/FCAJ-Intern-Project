<?php

/**
 * File: admin/shop_settings.php
 */
require_once __DIR__ . '/includes/AdminLayout.php';

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Cấu hình giao diện Cửa hàng',
    'subtitle' => 'Chỉnh sửa các tiêu đề, nút bấm và nhãn hiển thị trên trang bán hàng.'
]);
?>

<div class="row">
    <div class="col-12 mt-4">
        <?php if (isset($_SESSION['admin_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $_SESSION['admin_success'];
                                                        unset($_SESSION['admin_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> <?= $_SESSION['admin_error'];
                                                                unset($_SESSION['admin_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <?php foreach ($settingsByGroup as $groupName => $items): ?>
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 text-success fw-bold"><i class="ti-settings me-2"></i> <?= htmlspecialchars($groupName) ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-dark">
                                        <?= htmlspecialchars($item['label']) ?>
                                        <small class="text-muted fw-normal d-block" style="font-size: 0.7rem;">Key: <?= $item['content_key'] ?></small>
                                    </label>

                                    <?php if ($item['input_type'] === 'textarea'): ?>
                                        <textarea name="content[<?= $item['content_key'] ?>]" class="form-control" rows="3"><?= htmlspecialchars($item['content_value']) ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="content[<?= $item['content_key'] ?>]" class="form-control" value="<?= htmlspecialchars($item['content_value']) ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="settings-actions mb-5 text-end">
                <button type="submit" name="update_shop_content" class="btn btn-success btn-lg px-5 rounded-pill shadow">
                    <i class="fa-solid fa-save me-2"></i> Lưu tất cả thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>