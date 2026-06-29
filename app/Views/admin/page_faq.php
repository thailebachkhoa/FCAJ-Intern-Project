<?php
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Chỉnh sửa văn bản tĩnh hiển thị trên trang câu hỏi thường gặp.',
]);
 
$heading    = $pageTitle;
$subtitle   = 'Mở từng mục để chỉnh văn bản tĩnh.';
$previewUrl = BASE_URL . '/faq';
 
require __DIR__ . '/includes/page_editor_form.php';
 
admin_layout_end();