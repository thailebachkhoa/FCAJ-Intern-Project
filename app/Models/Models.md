<?php
/**
 * Comment Model
 * Handles all database operations for comments (Part #4)
 * Location: app/Models/Comment.php
 */
class Comment
{
    private $db;
    private $perPage = 10;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get approved comments for a news article (with user info)
     */
    public function getByNewsId($newsId)
    {
        $this->db->query("SELECT c.*, u.username, u.fullname, u.avatar
                          FROM comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.target_id = :nid AND c.target_type = 'news' AND c.status = 'approved'
                          ORDER BY c.created_at ASC");
        $this->db->bind(':nid', (int)$newsId);
        return $this->db->resultSet();
    }

    /**
     * Count approved comments for a news article
     */
    public function countByNewsId($newsId)
    {
        $this->db->query("SELECT COUNT(*) as total FROM comments
                          WHERE target_id = :nid AND target_type = 'news' AND status = 'approved'");
        $this->db->bind(':nid', (int)$newsId);
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Submit a new comment (defaults to 'pending', awaits admin approval)
     */
    public function create($data)
    {
        $this->db->query("INSERT INTO comments (user_id, target_id, target_type, content, status)
                          VALUES (:uid, :tid, 'news', :content, 'pending')");
        $this->db->bind(':uid',     (int)$data['user_id']);
        $this->db->bind(':tid',     (int)$data['target_id']);
        $this->db->bind(':content', $data['content']);
        return $this->db->execute();
    }

    /* ==========================================
       ADMIN METHODS
       ========================================== */

    /**
     * Get all comments (news only) with user + news info, paginated
     */
    public function getAll($page = 1, $search = '')
    {
        $offset = ($page - 1) * $this->perPage;
        if ($search) {
            $this->db->query("SELECT c.*, u.username, u.fullname, n.title as news_title, n.slug as news_slug
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                                AND (c.content LIKE :s1 OR u.username LIKE :s2 OR u.fullname LIKE :s3 OR n.title LIKE :s4)
                              ORDER BY c.created_at DESC LIMIT :lim OFFSET :off");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
            $this->db->bind(':s3', '%' . $search . '%');
            $this->db->bind(':s4', '%' . $search . '%');
        } else {
            $this->db->query("SELECT c.*, u.username, u.fullname, n.title as news_title, n.slug as news_slug
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                              ORDER BY c.created_at DESC LIMIT :lim OFFSET :off");
        }
        $this->db->bind(':lim', $this->perPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count all news comments (for admin pagination)
     */
    public function countAll($search = '')
    {
        if ($search) {
            $this->db->query("SELECT COUNT(*) as total
                              FROM comments c
                              JOIN users u ON c.user_id = u.id
                              LEFT JOIN news n ON c.target_id = n.id AND c.target_type = 'news'
                              WHERE c.target_type = 'news'
                                AND (c.content LIKE :s1 OR u.username LIKE :s2 OR u.fullname LIKE :s3 OR n.title LIKE :s4)");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
            $this->db->bind(':s3', '%' . $search . '%');
            $this->db->bind(':s4', '%' . $search . '%');
        } else {
            $this->db->query("SELECT COUNT(*) as total FROM comments WHERE target_type = 'news'");
        }
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single comment by ID
     */
    public function getById($id)
    {
        $this->db->query("SELECT c.*, u.username, u.fullname FROM comments c
                          JOIN users u ON c.user_id = u.id WHERE c.id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Toggle comment status: approved ↔ hidden
     * Also promotes 'pending' to 'approved' on first toggle
     */
    public function toggleStatus($id)
    {
        $this->db->query("SELECT status FROM comments WHERE id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        $current = $this->db->single();
        if (!$current) return false;

        $newStatus = ($current['status'] === 'approved') ? 'hidden' : 'approved';

        $this->db->query("UPDATE comments SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $this->db->bind(':status', $newStatus);
        $this->db->bind(':id',     (int)$id);
        return $this->db->execute();
    }

    /**
     * Delete a comment by ID
     */
    public function delete($id)
    {
        $this->db->query("DELETE FROM comments WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function getPerPage()
    {
        return $this->perPage;
    }
}
<?php
// Location: app/Models/Content.php
class Content
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllSiteContent()
    {
        $this->db->query("SELECT * FROM site_content ORDER BY content_group, id");

        return $this->db->resultSet();
    }

    public function getAllPages()
    {
        $this->db->query("SELECT * FROM pages ORDER BY slug");
        return $this->db->resultSet();
    }

    public function updateSiteContent($key, $value)
    {
        $this->db->query("UPDATE site_content SET content_value = :val WHERE content_key = :key");
        $this->db->bind(':val', $value);
        $this->db->bind(':key', $key);
        return $this->db->execute();
    }
    public function updateMultipleSiteContent(array $data)
    {
        foreach ($data as $key => $value) {
            $this->updateSiteContent($key, $value);
        }
        return true;
    }

    public function getSiteContentByGroups(array $groups)
    {

        $placeholders = implode(',', array_fill(0, count($groups), '?'));

        $sql = "SELECT * FROM site_content 
                WHERE content_group IN ($placeholders) 
                ORDER BY FIELD(content_group, 'Trang cửa hàng', 'Trang chi tiết SP', 'Trang giỏ hàng'), id ASC";

        $this->db->query($sql);

        foreach ($groups as $index => $group) {
            $this->db->bind($index + 1, $group);
        }

        return $this->db->resultSet();
    }

    public function seedDefaults(array $defaults): void
    {
        foreach ($defaults as $row) {
            $this->db->query(
                "INSERT INTO site_content (content_key, content_group, label, input_type, content_value)
                 VALUES (:k, :g, :l, :t, :v)
                 ON DUPLICATE KEY UPDATE
                     content_group = VALUES(content_group),
                     label         = VALUES(label),
                     input_type    = VALUES(input_type)"
            );
            $this->db->bind(':k', $row[0]);
            $this->db->bind(':g', $row[1]);
            $this->db->bind(':l', $row[2]);
            $this->db->bind(':t', $row[3]);
            $this->db->bind(':v', $row[4]);
            $this->db->execute();
        }
    }
 
    public function getByGroup(string $group): array
    {
        $this->db->query(
            "SELECT * FROM site_content WHERE content_group = :g ORDER BY id"
        );
        $this->db->bind(':g', $group);
        $rows   = $this->db->resultSet();
        $byKey  = [];
        foreach ($rows as $r) {
            $byKey[$r['content_key']] = $r;
        }
        return $byKey;
    }
 
    public function saveByPost(array $postContent): void
    {
        foreach ($postContent as $key => $value) {
            $this->db->query(
                "UPDATE site_content SET content_value = :v WHERE content_key = :k"
            );
            $this->db->bind(':v', trim((string) $value));
            $this->db->bind(':k', (string) $key);
            $this->db->execute();
        }
    }
}
<?php

/**
 * File: includes/data.php
 * Chuc nang: Nap du lieu hien thi tu MySQL, kem fallback de website van chay
 * duoc khi database chua san sang.
 * Location: app/Models/Data.php
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
<?php
/**
 * News Model
 * Handles all database operations for news/articles (Part #4)
 * Location: app/Models/News.php
 */
class News
{
    private $db;
    private $perPage = 9;
    private $adminPerPage = 10;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a URL-friendly slug from a Vietnamese title
     */
    public static function generateSlug($title, $suffix = '')
    {
        $from = ['à','á','ả','ã','ạ','â','ầ','ấ','ẩ','ẫ','ậ','ă','ằ','ắ','ẳ','ẵ','ặ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ','đ','À','Á','Ả','Ã','Ạ','Â','Ầ','Ấ','Ẩ','Ẫ','Ậ','Ă','Ằ','Ắ','Ẳ','Ẵ','Ặ','È','É','Ẻ','Ẽ','Ẹ','Ê','Ề','Ế','Ể','Ễ','Ệ','Ì','Í','Ỉ','Ĩ','Ị','Ò','Ó','Ỏ','Õ','Ọ','Ô','Ồ','Ố','Ổ','Ỗ','Ộ','Ơ','Ờ','Ớ','Ở','Ỡ','Ợ','Ù','Ú','Ủ','Ũ','Ụ','Ư','Ừ','Ứ','Ử','Ữ','Ự','Ỳ','Ý','Ỷ','Ỹ','Ỵ','Đ'];
        $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];

        $slug = str_replace($from, $to, $title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($suffix) $slug .= '-' . $suffix;
        return $slug ?: 'bai-viet-' . time();
    }

    /* ==========================================
       FRONTEND METHODS
       ========================================== */

    /**
     * Get published news with pagination and optional search
     */
    public function getPublished($page = 1, $search = '')
    {
        $offset = ($page - 1) * $this->perPage;
        if ($search) {
            $this->db->query("SELECT * FROM news WHERE status = 'published'
                              AND (title LIKE :s1 OR tags LIKE :s2)
                              ORDER BY created_at DESC LIMIT :lim OFFSET :off");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        } else {
            $this->db->query("SELECT * FROM news WHERE status = 'published'
                              ORDER BY created_at DESC LIMIT :lim OFFSET :off");
        }
        $this->db->bind(':lim', $this->perPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count published news (for pagination)
     */
    public function countPublished($search = '')
    {
        if ($search) {
            $this->db->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'
                              AND (title LIKE :s1 OR tags LIKE :s2)");
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        } else {
            $this->db->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'");
        }
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single published news by slug
     */
    public function getBySlug($slug)
    {
        $this->db->query("SELECT * FROM news WHERE slug = :slug AND status = 'published' LIMIT 1");
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    /**
     * Get related news by tags, excluding current article
     */
    public function getRelated($newsId, $tags = '', $limit = 3)
    {
        $tagList = array_filter(array_map('trim', explode(',', $tags ?? '')));
        if (!empty($tagList)) {
            $conditions = implode(' OR ', array_map(function ($tag) {
                return "tags LIKE '%" . str_replace("'", "''", trim($tag)) . "%'";
            }, $tagList));
            $this->db->query("SELECT * FROM news WHERE id != :id AND status = 'published'
                              AND ($conditions) ORDER BY created_at DESC LIMIT :lim");
        } else {
            $this->db->query("SELECT * FROM news WHERE id != :id AND status = 'published'
                              ORDER BY created_at DESC LIMIT :lim");
        }
        $this->db->bind(':id', $newsId);
        $this->db->bind(':lim', $limit);
        return $this->db->resultSet();
    }

    /* ==========================================
       ADMIN METHODS
       ========================================== */

    /**
     * Get all news for admin with pagination, search, and status filter
     */
    public function getAll($page = 1, $search = '', $status = '')
    {
        $offset = ($page - 1) * $this->adminPerPage;
        $where = '1=1';
        if ($search) $where .= ' AND (title LIKE :s1 OR tags LIKE :s2)';
        if ($status) $where .= ' AND status = :status';

        $this->db->query("SELECT * FROM news WHERE $where ORDER BY created_at DESC LIMIT :lim OFFSET :off");
        if ($search) {
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        }
        if ($status) $this->db->bind(':status', $status);
        $this->db->bind(':lim', $this->adminPerPage);
        $this->db->bind(':off', $offset);
        return $this->db->resultSet();
    }

    /**
     * Count all news (for admin pagination)
     */
    public function countAll($search = '', $status = '')
    {
        $where = '1=1';
        if ($search) $where .= ' AND (title LIKE :s1 OR tags LIKE :s2)';
        if ($status) $where .= ' AND status = :status';

        $this->db->query("SELECT COUNT(*) as total FROM news WHERE $where");
        if ($search) {
            $this->db->bind(':s1', '%' . $search . '%');
            $this->db->bind(':s2', '%' . $search . '%');
        }
        if ($status) $this->db->bind(':status', $status);
        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get a single news by ID (admin)
     */
    public function getById($id)
    {
        $this->db->query("SELECT * FROM news WHERE id = :id LIMIT 1");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Create a new news article
     * Returns the new ID on success
     */
    public function create($data)
    {
        $this->db->query("INSERT INTO news (title, slug, short_description, content, thumbnail, tags, seo_desc, author, status)
                          VALUES (:title, :slug, :short_desc, :content, :thumbnail, :tags, :seo_desc, :author, :status)");
        $this->db->bind(':title',      $data['title']);
        $this->db->bind(':slug',       $data['slug']);
        $this->db->bind(':short_desc', $data['short_description']);
        $this->db->bind(':content',    $data['content']);
        $this->db->bind(':thumbnail',  $data['thumbnail']);
        $this->db->bind(':tags',       $data['tags']);
        $this->db->bind(':seo_desc',   $data['seo_desc']);
        $this->db->bind(':author',     $data['author']);
        $this->db->bind(':status',     $data['status']);
        $this->db->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update slug after creation (to include the new ID)
     */
    public function updateSlug($id, $slug)
    {
        $this->db->query("UPDATE news SET slug = :slug WHERE id = :id");
        $this->db->bind(':slug', $slug);
        $this->db->bind(':id',   (int)$id);
        return $this->db->execute();
    }

    /**
     * Update an existing news article
     */
    public function update($id, $data)
    {
        $this->db->query("UPDATE news SET title=:title, slug=:slug, short_description=:short_desc,
                          content=:content, thumbnail=:thumbnail, tags=:tags, seo_desc=:seo_desc,
                          author=:author, status=:status, updated_at=CURRENT_TIMESTAMP WHERE id=:id");
        $this->db->bind(':title',      $data['title']);
        $this->db->bind(':slug',       $data['slug']);
        $this->db->bind(':short_desc', $data['short_description']);
        $this->db->bind(':content',    $data['content']);
        $this->db->bind(':thumbnail',  $data['thumbnail']);
        $this->db->bind(':tags',       $data['tags']);
        $this->db->bind(':seo_desc',   $data['seo_desc']);
        $this->db->bind(':author',     $data['author']);
        $this->db->bind(':status',     $data['status']);
        $this->db->bind(':id',         (int)$id);
        return $this->db->execute();
    }

    /**
     * Delete a news article by ID
     */
    public function delete($id)
    {
        $this->db->query("DELETE FROM news WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Get admin per-page count
     */
    public function getAdminPerPage()
    {
        return $this->adminPerPage;
    }

    /**
     * Get frontend per-page count
     */
    public function getPerPage()
    {
        return $this->perPage;
    }
}
<?php
// Location: app/Models/Order.php
class Order
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create($orderData, $cartItems)
    {
        try {
            // Bắt đầu Transaction
            $this->db->beginTransaction();

            // 1. Chèn vào bảng orders
            $this->db->query("INSERT INTO orders (user_id, fullname, phone, address, note, total_price) 
                              VALUES (:user_id, :fullname, :phone, :address, :note, :total_price)");
            $this->db->bind(':user_id', $orderData['user_id']);
            $this->db->bind(':fullname', $orderData['fullname']);
            $this->db->bind(':phone', $orderData['phone']);
            $this->db->bind(':address', $orderData['address']);
            $this->db->bind(':note', $orderData['note']);
            $this->db->bind(':total_price', $orderData['total_price']);
            $this->db->execute();

            $orderId = $this->db->lastInsertId();

            // 2. Chèn từng món vào order_items
            foreach ($cartItems as $item) {
                $this->db->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (:order_id, :product_id, :quantity, :price)");
                $this->db->bind(':order_id', $orderId);
                $this->db->bind(':product_id', $item['product_id']);
                $this->db->bind(':quantity', $item['quantity']);
                $this->db->bind(':price', $item['price']);
                $this->db->execute();
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            // TẠM THỜI IN LỖI RA MÀN HÌNH ĐỂ DEBUG:
            die("Lỗi đặt hàng: " . $e->getMessage());
            // return false;
        }
    }

    public function getOrdersByUserId($userId)
    {
        $this->db->query("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getAllOrders()
    {
        $this->db->query("SELECT o.*, u.fullname as user_name 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
        return $this->db->resultSet();
    }

    public function getOrderDetail($orderId)
    {
        $this->db->query("SELECT o.*, u.email as user_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = :id");
        $this->db->bind(':id', $orderId);
        $order = $this->db->single();

        if ($order) {

            $this->db->query("SELECT oi.*, p.name, p.image 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = :oid");
            $this->db->bind(':oid', $orderId);
            $order['items'] = $this->db->resultSet();
        }
        return $order;
    }

    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE orders SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
<?php
// Location: app/Models/Product.php
class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById($id)
    {
        $this->db->query("SELECT * FROM products WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getPaginated($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $this->db->query("SELECT * FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset");
        return $this->db->resultSet();
    }

    public function getFilteredProducts($limit, $offset, $category = 'all', $sort = 'newest', $search = '')
    {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];

        // 1. Xử lý tìm kiếm (Tìm theo tên hoặc mô tả)
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // 2. Xử lý lọc theo danh mục
        if ($category !== 'all') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        // 3. Xử lý sắp xếp
        if ($sort === 'price_asc') {
            $sql .= " ORDER BY price ASC";
        } elseif ($sort === 'price_desc') {
            $sql .= " ORDER BY price DESC";
        } else {
            $sql .= " ORDER BY id DESC"; // Mới nhất
        }

        // 4. Phân trang
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $this->db->query($sql);

        // Bind các tham số an toàn (Tránh SQL Injection)
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số sản phẩm sau khi lọc (Dùng để chia số trang)
     */
    public function countFilteredProducts($category = 'all', $search = '')
    {
        $sql = "SELECT COUNT(id) as total FROM products WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($category !== 'all') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $this->db->query($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    public function countAll()
    {
        $this->db->query("SELECT COUNT(id) as total FROM products");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    public function getRelated($exclude_id, $limit = 4)
    {
        $limit = (int)$limit;
        $this->db->query("SELECT * FROM products WHERE id != :id ORDER BY RAND() LIMIT $limit");
        $this->db->bind(':id', $exclude_id);
        return $this->db->resultSet();
    }

    public function getFeatured($limit = 4)
    {
        $limit = (int)$limit;
        $this->db->query("SELECT * FROM products WHERE is_featured = 1 ORDER BY id DESC LIMIT $limit");
        return $this->db->resultSet();
    }

    public function getAllProducts()
    {
        $this->db->query("SELECT * FROM products ORDER BY id DESC");
        return $this->db->resultSet();
    }

    public function create($data)
    {
        $this->db->query("INSERT INTO products (name, category, price, image, description, is_featured) 
                          VALUES (:name, :category, :price, :image, :description, :is_featured)");

        $this->db->bind(':name', $data['name']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':is_featured', $data['is_featured'] ?? 0);

        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $this->db->query("UPDATE products 
                          SET name = :name, category = :category, price = :price, 
                              image = :image, description = :description, is_featured = :is_featured 
                          WHERE id = :id");

        $this->db->bind(':id', $id);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':is_featured', $data['is_featured'] ?? 0);

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM products WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
<?php
// Location: app/Models/User.php
class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername($username)
    {
        $this->db->query("SELECT * FROM users WHERE username = :username LIMIT 1");
        $this->db->bind(':username', $username);
        return $this->db->single();
    }

    public function findByEmail($email)
    {
        $this->db->query("SELECT * FROM users WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    /**
     * Find user by username or email (for login)
     */
    public function findByUsernameOrEmail($username_or_email)
    {
        $this->db->query("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
        $this->db->bind(':username', $username_or_email);
        $this->db->bind(':email', $username_or_email);
        return $this->db->single();
    }

    public function findById($id)
    {
        $this->db->query("SELECT * FROM users WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function register($data)
    {
        $this->db->query("INSERT INTO users (username, password, email, fullname, role) VALUES (:username, :password, :email, :fullname, :role)");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':fullname', $data['fullname']);
        $this->db->bind(':role', 'member');

        return $this->db->execute();
    }

    // Lấy toàn bộ người dùng
    public function getAllUsers()
    {
        $this->db->query("SELECT * FROM users ORDER BY id DESC");
        return $this->db->resultSet();
    }

    // Khoá / Mở khoá người dùng
    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE users SET status = :status WHERE id = :id AND role != 'admin'");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Reset mật khẩu về 123456
    public function resetPassword($id, $newPasswordHash)
    {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $newPasswordHash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Xóa người dùng
    public function deleteUser($id)
    {
        $this->db->query("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Cập nhật thông tin profile (Tên và Avatar)
     **/
    public function updateProfile($id, $fullname, $avatar)
    {
        $this->db->query("UPDATE users SET fullname = :fullname, avatar = :avatar WHERE id = :id");
        $this->db->bind(':fullname', $fullname);
        $this->db->bind(':avatar', $avatar);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function updatePassword($id, $newPasswordHash)
    {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $newPasswordHash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getPaginated($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $this->db->query("SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset");
        return $this->db->resultSet();
    }
    public function countAll()
    {
        $this->db->query("SELECT COUNT(id) as total FROM users");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }
    public function create($data)
    {
        $this->db->query("INSERT INTO users (username, fullname, email, password, role, status) 
                          VALUES (:username, :fullname, :email, :password, :role, :status)");

        $this->db->bind(':username', $data['username']);
        $this->db->bind(':fullname', $data['fullname']);
        $this->db->bind(':email',    $data['email']);
        $this->db->bind(':password', $data['password']); // Lưu ý: Password đã được mã hóa (hash) ở Controller
        $this->db->bind(':role',     $data['role']);
        $this->db->bind(':status',   $data['status'] ?? 1);

        return $this->db->execute();
    }
}