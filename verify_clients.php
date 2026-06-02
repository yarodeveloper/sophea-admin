<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT MAX(id) FROM clients");
    $maxId = $stmt->fetchColumn();
    echo "Max Client ID: $maxId\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM clients");
    $count = $stmt->fetchColumn();
    echo "Total Clients: $count\n";
    
    // Check for 007 in client_number
    $stmt = $db->prepare("SELECT * FROM clients WHERE client_number LIKE ?");
    $stmt->execute(['%007%']);
    $c007 = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($c007) {
        echo "Found 007: ID " . $c007['id'] . " | Name " . $c007['company_name'] . "\n";
    } else {
        echo "No client found with 007 in client_number\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
