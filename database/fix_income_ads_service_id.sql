-- =====================================================
-- SOPHEA - Corregir transacciones income_ads sin service_id
-- =====================================================
-- Este script corrige las transacciones income_ads que no tienen service_id
-- usando el service_id del pago asociado
-- =====================================================

USE sophea_db;

-- 1. Ver transacciones que necesitan corrección
SELECT 
    pt.id,
    pt.payment_id,
    pt.service_id as current_service_id,
    p.service_id as payment_service_id,
    pt.amount,
    pt.transaction_date
FROM project_transactions pt
LEFT JOIN payments p ON pt.payment_id = p.id
WHERE pt.transaction_type = 'income_ads' 
AND (pt.service_id IS NULL OR pt.service_id = 0)
AND p.service_id IS NOT NULL;

-- 2. Actualizar transacciones usando el service_id del pago
UPDATE project_transactions pt
INNER JOIN payments p ON pt.payment_id = p.id
INNER JOIN services s ON p.service_id = s.id
SET pt.service_id = p.service_id,
    pt.client_id = s.client_id
WHERE pt.transaction_type = 'income_ads' 
AND (pt.service_id IS NULL OR pt.service_id = 0)
AND p.service_id IS NOT NULL
AND s.id IS NOT NULL;

-- 3. Verificar resultado
SELECT 
    COUNT(*) as total_income_ads,
    COUNT(CASE WHEN service_id IS NOT NULL AND service_id > 0 THEN 1 END) as with_service_id,
    COUNT(CASE WHEN service_id IS NULL OR service_id = 0 THEN 1 END) as without_service_id
FROM project_transactions
WHERE transaction_type = 'income_ads';

-- 4. Mostrar resumen por servicio después de la corrección
SELECT 
    s.id as service_id,
    s.service_name,
    s.is_ads_service,
    COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) as total_investment
FROM services s
LEFT JOIN project_transactions pt ON s.id = pt.service_id AND pt.transaction_type = 'income_ads'
WHERE s.is_ads_service = 1
GROUP BY s.id, s.service_name, s.is_ads_service
ORDER BY total_investment DESC;

