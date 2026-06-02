<?php
require_once 'config_db.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- PROJECT TRANSACTIONS TODAY --- \n";
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM project_transactions WHERE transaction_date = ?");
    $stmt->execute([$today]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }

    echo "\n--- ANY CLIENT OR SERVICE WITH 007 --- \n";
    $stmt = $pdo->query("SELECT id, client_number, company_name FROM clients WHERE client_number LIKE '%007%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Client Found: ID={$row['id']}, Num={$row['client_number']}, Name={$row['company_name']}\n";
    }

    $stmt = $pdo->query("SELECT id, service_name, client_id FROM services WHERE id LIKE '%007%' OR service_name LIKE '%007%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Service Found: ID={$row['id']}, Name={$row['service_name']}, ClientID={$row['client_id']}\n";
    }

    echo "\n--- ALL QUOTES (Recent) --- \n";
    $stmt = $pdo->query("SELECT id, quote_number, client_id FROM quotes ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Quote: ID={$row['id']}, Num={$row['quote_number']}, ClientID={$row['client_id']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
