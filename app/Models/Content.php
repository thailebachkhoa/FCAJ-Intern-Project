<?php
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