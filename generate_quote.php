<?php
/**
 * SOPHEA - Generate Quote HTML
 * 
 * Generates quote HTML for printing
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    die('No autorizado');
}

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Quote.php';
require_once 'classes/Client.php';
require_once 'classes/SiteSettings.php';

// Initialize authentication
$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    die('No autorizado');
}

// Get parameters
$quoteId = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if ($quoteId <= 0) {
    http_response_code(400);
    die('ID de cotización inválido');
}

try {
    $quote = new Quote();
    $client = new Client();
    $siteSettings = new SiteSettings();
    
    // Get quote data
    $quoteData = $quote->getQuoteById($quoteId);
    
    if (!$quoteData) {
        http_response_code(404);
        die('Cotización no encontrada');
    }
    
    // Get client data
    $clientData = $client->getClientById($quoteData['client_id']);
    
    if (!$clientData) {
        http_response_code(404);
        die('Cliente no encontrado');
    }
    
    // Get company data (SOPHEA company info)
    $companyLogo = $siteSettings->getMainLogo();
    $companyAddress = $siteSettings->getSetting('company_address', defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : '');
    $companyPhone = $siteSettings->getSetting('company_phone', defined('CONTACT_PHONE') ? CONTACT_PHONE : '');
    $companyPhoneWhatsapp = $siteSettings->getSetting('company_phone_whatsapp', '');
    $companyPhoneLandline = $siteSettings->getSetting('company_phone_landline', '');
    $companyEmail = $siteSettings->getSetting('company_email', defined('CONTACT_EMAIL_PUBLIC') ? CONTACT_EMAIL_PUBLIC : '');
    $companyChatbot = $siteSettings->getSetting('company_chatbot', '');
    $socialFacebook = $siteSettings->getSetting('social_facebook', defined('SOCIAL_FACEBOOK') ? SOCIAL_FACEBOOK : '');
    $socialInstagram = $siteSettings->getSetting('social_instagram', defined('SOCIAL_INSTAGRAM') ? SOCIAL_INSTAGRAM : '');
    $socialLinkedIn = $siteSettings->getSetting('social_linkedin', defined('SOCIAL_LINKEDIN') ? SOCIAL_LINKEDIN : '');
    $socialYouTube = $siteSettings->getSetting('social_youtube', defined('SOCIAL_YOUTUBE') ? SOCIAL_YOUTUBE : '');
    
    $companyData = [
        'name' => 'Sophea Marketing',
        'contact' => defined('DIRECTOR_NAME') ? DIRECTOR_NAME . ' - ' . CONTACT_PHONE : ($companyPhone ?: CONTACT_PHONE),
        'location' => defined('CONTACT_CITY') ? CONTACT_CITY : 'Tuxtla Gutiérrez',
        'address' => $companyAddress ?: (defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : ''),
        'logo' => $companyLogo
    ];
    
    // Get bank details
    $bankDetailsJson = $siteSettings->getSetting('quote_bank_details', '');
    $bankDetails = [
        'account_holder' => 'Alejandro Montoya Ruiz',
        'bank_name' => 'BBVA',
        'account_number' => '157 304 0456',
        'clabe' => '012 100 01573040456 1',
        'debit_card' => '4152 3143 0071 5342'
    ];
    
    if (!empty($bankDetailsJson)) {
        $decoded = json_decode($bankDetailsJson, true);
        if ($decoded && is_array($decoded)) {
            $bankDetails = array_merge($bankDetails, $decoded);
        }
    }
    
    // Prepare data for template
    $templateData = [
        'quote' => $quoteData,
        'client' => $clientData,
        'company' => $companyData,
        'bank_details' => $bankDetails,
        'logo' => $companyLogo,
        'company_address' => $companyAddress,
        'company_phone' => $companyPhone,
        'company_phone_whatsapp' => $companyPhoneWhatsapp,
        'company_phone_landline' => $companyPhoneLandline,
        'company_email' => $companyEmail,
        'company_chatbot' => $companyChatbot,
        'social_facebook' => $socialFacebook,
        'social_instagram' => $socialInstagram,
        'social_linkedin' => $socialLinkedIn,
        'social_youtube' => $socialYouTube
    ];
    
    // Generate based on format
    if ($format === 'pdf') {
        generatePDF($templateData);
    } elseif ($format === 'html') {
        include 'includes/quote_template.php';
    } else {
        http_response_code(400);
        die('Formato no soportado');
    }
    
} catch (Exception $e) {
    error_log("Error generating quote: " . $e->getMessage());
    http_response_code(500);
    die('Error al generar la cotización: ' . $e->getMessage());
}

/**
 * Generate PDF from quote data
 */
function generatePDF($templateData) {
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
        include 'includes/quote_template.php';
        $html = ob_get_clean();
        
        // Clean any whitespace or BOM that might corrupt the PDF
        $html = trim($html);
        // Remove BOM if present
        if (substr($html, 0, 3) === "\xEF\xBB\xBF") {
            $html = substr($html, 3);
        }
        
        // Convert remote image URLs to absolute paths for better PDF rendering
        if (!empty($templateData['logo'])) {
            $logoUrl = $templateData['logo'];
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
            $html = str_replace($templateData['logo'], $logoUrl, $html);
        }
        
        // Load HTML
        $dompdf->loadHtml($html, 'UTF-8');
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF
        $dompdf->render();
        
        // Generate filename
        $quoteNumber = $templateData['quote']['quote_number'];
        $filename = 'cotizacion-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $quoteNumber) . '.pdf';
        
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
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('Error al generar el PDF: ' . htmlspecialchars($e->getMessage()));
    } catch (Error $e) {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        error_log("Fatal error generating PDF: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('Error fatal al generar el PDF: ' . htmlspecialchars($e->getMessage()));
    }
}
?>

