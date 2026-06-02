-- =====================================================
-- SOPHEA - Sistema de Gestión de Clientes y Cotizaciones
-- Schema Completo v2.0
-- =====================================================
-- Este script crea todas las tablas necesarias para el
-- sistema de gestión de clientes, cotizaciones, servicios y pagos
-- 
-- Uso:
--   mysql -u username -p sophea_db < clients_quotes_schema.sql
--   O importar vía phpMyAdmin
-- =====================================================

USE sophea_db;

-- =====================================================
-- Tabla: clients (Clientes)
-- =====================================================
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'ID único: C-YYYY-XXX',
    company_name VARCHAR(255) NOT NULL COMMENT 'Nombre de la empresa',
    contact_name VARCHAR(255) NOT NULL COMMENT 'Nombre del contacto principal',
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    whatsapp VARCHAR(50),
    address TEXT COMMENT 'Dirección completa',
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'México',
    tax_id VARCHAR(50) COMMENT 'RFC o ID fiscal',
    website VARCHAR(255),
    industry VARCHAR(100) COMMENT 'Industria o sector',
    client_type ENUM('prospect', 'regular', 'strategic_partner') DEFAULT 'regular',
    legal_risk ENUM('low', 'medium', 'high') DEFAULT 'low',
    legal_compliance DECIMAL(5, 2) DEFAULT 100.00 COMMENT 'Porcentaje de cumplimiento legal',
    last_audit_date DATE NULL COMMENT 'Fecha de última auditoría',
    status ENUM('prospect', 'active', 'inactive', 'archived') DEFAULT 'prospect',
    notes TEXT COMMENT 'Notas generales del cliente',
    logo_url VARCHAR(500) COMMENT 'URL del logo del cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del admin que creó el cliente',
    INDEX idx_status (status),
    INDEX idx_client_number (client_number),
    INDEX idx_company_name (company_name),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    INDEX idx_client_type (client_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de clientes';

-- =====================================================
-- Tabla: quotes (Cotizaciones)
-- =====================================================
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número único: COT-YYYY-MM-XXXX',
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Título de la cotización',
    description TEXT COMMENT 'Descripción general',
    subtotal DECIMAL(10, 2) DEFAULT 0.00,
    tax_rate DECIMAL(5, 2) DEFAULT 16.00 COMMENT 'IVA en porcentaje',
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'MXN',
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    valid_until DATE COMMENT 'Fecha de validez',
    sent_at DATETIME NULL,
    accepted_at DATETIME NULL,
    rejected_at DATETIME NULL,
    notes TEXT COMMENT 'Notas internas',
    terms_conditions TEXT COMMENT 'Términos y condiciones',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_quote_number (quote_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de cotizaciones';

-- =====================================================
-- Tabla: quote_items (Items de Cotización)
-- =====================================================
CREATE TABLE IF NOT EXISTS quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL COMMENT 'Tipo de servicio',
    description TEXT NOT NULL COMMENT 'Descripción del item',
    quantity DECIMAL(10, 2) DEFAULT 1.00,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL COMMENT 'quantity * unit_price',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_quote_id (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items de cada cotización';

-- =====================================================
-- Tabla: services (Servicios Activos)
-- =====================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    quote_id INT NULL COMMENT 'Cotización que originó este servicio',
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
    ) NOT NULL,
    service_name VARCHAR(255) NOT NULL COMMENT 'Nombre específico del servicio',
    description TEXT,
    project_description TEXT COMMENT 'Descripción completa del proyecto y alcance',
    monthly_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa mensual',
    setup_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa de configuración inicial',
    billing_cycle ENUM('monthly', 'quarterly', 'yearly', 'one_time') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL si es servicio continuo',
    renewal_date DATE NULL COMMENT 'Fecha de renovación para contratos mensuales',
    progress_percentage INT DEFAULT 0 COMMENT 'Porcentaje de avance del proyecto',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    project_url VARCHAR(500) COMMENT 'URL a Canva, Figma, etc.',
    legal_coverage TEXT COMMENT 'Información sobre cobertura de riesgo legal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_renewal_date (renewal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Servicios activos por cliente';

-- =====================================================
-- Tabla: service_tasks (Tareas del Proyecto)
-- =====================================================
CREATE TABLE IF NOT EXISTS service_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    is_completed BOOLEAN DEFAULT FALSE,
    due_date DATE NULL,
    completed_at DATETIME NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_service_id (service_id),
    INDEX idx_is_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas/checklist de cada proyecto';

-- =====================================================
-- Tabla: payments (Pagos)
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_id INT NULL COMMENT 'Pago asociado a un servicio específico',
    quote_id INT NULL COMMENT 'Pago asociado a una cotización',
    invoice_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de factura: #XXXX',
    payment_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de pago único: PAY-YYYY-MM-XXXX',
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    payment_method ENUM('transfer', 'cash', 'card', 'check', 'other') DEFAULT 'transfer',
    payment_date DATE NOT NULL,
    due_date DATE NULL COMMENT 'Fecha de vencimiento si es pago pendiente',
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    paid_at DATETIME NULL,
    reference_number VARCHAR(100) COMMENT 'Número de referencia bancaria',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_id (service_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_due_date (due_date),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagos realizados y pendientes';

-- =====================================================
-- Tabla: documents (Documentos Adjuntos)
-- =====================================================
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('client', 'quote', 'service', 'payment') NOT NULL,
    entity_id INT NOT NULL COMMENT 'ID del cliente, cotización, servicio o pago',
    document_type ENUM('quote', 'contract', 'invoice', 'receipt', 'confidentiality_agreement', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'Ruta relativa al archivo',
    file_size INT COMMENT 'Tamaño en bytes',
    mime_type VARCHAR(100),
    description TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos adjuntos (PDFs, contratos, etc.)';

-- =====================================================
-- Tabla: daily_tasks (Tareas Diarias / Seguimiento)
-- =====================================================
CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    task_type ENUM('call', 'meeting', 'email', 'invoice', 'follow_up', 'other') DEFAULT 'follow_up',
    related_client_id INT NULL,
    related_service_id INT NULL,
    due_date DATE NOT NULL,
    due_time TIME NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at DATETIME NULL,
    priority ENUM('low', 'normal', 'urgent') DEFAULT 'normal',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (related_client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (related_service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_due_date (due_date),
    INDEX idx_is_completed (is_completed),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas diarias y seguimiento';

-- =====================================================
-- Tabla: client_notes (Notas de Cliente)
-- =====================================================
CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    note_text TEXT NOT NULL,
    note_type ENUM('general', 'call', 'meeting', 'email', 'task', 'alert') DEFAULT 'general',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notas y seguimiento de clientes';

-- =====================================================
-- Vistas Útiles
-- =====================================================

-- Vista: Resumen de Clientes
CREATE OR REPLACE VIEW v_client_summary AS
SELECT 
    c.id,
    c.client_number,
    c.company_name,
    c.status,
    c.client_type,
    COUNT(DISTINCT s.id) as active_services_count,
    COUNT(DISTINCT q.id) as total_quotes,
    COUNT(DISTINCT CASE WHEN q.status = 'pending' OR q.status = 'sent' THEN q.id END) as pending_quotes,
    COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) as total_pending,
    COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as total_paid
FROM clients c
LEFT JOIN services s ON c.id = s.client_id AND s.status = 'active'
LEFT JOIN quotes q ON c.id = q.client_id
LEFT JOIN payments p ON c.id = p.client_id
GROUP BY c.id;

-- Vista: Resumen de Ingresos por Servicio
CREATE OR REPLACE VIEW v_revenue_by_service AS
SELECT 
    s.service_type,
    COUNT(DISTINCT s.id) as service_count,
    COUNT(DISTINCT s.client_id) as client_count,
    SUM(s.monthly_fee) as total_monthly_revenue,
    COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) as total_pending
FROM services s
LEFT JOIN payments p ON s.id = p.service_id
WHERE s.status = 'active'
GROUP BY s.service_type;

-- =====================================================
-- Procedimientos Almacenados
-- =====================================================

-- Procedimiento: Generar número de cliente único
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_generate_client_number(OUT client_number VARCHAR(50))
BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE sequence_num INT;
    
    SET year_part = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(client_number, 7) AS UNSIGNED)), 0) + 1
    INTO sequence_num
    FROM clients
    WHERE client_number LIKE CONCAT('C-', year_part, '-%');
    
    SET client_number = CONCAT('C-', year_part, '-', LPAD(sequence_num, 3, '0'));
END//
DELIMITER ;

-- Procedimiento: Generar número de cotización único
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_generate_quote_number(OUT quote_number VARCHAR(50))
BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE month_part VARCHAR(2);
    DECLARE sequence_num INT;
    
    SET year_part = YEAR(CURDATE());
    SET month_part = LPAD(MONTH(CURDATE()), 2, '0');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(quote_number, 13) AS UNSIGNED)), 0) + 1
    INTO sequence_num
    FROM quotes
    WHERE quote_number LIKE CONCAT('COT-', year_part, '-', month_part, '-%');
    
    SET quote_number = CONCAT('COT-', year_part, '-', month_part, '-', LPAD(sequence_num, 4, '0'));
