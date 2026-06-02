<?php
/**
 * Script para verificar servicios nuevos y sus pagos
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

$db = Database::getInstance()->getConnection();
$clientId = 2;

echo "<h2>Verificación de Servicios y Pagos para Cliente ID {$clientId}</h2>";

// 1. Verificar servicios del cliente
echo "<h3>1. Servicios del Cliente:</h3>";
$sql = "SELECT id, service_name, is_ads_service, monthly_fee, status, created_at
        FROM services 
        WHERE client_id = :client_id
        ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':client_id' => $clientId]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($services)) {
    echo "<p style='color: red;'>❌ No se encontraron servicios</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nombre</th><th>Es Ads</th><th>Tarifa</th><th>Estado</th><th>Creado</th>";
    echo "</tr>";
    
    foreach ($services as $s) {
        $isAds = !empty($s['is_ads_service']) && ($s['is_ads_service'] == 1 || $s['is_ads_service'] === true || $s['is_ads_service'] === '1');
        $adsColor = $isAds ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$s['id']}</td>";
        echo "<td>{$s['service_name']}</td>";
        echo "<td style='color: {$adsColor}; font-weight: bold;'>" . ($isAds ? '✅ SÍ' : '❌ NO') . " (" . var_export($s['is_ads_service'], true) . ")</td>";
        echo "<td>\$" . number_format($s['monthly_fee'], 2) . "</td>";
        echo "<td>{$s['status']}</td>";
        echo "<td>{$s['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Verificar pagos del cliente
echo "<h3>2. Pagos del Cliente:</h3>";
$sql = "SELECT p.*, s.service_name, s.is_ads_service
        FROM payments p
        LEFT JOIN services s ON p.service_id = s.id
        WHERE p.client_id = :client_id
        ORDER BY p.payment_date DESC, p.created_at DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([':client_id' => $clientId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($payments)) {
    echo "<p style='color: red;'>❌ No se encontraron pagos</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Servicio</th><th>Es Ads</th><th>Tiene income_fee</th><th>Tiene income_ads</th>";
    echo "</tr>";
    
    foreach ($payments as $p) {
        // Verificar transacciones
        $sql2 = "SELECT transaction_type, SUM(amount) as total
                 FROM project_transactions
                 WHERE payment_id = :payment_id
                 GROUP BY transaction_type";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([':payment_id' => $p['id']]);
        $transactions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        $hasIncomeFee = false;
        $hasIncomeAds = false;
        $feeAmount = 0;
        $adsAmount = 0;
        
        foreach ($transactions as $tx) {
            if ($tx['transaction_type'] == 'income_fee') {
                $hasIncomeFee = true;
                $feeAmount = $tx['total'];
            }
            if ($tx['transaction_type'] == 'income_ads') {
                $hasIncomeAds = true;
                $adsAmount = $tx['total'];
            }
        }
        
        $isAds = !empty($p['is_ads_service']) && ($p['is_ads_service'] == 1 || $p['is_ads_service'] === true || $p['is_ads_service'] === '1');
        $rowColor = ($isAds && (!$hasIncomeFee || !$hasIncomeAds)) ? 'background: #fff3cd;' : '';
        
        echo "<tr style='{$rowColor}'>";
        echo "<td>{$p['id']}</td>";
        echo "<td>\$" . number_format($p['amount'], 2) . "</td>";
        echo "<td>{$p['payment_date']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>" . ($p['service_name'] ?? 'N/A') . " (ID: {$p['service_id']})</td>";
        echo "<td>" . ($isAds ? '✅ SÍ' : '❌ NO') . "</td>";
        echo "<td>" . ($hasIncomeFee ? "✅ \$" . number_format($feeAmount, 2) : '❌') . "</td>";
        echo "<td>" . ($hasIncomeAds ? "✅ \$" . number_format($adsAmount, 2) : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Verificar servicios más recientes
echo "<h3>3. Servicios más recientes (últimos 5):</h3>";
$sql = "SELECT id, service_name, is_ads_service, client_id, created_at
        FROM services 
        WHERE client_id = :client_id
        ORDER BY created_at DESC
        LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute([':client_id' => $clientId]);
$recentServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recentServices as $s) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>ID:</strong> {$s['id']}</p>";
    echo "<p><strong>Nombre:</strong> {$s['service_name']}</p>";
    echo "<p><strong>is_ads_service:</strong> " . var_export($s['is_ads_service'], true) . "</p>";
    echo "<p><strong>Creado:</strong> {$s['created_at']}</p>";
    echo "</div>";
}

?>

