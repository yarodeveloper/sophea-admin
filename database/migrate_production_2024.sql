-- =====================================================
-- SOPHEA - Script de Migración para Producción
-- =====================================================
-- Este script actualiza la base de datos con todas las
-- tablas nuevas creadas para el sistema completo
-- 
-- IMPORTANTE: 
-- - Haz un backup de tu base de datos antes de ejecutar
-- - Este script es seguro de ejecutar múltiples veces
-- - Verifica que todas las tablas se creen correctamente
-- 
-- Fecha: 2024
-- =====================================================

USE sophea_db;

-- =====================================================
-- 1. TABLAS DE WHATSAPP MARKETING
-- =====================================================

-- Table: whatsapp_campaigns
CREATE TABLE IF NOT EXISTS whatsapp_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la campaña',
    type ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'personalizado') NOT NULL COMMENT 'Tipo de campaña',
    template_name VARCHAR(100) COMMENT 'Nombre de la plantilla de WhatsApp',
    message_text TEXT COMMENT 'Texto del mensaje (si no usa plantilla)',
    status ENUM('draft', 'scheduled', 'sending', 'completed', 'paused', 'cancelled') DEFAULT 'draft' COMMENT 'Estado de la campaña',
    scheduled_at DATETIME COMMENT 'Fecha y hora programada para el envío',
    sent_at DATETIME NULL COMMENT 'Fecha y hora en que se inició el envío',
    completed_at DATETIME NULL COMMENT 'Fecha y hora en que se completó',
    created_by INT COMMENT 'ID del usuario que creó la campaña',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_recipients INT DEFAULT 0 COMMENT 'Total de destinatarios',
    total_sent INT DEFAULT 0 COMMENT 'Total de mensajes enviados',
    total_delivered INT DEFAULT 0 COMMENT 'Total de mensajes entregados',
    total_read INT DEFAULT 0 COMMENT 'Total de mensajes leídos',
    total_replied INT DEFAULT 0 COMMENT 'Total de respuestas recibidas',
    total_failed INT DEFAULT 0 COMMENT 'Total de mensajes fallidos',
    filter_criteria JSON COMMENT 'Criterios de segmentación en formato JSON',
    send_immediately BOOLEAN DEFAULT FALSE COMMENT 'Enviar inmediatamente o programar',
    respect_business_hours BOOLEAN DEFAULT TRUE COMMENT 'Respetar horarios de atención',
    exclude_weekends BOOLEAN DEFAULT FALSE COMMENT 'Excluir fines de semana',
    estimated_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo estimado de la campaña',
    actual_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo real de la campaña',
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Campañas de marketing por WhatsApp';

-- Table: whatsapp_campaign_recipients
CREATE TABLE IF NOT EXISTS whatsapp_campaign_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    lead_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL COMMENT 'Número de teléfono normalizado',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    message_id VARCHAR(100) COMMENT 'ID del mensaje de WhatsApp',
    waba_message_id VARCHAR(100) COMMENT 'ID del mensaje en WABA',
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    replied_at DATETIME NULL,
    failed_at DATETIME NULL,
    error_code VARCHAR(50) COMMENT 'Código de error de WhatsApp',
    error_message TEXT COMMENT 'Mensaje de error detallado',
    personalized_message TEXT COMMENT 'Mensaje personalizado para este destinatario',
    variables_used JSON COMMENT 'Variables reemplazadas en el mensaje',
    delivery_attempts INT DEFAULT 0 COMMENT 'Intentos de entrega',
    last_attempt_at DATETIME NULL,
    FOREIGN KEY (campaign_id) REFERENCES whatsapp_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_status (status),
    INDEX idx_phone_number (phone_number(20)),
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Destinatarios de campañas de WhatsApp';

-- Table: whatsapp_credits
CREATE TABLE IF NOT EXISTS whatsapp_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL COMMENT 'Fecha del registro',
    credits_available INT DEFAULT 0 COMMENT 'Créditos disponibles al inicio del día',
    credits_used INT DEFAULT 0 COMMENT 'Créditos usados en el día',
    credits_remaining INT DEFAULT 0 COMMENT 'Créditos restantes al final del día',
    cost_per_message DECIMAL(10,4) DEFAULT 0 COMMENT 'Costo por mensaje en USD',
    total_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo total del día',
    messages_sent INT DEFAULT 0 COMMENT 'Mensajes enviados',
    messages_delivered INT DEFAULT 0 COMMENT 'Mensajes entregados',
    messages_failed INT DEFAULT 0 COMMENT 'Mensajes fallidos',
    last_sync_at DATETIME NULL COMMENT 'Última sincronización con API de Meta',
    notes TEXT COMMENT 'Notas adicionales',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro diario de créditos de WhatsApp';

