<?php
/**
 * API Endpoint para agregar inversiones adicionales a servicios de Ads
 * Uso: POST con service_id, amount, platform (opcional), description (opcional), period_date (opcional)
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/ProjectTransaction.php';
require_once 'classes/Service.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
    exit;
}

// Configurar respuesta JSON
header('Content-Type: application/json');

// Obtener datos del POST
$serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$platform = isset($_POST['platform']) ? trim($_POST['platform']) : 'meta';
$description = isset($_POST['description']) ? trim($_POST['description']) : 'Inversión adicional en plataforma';
$periodDate = isset($_POST['period_date']) ? trim($_POST['period_date']) : date('Y-m-d');
$clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

// Validaciones básicas
if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de servicio inválido']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a cero']);
    exit;
}

try {
    // Inicializar clases
    $projectTransaction = new ProjectTransaction();
    $service = new Service();
    
    // Obtener información del servicio
    $serviceInfo = $service->getServiceById($serviceId);
    
    if (!$serviceInfo) {
        echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
        exit;
    }
    
    // Verificar que sea servicio de Ads
    if (!$serviceInfo['is_ads_service']) {
        echo json_encode(['success' => false, 'message' => 'Este servicio no está marcado como servicio de Ads']);
        exit;
    }
    
    // Usar client_id del servicio si no se proporcionó
    if ($clientId <= 0) {
        $clientId = $serviceInfo['client_id'];
    }
    
    // Crear la transacción
    $transactionData = [
        'service_id' => $serviceId,
        'client_id' => $clientId,
        'transaction_type' => 'income_ads',
        'amount' => $amount,
        'currency' => 'MXN',
        'description' => $description,
        'platform' => $platform,
        'transaction_date' => $periodDate,
        'created_by' => $currentUser['id']
    ];
    
    // Crear la transacción
    $result = $projectTransaction->createTransaction($transactionData);
    
    if ($result) {
        // Obtener el balance actualizado para retornarlo
        $balance = $projectTransaction->getCustodyBalance($serviceId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Inversión registrada exitosamente',
            'transaction_id' => $result,
            'total_investment' => $balance['total_investment']
        ]);
    } else {
        $lastError = $projectTransaction->getLastError();
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear la transacción. ' . ($lastError ? $lastError : 'Error desconocido')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en api_add_investment.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar la inversión'
    ]);
}
