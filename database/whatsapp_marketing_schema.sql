-- =====================================================
-- SOPHEA - WhatsApp Marketing Module Database Schema
-- =====================================================
-- Este script crea las tablas necesarias para el módulo
-- de Marketing por WhatsApp Business API
-- 
-- Fecha: 2024
-- =====================================================

USE sophea_db;

-- =====================================================
-- Table: whatsapp_campaigns
-- Almacena información de las campañas de marketing
-- =====================================================
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
    
    -- Métricas
    total_recipients INT DEFAULT 0 COMMENT 'Total de destinatarios',
    total_sent INT DEFAULT 0 COMMENT 'Total de mensajes enviados',
    total_delivered INT DEFAULT 0 COMMENT 'Total de mensajes entregados',
    total_read INT DEFAULT 0 COMMENT 'Total de mensajes leídos',
    total_replied INT DEFAULT 0 COMMENT 'Total de respuestas recibidas',
    total_failed INT DEFAULT 0 COMMENT 'Total de mensajes fallidos',
    
    -- Configuración
    filter_criteria JSON COMMENT 'Criterios de segmentación en formato JSON',
    send_immediately BOOLEAN DEFAULT FALSE COMMENT 'Enviar inmediatamente o programar',
    respect_business_hours BOOLEAN DEFAULT TRUE COMMENT 'Respetar horarios de atención',
    exclude_weekends BOOLEAN DEFAULT FALSE COMMENT 'Excluir fines de semana',
    
    -- Costos
    estimated_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo estimado de la campaña',
    actual_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo real de la campaña',
    
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Campañas de marketing por WhatsApp';

-- =====================================================
-- Table: whatsapp_campaign_recipients
-- Almacena los destinatarios de cada campaña y su estado
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_campaign_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    lead_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL COMMENT 'Número de teléfono normalizado',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    
    -- IDs de WhatsApp
    message_id VARCHAR(100) COMMENT 'ID del mensaje de WhatsApp',
    waba_message_id VARCHAR(100) COMMENT 'ID del mensaje en WABA',
    
    -- Timestamps
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    replied_at DATETIME NULL,
    failed_at DATETIME NULL,
    
    -- Información de error
    error_code VARCHAR(50) COMMENT 'Código de error de WhatsApp',
    error_message TEXT COMMENT 'Mensaje de error detallado',
    
    -- Personalización
    personalized_message TEXT COMMENT 'Mensaje personalizado para este destinatario',
    variables_used JSON COMMENT 'Variables reemplazadas en el mensaje',
    
    -- Métricas
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

-- =====================================================
-- Table: whatsapp_credits
-- Registro diario de créditos disponibles y usados
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL COMMENT 'Fecha del registro',
    credits_available INT DEFAULT 0 COMMENT 'Créditos disponibles al inicio del día',
    credits_used INT DEFAULT 0 COMMENT 'Créditos usados en el día',
    credits_remaining INT DEFAULT 0 COMMENT 'Créditos restantes al final del día',
    
    -- Costos
    cost_per_message DECIMAL(10,4) DEFAULT 0 COMMENT 'Costo por mensaje en USD',
    total_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo total del día',
    
    -- Métricas
    messages_sent INT DEFAULT 0 COMMENT 'Mensajes enviados',
    messages_delivered INT DEFAULT 0 COMMENT 'Mensajes entregados',
    messages_failed INT DEFAULT 0 COMMENT 'Mensajes fallidos',
    
    -- Información adicional
    last_sync_at DATETIME NULL COMMENT 'Última sincronización con API de Meta',
    notes TEXT COMMENT 'Notas adicionales',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro diario de créditos de WhatsApp';

-- =====================================================
-- Table: whatsapp_templates_custom
-- Plantillas personalizadas de mensajes
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_templates_custom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la plantilla',
    category ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'bienvenida', 'otro') NOT NULL,
    template_text TEXT NOT NULL COMMENT 'Texto de la plantilla con variables',
    variables JSON COMMENT 'Lista de variables disponibles en formato JSON',
    example_data JSON COMMENT 'Datos de ejemplo para vista previa',
    
    -- Configuración
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE COMMENT 'Si requiere aprobación de Meta',
    approved_at DATETIME NULL,
    
    -- Metadatos
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Plantillas personalizadas de WhatsApp';

