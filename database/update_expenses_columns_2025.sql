-- =====================================================
-- SOPHEA - Actualización de Base de Datos
-- Extensión de Tabla Expenses para Costos de Servicios
-- =====================================================
-- Fecha: 2025-01-14
-- Descripción: 
--   Agrega columnas opcionales a la tabla expenses para
--   asociar gastos con clientes y servicios, permitiendo
--   rastrear costos de servicios como Facebook Ads, etc.
--   
--   Este script es seguro de ejecutar múltiples veces
--   (idempotente) - verifica existencia antes de agregar
-- =====================================================

USE sophea_db;

-- =====================================================
-- 1. AGREGAR COLUMNA client_id (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'client_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN client_id INT NULL COMMENT ''ID del cliente (si es costo de servicio)''',
    'SELECT ''Columna client_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 2. AGREGAR COLUMNA service_id (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'service_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN service_id INT NULL COMMENT ''ID del servicio/proyecto (si es costo de servicio)''',
    'SELECT ''Columna service_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. AGREGAR COLUMNA is_client_service_cost (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'is_client_service_cost'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN is_client_service_cost BOOLEAN DEFAULT FALSE COMMENT ''Si es costo asociado a servicio de cliente''',
    'SELECT ''Columna is_client_service_cost ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. AGREGAR COLUMNA campaign_id (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'campaign_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN campaign_id VARCHAR(255) NULL COMMENT ''ID de campaña en plataforma externa (Facebook, Google, etc.)''',
    'SELECT ''Columna campaign_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 5. AGREGAR COLUMNA billing_period_start (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'billing_period_start'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN billing_period_start DATE NULL COMMENT ''Inicio del período facturado''',
    'SELECT ''Columna billing_period_start ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 6. AGREGAR COLUMNA billing_period_end (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'billing_period_end'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN billing_period_end DATE NULL COMMENT ''Fin del período facturado''',
    'SELECT ''Columna billing_period_end ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 7. AGREGAR COLUMNA reimbursement_status (si no existe)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'reimbursement_status'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE expenses ADD COLUMN reimbursement_status ENUM(''not_required'', ''pending'', ''billed'', ''paid'') DEFAULT ''not_required'' COMMENT ''Estado de reembolso al cliente''',
    'SELECT ''Columna reimbursement_status ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 8. AGREGAR ÍNDICES (si no existen)
-- =====================================================

-- Índice para client_id
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND INDEX_NAME = 'idx_client_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE expenses ADD INDEX idx_client_id (client_id)',
    'SELECT ''Índice idx_client_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para service_id
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND INDEX_NAME = 'idx_service_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE expenses ADD INDEX idx_service_id (service_id)',
    'SELECT ''Índice idx_service_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para is_client_service_cost
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND INDEX_NAME = 'idx_is_client_service_cost'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE expenses ADD INDEX idx_is_client_service_cost (is_client_service_cost)',
    'SELECT ''Índice idx_is_client_service_cost ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para campaign_id
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND INDEX_NAME = 'idx_campaign_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE expenses ADD INDEX idx_campaign_id (campaign_id)',
    'SELECT ''Índice idx_campaign_id ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 9. AGREGAR FOREIGN KEYS (si no existen)
-- =====================================================

-- Foreign key para client_id
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND CONSTRAINT_NAME = 'fk_expenses_client'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE expenses ADD CONSTRAINT fk_expenses_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL',
    'SELECT ''Foreign key fk_expenses_client ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Foreign key para service_id
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND CONSTRAINT_NAME = 'fk_expenses_service'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE expenses ADD CONSTRAINT fk_expenses_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL',
    'SELECT ''Foreign key fk_expenses_service ya existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 10. EXTENDER expense_type ENUM (si no incluye tipos de Ads)
-- =====================================================
-- Verificar si expense_type ya incluye 'ads_facebook'
SET @has_ads_types = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'expenses' 
    AND COLUMN_NAME = 'expense_type'
    AND COLUMN_TYPE LIKE '%ads_facebook%'
);

SET @sql = IF(@has_ads_types = 0,
    'ALTER TABLE expenses MODIFY COLUMN expense_type ENUM(
        ''hosting'',
        ''domain'',
        ''platform'',
        ''software'',
        ''salary'',
        ''freelancer'',
        ''marketing'',
        ''ads_facebook'',
        ''ads_google'',
        ''ads_instagram'',
        ''ads_tiktok'',
        ''ads_linkedin'',
        ''ads_other'',
        ''office'',
        ''utilities'',
        ''other''
    ) NOT NULL',
    'SELECT ''ENUM expense_type ya incluye tipos de Ads'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 11. ACTUALIZAR COMENTARIO DE LA TABLA
-- =====================================================
ALTER TABLE expenses 
COMMENT = 'Gastos operativos y costos de servicios de clientes';

-- =====================================================
-- RESUMEN FINAL
-- =====================================================
SELECT 
    '✅ Actualización de expenses completada' AS estado,
    NOW() AS fecha_ejecucion,
    'sophea_db' AS base_datos;

-- Verificar columnas agregadas
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sophea_db' 
AND TABLE_NAME = 'expenses' 
AND COLUMN_NAME IN (
    'client_id',
    'service_id',
    'is_client_service_cost',
    'campaign_id',
    'billing_period_start',
    'billing_period_end',
    'reimbursement_status'
)
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- NOTAS:
-- =====================================================
-- - Si is_client_service_cost = TRUE, entonces client_id y service_id deben estar presentes
-- - Los gastos operativos tienen is_client_service_cost = FALSE y client_id/service_id = NULL
-- - Los costos de servicios tienen is_client_service_cost = TRUE y client_id/service_id asignados
-- - El código PHP (Expense.php) maneja estas columnas dinámicamente, por lo que funciona
--   tanto si las columnas existen como si no (backward compatible)
-- =====================================================
