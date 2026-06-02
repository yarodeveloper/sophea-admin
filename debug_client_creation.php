<?php
/**
 * SOPHEA - Script de Diagnóstico para Creación de Clientes
 * 
 * Este script ayuda a identificar problemas al crear clientes
 * 
 * USO:
 * 1. Accede desde navegador: https://tudominio.com/debug_client_creation.php
 * 2. Revisa los resultados
 * 3. ⚠️ ELIMINA este archivo después de usar
 */

// Configuración de seguridad
$VERIFICATION_PASSWORD = 'sophea_debug_2025'; // ⚠️ Cambia esto antes de usar

// Verificar contraseña
if (!isset($_GET['pass']) || $_GET['pass'] !== $VERIFICATION_PASSWORD) {
    die('Acceso denegado. Proporciona la contraseña: ?pass=tu_password');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Creación de Clientes - SOPHEA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .test-form { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Creación de Clientes</h1>
        <p class="info">Fecha: <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        require_once 'config_db.php';
        require_once 'classes/Database.php';
        require_once 'classes/Client.php';
        
        $db = Database::getInstance()->getConnection();
        $client = new Client();
        
        // 1. Verificar estructura de la tabla clients
        echo '<div class="section">';
        echo '<h2>1. Estructura de la Tabla "clients"</h2>';
        
        try {
            $stmt = $db->query("DESCRIBE clients");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($col['Extra']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<p class="success">✅ Tabla "clients" existe y tiene estructura</p>';
        } catch (PDOException $e) {
            echo '<p class="error">❌ Error al verificar tabla: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 2. Probar generación de número de cliente
        echo '<div class="section">';
        echo '<h2>2. Prueba de Generación de Número de Cliente</h2>';
        
        try {
            $testNumber = $client->generateClientNumber();
            echo '<p><strong>Número generado:</strong> <code>' . htmlspecialchars($testNumber) . '</code></p>';
            
            if (preg_match('/^C-\d{4}-\d{3}$/', $testNumber)) {
                echo '<p class="success">✅ Formato de número válido</p>';
            } else {
                echo '<p class="error">❌ Formato de número inválido</p>';
            }
            
            // Verificar si ya existe
            $checkStmt = $db->prepare("SELECT id FROM clients WHERE client_number = ?");
            $checkStmt->execute([$testNumber]);
            if ($checkStmt->rowCount() > 0) {
                echo '<p class="warning">⚠️ Este número ya existe en la base de datos</p>';
            } else {
                echo '<p class="success">✅ Número único (no existe en BD)</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 3. Probar inserción de cliente de prueba
        echo '<div class="section">';
        echo '<h2>3. Prueba de Inserción de Cliente</h2>';
        
        if (isset($_POST['test_create'])) {
            $testData = [
                'company_name' => 'Empresa de Prueba ' . time(),
                'contact_name' => 'Contacto de Prueba',
                'email' => 'test' . time() . '@example.com',
                'phone' => '1234567890',
                'phone_country_code' => '+52',
                'whatsapp' => '1234567890',
                'whatsapp_country_code' => '+52',
                'address' => 'Dirección de prueba',
                'city' => 'Ciudad de prueba',
                'state' => 'Estado de prueba',
                'country' => 'México',
                'tax_id' => null,
                'website' => null,
                'industry' => 'Prueba',
                'client_type' => 'regular',
                'legal_risk' => 'low',
                'status' => 'prospect',
                'notes' => 'Cliente de prueba generado por script de diagnóstico',
                'created_by' => 1
            ];
            
            echo '<h3>Datos de prueba:</h3>';
            echo '<pre>' . htmlspecialchars(print_r($testData, true)) . '</pre>';
            
            try {
                $clientId = $client->createClient($testData);
                
                if ($clientId) {
                    echo '<p class="success">✅ Cliente creado exitosamente con ID: ' . $clientId . '</p>';
                    
                    // Obtener el cliente creado
                    $createdClient = $client->getClientById($clientId);
                    if ($createdClient) {
                        echo '<h3>Cliente creado:</h3>';
                        echo '<pre>' . htmlspecialchars(print_r($createdClient, true)) . '</pre>';
                    }
                    
                    // Opción para eliminar el cliente de prueba
                    if (isset($_POST['delete_test'])) {
                        $deleteStmt = $db->prepare("DELETE FROM clients WHERE id = ?");
                        $deleteStmt->execute([$clientId]);
                        echo '<p class="info">🗑️ Cliente de prueba eliminado</p>';
                    } else {
                        echo '<form method="POST" style="margin-top: 10px;">';
                        echo '<input type="hidden" name="test_create" value="1">';
                        echo '<input type="hidden" name="delete_test" value="1">';
                        echo '<button type="submit" style="background: #ef4444; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;">Eliminar Cliente de Prueba</button>';
                        echo '</form>';
                    }
                } else {
                    echo '<p class="error">❌ Error: createClient() retornó false</p>';
                    echo '<p class="info">Revisa los logs de PHP para más detalles</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Excepción: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
        } else {
            echo '<div class="test-form">';
            echo '<h3>Crear Cliente de Prueba</h3>';
            echo '<form method="POST">';
            echo '<input type="hidden" name="test_create" value="1">';
            echo '<p>Este formulario creará un cliente de prueba para verificar que la funcionalidad funciona correctamente.</p>';
            echo '<button type="submit" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Crear Cliente de Prueba</button>';
            echo '</form>';
            echo '</div>';
        }
        echo '</div>';
        
        // 4. Verificar últimos clientes creados
        echo '<div class="section">';
        echo '<h2>4. Últimos 5 Clientes Creados</h2>';
        
        try {
            $stmt = $db->query("SELECT id, client_number, company_name, contact_name, email, created_at FROM clients ORDER BY id DESC LIMIT 5");
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($clients)) {
                echo '<p class="info">No hay clientes en la base de datos</p>';
            } else {
                echo '<table>';
                echo '<tr><th>ID</th><th>Número</th><th>Empresa</th><th>Contacto</th><th>Email</th><th>Creado</th></tr>';
                foreach ($clients as $c) {
                    echo '<tr>';
                    echo '<td>' . $c['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($c['client_number']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['company_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['contact_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['created_at']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 5. Verificar permisos y configuración
        echo '<div class="section">';
        echo '<h2>5. Verificación de Configuración</h2>';
        
        echo '<ul>';
        echo '<li>PHP Version: ' . phpversion() . '</li>';
        echo '<li>PDO MySQL: ' . (extension_loaded('pdo_mysql') ? '<span class="success">✅ Instalado</span>' : '<span class="error">❌ No instalado</span>') . '</li>';
        echo '<li>Database Host: ' . DB_HOST . '</li>';
        echo '<li>Database Name: ' . DB_NAME . '</li>';
        echo '<li>Database User: ' . DB_USER . '</li>';
        echo '</ul>';
        echo '</div>';
        ?>
        
        <div style="background: #fff3cd; border: 2px solid #f59e0b; padding: 15px; margin-top: 20px; border-radius: 5px;">
            <h3>⚠️ IMPORTANTE</h3>
            <p><strong>Elimina este archivo después de usar:</strong></p>
            <code>rm debug_client_creation.php</code>
            <p>O elimínalo desde el panel de archivos de tu hosting.</p>
        </div>
    </div>
</body>
</html>