-- =====================================================
-- Table: whatsapp_automation_rules
-- Reglas de automatización para envíos automáticos
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la regla',
    description TEXT COMMENT 'Descripción de la regla',
    
    -- Condición de activación
    trigger_type ENUM('lead_created', 'lead_status_changed', 'days_since_contact', 'days_since_created', 'custom') NOT NULL,
    trigger_condition JSON NOT NULL COMMENT 'Condiciones en formato JSON',
    
    -- Acción a ejecutar
    action_type ENUM('send_message', 'send_template', 'update_status', 'add_tag', 'add_note') NOT NULL,
    action_config JSON NOT NULL COMMENT 'Configuración de la acción en formato JSON',
    
    -- Configuración
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0 COMMENT 'Prioridad de ejecución (mayor = primero)',
    delay_minutes INT DEFAULT 0 COMMENT 'Retraso en minutos antes de ejecutar',
    
    -- Estadísticas
    times_executed INT DEFAULT 0 COMMENT 'Veces que se ha ejecutado',
    last_executed_at DATETIME NULL,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_is_active (is_active),
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reglas de automatización de WhatsApp';

-- =====================================================
-- Table: whatsapp_automation_log
-- Log de ejecuciones de reglas de automatización
-- =====================================================
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

-- =====================================================
-- Table: whatsapp_contact_lists
-- Listas de contactos personalizadas para segmentación
-- =====================================================
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

-- =====================================================
-- Table: whatsapp_contact_list_members
-- Miembros de las listas de contactos
-- =====================================================
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

-- =====================================================
-- Table: whatsapp_lead_tags
-- Sistema de etiquetas para leads
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_lead_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre de la etiqueta',
    color VARCHAR(7) DEFAULT '#667eea' COMMENT 'Color en hexadecimal',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Etiquetas disponibles para leads';

-- =====================================================
-- Table: whatsapp_lead_tag_assignments
-- Asignación de etiquetas a leads
-- =====================================================
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

-- =====================================================
-- Table: whatsapp_message_log
-- Log detallado de todos los mensajes enviados
-- =====================================================
CREATE TABLE IF NOT EXISTS whatsapp_message_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NULL COMMENT 'ID de campaña si aplica',
    lead_id INT NULL COMMENT 'ID del lead destinatario',
    phone_number VARCHAR(50) NOT NULL,
    message_type ENUM('template', 'text', 'interactive') NOT NULL,
    template_name VARCHAR(100) NULL,
    message_text TEXT,
    
    -- Estado del mensaje
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    message_id VARCHAR(100) NULL,
    waba_message_id VARCHAR(100) NULL,
    
    -- Timestamps
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    replied_at DATETIME NULL,
    
    -- Información de error
    error_code VARCHAR(50) NULL,
    error_message TEXT NULL,
    
    -- Costo
    cost DECIMAL(10,4) DEFAULT 0,
    
    -- Metadatos
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

-- =====================================================
-- Table: whatsapp_ab_tests
-- Tests A/B para optimizar mensajes
-- =====================================================
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
    
    -- Métricas
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

-- =====================================================
-- Table: whatsapp_scheduled_jobs
-- Trabajos programados para procesamiento de cola
-- =====================================================
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
-- Insertar datos iniciales
-- =====================================================

-- Etiquetas predeterminadas
INSERT INTO whatsapp_lead_tags (name, color, description) VALUES
('Interesado', '#10b981', 'Lead que ha mostrado interés'),
('Caliente', '#ef4444', 'Lead con alta probabilidad de conversión'),
('Frío', '#6b7280', 'Lead con baja probabilidad de conversión'),
('VIP', '#f59e0b', 'Cliente o lead VIP'),
('Seguimiento', '#3b82f6', 'Requiere seguimiento'),
('Sin Respuesta', '#9ca3af', 'No responde a mensajes')
ON DUPLICATE KEY UPDATE name=name;