-- Table: whatsapp_templates_custom
CREATE TABLE IF NOT EXISTS whatsapp_templates_custom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la plantilla',
    category ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'bienvenida', 'otro') NOT NULL,
    template_text TEXT NOT NULL COMMENT 'Texto de la plantilla con variables',
    variables JSON COMMENT 'Lista de variables disponibles en formato JSON',
    example_data JSON COMMENT 'Datos de ejemplo para vista previa',
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE COMMENT 'Si requiere aprobación de Meta',
    approved_at DATETIME NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Plantillas personalizadas de WhatsApp';

-- Table: whatsapp_automation_rules
CREATE TABLE IF NOT EXISTS whatsapp_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la regla',
    description TEXT COMMENT 'Descripción de la regla',
    trigger_type ENUM('lead_created', 'lead_status_changed', 'days_since_contact', 'days_since_created', 'custom') NOT NULL,
    trigger_condition JSON NOT NULL COMMENT 'Condiciones en formato JSON',
    action_type ENUM('send_message', 'send_template', 'update_status', 'add_tag', 'add_note') NOT NULL,
    action_config JSON NOT NULL COMMENT 'Configuración de la acción en formato JSON',
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0 COMMENT 'Prioridad de ejecución (mayor = primero)',
    delay_minutes INT DEFAULT 0 COMMENT 'Retraso en minutos antes de ejecutar',
    times_executed INT DEFAULT 0 COMMENT 'Veces que se ha ejecutado',
    last_executed_at DATETIME NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reglas de automatización de WhatsApp';

