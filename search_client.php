<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $search = '007';
    echo "--- SEARCHING CLIENTS LIKE '$search' ---\n";
    $stmt = $db->prepare("SELECT id, company_name, client_number FROM clients WHERE client_number LIKE ?");
    $stmt->execute(['%' . $search . '%']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Name: " . $row['company_name'] . " | Number: " . $row['client_number'] . "\n";
    }
    
    echo "\n--- ALL PAYMENTS TODAY ---\n";
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT p.*, c.company_name, c.client_number FROM payments p JOIN clients c ON p.client_id = c.id WHERE p.payment_date = ? OR DATE(p.paid_at) = ?");
    $stmt->execute([$today, $today]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "PayID: " . $row['id'] . " | Client: " . $row['company_name'] . " (" . $row['client_number'] . ") | Amount: " . $row['amount'] . " | Status: " . $row['status'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
