-- =====================================================
-- SOPHEA - Actualizar platform de 'facebook' a 'meta'
-- =====================================================
-- Este script actualiza la columna platform en project_transactions
-- para cambiar 'facebook' por 'meta' (META)
-- =====================================================

USE sophea_db;

-- 1. Ver registros actuales con 'facebook'
SELECT 
    COUNT(*) as total_facebook,
    platform,
    transaction_type
FROM project_transactions
WHERE platform = 'facebook'
GROUP BY platform, transaction_type;

-- 2. Actualizar registros existentes de 'facebook' a 'meta'
UPDATE project_transactions
SET platform = 'meta'
WHERE platform = 'facebook';

-- 3. Modificar el ENUM para agregar 'meta' (mantener 'facebook' por compatibilidad)
-- Nota: MySQL no permite modificar ENUM directamente, hay que recrearlo
-- Mantenemos 'facebook' en el ENUM por si hay registros antiguos, pero usaremos 'meta' de ahora en adelante
ALTER TABLE project_transactions 
MODIFY COLUMN platform ENUM('meta', 'facebook', 'whatsapp', 'google', 'tiktok', 'linkedin', 'other') NULL
COMMENT 'Plataforma: meta (META), whatsapp (WhatsApp META), google, tiktok, linkedin, other';

-- 4. Verificar el cambio
SELECT 
    COUNT(*) as total_meta,
    platform,
    transaction_type
FROM project_transactions
WHERE platform = 'meta'
GROUP BY platform, transaction_type;

-- 5. Verificar que no queden registros con 'facebook'
SELECT COUNT(*) as remaining_facebook
FROM project_transactions
WHERE platform = 'facebook';

