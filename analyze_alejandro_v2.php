<?php
require_once 'config_db.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = 1; // Alejandro
    echo "--- ANALYSIS FOR CLIENT ID $id (Alejandro) --- \n";
    
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client) {
        echo "Client: {$client['company_name']} ({$client['client_number']})\n";
    }

    echo "\n--- SERVICES --- \n";
    $stmt = $pdo->prepare("SELECT s.*, 
                           COALESCE((SELECT SUM(amount) FROM payments WHERE service_id = s.id AND status = 'paid'), 0) as total_paid
                           FROM services s WHERE client_id = ?");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Service: ID={$row['id']}, Name={$row['service_name']}, Status={$row['status']}, Progress={$row['progress_percentage']}%, Monthly Fee={$row['monthly_fee']}, Total Paid={$row['total_paid']}\n";
    }

    echo "\n--- RECENT PAYMENTS --- \n";
    $stmt = $pdo->prepare("SELECT p.* FROM payments p 
                           JOIN services s ON p.service_id = s.id 
                           WHERE s.client_id = ? ORDER BY p.payment_date DESC");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Payment: ID={$row['id']}, Date={$row['payment_date']}, Amount={$row['amount']}, Status={$row['status']}, Created At={$row['created_at']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
