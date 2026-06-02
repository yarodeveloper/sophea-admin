<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_db.php';
require_once __DIR__ . '/classes/Database.php';

try {
    if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true);
    $db = Database::getInstance()->getConnection();
    
    echo "--- CHECKING CURRENT YEAR (2026) ---\n";
    $year = 2026;
    
    $sql = "SELECT COUNT(*) as cnt FROM payments WHERE YEAR(paid_at) = :year AND status = 'paid'";
    $stmt = $db->prepare($sql);
    $stmt->execute([':year' => $year]);
    $res = $stmt->fetch();
    echo "Payments (paid_at in 2026, status=paid): " . $res['cnt'] . "\n";
    
    $sql = "SELECT COUNT(*) as cnt FROM payments WHERE YEAR(payment_date) = :year AND status = 'paid'";
    $stmt = $db->prepare($sql);
    $stmt->execute([':year' => $year]);
    $res = $stmt->fetch();
    echo "Payments (payment_date in 2026, status=paid): " . $res['cnt'] . "\n";

    $sql = "SELECT COUNT(*) as cnt FROM payments WHERE YEAR(payment_date) = :year";
    $stmt = $db->prepare($sql);
    $stmt->execute([':year' => $year]);
    $res = $stmt->fetch();
    echo "Payments (payment_date in 2026, ANY status): " . $res['cnt'] . "\n";

    echo "\n--- BY MONTH (payment_date in 2026) ---\n";
    $sql = "SELECT MONTH(payment_date) as month, status, COUNT(*) as cnt FROM payments WHERE YEAR(payment_date) = :year GROUP BY month, status";
    $stmt = $db->prepare($sql);
    $stmt->execute([':year' => $year]);
    while ($row = $stmt->fetch()) {
        echo "Month " . $row['month'] . " | Status: " . $row['status'] . " | Count: " . $row['cnt'] . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
