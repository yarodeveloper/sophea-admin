-- SOPHEA Authentication System Schema
-- Run this to set up secure authentication

USE sophea_db;

-- Table for tracking login attempts (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update admin_users table if needed (add columns if they don't exist)
-- Note: These columns should already exist from schema.sql, but adding ALTER statements for safety

-- Ensure admin_users has all required columns
ALTER TABLE admin_users 
MODIFY COLUMN password_hash VARCHAR(255) NOT NULL,
MODIFY COLUMN email VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Clean up old login attempts (older than 24 hours)
-- This can be run periodically via cron job
-- DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
