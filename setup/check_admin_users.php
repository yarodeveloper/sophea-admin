<?php
/**
 * SOPHEA - Check Admin Users
 * 
 * Script to check existing admin users in the database
 */

require_once '../config.php';
require_once '../config_db.php';
require_once '../classes/Database.php';

$db = Database::getInstance()->getConnection();

try {
    // Get all admin users
    $sql = "SELECT id, username, email, full_name, is_active, created_at, last_login FROM admin_users";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Usuarios Admin en la Base de Datos</h2>";
    echo "<p>Total de usuarios: " . count($users) . "</p>";
    
    if (empty($users)) {
        echo "<p style='color:red;'><strong>No hay usuarios admin en la base de datos.</strong></p>";
        echo "<p>Necesitas crear un usuario usando: <a href='setup_admin_user.php'>setup_admin_user.php</a></p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Usuario</th><th>Email</th><th>Nombre</th><th>Activo</th><th>Creado</th><th>Último Login</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $active = $user['is_active'] ? 'Sí' : 'No';
            $lastLogin = $user['last_login'] ? $user['last_login'] : 'Nunca';
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name'] ?? '') . "</td>";
            echo "<td>" . $active . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "<td>" . $lastLogin . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>Información Importante:</h3>";
        echo "<p><strong>Las contraseñas están hasheadas en la base de datos.</strong></p>";
        echo "<p>No puedes ver la contraseña original, pero puedes:</p>";
        echo "<ul>";
        echo "<li>Crear un nuevo usuario con <a href='setup_admin_user.php'>setup_admin_user.php</a></li>";
        echo "<li>O cambiar la contraseña del usuario existente desde el panel admin (si puedes acceder)</li>";
        echo "</ul>";
        
        // Check if there's a default password hash
        echo "<hr>";
        echo "<h3>Verificar Contraseña por Defecto:</h3>";
        echo "<p>Si el usuario 'admin' fue creado con el schema.sql, la contraseña por defecto es: <strong>admin123</strong></p>";
        echo "<p>Prueba iniciar sesión con:</p>";
        echo "<ul>";
        echo "<li>Usuario: <strong>admin</strong></li>";
        echo "<li>Contraseña: <strong>admin123</strong></li>";
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica que la tabla admin_users existe en la base de datos.</p>";
}
?>
