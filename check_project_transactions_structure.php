<?php
/**
 * Script para verificar la estructura de la tabla project_transactions
 * y ver si el ENUM transaction_type incluye 'income_ads'
 */

require_once 'config.php';
require_once 'config_db.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>Verificación de Estructura: project_transactions</h2>\n";
    echo "<hr>\n";
    
    // 1. Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'project_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'><strong>❌ ERROR:</strong> La tabla 'project_transactions' NO EXISTE.</p>\n";
        echo "<p>Necesitas ejecutar el script de migración para crear la tabla.</p>\n";
        exit;
    }
    
    echo "<p style='color: green;'><strong>✅ La tabla 'project_transactions' existe.</strong></p>\n";
    echo "<hr>\n";
    
    // 2. Verificar la estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>\n";
    $stmt = $db->query("DESCRIBE project_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    $hasTransactionType = false;
    $transactionTypeEnum = '';
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>\n";
        
        if ($col['Field'] === 'transaction_type') {
            $hasTransactionType = true;
            $transactionTypeEnum = $col['Type'];
        }
    }
    echo "</table>\n";
    echo "<hr>\n";
    
    // 3. Verificar el campo transaction_type
    if (!$hasTransactionType) {
        echo "<p style='color: red;'><strong>❌ ERROR:</strong> El campo 'transaction_type' NO EXISTE en la tabla.</p>\n";
        exit;
    }
    
    echo "<p style='color: green;'><strong>✅ El campo 'transaction_type' existe.</strong></p>\n";
    echo "<p><strong>Tipo:</strong> " . htmlspecialchars($transactionTypeEnum) . "</p>\n";
    
    // 4. Extraer valores del ENUM
    if (preg_match("/ENUM\('([^']+)'(?:,'([^']+)')*(?:,'([^']+)')*\)/", $transactionTypeEnum, $matches)) {
        $enumValues = [];
        for ($i = 1; $i < count($matches); $i++) {
            if (!empty($matches[$i])) {
                $enumValues[] = $matches[$i];
            }
        }
        
        echo "<h3>Valores del ENUM transaction_type:</h3>\n";
        echo "<ul>\n";
        $hasIncomeAds = false;
        foreach ($enumValues as $value) {
            $isIncomeAds = ($value === 'income_ads');
            if ($isIncomeAds) {
                $hasIncomeAds = true;
                echo "<li style='color: green;'><strong>✅ '$value'</strong> (CORRECTO)</li>\n";
            } else {
                echo "<li>'$value'</li>\n";
            }
        }
        echo "</ul>\n";
        
        if (!$hasIncomeAds) {
            echo "<p style='color: red;'><strong>❌ ERROR:</strong> El valor 'income_ads' NO está en el ENUM.</p>\n";
            echo "<p>Necesitas modificar el ENUM para incluir 'income_ads'.</p>\n";
        } else {
            echo "<p style='color: green;'><strong>✅ El valor 'income_ads' está en el ENUM.</strong></p>\n";
        }
    }
    
    echo "<hr>\n";
    
    // 5. Verificar registros existentes
    echo "<h3>Registros existentes:</h3>\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_transactions");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total de registros:</strong> $total</p>\n";
    
    if ($total > 0) {
        // Contar por tipo de transacción
        $stmt = $db->query("
            SELECT 
                transaction_type,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM project_transactions
            GROUP BY transaction_type
        ");
        $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Tipo de Transacción</th><th>Cantidad</th><th>Total Monto</th></tr>\n";
        
        $hasIncomeAdsRecords = false;
        foreach ($byType as $row) {
            $isIncomeAds = ($row['transaction_type'] === 'income_ads');
            if ($isIncomeAds) {
                $hasIncomeAdsRecords = true;
            }
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['transaction_type']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['count']) . "</td>";
            echo "<td>$" . number_format($row['total_amount'], 2) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        if (!$hasIncomeAdsRecords) {
            echo "<p style='color: orange;'><strong>⚠️ ADVERTENCIA:</strong> No hay registros con transaction_type = 'income_ads'.</p>\n";
            echo "<p>Esto es normal si aún no se han creado inversiones en Ads.</p>\n";
        } else {
            echo "<p style='color: green;'><strong>✅ Hay registros con transaction_type = 'income_ads'.</strong></p>\n";
        }
    }
    
    echo "<hr>\n";
    
    // 6. Verificar registros de un servicio específico (si se proporciona)
    if (isset($_GET['service_id'])) {
        $serviceId = intval($_GET['service_id']);
        echo "<h3>Registros para Service ID: $serviceId</h3>\n";
        
        $stmt = $db->prepare("
            SELECT 
                id,
                transaction_type,
                amount,
                description,
                payment_id,
                transaction_date,
                created_at
            FROM project_transactions
            WHERE service_id = :service_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':service_id' => $serviceId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            echo "<p>No hay transacciones para este servicio.</p>\n";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
            echo "<tr><th>ID</th><th>Tipo</th><th>Monto</th><th>Descripción</th><th>Payment ID</th><th>Fecha</th></tr>\n";
            
            $totalIncomeAds = 0;
            foreach ($transactions as $tx) {
                if ($tx['transaction_type'] === 'income_ads') {
                    $totalIncomeAds += floatval($tx['amount']);
                }
                echo "<tr>";
                echo "<td>" . htmlspecialchars($tx['id']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($tx['transaction_type']) . "</strong></td>";
                echo "<td>$" . number_format($tx['amount'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($tx['description'] ?? '-') . "</td>";
                echo "<td>" . ($tx['payment_id'] ? htmlspecialchars($tx['payment_id']) : '-') . "</td>";
                echo "<td>" . htmlspecialchars($tx['transaction_date']) . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            
            echo "<p><strong>Total de inversión (income_ads):</strong> $" . number_format($totalIncomeAds, 2) . "</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Resumen:</h3>\n";
    echo "<ul>\n";
    echo "<li>" . ($tableExists ? "✅" : "❌") . " Tabla existe</li>\n";
    echo "<li>" . ($hasTransactionType ? "✅" : "❌") . " Campo transaction_type existe</li>\n";
    echo "<li>" . (isset($hasIncomeAds) && $hasIncomeAds ? "✅" : "❌") . " Valor 'income_ads' en ENUM</li>\n";
    echo "<li>" . (isset($hasIncomeAdsRecords) && $hasIncomeAdsRecords ? "✅" : "⚠️") . " Registros con income_ads</li>\n";
    echo "</ul>\n";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
