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

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Lấy danh sách message_id
            $messageIds = array_column($results, 'message_id');
            $attachmentsMap = [];

            if (!empty($messageIds)) {
                $inQuery = implode(',', array_fill(0, count($messageIds), '?'));
                $stmt2 = $this->db->prepare("SELECT message_id, file_url, file_type FROM attachments WHERE message_id IN ($inQuery)");
                $stmt2->execute($messageIds);
                $attachments = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

                // Gom attachments theo message_id
                foreach ($attachments as $att) {
                    $attachmentsMap[$att['message_id']][] = [
                        'file_url' => $att['file_url'],
                        'file_type' => $att['file_type']
                    ];
                }
            }

            // Gắn attachments vào từng message
            foreach ($results as &$msg) {
                $msg['attachments'] = $attachmentsMap[$msg['message_id']] ?? [];
            }

            return $results;
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    public function getDb()
    {
        return $this->db;
    }
}
