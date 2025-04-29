<?php

namespace Models;

use Core\Model;

class FriendRequest extends Model
{
    public function isRequestSent($senderUsername, $receiverUsername)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM friend_requests 
        WHERE sender_username = :senderUsername 
        AND receiver_username = :receiverUsername 
        AND status = 'Đợi'
    ");
        $stmt->execute([
            ':senderUsername' => $senderUsername,
            ':receiverUsername' => $receiverUsername,
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    public function isRequestReceived($senderUsername, $receiverUsername)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM friend_requests 
        WHERE sender_username = :senderUsername 
        AND receiver_username = :receiverUsername 
        AND status = 'Đợi'
    ");
        $stmt->execute([
            ':senderUsername' => $senderUsername,
            ':receiverUsername' => $receiverUsername,
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }
}
