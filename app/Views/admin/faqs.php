<?php

/**
 * File: admin/faqs.php
 * Chuc nang: Quan ly FAQ (xem, them, sua, xoa).
 */

require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Quan ly FAQ | Plantify Admin';
$db = Database::getInstance();
$message = '';
$error = '';

if (!$db) {
    $error = 'Chưa kết nối được Database. Hãy import database/migrations/schema.sql và kiểm tra lại.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));

    if ($action === 'reorder') {
        $orderedIds = json_decode($_POST['ordered_ids'] ?? '[]', true);
        if (is_array($orderedIds) && count($orderedIds) > 0) {
            foreach (array_values($orderedIds) as $index => $faqId) {
                $db->query('UPDATE faqs SET sort_order = :sort_order WHERE id = :id');
                $db->bind(':sort_order', $index + 1);
                $db->bind(':id', (int) $faqId);
                $db->execute();
            }
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Thứ tự FAQ đã được cập nhật.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Thứ tự FAQ đã được cập nhật.';
        } else {
            $error = 'Không có dữ liệu sắp xếp FAQ.';
        }
    } elseif (($action === 'add' || $action === 'edit') && mb_strlen($question) > 255) {
        $error = 'Câu hỏi không được vượt quá 255 ký tự.';
    } elseif (($action === 'add' || $action === 'edit') && mb_strlen($answer) > 5000) {
        $error = 'Câu trả lời không được vượt quá 5000 ký tự.';
    } elseif ($action === 'add' && $question && $answer) {
        $db->query('INSERT INTO faqs (question, answer, sort_order) VALUES (:question, :answer, :sort_order)');
        $db->bind(':question', $question);
        $db->bind(':answer', $answer);
        $db->bind(':sort_order', $sortOrder);
        $db->execute();
        $message = 'FAQ đã được thêm.';
    } elseif ($action === 'edit' && $id && $question && $answer) {
        $db->query('UPDATE faqs SET question = :question, answer = :answer, sort_order = :sort_order WHERE id = :id');
        $db->bind(':question', $question);
        $db->bind(':answer', $answer);
        $db->bind(':sort_order', $sortOrder);
        $db->bind(':id', $id);
        $db->execute();
        $message = 'FAQ đã được cập nhật.';
    } elseif ($action === 'delete' && $id) {
        $db->query('DELETE FROM faqs WHERE id = :id');
        $db->bind(':id', $id);
        $db->execute();
        $message = 'FAQ đã được xóa.';
    } else {
        $error = 'Vui lòng nhập đầy đủ câu hỏi và câu trả lời.';
    }
}

$db->query('SELECT * FROM faqs ORDER BY sort_order ASC, id DESC');
$faqs = $db->resultSet() ?: [];


admin_layout_start([
    'pageTitle' => 'Quản lý FAQ',
    'heading' => 'Quản lý Câu hỏi thường gặp',
    'subtitle' => 'Cập nhật danh sách câu hỏi và câu trả lời hiển thị trên website.',
    'actionHtml' => '<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa-solid fa-plus me-2"></i>Thêm FAQ</button>',
    'extraHead' => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@10/dist/style.min.css">',
]);
?>

