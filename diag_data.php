<?php
require_once __DIR__ . '/config_db.php';
require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- PAYMENTS SUMMARY (All) ---\n";
    $sql = "SELECT YEAR(paid_at) as year, MONTH(paid_at) as month, status, COUNT(*) as count, SUM(amount) as total 
            FROM payments 
            GROUP BY year, month, status";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Year: " . ($row['year'] ?? 'NULL') . " | Month: " . ($row['month'] ?? 'NULL') . " | Status: " . $row['status'] . " | Count: " . $row['count'] . " | Total: " . $row['total'] . "\n";
    }
    
    echo "\n--- EXPENSES SUMMARY (All) ---\n";
    $sql = "SELECT YEAR(payment_date) as year, MONTH(payment_date) as month, status, COUNT(*) as count, SUM(amount) as total 
            FROM expenses 
            GROUP BY year, month, status";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Year: " . ($row['year'] ?? 'NULL') . " | Month: " . ($row['month'] ?? 'NULL') . " | Status: " . $row['status'] . " | Count: " . $row['count'] . " | Total: " . $row['total'] . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
