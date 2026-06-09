-- =====================================================
-- SOPHEA - Phase 4: Billing & Receivables Schema Update
-- =====================================================
-- This script upgrades the `payments` table to function as "Charges/Invoices"
-- and introduces the `payment_receipts` table for partial payments ("Abonos").

USE sophea_db;

-- 1. Modify the `payments` table
-- -----------------------------------------------------
-- Change ENUM for status to include partially_paid
ALTER TABLE payments 
    MODIFY COLUMN status ENUM('pending', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'pending';

-- Add new financial columns for partial payment tracking
ALTER TABLE payments
    ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0.00 AFTER amount,
    ADD COLUMN tax_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER subtotal,
    ADD COLUMN paid_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER tax_amount,
    ADD COLUMN pending_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER paid_amount;

-- Update existing records: if 'paid', pending is 0 and paid is amount. If 'pending' or 'overdue', pending is amount.
UPDATE payments SET pending_amount = amount, paid_amount = 0 WHERE status IN ('pending', 'overdue');
UPDATE payments SET pending_amount = 0, paid_amount = amount WHERE status = 'paid';
UPDATE payments SET subtotal = amount, tax_amount = 0; -- Default assumption

-- 2. Create `payment_receipts` table
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL COMMENT 'ID del cargo (payments.id)',
    client_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Recibo único: REC-YYYY-MM-XXXX',
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto abonado',
    payment_method ENUM('transfer', 'cash', 'card', 'check', 'other') DEFAULT 'transfer',
    payment_date DATE NOT NULL,
    reference_number VARCHAR(100) COMMENT 'Referencia bancaria o ticket',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    INDEX idx_payment_id (payment_id),
    INDEX idx_client_id (client_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recibos / Abonos de clientes';

-- 3. Modify `project_transactions`
-- -----------------------------------------------------
-- Link transactions to the receipt that generated them
ALTER TABLE project_transactions
    ADD COLUMN receipt_id INT NULL COMMENT 'ID del abono (payment_receipts.id)' AFTER payment_id,
    ADD CONSTRAINT fk_project_transactions_receipt 
        FOREIGN KEY (receipt_id) REFERENCES payment_receipts(id) ON DELETE SET NULL;

-- Create an index for the new column
ALTER TABLE project_transactions
    ADD INDEX idx_receipt_id (receipt_id);
