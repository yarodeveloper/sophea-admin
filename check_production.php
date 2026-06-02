<?php
/**
 * SOPHEA - Production Environment Checker
 * 
 * Script para verificar que todo esté configurado correctamente en producción
 * 
 * IMPORTANTE: Elimina este archivo después de verificar, o protégelo con .htaccess
 */

// Load configurations
require_once 'config.php';
require_once 'config_db.php';
require_once 'config_whatsapp.php';
require_once 'classes/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Producción - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Verificación de Producción - SOPHEA</h1>
        
        <div class="space-y-4">
            <?php
            $all_ok = true;
            
            // 1. Verificar conexión a base de datos
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">1. Base de Datos</h2>';
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                echo '<p class="text-green-600">✅ Conexión a base de datos: OK</p>';
                
                // Verificar tablas
                $tables = ['leads', 'email_log', 'admin_users', 'whatsapp_messages'];
                foreach ($tables as $table) {
                    try {
                        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
                        if ($stmt->rowCount() > 0) {
                            echo "<p class="text-green-600 ml-4">✅ Tabla '{$table}': Existe</p>";
                        } else {
                            echo "<p class="text-yellow-600 ml-4">⚠️ Tabla '{$table}': No existe</p>";
                            $all_ok = false;
                        }
                    } catch (Exception $e) {
                        echo "<p class="text-red-600 ml-4">❌ Error verificando tabla '{$table}': " . htmlspecialchars($e->getMessage()) . "</p>";
                        $all_ok = false;
                    }
                }
                
                // Verificar vista lead_stats
                try {
                    $stmt = $conn->query("SELECT * FROM lead_stats LIMIT 1");
                    echo '<p class="text-green-600 ml-4">✅ Vista lead_stats: OK</p>';
                } catch (Exception $e) {
                    echo '<p class="text-yellow-600 ml-4">⚠️ Vista lead_stats: No existe</p>';
                    $all_ok = false;
                }
                
            } catch (Exception $e) {
                echo '<p class="text-red-600">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
                $all_ok = false;
            }
            echo '</div>';
            
            // 2. Verificar archivos de configuración
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">2. Archivos de Configuración</h2>';
            
            $config_files = [
                'config.php' => 'Configuración principal',
                'config_db.php' => 'Configuración de BD',
                'config_whatsapp.php' => 'Configuración WhatsApp'
            ];
            
            foreach ($config_files as $file => $desc) {
                if (file_exists($file)) {
                    echo "<p class="text-green-600">✅ {$desc} ({$file}): Existe</p>";
                } else {
                    echo "<p class="text-red-600">❌ {$desc} ({$file}): No existe</p>";
                    $all_ok = false;
                }
            }
            echo '</div>';
            
            // 3. Verificar constantes importantes
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">3. Configuración</h2>';
            
            // Database
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                echo '<p class="text-green-600">✅ Configuración de BD: Definida</p>';
                echo '<p class="text-gray-600 text-sm ml-4">Host: ' . htmlspecialchars(DB_HOST) . '</p>';
                echo '<p class="text-gray-600 text-sm ml-4">Base de datos: ' . htmlspecialchars(DB_NAME) . '</p>';
            } else {
                echo '<p class="text-red-600">❌ Configuración de BD: Incompleta</p>';
                $all_ok = false;
            }
            
            // WhatsApp
            if (defined('WHATSAPP_API_ENABLED')) {
                echo '<p class="text-green-600">✅ Configuración WhatsApp: Definida</p>';
                if (WHATSAPP_ACCESS_TOKEN === 'YOUR_ACCESS_TOKEN_HERE') {
                    echo '<p class="text-yellow-600 ml-4">⚠️ Access Token: No configurado (usa el admin para configurarlo)</p>';
                } else {
                    echo '<p class="text-green-600 ml-4">✅ Access Token: Configurado</p>';
                }
            } else {
                echo '<p class="text-yellow-600">⚠️ Configuración WhatsApp: No cargada</p>';
            }
            
            // Debug Mode
            if (defined('DEBUG_MODE')) {
                if (DEBUG_MODE) {
                    echo '<p class="text-yellow-600">⚠️ DEBUG_MODE: Activado (desactívalo en producción)</p>';
                } else {
                    echo '<p class="text-green-600">✅ DEBUG_MODE: Desactivado</p>';
                }
            }
            
            echo '</div>';
            
            // 4. Verificar permisos de escritura
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">4. Permisos</h2>';
            
            // Verificar sesiones
            $session_path = session_save_path();
            if (empty($session_path)) {
                $session_path = sys_get_temp_dir();
            }
            if (is_writable($session_path)) {
                echo '<p class="text-green-600">✅ Directorio de sesiones: Escribible (' . htmlspecialchars($session_path) . ')</p>';
            } else {
                echo '<p class="text-red-600">❌ Directorio de sesiones: No escribible (' . htmlspecialchars($session_path) . ')</p>';
                $all_ok = false;
            }
            
            echo '</div>';
            
            // 5. Verificar clases
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">5. Clases PHP</h2>';
            
            $classes = ['Database', 'WhatsAppAPI'];
            foreach ($classes as $class) {
                if (class_exists($class)) {
                    echo "<p class="text-green-600">✅ Clase '{$class}': Cargada</p>";
                } else {
                    echo "<p class="text-red-600">❌ Clase '{$class}': No encontrada</p>";
                    $all_ok = false;
                }
            }
            echo '</div>';
            
            // 6. Verificar PHP Version
            echo '<div class="bg-white rounded-lg shadow p-6">';
            echo '<h2 class="text-xl font-semibold mb-4">6. Versión de PHP</h2>';
            $php_version = phpversion();
            echo '<p class="text-gray-700">Versión actual: ' . htmlspecialchars($php_version) . '</p>';
            if (version_compare($php_version, '7.4', '>=')) {
                echo '<p class="text-green-600">✅ Versión de PHP: Compatible (>= 7.4)</p>';
            } else {
                echo '<p class="text-yellow-600">⚠️ Versión de PHP: Se recomienda >= 7.4</p>';
            }
            echo '</div>';
            
            // Resumen
            echo '<div class="bg-' . ($all_ok ? 'green' : 'yellow') . '-50 border-l-4 border-' . ($all_ok ? 'green' : 'yellow') . '-500 p-6 rounded-lg mt-6">';
            if ($all_ok) {
                echo '<h2 class="text-xl font-semibold text-green-800 mb-2">✅ Todo parece estar bien configurado</h2>';
                echo '<p class="text-green-700">El sistema está listo para producción. Recuerda:</p>';
                echo '<ul class="list-disc list-inside text-green-700 mt-2 space-y-1">';
                echo '<li>Desactivar DEBUG_MODE si está activado</li>';
                echo '<li>Configurar el Access Token de WhatsApp</li>';
                echo '<li>Cambiar las contraseñas del admin</li>';
                echo '<li>Eliminar o proteger este archivo (check_production.php)</li>';
                echo '</ul>';
            } else {
                echo '<h2 class="text-xl font-semibold text-yellow-800 mb-2">⚠️ Hay problemas que resolver</h2>';
                echo '<p class="text-yellow-700">Revisa los errores marcados arriba y corrígelos antes de usar el sistema en producción.</p>';
            }
            echo '</div>';
            ?>
        </div>
        
        <div class="mt-6 text-center">
            <a href="admin.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                Ir al Panel de Administración
            </a>
        </div>
    </div>
</body>
</html>

