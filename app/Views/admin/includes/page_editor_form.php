<?php

/**
 * File: app/Views/admin/includes/page_editor_form.php
 * Partial dùng chung cho 4 page-editor views.
 *
 * Biến cần truyền vào (extract từ view cha):
 *   $message  string
 *   $error    string
 *   $byKey    array   — kết quả Content::getByGroup()
 *   $sections array   — cấu trúc editor sections
 *   $previewUrl string — URL "Xem trang" (tuỳ chọn)
 *   $heading  string  — tiêu đề trang
 *   $subtitle string  — mô tả trang (tuỳ chọn)
 */
?>

<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" id="pageEditorForm">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($heading ?? '') ?></h4>
            <?php if (!empty($subtitle)): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?php if (!empty($previewUrl)): ?>
            <a class="btn btn-outline-success" href="<?= htmlspecialchars($previewUrl) ?>" target="_blank">
                <i class="fa-solid fa-eye me-2"></i>Xem trang
            </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success px-4">
                <i class="fa-solid fa-floppy-disk me-2"></i>Lưu thay đổi
            </button>
        </div>
    </div>

    <?php foreach ($sections as $i => $section): ?>
    <details class="pe-editor-section" <?= $i === 0 ? 'open' : '' ?>>
        <summary>
            <div class="pe-section-title">
                <div>
                    <strong><?= htmlspecialchars($section['title']) ?></strong>
                    <span class="d-block"><?= htmlspecialchars($section['desc'] ?? '') ?></span>
                </div>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </summary>
        <div class="pe-section-body">
            <div class="row">
                <?php foreach ($section['keys'] as $key):
                        if (empty($byKey[$key])) continue;
                        $row        = $byKey[$key];
                        $isTextarea = $row['input_type'] === 'textarea';
                        $colClass   = $isTextarea ? 'col-12' : 'col-lg-6';
                    ?>
                <div class="<?= $colClass ?> mb-3">
                    <label class="form-label" for="field_<?= htmlspecialchars($key) ?>">
                        <?= htmlspecialchars($row['label']) ?>
                        <span class="d-block small text-muted"><?= htmlspecialchars($key) ?></span>
                    </label>

                    <?php if ($isTextarea): ?>
                    <textarea id="field_<?= htmlspecialchars($key) ?>" class="form-control"
                        name="content[<?= htmlspecialchars($key) ?>]"
                        rows="3"><?= htmlspecialchars($row['content_value']) ?></textarea>
                    <?php else: ?>
                    <input id="field_<?= htmlspecialchars($key) ?>" class="form-control" type="text"
                        name="content[<?= htmlspecialchars($key) ?>]"
                        value="<?= htmlspecialchars($row['content_value']) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </details>
    <?php endforeach; ?>

    <div class="text-end mt-3 mb-5">
        <button type="submit" class="btn btn-success px-5">
            <i class="fa-solid fa-floppy-disk me-2"></i>Lưu tất cả thay đổi
        </button>
    </div>
</form>

<style>
.pe-editor-section {
    border: 1px solid #e5ece6;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    margin-bottom: 12px;
}

.pe-editor-section summary {
    cursor: pointer;
    list-style: none;
    padding: 14px 18px;
    background: #f7fbf7;
}

.pe-editor-section summary::-webkit-details-marker {
    display: none;
}

.pe-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.pe-section-title strong {
    color: #1d5f35;
    font-size: 15px;
}

.pe-section-title span {
    color: #748075;
    font-size: 13px;
}

.pe-section-title i {
    color: #198754;
    transition: transform .2s;
}

.pe-editor-section[open] .pe-section-title i {
    transform: rotate(180deg);
}

.pe-section-body {
    padding: 18px;
    border-top: 1px solid #e5ece6;
}
</style>