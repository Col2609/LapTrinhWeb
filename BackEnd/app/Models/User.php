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

    public function create($username, $nickname, $email, $password, $avatar = null, $isAdmin = 0)
    {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare('
        INSERT INTO users (username, nickname, email, password_hash, avatar, is_admin, last_active_UTC, created_at_UTC)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
        $stmt->execute([$username, $nickname, $email, $password_hash, $avatar, $isAdmin]);
    }

    // Đăng nhập người dùng
    public function login($username, $password)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    public function getByUsername($username)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByEmail($email)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getById($userId)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateLastActive($userId)
    {
        $stmt = $this->db->prepare('UPDATE users SET last_active_UTC = NOW() WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function updatePassword($userId, $newPassword)
    {
        $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        return $stmt->execute([$password_hash, $userId]);
    }
}
