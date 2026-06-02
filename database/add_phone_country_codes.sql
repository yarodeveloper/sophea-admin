-- =====================================================
-- SOPHEA - Agregar Campos de Código de País
-- =====================================================
-- Este script agrega los campos phone_country_code y 
-- whatsapp_country_code a la tabla clients
-- 
-- Uso:
--   mysql -u username -p sophea_db < add_phone_country_codes.sql
--   O importar vía phpMyAdmin
-- =====================================================

USE sophea_db;

-- Agregar campos de código de país para teléfono y WhatsApp
ALTER TABLE clients 
ADD COLUMN phone_country_code VARCHAR(5) DEFAULT '+52' COMMENT 'Código de país para teléfono (ej: +52, +1, +34)' AFTER phone,
ADD COLUMN whatsapp_country_code VARCHAR(5) DEFAULT '+52' COMMENT 'Código de país para WhatsApp (ej: +52, +1, +34)' AFTER whatsapp;

-- Actualizar registros existentes con código de país por defecto (México)
UPDATE clients 
SET phone_country_code = '+52', whatsapp_country_code = '+52' 
WHERE phone_country_code IS NULL OR whatsapp_country_code IS NULL;

