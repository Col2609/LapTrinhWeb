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

-- Tạo bảng messages
CREATE TABLE IF NOT EXISTS messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    content TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_sender_id (sender_id),
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