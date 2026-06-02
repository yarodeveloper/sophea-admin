-- =====================================================
-- SOPHEA - Catálogo de Servicios
-- =====================================================
-- Este script crea la tabla de catálogo de servicios
-- con precios sugeridos y observaciones
-- 
-- Uso:
--   mysql -u username -p sophea_db < services_catalog.sql
--   O importar vía phpMyAdmin
-- =====================================================

USE sophea_db;

-- =====================================================
-- Tabla: services_catalog (Catálogo de Servicios)
-- =====================================================
CREATE TABLE IF NOT EXISTS services_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL COMMENT 'Nombre del servicio',
    service_type ENUM(
        'redes_sociales', 
        'community_manager', 
        'diseno_web', 
        'ads', 
        'branding', 
        'chatbot', 
        'seo', 
        'content_marketing',
        'email_marketing',
        'consultoria_legal',
        'auditoria_datos',
        'otro'
    ) NOT NULL COMMENT 'Tipo de servicio',
    suggested_price DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Precio sugerido',
    currency VARCHAR(3) DEFAULT 'MXN' COMMENT 'Moneda del precio',
    description TEXT COMMENT 'Descripción del servicio',
    observations TEXT COMMENT 'Observaciones y notas adicionales',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Si el servicio está activo en el catálogo',
    display_order INT DEFAULT 0 COMMENT 'Orden de visualización',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del admin que creó el servicio',
    INDEX idx_service_type (service_type),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de servicios con precios sugeridos';

-- Insertar servicios iniciales
INSERT INTO services_catalog (service_name, service_type, suggested_price, currency, description, observations, display_order, is_active) VALUES
('Gestión de Redes Sociales', 'redes_sociales', 5000.00, 'MXN', 'Gestión completa de redes sociales: Facebook, Instagram, LinkedIn, etc.', 'Incluye diseño de publicaciones, programación y engagement', 1, TRUE),
('Community Manager', 'community_manager', 8000.00, 'MXN', 'Gestión profesional de comunidad en redes sociales', 'Respuesta a comentarios, mensajes y moderación', 2, TRUE),
('Diseño Web', 'diseno_web', 15000.00, 'MXN', 'Diseño y desarrollo de sitios web responsivos', 'Incluye diseño, desarrollo y optimización', 3, TRUE),
('Campañas de Ads', 'ads', 10000.00, 'MXN', 'Gestión de campañas publicitarias en Google Ads y Facebook Ads', 'Incluye configuración, optimización y reportes', 4, TRUE),
('Branding Corporativo', 'branding', 20000.00, 'MXN', 'Desarrollo de identidad visual corporativa', 'Logo, colores, tipografía y manual de marca', 5, TRUE),
('Chatbot para WhatsApp', 'chatbot', 12000.00, 'MXN', 'Desarrollo e implementación de chatbot automatizado', 'Configuración de flujos y respuestas automáticas', 6, TRUE),
('SEO y Posicionamiento', 'seo', 6000.00, 'MXN', 'Optimización para motores de búsqueda', 'Análisis de keywords, optimización on-page y off-page', 7, TRUE),
('Content Marketing', 'content_marketing', 7000.00, 'MXN', 'Creación de contenido estratégico', 'Blog posts, artículos, infografías y contenido multimedia', 8, TRUE),
('Email Marketing', 'email_marketing', 5000.00, 'MXN', 'Campañas de email marketing', 'Diseño de templates, segmentación y automatización', 9, TRUE),
('Consultoría Legal', 'consultoria_legal', 15000.00, 'MXN', 'Asesoría legal en protección de datos y cumplimiento', 'LOPD, GDPR y normativas aplicables', 10, TRUE),
('Auditoría de Datos', 'auditoria_datos', 18000.00, 'MXN', 'Auditoría de seguridad y protección de datos', 'Análisis de riesgos y cumplimiento normativo', 11, TRUE);

