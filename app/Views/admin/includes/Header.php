<?php

/**
 * File: admin/includes/Header.php
 * Chuc nang: Header/topbar admin theo SRTDash đã fix Responsive.
 */

if (!function_exists('admin_render_header')) {
    function admin_render_header($pageTitle = 'Admin')
    {
?>
<div class="header-area bg-white py-3 shadow-sm sticky-top" style="z-index:100;">
    <div class="container-fluid px-0">
        <div class="row align-items-center m-0 justify-content-between">
            <div class="col-8 col-md-6 d-flex align-items-center gap-3">
                <div class="nav-btn mb-0 mt-0">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="admin-header-title d-none d-sm-block text-truncate mb-0 mt-0" style="max-width: 80%;">
                    <span class="d-none d-md-inline text-muted me-1">Plantify Admin /</span>
                    <strong class="text-dark"><?php echo e($pageTitle); ?></strong>
                </div>
            </div>

            <div class="col-4 col-md-6 d-flex justify-content-end align-items-center">
                <ul class="notification-area d-flex align-items-center justify-content-end list-unstyled mb-0 gap-3"
                    style="padding: 0; margin: 0;">
                    <li id="full-view" class="d-none d-md-block"><i class="ti-fullscreen fs-5"></i></li>
                    <li id="full-view-exit" class="d-none d-md-block"><i class="ti-zoom-out fs-5"></i></li>

                    <li>
                        <a href="<?= BASE_URL ?>" title="Xem website" aria-label="Xem website"
                            class="text-dark text-decoration-none">
                            <i class="ti-home" style="font-size: 24px;"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
    }
}
?>