<?php
/**
 * SOPHEA - API Create Client
 * 
 * Handles client creation via AJAX for use in other forms (like quotes)
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Client.php';

header('Content-Type: application/json');

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$currentUser = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = new Client();
    
    // Validate required fields
    if (empty($_POST['company_name']) || empty($_POST['contact_name']) || empty($_POST['email'])) {
        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
        exit;
    }
    
    $clientData = [
        'company_name' => $_POST['company_name'] ?? '',
        'contact_name' => $_POST['contact_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'phone_country_code' => $_POST['phone_country_code'] ?? '+52',
        'whatsapp' => $_POST['whatsapp'] ?? '',
        'whatsapp_country_code' => $_POST['whatsapp_country_code'] ?? '+52',
        'address' => $_POST['address'] ?? null,
        'city' => $_POST['city'] ?? null,
        'state' => $_POST['state'] ?? null,
        'country' => $_POST['country'] ?? 'México',
        'tax_id' => $_POST['tax_id'] ?? null,
        'website' => $_POST['website'] ?? null,
        'industry' => $_POST['industry'] ?? null,
        'client_type' => $_POST['client_type'] ?? 'regular',
        'legal_risk' => $_POST['legal_risk'] ?? 'low',
        'status' => $_POST['status'] ?? 'prospect',
        'notes' => $_POST['notes'] ?? 'Creado desde el panel de cotizaciones',
        'created_by' => $currentUser['id']
    ];
    
    $clientId = $client->createClient($clientData);
    
    if ($clientId) {
        $newClient = $client->getClientById($clientId);
        echo json_encode([
            'success' => true, 
            'client_id' => $clientId, 
            'company_name' => $newClient['company_name'],
            'message' => 'Cliente creado exitosamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al crear el cliente en la base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
