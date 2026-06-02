-- =====================================================
-- SOPHEA - Actualización de Base de Datos
-- Cambios de la Última Semana
-- =====================================================
-- Este script consolida todos los cambios realizados
-- a la base de datos durante la última semana
-- =====================================================
-- Fecha: 2024
-- Descripción: 
--   1. Verificar/Crear tabla project_transactions (si no existe)
--   2. Verificar/Agregar columnas necesarias en services
--   3. Eliminación del campo redundante initial_management_fee
--   4. Corrección de transacciones income_ads sin service_id
--   5. Actualización de platform de 'facebook' a 'meta'
-- =====================================================

USE sophea_db;

-- =====================================================
-- 0. VERIFICAR/CREAR TABLA project_transactions
-- =====================================================
-- Asegurar que la tabla project_transactions existe
-- con todas las columnas necesarias

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
    platform ENUM('meta', 'facebook', 'whatsapp', 'google', 'tiktok', 'linkedin', 'other') NULL,
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
-- 0.1. VERIFICAR/AGREGAR COLUMNAS EN services
-- =====================================================
-- Asegurar que las columnas necesarias existen en services

-- Verificar si is_ads_service existe
SET @exist_is_ads_service = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'is_ads_service'
);

SET @sql = IF(@exist_is_ads_service = 0,
    'ALTER TABLE services ADD COLUMN is_ads_service BOOLEAN DEFAULT FALSE COMMENT ''Si es servicio de Ads con inversión de terceros''',
    'SELECT ''Columna is_ads_service ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si initial_investment_amount existe
SET @exist_initial_investment_amount = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'initial_investment_amount'
);

SET @sql = IF(@exist_initial_investment_amount = 0,
    'ALTER TABLE services ADD COLUMN initial_investment_amount DECIMAL(10, 2) DEFAULT 0.00 COMMENT ''Monto inicial de inversión publicitaria''',
    'SELECT ''Columna initial_investment_amount ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si consumed_budget existe
SET @exist_consumed_budget = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'consumed_budget'
);

SET @sql = IF(@exist_consumed_budget = 0,
    'ALTER TABLE services ADD COLUMN consumed_budget DECIMAL(10, 2) DEFAULT 0.00 COMMENT ''Presupuesto consumido en plataformas (Meta/Google)''',
    'SELECT ''Columna consumed_budget ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe el índice idx_is_ads_service
SET @exist_index = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND INDEX_NAME = 'idx_is_ads_service'
);

SET @sql = IF(@exist_index = 0,
    'ALTER TABLE services ADD INDEX idx_is_ads_service (is_ads_service)',
    'SELECT ''Índice idx_is_ads_service ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 1. ELIMINAR CAMPO initial_management_fee
-- =====================================================
-- Razón: monthly_fee ya representa los honorarios de gestión,
-- y el desglose real se hace al registrar pagos, no al crear el servicio.

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'initial_management_fee'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE services DROP COLUMN initial_management_fee',
    'SELECT "La columna initial_management_fee no existe, no se requiere acción" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar que se eliminó correctamente
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ Campo initial_management_fee eliminado correctamente'
        ELSE '⚠️ El campo initial_management_fee aún existe'
    END AS resultado_eliminacion
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sophea_db' 
AND TABLE_NAME = 'services' 
AND COLUMN_NAME = 'initial_management_fee';

-- =====================================================
-- 2. CORREGIR TRANSACCIONES income_ads SIN service_id
-- =====================================================
-- Razón: Algunas transacciones income_ads no tienen service_id
-- asociado, lo cual impide calcular correctamente las inversiones

-- Ver transacciones que necesitan corrección (solo para referencia)
-- Esta consulta es solo informativa, no modifica datos
SELECT 
    COUNT(*) as transacciones_a_corregir
FROM project_transactions pt
LEFT JOIN payments p ON pt.payment_id = p.id
WHERE pt.transaction_type = 'income_ads' 
  AND (pt.service_id IS NULL OR pt.service_id = 0)
  AND p.service_id IS NOT NULL;

-- Actualizar transacciones usando el service_id del pago
UPDATE project_transactions pt
INNER JOIN payments p ON pt.payment_id = p.id
INNER JOIN services s ON p.service_id = s.id
SET pt.service_id = p.service_id,
    pt.client_id = s.client_id
WHERE pt.transaction_type = 'income_ads' 
AND (pt.service_id IS NULL OR pt.service_id = 0)
AND p.service_id IS NOT NULL
AND s.id IS NOT NULL;

-- Verificar resultado
SELECT 
    COUNT(*) as total_income_ads,
    COUNT(CASE WHEN service_id IS NOT NULL AND service_id > 0 THEN 1 END) as con_service_id,
    COUNT(CASE WHEN service_id IS NULL OR service_id = 0 THEN 1 END) as sin_service_id,
    CASE 
        WHEN COUNT(CASE WHEN service_id IS NULL OR service_id = 0 THEN 1 END) = 0 
        THEN '✅ Todas las transacciones income_ads tienen service_id'
        ELSE CONCAT('⚠️ Aún hay ', COUNT(CASE WHEN service_id IS NULL OR service_id = 0 THEN 1 END), ' transacciones sin service_id')
    END AS resultado_correccion
FROM project_transactions
WHERE transaction_type = 'income_ads';

-- =====================================================
-- 3. ACTUALIZAR PLATFORM DE 'facebook' A 'meta'
-- =====================================================
-- Razón: META es el nombre correcto de la plataforma
-- (anteriormente Facebook)

-- Ver registros actuales con 'facebook' (solo para referencia)
SELECT 
    COUNT(*) as total_facebook,
    platform,
    transaction_type
FROM project_transactions
WHERE platform = 'facebook'
GROUP BY platform, transaction_type;

-- Actualizar registros existentes de 'facebook' a 'meta'
UPDATE project_transactions
SET platform = 'meta'
WHERE platform = 'facebook';

-- Modificar el ENUM para que 'meta' sea la primera opción
-- Nota: Mantenemos 'facebook' en el ENUM por compatibilidad con datos antiguos
ALTER TABLE project_transactions 
MODIFY COLUMN platform ENUM('meta', 'facebook', 'whatsapp', 'google', 'tiktok', 'linkedin', 'other') NULL
COMMENT 'Plataforma: meta (META), whatsapp (WhatsApp META), google, tiktok, linkedin, other';

-- Verificar el cambio
SELECT 
    COUNT(*) as total_meta,
    platform,
    transaction_type
FROM project_transactions
WHERE platform = 'meta'
GROUP BY platform, transaction_type;

-- Verificar que no queden registros con 'facebook'
SELECT 
    COUNT(*) as remaining_facebook,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ Todos los registros fueron actualizados a meta'
        ELSE CONCAT('⚠️ Aún hay ', COUNT(*), ' registros con platform = facebook')
    END AS resultado_actualizacion
FROM project_transactions
WHERE platform = 'facebook';

-- =====================================================
-- RESUMEN FINAL
-- =====================================================
SELECT 
    '✅ Actualización completada' AS estado,
    NOW() AS fecha_ejecucion,
    'sophea_db' AS base_datos;
