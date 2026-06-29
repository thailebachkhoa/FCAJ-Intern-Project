<?php
$isEdit     = ($mode === 'edit');
$pageTitle  = $isEdit ? 'Sửa bài viết' : 'Thêm bài viết mới';
$breadcrumb = 'Tin tức';
$activePage = 'news';
require_once __DIR__ . '/includes/AdminLayout.php';
admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading'   => $pageTitle,
    'subtitle'  => 'Soạn thảo nội dung bài viết.'
]);

// Populate form values from $formData (either POST data or existing record)
$f = $formData ?? [];
$fTitle     = $f['title']             ?? '';
$fSlug      = $f['slug']              ?? '';
$fShortDesc = $f['short_description'] ?? '';
$fContent   = $f['content']           ?? '';
$fTags      = $f['tags']              ?? '';
$fSeoDesc   = $f['seo_desc']          ?? '';
$fAuthor    = $f['author']            ?? 'Admin';
$fStatus    = $f['status']            ?? 'draft';
$fThumb     = $f['thumbnail']         ?? '';
?>

<!-- Back button -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
    </a>
</div>

<!-- ===== ERROR MESSAGE ===== -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FORM ===== -->
<form action="<?= BASE_URL ?>/admin/<?= $isEdit ? 'news_edit/' . $news['id'] : 'news_create' ?>"
    method="POST"
    enctype="multipart/form-data"
    id="newsForm"
    novalidate>

    <div class="row g-4">

        <!-- LEFT COLUMN: main fields -->
        <div class="col-lg-8">

            <!-- Title -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">📝 Thông tin cơ bản</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="titleInput"
                            value="<?= htmlspecialchars($fTitle) ?>"
                            placeholder="Nhập tiêu đề hấp dẫn..." required>
                        <div id="titleError" class="invalid-feedback">Tiêu đề không được để trống và phải có ít nhất 5 ký tự.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug (URL) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="slug" id="slugInput"
                            value="<?= htmlspecialchars($fSlug) ?>"
                            placeholder="tu-dong-tao-tu-tieu-de">
                        <div class="form-text">Tự động tạo từ tiêu đề. Chỉ dùng chữ thường, số và dấu gạch ngang.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả ngắn <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="short_description" rows="3"
                            placeholder="Mô tả ngắn gọn, hấp dẫn người đọc (hiển thị trên trang danh sách)..."
                            required><?= htmlspecialchars($fShortDesc) ?></textarea>
                        <div id="shortDescError" class="invalid-feedback">Mô tả ngắn không được để trống.</div>
                    </div>

                </div>
            </div>

            <!-- Content -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">📄 Nội dung bài viết <span class="text-danger">*</span></div>
                <div class="card-body">
                    <textarea class="form-control font-monospace" name="content" id="contentInput" rows="18"
                        placeholder="Nhập nội dung bài viết (hỗ trợ HTML: <h2>, <p>, <strong>, <ul>, <ol>, <li>)..."
                        required><?= htmlspecialchars($fContent) ?></textarea>
                    <div class="form-text mt-1">Hỗ trợ thẻ HTML cơ bản: &lt;h2&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;</div>
                    <div id="contentError" class="text-danger small mt-1" style="display:none;">Nội dung không được để trống.</div>
                </div>
            </div>

        </div>
        <!-- END LEFT COLUMN -->

        <!-- RIGHT COLUMN: meta & publish -->
        <div class="col-lg-4">

            <!-- Publish settings -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🚀 Xuất bản</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="published" <?= $fStatus === 'published' ? 'selected' : '' ?>>✅ Đã đăng</option>
                            <option value="draft" <?= $fStatus === 'draft'     ? 'selected' : '' ?>>📝 Bản nháp</option>
                            <option value="hidden" <?= $fStatus === 'hidden'    ? 'selected' : '' ?>>🚫 Ẩn</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tác giả</label>
                        <input type="text" class="form-control" name="author"
                            value="<?= htmlspecialchars($fAuthor) ?>"
                            placeholder="Admin">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <?= $isEdit ? ' Lưu thay đổi' : ' Đăng bài viết' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/admin/news" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark"></i> Hủy
                        </a>
                    </div>

                </div>
            </div>

            <!-- Thumbnail -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🖼️ Ảnh đại diện</div>
                <div class="card-body">
                    <input type="file" class="form-control mb-2" name="thumbnail"
                        id="thumbnailInput" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text mb-2">JPG, JPEG, PNG, WEBP — tối đa 2MB</div>
                    <div id="imgPreview" class="img-preview-wrap">
                        <?php if (!empty($fThumb) && file_exists(__DIR__ . '/../../../../public/' . $fThumb)): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($fThumb) ?>"
                                id="previewImg" alt="Ảnh hiện tại" style="max-width:100%;border-radius:8px;">
                            <input type="hidden" name="existing_thumbnail" value="<?= htmlspecialchars($fThumb) ?>">
                        <?php else: ?>
                            <div id="previewImg" style="display:none;"><img src="" style="max-width:100%;border-radius:8px;" id="previewImgEl"></div>
                        <?php endif; ?>
                    </div>
                    <div id="fileError" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>

            <!-- Tags + SEO -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold">🏷️ Tags & SEO</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" class="form-control" name="tags"
                            value="<?= htmlspecialchars($fTags) ?>"
                            placeholder="phong thuy, cay canh, ...">
                        <div class="form-text">Phân cách bằng dấu phẩy</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Mô tả SEO</label>
                        <textarea class="form-control" name="seo_desc" rows="2"
                            placeholder="Mô tả ngắn cho công cụ tìm kiếm (tối đa 160 ký tự)..."
                            maxlength="255"><?= htmlspecialchars($fSeoDesc) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <!-- END RIGHT COLUMN -->

    </div>
