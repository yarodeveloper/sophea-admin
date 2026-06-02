<?php
/**
 * Script de prueba: Probar creación de costo de servicio
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/ProjectTransaction.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Prueba de Creación de Costo de Servicio</h1>";

try {
    $projectTransaction = new ProjectTransaction();
    
    // Obtener un servicio de prueba
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, client_id, service_name FROM services LIMIT 1");
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        echo "<p style='color: red;'><strong>❌ No hay servicios en la base de datos para probar.</strong></p>";
        exit;
    }
    
    echo "<p><strong>Servicio de prueba:</strong> {$service['service_name']} (ID: {$service['id']}, Client ID: {$service['client_id']})</p>";
    
    // Intentar crear una transacción de prueba
    $result = $projectTransaction->recordConsumedBudget(
        $service['id'],
        $service['client_id'],
        100.00,
        'facebook',
        date('Y-m-d'),
        date('Y-m-d', strtotime('+30 days')),
        'Prueba de costo de servicio',
        1
    );
    
    if ($result) {
        echo "<p style='color: green;'><strong>✅ ÉXITO: Costo creado con ID: $result</strong></p>";
        
        // Verificar que se guardó
        $stmt = $db->prepare("SELECT * FROM project_transactions WHERE id = ?");
        $stmt->execute([$result]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            echo "<h2>Transacción creada:</h2>";
            echo "<pre>";
            print_r($transaction);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'><strong>❌ ERROR: La transacción no se encontró después de crearla.</strong></p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ ERROR: No se pudo crear el costo.</strong></p>";
        $error = $projectTransaction->getLastError();
        if ($error) {
            echo "<p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>";
        }
    }
    
    // Verificar estructura de la tabla
    echo "<h2>Verificación de estructura:</h2>";
    $stmt = $db->query("DESCRIBE project_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ EXCEPCIÓN: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

