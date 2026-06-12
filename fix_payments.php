<?php
require_once 'admin_auth_helper.php';
requireAdminAuth(); // Ensure user is authenticated

require_once 'config_db.php';
require_once 'classes/Database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("UPDATE payments SET paid_amount = amount, pending_amount = 0 WHERE paid_amount > amount");

echo "<h2>Base de datos corregida!</h2>";
echo "<p>Registros afectados: " . $stmt->rowCount() . "</p>";
echo "<a href='admin_dashboard.php'>Volver al Dashboard</a>";
