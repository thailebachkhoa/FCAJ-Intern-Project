<?php
// ============================================================
// app/Views/admin/page_news.php
// ============================================================
?>
<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản tĩnh trên trang danh sách tin tức.',
]);
 
$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản tĩnh đang hiển thị.';
$previewUrl = BASE_URL . '/news';
 
require __DIR__ . '/includes/page_editor_form.php';
 
admin_layout_end();