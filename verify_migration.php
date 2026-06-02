<?php
/**
 * SOPHEA - Script de Verificación Post-Migración
 * 
 * Este script verifica que la migración se haya completado correctamente
 * 
 * USO:
 * 1. Sube este archivo al servidor
 * 2. Accede desde navegador: https://tudominio.com/verify_migration.php
 * 3. Revisa los resultados
 * 4. ⚠️ ELIMINA este archivo después de verificar
 */

// Configuración de seguridad
$VERIFICATION_PASSWORD = 'sophea_2025_migration'; // ⚠️ Cambia esto antes de subir

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
    <title>Verificación de Migración - SOPHEA</title>
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
        .check-item { margin: 5px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Migración - SOPHEA</h1>
        <p class="info">Fecha de verificación: <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        $errors = [];
        $warnings = [];
        $success = [];
        
        // 1. Verificar PHP
        echo '<div class="section">';
        echo '<h2>1. Verificación de PHP</h2>';
        
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo '<p class="success">✅ PHP Version: ' . $phpVersion . ' (Compatible)</p>';
            $success[] = 'PHP version';
        } else {
            echo '<p class="error">❌ PHP Version: ' . $phpVersion . ' (Se requiere 7.4+)</p>';
            $errors[] = 'PHP version';
        }
        
        // Extensiones requeridas
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'gd', 'mbstring', 'openssl'];
        echo '<h3>Extensiones PHP:</h3><ul>';
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                echo '<li class="success">✅ ' . $ext . '</li>';
                $success[] = "Extension: $ext";
            } else {
                echo '<li class="error">❌ ' . $ext . ' (NO INSTALADA)</li>';
                $errors[] = "Extension: $ext";
            }
        }
        echo '</ul></div>';
        
        // 2. Verificar Archivos de Configuración
        echo '<div class="section">';
        echo '<h2>2. Archivos de Configuración</h2>';
        
        $requiredFiles = [
            'config.php',
            'config_db.php',
            'classes/Database.php',
            'classes/SiteSettings.php',
            'classes/Invoice.php',
            'classes/Quote.php',
            'classes/Client.php',
            'classes/Payment.php',
            'classes/Expense.php',
            'header.php',
            'footer.php',
            'admin_web.php',
            'includes/admin_web_contact_tab.php'
        ];
        
        echo '<ul>';
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo '<li class="success">✅ ' . $file . '</li>';
                $success[] = "File: $file";
            } else {
                echo '<li class="error">❌ ' . $file . ' (NO ENCONTRADO)</li>';
                $errors[] = "File: $file";
            }
        }
        echo '</ul></div>';
        
        // 3. Verificar Conexión a Base de Datos
        echo '<div class="section">';
        echo '<h2>3. Conexión a Base de Datos</h2>';
        
        try {
            if (!file_exists('config_db.php')) {
                throw new Exception('config_db.php no encontrado');
            }
            
            require_once 'config_db.php';
            require_once 'classes/Database.php';
            
            $db = Database::getInstance()->getConnection();
            echo '<p class="success">✅ Conexión a base de datos: EXITOSA</p>';
            $success[] = 'Database connection';
            
            // Verificar tablas principales
            echo '<h3>Tablas de Base de Datos:</h3>';
            $requiredTables = [
                'leads',
                'clients',
                'services',
                'payments',
                'expenses',
                'quotes',
                'site_settings',
                'users'
            ];
            
            echo '<table><tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr>';
            foreach ($requiredTables as $table) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $count = $result['count'] ?? 0;
                    echo '<tr>';
                    echo '<td>' . $table . '</td>';
                    echo '<td class="success">✅ Existe</td>';
                    echo '<td>' . $count . '</td>';
                    echo '</tr>';
                    $success[] = "Table: $table";
                } catch (PDOException $e) {
                    echo '<tr>';
                    echo '<td>' . $table . '</td>';
                    echo '<td class="error">❌ No existe</td>';
                    echo '<td>-</td>';
                    echo '</tr>';
                    $errors[] = "Table: $table";
                }
            }
            echo '</table>';
            
            // Verificar configuración de información de contacto
            echo '<h3>Configuración de Información de Contacto:</h3>';
            $siteSettings = new SiteSettings();
            $contactSettings = [
                'company_address',
                'company_phone',
                'company_phone_whatsapp',
                'company_phone_landline',
                'company_email',
                'company_chatbot',
                'social_facebook',
                'social_instagram',
                'social_linkedin',
                'social_youtube'
            ];
            
            echo '<table><tr><th>Setting</th><th>Estado</th><th>Valor (primeros 50 chars)</th></tr>';
            foreach ($contactSettings as $key) {
                $value = $siteSettings->getSetting($key, '');
                $status = !empty($value) ? 'success' : 'warning';
                $icon = !empty($value) ? '✅' : '⚠️';
                echo '<tr>';
                echo '<td>' . $key . '</td>';
                echo '<td class="' . $status . '">' . $icon . ' ' . (!empty($value) ? 'Configurado' : 'Vacío (usará constantes)') . '</td>';
                echo '<td>' . htmlspecialchars(substr($value, 0, 50)) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $errors[] = 'Database connection: ' . $e->getMessage();
        }
        echo '</div>';
        
        // 4. Verificar Funciones de Contacto
        echo '<div class="section">';
        echo '<h2>4. Funciones de Información de Contacto</h2>';
        
        if (file_exists('config.php')) {
            require_once 'config.php';
            
            $functions = ['get_contact_info', 'normalize_phone_number', 'get_whatsapp_number', 'get_whatsapp_link'];
            echo '<ul>';
            foreach ($functions as $func) {
                if (function_exists($func)) {
                    echo '<li class="success">✅ Función: ' . $func . '()</li>';
                    $success[] = "Function: $func";
                } else {
                    echo '<li class="error">❌ Función: ' . $func . '() (NO ENCONTRADA)</li>';
                    $errors[] = "Function: $func";
                }
            }
            echo '</ul>';
            
            // Probar get_contact_info()
            try {
                $contactInfo = get_contact_info();
                echo '<h3>Prueba de get_contact_info():</h3>';
                echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;">';
                echo htmlspecialchars(print_r($contactInfo, true));
                echo '</pre>';
                $success[] = 'get_contact_info() works';
            } catch (Exception $e) {
                echo '<p class="error">❌ Error al probar get_contact_info(): ' . htmlspecialchars($e->getMessage()) . '</p>';
                $errors[] = 'get_contact_info() test: ' . $e->getMessage();
            }
        } else {
            echo '<p class="error">❌ config.php no encontrado</p>';
            $errors[] = 'config.php missing';
        }
        echo '</div>';
        
        // 5. Verificar Permisos
        echo '<div class="section">';
        echo '<h2>5. Permisos de Archivos y Directorios</h2>';
        
        $directories = ['uploads', 'cache', 'logs'];
        echo '<ul>';
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $writable = is_writable($dir);
                if ($writable) {
                    echo '<li class="success">✅ ' . $dir . '/ (escribible)</li>';
                    $success[] = "Directory writable: $dir";
                } else {
                    echo '<li class="error">❌ ' . $dir . '/ (NO escribible - chmod 755)</li>';
                    $errors[] = "Directory not writable: $dir";
                }
            } else {
                echo '<li class="warning">⚠️ ' . $dir . '/ (no existe - se creará automáticamente si es necesario)</li>';
                $warnings[] = "Directory missing: $dir";
            }
        }
        echo '</ul></div>';
        
        // 6. Verificar Composer (si aplica)
        echo '<div class="section">';
        echo '<h2>6. Dependencias (Composer)</h2>';
        
        if (file_exists('vendor/autoload.php')) {
            echo '<p class="success">✅ Composer vendor/ encontrado</p>';
            $success[] = 'Composer vendor';
            
            // Verificar DomPDF
            if (file_exists('vendor/dompdf/dompdf')) {
                echo '<p class="success">✅ DomPDF instalado (para generación de PDFs)</p>';
                $success[] = 'DomPDF installed';
            } else {
                echo '<p class="warning">⚠️ DomPDF no encontrado (necesario para PDFs)</p>';
                $warnings[] = 'DomPDF missing';
            }
        } else {
            echo '<p class="warning">⚠️ Composer vendor/ no encontrado</p>';
            echo '<p class="info">Si necesitas generar PDFs, ejecuta: <code>composer require dompdf/dompdf</code></p>';
            $warnings[] = 'Composer vendor missing';
        }
        echo '</div>';
        
        // Resumen Final
        echo '<div class="section" style="background: ' . (empty($errors) ? '#d1fae5' : '#fee2e2') . ';">';
        echo '<h2>📊 Resumen de Verificación</h2>';
        echo '<p><strong>Éxitos:</strong> <span class="success">' . count($success) . '</span></p>';
        echo '<p><strong>Advertencias:</strong> <span class="warning">' . count($warnings) . '</span></p>';
        echo '<p><strong>Errores:</strong> <span class="error">' . count($errors) . '</span></p>';
        
        if (empty($errors)) {
            echo '<h3 class="success">✅ ¡Migración verificada exitosamente!</h3>';
            echo '<p>La plataforma está lista para usar. Recuerda:</p>';
            echo '<ul>';
            echo '<li>Configurar información de contacto en Admin Web → Información de Contacto</li>';
            echo '<li>Verificar que DEBUG_MODE esté en false en config.php</li>';
            echo '<li>Eliminar este archivo (verify_migration.php) por seguridad</li>';
            echo '</ul>';
        } else {
            echo '<h3 class="error">❌ Se encontraron errores que deben corregirse</h3>';
            echo '<p>Revisa la lista de errores arriba y corrígelos antes de usar la plataforma.</p>';
        }
        echo '</div>';
        
        // Información del Sistema
        echo '<div class="section">';
        echo '<h2>ℹ️ Información del Sistema</h2>';
        echo '<table>';
        echo '<tr><th>Item</th><th>Valor</th></tr>';
        echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td>Server Software</td><td>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . '</td></tr>';
        echo '<tr><td>Document Root</td><td>' . ($_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido') . '</td></tr>';
        echo '<tr><td>Script Path</td><td>' . __DIR__ . '</td></tr>';
        if (isset($db)) {
            try {
                $stmt = $db->query("SELECT VERSION() as version");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '<tr><td>MySQL Version</td><td>' . ($result['version'] ?? 'Desconocido') . '</td></tr>';
            } catch (Exception $e) {
                echo '<tr><td>MySQL Version</td><td>Error al obtener</td></tr>';
            }
        }
        echo '</table>';
        echo '</div>';
        ?>
        
        <div class="section" style="background: #fff3cd; border: 2px solid #f59e0b;">
            <h3>⚠️ IMPORTANTE</h3>
            <p><strong>Elimina este archivo después de verificar:</strong></p>
            <code>rm verify_migration.php</code>
            <p>O elimínalo desde el panel de archivos de tu hosting.</p>
        </div>
    </div>
</body>
</html>

