<?php
/**
 * SOPHEA - Database Configuration Tester
 * 
 * Script para verificar la configuración actual de la base de datos
 * 
 * IMPORTANTE: Elimina este archivo después de verificar
 */

// Check authentication
require_once '../admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Test Config DB - SOPHEA';
$auth_result = requireAdminAuth();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Configuración BD - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Diagnóstico de Configuración de Base de Datos</h1>
        
        <div class="space-y-4">
            <?php
            // Try to load config
            $config_loaded = false;
            $db_host = 'NO CONFIGURADO';
            $db_name = 'NO CONFIGURADO';
            $db_user = 'NO CONFIGURADO';
            $db_pass = 'NO CONFIGURADO (vacío)';
            
            if (file_exists('config_db.php')) {
                // Read config file
                $config_content = file_get_contents('config_db.php');
                
                // Extract values using regex
                if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $config_content, $matches)) {
                    $db_host = $matches[1];
                }
                if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $config_content, $matches)) {
                    $db_name = $matches[1];
                }
                if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $config_content, $matches)) {
                    $db_user = $matches[1];
                }
                if (preg_match("/define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
                    $db_pass = empty($matches[1]) ? 'VACÍO (sin contraseña)' : '***' . substr($matches[1], -3);
                }
                
                $config_loaded = true;
            }
            
            // Show current configuration
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">Configuración Actual en config_db.php</h2>';
            
            if (!$config_loaded) {
                echo '<p class="text-red-600">❌ No se encontró el archivo config_db.php</p>';
            } else {
                echo '<div class="space-y-2">';
                echo '<p><strong>DB_HOST:</strong> <code class="bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($db_host) . '</code></p>';
                echo '<p><strong>DB_NAME:</strong> <code class="bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($db_name) . '</code></p>';
                echo '<p><strong>DB_USER:</strong> <code class="bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($db_user) . '</code></p>';
                echo '<p><strong>DB_PASS:</strong> <code class="bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($db_pass) . '</code></p>';
                echo '</div>';
                
                // Check if it's still using development credentials
                if ($db_user === 'root' && ($db_pass === 'VACÍO (sin contraseña)' || empty($db_pass))) {
                    echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">';
                    echo '<h3 class="font-semibold text-red-800 mb-2">⚠️ PROBLEMA DETECTADO</h3>';
                    echo '<p class="text-red-700">El archivo config_db.php todavía tiene las credenciales de DESARROLLO:</p>';
                    echo '<ul class="list-disc list-inside text-red-700 mt-2 space-y-1">';
                    echo '<li>Usuario: <code>root</code> (esto es de desarrollo local)</li>';
                    echo '<li>Contraseña: Vacía (esto es de desarrollo local)</li>';
                    echo '</ul>';
                    echo '<p class="text-red-700 font-semibold mt-3">SOLUCIÓN: Debes editar config_db.php en el servidor y cambiar estas credenciales por las de producción.</p>';
                    echo '</div>';
                }
            }
            echo '</div>';
            
            // Try to connect with current config
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">Prueba de Conexión</h2>';
            
            if ($config_loaded) {
                try {
                    require_once '../config_db.php';
                    
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                    
                    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
                    
                    echo '<p class="text-green-600">✅ Conexión exitosa!</p>';
                    echo '<p class="text-gray-600 text-sm mt-2">La configuración está correcta.</p>';
                    
                    // Test query
                    try {
                        $stmt = $conn->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo '<div class="mt-4">';
                        echo '<p class="font-semibold">Tablas encontradas:</p>';
                        echo '<ul class="list-disc list-inside text-gray-700 mt-2">';
                        foreach ($tables as $table) {
                            echo '<li>' . htmlspecialchars($table) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<p class="text-yellow-600 mt-2">⚠️ Conexión OK pero error al listar tablas: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    
                } catch (PDOException $e) {
                    echo '<p class="text-red-600">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    
                    // Provide specific guidance
                    if (strpos($e->getMessage(), 'Access denied') !== false) {
                        echo '<div class="mt-4 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">';
                        echo '<h3 class="font-semibold text-yellow-800 mb-2">Guía para Corregir:</h3>';
                        echo '<ol class="list-decimal list-inside text-yellow-700 space-y-2">';
                        echo '<li>Accede a tu panel de hosting (cPanel, Plesk, etc.)</li>';
                        echo '<li>Ve a la sección "Bases de Datos MySQL" o "MySQL Databases"</li>';
                        echo '<li>Anota las credenciales de tu base de datos:</li>';
                        echo '<ul class="list-disc list-inside ml-6 mt-1">';
                        echo '<li>Nombre de la base de datos</li>';
                        echo '<li>Usuario de MySQL</li>';
                        echo '<li>Contraseña</li>';
                        echo '<li>Host (generalmente "localhost")</li>';
                        echo '</ul>';
                        echo '<li>Edita el archivo <code class="bg-yellow-100 px-2 py-1 rounded">config_db.php</code> en el servidor</li>';
                        echo '<li>Reemplaza los valores actuales con los de producción</li>';
                        echo '</ol>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<p class="text-gray-600">No se puede probar la conexión sin el archivo de configuración.</p>';
            }
            echo '</div>';
            
            // Instructions
            echo '<div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mt-6">';
            echo '<h2 class="text-xl font-semibold text-blue-800 mb-3">📝 Cómo Editar config_db.php en el Servidor</h2>';
            echo '<div class="text-blue-700 space-y-2">';
            echo '<p><strong>Opción 1: Via cPanel File Manager</strong></p>';
            echo '<ol class="list-decimal list-inside ml-4 space-y-1">';
            echo '<li>Accede a cPanel</li>';
            echo '<li>Ve a "File Manager"</li>';
            echo '<li>Navega a la carpeta de tu sitio</li>';
            echo '<li>Haz clic derecho en <code>config_db.php</code> > "Edit"</li>';
            echo '<li>Actualiza las credenciales</li>';
            echo '<li>Guarda los cambios</li>';
            echo '</ol>';
            
            echo '<p class="mt-4"><strong>Opción 2: Via FTP</strong></p>';
            echo '<ol class="list-decimal list-inside ml-4 space-y-1">';
            echo '<li>Conecta con FileZilla o tu cliente FTP</li>';
            echo '<li>Descarga <code>config_db.php</code></li>';
            echo '<li>Edítalo localmente con un editor de texto</li>';
            echo '<li>Sube el archivo actualizado (sobrescribe el anterior)</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
            ?>
        </div>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 mr-2">
                Ir al Sitio
            </a>
            <a href="admin.php" class="inline-block bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700">
                Ir al Admin
            </a>
        </div>
    </div>
</body>
</html>

