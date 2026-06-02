-- =====================================================
-- SOPHEA - Sistema de Historial de Facturas/Recibos
-- Schema para tabla de historial de facturas enviadas
-- =====================================================

USE sophea_db;

-- =====================================================
-- Tabla: invoices (Historial de Facturas/Recibos)
-- =====================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de factura generado',
    client_id INT NOT NULL COMMENT 'ID del cliente',
    service_id INT NULL COMMENT 'ID del servicio específico (NULL si es todos los servicios)',
    invoice_type ENUM('all_services', 'single_service') DEFAULT 'all_services' COMMENT 'Tipo de factura: todos los servicios o uno específico',
    total_amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto total del servicio',
    paid_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Monto pagado',
    pending_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Monto pendiente',
    remaining_amount DECIMAL(10, 2) NOT NULL COMMENT 'Saldo restante',
    invoice_date DATE NOT NULL COMMENT 'Fecha de la factura',
    format ENUM('html', 'pdf') DEFAULT 'html' COMMENT 'Formato en que se generó',
    file_path VARCHAR(500) NULL COMMENT 'Ruta del archivo PDF si se guardó',
    invoice_url VARCHAR(500) NULL COMMENT 'URL del recibo generado',
    sent_via ENUM('whatsapp', 'email', 'both') DEFAULT 'whatsapp' COMMENT 'Método de envío',
    sent_at DATETIME NULL COMMENT 'Fecha y hora de envío',
    recipient_phone VARCHAR(20) NULL COMMENT 'Número de teléfono al que se envió',
    recipient_email VARCHAR(255) NULL COMMENT 'Email al que se envió (si aplica)',
    message_sent TEXT NULL COMMENT 'Mensaje que se envió',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'ID del usuario que creó el recibo',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_client_id (client_id),
    INDEX idx_service_id (service_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de facturas/recibos enviados';

