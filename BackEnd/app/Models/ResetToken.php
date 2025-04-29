<?php

namespace Models;

use Core\Model;

class ResetToken extends Model
{
    public function create($userId, $resetUuid, $tokenHash, $expiresAt)
    {
        $sql = "INSERT INTO reset_tokens (user_id, reset_uuid, token_hash, expires_at_UTC) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $resetUuid, $tokenHash, $expiresAt]);
    }

    public function getByUuid($resetUuid)
    {
        $sql = "SELECT * FROM reset_tokens WHERE reset_uuid = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$resetUuid]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteByUserId($userId)
    {
        $sql = "DELETE FROM reset_tokens WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    public function deleteByUuid($resetUuid)
    {
        $sql = "DELETE FROM reset_tokens WHERE reset_uuid = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$resetUuid]);
    }

    public static function hashToken($token)
    {
        return hash('sha256', $token);
    }
} 