<?php
/**
 * Script para corregir pagos que tienen income_ads pero no income_fee
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/ProjectTransaction.php';

$db = Database::getInstance()->getConnection();
$projectTransaction = new ProjectTransaction();

$clientId = 2;

echo "<h2>Corregir Transacciones de Pagos</h2>";
echo "<p>Cliente ID: {$clientId}</p>";

// Buscar pagos de servicios Ads que tienen income_ads pero no income_fee
$sql = "SELECT p.*, s.service_name, s.is_ads_service,
        (SELECT SUM(amount) FROM project_transactions pt 
         WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as fee_amount,
        (SELECT SUM(amount) FROM project_transactions pt 
         WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads') as ads_amount
        FROM payments p
        INNER JOIN services s ON p.service_id = s.id
        WHERE p.client_id = :client_id
        AND s.is_ads_service = 1
        AND p.status = 'paid'
        ORDER BY p.payment_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':client_id' => $clientId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Pagos de Servicios Ads:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>Monto</th><th>Fecha</th><th>Servicio</th><th>income_fee</th><th>income_ads</th><th>Problema</th><th>Acción</th>";
echo "</tr>";

foreach ($payments as $p) {
    $feeAmount = floatval($p['fee_amount'] ?? 0);
    $adsAmount = floatval($p['ads_amount'] ?? 0);
    $totalAmount = floatval($p['amount']);
    $hasProblem = false;
    $problem = '';
    
    if ($feeAmount == 0 && $adsAmount > 0) {
        $hasProblem = true;
        $problem = 'Falta income_fee';
    } elseif ($feeAmount > 0 && $adsAmount == 0) {
        $hasProblem = true;
        $problem = 'Falta income_ads';
    } elseif ($feeAmount == 0 && $adsAmount == 0) {
        $hasProblem = true;
        $problem = 'Faltan ambas transacciones';
    } elseif (($feeAmount + $adsAmount) != $totalAmount) {
        $hasProblem = true;
        $problem = 'Suma no coincide';
    }
    
    $rowColor = $hasProblem ? 'background: #fff3cd;' : '';
    
    echo "<tr style='{$rowColor}'>";
    echo "<td>{$p['id']}</td>";
    echo "<td>\$" . number_format($totalAmount, 2) . "</td>";
    echo "<td>{$p['payment_date']}</td>";
    echo "<td>{$p['service_name']} (ID: {$p['service_id']})</td>";
    echo "<td>" . ($feeAmount > 0 ? "\$" . number_format($feeAmount, 2) : '❌') . "</td>";
    echo "<td>" . ($adsAmount > 0 ? "\$" . number_format($adsAmount, 2) : '❌') . "</td>";
    echo "<td>" . ($hasProblem ? "<span style='color: red;'>{$problem}</span>" : '✅ OK') . "</td>";
    
    if ($hasProblem) {
        if ($feeAmount == 0 && $adsAmount > 0) {
            // Calcular fee_amount (monto total - ads_amount)
            $calculatedFee = $totalAmount - $adsAmount;
            if ($calculatedFee > 0) {
                echo "<td><a href='?fix_fee={$p['id']}&fee_amount={$calculatedFee}' style='color: blue;'>Crear income_fee (\${$calculatedFee})</a> | <a href='?edit_split={$p['id']}' style='color: orange;'>Editar desglose</a></td>";
            } else {
                echo "<td><a href='?edit_split={$p['id']}' style='color: orange; font-weight: bold;'>Editar desglose (actual: todo en ads)</a></td>";
            }
        } elseif ($feeAmount > 0 && $adsAmount == 0) {
            // Calcular ads_amount (monto total - fee_amount)
            $calculatedAds = $totalAmount - $feeAmount;
            if ($calculatedAds > 0) {
                echo "<td><a href='?fix_ads={$p['id']}&ads_amount={$calculatedAds}' style='color: blue;'>Crear income_ads (\${$calculatedAds})</a> | <a href='?edit_split={$p['id']}' style='color: orange;'>Editar desglose</a></td>";
            } else {
                echo "<td><a href='?edit_split={$p['id']}' style='color: orange;'>Editar desglose</a></td>";
            }
        } elseif ($feeAmount == 0 && $adsAmount == 0) {
            echo "<td><a href='?edit_split={$p['id']}' style='color: blue;'>Crear desglose</a></td>";
        } else {
            echo "<td><a href='?edit_split={$p['id']}' style='color: orange;'>Corregir desglose</a></td>";
        }
    } else {
        echo "<td>-</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Procesar correcciones
if (isset($_GET['fix_fee']) && isset($_GET['fee_amount'])) {
    $paymentId = intval($_GET['fix_fee']);
    $feeAmount = floatval($_GET['fee_amount']);
    
    try {
        // Obtener información del pago
        $sql = "SELECT p.*, s.client_id 
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE p.id = :payment_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            // Crear transacción income_fee
            $sql = "INSERT INTO project_transactions 
                    (service_id, client_id, transaction_type, amount, currency, description,
                     payment_id, transaction_date, created_by)
                    VALUES 
                    (:service_id, :client_id, 'income_fee', :amount, :currency, :description,
                     :payment_id, :transaction_date, :created_by)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':service_id' => $payment['service_id'],
                ':client_id' => $payment['client_id'],
                ':amount' => $feeAmount,
                ':currency' => $payment['currency'] ?? 'MXN',
                ':description' => "Honorarios de gestión del pago #{$paymentId}",
                ':payment_id' => $paymentId,
                ':transaction_date' => $payment['payment_date'],
                ':created_by' => 1
            ]);
            
            if ($result) {
                echo "<p style='color: green; font-weight: bold;'>✅ Transacción income_fee creada exitosamente</p>";
                echo "<p>Monto: \$" . number_format($feeAmount, 2) . "</p>";
                echo "<script>setTimeout(function(){ window.location.href='?client_id={$clientId}'; }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Error al crear la transacción</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Pago no encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

if (isset($_GET['fix_ads']) && isset($_GET['ads_amount'])) {
    $paymentId = intval($_GET['fix_ads']);
    $adsAmount = floatval($_GET['ads_amount']);
    
    try {
        // Obtener información del pago
        $sql = "SELECT p.*, s.client_id 
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE p.id = :payment_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            // Crear transacción income_ads
            $sql = "INSERT INTO project_transactions 
                    (service_id, client_id, transaction_type, amount, currency, description,
                     payment_id, transaction_date, created_by)
                    VALUES 
                    (:service_id, :client_id, 'income_ads', :amount, :currency, :description,
                     :payment_id, :transaction_date, :created_by)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':service_id' => $payment['service_id'],
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
                echo "<script>setTimeout(function(){ window.location.href='?client_id={$clientId}'; }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Error al crear la transacción</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Pago no encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Mostrar formulario para editar desglose
if (isset($_GET['edit_split'])) {
    $paymentId = intval($_GET['edit_split']);
    
    // Obtener información del pago
    $sql = "SELECT p.*, s.service_name, s.client_id,
            (SELECT SUM(amount) FROM project_transactions pt 
             WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_fee') as current_fee,
            (SELECT SUM(amount) FROM project_transactions pt 
             WHERE pt.payment_id = p.id AND pt.transaction_type = 'income_ads') as current_ads
            FROM payments p
            INNER JOIN services s ON p.service_id = s.id
            WHERE p.id = :payment_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':payment_id' => $paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo "<p style='color: red;'>❌ Pago no encontrado</p>";
    } else {
        $currentFee = floatval($payment['current_fee'] ?? 0);
        $currentAds = floatval($payment['current_ads'] ?? 0);
        $totalAmount = floatval($payment['amount']);
        
        echo "<hr style='margin: 30px 0;'>";
        echo "<h3>Editar Desglose del Pago ID {$paymentId}</h3>";
        echo "<p><strong>Monto Total:</strong> \$" . number_format($totalAmount, 2) . "</p>";
        echo "<p><strong>Servicio:</strong> {$payment['service_name']}</p>";
        echo "<p><strong>Fecha:</strong> {$payment['payment_date']}</p>";
        echo "<p><strong>Estado actual:</strong> Fee: \$" . number_format($currentFee, 2) . " | Ads: \$" . number_format($currentAds, 2) . "</p>";
        
        echo "<form method='GET' style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;'>";
        echo "<input type='hidden' name='apply_split' value='1'>";
        echo "<input type='hidden' name='payment_id' value='{$paymentId}'>";
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
        echo "<div>";
        echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>Honorarios de Gestión (income_fee):</label>";
        echo "<input type='number' name='fee_amount' step='0.01' min='0' value='" . ($currentFee > 0 ? $currentFee : '750') . "' required style='width: 100%; padding: 8px;'>";
        echo "</div>";
        echo "<div>";
        echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>Fondo para Inversión (income_ads):</label>";
        echo "<input type='number' name='ads_amount' step='0.01' min='0' value='" . ($currentAds > 0 ? $currentAds : '250') . "' required style='width: 100%; padding: 8px;'>";
        echo "</div>";
        echo "</div>";
        echo "<p style='margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;'><strong>Total:</strong> <span id='splitTotal'>\$0.00</span> <span id='splitWarning' style='color: red; display: none;'>⚠️ No coincide con el monto total</span></p>";
        echo "<button type='submit' style='background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;'>Aplicar Desglose</button>";
        echo "<a href='?client_id={$clientId}' style='margin-left: 10px; padding: 10px 20px; background: gray; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Cancelar</a>";
        echo "</form>";
        
        echo "<script>
        function updateSplitTotal() {
            const fee = parseFloat(document.querySelector('input[name=\"fee_amount\"]').value || 0);
            const ads = parseFloat(document.querySelector('input[name=\"ads_amount\"]').value || 0);
            const total = fee + ads;
            const totalAmount = {$totalAmount};
            const diff = Math.abs(total - totalAmount);
            
            document.getElementById('splitTotal').textContent = '$' + total.toFixed(2);
            
            if (diff > 0.01) {
                document.getElementById('splitWarning').style.display = 'inline';
            } else {
                document.getElementById('splitWarning').style.display = 'none';
            }
        }
        
        document.querySelector('input[name=\"fee_amount\"]').addEventListener('input', updateSplitTotal);
        document.querySelector('input[name=\"ads_amount\"]').addEventListener('input', updateSplitTotal);
        updateSplitTotal();
        </script>";
    }
}

// Aplicar nuevo desglose
if (isset($_GET['apply_split']) && isset($_GET['payment_id'])) {
    $paymentId = intval($_GET['payment_id']);
    $newFeeAmount = floatval($_GET['fee_amount']);
    $newAdsAmount = floatval($_GET['ads_amount']);
    
    try {
        // Obtener información del pago
        $sql = "SELECT p.*, s.client_id 
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE p.id = :payment_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo "<p style='color: red;'>❌ Pago no encontrado</p>";
        } else {
            $db->beginTransaction();
            
            // Eliminar transacciones existentes de este pago
            $sql = "DELETE FROM project_transactions 
                    WHERE payment_id = :payment_id 
                    AND transaction_type IN ('income_fee', 'income_ads')";
            $stmt = $db->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            
            // Crear nueva transacción income_fee
            if ($newFeeAmount > 0) {
                $sql = "INSERT INTO project_transactions 
                        (service_id, client_id, transaction_type, amount, currency, description,
                         payment_id, transaction_date, created_by)
                        VALUES 
                        (:service_id, :client_id, 'income_fee', :amount, 'MXN', :description,
                         :payment_id, :transaction_date, :created_by)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':service_id' => $payment['service_id'],
                    ':client_id' => $payment['client_id'],
                    ':amount' => $newFeeAmount,
                    ':description' => "Honorarios de gestión del pago #{$paymentId}",
                    ':payment_id' => $paymentId,
                    ':transaction_date' => $payment['payment_date'],
                    ':created_by' => 1
                ]);
            }
            
            // Crear nueva transacción income_ads
            if ($newAdsAmount > 0) {
                $sql = "INSERT INTO project_transactions 
                        (service_id, client_id, transaction_type, amount, currency, description,
                         payment_id, transaction_date, created_by)
                        VALUES 
                        (:service_id, :client_id, 'income_ads', :amount, 'MXN', :description,
                         :payment_id, :transaction_date, :created_by)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':service_id' => $payment['service_id'],
                    ':client_id' => $payment['client_id'],
                    ':amount' => $newAdsAmount,
                    ':description' => "Inversión publicitaria del pago #{$paymentId}",
                    ':payment_id' => $paymentId,
                    ':transaction_date' => $payment['payment_date'],
                    ':created_by' => 1
                ]);
            }
            
            $db->commit();
            
            echo "<p style='color: green; font-weight: bold;'>✅ Desglose actualizado exitosamente</p>";
            echo "<p>Fee: \$" . number_format($newFeeAmount, 2) . " | Ads: \$" . number_format($newAdsAmount, 2) . "</p>";
            echo "<script>setTimeout(function(){ window.location.href='?client_id={$clientId}'; }, 2000);</script>";
        }
    } catch (Exception $e) {
        $db->rollBack();
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

?>

