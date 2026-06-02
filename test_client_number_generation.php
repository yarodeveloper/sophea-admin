<?php
/**
 * Script de prueba detallado para generación de números de cliente
 */

require_once 'config_db.php';
require_once 'classes/Database.php';

$db = Database::getInstance()->getConnection();
$year = date('Y');

echo "<h2>Prueba de Generación de Números de Cliente</h2>";
echo "<p>Año actual: <strong>$year</strong></p>";

// 1. Ver todos los clientes del año actual
echo "<h3>1. Todos los clientes del año $year:</h3>";
$stmt = $db->prepare("SELECT id, client_number FROM clients WHERE client_number LIKE ? ORDER BY id DESC");
$stmt->execute(["C-{$year}-%"]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clients)) {
    echo "<p>No hay clientes para el año $year</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Número de Cliente</th><th>Secuencia Extraída</th></tr>";
    foreach ($clients as $c) {
        $parts = explode('-', $c['client_number']);
        $seq = isset($parts[2]) && is_numeric($parts[2]) ? intval($parts[2]) : 'N/A';
        echo "<tr>";
        echo "<td>{$c['id']}</td>";
        echo "<td>{$c['client_number']}</td>";
        echo "<td>$seq</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Probar la consulta con CAST
echo "<h3>2. Consulta con CAST (método mejorado):</h3>";
try {
    $stmt = $db->prepare("
        SELECT client_number,
               CAST(SUBSTRING(client_number, 7) AS UNSIGNED) as sequence_num
        FROM clients 
        WHERE client_number LIKE ? 
          AND client_number REGEXP '^C-[0-9]{4}-[0-9]{3}$'
        ORDER BY sequence_num DESC 
        LIMIT 1
    ");
    $stmt->execute(["C-{$year}-%"]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>Resultado encontrado:</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        $nextSeq = isset($result['sequence_num']) ? intval($result['sequence_num']) + 1 : 1;
        echo "<p>Próximo número sugerido: <strong>C-{$year}-" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT) . "</strong></p>";
    } else {
        echo "<p>No se encontró resultado con la consulta mejorada</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error en consulta CAST: " . $e->getMessage() . "</p>";
}

// 3. Probar la consulta simple (método anterior)
echo "<h3>3. Consulta simple (método anterior):</h3>";
try {
    $stmt = $db->prepare("
        SELECT client_number 
        FROM clients 
        WHERE client_number LIKE ? 
        ORDER BY client_number DESC 
        LIMIT 1
    ");
    $stmt->execute(["C-{$year}-%"]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>Resultado encontrado: <strong>{$result['client_number']}</strong></p>";
        $parts = explode('-', $result['client_number']);
        if (count($parts) === 3 && is_numeric($parts[2])) {
            $nextSeq = intval($parts[2]) + 1;
            echo "<p>Próximo número sugerido: <strong>C-{$year}-" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT) . "</strong></p>";
        }
    } else {
        echo "<p>No se encontró resultado</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 4. Probar el stored procedure
echo "<h3>4. Probar Stored Procedure:</h3>";
try {
    $stmt = $db->prepare("CALL sp_generate_client_number(@client_num)");
    $stmt->execute();
    $stmt->closeCursor();
    
    $stmt = $db->query("SELECT @client_num as client_number");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['client_number'])) {
        echo "<p>Número generado por stored procedure: <strong>{$result['client_number']}</strong></p>";
        
        // Verificar si existe
        $checkStmt = $db->prepare("SELECT id FROM clients WHERE client_number = ?");
        $checkStmt->execute([$result['client_number']]);
        if ($checkStmt->rowCount() > 0) {
            echo "<p style='color:red'>⚠️ Este número YA EXISTE en la base de datos</p>";
        } else {
            echo "<p style='color:green'>✅ Este número NO existe (disponible)</p>";
        }
    } else {
        echo "<p>Stored procedure no retornó resultado o está vacío</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error en stored procedure: " . $e->getMessage() . "</p>";
}

// 5. Encontrar el máximo número manualmente
echo "<h3>5. Encontrar máximo número manualmente:</h3>";
$maxSeq = 0;
foreach ($clients as $c) {
    $parts = explode('-', $c['client_number']);
    if (count($parts) === 3 && is_numeric($parts[2])) {
        $seq = intval($parts[2]);
        if ($seq > $maxSeq) {
            $maxSeq = $seq;
        }
    }
}
echo "<p>Máximo número de secuencia encontrado: <strong>$maxSeq</strong></p>";
$nextSeq = $maxSeq + 1;
echo "<p>Próximo número debería ser: <strong>C-{$year}-" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT) . "</strong></p>";

// 6. Verificar si el próximo número existe
$nextNumber = "C-{$year}-" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
$checkStmt = $db->prepare("SELECT id FROM clients WHERE client_number = ?");
$checkStmt->execute([$nextNumber]);
if ($checkStmt->rowCount() > 0) {
    echo "<p style='color:red'>⚠️ El número $nextNumber YA EXISTE</p>";
} else {
    echo "<p style='color:green'>✅ El número $nextNumber está disponible</p>";
}

?>

