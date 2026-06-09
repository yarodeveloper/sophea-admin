<?php
require_once 'admin_auth_helper.php';
requireAdminAuth();

require_once 'classes/Service.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    $service = new Service();
    $data = $service->getServiceById(intval($_GET['id']));
    
    if ($data) {
        echo json_encode([
            'id' => $data['id'],
            'service_name' => $data['service_name'],
            'is_ads_service' => !empty($data['is_ads_service']) ? 1 : 0
        ]);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
}
