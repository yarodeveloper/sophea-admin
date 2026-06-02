<?php
/**
 * Script para ver los últimos logs de PHP
 * Útil cuando trabajas en local y no sabes dónde están los logs
 */

// Intentar leer los logs de diferentes ubicaciones comunes en XAMPP
$logPaths = [
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/apache/logs/error.log',
    ini_get('error_log'),
    __DIR__ . '/error_log',
    __DIR__ . '/php_error_log'
];

echo "<h2>📋 Últimos Logs de PHP</h2>\n";
echo "<hr>\n";

$foundLogs = false;

foreach ($logPaths as $logPath) {
    if ($logPath && file_exists($logPath) && is_readable($logPath)) {
        echo "<h3>📁 Archivo: " . htmlspecialchars($logPath) . "</h3>\n";
        
        // Leer las últimas 100 líneas
        $lines = file($logPath);
        $lastLines = array_slice($lines, -100);
        
        echo "<div style='background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 600px; overflow-y: auto;'>\n";
        
        // Filtrar solo líneas relacionadas con nuestro código
        $relevantLines = array_filter($lastLines, function($line) {
            return stripos($line, 'DEBUG:') !== false || 
                   stripos($line, 'createTransaction') !== false ||
                   stripos($line, 'income_ads') !== false ||
                   stripos($line, 'create_service_cost') !== false ||
                   stripos($line, 'admin_client_detail') !== false;
        });
        
        if (empty($relevantLines)) {
            echo "<p style='color: #888;'>No hay logs relevantes en las últimas 100 líneas.</p>\n";
            echo "<p style='color: #888;'>Mostrando todas las últimas líneas:</p>\n";
            $relevantLines = $lastLines;
        }
        
        foreach ($relevantLines as $line) {
            // Resaltar líneas de DEBUG
            if (stripos($line, 'DEBUG:') !== false) {
                echo "<div style='color: #4ec9b0;'>" . htmlspecialchars($line) . "</div>\n";
            } elseif (stripos($line, 'ERROR') !== false || stripos($line, 'Error') !== false) {
                echo "<div style='color: #f48771;'>" . htmlspecialchars($line) . "</div>\n";
            } elseif (stripos($line, 'SUCCESS') !== false || stripos($line, 'Success') !== false) {
                echo "<div style='color: #89d185;'>" . htmlspecialchars($line) . "</div>\n";
            } else {
                echo "<div>" . htmlspecialchars($line) . "</div>\n";
            }
        }
        
        echo "</div>\n";
        echo "<hr>\n";
        $foundLogs = true;
    }
}

if (!$foundLogs) {
    echo "<p style='color: orange;'>⚠️ No se encontraron archivos de log en las ubicaciones comunes.</p>\n";
    echo "<p>Ubicaciones verificadas:</p>\n";
    echo "<ul>\n";
    foreach ($logPaths as $path) {
        echo "<li>" . htmlspecialchars($path ?: 'NULL') . "</li>\n";
    }
    echo "</ul>\n";
    
    echo "<hr>\n";
    echo "<h3>Configuración actual de PHP:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>error_log:</strong> " . htmlspecialchars(ini_get('error_log') ?: 'No configurado') . "</li>\n";
    echo "<li><strong>display_errors:</strong> " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</li>\n";
    echo "<li><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'ON' : 'OFF') . "</li>\n";
    echo "</ul>\n";
}

echo "<hr>\n";
echo "<p><a href='admin_client_detail.php'>← Volver</a></p>\n";
