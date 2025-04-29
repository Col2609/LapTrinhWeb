<?php

namespace Models;

use Core\Model;

class Notification extends Model
{
    public function create($userUsername, $senderUsername = null, $message, $type = 'system', $relatedId = null, $relatedTable = null, $isRead = false)
    {
        // Chuyển đổi isRead thành số nguyên (0 hoặc 1)
        $isRead = $isRead ? 1 : 0;
        
        $sql = "INSERT INTO notifications (user_username, sender_username, message, type, related_id, related_table, is_read, created_at_UTC) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userUsername, $senderUsername, $message, $type, $relatedId, $relatedTable, $isRead]);
    }

    public function getByUsername($username)
    {
        $sql = "SELECT * FROM notifications WHERE user_username = ? ORDER BY created_at_UTC DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUnreadByUsername($username)
    {
        $sql = "SELECT * FROM notifications WHERE user_username = ? AND is_read = 0 ORDER BY created_at_UTC DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId]);
    }

    public function markAllAsRead($username)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_username = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$username]);
    }

    public function delete($notificationId)
    {
        $sql = "DELETE FROM notifications WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId]);
    }

    public function getByType($username, $type)
    {
        $sql = "SELECT * FROM notifications WHERE user_username = ? AND type = ? ORDER BY created_at_UTC DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByRelatedTable($username, $relatedTable)
    {
        $sql = "SELECT * FROM notifications WHERE user_username = ? AND related_table = ? ORDER BY created_at_UTC DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $relatedTable]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByRelatedId($username, $relatedId)
    {
        $sql = "SELECT * FROM notifications WHERE user_username = ? AND related_id = ? ORDER BY created_at_UTC DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $relatedId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 