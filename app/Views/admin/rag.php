<?php
/**
 * File: admin/rag.php
 * Chức năng: Quản lý nội dung RAG/RAG.txt để cập nhật nguồn thông tin cho bot.
 */

require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Dữ liệu bot | Plantify Admin';
$ragPath = realpath(STORAGE_PATH . '/rag');
$ragFile = $ragPath ? $ragPath . DIRECTORY_SEPARATOR . 'RAG.txt' : null;
$message = '';
$error = '';

if (!$ragPath || !$ragFile) {
    $error = 'Không tìm thấy thư mục RAG.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['rag_content'] ?? '';

    if (strlen($content) > 1024 * 1024) {
        $error = 'Nội dung RAG.txt không được vượt quá 1MB.';
    } elseif (file_put_contents($ragFile, $content, LOCK_EX) === false) {
        $error = 'Không thể lưu RAG.txt. Hãy kiểm tra quyền ghi của thư mục RAG.';
    } else {
        $message = 'Đã cập nhật RAG.txt cho bot.';
    }
}

$ragContent = '';
if ($ragFile && is_file($ragFile)) {
    $ragContent = file_get_contents($ragFile);
    if ($ragContent === false) {
        $ragContent = '';
        $error = $error ?: 'Không thể đọc RAG.txt.';
    }
} elseif ($ragFile && !$error) {
    $error = 'Không tìm thấy file RAG.txt.';
}

clearstatcache(true, $ragFile ?: '');
$fileSize = ($ragFile && is_file($ragFile)) ? filesize($ragFile) : 0;
$updatedAt = 'Chưa có';
if ($ragFile && is_file($ragFile)) {
    $updatedAtDate = new DateTime('@' . filemtime($ragFile));
    $updatedAtDate->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    $updatedAt = $updatedAtDate->format('d/m/Y H:i:s');
}
$lineCount = $ragContent === '' ? 0 : substr_count($ragContent, "\n") + 1;

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Dữ liệu huấn luyện bot',
    'subtitle' => 'Sửa nội dung trong RAG.txt để cập nhật kiến thức mà trợ lý AI dùng khi trả lời khách hàng.',
]);
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

<div class="row">
    <div class="col-xl-8">
        <form method="post" class="card">
            <div class="card-body">
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="header-title mb-1">Nội dung RAG.txt</h4>
                        <p class="admin-muted mb-0">Nên viết theo từng mục rõ ràng: dịch vụ, sản phẩm, quy trình, FAQ, liên hệ.</p>
                    </div>
                    <button type="submit" class="btn btn-success mt-3 mt-sm-0">Lưu dữ liệu bot</button>
                </div>
                <textarea class="form-control admin-textarea-large" name="rag_content" spellcheck="false"><?php echo e($ragContent); ?></textarea>
            </div>
        </form>
    </div>
    <div class="col-xl-4 mt-lg-30 mt-md-30 mt-xs-30">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Thông tin file</h4>
                <div class="admin-meta-list mt-4">
                    <div>
                        <span>Cập nhật lần cuối</span>
                        <strong><?php echo e($updatedAt); ?></strong>
                    </div>
                    <div>
                        <span>Dung lượng</span>
                        <strong><?php echo e(number_format((float) $fileSize / 1024, 2)); ?> KB</strong>
                    </div>
                    <div>
                        <span>Số dòng</span>
                        <strong><?php echo e($lineCount); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h4 class="header-title">Gợi ý cập nhật</h4>
                <p class="admin-muted mb-0">Sau khi lưu, nếu server RAG đang giữ dữ liệu trong bộ nhớ, hãy khởi động lại process chatbot để bot đọc phiên bản mới của file.</p>
            </div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>
