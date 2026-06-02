<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Updating clients table structure...\n";
    
    // Check if contract_path already exists
    $stmt = $db->query("SHOW COLUMNS FROM clients LIKE 'contract_path'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE clients ADD COLUMN contract_path VARCHAR(255) NULL AFTER logo_url");
        echo "- Column 'contract_path' added.\n";
    } else {
        echo "- Column 'contract_path' already exists.\n";
    }
    
    // Check if contract_note already exists
    $stmt = $db->query("SHOW COLUMNS FROM clients LIKE 'contract_note'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE clients ADD COLUMN contract_note TEXT NULL AFTER contract_path");
        echo "- Column 'contract_note' added.\n";
    } else {
        echo "- Column 'contract_note' already exists.\n";
    }
    
    echo "\nDatabase update completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
