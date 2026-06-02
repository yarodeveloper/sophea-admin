<?php
/**
 * Script para verificar las inversiones de un servicio específico
 * Uso: check_service_investment.php?service_id=5
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/ProjectTransaction.php';

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($serviceId <= 0) {
    die("Por favor proporciona un service_id válido: check_service_investment.php?service_id=5");
}

try {
    $projectTransaction = new ProjectTransaction();
    
    echo "<h2>Verificación de Inversiones - Service ID: $serviceId</h2>\n";
    echo "<hr>\n";
    
    // 1. Obtener todas las transacciones income_ads para este servicio
    $allTransactions = $projectTransaction->getTransactionsByService($serviceId, [
        'transaction_type' => 'income_ads'
    ]);
    
    echo "<h3>Transacciones income_ads para este servicio:</h3>\n";
    
    if (empty($allTransactions)) {
        echo "<p style='color: orange;'><strong>⚠️ No hay transacciones income_ads para este servicio.</strong></p>\n";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Monto</th><th>Descripción</th><th>Payment ID</th><th>Platform</th><th>Fecha</th><th>Creado</th></tr>\n";
        
        $total = 0;
        foreach ($allTransactions as $tx) {
            $total += floatval($tx['amount'] ?? 0);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($tx['id']) . "</td>";
            echo "<td>$" . number_format($tx['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($tx['description'] ?? '-') . "</td>";
            echo "<td>" . ($tx['payment_id'] ? htmlspecialchars($tx['payment_id']) : '<strong style="color: blue;">MANUAL</strong>') . "</td>";
            echo "<td>" . htmlspecialchars($tx['platform'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($tx['transaction_date']) . "</td>";
            echo "<td>" . htmlspecialchars($tx['created_at']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<p><strong>Total calculado manualmente:</strong> $" . number_format($total, 2) . "</p>\n";
    }
    
    echo "<hr>\n";
    
    // 2. Obtener el balance usando getCustodyBalance()
    $balance = $projectTransaction->getCustodyBalance($serviceId);
    
    echo "<h3>Balance usando getCustodyBalance():</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Total Inversión:</strong> $" . number_format($balance['total_investment'], 2) . "</li>\n";
    echo "<li><strong>Total Consumido:</strong> $" . number_format($balance['total_consumed'], 2) . "</li>\n";
    echo "<li><strong>Balance:</strong> $" . number_format($balance['balance'], 2) . "</li>\n";
    echo "</ul>\n";
    
    echo "<hr>\n";
    
    // 3. Verificar información del servicio
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $db->prepare("SELECT * FROM services WHERE id = :service_id");
    $stmt->execute([':service_id' => $serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service) {
        echo "<h3>Información del Servicio:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>ID:</strong> " . htmlspecialchars($service['id']) . "</li>\n";
        echo "<li><strong>Nombre:</strong> " . htmlspecialchars($service['service_name']) . "</li>\n";
        echo "<li><strong>is_ads_service:</strong> " . ($service['is_ads_service'] ? 'SÍ (1)' : 'NO (0)') . "</li>\n";
        echo "<li><strong>initial_investment_amount:</strong> $" . number_format($service['initial_investment_amount'] ?? 0, 2) . "</li>\n";
        echo "<li><strong>Client ID:</strong> " . htmlspecialchars($service['client_id']) . "</li>\n";
        echo "</ul>\n";
        
        if (!$service['is_ads_service']) {
            echo "<p style='color: orange;'><strong>⚠️ ADVERTENCIA:</strong> Este servicio NO está marcado como 'is_ads_service = 1'.</p>\n";
            echo "<p>Por eso puede que no se muestre la inversión en la interfaz.</p>\n";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ ERROR:</strong> No se encontró el servicio con ID $serviceId.</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>Resumen:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Transacciones encontradas:</strong> " . count($allTransactions) . "</li>\n";
    echo "<li><strong>Total calculado manualmente:</strong> $" . number_format($total ?? 0, 2) . "</li>\n";
    echo "<li><strong>Total según getCustodyBalance():</strong> $" . number_format($balance['total_investment'], 2) . "</li>\n";
    echo "<li><strong>¿Coinciden?</strong> " . (abs(($total ?? 0) - $balance['total_investment']) < 0.01 ? "✅ SÍ" : "❌ NO") . "</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
