<?php
/**
 * SOPHEA - API Endpoint for New Leads Count
 * 
 * Returns the count of leads with status='nuevo' for real-time notifications
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $count = $db->getNewLeadsCount();
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} catch (Exception $e) {
    error_log("Error getting new leads count: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el conteo de leads',
        'count' => 0
    ]);
}
