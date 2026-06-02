<?php
/**
 * API Endpoint para obtener el historial de inversiones de un servicio de Ads
 * Uso: GET con service_id
 */

require_once 'config.php';
// Asegurar que no se muestren errores o advertencias que corrompan el JSON
ini_set('display_errors', 0);
error_reporting(0);
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/ProjectTransaction.php';

// Verificar autenticación
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Configurar respuesta JSON
header('Content-Type: application/json');

// Obtener service_id
$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de servicio inválido']);
    exit;
}

try {
    $projectTransaction = new ProjectTransaction();
    
    // Obtener transacciones de tipo 'income_ads'
    $investments = $projectTransaction->getTransactionsByService($serviceId, [
        'transaction_type' => 'income_ads'
    ]);
    
    // Mapear campos para que coincidan con lo que espera el frontend si es necesario
    // El frontend usa: inv.amount, inv.platform, inv.transaction_date (en el código dice period_date, corregiré eso)
    
    $formattedInvestments = array_map(function($inv) {
        return [
            'amount' => $inv['amount'],
            'platform' => $inv['platform'] ?? 'N/A',
            'period_date' => $inv['transaction_date'],
            'description' => $inv['description']
        ];
    }, $investments);

    echo json_encode([
        'success' => true,
        'investments' => $formattedInvestments
    ]);
    
} catch (Exception $e) {
    error_log("Error en api_get_service_investments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las inversiones'
    ]);
}
