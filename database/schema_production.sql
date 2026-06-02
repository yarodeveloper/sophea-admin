-- =====================================================
-- SOPHEA Database Schema - Production Ready
-- =====================================================
-- This script creates the complete database structure
-- for the SOPHEA admin system including WhatsApp integration
-- 
-- Usage:
--   mysql -u username -p database_name < schema_production.sql
--   Or import via phpMyAdmin
-- =====================================================

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS sophea_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sophea_db;

-- =====================================================
-- Table: leads
-- Stores contact form submissions and lead information
-- =====================================================
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre completo del lead',
    especialidad VARCHAR(255) NOT NULL COMMENT 'Especialidad o giro del negocio',
    whatsapp VARCHAR(50) NOT NULL COMMENT 'Número de WhatsApp',
    mensaje TEXT COMMENT 'Mensaje opcional del formulario',
    ip_address VARCHAR(45) COMMENT 'IP del cliente para rate limiting',
    user_agent TEXT COMMENT 'User agent del navegador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    status ENUM('nuevo', 'contactado', 'calificado', 'convertido', 'descartado') DEFAULT 'nuevo' COMMENT 'Estado del lead',
    source VARCHAR(100) DEFAULT 'website' COMMENT 'Origen del lead (website, whatsapp, etc)',
    notes TEXT COMMENT 'Notas adicionales del administrador',
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_whatsapp (whatsapp(20)),
    INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de leads';

-- =====================================================
-- Table: email_log
-- Tracks sent email notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT COMMENT 'ID del lead relacionado',
    recipient VARCHAR(255) NOT NULL COMMENT 'Email del destinatario',
    subject VARCHAR(500) COMMENT 'Asunto del email',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de envío',
    status ENUM('sent', 'failed') DEFAULT 'sent' COMMENT 'Estado del envío',
    error_message TEXT COMMENT 'Mensaje de error si falló',
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_sent_at (sent_at),
    INDEX idx_status (status),
    INDEX idx_lead_id (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de emails enviados';

-- =====================================================
-- Table: admin_users
-- Admin users for future authentication system
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL COMMENT 'Nombre de usuario',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña',
    email VARCHAR(255) NOT NULL COMMENT 'Email del administrador',
    full_name VARCHAR(255) COMMENT 'Nombre completo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    last_login TIMESTAMP NULL COMMENT 'Último inicio de sesión',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Usuario activo',
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios administradores';

-- =====================================================
-- Table: whatsapp_messages
-- Logs WhatsApp messages sent/received via API
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT COMMENT 'ID del lead relacionado',
    message_id VARCHAR(255) COMMENT 'ID del mensaje de WhatsApp API',
    direction ENUM('outbound', 'inbound') NOT NULL COMMENT 'Dirección del mensaje',
    to_number VARCHAR(50) NOT NULL COMMENT 'Número de destino',
    from_number VARCHAR(50) COMMENT 'Número de origen (para inbound)',
    message_type VARCHAR(50) COMMENT 'Tipo: text, image, document, etc',
    message_body TEXT COMMENT 'Contenido del mensaje',
    status VARCHAR(50) COMMENT 'Estado: sent, delivered, read, failed',
    status_timestamp TIMESTAMP NULL COMMENT 'Timestamp del último cambio de estado',
    error_code VARCHAR(50) COMMENT 'Código de error si falló',
    error_message TEXT COMMENT 'Mensaje de error',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    INDEX idx_lead_id (lead_id),
    INDEX idx_message_id (message_id(50)),
    INDEX idx_to_number (to_number(20)),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de mensajes de WhatsApp';

-- =====================================================
-- View: lead_stats
-- Statistics view for dashboard
-- =====================================================
CREATE OR REPLACE VIEW lead_stats AS
SELECT 
    COUNT(*) as total_leads,
    SUM(CASE WHEN status = 'nuevo' THEN 1 ELSE 0 END) as nuevos,
    SUM(CASE WHEN status = 'contactado' THEN 1 ELSE 0 END) as contactados,
    SUM(CASE WHEN status = 'calificado' THEN 1 ELSE 0 END) as calificados,
    SUM(CASE WHEN status = 'convertido' THEN 1 ELSE 0 END) as convertidos,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as hoy,
    SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as esta_semana,
    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as este_mes
FROM leads;

-- =====================================================
-- Insert default admin user (OPTIONAL)
-- Password: admin123 (CHANGE THIS IN PRODUCTION!)
-- =====================================================
-- Uncomment and modify if you want to use database authentication
-- INSERT INTO admin_users (username, password_hash, email, full_name) 
-- VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sophea.com.mx', 'Administrador')
-- ON DUPLICATE KEY UPDATE username=username;

-- =====================================================
-- End of Schema
-- =====================================================

