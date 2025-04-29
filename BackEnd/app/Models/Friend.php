<?php

namespace Models;

use Core\Model;

class Friend extends Model
{
    public function isFriend($username1, $username2)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM friends 
        WHERE (user_username = :username1 AND friend_username = :username2)
        OR (user_username = :username2 AND friend_username = :username1)
    ");
        $stmt->execute([
            ':username1' => $username1,
            ':username2' => $username2,
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    public function addFriend($username1, $username2)
    {
        $stmt = $this->db->prepare('
            INSERT INTO friends (user_username, friend_username, created_at_UTC)
            VALUES (?, ?, NOW())
        ');
        return $stmt->execute([$username1, $username2]);
    }

    public function removeFriend($username1, $username2)
    {
        $stmt = $this->db->prepare('
            DELETE FROM friends 
            WHERE (user_username = ? AND friend_username = ?)
            OR (user_username = ? AND friend_username = ?)
        ');
        return $stmt->execute([$username1, $username2, $username2, $username1]);
    }

    public function getFriends($username)
    {
        $stmt = $this->db->prepare('
            SELECT u.* FROM users u
            JOIN friends f ON (f.friend_username = u.username AND f.user_username = ?)
            OR (f.user_username = u.username AND f.friend_username = ?)
        ');
        $stmt->execute([$username, $username]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
