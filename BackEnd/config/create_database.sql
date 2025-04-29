-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS appchat;
USE appchat;

-- Tạo bảng users
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    nickname VARCHAR(255),
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    last_active_UTC DATETIME,
    created_at_UTC DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Tạo bảng conversations
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    avatar_url VARCHAR(255),
    type ENUM('private', 'group') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE NOT NULL,
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type)
);

-- Tạo bảng group_members
CREATE TABLE IF NOT EXISTS group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT,
    username VARCHAR(255),
    role ENUM('admin', 'member') DEFAULT 'member',
    joined_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_username (username)
);

-- Tạo bảng messages
CREATE TABLE IF NOT EXISTS messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    conversation_id INT NOT NULL,
    content TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_timestamp (timestamp)
);

-- Tạo bảng attachments
CREATE TABLE IF NOT EXISTS attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT,
    file_url VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id)
);

-- Tạo bảng friend_requests
CREATE TABLE IF NOT EXISTS friend_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_username VARCHAR(50) NOT NULL,
    receiver_username VARCHAR(50) NOT NULL,
    status ENUM('Đợi', 'Chấp nhận', 'Từ chối') DEFAULT 'Đợi',
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_username) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (receiver_username) REFERENCES users(username) ON DELETE CASCADE,
    UNIQUE KEY unique_friend_request (sender_username, receiver_username),
    INDEX idx_sender_username (sender_username),
    INDEX idx_receiver_username (receiver_username),
    INDEX idx_status (status)
);

-- Tạo bảng friends
CREATE TABLE IF NOT EXISTS friends (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_username VARCHAR(50) NOT NULL,
    friend_username VARCHAR(50) NOT NULL,
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (friend_username) REFERENCES users(username) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_username, friend_username),
    CONSTRAINT no_self_friendship CHECK (user_username != friend_username),
    INDEX idx_user_username (user_username),
    INDEX idx_friend_username (friend_username)
);

-- Tạo bảng notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_username VARCHAR(255) NOT NULL,
    sender_username VARCHAR(255),
    message VARCHAR(255) NOT NULL,
    type ENUM('conversations', 'friend_request', 'friend_accept', 'friend_reject', 'message', 'system', 'report', 'warning') NOT NULL,
    related_id INT,
    related_table ENUM('friend_requests', 'messages', 'conversations', 'reports', 'users', 'warnings'),
    is_read TINYINT(1) DEFAULT 0,
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (sender_username) REFERENCES users(username) ON DELETE SET NULL,
    INDEX idx_user_username (user_username),
    INDEX idx_type (type),
    INDEX idx_related_id (related_id),
    INDEX idx_related_table (related_table),
    INDEX idx_is_read (is_read)
);

-- Tạo bảng reset_tokens
CREATE TABLE IF NOT EXISTS reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reset_uuid CHAR(36) UNIQUE NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at_UTC DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_reset_uuid (reset_uuid)
);

-- Tạo bảng reports
CREATE TABLE IF NOT EXISTS reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_username VARCHAR(50),
    report_type ENUM('user', 'group', 'bug') NOT NULL,
    target_id INT,
    target_table ENUM('users', 'conversations'),
    description TEXT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending' NOT NULL,
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_username) REFERENCES users(username) ON DELETE CASCADE,
    INDEX idx_reporter_username (reporter_username),
    INDEX idx_report_type (report_type),
    INDEX idx_target_id (target_id),
    INDEX idx_target_table (target_table),
    INDEX idx_status (status)
);

-- Tạo bảng warnings
CREATE TABLE IF NOT EXISTS warnings (
    warning_id INT PRIMARY KEY AUTO_INCREMENT,
    target_type ENUM('users', 'groups') NOT NULL,
    target_id INT,
    reason TEXT NOT NULL,
    ban_duration INT DEFAULT 0,
    ban_count INT DEFAULT 1,
    created_at_UTC DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_valid_ban_duration CHECK (ban_duration IN (0, 5, 15, 30, 60)),
    INDEX idx_target_type (target_type),
    INDEX idx_target_id (target_id)
); 