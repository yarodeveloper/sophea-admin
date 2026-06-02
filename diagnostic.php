<?php
/**
 * SOPHEA - Diagnostic Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNÓSTICO SOPHEA ===\n\n";

// 1. Verificar archivos de configuración
echo "1. Verificando archivos de configuración...\n";
if (file_exists('config.php')) {
    echo "   ✓ config.php existe\n";
    require_once 'config.php';
} else {
    echo "   ✗ config.php NO existe\n";
    exit;
}

if (file_exists('config_db.php')) {
    echo "   ✓ config_db.php existe\n";
    require_once 'config_db.php';
} else {
    echo "   ✗ config_db.php NO existe\n";
    exit;
}

// 2. Verificar clase Blog
echo "\n2. Verificando clase Blog...\n";
if (file_exists('classes/Blog.php')) {
    echo "   ✓ classes/Blog.php existe\n";
    require_once 'classes/Blog.php';
} else {
    echo "   ✗ classes/Blog.php NO existe\n";
    exit;
}

// 3. Verificar conexión a base de datos
echo "\n3. Verificando conexión a base de datos...\n";
try {
    $blog = new Blog();
    echo "   ✓ Clase Blog instanciada correctamente\n";
    
    // Verificar categorías
    $categories = $blog->getAllCategories();
    echo "   ✓ Categorías obtenidas: " . count($categories) . "\n";
    foreach ($categories as $cat) {
        echo "      - {$cat['name']} (ID: {$cat['id']})\n";
    }
    
    // Verificar posts existentes
    $existingPosts = $blog->getAllPosts(10);
    echo "\n   ✓ Posts existentes: " . count($existingPosts) . "\n";
    
} catch (Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    exit;
}

// 4. Verificar archivos de contenido
echo "\n4. Verificando archivos de contenido...\n";
$contentFiles = [
    'blog_content/post1_10_pasos.html',
    'blog_content/post2_seo.html',
    'blog_content/post3_cuanto_web.html',
    'blog_content/post4_restaurantes.html'
];

foreach ($contentFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✓ {$file} ({$size} bytes)\n";
    } else {
        echo "   ✗ {$file} NO existe\n";
    }
}

echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";

?>
