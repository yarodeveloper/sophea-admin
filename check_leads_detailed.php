<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- LEADS SCHEMA ---\n";
    $stmt = $db->query("DESCRIBE leads");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n--- ALL LEADS ---\n";
    $stmt = $db->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 50");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
