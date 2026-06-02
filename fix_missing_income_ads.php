<?php
/**
 * Script para crear transacciones income_ads faltantes desde pagos existentes
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/ProjectTransaction.php';

$db = Database::getInstance()->getConnection();
$projectTransaction = new ProjectTransaction();

// ID del servicio a verificar
$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 5;
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 2;

echo "<h2>Corregir Transacciones income_ads Faltantes</h2>";
echo "<p>Servicio ID: {$serviceId} | Cliente ID: {$clientId}</p>";

// Verificar información del servicio
$sql = "SELECT id, service_name, is_ads_service, client_id 
        FROM services 
        WHERE id = :service_id";
$stmt = $db->prepare($sql);
$stmt->execute([':service_id' => $serviceId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo "<p style='color: red;'>❌ Servicio no encontrado</p>";
    exit;
}

if (!$service['is_ads_service']) {
    echo "<p style='color: red;'>❌ Este servicio no está marcado como Ads</p>";
    exit;
}

echo "<p style='color: green;'>✅ Servicio: {$service['service_name']}</p>";

// Buscar pagos sin transacciones income_ads
// Primero buscar por service_id
$sql = "SELECT p.*,
        (SELECT COUNT(*) FROM project_transactions pt 
         WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads') as has_income_ads,
        (SELECT COUNT(*) FROM project_transactions pt 
         WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as has_income_fee,
        (SELECT SUM(amount) FROM project_transactions pt 
         WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as fee_amount
        FROM payments p
        WHERE p.service_id = :service_id
        ORDER BY p.payment_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':service_id' => $serviceId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay pagos, buscar todos los pagos del cliente
if (empty($payments)) {
    echo "<p style='color: orange;'>⚠️ No se encontraron pagos registrados para este servicio específico</p>";
    echo "<h4>Buscando todos los pagos del cliente:</h4>";
    
    $sql = "SELECT p.*, s.service_name, s.id as service_id_from_payment,
            (SELECT COUNT(*) FROM project_transactions pt 
             WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads') as has_income_ads,
            (SELECT COUNT(*) FROM project_transactions pt 
             WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as has_income_fee,
            (SELECT SUM(amount) FROM project_transactions pt 
             WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as fee_amount
            FROM payments p
            LEFT JOIN services s ON p.service_id = s.id
            WHERE p.client_id = :client_id
            ORDER BY p.payment_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':client_id' => $clientId]);
    $allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allPayments)) {
        echo "<p style='color: blue;'>ℹ️ Se encontraron " . count($allPayments) . " pagos del cliente:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Servicio ID</th><th>Nombre Servicio</th><th>Tiene income_fee</th><th>Tiene income_ads</th><th>Acción</th>";
        echo "</tr>";
        
        foreach ($allPayments as $p) {
            $isCorrectService = ($p['service_id_from_payment'] == $serviceId);
            $rowColor = $isCorrectService ? 'background: #e8f5e9;' : '';
            
            echo "<tr style='{$rowColor}'>";
            echo "<td>{$p['id']}</td>";
            echo "<td>\$" . number_format($p['amount'], 2) . "</td>";
            echo "<td>{$p['payment_date']}</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td>{$p['service_id_from_payment']}</td>";
            echo "<td>" . ($p['service_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($p['has_income_fee'] > 0 ? '✅' : '❌') . "</td>";
            echo "<td>" . ($p['has_income_ads'] > 0 ? '✅' : '❌') . "</td>";
            
            if ($isCorrectService && $p['has_income_ads'] == 0) {
                $feeAmount = floatval($p['fee_amount'] ?? 0);
                $adsAmount = floatval($p['amount']) - $feeAmount;
                if ($adsAmount > 0) {
                    echo "<td><a href='?create={$p['id']}&service_id={$serviceId}' style='color: blue; font-weight: bold;'>Crear income_ads</a></td>";
                } else {
                    echo "<td>-</td>";
                }
            } elseif (!$isCorrectService) {
                echo "<td><a href='?reassign={$p['id']}&service_id={$serviceId}' style='color: orange;'>Reasignar a este servicio</a></td>";
            } else {
                echo "<td>-</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No se encontraron pagos para este cliente</p>";
        echo "<p>Esto significa que:</p>";
        echo "<ul>";
        echo "<li>Los pagos pueden no estar registrados en el sistema</li>";
        echo "<li>Los pagos pueden estar asociados a otro cliente</li>";
        echo "<li>Los costos registrados (expense_ads_consumed) pueden haber sido creados manualmente sin un pago previo</li>";
        echo "</ul>";
    }
    
    // No salir, continuar para mostrar opciones
    // Mostrar formulario para crear transacción manual
    echo "<hr style='margin: 30px 0;'>";
    echo "<h3>Opción alternativa: Crear transacción income_ads manualmente</h3>";
    echo "<p style='color: orange;'>Si no hay pagos registrados pero sabes cuánto se invirtió, puedes crear la transacción manualmente:</p>";
    echo "<form method='GET' style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>";
    echo "<input type='hidden' name='service_id' value='{$serviceId}'>";
    echo "<input type='hidden' name='create_manual' value='1'>";
    echo "<p><label>Monto de inversión: <input type='number' name='amount' step='0.01' min='0' required style='padding: 5px;'></label></p>";
    echo "<p><label>Fecha: <input type='date' name='transaction_date' value='" . date('Y-m-d') . "' required style='padding: 5px;'></label></p>";
    echo "<p><label>Descripción: <input type='text' name='description' value='Inversión publicitaria inicial' style='padding: 5px; width: 300px;'></label></p>";
    echo "<p><button type='submit' style='background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Crear Transacción Manual</button></p>";
    echo "</form>";
} else {

echo "<h3>Pagos encontrados:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>Monto</th><th>Fecha</th><th>Estado</th><th>Tiene income_fee</th><th>Tiene income_ads</th><th>Fee Amount</th><th>Ads Amount</th><th>Acción</th>";
echo "</tr>";

$totalToCreate = 0;

foreach ($payments as $p) {
    $feeAmount = floatval($p['fee_amount'] ?? 0);
    $adsAmount = floatval($p['amount']) - $feeAmount;
    $needsFix = ($p['has_income_ads'] == 0 && $adsAmount > 0);
    
    if ($needsFix) {
        $totalToCreate += $adsAmount;
    }
    
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>\$" . number_format($p['amount'], 2) . "</td>";
    echo "<td>{$p['payment_date']}</td>";
    echo "<td>{$p['status']}</td>";
    echo "<td>" . ($p['has_income_fee'] > 0 ? '✅' : '❌') . "</td>";
    echo "<td>" . ($p['has_income_ads'] > 0 ? '✅' : '❌') . "</td>";
    echo "<td>\$" . number_format($feeAmount, 2) . "</td>";
    echo "<td style='color: " . ($adsAmount > 0 ? 'blue' : 'gray') . ";'>\$" . number_format($adsAmount, 2) . "</td>";
    
    if ($needsFix) {
        echo "<td><a href='?create={$p['id']}&service_id={$serviceId}' style='color: blue; font-weight: bold;'>Crear income_ads</a></td>";
    } else {
        echo "<td>-</td>";
    }
    echo "</tr>";
}

echo "</table>";

if ($totalToCreate > 0) {
    echo "<p style='color: blue; font-weight: bold; margin-top: 20px;'>Total a crear: \$" . number_format($totalToCreate, 2) . "</p>";
    echo "<p><a href='?create_all=1&service_id={$serviceId}' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Crear todas las transacciones faltantes</a></p>";
}

    // Mostrar formulario para crear transacción manual
    echo "<hr style='margin: 30px 0;'>";
    echo "<h3>Opción alternativa: Crear transacción income_ads manualmente</h3>";
    echo "<p style='color: orange;'>Si no hay pagos registrados pero sabes cuánto se invirtió, puedes crear la transacción manualmente:</p>";
    echo "<form method='GET' style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>";
    echo "<input type='hidden' name='service_id' value='{$serviceId}'>";
    echo "<input type='hidden' name='create_manual' value='1'>";
    echo "<p><label>Monto de inversión: <input type='number' name='amount' step='0.01' min='0' required style='padding: 5px;'></label></p>";
    echo "<p><label>Fecha: <input type='date' name='transaction_date' value='" . date('Y-m-d') . "' required style='padding: 5px;'></label></p>";
    echo "<p><label>Descripción: <input type='text' name='description' value='Inversión publicitaria inicial' style='padding: 5px; width: 300px;'></label></p>";
    echo "<p><button type='submit' style='background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Crear Transacción Manual</button></p>";
    echo "</form>";
}

// Procesar creación individual
if (isset($_GET['create']) && isset($_GET['service_id'])) {
    $paymentId = intval($_GET['create']);
    $serviceId = intval($_GET['service_id']);
    
    try {
        // Obtener información del pago
        $sql = "SELECT p.*, s.client_id 
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE p.id = :payment_id AND p.service_id = :service_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId, ':service_id' => $serviceId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo "<p style='color: red;'>❌ Pago no encontrado</p>";
        } else {
            // Obtener monto de fee si existe
            $sql = "SELECT SUM(amount) as total_fee 
                    FROM project_transactions 
                    WHERE payment_id = :payment_id AND transaction_type = 'income_fee'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            $feeResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $feeAmount = floatval($feeResult['total_fee'] ?? 0);
            $adsAmount = floatval($payment['amount']) - $feeAmount;
            
            if ($adsAmount <= 0) {
                echo "<p style='color: orange;'>⚠️ El monto de inversión sería \$0 o negativo.</p>";
                echo "<p>Monto total: \$" . number_format($payment['amount'], 2) . "</p>";
                echo "<p>Monto fee: \$" . number_format($feeAmount, 2) . "</p>";
                echo "<p>Monto ads calculado: \$" . number_format($adsAmount, 2) . "</p>";
            } else {
                // Verificar si ya existe
                $sql = "SELECT COUNT(*) as count FROM project_transactions 
                        WHERE payment_id = :payment_id AND transaction_type = 'income_ads'";
                $stmt = $db->prepare($sql);
                $stmt->execute([':payment_id' => $paymentId]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists['count'] > 0) {
                    echo "<p style='color: orange;'>⚠️ Ya existe una transacción income_ads para este pago</p>";
                } else {
                    // Crear transacción income_ads
                    $sql = "INSERT INTO project_transactions 
                            (service_id, client_id, transaction_type, amount, currency, description,
                             payment_id, transaction_date, created_by)
                            VALUES 
                            (:service_id, :client_id, 'income_ads', :amount, :currency, :description,
                             :payment_id, :transaction_date, :created_by)";
                    
                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute([
                        ':service_id' => $serviceId,
                        ':client_id' => $payment['client_id'],
                        ':amount' => $adsAmount,
                        ':currency' => $payment['currency'] ?? 'MXN',
                        ':description' => "Inversión publicitaria del pago #{$paymentId}",
                        ':payment_id' => $paymentId,
                        ':transaction_date' => $payment['payment_date'],
                        ':created_by' => 1
                    ]);
                    
                    if ($result) {
                        echo "<p style='color: green; font-weight: bold;'>✅ Transacción income_ads creada exitosamente</p>";
                        echo "<p>Monto: \$" . number_format($adsAmount, 2) . "</p>";
                        echo "<script>setTimeout(function(){ window.location.href='?service_id={$serviceId}'; }, 2000);</script>";
                    } else {
                        echo "<p style='color: red;'>❌ Error al crear la transacción</p>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Procesar creación masiva
if (isset($_GET['create_all']) && isset($_GET['service_id'])) {
    $serviceId = intval($_GET['service_id']);
    
    try {
        // Obtener todos los pagos sin income_ads
        $sql = "SELECT p.*, s.client_id,
                (SELECT SUM(amount) FROM project_transactions pt 
                 WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as fee_amount
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE p.service_id = :service_id
                AND p.status = 'paid'
                AND NOT EXISTS (
                    SELECT 1 FROM project_transactions pt 
                    WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads'
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([':service_id' => $serviceId]);
        $paymentsToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $created = 0;
        $totalAmount = 0;
        
        foreach ($paymentsToFix as $payment) {
            $feeAmount = floatval($payment['fee_amount'] ?? 0);
            $adsAmount = floatval($payment['amount']) - $feeAmount;
            
            if ($adsAmount > 0) {
                $sql = "INSERT INTO project_transactions 
                        (service_id, client_id, transaction_type, amount, currency, description,
                         payment_id, transaction_date, created_by)
                        VALUES 
                        (:service_id, :client_id, 'income_ads', :amount, :currency, :description,
                         :payment_id, :transaction_date, :created_by)";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':service_id' => $serviceId,
                    ':client_id' => $payment['client_id'],
                    ':amount' => $adsAmount,
                    ':currency' => $payment['currency'] ?? 'MXN',
                    ':description' => "Inversión publicitaria del pago #{$payment['id']}",
                    ':payment_id' => $payment['id'],
                    ':transaction_date' => $payment['payment_date'],
                    ':created_by' => 1
                ]);
                
                if ($result) {
                    $created++;
                    $totalAmount += $adsAmount;
                }
            }
        }
        
        if ($created > 0) {
            echo "<p style='color: green; font-weight: bold;'>✅ Se crearon {$created} transacciones income_ads</p>";
            echo "<p>Total: \$" . number_format($totalAmount, 2) . "</p>";
            echo "<script>setTimeout(function(){ window.location.href='?service_id={$serviceId}'; }, 2000);</script>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se crearon transacciones (posiblemente todos los montos son \$0 o negativos)</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Procesar reasignación de pago a otro servicio
if (isset($_GET['reassign']) && isset($_GET['service_id'])) {
    $paymentId = intval($_GET['reassign']);
    $serviceId = intval($_GET['service_id']);
    
    try {
        // Verificar que el servicio existe y es Ads
        $sql = "SELECT id, client_id, is_ads_service FROM services WHERE id = :service_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':service_id' => $serviceId]);
        $targetService = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$targetService || !$targetService['is_ads_service']) {
            echo "<p style='color: red;'>❌ El servicio destino no existe o no es un servicio Ads</p>";
        } else {
            // Actualizar el service_id del pago
            $sql = "UPDATE payments SET service_id = :service_id WHERE id = :payment_id";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':service_id' => $serviceId,
                ':payment_id' => $paymentId
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Pago reasignado exitosamente</p>";
                echo "<script>setTimeout(function(){ window.location.href='?service_id={$serviceId}'; }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Error al reasignar el pago</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Procesar creación manual de transacción income_ads
if (isset($_GET['create_manual']) && isset($_GET['service_id']) && isset($_GET['amount'])) {
    $serviceId = intval($_GET['service_id']);
    $amount = floatval($_GET['amount']);
    $transactionDate = $_GET['transaction_date'] ?? date('Y-m-d');
    $description = $_GET['description'] ?? 'Inversión publicitaria manual';
    
    if ($amount <= 0) {
        echo "<p style='color: red;'>❌ El monto debe ser mayor a 0</p>";
    } else {
        try {
            // Obtener información del servicio
            $sql = "SELECT id, client_id, is_ads_service FROM services WHERE id = :service_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':service_id' => $serviceId]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$service || !$service['is_ads_service']) {
                echo "<p style='color: red;'>❌ El servicio no existe o no es un servicio Ads</p>";
            } else {
                // Crear transacción income_ads manual
                $sql = "INSERT INTO project_transactions 
                        (service_id, client_id, transaction_type, amount, currency, description,
                         transaction_date, created_by)
                        VALUES 
                        (:service_id, :client_id, 'income_ads', :amount, 'MXN', :description,
                         :transaction_date, :created_by)";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':service_id' => $serviceId,
                    ':client_id' => $service['client_id'],
                    ':amount' => $amount,
                    ':description' => $description,
                    ':transaction_date' => $transactionDate,
                    ':created_by' => 1
                ]);
                
                if ($result) {
                    echo "<p style='color: green; font-weight: bold;'>✅ Transacción income_ads creada exitosamente</p>";
                    echo "<p>Monto: \$" . number_format($amount, 2) . "</p>";
                    echo "<p>Fecha: {$transactionDate}</p>";
                    echo "<script>setTimeout(function(){ window.location.href='?service_id={$serviceId}'; }, 2000);</script>";
                } else {
                    echo "<p style='color: red;'>❌ Error al crear la transacción</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}

?>

