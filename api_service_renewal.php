<?php
/**
 * SOPHEA - API Service Renewal
 * 
 * Handles service renewal via AJAX
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Service.php';

header('Content-Type: application/json');

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = new Service();
    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if ($serviceId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de servicio no válido']);
        exit;
    }
    
    $result = $service->renewService($serviceId);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'new_service_id' => $result,
            'message' => 'Servicio renovado exitosamente para el siguiente periodo'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al renovar el servicio']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