END//
DELIMITER ;

-- Procedimiento: Generar número de pago único
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_generate_payment_number(OUT payment_number VARCHAR(50))
BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE month_part VARCHAR(2);
    DECLARE sequence_num INT;
    
    SET year_part = YEAR(CURDATE());
    SET month_part = LPAD(MONTH(CURDATE()), 2, '0');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(payment_number, 13) AS UNSIGNED)), 0) + 1
    INTO sequence_num
    FROM payments
    WHERE payment_number LIKE CONCAT('PAY-', year_part, '-', month_part, '-%');
    
    SET payment_number = CONCAT('PAY-', year_part, '-', month_part, '-', LPAD(sequence_num, 4, '0'));
END//
DELIMITER ;

-- Procedimiento: Generar número de factura único
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_generate_invoice_number(OUT invoice_number VARCHAR(50))
BEGIN
    DECLARE sequence_num INT;
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number, 2) AS UNSIGNED)), 0) + 1
    INTO sequence_num
    FROM payments
    WHERE invoice_number LIKE '#%';
    
    SET invoice_number = CONCAT('#', LPAD(sequence_num, 4, '0'));
END//
DELIMITER ;

-- =====================================================
-- Triggers
-- =====================================================

-- Trigger: Actualizar totales de cotización cuando se modifica un item
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_update_quote_totals
AFTER INSERT ON quote_items
FOR EACH ROW
BEGIN
    UPDATE quotes q
    SET q.subtotal = (
        SELECT COALESCE(SUM(total), 0) FROM quote_items WHERE quote_id = NEW.quote_id
    ),
    q.tax_amount = q.subtotal * (q.tax_rate / 100),
    q.total = q.subtotal + q.tax_amount
    WHERE q.id = NEW.quote_id;
