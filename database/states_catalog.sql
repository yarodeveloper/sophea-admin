-- =====================================================
-- SOPHEA - Catálogo de Estados
-- =====================================================
-- Este script crea la tabla de estados para México
-- y otros países, con opción "Sin asignar"
-- 
-- Uso:
--   mysql -u username -p sophea_db < states_catalog.sql
--   O importar vía phpMyAdmin
-- =====================================================

USE sophea_db;

-- =====================================================
-- Tabla: states_catalog (Catálogo de Estados)
-- =====================================================
CREATE TABLE IF NOT EXISTS states_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(3) NOT NULL DEFAULT 'MX' COMMENT 'Código de país ISO (MX, US, etc.)',
    state_code VARCHAR(10) COMMENT 'Código del estado (opcional)',
    state_name VARCHAR(100) NOT NULL COMMENT 'Nombre del estado',
    display_order INT DEFAULT 0 COMMENT 'Orden de visualización',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_country_code (country_code),
    INDEX idx_state_name (state_name),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estados/provincias por país';

-- Insertar "Sin asignar" como primera opción
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('XX', 'N/A', 'Sin asignar', 0, TRUE);

-- Insertar Estados de México
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('MX', 'AGU', 'Aguascalientes', 1, TRUE),
('MX', 'BCN', 'Baja California', 2, TRUE),
('MX', 'BCS', 'Baja California Sur', 3, TRUE),
('MX', 'CAM', 'Campeche', 4, TRUE),
('MX', 'CHP', 'Chiapas', 5, TRUE),
('MX', 'CHH', 'Chihuahua', 6, TRUE),
('MX', 'COA', 'Coahuila', 7, TRUE),
('MX', 'COL', 'Colima', 8, TRUE),
('MX', 'DIF', 'Ciudad de México', 9, TRUE),
('MX', 'DUR', 'Durango', 10, TRUE),
('MX', 'GUA', 'Guanajuato', 11, TRUE),
('MX', 'GRO', 'Guerrero', 12, TRUE),
('MX', 'HID', 'Hidalgo', 13, TRUE),
('MX', 'JAL', 'Jalisco', 14, TRUE),
('MX', 'MEX', 'Estado de México', 15, TRUE),
('MX', 'MIC', 'Michoacán', 16, TRUE),
('MX', 'MOR', 'Morelos', 17, TRUE),
('MX', 'NAY', 'Nayarit', 18, TRUE),
('MX', 'NLE', 'Nuevo León', 19, TRUE),
('MX', 'OAX', 'Oaxaca', 20, TRUE),
('MX', 'PUE', 'Puebla', 21, TRUE),
('MX', 'QUE', 'Querétaro', 22, TRUE),
('MX', 'ROO', 'Quintana Roo', 23, TRUE),
('MX', 'SLP', 'San Luis Potosí', 24, TRUE),
('MX', 'SIN', 'Sinaloa', 25, TRUE),
('MX', 'SON', 'Sonora', 26, TRUE),
('MX', 'TAB', 'Tabasco', 27, TRUE),
('MX', 'TAM', 'Tamaulipas', 28, TRUE),
('MX', 'TLA', 'Tlaxcala', 29, TRUE),
('MX', 'VER', 'Veracruz', 30, TRUE),
('MX', 'YUC', 'Yucatán', 31, TRUE),
('MX', 'ZAC', 'Zacatecas', 32, TRUE);

-- Insertar algunos estados de otros países comunes
-- Estados Unidos
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('US', 'AL', 'Alabama', 100, TRUE),
('US', 'AK', 'Alaska', 101, TRUE),
('US', 'AZ', 'Arizona', 102, TRUE),
('US', 'CA', 'California', 103, TRUE),
('US', 'TX', 'Texas', 104, TRUE),
('US', 'FL', 'Florida', 105, TRUE),
('US', 'NY', 'New York', 106, TRUE);

-- Guatemala
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('GT', 'GT-01', 'Guatemala', 200, TRUE),
('GT', 'GT-02', 'Sacatepéquez', 201, TRUE),
('GT', 'GT-03', 'Escuintla', 202, TRUE);

-- Colombia
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('CO', 'CO-DC', 'Bogotá D.C.', 300, TRUE),
('CO', 'CO-ANT', 'Antioquia', 301, TRUE),
('CO', 'CO-VAL', 'Valle del Cauca', 302, TRUE);

-- España
INSERT INTO states_catalog (country_code, state_code, state_name, display_order, is_active) VALUES
('ES', 'ES-M', 'Madrid', 400, TRUE),
('ES', 'ES-B', 'Barcelona', 401, TRUE),
('ES', 'ES-V', 'Valencia', 402, TRUE);

