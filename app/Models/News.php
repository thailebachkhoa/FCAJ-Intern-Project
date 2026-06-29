<?php
/**
 * News Model
 * Handles all database operations for news/articles (Part #4)
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