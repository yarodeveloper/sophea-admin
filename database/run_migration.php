<?php
/**
 * SOPHEA - Script de Migración de Base de Datos
 * 
 * Este script ejecuta la migración de la base de datos de forma segura
 * desde el navegador. Úsalo solo si no tienes acceso a línea de comandos.
 * 
 * IMPORTANTE: Elimina este archivo después de ejecutar la migración
 * por seguridad.
 */

// Configuración de seguridad
$ALLOWED_IPS = ['127.0.0.1', '::1']; // Agrega tu IP aquí si es necesario
$MIGRATION_PASSWORD = 'sophea2024'; // Cambia esta contraseña

// Verificar IP (opcional, comenta si no necesitas esta validación)
// if (!in_array($_SERVER['REMOTE_ADDR'], $ALLOWED_IPS)) {
//     die('Acceso denegado. Solo IPs autorizadas pueden ejecutar este script.');
// }

// Verificar contraseña
if (!isset($_POST['password']) || $_POST['password'] !== $MIGRATION_PASSWORD) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Migración de Base de Datos - SOPHEA</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 { color: #667eea; }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            button {
                background: #667eea;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            }
            button:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 Migración de Base de Datos</h1>
            <div class="warning">
                <strong>⚠️ ADVERTENCIA:</strong> Este script modificará tu base de datos.
                Asegúrate de tener un backup antes de continuar.
            </div>
            <form method="POST">
                <label>Contraseña de migración:</label>
                <input type="password" name="password" required autofocus>
                <button type="submit">Ejecutar Migración</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Cargar configuración de base de datos
require_once __DIR__ . '/../config_db.php';

// Leer el archivo SQL
$sqlFile = __DIR__ . '/migrate_production_2024.sql';
if (!file_exists($sqlFile)) {
    die('Error: No se encontró el archivo de migración.');
}

$sql = file_get_contents($sqlFile);

// Dividir en consultas individuales
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        return !empty($query) && 
               !preg_match('/^\s*--/', $query) && 
               !preg_match('/^\s*\/\*/', $query);
    }
);

// Ejecutar migración
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejecutando Migración - SOPHEA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .query { 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            background: #f3f4f6; 
            padding: 5px; 
            margin: 5px 0;
            border-left: 3px solid #667eea;
        }
        .stats {
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Ejecutando Migración de Base de Datos</h1>
        
        <?php
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            echo '<div class="info">Conectado a la base de datos: <strong>' . DB_NAME . '</strong></div>';
            echo '<p>Ejecutando ' . count($queries) . ' consultas...</p>';
            
            foreach ($queries as $index => $query) {
                // Saltar comentarios y líneas vacías
                if (empty(trim($query)) || preg_match('/^\s*--/', $query)) {
                    continue;
                }
                
                // Saltar bloques de comentarios
                if (preg_match('/^\s*\/\*/', $query)) {
                    continue;
                }
                
                try {
                    // Ejecutar consulta
                    $pdo->exec($query);
                    $successCount++;
                    
                    // Mostrar solo las primeras 50 caracteres de cada consulta
                    $queryPreview = substr(trim($query), 0, 100);
                    if (strlen($query) > 100) {
                        $queryPreview .= '...';
                    }
                    
                    // Solo mostrar CREATE TABLE y ALTER TABLE
                    if (preg_match('/^\s*(CREATE|ALTER|INSERT|UPDATE)/i', $query)) {
                        echo '<div class="query">✓ ' . htmlspecialchars($queryPreview) . '</div>';
                    }
                    
                } catch (PDOException $e) {
                    $errorCount++;
                    $errorMsg = $e->getMessage();
                    
                    // Ignorar errores de "table already exists" y similares
                    if (strpos($errorMsg, 'already exists') !== false || 
                        strpos($errorMsg, 'Duplicate') !== false ||
                        strpos($errorMsg, 'Unknown column') !== false) {
                        // Estos son errores esperados, no críticos
                        continue;
                    }
                    
                    $errors[] = [
                        'query' => substr($query, 0, 100),
                        'error' => $errorMsg
                    ];
                    
                    echo '<div class="error">✗ Error: ' . htmlspecialchars($errorMsg) . '</div>';
                }
            }
            
            echo '<div class="stats">';
            echo '<h3>📊 Resumen de la Migración</h3>';
            echo '<p><strong>Consultas exitosas:</strong> ' . $successCount . '</p>';
            echo '<p><strong>Errores:</strong> ' . $errorCount . '</p>';
            
            if ($errorCount > 0 && count($errors) > 0) {
                echo '<h4>Errores encontrados:</h4>';
                foreach ($errors as $error) {
                    echo '<div class="error">';
                    echo '<strong>Consulta:</strong> ' . htmlspecialchars($error['query']) . '<br>';
                    echo '<strong>Error:</strong> ' . htmlspecialchars($error['error']);
                    echo '</div>';
                }
            }
            
            if ($successCount > 0) {
                echo '<div class="success">';
                echo '<strong>✅ Migración completada exitosamente!</strong><br>';
                echo 'Las tablas han sido creadas o actualizadas.';
                echo '</div>';
            }
            
            echo '</div>';
            
            // Verificar tablas creadas
            echo '<h3>📋 Tablas en la base de datos:</h3>';
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
            echo '<p><strong>Total de tablas:</strong> ' . count($tables) . '</p>';
            
        } catch (PDOException $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error de conexión:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fef3c7; border-radius: 5px;">
            <strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (run_migration.php) después de ejecutar la migración por seguridad.
        </div>
    </div>
</body>
</html>

