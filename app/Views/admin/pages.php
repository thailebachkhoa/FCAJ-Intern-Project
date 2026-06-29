<?php

/**
 * File: admin/pages.php
 * Chức năng: Quản lý nội dung tĩnh, ảnh và video hero trang giới thiệu.
 */
require_once __DIR__ . '/includes/AdminLayout.php';

$pageTitle = 'Quản lý nội dung | Plantify Admin';
$db = Database::getInstance();
$message = '';
$error = '';

function admin_page_image_upload($fieldName, &$error)
{
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Upload hình ảnh thất bại. Vui lòng chọn lại file.';
        return '';
    }

    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $error = 'Hình ảnh vượt quá giới hạn 5MB.';
        return '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'Chỉ hỗ trợ định dạng JPG, PNG, WEBP hoặc GIF.';
        return '';
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($mime && !in_array($mime, $allowedMimes, true)) {
            $error = 'File upload không phải hình ảnh hợp lệ.';
            return '';
        }
    }

    $uploadDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pages';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        $error = 'Không tạo được thư mục public/assets/uploads/pages.';
        return '';
    }

    $fileName = 'about-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Không lưu được hình ảnh lên server.';
        return '';
    }

    return 'assets/uploads/pages/' . $fileName;
}

function admin_delete_old_about_image($relativePath)
{
    $relativePath = str_replace('\\', '/', (string) $relativePath);
    if (!preg_match('#^assets/uploads/pages/about-[a-zA-Z0-9_.-]+\.(jpe?g|png|webp|gif)$#i', $relativePath)) {
        return;
    }

    $baseDir = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pages');
    $targetPath = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if (!$baseDir || !$targetPath || strpos($targetPath, $baseDir . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }

    if (is_file($targetPath)) {
        @unlink($targetPath);
    }
}

if (!$db) {
    $error = 'Chưa kết nối được database.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_content') {
        $content = $_POST['content'] ?? [];
        if (!is_array($content)) {
            $error = 'Dữ liệu nội dung không hợp lệ.';
        } else {
            foreach ($content as $key => $value) {
                $key = (string) $key;
                $value = trim((string) $value);

                if (!preg_match('/^[a-z0-9_.-]+$/', $key) || mb_strlen($key) > 120) {
                    $error = 'Key nội dung không hợp lệ.';
                    break;
                }
                if (mb_strlen($value) > 5000) {
                    $error = 'Giá trị nội dung không được vượt quá 5000 ký tự.';
                    break;
                }

                $db->query('UPDATE site_content SET content_value = :value WHERE content_key = :key');
                $db->bind(':value', $value);
                $db->bind(':key', $key);
                $db->execute();
            }

            if (!$error) {
                $message = 'Nội dung website đã được cập nhật.';
            }
        }
    }

    if ($action === 'save_about_image') {
        $uploadedImage = admin_page_image_upload('image_file', $error);
        if (!$error && $uploadedImage) {
            $db->query("SELECT image FROM pages WHERE slug = 'about' LIMIT 1");
            $currentAboutPage = $db->single();
            $oldImage = $currentAboutPage['image'] ?? '';

            $db->query("INSERT INTO pages (slug, title, content, image)
                VALUES ('about', 'Giới thiệu Plantify Co', 'Nội dung đang được cập nhật.', :image)
                ON DUPLICATE KEY UPDATE image = :update_image");
            $db->bind(':image', $uploadedImage);
            $db->bind(':update_image', $uploadedImage);
            $db->execute();

            if ($oldImage && $oldImage !== $uploadedImage) {
                admin_delete_old_about_image($oldImage);
            }

            $message = 'Hình ảnh trang giới thiệu đã được cập nhật.';
        } elseif (!$error) {
            $error = 'Vui lòng chọn hình ảnh cần upload.';
        }
    }
}

$contentRows = [];
$groupedContent = [];
$contentByKey = [];
$otherGroupedContent = [];
$aboutPage = null;
$heroVideoAdmin = 'assets/videos/about/about-hero.m3u8';

if ($db) {
    try {
        $aboutDefaults = [
            ['about.meta_title', 'Trang giới thiệu', 'Meta title', 'text', 'Giới thiệu | Plantify Co'],
            ['about.meta_description', 'Trang giới thiệu', 'Meta description', 'textarea', 'Tìm hiểu Plantify Co, công ty thiết kế decor cây xanh.'],
            ['about.hero_video', 'Trang giới thiệu', 'Video nền đầu trang giới thiệu', 'text', 'assets/videos/about/about-hero.m3u8'],
            ['about.hero_video_label', 'Trang giới thiệu', 'Nhãn truy cập video hero', 'text', 'Video nền giới thiệu Plantify Co'],
            ['about.hero_kicker', 'Trang giới thiệu', 'Nhãn hero', 'text', 'Về Plantify'],
            ['about.hero_title', 'Trang giới thiệu', 'Tiêu đề hero', 'textarea', 'Thiết kế mảng xanh bền vững cho không gian sống và làm việc hiện đại.'],
            ['about.hero_description', 'Trang giới thiệu', 'Mô tả hero', 'textarea', 'Plantify kết hợp tư duy thiết kế, hiểu biết cây trồng và quy trình chăm sóc định kỳ để tạo nên những không gian xanh đẹp, khỏe và dễ duy trì.'],
            ['about.hero_primary_button', 'Trang giới thiệu', 'Nút hero chính', 'text', 'Xem FAQ'],
            ['about.hero_secondary_button', 'Trang giới thiệu', 'Nút hero phụ', 'text', 'Xem câu hỏi thường gặp'],
            ['about.hero_card_title', 'Trang giới thiệu', 'Tiêu đề thẻ hero', 'text', 'Không chỉ đặt cây vào phòng'],
            ['about.hero_card_text', 'Trang giới thiệu', 'Nội dung thẻ hero', 'textarea', 'Chúng tôi tính ánh sáng, luồng di chuyển, độ ẩm, chất liệu chậu và chi phí bảo dưỡng trước khi đề xuất phương án.'],
            ['about.metric_1_value', 'Trang giới thiệu', 'Chỉ số 1', 'text', '120+'],
            ['about.metric_1_label', 'Trang giới thiệu', 'Nhãn chỉ số 1', 'text', 'không gian đã tư vấn'],
            ['about.metric_2_value', 'Trang giới thiệu', 'Chỉ số 2', 'text', '30 ngày'],
            ['about.metric_2_label', 'Trang giới thiệu', 'Nhãn chỉ số 2', 'text', 'theo dõi sau bàn giao'],
            ['about.metric_3_value', 'Trang giới thiệu', 'Chỉ số 3', 'text', '24h'],
            ['about.metric_3_label', 'Trang giới thiệu', 'Nhãn chỉ số 3', 'text', 'phản hồi hồ sơ online'],
            ['about.metric_4_value', 'Trang giới thiệu', 'Chỉ số 4', 'text', '4 bước'],
            ['about.metric_4_label', 'Trang giới thiệu', 'Nhãn chỉ số 4', 'text', 'quy trình triển khai rõ ràng'],
            ['about.image_alt', 'Trang giới thiệu', 'Alt ảnh giới thiệu', 'text', 'Chăm sóc cây xanh trong không gian nội thất'],
            ['about.image_note_title', 'Trang giới thiệu', 'Tiêu đề ghi chú ảnh', 'text', 'Khảo sát trước khi chọn cây'],
            ['about.image_note_text', 'Trang giới thiệu', 'Nội dung ghi chú ảnh', 'textarea', 'Ánh sáng, hướng gió và thói quen sử dụng quyết định 70% độ bền của mảng xanh.'],
            ['about.story_kicker', 'Trang giới thiệu', 'Nhãn câu chuyện', 'text', 'Câu chuyện'],
            ['about.story_title', 'Trang giới thiệu', 'Tiêu đề câu chuyện', 'textarea', 'Từ những chậu cây nhỏ đến giải pháp xanh cho doanh nghiệp'],
            ['about.story_paragraph_1', 'Trang giới thiệu', 'Đoạn câu chuyện 1', 'textarea', 'Chúng tôi phục vụ văn phòng, căn hộ dịch vụ, showroom, nhà hàng và không gian bán lẻ cần một hình ảnh xanh chỉn chu. Mỗi dự án bắt đầu bằng khảo sát thực tế, sau đó đội ngũ thiết kế chọn cây theo ánh sáng, độ ẩm, mật độ sử dụng và phong cách nội thất.'],
            ['about.story_paragraph_2', 'Trang giới thiệu', 'Đoạn câu chuyện 2', 'textarea', 'Plantify không chạy theo bố cục rườm rà. Chúng tôi tập trung vào cây khỏe, chậu đẹp, tỷ lệ hài hòa và quy trình chăm sóc sau bàn giao.'],
            ['about.check_1', 'Trang giới thiệu', 'Gạch đầu dòng 1', 'text', 'Tư vấn theo ngân sách'],
            ['about.check_2', 'Trang giới thiệu', 'Gạch đầu dòng 2', 'text', 'Bố trí theo mặt bằng'],
            ['about.check_3', 'Trang giới thiệu', 'Gạch đầu dòng 3', 'text', 'Chọn cây theo điều kiện sáng'],
            ['about.check_4', 'Trang giới thiệu', 'Gạch đầu dòng 4', 'text', 'Theo dõi sức khỏe cây'],
            ['about.capability_kicker', 'Trang giới thiệu', 'Nhãn năng lực', 'text', 'Năng lực cốt lõi'],
            ['about.capability_title', 'Trang giới thiệu', 'Tiêu đề năng lực', 'textarea', 'Thiết kế đẹp nhưng vẫn dễ vận hành mỗi ngày'],
            ['about.capability_text', 'Trang giới thiệu', 'Mô tả năng lực', 'textarea', 'Plantify xây dựng phương án theo cả thẩm mỹ lẫn chi phí duy trì, phù hợp cho không gian có nhiều người sử dụng.'],
            ['about.feature_1_title', 'Trang giới thiệu', 'Tiêu đề năng lực 1', 'text', 'Thiết kế đúng không gian'],
            ['about.feature_1_text', 'Trang giới thiệu', 'Nội dung năng lực 1', 'textarea', 'Mỗi loại cây được chọn theo ánh sáng, diện tích, luồng di chuyển và chất liệu nội thất.'],
            ['about.feature_2_title', 'Trang giới thiệu', 'Tiêu đề năng lực 2', 'text', 'Cây khỏe, nguồn rõ'],
            ['about.feature_2_text', 'Trang giới thiệu', 'Nội dung năng lực 2', 'textarea', 'Cây được kiểm tra rễ, lá, sâu bệnh và khả năng thích nghi trước khi bàn giao.'],
            ['about.feature_3_title', 'Trang giới thiệu', 'Tiêu đề năng lực 3', 'text', 'Bảo dưỡng đều đặn'],
            ['about.feature_3_text', 'Trang giới thiệu', 'Nội dung năng lực 3', 'textarea', 'Lịch chăm sóc định kỳ giúp không gian xanh luôn sạch, an toàn và giữ hình ảnh chuyên nghiệp.'],
            ['about.process_kicker', 'Trang giới thiệu', 'Nhãn quy trình', 'text', 'Quy trình'],
            ['about.process_title', 'Trang giới thiệu', 'Tiêu đề quy trình', 'textarea', 'Rõ từng bước để khách hàng dễ theo dõi'],
            ['about.process_text', 'Trang giới thiệu', 'Mô tả quy trình', 'textarea', 'Từ ảnh không gian ban đầu đến chăm sóc định kỳ, mỗi giai đoạn đều có đầu ra cụ thể để bạn duyệt nhanh và kiểm soát ngân sách.'],
            ['about.process_1_title', 'Trang giới thiệu', 'Tiêu đề bước 1', 'text', 'Tiếp nhận nhu cầu'],
            ['about.process_1_text', 'Trang giới thiệu', 'Nội dung bước 1', 'textarea', 'Nhận ảnh, mặt bằng, phong cách mong muốn và mức ngân sách dự kiến.'],
            ['about.process_2_title', 'Trang giới thiệu', 'Tiêu đề bước 2', 'text', 'Khảo sát điều kiện'],
            ['about.process_2_text', 'Trang giới thiệu', 'Nội dung bước 2', 'textarea', 'Đánh giá ánh sáng, gió, ổ cắm, lối đi, vị trí tưới và rủi ro bẩn sàn.'],
            ['about.process_3_title', 'Trang giới thiệu', 'Tiêu đề bước 3', 'text', 'Đề xuất phương án'],
            ['about.process_3_text', 'Trang giới thiệu', 'Nội dung bước 3', 'textarea', 'Gợi ý cây, chậu, bố cục, tần suất chăm sóc và phương án thay thế khi cần.'],
            ['about.process_4_title', 'Trang giới thiệu', 'Tiêu đề bước 4', 'text', 'Bàn giao và duy trì'],
            ['about.process_4_text', 'Trang giới thiệu', 'Nội dung bước 4', 'textarea', 'Lắp đặt gọn, hướng dẫn chăm sóc, theo dõi cây sau bàn giao và bảo dưỡng định kỳ.'],
            ['about.testimonial_kicker', 'Trang giới thiệu', 'Nhãn phản hồi', 'text', 'Khách hàng nói gì'],
            ['about.testimonial_title', 'Trang giới thiệu', 'Tiêu đề phản hồi', 'textarea', 'Phản hồi từ các dự án đã triển khai'],
            ['about.testimonial_1_quote', 'Trang giới thiệu', 'Phản hồi 1', 'textarea', 'Plantify thiết kế mảng xanh gọn gàng, đúng tinh thần văn phòng của chúng tôi và chăm sóc cây rất đều.'],
            ['about.testimonial_1_name', 'Trang giới thiệu', 'Tên khách hàng 1', 'text', 'Ms. Linh Nguyễn'],
            ['about.testimonial_1_role', 'Trang giới thiệu', 'Vai trò khách hàng 1', 'text', 'Office Manager, Aster Tech'],
            ['about.testimonial_2_quote', 'Trang giới thiệu', 'Phản hồi 2', 'textarea', 'Đội ngũ tư vấn kỹ về ánh sáng và chất liệu chậu. Không gian studio sau khi decor trông ấm hơn nhưng vẫn rất tinh tế.'],
            ['about.testimonial_2_name', 'Trang giới thiệu', 'Tên khách hàng 2', 'text', 'Mr. Minh Trần'],
            ['about.testimonial_2_role', 'Trang giới thiệu', 'Vai trò khách hàng 2', 'text', 'Founder, Annam Studio'],
            ['about.map_kicker', 'Trang giới thiệu', 'Nhãn vị trí', 'text', 'Vị trí'],
            ['about.map_title', 'Trang giới thiệu', 'Tiêu đề vị trí', 'textarea', 'Ghé Plantify để chọn cây và chậu trực tiếp'],
            ['about.map_iframe_title', 'Trang giới thiệu', 'Tiêu đề iframe bản đồ', 'text', 'Bản đồ Plantify Co'],
            ['about.cta_title', 'Trang giới thiệu', 'Tiêu đề CTA', 'textarea', 'Muốn biết không gian của bạn hợp cây gì?'],
            ['about.cta_text', 'Trang giới thiệu', 'Mô tả CTA', 'textarea', 'Gửi ảnh hiện trạng, Plantify sẽ gợi ý nhóm cây, kích thước chậu và cách chăm sóc phù hợp.'],
            ['about.cta_button', 'Trang giới thiệu', 'Nút CTA', 'text', 'Xem FAQ'],
        ];

        foreach ($aboutDefaults as $row) {
            $db->query("INSERT INTO site_content (content_key, content_group, label, input_type, content_value)
                VALUES (:content_key, :content_group, :label, :input_type, :content_value)
                ON DUPLICATE KEY UPDATE
                    content_group = VALUES(content_group),
                    label = VALUES(label),
                    input_type = VALUES(input_type),
                    content_value = IF(content_key = 'about.hero_video' AND content_value = 'assets/videos/about/about.m3u8', VALUES(content_value), content_value)");
            $db->bind(':content_key', $row[0]);
            $db->bind(':content_group', $row[1]);
            $db->bind(':label', $row[2]);
            $db->bind(':input_type', $row[3]);
            $db->bind(':content_value', $row[4]);
            $db->execute();
        }

        $db->query('SELECT * FROM site_content ORDER BY content_group, id');
        $contentRows = $db->resultSet();

        $db->query("SELECT * FROM pages WHERE slug = 'about' LIMIT 1");
        $aboutPage = $db->single();
    } catch (Exception $e) {
        $error = 'Lỗi truy vấn database: ' . $e->getMessage();
    }
}

foreach ($contentRows as $row) {
    $contentByKey[$row['content_key']] = $row;
    if (strpos($row['content_key'], 'about.') === 0) {
        $groupedContent[$row['content_group']][] = $row;
    } else {
        $otherGroupedContent[$row['content_group']][] = $row;
    }
    if ($row['content_key'] === 'about.hero_video') {
        $heroVideoAdmin = $row['content_value'];
    }
}

$aboutEditorSections = [
    [
        'title' => 'SEO & thông tin trang',
        'description' => 'Tên tab trình duyệt và mô tả tìm kiếm.',
        'keys' => ['about.meta_title', 'about.meta_description'],
    ],
    [
        'title' => 'Hero đầu trang',
        'description' => 'Tiêu đề lớn, mô tả, nút bấm và thẻ thông tin trên nền video.',
        'keys' => ['about.hero_video', 'about.hero_video_label', 'about.hero_kicker', 'about.hero_title', 'about.hero_description', 'about.hero_primary_button', 'about.hero_secondary_button', 'about.hero_card_title', 'about.hero_card_text'],
    ],
    [
        'title' => 'Các chỉ số nổi bật',
        'description' => 'Bốn con số ngay dưới phần hero.',
        'keys' => ['about.metric_1_value', 'about.metric_1_label', 'about.metric_2_value', 'about.metric_2_label', 'about.metric_3_value', 'about.metric_3_label', 'about.metric_4_value', 'about.metric_4_label'],
    ],
    [
        'title' => 'Ảnh & ghi chú ảnh',
        'description' => 'Alt ảnh và nội dung ghi chú nổi trên ảnh giới thiệu.',
        'keys' => ['about.image_alt', 'about.image_note_title', 'about.image_note_text'],
    ],
    [
        'title' => 'Câu chuyện thương hiệu',
        'description' => 'Khối nội dung kể về Plantify và bốn gạch đầu dòng.',
        'keys' => ['about.story_kicker', 'about.story_title', 'about.story_paragraph_1', 'about.story_paragraph_2', 'about.check_1', 'about.check_2', 'about.check_3', 'about.check_4'],
    ],
    [
        'title' => 'Năng lực cốt lõi',
        'description' => 'Tiêu đề phần năng lực và ba thẻ dịch vụ.',
        'keys' => ['about.capability_kicker', 'about.capability_title', 'about.capability_text', 'about.feature_1_title', 'about.feature_1_text', 'about.feature_2_title', 'about.feature_2_text', 'about.feature_3_title', 'about.feature_3_text'],
    ],
    [
        'title' => 'Quy trình triển khai',
        'description' => 'Mô tả phần quy trình và bốn bước thực hiện.',
        'keys' => ['about.process_kicker', 'about.process_title', 'about.process_text', 'about.process_1_title', 'about.process_1_text', 'about.process_2_title', 'about.process_2_text', 'about.process_3_title', 'about.process_3_text', 'about.process_4_title', 'about.process_4_text'],
    ],
    [
        'title' => 'Phản hồi khách hàng',
        'description' => 'Tiêu đề phần phản hồi và hai lời chứng thực.',
        'keys' => ['about.testimonial_kicker', 'about.testimonial_title', 'about.testimonial_1_quote', 'about.testimonial_1_name', 'about.testimonial_1_role', 'about.testimonial_2_quote', 'about.testimonial_2_name', 'about.testimonial_2_role'],
    ],
    [
        'title' => 'Bản đồ & liên hệ nhanh',
        'description' => 'Tiêu đề khu vực bản đồ trên trang giới thiệu.',
        'keys' => ['about.map_kicker', 'about.map_title', 'about.map_iframe_title'],
    ],
    [
        'title' => 'CTA cuối trang',
        'description' => 'Khối kêu gọi hành động cuối trang.',
        'keys' => ['about.cta_title', 'about.cta_text', 'about.cta_button'],
    ],
];

admin_layout_start([
    'pageTitle' => $pageTitle,
    'heading' => 'Quản lý nội dung website',
    'subtitle' => 'Chỉnh văn bản tĩnh, ảnh giới thiệu và video hero đang hiển thị trên website.',
    'actionHtml' => '<a class="btn btn-outline-success" href="' . BASE_URL . '/about"><i class="fa-solid fa-eye me-2"></i>Xem trang giới thiệu</a>',
    'extraHead' => '<style>
        .about-editor-layout { display: grid; gap: 16px; }
        .about-editor-section { border: 1px solid #e5ece6; border-radius: 10px; background: #fff; overflow: hidden; }
        .about-editor-section summary { cursor: pointer; list-style: none; padding: 16px 18px; background: #f7fbf7; }
        .about-editor-section summary::-webkit-details-marker { display: none; }
        .about-editor-section-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .about-editor-section-title strong { color: #1d5f35; font-size: 16px; }
        .about-editor-section-title span { color: #748075; font-size: 13px; }
        .about-editor-section-title i { color: #198754; transition: transform .2s ease; }
        .about-editor-section[open] .about-editor-section-title i { transform: rotate(180deg); }
        .about-editor-section-body { padding: 18px; border-top: 1px solid #e5ece6; }
        .about-editor-toolbar { gap: 12px; }
        .about-current-image-preview { aspect-ratio: 1 / 1; width: min(100%, 220px); background: #f7fbf7; }
        .about-current-image-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        @media (max-width: 767px) { .about-editor-section-title { align-items: flex-start; } }
    </style>',
]);
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

<form method="post" class="admin-card mb-4">
    <input type="hidden" name="action" value="save_content">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 about-editor-toolbar">
        <div>
            <h4 class="mb-1">Nội dung trang giới thiệu</h4>
            <p class="text-muted mb-0">Mở từng mục nhỏ để chỉnh đúng khu vực đang hiển thị trên trang giới thiệu.</p>
        </div>
        <button type="submit" class="btn btn-success">Lưu thay đổi</button>
    </div>

    <div class="about-editor-layout">
        <?php foreach ($aboutEditorSections as $index => $section): ?>
            <details class="about-editor-section" <?php echo $index < 2 ? 'open' : ''; ?>>
                <summary>
                    <div class="about-editor-section-title">
                        <div>
                            <strong><?php echo e($section['title']); ?></strong>
                            <span class="d-block"><?php echo e($section['description']); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </summary>
                <div class="about-editor-section-body">
                    <div class="row">
                        <?php foreach ($section['keys'] as $key): ?>
                            <?php if (empty($contentByKey[$key])) {
                                continue;
                            } ?>
                            <?php $row = $contentByKey[$key]; ?>
                            <div class="<?php echo $row['input_type'] === 'textarea' ? 'col-lg-12' : 'col-lg-6'; ?> mb-3">
                                <label class="form-label" for="content_<?php echo e($row['id']); ?>">
                                    <?php echo e($row['label']); ?>
                                    <span class="d-block small text-muted"><?php echo e($row['content_key']); ?></span>
                                </label>
                                <?php if ($row['content_key'] === 'about.hero_video'): ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control bg-light" type="text" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>" readonly>
                                    <small class="form-text text-muted">Dùng khung upload video bên dưới để cập nhật file m3u8.</small>
                                <?php elseif ($row['input_type'] === 'textarea'): ?>
                                    <textarea id="content_<?php echo e($row['id']); ?>" class="form-control" name="content[<?php echo e($row['content_key']); ?>]" rows="3"><?php echo e($row['content_value']); ?></textarea>
                                <?php else: ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control" type="<?php echo $row['input_type'] === 'url' ? 'url' : 'text'; ?>" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($otherGroupedContent)): ?>
        <details class="about-editor-section mt-4">
            <summary>
                <div class="about-editor-section-title">
                    <div>
                        <strong>Nội dung dùng chung</strong>
                        <span class="d-block">Một số thông tin khác đang được dùng lại ở nhiều trang.</span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </summary>
            <div class="about-editor-section-body">
                <?php foreach ($otherGroupedContent as $group => $rows): ?>
                    <h5 class="text-success mt-2"><?php echo e($group); ?></h5>
                    <div class="row">
                        <?php foreach ($rows as $row): ?>
                            <div class="<?php echo $row['input_type'] === 'textarea' ? 'col-lg-12' : 'col-lg-6'; ?> mb-3">
                                <label class="form-label" for="content_<?php echo e($row['id']); ?>">
                                    <?php echo e($row['label']); ?>
                                    <span class="d-block small text-muted"><?php echo e($row['content_key']); ?></span>
                                </label>
                                <?php if ($row['input_type'] === 'textarea'): ?>
                                    <textarea id="content_<?php echo e($row['id']); ?>" class="form-control" name="content[<?php echo e($row['content_key']); ?>]" rows="3"><?php echo e($row['content_value']); ?></textarea>
                                <?php else: ?>
                                    <input id="content_<?php echo e($row['id']); ?>" class="form-control" type="<?php echo $row['input_type'] === 'url' ? 'url' : 'text'; ?>" name="content[<?php echo e($row['content_key']); ?>]" value="<?php echo e($row['content_value']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</form>

<div class="admin-card video-upload-card mb-4">
    <h4>Cấu hình video hero trang giới thiệu</h4>
    <form id="heroVideoUploadForm" class="video-upload-grid" method="post" action="<?php echo e(BASE_URL); ?>/api/upload-video.php" enctype="multipart/form-data">
        <label class="video-drop-zone" for="heroVideoFile">
            <input type="file" id="heroVideoFile" name="video" accept="video/mp4,video/quicktime,video/webm" required>
            <span class="video-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
            <strong id="heroVideoFileName">Kéo thả hoặc bấm để chọn video</strong>
            <small>MP4, MOV, WEBM. Hệ thống sẽ đổi sang HLS m3u8.</small>
        </label>
        <div class="video-upload-controls">
            <div class="row g-2">
                <div class="col-6">
                    <label for="videoStartSecond">Bắt đầu(s)</label>
                    <input type="number" id="videoStartSecond" class="form-control" name="start_second" min="0" step="0.1" value="0">
                </div>
                <div class="col-6">
                    <label for="videoEndSecond">Kết thúc(s)</label>
                    <input type="number" id="videoEndSecond" class="form-control" name="end_second" min="0" max="120" step="0.1" placeholder="Mặc định 30">
                </div>
            </div>
            <div class="video-current-path"><span>File hiện tại</span><strong id="heroVideoCurrentPath"><?php echo e($heroVideoAdmin); ?></strong></div>
            <button type="submit" class="btn btn-success w-100">Upload và đổi sang m3u8</button>
        </div>
    </form>
    <div class="video-upload-progress" id="heroVideoProgress" hidden>
        <div class="sort-spinner"></div>
        <strong>Đang xử lý video...</strong>
    </div>
    <div class="video-upload-message" id="heroVideoMessage" hidden></div>
</div>

<div class="admin-card mb-4">
    <h4 class="mb-4">Hình ảnh trang giới thiệu</h4>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_about_image">
        <div class="row align-items-end">
            <div class="col-lg-8 mb-3">
                <label class="form-label fw-bold" for="aboutImageFile">Upload hình ảnh</label>
                <input type="file" name="image_file" id="aboutImageFile" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" required>
                <small class="form-text text-muted">Hỗ trợ JPG, PNG, WEBP, GIF. Giới hạn 5MB.</small>
            </div>
            <div class="col-lg-4 mb-3">
                <label class="form-label fw-bold d-block">Hình hiện tại</label>
                <div class="admin-image-preview about-current-image-preview border rounded d-flex align-items-center justify-content-center overflow-hidden">
                    <?php if (!empty($aboutPage['image'])): ?>
                        <img src="<?php echo e(media_url($aboutPage['image'])); ?>" alt="Preview">
                    <?php else: ?>
                        <span class="text-muted">Chưa có ảnh</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success px-5 mt-2">Lưu hình ảnh</button>
    </form>
</div>

<?php
$extraScripts = '
<script>
const heroVideoUploadForm = document.getElementById("heroVideoUploadForm");
const heroVideoFile = document.getElementById("heroVideoFile");
const heroVideoFileName = document.getElementById("heroVideoFileName");
const heroVideoProgress = document.getElementById("heroVideoProgress");
const heroVideoMessage = document.getElementById("heroVideoMessage");
const heroVideoCurrentPath = document.getElementById("heroVideoCurrentPath");
const maxHeroVideoSize = 512 * 1024 * 1024;

if (heroVideoFile) {
    heroVideoFile.addEventListener("change", () => {
        heroVideoFileName.textContent = heroVideoFile.files[0] ? heroVideoFile.files[0].name : "Kéo thả hoặc bấm để chọn video";
    });
}

if (heroVideoUploadForm) {
    heroVideoUploadForm.addEventListener("submit", event => {
        event.preventDefault();
        if (heroVideoFile.files[0] && heroVideoFile.files[0].size > maxHeroVideoSize) {
            heroVideoMessage.textContent = "Video vượt quá giới hạn 512MB. Hãy cắt ngắn hơn hoặc nén file trước khi upload.";
            heroVideoMessage.className = "video-upload-message is-error";
            heroVideoMessage.hidden = false;
            return;
        }

        const form = new FormData(heroVideoUploadForm);
        heroVideoProgress.hidden = false;
        heroVideoMessage.hidden = true;
        heroVideoUploadForm.classList.add("is-uploading");

        fetch("' . BASE_URL . '/api/upload-video.php", {
            method: "POST",
            body: form
        })
            .then(response => response.text().then(text => {
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    throw new Error("Server không trả về JSON. Hãy kiểm tra log Apache/PHP.");
                }
                if (!response.ok) {
                    throw new Error(data.message || "Upload video thất bại.");
                }
                return data;
            }))
            .then(data => {
                if (!data.success) {
                    throw new Error(data.detail || data.message || "Không upload được video.");
                }
                heroVideoCurrentPath.textContent = data.path;
                document.querySelectorAll(\'input[name="content[about.hero_video]"]\').forEach(input => {
                    input.value = data.path;
                });
                heroVideoMessage.textContent = data.message || "Đã cập nhật video hero.";
                heroVideoMessage.className = "video-upload-message is-success";
            })
            .catch(error => {
                heroVideoMessage.textContent = error.message || "Upload video thất bại.";
                heroVideoMessage.className = "video-upload-message is-error";
            })
            .finally(() => {
                heroVideoProgress.hidden = true;
                heroVideoMessage.hidden = false;
                heroVideoUploadForm.classList.remove("is-uploading");
            });
    });
}
</script>';
admin_layout_end($extraScripts);
?>
