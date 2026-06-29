<?php

/**
 * File: admin/includes/ContentWrapper.php
 * Chuc nang: Page title va content wrapper dung chung cho admin.
 */

if (!function_exists('admin_render_content_start')) {
    function admin_render_content_start($title, $subtitle = '', $actionHtml = '')
    {
?>
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-7">
            <div class="breadcrumbs-area clearfix">
                <h1 class="page-title float-start"><?php echo e($title); ?></h1>
                <ul class="breadcrumbs float-start">
                    <li><a href=".index">Admin</a></li>
                    <li><span><?php echo e($title); ?></span></li>
                </ul>
            </div>
            <?php if ($subtitle): ?>
            <p class="admin-page-subtitle"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($actionHtml): ?>
        <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
            <?php echo $actionHtml; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="main-content-inner" id="main-content">
    <?php
    }
}

if (!function_exists('admin_render_content_end')) {
    function admin_render_content_end()
    {
        echo '</div>';
    }
}