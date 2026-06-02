<?php
/**
 * SOPHEA - WhatsApp Marketing Database Setup
 * 
 * Script to initialize the WhatsApp Marketing module database tables
 * Run this once to set up the database structure
 */

require_once '../config_db.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup WhatsApp Marketing DB</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <script src='https://unpkg.com/@phosphor-icons/web'></script>
    </head>
    <body class='bg-gray-50 p-8'>
        <div class='max-w-4xl mx-auto'>
            <div class='bg-white rounded-xl shadow-lg p-8'>
                <h1 class='text-3xl font-bold text-gray-800 mb-6 flex items-center'>
                    <i class='ph-fill ph-database text-green-600 mr-3'></i>
                    Configuración de Base de Datos - WhatsApp Marketing
                </h1>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/whatsapp_marketing_schema.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove USE statement if present (we're already connected)
    $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt) &&
                   !preg_match('/^DELIMITER/i', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    echo "<div class='space-y-4'>";
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            // Skip comments and empty lines
            if (preg_match('/^(--|\/\*|CREATE|INSERT|ALTER|DROP)/i', trim($statement))) {
                $pdo->exec($statement);
                $successCount++;
                
                // Extract table/view name for display
                if (preg_match('/CREATE\s+(?:TABLE|VIEW|OR\s+REPLACE\s+VIEW)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                    $name = $matches[1] ?? 'objeto';
                    echo "<div class='flex items-center p-3 bg-green-50 rounded-lg border border-green-200'>
                        <i class='ph-fill ph-check-circle text-green-600 mr-3'></i>
                        <span class='text-green-800'>✓ Creado: <strong>{$name}</strong></span>
                    </div>";
                } elseif (preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                    $name = $matches[1] ?? 'tabla';
                    echo "<div class='flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200'>
                        <i class='ph-fill ph-check-circle text-blue-600 mr-3'></i>
                        <span class='text-blue-800'>✓ Datos insertados en: <strong>{$name}</strong></span>
                    </div>";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            $errors[] = $errorMsg;
            
            // Check if it's a "table already exists" error (not critical)
            if (strpos($errorMsg, 'already exists') !== false) {
                echo "<div class='flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200'>
                    <i class='ph-fill ph-warning text-yellow-600 mr-3'></i>
                    <span class='text-yellow-800'>⚠ Ya existe (ignorado): " . htmlspecialchars(substr($errorMsg, 0, 100)) . "</span>
                </div>";
            } else {
                echo "<div class='flex items-center p-3 bg-red-50 rounded-lg border border-red-200'>
                    <i class='ph-fill ph-x-circle text-red-600 mr-3'></i>
                    <span class='text-red-800'>✗ Error: " . htmlspecialchars(substr($errorMsg, 0, 150)) . "</span>
                </div>";
            }
        }
    }
    
    echo "</div>";
    
    // Summary
    echo "<div class='mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200'>
        <h2 class='text-xl font-bold text-gray-800 mb-4'>Resumen</h2>
        <div class='grid grid-cols-3 gap-4'>
            <div class='text-center'>
                <div class='text-3xl font-bold text-green-600'>{$successCount}</div>
                <div class='text-sm text-gray-600'>Operaciones Exitosas</div>
            </div>
            <div class='text-center'>
                <div class='text-3xl font-bold " . ($errorCount > 0 ? 'text-yellow-600' : 'text-gray-400') . "'>{$errorCount}</div>
                <div class='text-sm text-gray-600'>Advertencias</div>
            </div>
            <div class='text-center'>
                <div class='text-3xl font-bold " . (count($errors) > 0 ? 'text-red-600' : 'text-green-600') . "'>" . (count($errors) > 0 ? '✗' : '✓') . "</div>
                <div class='text-sm text-gray-600'>Estado</div>
            </div>
        </div>
    </div>";
    
    if ($successCount > 0 && count(array_filter($errors, function($e) { return strpos($e, 'already exists') === false; })) == 0) {
        echo "<div class='mt-6 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg'>
            <i class='ph-fill ph-check-circle mr-2'></i>
            <strong>¡Éxito!</strong> La base de datos del módulo WhatsApp Marketing ha sido configurada correctamente.
        </div>";
        
        echo "<div class='mt-4'>
            <a href='admin_whatsapp_marketing.php' class='inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-medium'>
                <i class='ph-fill ph-arrow-right mr-2'></i>
                Ir al Dashboard de WhatsApp Marketing
            </a>
        </div>";
    }
    
    echo "</div></div></body></html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error - Setup DB</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 p-8'>
        <div class='max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8'>
            <div class='bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg'>
                <h2 class='font-bold mb-2'>Error al configurar la base de datos</h2>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>
        </div>
    </body>
    </html>";
}

