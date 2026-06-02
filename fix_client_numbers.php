<?php
/**
 * SOPHEA - Script para Corregir Números de Cliente Mal Formados
 * 
 * Este script busca y corrige números de cliente con formato incorrecto
 * 
 * USO:
 * 1. Ejecuta desde navegador: https://tudominio.com/fix_client_numbers.php
 * 2. Revisa los resultados
 * 3. ⚠️ ELIMINA este archivo después de usar
 */

// Configuración de seguridad
$VERIFICATION_PASSWORD = 'sophea_fix_2025'; // ⚠️ Cambia esto antes de usar

// Verificar contraseña
if (!isset($_GET['pass']) || $_GET['pass'] !== $VERIFICATION_PASSWORD) {
    die('Acceso denegado. Proporciona la contraseña: ?pass=tu_password');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Números de Cliente - SOPHEA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrección de Números de Cliente</h1>
        
        <?php
        require_once 'config_db.php';
        require_once 'classes/Database.php';
        require_once 'classes/Client.php';
        
        $db = Database::getInstance()->getConnection();
        $client = new Client();
        
        // Buscar clientes con números mal formados
        $stmt = $db->query("SELECT id, client_number, company_name, created_at FROM clients ORDER BY id");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invalidClients = [];
        $validPattern = '/^C-\d{4}-\d{3}$/'; // Formato: C-YYYY-XXX
        
        foreach ($clients as $c) {
            if (!preg_match($validPattern, $c['client_number'])) {
                $invalidClients[] = $c;
            }
        }
        
        echo '<h2>Clientes con Números Inválidos: ' . count($invalidClients) . '</h2>';
        
        if (count($invalidClients) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Número Actual (Inválido)</th><th>Nombre</th><th>Nuevo Número</th><th>Acción</th></tr>';
            
            foreach ($invalidClients as $clientData) {
                // Generar nuevo número válido
                $year = date('Y', strtotime($clientData['created_at']));
                if (empty($year) || $year < 2020) {
                    $year = date('Y');
                }
                
                // Obtener el siguiente número disponible para ese año
                $stmt = $db->prepare("
                    SELECT client_number 
                    FROM clients 
                    WHERE client_number LIKE ? 
                    AND id != ?
                    ORDER BY client_number DESC 
                    LIMIT 1
                ");
                $stmt->execute(["C-{$year}-%", $clientData['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $sequence = 1;
                if ($result && !empty($result['client_number'])) {
                    $parts = explode('-', $result['client_number']);
                    if (count($parts) === 3 && is_numeric($parts[2])) {
                        $sequence = intval($parts[2]) + 1;
                    }
                }
                
                // Verificar que no exista ya
                $newNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                $checkStmt = $db->prepare("SELECT id FROM clients WHERE client_number = ? AND id != ?");
                $checkStmt->execute([$newNumber, $clientData['id']]);
                
                if ($checkStmt->rowCount() > 0) {
                    // Si existe, buscar el siguiente disponible
                    $sequence++;
                    $newNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                }
                
                echo '<tr>';
                echo '<td>' . $clientData['id'] . '</td>';
                echo '<td class="error">' . htmlspecialchars($clientData['client_number']) . '</td>';
                echo '<td>' . htmlspecialchars($clientData['company_name']) . '</td>';
                echo '<td class="success">' . $newNumber . '</td>';
                echo '<td>';
                
                // Botón para corregir
                if (isset($_GET['fix']) && $_GET['fix'] == $clientData['id']) {
                    try {
                        $updateStmt = $db->prepare("UPDATE clients SET client_number = ? WHERE id = ?");
                        $updateStmt->execute([$newNumber, $clientData['id']]);
                        echo '<span class="success">✅ Corregido</span>';
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                    }
                } else {
                    echo '<a href="?pass=' . urlencode($VERIFICATION_PASSWORD) . '&fix=' . $clientData['id'] . '" class="btn">Corregir</a>';
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            if (isset($_GET['fixall'])) {
                echo '<h2>Corrigiendo todos los números...</h2>';
                $fixed = 0;
                $errors = 0;
                
                foreach ($invalidClients as $clientData) {
                    $year = date('Y', strtotime($clientData['created_at']));
                    if (empty($year) || $year < 2020) {
                        $year = date('Y');
                    }
                    
                    // Obtener siguiente número disponible
                    $stmt = $db->prepare("
                        SELECT client_number 
                        FROM clients 
                        WHERE client_number LIKE ? 
                        AND id != ?
                        AND client_number REGEXP '^C-[0-9]{4}-[0-9]{3}$'
                        ORDER BY client_number DESC 
                        LIMIT 1
                    ");
                    $stmt->execute(["C-{$year}-%", $clientData['id']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $sequence = 1;
                    if ($result && !empty($result['client_number'])) {
                        $parts = explode('-', $result['client_number']);
                        if (count($parts) === 3 && is_numeric($parts[2])) {
                            $sequence = intval($parts[2]) + 1;
                        }
                    }
                    
                    $newNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                    
                    // Verificar unicidad
                    $checkStmt = $db->prepare("SELECT id FROM clients WHERE client_number = ?");
                    $checkStmt->execute([$newNumber]);
                    $attempts = 0;
                    while ($checkStmt->rowCount() > 0 && $attempts < 100) {
                        $sequence++;
                        $newNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                        $checkStmt->execute([$newNumber]);
                        $attempts++;
                    }
                    
                    try {
                        $updateStmt = $db->prepare("UPDATE clients SET client_number = ? WHERE id = ?");
                        $updateStmt->execute([$newNumber, $clientData['id']]);
                        echo '<p class="success">✅ Cliente ID ' . $clientData['id'] . ': ' . $clientData['client_number'] . ' → ' . $newNumber . '</p>';
                        $fixed++;
                    } catch (PDOException $e) {
                        echo '<p class="error">❌ Error corrigiendo cliente ID ' . $clientData['id'] . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                        $errors++;
                    }
                }
                
                echo '<h3>Resumen:</h3>';
                echo '<p class="success">Corregidos: ' . $fixed . '</p>';
                echo '<p class="error">Errores: ' . $errors . '</p>';
            } else {
                echo '<p><a href="?pass=' . urlencode($VERIFICATION_PASSWORD) . '&fixall=1" class="btn" style="background: #ef4444;">⚠️ Corregir Todos los Números</a></p>';
            }
        } else {
            echo '<p class="success">✅ No se encontraron clientes con números inválidos.</p>';
        }
        
        // Mostrar todos los clientes para referencia
        echo '<h2>Todos los Clientes</h2>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Número</th><th>Nombre</th><th>Estado</th></tr>';
        foreach ($clients as $c) {
            $isValid = preg_match($validPattern, $c['client_number']);
            echo '<tr>';
            echo '<td>' . $c['id'] . '</td>';
            echo '<td class="' . ($isValid ? 'success' : 'error') . '">' . htmlspecialchars($c['client_number']) . '</td>';
            echo '<td>' . htmlspecialchars($c['company_name']) . '</td>';
            echo '<td>' . ($isValid ? '✅ Válido' : '❌ Inválido') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        ?>
        
        <div style="background: #fff3cd; border: 2px solid #f59e0b; padding: 15px; margin-top: 20px; border-radius: 5px;">
            <h3>⚠️ IMPORTANTE</h3>
            <p><strong>Elimina este archivo después de usar:</strong></p>
            <code>rm fix_client_numbers.php</code>
            <p>O elimínalo desde el panel de archivos de tu hosting.</p>
        </div>
    </div>
</body>
</html>

