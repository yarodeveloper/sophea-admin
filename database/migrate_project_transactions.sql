-- =====================================================
-- SOPHEA - Migración: Sistema de Transacciones de Proyectos (Ads)
-- =====================================================
-- Este script implementa el sistema de gestión financiera para proyectos de Ads
-- que diferencia entre Honorarios de Gestión e Inversión Publicitaria

USE sophea_db;

-- =====================================================
-- 1. Modificar tabla services: Agregar flag y campos
-- =====================================================

-- Agregar flag para identificar servicios de Ads
ALTER TABLE services 
ADD COLUMN is_ads_service BOOLEAN DEFAULT FALSE COMMENT 'Si es servicio de Ads con inversión de terceros';

-- Agregar campos para montos iniciales (opcional, para proyectos nuevos)
ALTER TABLE services 
ADD COLUMN initial_management_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Monto inicial de honorarios de gestión',
ADD COLUMN initial_investment_amount DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Monto inicial de inversión publicitaria';

-- Agregar campo para presupuesto consumido (simplificado)
ALTER TABLE services 
ADD COLUMN consumed_budget DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Presupuesto consumido en plataformas (Meta/Google)';

-- Agregar índice para mejorar consultas
ALTER TABLE services 
ADD INDEX idx_is_ads_service (is_ads_service);

-- =====================================================
-- 2. Crear tabla project_transactions
-- =====================================================

CREATE TABLE IF NOT EXISTS project_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL COMMENT 'Proyecto/Servicio asociado',
    client_id INT NOT NULL COMMENT 'Cliente (redundante pero útil para queries)',
    transaction_type ENUM(
        'income_fee',           -- Honorarios de gestión (va a reporte de ventas)
        'income_ads',            -- Inversión publicitaria (billetera virtual)
        'expense_ads_consumed'   -- Presupuesto consumido (simplificado, no pagos individuales)
    ) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto positivo o negativo',
    currency VARCHAR(3) DEFAULT 'MXN',
    description TEXT COMMENT 'Descripción de la transacción',
    
    -- Relación con payment original (si aplica)
    payment_id INT NULL COMMENT 'ID del pago que originó esta transacción',
    
    -- Para expense_ads_consumed (simplificado)
    platform ENUM('facebook', 'whatsapp', 'google', 'tiktok', 'linkedin', 'other') NULL,
    billing_period_start DATE NULL COMMENT 'Inicio del período facturado',
    billing_period_end DATE NULL COMMENT 'Fin del período facturado',
    
    -- Metadata
    transaction_date DATE NOT NULL COMMENT 'Fecha de la transacción',
    reference_number VARCHAR(100) NULL COMMENT 'Número de referencia',
    notes TEXT COMMENT 'Notas adicionales',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del usuario que creó la transacción',
    
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    
    INDEX idx_service_id (service_id),
    INDEX idx_client_id (client_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Transacciones financieras de proyectos (honorarios, inversión publicitaria, presupuesto consumido)';

-- =====================================================
-- 3. Comentarios y validaciones
-- =====================================================

-- Notas importantes:
-- - Solo servicios con is_ads_service = TRUE usan este sistema
-- - income_fee: Honorarios de gestión (va a reporte de ingresos brutos)
-- - income_ads: Fondo para inversión (acumula en Saldo en Custodia)
-- - expense_ads_consumed: Presupuesto consumido (simplificado, no pagos individuales)
-- - Saldo en Custodia = SUM(income_ads) - SUM(expense_ads_consumed)
-- - Si saldo negativo: alerta visual (estás financiando al cliente)

-- =====================================================
-- 4. Vista para Saldo en Custodia (opcional, puede hacerse en PHP)
-- =====================================================

-- Esta vista puede ser útil para consultas rápidas
CREATE OR REPLACE VIEW v_project_custody_balance AS
SELECT 
    s.id as service_id,
    s.service_name,
    s.client_id,
    c.company_name,
    s.is_ads_service,
    COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) as total_investment,
    COALESCE(SUM(CASE WHEN pt.transaction_type = 'expense_ads_consumed' THEN ABS(pt.amount) ELSE 0 END), 0) as total_consumed,
    (COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) - 
     COALESCE(SUM(CASE WHEN pt.transaction_type = 'expense_ads_consumed' THEN ABS(pt.amount) ELSE 0 END), 0)) as custody_balance,
    COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_fee' THEN pt.amount ELSE 0 END), 0) as total_fees
FROM services s
LEFT JOIN clients c ON s.client_id = c.id
LEFT JOIN project_transactions pt ON s.id = pt.service_id
WHERE s.is_ads_service = TRUE
GROUP BY s.id, s.service_name, s.client_id, c.company_name, s.is_ads_service;

