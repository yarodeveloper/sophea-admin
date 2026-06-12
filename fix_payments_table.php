<?php
require 'config_db.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add missing columns to payments
    $sql = "ALTER TABLE payments 
            ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount,
            ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subtotal,
            ADD COLUMN pending_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER paid_amount";
            
    $pdo->exec($sql);
    echo "Columns subtotal, paid_amount, pending_amount added successfully.\n";

    // Update existing rows to have sensible defaults based on status
    $updateSql = "UPDATE payments SET 
                    subtotal = amount,
                    paid_amount = CASE WHEN status IN ('paid') THEN amount ELSE 0 END,
                    pending_amount = CASE WHEN status IN ('paid') THEN 0 ELSE amount END";
    $pdo->exec($updateSql);
    echo "Existing rows updated successfully.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
