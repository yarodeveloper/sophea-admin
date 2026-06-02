<?php
/**
 * SOPHEA - Send WhatsApp Message Endpoint
 * 
 * Handles sending WhatsApp messages from admin panel
 */

session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Load configurations
require_once 'config.php';
require_once 'config_db.php';
require_once 'config_whatsapp.php';
require_once 'classes/Database.php';
require_once 'classes/WhatsAppAPI.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no válido');
    }
    
    // Get input data
    $leadId = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
    $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = trim($_POST['message'] ?? '');
    $useTemplate = isset($_POST['use_template']) && $_POST['use_template'] === '1';
    $templateName = trim($_POST['template_name'] ?? '');
    $templateParams = isset($_POST['template_params']) ? json_decode($_POST['template_params'], true) : [];
    
    // Validation
    $whatsappNumber = '';
    $recipientName = '';
    $recipientType = '';
    
    if ($leadId > 0) {
        // Get lead from database
        $db = Database::getInstance();
        $lead = $db->getLeadById($leadId);
        
        if (!$lead) {
            throw new Exception('Lead no encontrado');
        }
        
        $whatsappNumber = $lead['whatsapp'];
        $recipientName = $lead['nombre'] ?? 'Lead';
        $recipientType = 'lead';
    } elseif ($clientId > 0 && !empty($phone)) {
        // Get client from database
        require_once 'classes/Client.php';
        $client = new Client();
        $clientData = $client->getClientById($clientId);
        
        if (!$clientData) {
            throw new Exception('Cliente no encontrado');
        }
        
        $whatsappNumber = $phone; // Use the phone number passed from the form
        $recipientName = $clientData['company_name'] ?? 'Cliente';
        $recipientType = 'client';
    } else {
        throw new Exception('ID de lead/cliente inválido o número de teléfono faltante');
    }
    
    if ($useTemplate) {
        if (empty($templateName)) {
            throw new Exception('Debes seleccionar una plantilla');
        }
    } else {
        if (empty($message)) {
            throw new Exception('El mensaje no puede estar vacío');
        }
        
        if (strlen($message) > 4096) {
            throw new Exception('El mensaje es demasiado largo (máximo 4096 caracteres)');
        }
    }
    
    // Send WhatsApp message
    $whatsappAPI = new WhatsAppAPI();
    
    // Log attempt
    error_log("SOPHEA WhatsApp Send Attempt: {$recipientType} #" . ($leadId > 0 ? $leadId : $clientId) . ", Phone: {$whatsappNumber}, UseTemplate: " . ($useTemplate ? 'Yes' : 'No'));
    
    $result = null;
    
    // Try to send message
    if ($useTemplate) {
        // Send template message
        $result = $whatsappAPI->sendTemplateMessage($whatsappNumber, $templateName, $templateParams);
    } else {
        // Try to send free text message first
        try {
            $result = $whatsappAPI->sendMessage($whatsappNumber, $message);
        } catch (Exception $e) {
            // If error 131026 (outside 24h window), suggest using template
            if (strpos($e->getMessage(), '131026') !== false || strpos($e->getMessage(), 'ventana de 24 horas') !== false) {
                // Try with default template as fallback
                error_log("SOPHEA WhatsApp: Free message failed, trying with template 'recordatorio'");
                try {
                    // Use recordatorio template with message as parameter
                    $result = $whatsappAPI->sendTemplateMessage($whatsappNumber, 'recordatorio', [$message]);
                    $response['warning'] = 'El mensaje libre no pudo enviarse (fuera de ventana de 24h), se envió usando plantilla "recordatorio"';
                } catch (Exception $e2) {
                    throw new Exception('No se pudo enviar el mensaje libre ni con plantilla. Error: ' . $e->getMessage() . ' | Template Error: ' . $e2->getMessage());
                }
            } else {
                // Re-throw other errors
                throw $e;
            }
        }
    }
    
    if ($result['success']) {
        // Update lead status to "contactado" if it's "nuevo"
        if ($lead['status'] === 'nuevo') {
            $db->updateLeadStatus($leadId, 'contactado', 'Contactado por WhatsApp - ' . date('Y-m-d H:i:s'));
        }
        
        // Log message in database (optional - you might want to create a whatsapp_messages table)
        error_log("SOPHEA WhatsApp Sent Successfully: Lead #{$leadId} - Message ID: " . ($result['message_id'] ?? 'N/A'));
        
        $response['success'] = true;
        $response['message'] = 'Mensaje enviado exitosamente. ID: ' . ($result['message_id'] ?? 'N/A');
        $response['message_id'] = $result['message_id'] ?? null;
        $response['details'] = 'El mensaje fue aceptado por la API de WhatsApp. Verifica que llegue al destinatario.';
    } else {
        throw new Exception('Error al enviar mensaje: La API no devolvió éxito');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("SOPHEA WhatsApp Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

