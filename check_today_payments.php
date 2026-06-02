<?php
require_once 'config_db.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- PAYMENTS CREATED TODAY (UTC) --- \n";
    $today_utc = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT p.*, c.company_name, c.client_number FROM payments p 
                           JOIN services s ON p.service_id = s.id 
                           JOIN clients c ON s.client_id = c.id 
                           WHERE DATE(p.created_at) = ?");
    $stmt->execute([$today_utc]);
    $found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found = true;
        echo "Payment ID: {$row['id']} | Amount: {$row['amount']} | Client: {$row['company_name']} ({$row['client_number']})\n";
    }
    if (!$found) echo "No payments found created today ($today_utc).\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
