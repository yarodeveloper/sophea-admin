<?php
require_once 'config_db.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- RECENT PAYMENTS (Last 2 days) --- \n";
    $stmt = $pdo->prepare("SELECT p.*, c.name as client_name, c.client_number FROM payments p 
                           LEFT JOIN services s ON p.service_id = s.id 
                           LEFT JOIN clients c ON s.client_id = c.id 
                           ORDER BY p.created_at DESC LIMIT 20");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Date: {$row['payment_date']} | Amount: {$row['amount']} | Client: {$row['client_name']} ({$row['client_number']})\n";
    }

    echo "\n--- ALL TABLES in " . DB_NAME . " --- \n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
