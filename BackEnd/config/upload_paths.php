<?php

define('UPLOAD_DIR', 'uploads');
define('AVATARS_USER_DIR', UPLOAD_DIR . '/avatars/users');
define('AVATARS_GROUP_DIR', UPLOAD_DIR . '/avatars/groups');
define('CONVERSATION_ATTACHMENTS_DIR', UPLOAD_DIR . '/conversations');

// Tạo các thư mục nếu chưa tồn tại
function createUploadDirectories() {
    $directories = [
        AVATARS_USER_DIR,
        AVATARS_GROUP_DIR,
        CONVERSATION_ATTACHMENTS_DIR
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                error_log("Failed to create directory: " . $dir);
            }
        }
    }
} 