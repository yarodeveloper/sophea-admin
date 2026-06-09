<?php
/**
 * SOPHEA - Phase 4 Billing Migration Script
 * 
 * This script runs the SQL schema updates and then migrates existing
 * paid payments into the new payment_receipts table.
 */

require_once __DIR__ . '/../config_db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>SOPHEA - Phase 4 Billing Migration</h1>";
    
    // 1. Run the SQL schema
    echo "<p>1. Executing phase4_billing_schema.sql...</p>";
    $sqlContent = file_get_contents(__DIR__ . '/phase4_billing_schema.sql');
    
    if ($sqlContent) {
        $db->exec($sqlContent);
        echo "<p style='color:green'>Schema updated successfully!</p>";
    } else {
        throw new Exception("Could not read phase4_billing_schema.sql");
    }
    
    // 2. Migrate existing 'paid' payments to have a receipt
    echo "<p>2. Migrating historical payments to receipts...</p>";
    
    $stmt = $db->query("SELECT * FROM payments WHERE status = 'paid'");
    $paidPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $receiptsCreated = 0;
    
    foreach ($paidPayments as $payment) {
        // Check if a receipt already exists (to avoid duplicates if run multiple times)
        $checkStmt = $db->prepare("SELECT id FROM payment_receipts WHERE payment_id = ?");
        $checkStmt->execute([$payment['id']]);
        if ($checkStmt->fetch()) {
            continue; // Receipt already exists
        }
        
        // Generate a receipt number
        $yearPart = date('Y', strtotime($payment['payment_date'] ?? date('Y-m-d')));
        $monthPart = date('m', strtotime($payment['payment_date'] ?? date('Y-m-d')));
        
        $seqStmt = $db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(receipt_number, 13) AS UNSIGNED)), 0) + 1 as seq FROM payment_receipts WHERE receipt_number LIKE 'REC-{$yearPart}-{$monthPart}-%'");
        $seqRow = $seqStmt->fetch(PDO::FETCH_ASSOC);
        $seq = str_pad($seqRow['seq'] ?? 1, 4, '0', STR_PAD_LEFT);
        $receiptNumber = "REC-{$yearPart}-{$monthPart}-{$seq}";
        
        // Insert the receipt
        $insertStmt = $db->prepare("
            INSERT INTO payment_receipts 
            (payment_id, client_id, receipt_number, amount, payment_method, payment_date, reference_number, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $payment['id'],
            $payment['client_id'],
            $receiptNumber,
            $payment['amount'],
            $payment['payment_method'],
            $payment['paid_at'] ? date('Y-m-d', strtotime($payment['paid_at'])) : ($payment['payment_date'] ?? date('Y-m-d')),
            $payment['reference_number'],
            'Migración automática de pago histórico',
            $payment['created_by'],
            $payment['created_at'] // Keep original creation date
        ]);
        
        $receiptId = $db->lastInsertId();
        
        // Update project_transactions to link them to this receipt
        $updateTxStmt = $db->prepare("UPDATE project_transactions SET receipt_id = ? WHERE payment_id = ? AND transaction_type IN ('income_fee', 'income_ads')");
        $updateTxStmt->execute([$receiptId, $payment['id']]);
        
        $receiptsCreated++;
    }
    
    echo "<p style='color:green'>Migrated {$receiptsCreated} paid payments to receipts successfully!</p>";
    echo "<p><strong>Migration Completed!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    error_log("Migration Error: " . $e->getMessage());
}
