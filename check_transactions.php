<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- PROJECT TRANSACTIONS TODAY ---\n";
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT * FROM project_transactions WHERE transaction_date = ? OR created_at LIKE ?");
    $stmt->execute([$today, "$today%"]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n--- ALL PROJECT TRANSACTIONS (LATEST 20) ---\n";
    $stmt = $db->query("SELECT * FROM project_transactions ORDER BY id DESC LIMIT 20");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