-- Table: whatsapp_automation_log
CREATE TABLE IF NOT EXISTS whatsapp_automation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    lead_id INT NOT NULL,
    execution_status ENUM('success', 'failed', 'skipped') NOT NULL,
    execution_message TEXT COMMENT 'Mensaje de resultado',
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES whatsapp_automation_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_executed_at (executed_at),
    INDEX idx_execution_status (execution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de ejecuciones de automatización';

-- Table: whatsapp_contact_lists
CREATE TABLE IF NOT EXISTS whatsapp_contact_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la lista',
    description TEXT COMMENT 'Descripción de la lista',
    filter_criteria JSON COMMENT 'Criterios de filtrado en formato JSON',
    total_contacts INT DEFAULT 0 COMMENT 'Total de contactos en la lista',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Listas de contactos para segmentación';

-- Table: whatsapp_contact_list_members
CREATE TABLE IF NOT EXISTS whatsapp_contact_list_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    lead_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES whatsapp_contact_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    UNIQUE KEY unique_list_lead (list_id, lead_id),
    INDEX idx_list_id (list_id),
    INDEX idx_lead_id (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Miembros de listas de contactos';

-- Table: whatsapp_lead_tags
CREATE TABLE IF NOT EXISTS whatsapp_lead_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre de la etiqueta',
    color VARCHAR(7) DEFAULT '#667eea' COMMENT 'Color en hexadecimal',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Etiquetas disponibles para leads';

-- Table: whatsapp_lead_tag_assignments
CREATE TABLE IF NOT EXISTS whatsapp_lead_tag_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    tag_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES whatsapp_lead_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lead_tag (lead_id, tag_id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignaciones de etiquetas a leads';

-- Table: whatsapp_message_log
CREATE TABLE IF NOT EXISTS whatsapp_message_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NULL COMMENT 'ID de campaña si aplica',
    lead_id INT NULL COMMENT 'ID del lead destinatario',
    phone_number VARCHAR(50) NOT NULL,
    message_type ENUM('template', 'text', 'interactive') NOT NULL,
    template_name VARCHAR(100) NULL,
    message_text TEXT,
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    message_id VARCHAR(100) NULL,
    waba_message_id VARCHAR(100) NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    replied_at DATETIME NULL,
    error_code VARCHAR(50) NULL,
    error_message TEXT NULL,
    cost DECIMAL(10,4) DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_phone_number (phone_number(20)),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at),
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log detallado de mensajes de WhatsApp';

-- Table: whatsapp_ab_tests
CREATE TABLE IF NOT EXISTS whatsapp_ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    campaign_id INT NOT NULL,
    variant_a_message TEXT NOT NULL,
    variant_b_message TEXT NOT NULL,
    split_percentage INT DEFAULT 50 COMMENT 'Porcentaje para variante A (resto va a B)',
    status ENUM('running', 'completed', 'paused') DEFAULT 'running',
    winner_variant ENUM('A', 'B', 'tie') NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    variant_a_sent INT DEFAULT 0,
    variant_a_delivered INT DEFAULT 0,
    variant_a_read INT DEFAULT 0,
    variant_a_replied INT DEFAULT 0,
    variant_b_sent INT DEFAULT 0,
    variant_b_delivered INT DEFAULT 0,
    variant_b_read INT DEFAULT 0,
    variant_b_replied INT DEFAULT 0,
    FOREIGN KEY (campaign_id) REFERENCES whatsapp_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tests A/B de mensajes';

-- Table: whatsapp_scheduled_jobs
CREATE TABLE IF NOT EXISTS whatsapp_scheduled_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type ENUM('send_campaign', 'sync_credits', 'process_automation', 'update_statuses') NOT NULL,
    job_data JSON COMMENT 'Datos del trabajo en formato JSON',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    priority INT DEFAULT 0 COMMENT 'Prioridad (mayor = primero)',
    scheduled_at DATETIME NOT NULL COMMENT 'Cuándo debe ejecutarse',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_priority (priority),
    INDEX idx_job_type (job_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cola de trabajos programados';

-- =====================================================
-- 2. TABLAS DE TESTIMONIOS
-- =====================================================

-- Table: testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    client_title VARCHAR(255),
    client_company VARCHAR(255),
    client_location VARCHAR(255),
    client_avatar VARCHAR(500),
    testimonial_text TEXT NOT NULL,
    full_story LONGTEXT,
    slug VARCHAR(255) NOT NULL UNIQUE,
    featured_image VARCHAR(500),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    featured BOOLEAN DEFAULT 0,
    display_order INT DEFAULT 0,
    metric1_label VARCHAR(100),
    metric1_value VARCHAR(50),
    metric1_color VARCHAR(50) DEFAULT 'purple',
    metric2_label VARCHAR(100),
    metric2_value VARCHAR(50),
    metric2_color VARCHAR(50) DEFAULT 'blue',
    metric3_label VARCHAR(100),
    metric3_value VARCHAR(50),
    metric3_color VARCHAR(50) DEFAULT 'green',
    services_used TEXT,
    sector ENUM('salud', 'general', 'retail', 'servicios') DEFAULT 'general',
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    views INT DEFAULT 0,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_display_order (display_order),
    INDEX idx_published_at (published_at),
    INDEX idx_sector (sector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: testimonial_images
CREATE TABLE IF NOT EXISTS testimonial_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    testimonial_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (testimonial_id) REFERENCES testimonials(id) ON DELETE CASCADE,
    INDEX idx_testimonial_id (testimonial_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABLAS DE CONFIGURACIÓN DEL SITIO
-- =====================================================

-- Table: site_settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('image', 'text', 'url') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TABLAS DE BLOG
-- =====================================================

-- Table: blog_posts
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(500),
    author_id INT DEFAULT 1,
    author_name VARCHAR(255) DEFAULT 'SOPHEA',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_search (title, content, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blog_categories
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blog_post_categories
CREATE TABLE IF NOT EXISTS blog_post_categories (
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. TABLAS DE AUTENTICACIÓN
-- =====================================================

-- Table: login_attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. ACTUALIZAR TABLA LEADS (si falta updated_at)
-- =====================================================

-- Verificar y agregar columna updated_at si no existe
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
-- 7. DATOS INICIALES
-- =====================================================

-- Etiquetas predeterminadas de WhatsApp
INSERT IGNORE INTO whatsapp_lead_tags (name, color, description) VALUES
('Interesado', '#10b981', 'Lead que ha mostrado interés'),
('Caliente', '#ef4444', 'Lead con alta probabilidad de conversión'),
('Frío', '#6b7280', 'Lead con baja probabilidad de conversión'),
('VIP', '#f59e0b', 'Cliente o lead VIP'),
('Seguimiento', '#3b82f6', 'Requiere seguimiento'),
('Sin Respuesta', '#9ca3af', 'No responde a mensajes');

-- Configuración inicial del sitio
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES
('main_banner', '', 'image'),
('main_logo', '', 'image');

-- Categorías predeterminadas del blog
INSERT IGNORE INTO blog_categories (name, slug, description) VALUES
('Compliance COFEPRIS', 'compliance-cofepris', 'Artículos sobre regulaciones y compliance COFEPRIS'),
('Marketing Digital', 'marketing-digital', 'Estrategias y técnicas de marketing digital'),
('Sector Salud', 'sector-salud', 'Contenido especializado para profesionales de la salud'),
('Casos de Éxito', 'casos-exito', 'Historias de éxito de nuestros clientes'),
('Guías y Tutoriales', 'guias-tutoriales', 'Guías prácticas y tutoriales paso a paso');

-- =====================================================
-- 8. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX IF NOT EXISTS idx_campaign_recipients_status ON whatsapp_campaign_recipients(campaign_id, status);
CREATE INDEX IF NOT EXISTS idx_message_log_lead_status ON whatsapp_message_log(lead_id, status);
CREATE INDEX IF NOT EXISTS idx_message_log_date_status ON whatsapp_message_log(created_at, status);

-- =====================================================
-- 9. VISTAS ÚTILES
-- =====================================================

-- Vista: Resumen de créditos del mes actual
CREATE OR REPLACE VIEW v_whatsapp_credits_monthly AS
SELECT 
    DATE_FORMAT(date, '%Y-%m') as month,
    SUM(credits_used) as total_credits_used,
    SUM(total_cost) as total_cost,
    AVG(cost_per_message) as avg_cost_per_message,
    SUM(messages_sent) as total_messages_sent,
    SUM(messages_delivered) as total_messages_delivered,
    SUM(messages_failed) as total_messages_failed
FROM whatsapp_credits
WHERE date >= DATE_FORMAT(NOW(), '%Y-%m-01')
GROUP BY DATE_FORMAT(date, '%Y-%m');

-- Vista: Campañas activas
-- NOTA: Esta versión es compatible con MySQL 5.6 y anteriores
-- (No usa ANY_VALUE() porque no está disponible en versiones antiguas)
-- Si tu MySQL es 5.7.2+, puedes usar la versión con ANY_VALUE() del archivo
-- database/CREAR_VISTA_AHORA.sql
CREATE OR REPLACE VIEW v_whatsapp_active_campaigns AS
SELECT 
    c.id,
    c.name,
    c.type,
    c.template_name,
    c.message_text,
    c.status,
    c.scheduled_at,
    c.sent_at,
    c.completed_at,
    c.created_by,
    c.created_at,
    c.updated_at,
    c.total_recipients,
    c.total_sent,
    c.total_delivered,
    c.total_read,
    c.total_replied,
    c.total_failed,
    c.filter_criteria,
    c.send_immediately,
    c.respect_business_hours,
    c.exclude_weekends,
    c.estimated_cost,
    c.actual_cost,
    COUNT(DISTINCT r.id) as calculated_total_recipients,
    SUM(CASE WHEN r.status = 'sent' THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN r.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN r.status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN r.status = 'replied' THEN 1 ELSE 0 END) as replied_count
FROM whatsapp_campaigns c
LEFT JOIN whatsapp_campaign_recipients r ON c.id = r.campaign_id
WHERE c.status IN ('scheduled', 'sending')
GROUP BY c.id, c.name, c.type, c.template_name, c.message_text, c.status, 
         c.scheduled_at, c.sent_at, c.completed_at, c.created_by, c.created_at, 
         c.updated_at, c.total_recipients, c.total_sent, c.total_delivered, 
         c.total_read, c.total_replied, c.total_failed, c.filter_criteria, 
         c.send_immediately, c.respect_business_hours, c.exclude_weekends, 
         c.estimated_cost, c.actual_cost;

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
-- 
-- Verificación: Ejecuta estas consultas para verificar que todo se creó correctamente
-- 
-- SELECT COUNT(*) as total_tablas FROM information_schema.tables 
-- WHERE table_schema = 'sophea_db';
-- 
-- SHOW TABLES;
-- 
-- =====================================================

