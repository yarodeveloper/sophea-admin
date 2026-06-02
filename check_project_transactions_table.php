<?php
/**
 * Script de verificación: Verificar que la tabla project_transactions existe y tiene la estructura correcta
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Verificación de Tabla project_transactions</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'project_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'><strong>❌ ERROR: La tabla 'project_transactions' NO existe.</strong></p>";
        echo "<p>Necesitas ejecutar el script: <code>database/migrate_project_transactions.sql</code></p>";
        exit;
    }
    
    echo "<p style='color: green;'><strong>✅ La tabla 'project_transactions' existe.</strong></p>";
    
    // Verificar estructura de la tabla
    $stmt = $db->query("DESCRIBE project_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estructura de la tabla:</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar ENUM de platform
    $stmt = $db->query("SHOW COLUMNS FROM project_transactions WHERE Field = 'platform'");
    $platformCol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($platformCol) {
        echo "<h2>ENUM de platform:</h2>";
        echo "<p><code>{$platformCol['Type']}</code></p>";
        
        // Verificar si incluye 'whatsapp'
        if (strpos($platformCol['Type'], 'whatsapp') !== false) {
            echo "<p style='color: green;'><strong>✅ El ENUM incluye 'whatsapp'</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ El ENUM NO incluye 'whatsapp'</strong></p>";
            echo "<p>Necesitas ejecutar el script: <code>database/update_platform_enum.sql</code></p>";
        }
    }
    
    // Verificar si hay datos
    $stmt = $db->query("SELECT COUNT(*) as count FROM project_transactions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Datos en la tabla:</h2>";
    echo "<p>Total de transacciones: <strong>{$count['count']}</strong></p>";
    
    if ($count['count'] > 0) {
        echo "<h3>Últimas 5 transacciones:</h3>";
        $stmt = $db->query("SELECT * FROM project_transactions ORDER BY created_at DESC LIMIT 5");
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Service ID</th><th>Tipo</th><th>Monto</th><th>Plataforma</th><th>Fecha</th></tr>";
        foreach ($transactions as $t) {
            $platform = isset($t['platform']) && $t['platform'] !== null ? $t['platform'] : '-';
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['service_id']}</td>";
            echo "<td>{$t['transaction_type']}</td>";
            echo "<td>{$t['amount']}</td>";
            echo "<td>{$platform}</td>";
            echo "<td>{$t['transaction_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar servicios con is_ads_service
    $stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE is_ads_service = 1");
    $adsServices = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Servicios de Ads:</h2>";
    echo "<p>Total de servicios marcados como Ads: <strong>{$adsServices['count']}</strong></p>";
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>✅ Verificación completada</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

