<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $clientId = 1; // Alejandro Montoya Ruiz
    echo "--- ANALYZING ALEJANDRO MONTOYA RUIZ (ID 1) ---\n";
    
    $stmt = $db->prepare("SELECT id, service_name, monthly_fee, status, progress_percentage FROM services WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as $svc) {
        $stmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE service_id = ? AND status = 'paid'");
        $stmt->execute([$svc['id']]);
        $totalPaid = $stmt->fetchColumn() ?: 0;
        
        echo "Service: {$svc['service_name']} | Status: {$svc['status']} | Progress: {$svc['progress_percentage']}% | Fee: {$svc['monthly_fee']} | Total Paid: $totalPaid\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