-- =====================================================
-- Vistas útiles
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
-- Nota: Usa ANY_VALUE() para compatibilidad con MySQL 5.7.2+
CREATE OR REPLACE VIEW v_whatsapp_active_campaigns AS
SELECT 
    ANY_VALUE(c.id) as id,
    ANY_VALUE(c.name) as name,
    ANY_VALUE(c.type) as type,
    ANY_VALUE(c.template_name) as template_name,
    ANY_VALUE(c.message_text) as message_text,
    ANY_VALUE(c.status) as status,
    ANY_VALUE(c.scheduled_at) as scheduled_at,
    ANY_VALUE(c.sent_at) as sent_at,
    ANY_VALUE(c.completed_at) as completed_at,
    ANY_VALUE(c.created_by) as created_by,
    ANY_VALUE(c.created_at) as created_at,
    ANY_VALUE(c.updated_at) as updated_at,
    ANY_VALUE(c.total_recipients) as total_recipients,
    ANY_VALUE(c.total_sent) as total_sent,
    ANY_VALUE(c.total_delivered) as total_delivered,
    ANY_VALUE(c.total_read) as total_read,
    ANY_VALUE(c.total_replied) as total_replied,
    ANY_VALUE(c.total_failed) as total_failed,
    ANY_VALUE(c.filter_criteria) as filter_criteria,
    ANY_VALUE(c.send_immediately) as send_immediately,
    ANY_VALUE(c.respect_business_hours) as respect_business_hours,
    ANY_VALUE(c.exclude_weekends) as exclude_weekends,
    ANY_VALUE(c.estimated_cost) as estimated_cost,
    ANY_VALUE(c.actual_cost) as actual_cost,
    COUNT(DISTINCT r.id) as calculated_total_recipients,
    SUM(CASE WHEN r.status = 'sent' THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN r.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN r.status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN r.status = 'replied' THEN 1 ELSE 0 END) as replied_count
FROM whatsapp_campaigns c
LEFT JOIN whatsapp_campaign_recipients r ON c.id = r.campaign_id
WHERE c.status IN ('scheduled', 'sending')
GROUP BY c.id;

-- =====================================================
-- Procedimientos almacenados útiles
-- =====================================================

DELIMITER //

-- Procedimiento: Actualizar métricas de campaña
CREATE PROCEDURE IF NOT EXISTS sp_update_campaign_metrics(IN campaign_id INT)
BEGIN
    UPDATE whatsapp_campaigns c
    SET 
        c.total_sent = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('sent', 'delivered', 'read', 'replied')),
        c.total_delivered = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('delivered', 'read', 'replied')),
        c.total_read = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('read', 'replied')),
        c.total_replied = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status = 'replied'),
        c.total_failed = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status = 'failed')
    WHERE c.id = campaign_id;
END //

-- Procedimiento: Sincronizar créditos diarios
CREATE PROCEDURE IF NOT EXISTS sp_sync_daily_credits(IN p_date DATE, IN p_available INT, IN p_used INT, IN p_cost DECIMAL(10,2))
BEGIN
    INSERT INTO whatsapp_credits (date, credits_available, credits_used, credits_remaining, total_cost, last_sync_at)
    VALUES (p_date, p_available, p_used, p_available - p_used, p_cost, NOW())
    ON DUPLICATE KEY UPDATE
        credits_available = p_available,
        credits_used = p_used,
        credits_remaining = p_available - p_used,
        total_cost = p_cost,
        last_sync_at = NOW();
END //

DELIMITER ;

-- =====================================================
-- Índices adicionales para optimización
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_campaign_recipients_status ON whatsapp_campaign_recipients(campaign_id, status);
CREATE INDEX idx_message_log_lead_status ON whatsapp_message_log(lead_id, status);
CREATE INDEX idx_message_log_date_status ON whatsapp_message_log(created_at, status);