<!-- Thông báo -->
<?php if (!empty($message)): ?><div class="alert alert-success rounded-3"><i
        class="fa-solid fa-circle-check me-2"></i><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger rounded-3"><i
        class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Danh sách FAQ</h4>
        <span class="text-muted small"><i class="fa-solid fa-arrows-up-down me-1"></i>Kéo thả để sắp xếp</span>
    </div>

    <div class="table-responsive">
        <table id="faqTable" class="table table-hover admin-table">
            <thead>
                <tr>
                    <th width="10%">Thứ tự</th>
                    <th width="30%">Câu hỏi</th>
                    <th width="40%">Câu trả lời</th>
                    <th width="20%" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody id="faqSortTable">
                <?php foreach ($faqs as $faq): ?>
                <tr class="faq-sort-row" draggable="true" data-id="<?= e($faq['id']) ?>">
                    <td class="text-center">
                        <span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>
                        <span class="faq-order-number fw-bold"><?= e($faq['sort_order']) ?></span>
                    </td>
                    <td class="fw-bold"><?= e($faq['question']) ?></td>
                    <td class="text-muted"><?= e(mb_strimwidth($faq['answer'], 0, 100, '...')) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" type="button"
                            data-faq='<?= htmlspecialchars(json_encode($faq, JSON_UNESCAPED_UNICODE)) ?>'
                            onclick="editFaq(this)">Sửa</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($faq['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Xóa FAQ này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sort-save-state mt-3" id="faqSortState" hidden>
        <div class="sort-spinner me-2"></div> <strong>Đang lưu thứ tự...</strong>
    </div>
</div>

<!-- Modal Thêm -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title">Thêm FAQ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Câu hỏi</label><input type="text" name="question"
                        class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Câu trả lời</label><textarea name="answer"
                        class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Lưu FAQ</button></div>
        </form>
    </div>
</div>

<!-- Modal Sửa -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="sort_order" id="editSortOrder">
            <div class="modal-header">
                <h5 class="modal-title">Sửa FAQ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Câu hỏi</label><input type="text" name="question"
                        id="editQuestion" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Câu trả lời</label><textarea name="answer" id="editAnswer"
                        class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Cập nhật</button></div>
        </form>
    </div>
</div>

<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10/dist/umd/simple-datatables.min.js"></script>
<script>
function editFaq(button) {
    const faq = JSON.parse(button.dataset.faq);
    document.getElementById("editId").value = faq.id;
    document.getElementById("editQuestion").value = faq.question;
    document.getElementById("editAnswer").value = faq.answer;
    document.getElementById("editSortOrder").value = faq.sort_order || 0;
    new bootstrap.Modal(document.getElementById("editModal")).show();
}

document.addEventListener("DOMContentLoaded", function () {
    const faqTable = document.getElementById("faqTable");
    if (faqTable && window.simpleDatatables) {
        new simpleDatatables.DataTable(faqTable, { perPage: 10 });
    }
});

const faqSortTable = document.getElementById("faqSortTable");
const faqSortState = document.getElementById("faqSortState");
let draggedRow = null;
let saveTimer = null;

function updateFaqOrderNumbers() {
    Array.from(faqSortTable.querySelectorAll(".faq-sort-row")).forEach((row, index) => {
        row.querySelector(".faq-order-number").textContent = index + 1;
    });
}

function saveFaqOrder() {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(() => {
        const orderedIds = Array.from(faqSortTable.querySelectorAll(".faq-sort-row")).map(row => row.dataset.id);
        const form = new FormData();
        form.append("action", "reorder");
        form.append("ordered_ids", JSON.stringify(orderedIds));
        faqSortState.hidden = false;

        fetch("' . BASE_URL . '/admin/faqs", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: form
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || "Khong luu duoc thu tu FAQ.");
                }
                faqSortState.querySelector("strong").textContent = "Da luu thu tu moi";
                window.setTimeout(() => {
                    faqSortState.hidden = true;
                    faqSortState.querySelector("strong").textContent = "Dang luu thu tu...";
                }, 900);
            })
            .catch(() => {
                faqSortState.querySelector("strong").textContent = "Luu thu tu that bai";
            });
    }, 250);
}

if (faqSortTable) {
    faqSortTable.addEventListener("dragstart", event => {
        draggedRow = event.target.closest(".faq-sort-row");
        if (!draggedRow) return;
        draggedRow.classList.add("is-dragging");
        event.dataTransfer.effectAllowed = "move";
    });

    faqSortTable.addEventListener("dragover", event => {
        event.preventDefault();
        const targetRow = event.target.closest(".faq-sort-row");
        if (!targetRow || targetRow === draggedRow) return;
        const rect = targetRow.getBoundingClientRect();
        const shouldInsertAfter = event.clientY > rect.top + rect.height / 2;
        faqSortTable.insertBefore(draggedRow, shouldInsertAfter ? targetRow.nextSibling : targetRow);
    });

    faqSortTable.addEventListener("dragend", () => {
        if (!draggedRow) return;
        draggedRow.classList.remove("is-dragging");
        draggedRow = null;
        updateFaqOrderNumbers();
        saveFaqOrder();
    });
}
</script>';

admin_layout_end($extraScripts);
?>