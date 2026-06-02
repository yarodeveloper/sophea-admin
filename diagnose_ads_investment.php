<?php
/**
 * Script de diagnóstico para verificar por qué no se muestra la inversión en Ads
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Service.php';
require_once 'classes/ProjectTransaction.php';

$db = Database::getInstance()->getConnection();
$service = new Service();
$projectTransaction = new ProjectTransaction();

echo "<h2>Diagnóstico: Inversión en Ads por Servicio</h2>";

// 1. Buscar servicios "Campañas de Ads"
echo "<h3>1. Servicios 'Campañas de Ads':</h3>";
$sql = "SELECT id, service_name, is_ads_service, monthly_fee, client_id 
        FROM services 
        WHERE service_name LIKE '%Campañas de Ads%' OR service_name LIKE '%Ads%'
        ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$adsServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($adsServices)) {
    echo "<p style='color: orange;'>⚠️ No se encontraron servicios con 'Campañas de Ads' en el nombre</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>is_ads_service</th><th>Tarifa</th><th>Client ID</th><th>Transacciones income_ads</th><th>Total Inversión</th></tr>";
    
    foreach ($adsServices as $svc) {
        // Verificar transacciones
        $sql = "SELECT COUNT(*) as count, SUM(amount) as total
                FROM project_transactions
                WHERE service_id = :service_id AND transaction_type = 'income_ads'";
        $stmt2 = $db->prepare($sql);
        $stmt2->execute([':service_id' => $svc['id']]);
        $txInfo = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Obtener balance usando el método de la clase
        $balance = $projectTransaction->getCustodyBalance($svc['id']);
        
        $isAdsColor = ($svc['is_ads_service'] == 1) ? 'green' : 'red';
        $investmentColor = ($balance['total_investment'] > 0) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$svc['id']}</td>";
        echo "<td>{$svc['service_name']}</td>";
        echo "<td style='color: {$isAdsColor}; font-weight: bold;'>{$svc['is_ads_service']}</td>";
        echo "<td>\$" . number_format($svc['monthly_fee'], 2) . "</td>";
        echo "<td>{$svc['client_id']}</td>";
        echo "<td>{$txInfo['count']} transacciones</td>";
        echo "<td style='color: {$investmentColor}; font-weight: bold;'>\$" . number_format($balance['total_investment'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Verificar todas las transacciones income_ads
echo "<h3>2. Todas las transacciones income_ads (últimas 20):</h3>";
$sql = "SELECT pt.*, s.service_name, s.is_ads_service, p.service_id as payment_service_id
        FROM project_transactions pt
        LEFT JOIN services s ON pt.service_id = s.id
        LEFT JOIN payments p ON pt.payment_id = p.id
        WHERE pt.transaction_type = 'income_ads'
        ORDER BY pt.transaction_date DESC
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute();
$allIncomeAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($allIncomeAds)) {
    echo "<p style='color: orange;'>⚠️ No hay transacciones income_ads registradas</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Service ID</th><th>Service Name</th><th>Payment ID</th><th>Payment Service ID</th><th>Amount</th><th>Date</th><th>Status</th></tr>";
    
    foreach ($allIncomeAds as $tx) {
        $statusColor = 'green';
        $status = 'OK';
        
        if (empty($tx['service_id']) || $tx['service_id'] == 0) {
            $statusColor = 'red';
            $status = 'SIN SERVICE_ID';
        } elseif (!empty($tx['payment_service_id']) && $tx['service_id'] != $tx['payment_service_id']) {
            $statusColor = 'orange';
            $status = 'DIFERENTE';
        }
        
        echo "<tr style='color: {$statusColor};'>";
        echo "<td>{$tx['id']}</td>";
        echo "<td>" . ($tx['service_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($tx['service_name'] ?? '-') . "</td>";
        echo "<td>{$tx['payment_id']}</td>";
        echo "<td>" . ($tx['payment_service_id'] ?? 'NULL') . "</td>";
        echo "<td>\$" . number_format($tx['amount'], 2) . "</td>";
        echo "<td>{$tx['transaction_date']}</td>";
        echo "<td><strong>{$status}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Verificar servicios activos con is_ads_service
echo "<h3>3. Todos los servicios activos marcados como Ads:</h3>";
$sql = "SELECT id, service_name, is_ads_service, monthly_fee, client_id
        FROM services
        WHERE is_ads_service = 1 AND status = 'active'
        ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$allAdsServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($allAdsServices)) {
    echo "<p style='color: orange;'>⚠️ No hay servicios activos marcados como Ads (is_ads_service = 1)</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Tarifa</th><th>Client ID</th><th>Total Inversión</th><th>Total Consumido</th><th>Saldo</th></tr>";
    
    foreach ($allAdsServices as $svc) {
        $balance = $projectTransaction->getCustodyBalance($svc['id']);
        
        echo "<tr>";
        echo "<td>{$svc['id']}</td>";
        echo "<td>{$svc['service_name']}</td>";
        echo "<td>\$" . number_format($svc['monthly_fee'], 2) . "</td>";
        echo "<td>{$svc['client_id']}</td>";
        echo "<td style='color: blue; font-weight: bold;'>\$" . number_format($balance['total_investment'], 2) . "</td>";
        echo "<td>\$" . number_format($balance['total_consumed'], 2) . "</td>";
        $balanceColor = $balance['balance'] < 0 ? 'red' : 'green';
        echo "<td style='color: {$balanceColor}; font-weight: bold;'>\$" . number_format($balance['balance'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Verificar pagos con desglose
echo "<h3>4. Pagos recientes con desglose (fee_amount y ads_amount):</h3>";
$sql = "SELECT p.id, p.service_id, p.amount, p.payment_date, 
               s.service_name, s.is_ads_service,
               (SELECT COUNT(*) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_fee') as has_fee_tx,
               (SELECT COUNT(*) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_ads') as has_ads_tx
        FROM payments p
        LEFT JOIN services s ON p.service_id = s.id
        WHERE p.service_id IS NOT NULL
        ORDER BY p.payment_date DESC
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute();
$recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($recentPayments)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Payment ID</th><th>Service ID</th><th>Service Name</th><th>Is Ads?</th><th>Amount</th><th>Date</th><th>Fee TX</th><th>Ads TX</th></tr>";
    
    foreach ($recentPayments as $pay) {
        $isAdsColor = ($pay['is_ads_service'] == 1) ? 'green' : 'gray';
        $hasTxColor = ($pay['has_fee_tx'] > 0 || $pay['has_ads_tx'] > 0) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$pay['id']}</td>";
        echo "<td>{$pay['service_id']}</td>";
        echo "<td>" . ($pay['service_name'] ?? '-') . "</td>";
        echo "<td style='color: {$isAdsColor};'>{$pay['is_ads_service']}</td>";
        echo "<td>\$" . number_format($pay['amount'], 2) . "</td>";
        echo "<td>{$pay['payment_date']}</td>";
        echo "<td style='color: {$hasTxColor};'>{$pay['has_fee_tx']}</td>";
        echo "<td style='color: {$hasTxColor};'>{$pay['has_ads_tx']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

?>

