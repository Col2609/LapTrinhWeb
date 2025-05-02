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
        try {
            $limit = (int)$limit;
            $offset = (int)(($page - 1) * $limit);

            $sql = '
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
                LIMIT :limit OFFSET :offset
            ';

            error_log("SQL Query: $sql");
            error_log("Parameters: limit=$limit, offset=$offset");

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("Found " . count($results) . " messages");

            return $results;
        } catch (\PDOException $e) {
            error_log("Database error in getMessages: " . $e->getMessage());
            throw $e;
        }
    }
}
