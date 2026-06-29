<?php

/**
 * File: includes/data.php
 * Chuc nang: Nap du lieu hien thi tu MySQL, kem fallback de website van chay
 * duoc khi database chua san sang.
 */

class Data
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function fetch_table_rows($table, $orderBy = 'id')
    {
        try {
            $this->db->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
            return $this->db->resultSet(); // Trả về kết quả thông qua resultSet()
        } catch (PDOException $exception) {
            return null;
        }
    }
    public function site_content_all()
    {
        static $content = null;

        if ($content !== null) {
            return $content;
        }

        $content = [];
        $db = $this->db;
        if (!$db) {
            return $content;
        }

        try {
            $db->query('SELECT content_key, content_value FROM site_content');
            $rows = $db->resultSet();

            foreach ($rows as $row) {
                $content[$row['content_key']] = $row['content_value'];
            }
        } catch (PDOException $exception) {
            $content = [];
        }

        return $content;
    }

    public function content_value($key, $default = '')
    {
        $content = $this->site_content_all();
        return array_key_exists($key, $content) && $content[$key] !== '' ? $content[$key] : $default;
    }

    public function get_company()
    {
        $fallback = [
            'name' => 'GreenNest Landscape',
            'tagline' => 'Cây xanh tinh tế cho không gian sống và làm việc',
            'phone' => '0908 246 135',
            'email' => 'hello@greennest.vn',
            'address' => '128 Nguyễn Văn Hưởng, Thảo Điền, TP. Thủ Đức, TP. Hồ Chí Minh',
            'hours' => 'Thứ 2 - Thứ 7: 08:00 - 18:00',
        ];

        return [
            'name' => $this->content_value('company.name', $fallback['name']),
            'tagline' => $this->content_value('company.tagline', $fallback['tagline']),
            'phone' => $this->content_value('company.phone', $fallback['phone']),
            'email' => $this->content_value('company.email', $fallback['email']),
            'address' => $this->content_value('company.address', $fallback['address']),
            'hours' => $this->content_value('company.hours', $fallback['hours']),
        ];
    }

    public function get_services()
    {
        $fallback = [
            [
                'icon' => 'fa-seedling',
                'title' => 'Thiết kế decor cây xanh',
                'description' => 'Khảo sát mặt bằng, tư vấn concept và bố trí cây cảnh phù hợp với văn phòng, nhà mẫu, showroom và căn hộ cao cấp.',
            ],
            [
                'icon' => 'fa-leaf',
                'title' => 'Cung cấp cây nội thất',
                'description' => 'Tuyển chọn cây khỏe, dáng đẹp, chậu phù hợp với phong cách hiện đại, tối giản và sang trọng.',
            ],
            [
                'icon' => 'fa-hand-holding-droplet',
                'title' => 'Chăm sóc định kỳ',
                'description' => 'Bảo dưỡng cây, cắt tỉa, bổ sung dinh dưỡng, xử lý sâu bệnh và thay thế cây theo gói dịch vụ doanh nghiệp.',
            ],
            [
                'icon' => 'fa-tree-city',
                'title' => 'Cảnh quan ban công và sân vườn',
                'description' => 'Thiết kế mảng xanh cho ban công, sân thượng, sân vườn nhỏ với giải pháp tưới và thoát nước an toàn.',
            ],
        ];

        $dbServices = $this->fetch_table_rows('services', 'id');
        return $dbServices ?? $fallback;
    }

    public function get_products()
    {
        $fallback = [
            [
                'name' => 'Bàng Singapore',
                'category' => 'Cây nội thất cao cấp',
                'price' => '1.250.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Tán lá lớn, dáng cây sang, phù hợp sảnh lễ tân, phòng họp và góc sofa.',
            ],
            [
                'name' => 'Monstera Deliciosa',
                'category' => 'Cây decor hiện đại',
                'price' => '780.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Lá xẻ độc đáo, tạo điểm nhấn xanh cho studio, căn hộ và không gian sáng tạo.',
            ],
            [
                'name' => 'Kim Tiền chậu gốm',
                'category' => 'Cây phong thủy',
                'price' => '520.000 VND',
                'image' => 'assets/images/Screenshot 2025-12-26 172140.png',
                'description' => 'Dễ chăm sóc, phù hợp bàn làm việc, quầy tiếp tân và quà tặng doanh nghiệp.',
            ],
        ];

        $dbProducts = $this->fetch_table_rows('products', 'is_featured DESC, id');
        if ($dbProducts) {
            return array_map(function ($product) {
                $product['price'] = number_format((float) $product['price'], 0, ',', '.') . ' VND';
                return $product;
            }, $dbProducts);
        }
        return $fallback;
    }

    public function get_faqs()
    {
        $fallback = [
            [
                'question' => 'Plantify có khảo sát trực tiếp trước khi thiết kế không?',
                'answer' => 'Có. Đội ngũ tư vấn sẽ khảo sát ánh sáng, diện tích, luồng gió và phong cách nội thất để đề xuất loại cây, chậu và vị trí phù hợp.',
            ],
            [
                'question' => 'Cây có được bảo hành sau khi bàn giao không?',
                'answer' => 'Tất cả cây trong gói decor doanh nghiệp được theo dõi sức khỏe trong 30 ngày đầu. Gói chăm sóc định kỳ có chính sách thay thế theo hợp đồng.',
            ],
            [
                'question' => 'Tôi có thể gửi ảnh mặt bằng để được tư vấn online không?',
                'answer' => 'Có. Bạn có thể chuẩn bị ảnh tổng thể, kích thước khu vực và điều kiện ánh sáng để đội ngũ tư vấn phân tích phương án phù hợp.',
            ],
            [
                'question' => 'Website có hỗ trợ quản lý sản phẩm bằng MySQL không?',
                'answer' => 'Có. Cấu trúc database có sẵn bảng products, services, faqs và pages để nâng cấp thành hệ thống quản trị nội dung đầy đủ.',
            ],
        ];

        $dbFaqs = $this->fetch_table_rows('faqs', 'sort_order, id');
        return $dbFaqs ?? $fallback;
    }

    public function get_testimonials()
    {
        return [
            [
                'name' => 'Ms. Linh Nguyễn',
                'role' => 'Office Manager, Aster Tech',
                'quote' => 'Plantify thiết kế mảng xanh gọn gàng, đúng tinh thần văn phòng của chúng tôi và chăm sóc cây rất đều.',
            ],
            [
                'name' => 'Mr. Minh Trần',
                'role' => 'Founder, Annam Studio',
                'quote' => 'Đội ngũ tư vấn kỹ về ánh sáng và chất liệu chậu. Không gian studio sau khi decor trông ấm hơn nhưng vẫn rất tinh tế.',
            ],
        ];
    }
}