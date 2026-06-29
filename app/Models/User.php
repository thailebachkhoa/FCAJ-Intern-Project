<?php
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