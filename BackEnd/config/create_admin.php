<?php

require_once __DIR__ . '/database.php';

function create_default_admin() {
    try {
        $db = require __DIR__ . '/database.php';
        
        // Kiểm tra xem đã có admin chưa
        $stmt = $db->prepare('SELECT * FROM users WHERE is_admin = 1 LIMIT 1');
        $stmt->execute();
        $existing_admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing_admin) {
            // Tạo admin mới
            $username = 'admin';
            $nickname = 'Admin';
            $email = 'appchat.noreply@gmail.com';
            $password = 'Admin1234@';
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = 1;
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare('
                INSERT INTO users (username, nickname, email, password_hash, is_admin, last_active_UTC, created_at_UTC)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([$username, $nickname, $email, $password_hash, $is_admin, $now, $now]);
            
            // Ghi log vào file
            $log_message = date('Y-m-d H:i:s') . " - Admin mặc định đã được tạo!\n";
            $log_message .= "Đăng nhập với: username='admin', password='Admin1234@'\n";
            file_put_contents(__DIR__ . '/admin_creation.log', $log_message, FILE_APPEND);
            
            // Hiển thị thông báo trong console
            error_log("🔹 Admin mặc định đã được tạo!");
            error_log("Đăng nhập với: username='admin', password='Admin1234@'");
        } else {
            error_log("Đã tồn tại Admin trong hệ thống.");
        }
    } catch (\PDOException $e) {
        error_log("Lỗi khi kiểm tra/tạo Admin: " . $e->getMessage());
    }
}

// Gọi hàm tạo admin
create_default_admin(); 