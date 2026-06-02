<?php
/**
 * SOPHEA - Form Processing Script
 * 
 * Handles contact form submissions with validation, database storage, and email notifications
 */

// Disable error display for this script (we'll return JSON errors instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session FIRST (before any headers or output)
// This is critical for CSRF token validation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header (after session start)
header('Content-Type: application/json; charset=utf-8');

// Start output buffering to catch any unexpected output
ob_start();

// Response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {

    // Load configurations
    require_once 'config.php';
    require_once 'config_db.php';
    require_once 'classes/Database.php';
    
    // Clear any output buffer
    ob_clean();

    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no válido');
    }
    
    // Rate Limiting - Prevent spam (Improved)
    $rate_limit_seconds = 60; // 1 minute between submissions
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Use IP-based rate limiting stored in session
    $last_submit_key = 'last_form_submit_' . md5($ip_address);
    $last_submit = isset($_SESSION[$last_submit_key]) ? (int)$_SESSION[$last_submit_key] : 0;
    $time_since_last = time() - $last_submit;
    
    if ($time_since_last < $rate_limit_seconds) {
        $remaining = $rate_limit_seconds - $time_since_last;
        $response['success'] = false;
        $response['message'] = "Por favor espera {$remaining} segundo" . ($remaining > 1 ? 's' : '') . " antes de enviar otro formulario";
        $response['rate_limit'] = true;
        $response['remaining_seconds'] = $remaining;
        ob_end_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // CSRF Protection
    if (ENABLE_CSRF_PROTECTION) {
        // Debug: Log session info
        error_log("SOPHEA CSRF Debug: Session ID: " . session_id());
        error_log("SOPHEA CSRF Debug: Session token exists: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO'));
        error_log("SOPHEA CSRF Debug: POST token exists: " . (isset($_POST['csrf_token']) ? 'YES' : 'NO'));
        
        // Check if CSRF token exists in session
        if (!isset($_SESSION['csrf_token'])) {
            error_log("SOPHEA CSRF Error: No token in session. Session ID: " . session_id());
            error_log("SOPHEA CSRF Error: All session vars: " . print_r($_SESSION, true));
            throw new Exception('Token de seguridad no encontrado. Por favor, recarga la página e intenta de nuevo.');
        }
        
        // Check if CSRF token was sent in POST
        if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
            error_log("SOPHEA CSRF Error: No token in POST data");
            error_log("SOPHEA CSRF Error: POST data: " . print_r($_POST, true));
            throw new Exception('Token de seguridad no recibido. Por favor, recarga la página e intenta de nuevo.');
        }
        
        // Validate CSRF token
        $postToken = trim($_POST['csrf_token']);
        $sessionToken = $_SESSION['csrf_token'];
        
        // Debug log (only first few chars for security)
        error_log("SOPHEA CSRF Debug: Comparing tokens - Session: " . substr($sessionToken, 0, 10) . "... POST: " . substr($postToken, 0, 10) . "...");
        error_log("SOPHEA CSRF Debug: Token lengths - Session: " . strlen($sessionToken) . " POST: " . strlen($postToken));
        
        if (!hash_equals($sessionToken, $postToken)) {
            error_log("SOPHEA CSRF Error: Token mismatch!");
            error_log("SOPHEA CSRF Error: Session token (first 20): " . substr($sessionToken, 0, 20));
            error_log("SOPHEA CSRF Error: POST token (first 20): " . substr($postToken, 0, 20));
            throw new Exception('Token de seguridad inválido. Por favor, recarga la página e intenta de nuevo.');
        }
        
        error_log("SOPHEA CSRF Success: Token validated successfully");
        
        // Token is valid, regenerate for next request (only after successful validation)
        // We'll do this at the end, after all processing is complete
    }
    
    // Honeypot validation - If this field is filled, it's a bot
    $honeypot = trim($_POST['website_url'] ?? '');
    if (!empty($honeypot)) {
        error_log("SOPHEA Bot Detection: Honeypot field filled. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $response['success'] = false;
        $response['message'] = 'Error al procesar el formulario. Por favor, intenta de nuevo.';
        ob_end_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // Time-based validation - Check if form was submitted too quickly (less than 3 seconds)
    $formTimestamp = isset($_POST['form_timestamp']) ? intval($_POST['form_timestamp']) : 0;
    $currentTime = time();
    $timeElapsed = $currentTime - $formTimestamp;
    
    // Minimum time: 3 seconds (humans need time to fill the form)
    // Maximum time: 1 hour (prevent stale submissions)
    if ($formTimestamp > 0) {
        if ($timeElapsed < 3) {
            error_log("SOPHEA Bot Detection: Form submitted too quickly ({$timeElapsed} seconds). IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $response['success'] = false;
            $response['message'] = 'El formulario se completó demasiado rápido. Por favor, tómate tu tiempo y vuelve a intentar.';
            ob_end_clean();
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        if ($timeElapsed > 3600) {
            error_log("SOPHEA Bot Detection: Form submitted after too long ({$timeElapsed} seconds). IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $response['success'] = false;
            $response['message'] = 'El formulario ha expirado. Por favor, recarga la página e intenta de nuevo.';
            ob_end_clean();
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    
    // Get and sanitize input data
    $nombre = trim($_POST['nombre'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (strlen($nombre) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    } elseif (strlen($nombre) > 255) {
        $errors['nombre'] = 'El nombre es demasiado largo';
    }
    
    if (empty($especialidad)) {
        $errors['especialidad'] = 'La especialidad/giro es requerido';
    } elseif (strlen($especialidad) > 255) {
        $errors['especialidad'] = 'La especialidad es demasiado larga';
    }
    
    if (empty($whatsapp)) {
        $errors['whatsapp'] = 'El WhatsApp es requerido';
    } else {
        // Remove all whitespace for validation
        $whatsapp_trimmed = trim($whatsapp);
        
        // Validate format - allow digits, spaces, +, -, (, )
        if (!preg_match('/^[\d\s\+\-\(\)]+$/', $whatsapp_trimmed)) {
            $errors['whatsapp'] = 'Formato de WhatsApp inválido. Solo se permiten números, espacios, +, - y paréntesis';
        } else {
            // Normalize number - remove all non-digit characters except +
            $whatsapp_clean = preg_replace('/[^0-9+]/', '', $whatsapp_trimmed);
            
            // Remove leading + for length validation (we'll add it back if needed)
            $whatsapp_digits = ltrim($whatsapp_clean, '+');
            
            // Validate length (international format: 10-15 digits)
            $digit_count = strlen($whatsapp_digits);
            
            if ($digit_count < 10) {
                $errors['whatsapp'] = 'El número de WhatsApp debe tener al menos 10 dígitos';
            } elseif ($digit_count > 15) {
                $errors['whatsapp'] = 'El número de WhatsApp no puede tener más de 15 dígitos';
            } else {
                // Validate country code format (optional but helpful)
                // Most countries have 1-3 digit country codes
                // Mexico: +52, USA: +1, etc.
                
                // Check if it starts with common country codes for Mexico
                if (strlen($whatsapp_digits) >= 12 && substr($whatsapp_digits, 0, 2) !== '52') {
                    // If it's 12+ digits and doesn't start with 52 (Mexico), it might be valid
                    // We'll allow it but could add more specific validation here
                }
                
                // Store normalized version for later use
                // If no + prefix, it's assumed to be a local number
                if (strpos($whatsapp_clean, '+') !== 0) {
                    if ($digit_count == 10) {
                        // Assume it's a Mexican number without country code
                        // Could add 52 prefix, but we'll keep it as is for now
                    }
                }
            }
        }
    }
    
    if (!empty($mensaje) && strlen($mensaje) > 1000) {
        $errors['mensaje'] = 'El mensaje es demasiado largo (máximo 1000 caracteres)';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Por favor, corrija los errores en el formulario';
        ob_end_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // Sanitize data
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $especialidad = htmlspecialchars($especialidad, ENT_QUOTES, 'UTF-8');
    $whatsapp = htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8');
    $mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    
    // Prepare data for database
    $leadData = [
        'nombre' => $nombre,
        'especialidad' => $especialidad,
        'whatsapp' => $whatsapp,
        'mensaje' => $mensaje,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'source' => 'website'
    ];
    
    $leadId = null;
    
    // Save to database if enabled (CRITICAL - Always try to save)
    if (ENABLE_DATABASE_STORAGE) {
        try {
            $db = Database::getInstance();
            $leadId = $db->insertLead($leadData);
            
            if (!$leadId) {
                // Log error but don't fail the form submission
                error_log("SOPHEA Error: Failed to save lead to database. Data: " . json_encode($leadData));
                // Try to continue, but log the issue
            } else {
                // Successfully saved to database
                error_log("SOPHEA Success: Lead #{$leadId} saved to database for {$nombre}");
            }
        } catch (PDOException $e) {
            error_log("SOPHEA Database PDO Error: " . $e->getMessage());
            error_log("SOPHEA Failed Lead Data: " . json_encode($leadData));
            // Continue even if database fails - don't break user experience
        } catch (Exception $e) {
            error_log("SOPHEA Database Error: " . $e->getMessage());
            error_log("SOPHEA Failed Lead Data: " . json_encode($leadData));
            // Continue even if database fails
        }
    } else {
        error_log("SOPHEA Warning: Database storage is disabled in config_db.php");
    }
    
    // Send email notification if enabled
    if (ENABLE_EMAIL_NOTIFICATIONS) {
        try {
            $emailSent = sendEmailNotification($leadData, $leadId);
            
            if ($emailSent && $leadId) {
                try {
                    $db = Database::getInstance();
                    $db->logEmail($leadId, ADMIN_EMAIL, 'Nuevo Lead - ' . $nombre, 'sent');
                } catch (Exception $e) {
                    error_log("SOPHEA Email log error: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("SOPHEA Email error: " . $e->getMessage());
            // Continue even if email fails
        }
    }
    
    // Update rate limiting timestamp (only after successful processing)
    $_SESSION[$last_submit_key] = time();
    
    // Regenerate CSRF token after successful validation (for next request)
    if (ENABLE_CSRF_PROTECTION) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log("SOPHEA CSRF: Token regenerated for next request");
    }
    
    // Success response - Include lead ID if saved
    $response['success'] = true;
    $response['message'] = '¡Gracias por tu interés! Te contactaremos pronto.';
    if ($leadId) {
        $response['lead_id'] = $leadId;
        $response['message'] .= ' Tu solicitud ha sido registrada con el ID #' . $leadId;
    }
    
    // Generate WhatsApp link if enabled
    if (ENABLE_WHATSAPP_REDIRECT) {
        $whatsappMessage = "Hola, soy {$nombre}, Quiero asesoria gratuita";
        $response['whatsapp_url'] = get_whatsapp_link($whatsappMessage);
    }
    
} catch (Exception $e) {
    // Clear any output
    ob_clean();
    
    $response['success'] = false;
    $response['message'] = 'Error al procesar el formulario. Por favor, intenta de nuevo.';
    // Only include error details if DEBUG_MODE is defined and true
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['error_details'] = $e->getMessage();
    }
    
    error_log("SOPHEA Form Error: " . $e->getMessage());
    error_log("SOPHEA Form Error Trace: " . $e->getTraceAsString());
} catch (Error $e) {
    // Catch PHP 7+ errors (fatal errors, etc.)
    ob_clean();
    
    $response['success'] = false;
    $response['message'] = 'Error al procesar el formulario. Por favor, intenta de nuevo.';
    // Only include error details if DEBUG_MODE is defined and true
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['error_details'] = $e->getMessage();
    }
    
    error_log("SOPHEA Fatal Error: " . $e->getMessage());
    error_log("SOPHEA Fatal Error Trace: " . $e->getTraceAsString());
} catch (Throwable $e) {
    // Catch any other throwable
    ob_clean();
    
    $response['success'] = false;
    $response['message'] = 'Error al procesar el formulario. Por favor, intenta de nuevo.';
    // Only include error details if DEBUG_MODE is defined and true
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['error_details'] = $e->getMessage();
    }
    
    error_log("SOPHEA Throwable Error: " . $e->getMessage());
}

// Clear output buffer and return JSON
ob_end_clean();

// Ensure we're returning valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    $response = [
        'success' => false,
        'message' => 'Error al generar respuesta. Por favor, intenta de nuevo.',
        'errors' => []
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

/**
 * Send email notification to admin
 */
function sendEmailNotification($data, $leadId = null) {
    $to = ADMIN_EMAIL;
    $subject = 'Nuevo Lead desde el sitio web - ' . $data['nombre'];
    
    // Email body (HTML)
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #667eea; }
            .value { margin-top: 5px; padding: 10px; background: white; border-left: 3px solid #667eea; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎯 Nuevo Lead - SOPHEA</h1>
            </div>
            <div class="content">
                <p>Se ha recibido un nuevo contacto desde el formulario del sitio web:</p>
                
                <div class="field">
                    <div class="label">👤 Nombre:</div>
                    <div class="value">' . htmlspecialchars($data['nombre']) . '</div>
                </div>
                
                <div class="field">
                    <div class="label">💼 Especialidad/Giro:</div>
                    <div class="value">' . htmlspecialchars($data['especialidad']) . '</div>
                </div>
                
                <div class="field">
                    <div class="label">📱 WhatsApp:</div>
                    <div class="value"><a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $data['whatsapp']) . '">' . htmlspecialchars($data['whatsapp']) . '</a></div>
                </div>
                
                ' . (!empty($data['mensaje']) ? '
                <div class="field">
                    <div class="label">💬 Mensaje:</div>
                    <div class="value">' . nl2br(htmlspecialchars($data['mensaje'])) . '</div>
                </div>
                ' : '') . '
                
                <div class="field">
                    <div class="label">🌐 IP Address:</div>
                    <div class="value">' . htmlspecialchars($data['ip_address'] ?? 'N/A') . '</div>
                </div>
                
                <div class="field">
                    <div class="label">📅 Fecha:</div>
                    <div class="value">' . date('d/m/Y H:i:s') . '</div>
                </div>
                
                ' . ($leadId ? '
                <div class="field">
                    <div class="label">🔢 ID Lead:</div>
                    <div class="value">#' . $leadId . '</div>
                </div>
                ' : '') . '
            </div>
            <div class="footer">
                <p>Este email fue generado automáticamente por el sistema de contacto de SOPHEA</p>
                <p>&copy; ' . date('Y') . ' SOPHEA - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . $data['whatsapp'] . ' <noreply@sophea.com.mx>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email
    return mail($to, $subject, $message, implode("\r\n", $headers));
}