</form>

<!-- ===== JS: auto-slug + preview + validation ===== -->
<script>
    // Auto-generate slug from title
    const titleInput = document.getElementById('titleInput');
    const slugInput = document.getElementById('slugInput');

    titleInput.addEventListener('input', function() {
        const title = this.value;
        let slug = title.toLowerCase();
        // Transliterate common Vietnamese chars
        const from = 'àáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ';
        const to = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiioooooooooooooooooouuuuuuuuuuuyyyyyд';
        // Simple replacement map for slug preview
        const map = {
            'à': 'a',
            'á': 'a',
            'ả': 'a',
            'ã': 'a',
            'ạ': 'a',
            'â': 'a',
            'ầ': 'a',
            'ấ': 'a',
            'ẩ': 'a',
            'ẫ': 'a',
            'ậ': 'a',
            'ă': 'a',
            'ằ': 'a',
            'ắ': 'a',
            'ẳ': 'a',
            'ẵ': 'a',
            'ặ': 'a',
            'è': 'e',
            'é': 'e',
            'ẻ': 'e',
            'ẽ': 'e',
            'ẹ': 'e',
            'ê': 'e',
            'ề': 'e',
            'ế': 'e',
            'ể': 'e',
            'ễ': 'e',
            'ệ': 'e',
            'ì': 'i',
            'í': 'i',
            'ỉ': 'i',
            'ĩ': 'i',
            'ị': 'i',
            'ò': 'o',
            'ó': 'o',
            'ỏ': 'o',
            'õ': 'o',
            'ọ': 'o',
            'ô': 'o',
            'ồ': 'o',
            'ố': 'o',
            'ổ': 'o',
            'ỗ': 'o',
            'ộ': 'o',
            'ơ': 'o',
            'ờ': 'o',
            'ớ': 'o',
            'ở': 'o',
            'ỡ': 'o',
            'ợ': 'o',
            'ù': 'u',
            'ú': 'u',
            'ủ': 'u',
            'ũ': 'u',
            'ụ': 'u',
            'ư': 'u',
            'ừ': 'u',
            'ứ': 'u',
            'ử': 'u',
            'ữ': 'u',
            'ự': 'u',
            'ỳ': 'y',
            'ý': 'y',
            'ỷ': 'y',
            'ỹ': 'y',
            'ỵ': 'y',
            'đ': 'd'
        };
        slug = slug.replace(/[^\u0000-\u007E]/g, c => map[c] || '');
        slug = slug.replace(/[^a-z0-9\s-]/g, '');
        slug = slug.replace(/[\s-]+/g, '-').replace(/^-|-$/g, '');
        slugInput.value = slug;
    });

    // Image preview
    document.getElementById('thumbnailInput').addEventListener('change', function() {
        const file = this.files[0];
        const errBox = document.getElementById('fileError');
        const preview = document.getElementById('previewImg');

        errBox.style.display = 'none';
        if (!file) return;

        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            errBox.textContent = 'Chỉ chấp nhận file ảnh: JPG, JPEG, PNG, WEBP!';
            errBox.style.display = 'block';
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            errBox.textContent = 'Ảnh không được vượt quá 2MB!';
            errBox.style.display = 'block';
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            // If existing img element
            let img = document.getElementById('previewImgEl');
            if (!img) {
                img = document.createElement('img');
                img.style.cssText = 'max-width:100%;border-radius:8px;';
                preview.appendChild(img);
            }
            img.src = e.target.result;
            preview.style.display = '';
        };
        reader.readAsDataURL(file);
    });

    // Form validation
    document.getElementById('newsForm').addEventListener('submit', function(e) {
        let ok = true;
        const title = document.getElementById('titleInput');
        const content = document.getElementById('contentInput');
        const titleErr = document.getElementById('titleError');
        const contentErr = document.getElementById('contentError');

        titleErr.style.display = 'none';
        title.classList.remove('is-invalid');
        contentErr.style.display = 'none';

        if (!title.value.trim() || title.value.trim().length < 5) {
            title.classList.add('is-invalid');
            ok = false;
        }
        if (!content.value.trim()) {
            contentErr.style.display = 'block';
            ok = false;
        }
        if (!ok) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...';
        }
    });
</script>

<?php
// Nếu bạn có script validation riêng, hãy bỏ vào biến này
$extraScripts = '<script> ... code JS của bạn ... </script>';
admin_layout_end($extraScripts);
?>