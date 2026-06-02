<?php
/**
 * Script de diagnóstico completo para verificar la lógica de inversiones en Ads
 * Analiza: inversión inicial, pagos desglosados, inversiones adicionales, y total
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/ProjectTransaction.php';
require_once 'classes/Service.php';

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($serviceId <= 0) {
    die("Por favor proporciona un service_id válido: diagnose_ads_investment_logic.php?service_id=5");
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $projectTransaction = new ProjectTransaction();
    $service = new Service();
    
    echo "<h2>🔍 Diagnóstico Completo: Lógica de Inversiones en Ads</h2>\n";
    echo "<h3>Service ID: $serviceId</h3>\n";
    echo "<hr>\n";
    
    // 1. Información del servicio
    $stmt = $db->prepare("SELECT * FROM services WHERE id = :service_id");
    $stmt->execute([':service_id' => $serviceId]);
    $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$serviceData) {
        die("<p style='color: red;'>❌ No se encontró el servicio con ID $serviceId</p>");
    }
    
    echo "<h3>1. Información del Servicio</h3>\n";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Campo</th><th>Valor</th></tr>\n";
    echo "<tr><td><strong>ID</strong></td><td>" . htmlspecialchars($serviceData['id']) . "</td></tr>\n";
    echo "<tr><td><strong>Nombre</strong></td><td>" . htmlspecialchars($serviceData['service_name']) . "</td></tr>\n";
    echo "<tr><td><strong>is_ads_service</strong></td><td>" . ($serviceData['is_ads_service'] ? '<span style="color: green;">✅ SÍ (1)</span>' : '<span style="color: red;">❌ NO (0)</span>') . "</td></tr>\n";
    echo "<tr><td><strong>initial_investment_amount</strong></td><td>$" . number_format($serviceData['initial_investment_amount'] ?? 0, 2) . "</td></tr>\n";
    echo "<tr><td><strong>monthly_fee</strong></td><td>$" . number_format($serviceData['monthly_fee'] ?? 0, 2) . "</td></tr>\n";
    echo "<tr><td><strong>client_id</strong></td><td>" . htmlspecialchars($serviceData['client_id']) . "</td></tr>\n";
    echo "</table>\n";
    
    if (!$serviceData['is_ads_service']) {
        echo "<p style='color: orange;'><strong>⚠️ ADVERTENCIA:</strong> Este servicio NO está marcado como Ads (is_ads_service = 0).</p>\n";
        echo "<p>Por eso puede que no se muestre la inversión en la interfaz.</p>\n";
    }
    
    echo "<hr>\n";
    
    // 2. Todas las transacciones income_ads para este servicio
    echo "<h3>2. Todas las Transacciones income_ads</h3>\n";
    
    $allTransactions = $projectTransaction->getTransactionsByService($serviceId, [
        'transaction_type' => 'income_ads'
    ]);
    
    if (empty($allTransactions)) {
        echo "<p style='color: orange;'><strong>⚠️ No hay transacciones income_ads para este servicio.</strong></p>\n";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Monto</th><th>Descripción</th><th>Payment ID</th><th>Platform</th><th>Fecha</th><th>Origen</th></tr>\n";
        
        $totalManual = 0;
        $inversionInicial = 0;
        $inversionPagos = 0;
        $inversionAdicional = 0;
        
        foreach ($allTransactions as $tx) {
            $amount = floatval($tx['amount'] ?? 0);
            $totalManual += $amount;
            
            // Determinar origen
            $origen = '';
            if (empty($tx['payment_id'])) {
                if (stripos($tx['description'] ?? '', 'inicial') !== false) {
                    $origen = '<span style="color: blue;">📌 Inversión Inicial</span>';
                    $inversionInicial += $amount;
                } else {
                    $origen = '<span style="color: green;">➕ Inversión Adicional</span>';
                    $inversionAdicional += $amount;
                }
            } else {
                $origen = '<span style="color: purple;">💰 De Pago</span>';
                $inversionPagos += $amount;
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($tx['id']) . "</td>";
            echo "<td>$" . number_format($amount, 2) . "</td>";
            echo "<td>" . htmlspecialchars($tx['description'] ?? '-') . "</td>";
            echo "<td>" . ($tx['payment_id'] ? htmlspecialchars($tx['payment_id']) : '<strong style="color: blue;">MANUAL</strong>') . "</td>";
            echo "<td>" . htmlspecialchars($tx['platform'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($tx['transaction_date']) . "</td>";
            echo "<td>$origen</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<h4>Desglose por Origen:</h4>\n";
        echo "<ul>\n";
        echo "<li><strong>Inversión Inicial:</strong> $" . number_format($inversionInicial, 2) . "</li>\n";
        echo "<li><strong>De Pagos (desglosados):</strong> $" . number_format($inversionPagos, 2) . "</li>\n";
        echo "<li><strong>Inversiones Adicionales:</strong> $" . number_format($inversionAdicional, 2) . "</li>\n";
        echo "<li><strong>Total Manual:</strong> <strong style='color: green; font-size: 1.2em;'>$" . number_format($totalManual, 2) . "</strong></li>\n";
        echo "</ul>\n";
    }
    
    echo "<hr>\n";
    
    // 3. Balance usando getCustodyBalance()
    echo "<h3>3. Balance usando getCustodyBalance()</h3>\n";
    
    $balance = $projectTransaction->getCustodyBalance($serviceId);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Concepto</th><th>Valor</th></tr>\n";
    echo "<tr><td><strong>Total Inversión (income_ads)</strong></td><td style='color: green; font-size: 1.2em;'><strong>$" . number_format($balance['total_investment'], 2) . "</strong></td></tr>\n";
    echo "<tr><td><strong>Total Consumido (expense_ads_consumed)</strong></td><td>$" . number_format($balance['total_consumed'], 2) . "</td></tr>\n";
    echo "<tr><td><strong>Balance</strong></td><td>$" . number_format($balance['balance'], 2) . "</td></tr>\n";
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // 4. Verificar coincidencia
    echo "<h3>4. Verificación</h3>\n";
    
    $coincide = abs(($totalManual ?? 0) - $balance['total_investment']) < 0.01;
    
    echo "<p><strong>Total calculado manualmente:</strong> $" . number_format($totalManual ?? 0, 2) . "</p>\n";
    echo "<p><strong>Total según getCustodyBalance():</strong> $" . number_format($balance['total_investment'], 2) . "</p>\n";
    echo "<p><strong>¿Coinciden?</strong> " . ($coincide ? "<span style='color: green; font-size: 1.2em;'>✅ SÍ</span>" : "<span style='color: red; font-size: 1.2em;'>❌ NO</span>") . "</p>\n";
    
    if (!$coincide) {
        $diferencia = abs(($totalManual ?? 0) - $balance['total_investment']);
        echo "<p style='color: red;'><strong>⚠️ Diferencia:</strong> $" . number_format($diferencia, 2) . "</p>\n";
    }
    
    echo "<hr>\n";
    
    // 5. Verificar pagos relacionados
    echo "<h3>5. Pagos Relacionados</h3>\n";
    
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.amount,
            p.payment_date,
            p.status,
            pt_fee.amount as fee_amount,
            pt_ads.amount as ads_amount
        FROM payments p
        LEFT JOIN project_transactions pt_fee ON p.id = pt_fee.payment_id AND pt_fee.transaction_type = 'income_fee'
        LEFT JOIN project_transactions pt_ads ON p.id = pt_ads.payment_id AND pt_ads.transaction_type = 'income_ads'
        WHERE p.service_id = :service_id
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute([':service_id' => $serviceId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "<p>No hay pagos registrados para este servicio.</p>\n";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Payment ID</th><th>Monto Total</th><th>Honorarios</th><th>Inversión</th><th>Fecha</th><th>Estado</th></tr>\n";
        
        foreach ($payments as $p) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($p['id']) . "</td>";
            echo "<td>$" . number_format($p['amount'], 2) . "</td>";
            echo "<td>" . ($p['fee_amount'] ? "$" . number_format($p['fee_amount'], 2) : '-') . "</td>";
            echo "<td>" . ($p['ads_amount'] ? "<strong style='color: green;'>$" . number_format($p['ads_amount'], 2) . "</strong>" : '-') . "</td>";
            echo "<td>" . htmlspecialchars($p['payment_date']) . "</td>";
            echo "<td>" . htmlspecialchars($p['status']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<hr>\n";
    
    // 6. Resumen y recomendaciones
    echo "<h3>6. Resumen y Diagnóstico</h3>\n";
    
    $issues = [];
    $success = [];
    
    if (!$serviceData['is_ads_service']) {
        $issues[] = "El servicio NO está marcado como Ads (is_ads_service = 0)";
    } else {
        $success[] = "El servicio está marcado como Ads correctamente";
    }
    
    if (empty($allTransactions)) {
        $issues[] = "No hay transacciones income_ads registradas";
    } else {
        $success[] = "Hay " . count($allTransactions) . " transacciones income_ads";
    }
    
    if (!$coincide) {
        $issues[] = "El total manual no coincide con getCustodyBalance()";
    } else {
        $success[] = "El cálculo del total es correcto";
    }
    
    if ($serviceData['initial_investment_amount'] > 0 && $inversionInicial == 0) {
        $issues[] = "Hay initial_investment_amount pero no hay transacción income_ads inicial";
    } elseif ($serviceData['initial_investment_amount'] > 0 && $inversionInicial > 0) {
        $success[] = "La inversión inicial está registrada correctamente";
    }
    
    if (!empty($success)) {
        echo "<h4 style='color: green;'>✅ Aspectos Correctos:</h4>\n";
        echo "<ul>\n";
        foreach ($success as $s) {
            echo "<li style='color: green;'>$s</li>\n";
        }
        echo "</ul>\n";
    }
    
    if (!empty($issues)) {
        echo "<h4 style='color: red;'>❌ Problemas Encontrados:</h4>\n";
        echo "<ul>\n";
        foreach ($issues as $issue) {
            echo "<li style='color: red;'>$issue</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p style='color: green; font-size: 1.2em;'><strong>✅ No se encontraron problemas. La lógica está funcionando correctamente.</strong></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
