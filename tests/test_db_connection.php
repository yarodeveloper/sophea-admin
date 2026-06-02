<?php
/**
 * SOPHEA - Database Connection Test
 * 
 * Use this file to test if the database connection is working correctly
 * Access: http://localhost/sopheaadmin/test_db_connection.php
 */

// Check authentication
require_once '../admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Test Conexión DB - SOPHEA';
$auth_result = requireAdminAuth();

// Load configurations
require_once '../config.php';
require_once '../config_db.php';
require_once '../classes/Database.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Test de Conexión a Base de Datos</h1>
        
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold mb-4">Configuración Actual:</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong>Host:</strong> <?php echo DB_HOST; ?>
                </div>
                <div>
                    <strong>Database:</strong> <?php echo DB_NAME; ?>
                </div>
                <div>
                    <strong>User:</strong> <?php echo DB_USER; ?>
                </div>
                <div>
                    <strong>Charset:</strong> <?php echo DB_CHARSET; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-semibold mb-4">Resultado de la Conexión:</h2>
            
            <?php
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">';
                echo '<p class="text-green-800 font-semibold">✅ Conexión exitosa a la base de datos</p>';
                echo '</div>';
                
                // Test query
                $stmt = $conn->query("SELECT DATABASE() as db_name");
                $db_info = $stmt->fetch();
                echo '<p class="mb-2"><strong>Base de datos conectada:</strong> ' . htmlspecialchars($db_info['db_name']) . '</p>';
                
                // Check if tables exist
                $tables = ['leads', 'email_log', 'admin_users'];
                echo '<h3 class="font-semibold mt-4 mb-2">Tablas en la base de datos:</h3>';
                echo '<ul class="list-disc list-inside space-y-1">';
                
                foreach ($tables as $table) {
                    try {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                        $result = $stmt->fetch();
                        $count = $result['count'] ?? 0;
                        echo "<li class='text-green-600'>✅ Tabla <strong>$table</strong> existe ($count registros)</li>";
                    } catch (PDOException $e) {
                        echo "<li class='text-red-600'>❌ Tabla <strong>$table</strong> NO existe o hay un error</li>";
                    }
                }
                echo '</ul>';
                
                // Check leads table structure
                echo '<h3 class="font-semibold mt-4 mb-2">Estructura de la tabla leads:</h3>';
                try {
                    $stmt = $conn->query("DESCRIBE leads");
                    $columns = $stmt->fetchAll();
                    echo '<div class="overflow-x-auto"><table class="min-w-full border-collapse border border-gray-300">';
                    echo '<thead class="bg-gray-100"><tr><th class="border border-gray-300 px-4 py-2">Campo</th><th class="border border-gray-300 px-4 py-2">Tipo</th><th class="border border-gray-300 px-4 py-2">Null</th><th class="border border-gray-300 px-4 py-2">Key</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($columns as $col) {
                        echo '<tr>';
                        echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($col['Field']) . '</td>';
                        echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($col['Type']) . '</td>';
                        echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($col['Null']) . '</td>';
                        echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($col['Key']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                } catch (PDOException $e) {
                    echo '<p class="text-red-600">Error al obtener estructura: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                
                // Get recent leads
                echo '<h3 class="font-semibold mt-4 mb-2">Últimos 5 leads registrados:</h3>';
                try {
                    $stmt = $conn->query("SELECT id, nombre, especialidad, whatsapp, created_at, status FROM leads ORDER BY created_at DESC LIMIT 5");
                    $leads = $stmt->fetchAll();
                    
                    if (empty($leads)) {
                        echo '<p class="text-gray-600">No hay leads registrados aún.</p>';
                    } else {
                        echo '<div class="overflow-x-auto"><table class="min-w-full border-collapse border border-gray-300">';
                        echo '<thead class="bg-gray-100"><tr>';
                        echo '<th class="border border-gray-300 px-4 py-2">ID</th>';
                        echo '<th class="border border-gray-300 px-4 py-2">Nombre</th>';
                        echo '<th class="border border-gray-300 px-4 py-2">Especialidad</th>';
                        echo '<th class="border border-gray-300 px-4 py-2">WhatsApp</th>';
                        echo '<th class="border border-gray-300 px-4 py-2">Fecha</th>';
                        echo '<th class="border border-gray-300 px-4 py-2">Estado</th>';
                        echo '</tr></thead><tbody>';
                        
                        foreach ($leads as $lead) {
                            echo '<tr>';
                            echo '<td class="border border-gray-300 px-4 py-2">#' . htmlspecialchars($lead['id']) . '</td>';
                            echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($lead['nombre']) . '</td>';
                            echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($lead['especialidad']) . '</td>';
                            echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($lead['whatsapp']) . '</td>';
                            echo '<td class="border border-gray-300 px-4 py-2">' . date('d/m/Y H:i', strtotime($lead['created_at'])) . '</td>';
                            echo '<td class="border border-gray-300 px-4 py-2">' . htmlspecialchars($lead['status']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table></div>';
                    }
                } catch (PDOException $e) {
                    echo '<p class="text-red-600">Error al obtener leads: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                
            } catch (Exception $e) {
                echo '<div class="bg-red-50 border-l-4 border-red-500 p-4">';
                echo '<p class="text-red-800 font-semibold">❌ Error de conexión</p>';
                echo '<p class="text-red-600 mt-2">' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
                
                echo '<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mt-4">';
                echo '<p class="font-semibold">Sugerencias para solucionar:</p>';
                echo '<ul class="list-disc list-inside mt-2 space-y-1">';
                echo '<li>Verifica que MySQL esté corriendo en XAMPP</li>';
                echo '<li>Verifica las credenciales en <code>config_db.php</code></li>';
                echo '<li>Asegúrate de que la base de datos <code>' . DB_NAME . '</code> existe</li>';
                echo '<li>Ejecuta el script <code>database/schema.sql</code> en phpMyAdmin</li>';
                echo '</ul>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
            <p class="font-semibold">📝 Notas:</p>
            <ul class="list-disc list-inside mt-2 space-y-1 text-sm">
                <li>Si la conexión es exitosa, el formulario debería guardar los datos correctamente</li>
                <li>Si hay errores, revisa los logs de PHP en XAMPP</li>
                <li>Elimina este archivo después de verificar la conexión (por seguridad)</li>
            </ul>
        </div>

        <div class="mt-6">
            <a href="index.php" class="text-purple-600 hover:text-purple-700 font-medium">← Volver al sitio</a>
            <span class="mx-2">|</span>
            <a href="admin.php" class="text-purple-600 hover:text-purple-700 font-medium">Ir al Admin Panel →</a>
        </div>
    </div>
</body>
</html>

