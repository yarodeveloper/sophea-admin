<?php
/**
 * SOPHEA - Send Invoice/Receipt
 * 
 * Handles sending invoice/receipt via WhatsApp or Email
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Invoice.php';
require_once 'classes/Payment.php';
require_once 'classes/Client.php';
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

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
    $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $invoiceType = isset($_POST['invoice_type']) ? $_POST['invoice_type'] : 'all';
    $serviceId = ($invoiceType === 'service' && !empty($_POST['service_id'])) ? intval($_POST['service_id']) : null;
    $paymentId = !empty($_POST['payment_id']) ? intval($_POST['payment_id']) : null;
    $sendVia = isset($_POST['send_via']) ? $_POST['send_via'] : 'whatsapp'; // whatsapp, email, both
    $includePaid = isset($_POST['include_paid']) && $_POST['include_paid'] === '1';
    $includePending = isset($_POST['include_pending']) && $_POST['include_pending'] === '1';
    $includeFinished = isset($_POST['include_finished']) && $_POST['include_finished'] === '1';
    
    // If paymentId is provided, we might want to override serviceId to match that payment
    if ($paymentId && !$serviceId) {
        $paymentObj = new Payment();
        $payData = $paymentObj->getPaymentById($paymentId);
        if ($payData && !empty($payData['service_id'])) {
            $serviceId = intval($payData['service_id']);
        }
    }
    
    if ($clientId <= 0) {
        throw new Exception('ID de cliente inválido');
    }
    
    // Get client data
    $client = new Client();
    $clientData = $client->getClientById($clientId);
    
    if (!$clientData) {
        throw new Exception('Cliente no encontrado');
    }
    
    // Get WhatsApp number
    $whatsappNumber = $clientData['whatsapp'] ?? $clientData['phone'] ?? '';
    if (empty($whatsappNumber)) {
        throw new Exception('El cliente no tiene número de WhatsApp registrado');
    }
    
    // Generate invoice
    $invoice = new Invoice();
    $paymentDateLimit = null;
    if ($paymentId) {
        $paymentObj = new Payment();
        $payData = $paymentObj->getPaymentById($paymentId);
        if ($payData && !empty($payData['payment_date'])) {
            $paymentDateLimit = $payData['payment_date'];
        }
    }
    
    $invoiceData = $invoice->getInvoiceData($clientId, $serviceId, $paymentDateLimit, $includeFinished);
    
    // Generate invoice URL
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['PHP_SELF'] ?? '/');
    
    // Fix path if it's just '.' or empty
    if ($scriptPath === '.' || $scriptPath === '') {
        $scriptPath = '';
    } else {
        // Ensure path starts with / and doesn't end with /
        $scriptPath = '/' . trim($scriptPath, '/');
    }
    
    $invoiceUrl = $protocol . '://' . $host . $scriptPath . '/generate_invoice.php?client_id=' . $clientId . 
                  ($serviceId ? '&service_id=' . $serviceId : '') .
                  ($paymentId ? '&payment_id=' . $paymentId : '') .
                  '&include_finished=' . ($includeFinished ? '1' : '0');
    
    error_log("Generated invoice URL: " . $invoiceUrl);
    
    // Prepare message
    $clientName = $clientData['company_name'] ?? $clientData['contact_name'] ?? 'Cliente';
    $serviceName = 'tus proyectos';
    if ($serviceId) {
        $clientServices = $invoice->getClientServices($clientId);
        foreach ($clientServices as $svc) {
            if ($svc['id'] == $serviceId) {
                $serviceName = $svc['service_name'] ?? 'proyecto';
                break;
            }
        }
    }
    
    $message = "📄 *Recibo de Pago - SOPHEA*\n\n";
    $message .= "Hola " . $clientName . ",\n\n";
    $message .= "Adjunto encontrarás el resumen de pagos de " . $serviceName . ".\n\n";
    $message .= "*Resumen:*\n";
    $message .= "• Total del servicio: $" . number_format($invoiceData['totals']['service_total'], 2) . "\n";
    
    if ($includePaid && $invoiceData['totals']['paid_total'] > 0) {
        $message .= "• Pagos realizados: $" . number_format($invoiceData['totals']['paid_total'], 2) . "\n";
    }
    
    if ($includePending && $invoiceData['totals']['pending_total'] > 0) {
        $message .= "• Pagos pendientes: $" . number_format($invoiceData['totals']['pending_total'], 2) . "\n";
    }
    
    $message .= "• Saldo pendiente: $" . number_format($invoiceData['totals']['remaining_total'], 2) . "\n\n";
    $message .= "Número de recibo: " . $invoiceData['invoice_number'] . "\n\n";
    $message .= "Ver recibo completo: " . $invoiceUrl . "\n\n";
    $message .= "¿Tienes alguna pregunta? Estamos para ayudarte.\n\n";
    $message .= "SOPHEA Marketing\n";
    $message .= CONTACT_PHONE;
    
    // Prepare data for saving history (before sending)
    $auth = new Auth();
    $currentUser = $auth->getCurrentUser();
    $createdBy = $currentUser ? $currentUser['id'] : null;
    
    // Determine format (check if PDF was requested)
    $format = isset($_POST['format']) && $_POST['format'] === 'pdf' ? 'pdf' : 'html';
    
    // Determine recipient info
    $recipientPhone = ($sendVia === 'whatsapp' || $sendVia === 'both') ? $whatsappNumber : null;
    $recipientEmail = ($sendVia === 'email' || $sendVia === 'both') ? ($clientData['email'] ?? null) : null;
    
    // Update related payments to mark invoice as sent
    $payment = new Payment();
    $paymentFilters = ['client_id' => $clientId];
    if ($serviceId) {
        $paymentFilters['service_id'] = $serviceId;
    }
    
    // Remove limit to get all related payments
    unset($paymentFilters['limit']);
    unset($paymentFilters['offset']);
    
    // Get related payments (without pagination)
    $relatedPayments = $payment->getAllPayments($paymentFilters);
    
    error_log("Found " . count($relatedPayments) . " payments to update for invoice " . $invoiceData['invoice_number'] . " (Client ID: $clientId" . ($serviceId ? ", Service ID: $serviceId" : "") . ")");
    
    if (empty($relatedPayments)) {
        error_log("WARNING: No payments found for client ID: $clientId" . ($serviceId ? ", service ID: $serviceId" : ""));
    }
    
    // Update each payment to mark invoice as sent
    $updatedCount = 0;
    $failedCount = 0;
    foreach ($relatedPayments as $relatedPayment) {
        $result = $payment->updatePaymentInvoiceInfo(
            $relatedPayment['id'],
            true, // invoice_sent
            date('Y-m-d H:i:s'), // invoice_sent_at
            $invoiceUrl, // invoice_url
            $sendVia // invoice_sent_via
        );
        
        if ($result) {
            $updatedCount++;
            error_log("✓ Updated payment ID: " . $relatedPayment['id'] . " (Invoice: " . ($relatedPayment['invoice_number'] ?? 'N/A') . ")");
        } else {
            $failedCount++;
            error_log("✗ Failed to update payment ID: " . $relatedPayment['id'] . " (Invoice: " . ($relatedPayment['invoice_number'] ?? 'N/A') . ")");
        }
    }
    
    error_log("Update summary: $updatedCount successful, $failedCount failed out of " . count($relatedPayments) . " total payments");
    
    // Add update info to response
    $response['payments_updated'] = $updatedCount;
    $response['payments_found'] = count($relatedPayments);
    $response['payments_failed'] = $failedCount;
    
    // Save invoice to history (optional, for backup)
    $invoiceHistoryId = $invoice->saveInvoiceHistory(
        $invoiceData,
        $format,
        $invoiceUrl,
        $sendVia,
        $recipientPhone,
        $recipientEmail,
        $message,
        null, // file_path (can be added if PDF is saved)
        $createdBy
    );
    
    if (!$invoiceHistoryId) {
        error_log("Warning: Failed to save invoice history for invoice " . $invoiceData['invoice_number']);
    }
    
    // Send via WhatsApp
    $whatsappSent = false;
    $whatsappError = null;
    if ($sendVia === 'whatsapp' || $sendVia === 'both') {
        try {
            $whatsappAPI = new WhatsAppAPI();
            
            // Clean WhatsApp number
            $cleanNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);
            if (substr($cleanNumber, 0, 2) !== '52') {
                $cleanNumber = '52' . ltrim($cleanNumber, '0');
            }
            
            $result = $whatsappAPI->sendMessage($cleanNumber, $message);
            
            if ($result['success'] ?? false) {
                $whatsappSent = true;
                $response['success'] = true;
                $response['message'] = 'Recibo enviado exitosamente por WhatsApp';
                $response['whatsapp_sent'] = true;
            } else {
                $whatsappError = 'Error al enviar por WhatsApp: ' . ($result['message'] ?? 'Error desconocido');
                error_log($whatsappError);
                // Don't throw exception, just log the error
                $response['success'] = false;
                $response['message'] = $whatsappError;
                $response['whatsapp_sent'] = false;
            }
        } catch (Exception $e) {
            $whatsappError = 'Error al enviar por WhatsApp: ' . $e->getMessage();
            error_log($whatsappError);
            $response['success'] = false;
            $response['message'] = $whatsappError;
            $response['whatsapp_sent'] = false;
        }
    }
    
    // Send via Email (if needed in the future)
    $emailSent = false;
    if ($sendVia === 'email' || $sendVia === 'both') {
        // Email functionality can be added here
        // For now, we'll just log it
        error_log("Email sending requested for invoice " . $invoiceData['invoice_number']);
        $emailSent = true;
    }
    
    // Add invoice history ID to response
    if ($invoiceHistoryId) {
        $response['invoice_history_id'] = $invoiceHistoryId;
    }
    
    $response['invoice_number'] = $invoiceData['invoice_number'];
    $response['invoice_url'] = $invoiceUrl;
    
    // If WhatsApp failed but we saved the history, still return success for history
    if (!$whatsappSent && $invoiceHistoryId) {
        $response['message'] = 'Factura guardada en historial. ' . ($whatsappError ?? 'Error al enviar por WhatsApp');
    }
    
} catch (Exception $e) {
    error_log("Error sending invoice: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

