-- Migration: Add recurring fields to services table
-- Part of the Recurring Services feature for Ads and Social Media

USE sophea_db;

-- Add columns to services table
ALTER TABLE services 
ADD COLUMN is_recurring TINYINT(1) DEFAULT 0 COMMENT 'Indica si el servicio es recurrente',
ADD COLUMN renewal_mode ENUM('automatic', 'manual') DEFAULT 'manual' COMMENT 'Modo de renovación',
ADD COLUMN base_service_id INT NULL COMMENT 'ID del servicio original o plantilla',
ADD COLUMN period_number INT DEFAULT 1 COMMENT 'Número de período/mes transcurrido';

-- Add foreign key constraint for base_service_id
ALTER TABLE services 
ADD CONSTRAINT fk_base_service 
FOREIGN KEY (base_service_id) REFERENCES services(id) ON DELETE SET NULL;

-- Update existing services to have default values (already handled by DEFAULT)
