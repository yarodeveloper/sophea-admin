<?php
/**
 * SOPHEA - Verificador de Credenciales de Base de Datos
 * 
 * Este script te ayuda a encontrar las credenciales correctas
 * 
 * IMPORTANTE: Elimina este archivo después de usar
 */

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Credenciales BD - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Verificador de Credenciales de Base de Datos</h1>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
            <h2 class="text-xl font-semibold text-blue-800 mb-3">📋 Instrucciones</h2>
            <ol class="list-decimal list-inside text-blue-700 space-y-2">
                <li>Ve a tu cPanel</li>
                <li>Accede a "Bases de Datos MySQL" o "MySQL Databases"</li>
                <li>Completa el formulario abajo con la información que veas</li>
                <li>Este script generará el código correcto para config_db.php</li>
            </ol>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Formulario de Credenciales</h2>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Usuario de cPanel (el que aparece en la URL o email)
                    </label>
                    <input type="text" name="cpanel_user" required
                           placeholder="Ejemplo: miusuario"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Este es tu usuario principal de cPanel</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre de la Base de Datos (sin el prefijo del usuario)
                    </label>
                    <input type="text" name="db_name" required
                           placeholder="Ejemplo: sophea"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">El nombre que le diste a la BD al crearla</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del Usuario MySQL (sin el prefijo del usuario)
                    </label>
                    <input type="text" name="db_user" required
                           placeholder="Ejemplo: sophea_user"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">El usuario MySQL que creaste o quieres crear</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña del Usuario MySQL
                    </label>
                    <input type="password" name="db_pass" required
                           placeholder="Tu contraseña"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">La contraseña que configuraste para el usuario MySQL</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Host de MySQL
                    </label>
                    <input type="text" name="db_host" value="localhost"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Generalmente es "localhost", pero puede variar</p>
                </div>

                <button type="submit" 
                        class="w-full bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 font-semibold">
                    Generar Código para config_db.php
                </button>
            </form>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cpanel_user = trim($_POST['cpanel_user'] ?? '');
            $db_name = trim($_POST['db_name'] ?? '');
            $db_user = trim($_POST['db_user'] ?? '');
            $db_pass = $_POST['db_pass'] ?? '';
            $db_host = trim($_POST['db_host'] ?? 'localhost');
            
            // Generate full names (with prefix)
            $full_db_name = $cpanel_user . '_' . $db_name;
            $full_db_user = $cpanel_user . '_' . $db_user;
            
            echo '<div class="bg-white rounded-lg shadow p-6 mt-6">';
            echo '<h2 class="text-xl font-semibold mb-4">✅ Credenciales Generadas</h2>';
            
            echo '<div class="bg-gray-50 p-4 rounded-lg mb-4">';
            echo '<h3 class="font-semibold mb-2">Credenciales Completas:</h3>';
            echo '<ul class="space-y-1 text-sm">';
            echo '<li><strong>Host:</strong> <code class="bg-white px-2 py-1 rounded">' . htmlspecialchars($db_host) . '</code></li>';
            echo '<li><strong>Base de Datos:</strong> <code class="bg-white px-2 py-1 rounded">' . htmlspecialchars($full_db_name) . '</code></li>';
            echo '<li><strong>Usuario:</strong> <code class="bg-white px-2 py-1 rounded">' . htmlspecialchars($full_db_user) . '</code></li>';
            echo '<li><strong>Contraseña:</strong> <code class="bg-white px-2 py-1 rounded">' . str_repeat('*', strlen($db_pass)) . '</code></li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 rounded mb-4">';
            echo '<h3 class="font-semibold text-green-800 mb-2">Código para config_db.php:</h3>';
            echo '<pre class="bg-white p-4 rounded overflow-x-auto"><code>';
            echo "// Database Configuration\n";
            echo "define('DB_HOST', '" . htmlspecialchars($db_host) . "');\n";
            echo "define('DB_NAME', '" . htmlspecialchars($full_db_name) . "');\n";
            echo "define('DB_USER', '" . htmlspecialchars($full_db_user) . "');\n";
            echo "define('DB_PASS', '" . htmlspecialchars($db_pass) . "');\n";
            echo "define('DB_CHARSET', 'utf8mb4');";
            echo '</code></pre>';
            echo '</div>';
            
            // Test connection
            echo '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">';
            echo '<h3 class="font-semibold text-blue-800 mb-2">Probar Conexión:</h3>';
            
            try {
                $dsn = "mysql:host={$db_host};dbname={$full_db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                
                $conn = new PDO($dsn, $full_db_user, $db_pass, $options);
                
                echo '<p class="text-green-600">✅ ¡Conexión exitosa!</p>';
                echo '<p class="text-sm text-gray-700 mt-2">Las credenciales son correctas. Puedes usar el código de arriba en config_db.php</p>';
                
                // List tables
                try {
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (count($tables) > 0) {
                        echo '<p class="text-sm text-gray-700 mt-2"><strong>Tablas encontradas:</strong> ' . implode(', ', $tables) . '</p>';
                    } else {
                        echo '<p class="text-yellow-600 text-sm mt-2">⚠️ La base de datos está vacía. Necesitas importar el esquema SQL.</p>';
                    }
                } catch (Exception $e) {
                    // Ignore
                }
                
            } catch (PDOException $e) {
                echo '<p class="text-red-600">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p class="text-sm text-gray-700 mt-2">Verifica que:</p>';
                echo '<ul class="list-disc list-inside text-sm text-gray-700 mt-1 ml-4">';
                echo '<li>El usuario existe y está asignado a la base de datos</li>';
                echo '<li>La contraseña es correcta</li>';
                echo '<li>El nombre de la base de datos es correcto</li>';
                echo '<li>El host es correcto (puede ser "localhost" o una IP)</li>';
                echo '</ul>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

