<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- ALL CLIENTS ---\n";
    $stmt = $db->query("SELECT id, company_name, client_number FROM clients");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Name: " . $row['company_name'] . " | Number: " . $row['client_number'] . "\n";
    }
    
    echo "\n--- ALL PAYMENTS IN JAN 2026 ---\n";
    $stmt = $db->query("SELECT p.*, c.company_name, c.client_number FROM payments p JOIN clients c ON p.client_id = c.id WHERE p.payment_date >= '2026-01-01' ORDER BY p.payment_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "PayID: " . $row['id'] . " | Client: " . $row['company_name'] . " (" . $row['client_number'] . ") | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Date: " . $row['payment_date'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
