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

    public function create($name, $email)
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
        $stmt->execute([$name, $email]);
    }
}
