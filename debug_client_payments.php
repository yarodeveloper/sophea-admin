<?php
/**
 * Script de diagnóstico para verificar pagos de un cliente
 */

require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Payment.php';

$db = Database::getInstance()->getConnection();
$payment = new Payment();

// Obtener client_id de la URL
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if ($clientId <= 0) {
    die("Por favor, proporciona un client_id válido: ?client_id=1");
}

echo "<h2>Diagnóstico de Pagos para Cliente ID: $clientId</h2>";

// 1. Verificar que el cliente existe
echo "<h3>1. Información del Cliente</h3>";
$stmt = $db->prepare("SELECT id, client_number, company_name FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die("<p style='color:red'>❌ Cliente con ID $clientId no encontrado</p>");
}

echo "<p>✅ Cliente encontrado:</p>";
echo "<ul>";
echo "<li>ID: {$client['id']}</li>";
echo "<li>Número: {$client['client_number']}</li>";
echo "<li>Empresa: {$client['company_name']}</li>";
echo "</ul>";

// 2. Ver todos los pagos directamente de la base de datos
echo "<h3>2. Todos los Pagos en la Base de Datos (Directo SQL)</h3>";
$stmt = $db->prepare("SELECT * FROM payments WHERE client_id = ? ORDER BY id DESC");
$stmt->execute([$clientId]);
$allPaymentsDirect = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total de pagos encontrados directamente: <strong>" . count($allPaymentsDirect) . "</strong></p>";

if (empty($allPaymentsDirect)) {
    echo "<p style='color:orange'>⚠️ No se encontraron pagos directamente en la base de datos</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Número</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Service ID</th><th>Cliente ID</th></tr>";
    foreach ($allPaymentsDirect as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['payment_number']}</td>";
        echo "<td>\${$p['amount']} {$p['currency']}</td>";
        echo "<td>{$p['payment_date']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>" . ($p['service_id'] ?? 'NULL') . "</td>";
        echo "<td>{$p['client_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Probar el método getPaymentsByClient
echo "<h3>3. Pagos usando getPaymentsByClient()</h3>";
try {
    $paymentsByMethod = $payment->getPaymentsByClient($clientId);
    echo "<p>Total de pagos encontrados por método: <strong>" . count($paymentsByMethod) . "</strong></p>";
    
    if (empty($paymentsByMethod)) {
        echo "<p style='color:orange'>⚠️ El método getPaymentsByClient() no retornó pagos</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Número</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Servicio</th></tr>";
        foreach ($paymentsByMethod as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['payment_number']}</td>";
            echo "<td>\${$p['amount']} {$p['currency']}</td>";
            echo "<td>{$p['payment_date']}</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td>" . ($p['service_name'] ?? 'Sin servicio') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error al llamar getPaymentsByClient(): " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. Verificar si hay pagos sin service_id
echo "<h3>4. Pagos sin Service ID (pagos generales del cliente)</h3>";
$stmt = $db->prepare("SELECT * FROM payments WHERE client_id = ? AND (service_id IS NULL OR service_id = 0) ORDER BY id DESC");
$stmt->execute([$clientId]);
$paymentsWithoutService = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total de pagos sin service_id: <strong>" . count($paymentsWithoutService) . "</strong></p>";

if (!empty($paymentsWithoutService)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Número</th><th>Monto</th><th>Fecha</th><th>Estado</th></tr>";
    foreach ($paymentsWithoutService as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['payment_number']}</td>";
        echo "<td>\${$p['amount']} {$p['currency']}</td>";
        echo "<td>{$p['payment_date']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Verificar servicios del cliente
echo "<h3>5. Servicios del Cliente</h3>";
$stmt = $db->prepare("SELECT id, service_name, status FROM services WHERE client_id = ? ORDER BY id DESC");
$stmt->execute([$clientId]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total de servicios: <strong>" . count($services) . "</strong></p>";

if (!empty($services)) {
    echo "<ul>";
    foreach ($services as $s) {
        $servicePayments = $payment->getPaymentsByService($s['id']);
        echo "<li>Servicio ID {$s['id']}: {$s['service_name']} ({$s['status']}) - Pagos: " . count($servicePayments) . "</li>";
    }
    echo "</ul>";
}

// 6. Verificar la consulta SQL exacta que usa getAllPayments
echo "<h3>6. Consulta SQL Exacta</h3>";
echo "<pre>";
echo "SELECT p.*, c.company_name, c.client_number, s.service_name 
FROM payments p
INNER JOIN clients c ON p.client_id = c.id
LEFT JOIN services s ON p.service_id = s.id
WHERE 1=1 AND p.client_id = $clientId
ORDER BY p.payment_date DESC, p.id DESC
";
echo "</pre>";

$stmt = $db->prepare("
    SELECT p.*, c.company_name, c.client_number, s.service_name 
    FROM payments p
    INNER JOIN clients c ON p.client_id = c.id
    LEFT JOIN services s ON p.service_id = s.id
    WHERE 1=1 AND p.client_id = ?
    ORDER BY p.payment_date DESC, p.id DESC
");
$stmt->execute([$clientId]);
$sqlResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Resultados de la consulta SQL directa: <strong>" . count($sqlResults) . "</strong></p>";

if (!empty($sqlResults)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Número</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Servicio</th><th>Cliente</th></tr>";
    foreach ($sqlResults as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['payment_number']}</td>";
        echo "<td>\${$p['amount']} {$p['currency']}</td>";
        echo "<td>{$p['payment_date']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>" . ($p['service_name'] ?? 'Sin servicio') . "</td>";
        echo "<td>{$p['company_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

?>

