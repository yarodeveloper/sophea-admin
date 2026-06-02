-- =====================================================
-- SOPHEA - Sistema de Gestión de Gastos/Egresos
-- Schema para tabla de gastos operativos
-- =====================================================

USE sophea_db;

-- =====================================================
-- Tabla: expenses (Gastos/Egresos)
-- =====================================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número único: EXP-YYYY-MM-XXXX',
    expense_type ENUM(
        'hosting',
        'domain',
        'platform', -- Canva, etc.
        'software', -- Cursor IA, Gemini, etc.
        'salary',
        'freelancer',
        'marketing',
        'office',
        'utilities',
        'other'
    ) NOT NULL,
    category VARCHAR(100) NOT NULL COMMENT 'Categoría específica: Hosting, Dominio, Canva Pro, etc.',
    description TEXT COMMENT 'Descripción detallada del gasto',
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto del gasto',
    currency VARCHAR(3) DEFAULT 'MXN',
    payment_method ENUM('transfer', 'cash', 'card', 'paypal', 'stripe', 'other') DEFAULT 'transfer',
    payment_date DATE NOT NULL COMMENT 'Fecha de pago',
    due_date DATE NULL COMMENT 'Fecha de vencimiento si es recurrente',
    billing_cycle ENUM('one_time', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly' COMMENT 'Ciclo de facturación',
    vendor VARCHAR(255) COMMENT 'Proveedor o empresa que factura',
    invoice_number VARCHAR(100) COMMENT 'Número de factura',
    receipt_url VARCHAR(500) COMMENT 'URL del comprobante o factura',
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    is_recurring BOOLEAN DEFAULT TRUE COMMENT 'Si es un gasto recurrente',
    notes TEXT COMMENT 'Notas adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del admin que registró el gasto',
    INDEX idx_expense_type (expense_type),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_due_date (due_date),
    INDEX idx_billing_cycle (billing_cycle),
    INDEX idx_expense_number (expense_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gastos y egresos operativos';

-- =====================================================
-- Insertar categorías comunes de ejemplo
-- =====================================================
-- Estas son solo referencias, los gastos reales se insertan desde el admin

