<?php
require_once __DIR__ . '/../config_db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>Forzando Actualización de Columnas (Fase 4)</h1>";
    
    // Check if columns exist
    $checkCols = $db->query("SHOW COLUMNS FROM payments LIKE 'pending_amount'");
    if ($checkCols->rowCount() == 0) {
        echo "<p style='color:red'>Las columnas nuevas no existen. El script de esquema SQL falló anteriormente.</p>";
        
        // Execute queries one by one
        $queries = [
            "ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'pending'",
            "ALTER TABLE payments ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0.00 AFTER amount",
            "ALTER TABLE payments ADD COLUMN tax_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER subtotal",
            "ALTER TABLE payments ADD COLUMN paid_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER tax_amount",
            "ALTER TABLE payments ADD COLUMN pending_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER paid_amount",
            
            "CREATE TABLE IF NOT EXISTS payment_receipts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_id INT NOT NULL,
                client_id INT NOT NULL,
                receipt_number VARCHAR(50) UNIQUE NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method ENUM('transfer', 'cash', 'card', 'check', 'other') DEFAULT 'transfer',
                payment_date DATE NOT NULL,
                reference_number VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT,
                FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "ALTER TABLE project_transactions ADD COLUMN receipt_id INT NULL AFTER payment_id",
            "ALTER TABLE project_transactions ADD CONSTRAINT fk_project_transactions_receipt FOREIGN KEY (receipt_id) REFERENCES payment_receipts(id) ON DELETE SET NULL"
        ];
        
        foreach ($queries as $q) {
            try {
                $db->exec($q);
                echo "<p>OK: " . substr($q, 0, 50) . "...</p>";
            } catch (Exception $eq) {
                echo "<p style='color:orange'>Ignorado/Ya existe: " . $eq->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color:green'>Las columnas ya existen en la tabla payments.</p>";
    }
    
    // Always force update the pending_amount and paid_amount
    echo "<p>Actualizando valores de paid_amount y pending_amount para los pagos existentes...</p>";
    
    $upd1 = $db->exec("UPDATE payments SET pending_amount = amount, paid_amount = 0 WHERE status IN ('pending', 'overdue') AND pending_amount = 0");
    echo "<p>Pagos pendientes actualizados: $upd1</p>";
    
    $upd2 = $db->exec("UPDATE payments SET pending_amount = 0, paid_amount = amount WHERE status = 'paid' AND paid_amount = 0");
    echo "<p>Pagos cobrados actualizados: $upd2</p>";
    
    $upd3 = $db->exec("UPDATE payments SET subtotal = amount, tax_amount = 0 WHERE subtotal = 0");
    echo "<p>Subtotales actualizados: $upd3</p>";
    
    echo "<p><strong>¡Hecho! Ve al Dashboard para revisar.</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error fatal: " . $e->getMessage() . "</p>";
}
