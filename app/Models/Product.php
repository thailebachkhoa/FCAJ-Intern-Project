<?php

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