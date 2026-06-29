<?php
// ============================================================
// app/Views/admin/page_home.php
// ============================================================
?>
<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản hiển thị trên trang chủ website.',
]);

$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản đang hiển thị.';
$previewUrl = BASE_URL . '/';

require __DIR__ . '/includes/page_editor_form.php';

admin_layout_end();