<?php

namespace Models;

use Core\Model;

class Message extends Model
{
    public function create($data)
    {
        $stmt = $this->db->prepare('
            INSERT INTO messages (sender_id, content, timestamp)
            VALUES (?, ?, NOW())
        ');
        
        $stmt->execute([
            $data['sender_id'],
            $data['content']
        ]);

        return $this->db->lastInsertId();
    }

    public function getMessages($page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare('
            SELECT 
                m.message_id,
                m.content,
                m.timestamp,
                u.user_id,
                u.username,
                u.nickname,
                u.avatar
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.user_id
            ORDER BY m.timestamp DESC
            LIMIT ? OFFSET ?
        ');
        
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}