-- =====================================================
-- SOPHEA - Eliminar campo initial_management_fee
-- =====================================================
-- Este script elimina el campo initial_management_fee de la tabla services
-- ya que es redundante con monthly_fee (Tarifa Mensual)
--
-- Razón: monthly_fee ya representa los honorarios de gestión,
-- y el desglose real se hace al registrar pagos, no al crear el servicio.
-- =====================================================

USE sophea_db;

-- Verificar si la columna existe antes de eliminarla
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'sophea_db' 
    AND TABLE_NAME = 'services' 
    AND COLUMN_NAME = 'initial_management_fee'
);

-- Eliminar la columna si existe
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
    END AS resultado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sophea_db' 
AND TABLE_NAME = 'services' 
AND COLUMN_NAME = 'initial_management_fee';

