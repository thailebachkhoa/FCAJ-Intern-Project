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
    // ==================== Cognito / Google login ====================

    public function findByCognitoSub($sub)
    {
        $this->db->query("SELECT * FROM users WHERE cognito_sub = :sub LIMIT 1");
        $this->db->bind(':sub', $sub);
        return $this->db->single();
    }

    /**
     * Gắn cognito_sub vào 1 tài khoản local đã có sẵn (trường hợp email
     * trùng với tài khoản đăng ký kiểu cũ trước khi có Google login).
     */
    public function attachCognitoSub($id, $sub)
    {
        $this->db->query("UPDATE users SET cognito_sub = :sub WHERE id = :id");
        $this->db->bind(':sub', $sub);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Tạo user mới ngay lần đầu đăng nhập Google (JIT provisioning).
     * password để NULL vì tài khoản này không có mật khẩu local.
     */
    public function createFromCognito($sub, $email, $fullname)
    {
        // Tính username TRƯỚC, vì hàm này tự chạy 1 câu SQL khác (SELECT) bên trong -
        // nếu để lồng ngay trong lúc bind() của câu INSERT, nó sẽ ghi đè mất prepared statement đang dùng.
        $username = $this->makeUsernameFromEmail($email);

        $this->db->query("INSERT INTO users (username, password, email, fullname, cognito_sub, role, status)
                           VALUES (:username, NULL, :email, :fullname, :sub, 'member', 'active')");
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':fullname', $fullname !== '' ? $fullname : $email);
        $this->db->bind(':sub', $sub);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    /** Sinh username duy nhất từ phần trước @ của email, tránh trùng cột UNIQUE username */
    private function makeUsernameFromEmail($email)
    {
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '', strstr($email, '@', true) ?: $email);
        $base = $base !== '' ? $base : 'user';

        $candidate = $base;
        $i = 1;
        while ($this->findByUsername($candidate)) {
            $candidate = $base . $i;
            $i++;
        }
        return $candidate;
    }

    /**
     * Đồng bộ role local theo Cognito Group mỗi lần đăng nhập Google,
     * để các đoạn code cũ dựa vào cột users.role (Auth::role(), check
     * role=='admin' trong view...) vẫn chạy đúng mà không cần sửa lại.
     */
    public function syncRole($id, $isAdmin)
    {
        $this->db->query("UPDATE users SET role = :role WHERE id = :id");
        $this->db->bind(':role', $isAdmin ? 'admin' : 'member');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // ==================== TOTP (Google Authenticator cho Admin) ====================

    public function setTotpSecret($id, $secret)
    {
        $this->db->query("UPDATE users SET totp_secret = :secret WHERE id = :id");
        $this->db->bind(':secret', $secret);
        $this->db->bind(':id', $id);
        return $this->db->execute();
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