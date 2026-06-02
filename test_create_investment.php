<?php
/**
 * Script de prueba para crear una inversión adicional manualmente
 * Uso: test_create_investment.php?service_id=10&amount=100
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/ProjectTransaction.php';
require_once 'classes/Service.php';

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

if ($serviceId <= 0 || $amount <= 0) {
    die("Uso: test_create_investment.php?service_id=10&amount=100");
}

try {
    $projectTransaction = new ProjectTransaction();
    $service = new Service();
    
    // Obtener información del servicio
    $serviceInfo = $service->getServiceById($serviceId);
    
    if (!$serviceInfo) {
        die("<p style='color: red;'>❌ No se encontró el servicio con ID $serviceId</p>");
    }
    
    echo "<h2>Prueba: Crear Inversión Adicional</h2>\n";
    echo "<hr>\n";
    
    echo "<h3>Información del Servicio:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>ID:</strong> " . htmlspecialchars($serviceInfo['id']) . "</li>\n";
    echo "<li><strong>Nombre:</strong> " . htmlspecialchars($serviceInfo['service_name']) . "</li>\n";
    echo "<li><strong>is_ads_service:</strong> " . ($serviceInfo['is_ads_service'] ? 'SÍ (1)' : 'NO (0)') . "</li>\n";
    echo "<li><strong>client_id:</strong> " . htmlspecialchars($serviceInfo['client_id']) . "</li>\n";
    echo "</ul>\n";
    
    if (!$serviceInfo['is_ads_service']) {
        die("<p style='color: red;'>❌ Este servicio NO está marcado como Ads (is_ads_service = 0)</p>");
    }
    
    echo "<hr>\n";
    echo "<h3>Intentando crear transacción income_ads:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Service ID:</strong> $serviceId</li>\n";
    echo "<li><strong>Client ID:</strong> " . htmlspecialchars($serviceInfo['client_id']) . "</li>\n";
    echo "<li><strong>Amount:</strong> $" . number_format($amount, 2) . "</li>\n";
    echo "<li><strong>Transaction Type:</strong> income_ads</li>\n";
    echo "</ul>\n";
    
    // Crear la transacción
    $transactionData = [
        'service_id' => $serviceId,
        'client_id' => $serviceInfo['client_id'],
        'transaction_type' => 'income_ads',
        'amount' => $amount,
        'currency' => 'MXN',
        'description' => 'Inversión adicional de prueba',
        'platform' => 'meta',
        'transaction_date' => date('Y-m-d'),
        'created_by' => 1 // Usuario de prueba
    ];
    
    echo "<h4>Datos de la transacción:</h4>\n";
    echo "<pre>" . print_r($transactionData, true) . "</pre>\n";
    
    $result = $projectTransaction->createTransaction($transactionData);
    
    echo "<hr>\n";
    
    if ($result) {
        echo "<p style='color: green; font-size: 1.2em;'><strong>✅ ÉXITO: Transacción creada con ID: $result</strong></p>\n";
        
        // Verificar que se creó
        $balance = $projectTransaction->getCustodyBalance($serviceId);
        echo "<h3>Balance actualizado:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Total Inversión:</strong> $" . number_format($balance['total_investment'], 2) . "</li>\n";
        echo "</ul>\n";
        
        // Mostrar todas las transacciones
        $allTransactions = $projectTransaction->getTransactionsByService($serviceId, [
            'transaction_type' => 'income_ads'
        ]);
        
        echo "<h3>Todas las transacciones income_ads:</h3>\n";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Monto</th><th>Descripción</th><th>Fecha</th></tr>\n";
        
        foreach ($allTransactions as $tx) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($tx['id']) . "</td>";
            echo "<td>$" . number_format($tx['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($tx['description'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($tx['transaction_date']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } else {
        echo "<p style='color: red; font-size: 1.2em;'><strong>❌ ERROR: No se pudo crear la transacción</strong></p>\n";
        
        $lastError = $projectTransaction->getLastError();
        if ($lastError) {
            echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($lastError) . "</p>\n";
        } else {
            echo "<p style='color: orange;'>No hay mensaje de error disponible. Revisa los logs del servidor.</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>EXCEPCIÓN:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
