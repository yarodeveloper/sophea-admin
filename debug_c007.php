<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- PAYMENTS SINCE 2026-01-15 ---\n";
    $stmt = $db->query("SELECT p.*, c.company_name, c.client_number FROM payments p JOIN clients c ON p.client_id = c.id WHERE p.payment_date >= '2026-01-15' ORDER BY p.payment_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "PayID: " . $row['id'] . " | Client: " . $row['company_name'] . " (" . $row['client_number'] . ") | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Date: " . $row['payment_date'] . "\n";
    }
    
    echo "\n--- ALL CLIENTS (NO LIMIT) ---\n";
    $stmt = $db->query("SELECT id, company_name, client_number FROM clients ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | " . $row['client_number'] . " | " . $row['company_name'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
