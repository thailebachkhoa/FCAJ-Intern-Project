<?php
/**
 * Comment Model
 * Handles all database operations for comments (Part #4)
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