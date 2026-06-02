<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- LISTING CLIENTS ---\n";
    $stmt = $db->query("SELECT id, company_name, client_number FROM clients LIMIT 50");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Name: " . $row['company_name'] . " | Number: " . $row['client_number'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
