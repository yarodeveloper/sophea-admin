-- SOPHEA Database Schema
-- Create database and tables for lead management

-- Create database
CREATE DATABASE IF NOT EXISTS sophea_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sophea_db;

-- Leads table
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    especialidad VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(50) NOT NULL,
    mensaje TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('nuevo', 'contactado', 'calificado', 'convertido', 'descartado') DEFAULT 'nuevo',
    source VARCHAR(100) DEFAULT 'website',
    notes TEXT,
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_whatsapp (whatsapp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email log table (optional - for tracking sent emails)
CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_sent_at (sent_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table (for future admin panel)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123' using PASSWORD_DEFAULT
INSERT INTO admin_users (username, password_hash, email, full_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sophea.com.mx', 'Administrador');

-- Create view for lead statistics
CREATE OR REPLACE VIEW lead_stats AS
SELECT 
    COUNT(*) as total_leads,
    COUNT(CASE WHEN status = 'nuevo' THEN 1 END) as nuevos,
    COUNT(CASE WHEN status = 'contactado' THEN 1 END) as contactados,
    COUNT(CASE WHEN status = 'calificado' THEN 1 END) as calificados,
    COUNT(CASE WHEN status = 'convertido' THEN 1 END) as convertidos,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as hoy,
    COUNT(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) THEN 1 END) as esta_semana,
    COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN 1 END) as este_mes
FROM leads;
