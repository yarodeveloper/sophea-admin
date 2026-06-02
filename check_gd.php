<?php
/**
 * SOPHEA - Verificación de Extensión GD
 * 
 * Script para verificar si la extensión GD está habilitada
 */

// Headers para HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación GD - SOPHEA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .info-item {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Extensión GD</h1>
        
        <?php
        // Verificar si GD está cargado
        $gdLoaded = extension_loaded('gd');
        
        if ($gdLoaded) {
            echo '<div class="status success">';
            echo '✅ <strong>GD está habilitado correctamente</strong>';
            echo '</div>';
            
            // Obtener información de GD
            if (function_exists('gd_info')) {
                $gdInfo = gd_info();
                echo '<div class="info">';
                echo '<h3>Información de GD:</h3>';
                echo '<div class="info-item">';
                echo '<span class="info-label">Versión:</span> ' . htmlspecialchars($gdInfo['GD Version'] ?? 'N/A');
                echo '</div>';
                
                if (isset($gdInfo['FreeType Support'])) {
                    echo '<div class="info-item">';
                    echo '<span class="info-label">Soporte FreeType:</span> ' . ($gdInfo['FreeType Support'] ? '✅ Sí' : '❌ No');
                    echo '</div>';
                }
                
                if (isset($gdInfo['JPEG Support'])) {
                    echo '<div class="info-item">';
                    echo '<span class="info-label">Soporte JPEG:</span> ' . ($gdInfo['JPEG Support'] ? '✅ Sí' : '❌ No');
                    echo '</div>';
                }
                
                if (isset($gdInfo['PNG Support'])) {
                    echo '<div class="info-item">';
                    echo '<span class="info-label">Soporte PNG:</span> ' . ($gdInfo['PNG Support'] ? '✅ Sí' : '❌ No');
                    echo '</div>';
                }
                
                echo '</div>';
            }
        } else {
            echo '<div class="status error">';
            echo '❌ <strong>GD NO está habilitado</strong>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>📋 Instrucciones para habilitar GD:</h3>';
            echo '<ol>';
            echo '<li>Localiza el archivo <code>php.ini</code> en: <code>C:\\xampp\\php\\php.ini</code></li>';
            echo '<li>Ábrelo con un editor de texto (Notepad++, VS Code, etc.)</li>';
            echo '<li>Busca la línea: <code>;extension=gd</code></li>';
            echo '<li>Elimina el <code>;</code> al inicio para que quede: <code>extension=gd</code></li>';
            echo '<li>Guarda el archivo</li>';
            echo '<li>Reinicia Apache desde el XAMPP Control Panel</li>';
            echo '</ol>';
            echo '<p><strong>Nota:</strong> Si no encuentras la línea, agrega <code>extension=gd</code> en la sección de extensiones.</p>';
            echo '</div>';
        }
        
        // Información adicional de PHP
        echo '<div class="info">';
        echo '<h3>ℹ️ Información del Sistema:</h3>';
        echo '<div class="info-item">';
        echo '<span class="info-label">Versión de PHP:</span> ' . phpversion();
        echo '</div>';
        echo '<div class="info-item">';
        echo '<span class="info-label">Archivo php.ini cargado:</span> ' . php_ini_loaded_file();
        echo '</div>';
        if (php_ini_scanned_files()) {
            echo '<div class="info-item">';
            echo '<span class="info-label">Archivos adicionales escaneados:</span> ' . php_ini_scanned_files();
            echo '</div>';
        }
        echo '</div>';
        
        // Verificar DomPDF
        echo '<div class="info">';
        echo '<h3>📦 Verificación de DomPDF:</h3>';
        $dompdfPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($dompdfPath)) {
            echo '<div class="status success">';
            echo '✅ DomPDF está instalado';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '❌ DomPDF NO está instalado';
            echo '</div>';
            echo '<p>Ejecuta: <code>composer require dompdf/dompdf</code></p>';
        }
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
            <p><small>Una vez verificado, elimina este archivo por seguridad.</small></p>
        </div>
    </div>
</body>
</html>

