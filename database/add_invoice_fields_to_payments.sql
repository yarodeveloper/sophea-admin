-- =====================================================
-- SOPHEA - Agregar campos de facturación a tabla payments
-- =====================================================

USE sophea_db;

-- Agregar campos para rastrear envío de facturas
ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS invoice_sent BOOLEAN DEFAULT FALSE COMMENT 'Si se ha enviado una factura para este pago',
ADD COLUMN IF NOT EXISTS invoice_sent_at DATETIME NULL COMMENT 'Fecha y hora en que se envió la factura',
ADD COLUMN IF NOT EXISTS invoice_url VARCHAR(500) NULL COMMENT 'URL de la factura generada',
ADD COLUMN IF NOT EXISTS invoice_sent_via ENUM('whatsapp', 'email', 'both') NULL COMMENT 'Método por el cual se envió la factura';

-- Agregar índices para búsquedas
ALTER TABLE payments 
ADD INDEX IF NOT EXISTS idx_invoice_sent (invoice_sent),
ADD INDEX IF NOT EXISTS idx_invoice_sent_at (invoice_sent_at);

