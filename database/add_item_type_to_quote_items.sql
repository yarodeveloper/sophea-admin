-- =====================================================
-- Migración: Agregar item_type a quote_items
-- Propósito: Distinguir explícitamente entre Honorario (fee)
--            e Inversión en plataforma ADS (ads_investment)
--            en cada ítem de una cotización.
-- =====================================================

USE sophea_db;

ALTER TABLE quote_items
    ADD COLUMN item_type ENUM('fee', 'ads_investment') NOT NULL DEFAULT 'fee'
        COMMENT 'Tipo de ítem: fee = honorario del servicio, ads_investment = pauta/inversión en plataforma (Meta, Google, etc.)'
    AFTER service_type;

-- También agregar columna platform en quote_items para especificar la plataforma ADS
ALTER TABLE quote_items
    ADD COLUMN ads_platform VARCHAR(50) NULL DEFAULT NULL
        COMMENT 'Plataforma ADS: meta, google, tiktok, etc. Solo aplica cuando item_type = ads_investment'
    AFTER item_type;

-- Índice para búsquedas por tipo de ítem
ALTER TABLE quote_items
    ADD INDEX idx_item_type (item_type);

-- Los ítems existentes quedan como 'fee' (valor por defecto), no se rompe nada.
