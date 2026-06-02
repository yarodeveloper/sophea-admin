<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- RECENT PAYMENTS ---\n";
    $stmt = $db->query("SELECT p.*, c.company_name, c.client_number FROM payments p JOIN clients c ON p.client_id = c.id ORDER BY p.created_at DESC LIMIT 20");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "PayID: " . $row['id'] . " | Client: " . $row['company_name'] . " (" . $row['client_number'] . ") | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Created: " . $row['created_at'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
