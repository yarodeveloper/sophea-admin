-- SOPHEA - Migración: Información de Contacto Dinámica
-- Este script agrega los campos necesarios para información de contacto dinámica
-- Ejecutar después de tener la tabla site_settings creada

USE sophea_db;

-- Asegurar que la tabla site_settings existe
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('image', 'text', 'url') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar o actualizar configuración de información de contacto
-- Estos valores se pueden editar desde Admin Web → Información de Contacto

INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('company_address', '', 'text'),
('company_phone', '', 'text'),
('company_phone_whatsapp', '', 'text'),
('company_phone_landline', '', 'text'),
('company_email', '', 'text'),
('company_chatbot', '', 'text'),
('social_facebook', '', 'url'),
('social_instagram', '', 'url'),
('social_linkedin', '', 'url'),
('social_youtube', '', 'url')
ON DUPLICATE KEY UPDATE 
    setting_key = VALUES(setting_key),
    setting_type = VALUES(setting_type);

-- Nota: Los valores vacíos se llenarán desde Admin Web o usarán las constantes de config.php como fallback

