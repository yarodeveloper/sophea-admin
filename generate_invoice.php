<?php
/**
 * SOPHEA - Generate Invoice/Receipt
 * 
 * Generates invoice/receipt HTML for a client
 */

// Start output buffering early to catch any unwanted output
if (!ob_get_level()) {
    ob_start();
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(401);
    die('No autorizado');
}

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Invoice.php';

// Initialize authentication
$auth = new Auth();

// Allow preview mode (logged in users can preview)
// For actual generation, still require login
$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$isPreview && !$auth->isLoggedIn()) {
    http_response_code(401);
    die('No autorizado');
}

// If preview mode, still check if user is logged in (but allow it)
if ($isPreview && !$auth->isLoggedIn()) {
    http_response_code(401);
    die('No autorizado para vista previa');
}

// Get parameters
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;
$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : null;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if ($clientId <= 0) {
    http_response_code(400);
    die('ID de cliente inválido');
}

try {
    $invoice = new Invoice();
    
    // Si se proporciona payment_id, obtener la fecha del pago para filtrar
    $paymentDateLimit = null;
    if ($paymentId) {
        require_once 'classes/Payment.php';
        $payment = new Payment();
        $paymentData = $payment->getPaymentById($paymentId);
        if ($paymentData && !empty($paymentData['payment_date'])) {
            $paymentDateLimit = $paymentData['payment_date'];
        }
    }
    
    $includeFinished = isset($_GET['include_finished']) ? (bool)intval($_GET['include_finished']) : false;
    
    $invoiceData = $invoice->getInvoiceData($clientId, $serviceId, $paymentDateLimit, $includeFinished);
    
    // Generate based on format
    if ($format === 'pdf') {
        // Generate PDF
        generatePDF($invoiceData);
    } else {
        // Generate HTML
        include 'includes/invoice_template.php';
    }
} catch (Exception $e) {
    error_log("Error generating invoice: " . $e->getMessage());
    http_response_code(500);
    die('Error al generar el recibo: ' . $e->getMessage());
}

/**
 * Generate PDF from invoice data
 */
function generatePDF($invoiceData) {
    // Clean any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start output buffering
    ob_start();
    
    // Check if GD extension is loaded
    if (!extension_loaded('gd')) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('Error: La extensión GD de PHP no está instalada o habilitada. ' .
            'Para habilitarla en XAMPP, edita php.ini y descomenta la línea: extension=gd');
    }
    
    // Load Composer autoloader
    $composerAutoload = __DIR__ . '/vendor/autoload.php';
    
    if (!file_exists($composerAutoload)) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('DomPDF no está instalado. Por favor, ejecuta: composer require dompdf/dompdf');
    }
    
    require_once $composerAutoload;
    
    try {
        // Use full namespace for DomPDF classes
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('isPhpEnabled', true);
        $options->set('chroot', __DIR__);
        
        // Create DomPDF instance
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Capture HTML output
        ob_start();
        include 'includes/invoice_template.php';
        $html = ob_get_clean();
        
        // Clean any whitespace or BOM that might corrupt the PDF
        $html = trim($html);
        // Remove BOM if present
        if (substr($html, 0, 3) === "\xEF\xBB\xBF") {
            $html = substr($html, 3);
        }
        
        // Convert remote image URLs to absolute paths for better PDF rendering
        // This helps DomPDF load images correctly
        if (!empty($invoiceData['company']['logo'])) {
            $logoUrl = $invoiceData['company']['logo'];
            // If it's a relative path, convert to absolute
            if (strpos($logoUrl, 'http') !== 0 && strpos($logoUrl, '//') !== 0) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                          '://' . $_SERVER['HTTP_HOST'] . 
                          dirname($_SERVER['PHP_SELF']);
                if (substr($logoUrl, 0, 1) !== '/') {
                    $logoUrl = $baseUrl . '/' . $logoUrl;
                } else {
                    $logoUrl = $baseUrl . $logoUrl;
                }
            }
            // Replace logo URL in HTML
            $html = str_replace($invoiceData['company']['logo'], $logoUrl, $html);
        }
        
        // Load HTML
        $dompdf->loadHtml($html, 'UTF-8');
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF
        $dompdf->render();
        
        // Generate filename (sanitize to avoid issues)
        $filename = 'recibo-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $invoiceData['invoice_number']) . '.pdf';
        
        // Clean output buffer before sending PDF
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Output PDF
        $dompdf->stream($filename, [
            'Attachment' => 1 // 1 = download, 0 = preview
        ]);
        
        // Exit to prevent any additional output
        exit;
        
    } catch (Exception $e) {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        error_log("Error generating PDF: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('Error al generar el PDF: ' . htmlspecialchars($e->getMessage()));
    } catch (Error $e) {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        error_log("Fatal error generating PDF: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('Error fatal al generar el PDF: ' . htmlspecialchars($e->getMessage()));
    }
}

