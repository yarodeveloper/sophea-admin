<?php
require_once 'admin_auth_helper.php';
require_once 'classes/Database.php';

// Verificar que el usuario sea administrador
$auth_data = requireAdminAuth();

$db = Database::getInstance()->getConnection();

echo "<h1>Actualizando Base de Datos...</h1>";

$tables = ['services_catalog', 'services', 'quote_items'];
$success = true;

foreach ($tables as $table) {
    try {
        // Verificar si la tabla existe
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            // Alterar la columna service_type para que sea VARCHAR en lugar de ENUM
            // Esto previene errores futuros al agregar nuevos tipos de servicios
            $db->exec("ALTER TABLE $table MODIFY COLUMN service_type VARCHAR(50) NOT NULL DEFAULT 'otro'");
            echo "<p style='color: green;'>✅ Tabla <b>$table</b> actualizada correctamente.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al actualizar la tabla <b>$table</b>: " . $e->getMessage() . "</p>";
        $success = false;
    }
}

if ($success) {
    echo "<h2>¡Actualización completada exitosamente!</h2>";
    echo "<p>Ya puedes asignar 'Hosting / Dominio' u otros servicios sin problema.</p>";
    echo "<p><b>Por seguridad, por favor elimina este archivo (update_db.php) del servidor después de usarlo.</b></p>";
    echo "<a href='admin_tools.php?tab=services_catalog'>Volver al Catálogo de Servicios</a>";
} else {
    echo "<h2>Hubo algunos errores durante la actualización.</h2>";
}
?>
