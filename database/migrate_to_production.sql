-- =====================================================
-- SOPHEA Database Migration Script
-- =====================================================
-- Use this script if you already have a database
-- and need to add new tables/columns
-- 
-- This script is safe to run multiple times
-- =====================================================

USE sophea_db;

-- =====================================================
-- Add updated_at column to leads if it doesn't exist
-- =====================================================
SET @dbname = DATABASE();
SET @tablename = 'leads';
SET @columnname = 'updated_at';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT ''Última actualización''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- Create whatsapp_messages table if it doesn't exist
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
-- Create or replace lead_stats view
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
-- Ensure email_log table exists
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
-- Ensure admin_users table exists
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
-- End of Migration
-- =====================================================

