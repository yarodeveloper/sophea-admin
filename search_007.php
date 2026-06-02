<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $term = '%007%';
    echo "--- SEARCHING FOR '$term' ---\n";
    
    echo "In quotes:\n";
    $stmt = $db->prepare("SELECT id, quote_number, title, client_id FROM quotes WHERE quote_number LIKE ?");
    $stmt->execute([$term]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\nIn client_number:\n";
    $stmt = $db->prepare("SELECT id, client_number, company_name FROM clients WHERE client_number LIKE ?");
    $stmt->execute([$term]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
