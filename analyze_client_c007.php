<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $clientNumber = 'C-2026-007';
    
    echo "--- ANALYZING CLIENT: $clientNumber ---\n\n";
    
    // 1. Get client data
    $stmt = $db->prepare("SELECT * FROM clients WHERE client_number = ?");
    $stmt->execute([$clientNumber]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        die("Client $clientNumber not found.\n");
    }
    
    $clientId = $client['id'];
    echo "Client ID: $clientId\n";
    echo "Company: " . $client['company_name'] . "\n";
    echo "Status: " . $client['status'] . "\n\n";
    
    // 2. Get services
    echo "--- SERVICES ---\n";
    $stmt = $db->prepare("SELECT id, service_name, service_type, monthly_fee, setup_fee, status, progress_percentage, start_date, end_date FROM services WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($services)) {
        echo "No services found.\n";
    } else {
        foreach ($services as $svc) {
            echo "ID: " . $svc['id'] . " | Name: " . $svc['service_name'] . " | Status: " . $svc['status'] . " | Progress: " . $svc['progress_percentage'] . "% | Fee: $" . $svc['monthly_fee'] . "\n";
        }
    }
    echo "\n";
    
    // 3. Get payments
    echo "--- PAYMENTS ---\n";
    $stmt = $db->prepare("SELECT p.*, s.service_name 
                          FROM payments p 
                          LEFT JOIN services s ON p.service_id = s.id 
                          WHERE p.client_id = ? 
                          ORDER BY p.payment_date DESC, p.created_at DESC");
    $stmt->execute([$clientId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "No payments found.\n";
    } else {
        foreach ($payments as $p) {
            echo "ID: " . $p['id'] . " | Amount: $" . $p['amount'] . " | Status: " . $p['status'] . " | Date: " . $p['payment_date'] . " | Paid At: " . $p['paid_at'] . " | Service: " . ($p['service_name'] ?? 'N/A') . "\n";
            echo "   Notes: " . $p['notes'] . "\n";
        }
    }
    echo "\n";
    
    // 4. Analysis of "Concluidos"
    echo "--- CONSISTENCY CHECK ---\n";
    foreach ($services as $svc) {
        // If progress is 100% but status is not completed/finished
        if ($svc['progress_percentage'] == 100 && !in_array($svc['status'], ['completed', 'finished'])) {
            echo "WARNING: Service '{$svc['service_name']}' (ID: {$svc['id']}) has 100% progress but status is '{$svc['status']}'. Should probably be completed.\n";
        }
        
        // Calculate total paid for this service
        $totalPaid = 0;
        foreach ($payments as $p) {
            if ($p['service_id'] == $svc['id'] && $p['status'] == 'paid') {
                $totalPaid += floatval($p['amount']);
            }
        }
        
        $expected = floatval($svc['monthly_fee']) + floatval($svc['setup_fee']);
        echo "Service '{$svc['service_name']}': Expected: $$expected | Total Paid: $$totalPaid\n";
        
        if ($totalPaid >= $expected && $svc['status'] == 'active' && $svc['progress_percentage'] == 100) {
            echo "   -> This service is fully paid and 100% progress. It should be 'completed'.\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
