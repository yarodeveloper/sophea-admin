<?php
/**
 * Script de prueba para verificar por qué no se muestra la inversión en Ads
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Service.php';
require_once 'classes/ProjectTransaction.php';

$db = Database::getInstance()->getConnection();
$service = new Service();
$projectTransaction = new ProjectTransaction();

// ID del servicio a verificar (cambiar según necesites)
$serviceId = 5; // Del ejemplo del usuario
$clientId = 2;

echo "<h2>Diagnóstico: Inversión en Ads para Servicio ID {$serviceId}</h2>";

// 1. Verificar el servicio
echo "<h3>1. Información del Servicio:</h3>";
$sql = "SELECT id, service_name, is_ads_service, monthly_fee, client_id, status
        FROM services 
        WHERE id = :service_id";
$stmt = $db->prepare($sql);
$stmt->execute([':service_id' => $serviceId]);
$svc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$svc) {
    echo "<p style='color: red;'>❌ Servicio no encontrado</p>";
    exit;
}

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
echo "<tr><td>ID</td><td>{$svc['id']}</td></tr>";
echo "<tr><td>Nombre</td><td>{$svc['service_name']}</td></tr>";
echo "<tr><td>is_ads_service</td><td style='color: " . ($svc['is_ads_service'] == 1 ? 'green' : 'red') . "; font-weight: bold;'>{$svc['is_ads_service']}</td></tr>";
echo "<tr><td>Tarifa</td><td>\$" . number_format($svc['monthly_fee'], 2) . "</td></tr>";
echo "<tr><td>Client ID</td><td>{$svc['client_id']}</td></tr>";
echo "<tr><td>Status</td><td>{$svc['status']}</td></tr>";
echo "</table>";

// 2. Verificar transacciones income_ads
echo "<h3>2. Transacciones income_ads para este servicio:</h3>";
$sql = "SELECT * 
        FROM project_transactions 
        WHERE service_id = :service_id 
        AND transaction_type = 'income_ads'
        ORDER BY transaction_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':service_id' => $serviceId]);
$incomeAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($incomeAds)) {
        echo "<p style='color: red;'>❌ No hay transacciones income_ads para este servicio</p>";
        
        // Verificar si hay transacciones sin service_id
        echo "<h4>Buscando transacciones income_ads sin service_id para este cliente:</h4>";
        $sql = "SELECT pt.*, p.service_id as payment_service_id
                FROM project_transactions pt
                LEFT JOIN payments p ON pt.payment_id = p.id
                WHERE pt.transaction_type = 'income_ads'
                AND (pt.service_id IS NULL OR pt.service_id = 0)
                AND pt.client_id = :client_id
                ORDER BY pt.transaction_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':client_id' => $clientId]);
        $orphanTx = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($orphanTx)) {
            echo "<p style='color: orange;'>⚠️ Se encontraron " . count($orphanTx) . " transacciones income_ads sin service_id:</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Payment ID</th><th>Payment Service ID</th><th>Amount</th><th>Date</th><th>Acción</th></tr>";
            foreach ($orphanTx as $tx) {
                echo "<tr>";
                echo "<td>{$tx['id']}</td>";
                echo "<td>{$tx['payment_id']}</td>";
                echo "<td>" . ($tx['payment_service_id'] ?? 'NULL') . "</td>";
                echo "<td>\${$tx['amount']}</td>";
                echo "<td>{$tx['transaction_date']}</td>";
                if (!empty($tx['payment_service_id']) && $tx['payment_service_id'] == $serviceId) {
                    echo "<td><a href='?fix={$tx['id']}&service_id={$serviceId}'>Corregir</a></td>";
                } else {
                    echo "<td>-</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontraron transacciones income_ads huérfanas</p>";
        }
        
        // Verificar pagos registrados para este servicio
        echo "<h4>Verificando pagos registrados para este servicio:</h4>";
        $sql = "SELECT p.*, 
                (SELECT COUNT(*) FROM project_transactions pt WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads') as has_income_ads,
                (SELECT COUNT(*) FROM project_transactions pt WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as has_income_fee
                FROM payments p
                WHERE p.service_id = :service_id
                ORDER BY p.payment_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':service_id' => $serviceId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($payments)) {
            echo "<p style='color: blue;'>ℹ️ Se encontraron " . count($payments) . " pagos registrados:</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Tiene income_fee</th><th>Tiene income_ads</th><th>Acción</th></tr>";
            foreach ($payments as $p) {
                echo "<tr>";
                echo "<td>{$p['id']}</td>";
                echo "<td>\${$p['amount']}</td>";
                echo "<td>{$p['payment_date']}</td>";
                echo "<td>{$p['status']}</td>";
                echo "<td>" . ($p['has_income_fee'] > 0 ? '✅' : '❌') . "</td>";
                echo "<td>" . ($p['has_income_ads'] > 0 ? '✅' : '❌') . "</td>";
                if ($p['has_income_ads'] == 0 && $p['status'] == 'paid') {
                    echo "<td><a href='?create_income_ads={$p['id']}&service_id={$serviceId}'>Crear income_ads</a></td>";
                } else {
                    echo "<td>-</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontraron pagos registrados para este servicio</p>";
        }
    } else {
    echo "<p style='color: green;'>✅ Se encontraron " . count($incomeAds) . " transacciones income_ads</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Amount</th><th>Date</th><th>Payment ID</th><th>Description</th></tr>";
    $total = 0;
    foreach ($incomeAds as $tx) {
        $total += $tx['amount'];
        echo "<tr>";
        echo "<td>{$tx['id']}</td>";
        echo "<td>\$" . number_format($tx['amount'], 2) . "</td>";
        echo "<td>{$tx['transaction_date']}</td>";
        echo "<td>{$tx['payment_id']}</td>";
        echo "<td>{$tx['description']}</td>";
        echo "</tr>";
    }
    echo "<tr style='font-weight: bold; background: #f0f0f0;'><td colspan='2'>TOTAL</td><td colspan='3'>\$" . number_format($total, 2) . "</td></tr>";
    echo "</table>";
}

// 3. Calcular balance usando el método de la clase
echo "<h3>3. Balance calculado usando getCustodyBalance():</h3>";
$balance = $projectTransaction->getCustodyBalance($serviceId);
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Concepto</th><th>Valor</th></tr>";
echo "<tr><td>Total Inversión (income_ads)</td><td style='color: blue; font-weight: bold;'>\$" . number_format($balance['total_investment'], 2) . "</td></tr>";
echo "<tr><td>Total Consumido (expense_ads_consumed)</td><td>\$" . number_format($balance['total_consumed'], 2) . "</td></tr>";
$balanceColor = $balance['balance'] < 0 ? 'red' : 'green';
echo "<tr><td>Saldo en Custodia</td><td style='color: {$balanceColor}; font-weight: bold;'>\$" . number_format($balance['balance'], 2) . "</td></tr>";
echo "</table>";

// 4. Simular el código de admin_client_detail.php
echo "<h3>4. Simulación del código de visualización:</h3>";
$isAdsService = false;
if (isset($svc['is_ads_service'])) {
    $isAdsValue = $svc['is_ads_service'];
    $isAdsService = ($isAdsValue == 1 || 
                    $isAdsValue === true || 
                    $isAdsValue === '1' ||
                    $isAdsValue === 1 ||
                    intval($isAdsValue) === 1 ||
                    $isAdsValue === 'true');
}

$adsInvestment = $balance['total_investment'];

echo "<p><strong>is_ads_service detectado:</strong> " . ($isAdsService ? '✅ SÍ' : '❌ NO') . "</p>";
echo "<p><strong>Valor de is_ads_service:</strong> " . var_export($svc['is_ads_service'], true) . "</p>";
echo "<p><strong>Inversión calculada:</strong> \$" . number_format($adsInvestment, 2) . "</p>";
echo "<p><strong>¿Se mostraría?</strong> " . ($isAdsService && $adsInvestment > 0 ? '✅ SÍ' : '❌ NO') . "</p>";

if ($isAdsService && $adsInvestment > 0) {
    echo "<div style='border: 2px solid green; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Tarifa:</strong> \$" . number_format($svc['monthly_fee'], 2) . "</p>";
    echo "<p style='color: blue; font-size: 12px;'><strong>Ads:</strong> \$" . number_format($adsInvestment, 2) . "</p>";
    echo "</div>";
} else {
    echo "<div style='border: 2px solid red; padding: 10px; margin: 10px 0;'>";
    echo "<p style='color: red;'>⚠️ NO se mostraría porque:</p>";
    if (!$isAdsService) {
        echo "<p>- El servicio NO está marcado como Ads (is_ads_service = " . var_export($svc['is_ads_service'], true) . ")</p>";
    }
    if ($adsInvestment <= 0) {
        echo "<p>- No hay inversión registrada (total = \$" . number_format($adsInvestment, 2) . ")</p>";
    }
    echo "</div>";
}

// 5. Corregir transacciones si se solicita
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

?>

