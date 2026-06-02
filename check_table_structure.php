<?php
/**
 * Script para verificar la estructura real de la tabla project_transactions
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Verificación de Estructura de Tabla: project_transactions</h2>\n";
    echo "<hr>\n";
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'project_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        die("<p style='color: red;'>❌ La tabla 'project_transactions' NO existe</p>");
    }
    
    echo "<p style='color: green;'>✅ La tabla 'project_transactions' existe</p>\n";
    echo "<hr>\n";
    
    // Obtener estructura de la tabla
    $stmt = $db->query("DESCRIBE project_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Columnas de la tabla:</h3>\n";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    echo "<hr>\n";
    
    // Verificar campos específicos
    $requiredFields = ['service_id', 'client_id', 'transaction_type', 'amount', 'billing_period_start', 'billing_period_end', 'platform'];
    $fieldNames = array_column($columns, 'Field');
    
    echo "<h3>Verificación de campos requeridos:</h3>\n";
    echo "<ul>\n";
    
    foreach ($requiredFields as $field) {
        if (in_array($field, $fieldNames)) {
            echo "<li style='color: green;'>✅ Campo '$field' existe</li>\n";
        } else {
            echo "<li style='color: red;'>❌ Campo '$field' NO existe</li>\n";
        }
    }
    
    echo "</ul>\n";
    echo "<hr>\n";
    
    // Verificar valores ENUM de platform
    $stmt = $db->query("SHOW COLUMNS FROM project_transactions WHERE Field = 'platform'");
    $platformCol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($platformCol) {
        echo "<h3>Valores ENUM de 'platform':</h3>\n";
        echo "<p>" . htmlspecialchars($platformCol['Type']) . "</p>\n";
        
        // Extraer valores ENUM
        preg_match("/ENUM\((.*)\)/", $platformCol['Type'], $matches);
        if (isset($matches[1])) {
            $enumValues = array_map(function($v) {
                return trim($v, "'\"");
            }, explode(',', $matches[1]));
            
            echo "<ul>\n";
            foreach ($enumValues as $val) {
                echo "<li>" . htmlspecialchars($val) . "</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
    echo "<hr>\n";
    
    // Contar registros
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_transactions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Total de registros:</h3>\n";
    echo "<p><strong>" . $count['total'] . "</strong> registros en la tabla</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
