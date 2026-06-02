<?php
/**
 * Script para verificar y corregir transacciones income_ads sin service_id
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/ProjectTransaction.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Verificación de Transacciones income_ads</h2>";

// 1. Verificar transacciones sin service_id
echo "<h3>1. Transacciones income_ads sin service_id:</h3>";
$sql = "SELECT pt.*, p.service_id as payment_service_id, p.client_id as payment_client_id
        FROM project_transactions pt
        LEFT JOIN payments p ON pt.payment_id = p.id
        WHERE pt.transaction_type = 'income_ads' 
        AND (pt.service_id IS NULL OR pt.service_id = 0)";
$stmt = $db->prepare($sql);
$stmt->execute();
$transactionsWithoutService = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($transactionsWithoutService)) {
    echo "<p style='color: green;'>✅ Todas las transacciones income_ads tienen service_id</p>";
} else {
    echo "<p style='color: red;'>⚠️ Se encontraron " . count($transactionsWithoutService) . " transacciones sin service_id:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Payment ID</th><th>Service ID (del pago)</th><th>Client ID</th><th>Amount</th><th>Date</th><th>Acción</th></tr>";
    
    foreach ($transactionsWithoutService as $tx) {
        echo "<tr>";
        echo "<td>{$tx['id']}</td>";
        echo "<td>{$tx['payment_id']}</td>";
        echo "<td>" . ($tx['payment_service_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($tx['payment_client_id'] ?? $tx['client_id']) . "</td>";
        echo "<td>\${$tx['amount']}</td>";
        echo "<td>{$tx['transaction_date']}</td>";
        
        // Intentar corregir si el pago tiene service_id
        if (!empty($tx['payment_service_id'])) {
            echo "<td><a href='?fix={$tx['id']}&service_id={$tx['payment_service_id']}'>Corregir</a></td>";
        } else {
            echo "<td>No se puede corregir (pago sin service_id)</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Verificar todas las transacciones income_ads
echo "<h3>2. Todas las transacciones income_ads:</h3>";
$sql = "SELECT pt.*, s.service_name, s.is_ads_service
        FROM project_transactions pt
        LEFT JOIN services s ON pt.service_id = s.id
        WHERE pt.transaction_type = 'income_ads'
        ORDER BY pt.transaction_date DESC
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute();
$allIncomeAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Service ID</th><th>Service Name</th><th>Is Ads?</th><th>Amount</th><th>Date</th><th>Payment ID</th></tr>";

foreach ($allIncomeAds as $tx) {
    $color = empty($tx['service_id']) ? 'red' : 'black';
    echo "<tr style='color: {$color};'>";
    echo "<td>{$tx['id']}</td>";
    echo "<td>" . ($tx['service_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($tx['service_name'] ?? '-') . "</td>";
    echo "<td>" . ($tx['is_ads_service'] ?? '-') . "</td>";
    echo "<td>\${$tx['amount']}</td>";
    echo "<td>{$tx['transaction_date']}</td>";
    echo "<td>{$tx['payment_id']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Corregir transacciones si se solicita
if (isset($_GET['fix']) && isset($_GET['service_id'])) {
    $txId = intval($_GET['fix']);
    $serviceId = intval($_GET['service_id']);
    
    try {
        // Obtener el client_id del servicio
        $sql = "SELECT client_id FROM services WHERE id = :service_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':service_id' => $serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service) {
            $sql = "UPDATE project_transactions 
                    SET service_id = :service_id, 
                        client_id = :client_id
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':service_id' => $serviceId,
                ':client_id' => $service['client_id'],
                ':id' => $txId
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Transacción {$txId} corregida exitosamente</p>";
                echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Error al corregir la transacción</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Servicio no encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// 4. Resumen por servicio
echo "<h3>3. Resumen de inversión por servicio:</h3>";
$sql = "SELECT s.id, s.service_name, s.is_ads_service,
               COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) as total_investment
        FROM services s
        LEFT JOIN project_transactions pt ON s.id = pt.service_id AND pt.transaction_type = 'income_ads'
        WHERE s.is_ads_service = 1
        GROUP BY s.id, s.service_name, s.is_ads_service
        ORDER BY total_investment DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($summary)) {
    echo "<p>No hay servicios Ads con inversión registrada</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Service ID</th><th>Service Name</th><th>Total Inversión</th></tr>";
    foreach ($summary as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['service_name']}</td>";
        echo "<td>\$" . number_format($row['total_investment'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

?>

