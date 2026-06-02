<?php
/**
 * SOPHEA - Testimonials Diagnostic Script
 * 
 * Script to diagnose issues with testimonials system
 */

// Check authentication
require_once '../admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Test Testimonios - SOPHEA';
$auth_result = requireAdminAuth();

require_once '../config.php';
require_once '../config_db.php';
require_once '../classes/Testimonials.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Testimonios - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Diagnóstico del Sistema de Testimonios</h1>
        
        <div class="space-y-6">
            <!-- Database Connection -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">1. Conexión a Base de Datos</h2>
                <?php
                try {
                    require_once '../classes/Database.php';
                    $db = Database::getInstance()->getConnection();
                    echo '<p class="text-green-600">✓ Conexión exitosa a la base de datos</p>';
                } catch (Exception $e) {
                    echo '<p class="text-red-600">✗ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>

            <!-- Table Existence -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">2. Verificación de Tablas</h2>
                <?php
                try {
                    $db = Database::getInstance()->getConnection();
                    
                    // Check testimonials table
                    $stmt = $db->query("SHOW TABLES LIKE 'testimonials'");
                    $testimonialsTable = $stmt->fetch();
                    
                    if ($testimonialsTable) {
                        echo '<p class="text-green-600">✓ Tabla "testimonials" existe</p>';
                        
                        // Check structure
                        $stmt = $db->query("DESCRIBE testimonials");
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo '<p class="text-sm text-gray-600 ml-4">Columnas: ' . implode(', ', $columns) . '</p>';
                    } else {
                        echo '<p class="text-red-600">✗ Tabla "testimonials" NO existe</p>';
                        echo '<p class="text-yellow-600 ml-4">⚠️ Necesitas ejecutar: database/testimonials_schema.sql</p>';
                    }
                    
                    // Check testimonial_images table
                    $stmt = $db->query("SHOW TABLES LIKE 'testimonial_images'");
                    $imagesTable = $stmt->fetch();
                    
                    if ($imagesTable) {
                        echo '<p class="text-green-600">✓ Tabla "testimonial_images" existe</p>';
                    } else {
                        echo '<p class="text-red-600">✗ Tabla "testimonial_images" NO existe</p>';
                        echo '<p class="text-yellow-600 ml-4">⚠️ Necesitas ejecutar: database/testimonials_schema.sql</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>

            <!-- Class Loading -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">3. Carga de Clases</h2>
                <?php
                if (class_exists('Testimonials')) {
                    echo '<p class="text-green-600">✓ Clase Testimonials cargada correctamente</p>';
                    
                    try {
                        $testimonials = new Testimonials();
                        echo '<p class="text-green-600">✓ Instancia de Testimonials creada correctamente</p>';
                    } catch (Exception $e) {
                        echo '<p class="text-red-600">✗ Error al crear instancia: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<p class="text-red-600">✗ Clase Testimonials NO encontrada</p>';
                }
                ?>
            </div>

            <!-- Test Create -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">4. Prueba de Creación</h2>
                <?php
                try {
                    $testimonials = new Testimonials();
                    
                    $testData = [
                        'client_name' => 'Test Cliente',
                        'testimonial_text' => 'Este es un testimonio de prueba',
                        'status' => 'draft'
                    ];
                    
                    echo '<p class="text-gray-700 mb-2">Intentando crear testimonio de prueba...</p>';
                    $result = $testimonials->createTestimonial($testData);
                    
                    if ($result) {
                        echo '<p class="text-green-600">✓ Testimonio de prueba creado exitosamente (ID: ' . $result . ')</p>';
                        
                        // Delete test testimonial
                        $testimonials->deleteTestimonial($result);
                        echo '<p class="text-gray-600 text-sm">Testimonio de prueba eliminado</p>';
                    } else {
                        echo '<p class="text-red-600">✗ No se pudo crear el testimonio de prueba</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p class="text-gray-600 text-sm mt-2">Detalles: ' . htmlspecialchars($e->getTraceAsString()) . '</p>';
                }
                ?>
            </div>

            <!-- Upload Directory -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">5. Directorio de Uploads</h2>
                <?php
                $uploadDir = __DIR__ . '/uploads/testimonials/';
                if (file_exists($uploadDir)) {
                    echo '<p class="text-green-600">✓ Directorio existe: ' . $uploadDir . '</p>';
                    if (is_writable($uploadDir)) {
                        echo '<p class="text-green-600">✓ Directorio es escribible</p>';
                    } else {
                        echo '<p class="text-red-600">✗ Directorio NO es escribible</p>';
                        echo '<p class="text-yellow-600 ml-4">⚠️ Cambia los permisos del directorio</p>';
                    }
                } else {
                    echo '<p class="text-red-600">✗ Directorio NO existe: ' . $uploadDir . '</p>';
                    echo '<p class="text-yellow-600 ml-4">⚠️ Crea el directorio manualmente</p>';
                }
                ?>
            </div>

            <!-- Current Testimonials Count -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">6. Testimonios Existentes</h2>
                <?php
                try {
                    $testimonials = new Testimonials();
                    $count = $testimonials->getPublishedCount();
                    $countFeatured = $testimonials->getPublishedCount(true);
                    $all = $testimonials->getAllTestimonials(100);
                    
                    echo '<p class="text-gray-700">Total de testimonios publicados: <strong>' . $count . '</strong></p>';
                    echo '<p class="text-gray-700">Total de testimonios destacados: <strong>' . $countFeatured . '</strong></p>';
                    echo '<p class="text-gray-700">Total de testimonios (todos): <strong>' . count($all) . '</strong></p>';
                    
                    if (!empty($all)) {
                        echo '<h3 class="text-lg font-semibold mt-4 mb-2">Detalle de Testimonios:</h3>';
                        echo '<table class="w-full border-collapse border border-gray-300 text-sm">';
                        echo '<tr class="bg-gray-100"><th class="border p-2">ID</th><th class="border p-2">Nombre</th><th class="border p-2">Status</th><th class="border p-2">Featured</th><th class="border p-2">Published At</th></tr>';
                        foreach ($all as $t) {
                            $statusColor = $t['status'] === 'published' ? 'text-green-600' : 'text-yellow-600';
                            $featuredIcon = $t['featured'] ? '✓' : '✗';
                            echo '<tr>';
                            echo '<td class="border p-2">' . $t['id'] . '</td>';
                            echo '<td class="border p-2">' . htmlspecialchars($t['client_name']) . '</td>';
                            echo '<td class="border p-2 ' . $statusColor . '">' . $t['status'] . '</td>';
                            echo '<td class="border p-2">' . $featuredIcon . '</td>';
                            echo '<td class="border p-2">' . ($t['published_at'] ?? 'NULL') . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } catch (Exception $e) {
                    echo '<p class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <!-- Test Homepage Query -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">7. Prueba de Consulta para Homepage</h2>
                <?php
                try {
                    $testimonials = new Testimonials();
                    
                    // Test featured query
                    $featured = $testimonials->getPublishedTestimonials(2, 0, true);
                    echo '<p class="text-gray-700 mb-2"><strong>Testimonios destacados (featured=1, status=published):</strong> ' . count($featured) . '</p>';
                    
                    if (!empty($featured)) {
                        foreach ($featured as $t) {
                            echo '<p class="text-sm text-gray-600 ml-4">- ' . htmlspecialchars($t['client_name']) . '</p>';
                        }
                    } else {
                        echo '<p class="text-yellow-600">⚠️ No hay testimonios destacados</p>';
                    }
                    
                    // Test published query
                    $published = $testimonials->getPublishedTestimonials(2, 0, false);
                    echo '<p class="text-gray-700 mb-2 mt-4"><strong>Testimonios publicados (sin filtro featured):</strong> ' . count($published) . '</p>';
                    
                    if (!empty($published)) {
                        foreach ($published as $t) {
                            echo '<p class="text-sm text-gray-600 ml-4">- ' . htmlspecialchars($t['client_name']) . ' (Featured: ' . ($t['featured'] ? 'Sí' : 'No') . ')</p>';
                        }
                        echo '<p class="text-green-600 mt-2">✓ Estos testimonios deberían aparecer en el home</p>';
                    } else {
                        echo '<p class="text-red-600">✗ No hay testimonios publicados. Verifica que el testimonio tenga status="published"</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>

        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-bold text-blue-800 mb-2">Instrucciones</h3>
            <ol class="list-decimal list-inside text-blue-700 space-y-1 text-sm">
                <li>Si la tabla no existe, ejecuta: <code>database/testimonials_schema.sql</code></li>
                <li>Si el directorio no existe, créalo: <code>uploads/testimonials/</code></li>
                <li>Si hay errores de permisos, ajusta los permisos del directorio</li>
                <li>Revisa los logs de PHP para más detalles de errores</li>
            </ol>
        </div>

        <div class="mt-6">
            <a href="admin_testimonials.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                Volver al Panel de Testimonios
            </a>
        </div>
    </div>
</body>
</html>