END//

CREATE TRIGGER IF NOT EXISTS trg_update_quote_totals_update
AFTER UPDATE ON quote_items
FOR EACH ROW
BEGIN
    UPDATE quotes q
    SET q.subtotal = (
        SELECT COALESCE(SUM(total), 0) FROM quote_items WHERE quote_id = NEW.quote_id
    ),
    q.tax_amount = q.subtotal * (q.tax_rate / 100),
    q.total = q.subtotal + q.tax_amount
    WHERE q.id = NEW.quote_id;
END//

CREATE TRIGGER IF NOT EXISTS trg_update_quote_totals_delete
AFTER DELETE ON quote_items
FOR EACH ROW
BEGIN
    UPDATE quotes q
    SET q.subtotal = (
        SELECT COALESCE(SUM(total), 0) FROM quote_items WHERE quote_id = OLD.quote_id
    ),
    q.tax_amount = q.subtotal * (q.tax_rate / 100),
    q.total = q.subtotal + q.tax_amount
    WHERE q.id = OLD.quote_id;
END//
DELIMITER ;

-- Trigger: Actualizar estado de pago a "overdue" cuando pasa la fecha de vencimiento
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_check_overdue_payments
BEFORE UPDATE ON payments
FOR EACH ROW
BEGIN
    IF NEW.status = 'pending' AND NEW.due_date IS NOT NULL AND NEW.due_date < CURDATE() THEN
        SET NEW.status = 'overdue';
    END IF;
END//
DELIMITER ;

-- =====================================================
-- Datos de Prueba (Opcional - Comentar en producción)
-- =====================================================

-- INSERT INTO clients (client_number, company_name, contact_name, email, phone, status, client_type, legal_risk, legal_compliance) VALUES
-- ('C-2024-001', 'TechFlow Solutions', 'Juan Pérez', 'juan@techflow.com', '+52 961 123 4567', 'active', 'regular', 'low', 100.00),
-- ('C-2024-002', 'Green Earth', 'María González', 'maria@greenearth.com', '+52 961 234 5678', 'active', 'strategic_partner', 'low', 100.00),
-- ('C-2024-003', 'Urban Coffee', 'Carlos Rodríguez', 'carlos@urbancoffee.com', '+52 961 345 6789', 'active', 'regular', 'medium', 95.00);

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================

