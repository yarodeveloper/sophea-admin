<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- CLIENTS BY ID ---";
    $stmt = $db->query("SELECT id, client_number, company_name FROM clients ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Number: " . $row['client_number'] . " | Name: " . $row['company_name'] . "\n";
    }
    
    echo "\n--- PROJECTS/SERVICES ---";
    $stmt = $db->query("SELECT id, client_id, service_name, status FROM services ORDER BY id DESC LIMIT 20");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | ClientID: " . $row['client_id'] . " | Name: " . $row['service_name'] . " | Status: " . $row['status'] . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
