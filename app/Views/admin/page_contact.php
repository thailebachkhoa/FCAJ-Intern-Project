<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản tĩnh trên trang Contact. Thông tin địa chỉ, SĐT, email lấy từ mục Công ty.',
]);
 
$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản tĩnh.';
$previewUrl = BASE_URL . '/contact';// Ghi chú: SĐT, địa chỉ, email kéo từ nhóm Công ty — chỉnh tại Trang giới thiệu.
?>
<div class="alert alert-info d-flex gap-2 align-items-start mb-4">
    <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>
    <div>
        Địa chỉ, số điện thoại, email và giờ làm việc được kéo từ nhóm <strong>Công ty</strong>.
        Chỉnh sửa tại
        <a href="<?= BASE_URL ?>/admin/pages" class="alert-link">Trang giới thiệu → Nội dung dùng chung</a>.
    </div>
</div>

<?php
require __DIR__ . '/includes/page_editor_form.php';
 
admin_layout_end();