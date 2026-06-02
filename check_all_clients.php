<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "--- ALL CLIENT NUMBERS ---\n";
    $stmt = $db->query("SELECT id, client_number, company_name FROM clients ORDER BY client_number DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Number: " . $row['client_number'] . " | Name: " . $row['company_name'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
