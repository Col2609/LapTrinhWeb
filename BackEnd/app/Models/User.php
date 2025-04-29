<?php

namespace Models;

use Core\Model;

class User extends Model
{
    public function getAll()
    {
        $stmt = $this->db->query('SELECT * FROM users');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create($username, $email, $password)
    {
        // Băm mật khẩu trước khi lưu vào cơ sở dữ liệu
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Chuẩn bị câu lệnh SQL để thêm người dùng mới
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $password_hash]);
    }

    // Đăng nhập người dùng
    public function login($email, $password)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user; // Trả về thông tin người dùng nếu đăng nhập thành công
        }

        return false; // Trả về false nếu đăng nhập thất bại
    }

    // Lấy thông tin người dùng theo ID
    public function getById($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
