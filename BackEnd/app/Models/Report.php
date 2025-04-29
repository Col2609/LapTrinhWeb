<?php

namespace Models;

use Core\Model;

class Report extends Model
{
    public function create($reporterUsername, $reportedUsername, $reportType, $description)
    {
        $stmt = $this->db->prepare('
            INSERT INTO reports (reporter_username, reported_username, report_type, description, created_at_UTC)
            VALUES (?, ?, ?, ?, NOW())
        ');
        return $stmt->execute([$reporterUsername, $reportedUsername, $reportType, $description]);
    }

    public function getReports($username)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM reports 
            WHERE reporter_username = ? OR reported_username = ?
            ORDER BY created_at_UTC DESC
        ');
        $stmt->execute([$username, $username]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deleteReport($reportId)
    {
        $stmt = $this->db->prepare('DELETE FROM reports WHERE id = ?');
        return $stmt->execute([$reportId]);
    }

    public function checkBanStatus($username)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM reports 
            WHERE reported_username = ? 
            AND report_type = "ban"
            ORDER BY created_at_UTC DESC
            LIMIT 1
        ');
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
} 