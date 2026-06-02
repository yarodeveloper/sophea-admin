<?php
/**
 * SOPHEA - WhatsApp Business API Webhook
 * 
 * Handles webhook events from Meta WhatsApp Business API
 * 
 * Setup in Meta Business Manager:
 * - Callback URL: https://tudominio.com/webhook_whatsapp.php
 * - Verify Token: (set in config_whatsapp.php)
 */

// Load configurations
require_once 'config.php';
require_once 'config_db.php';
require_once 'config_whatsapp.php';
require_once 'classes/Database.php';

// Webhook Verification Token is loaded from config_whatsapp.php
// No need to redefine here

/**
 * Verify Webhook (GET request from Meta)
 * 
 * Meta sends a GET request to verify the webhook endpoint
 * Required parameters:
 * - hub.mode: "subscribe"
 * - hub.verify_token: Must match WHATSAPP_WEBHOOK_VERIFY_TOKEN
 * - hub.challenge: Random string to echo back
 * 
 * IMPORTANT: Meta expects ONLY the challenge string as response (text/plain), not JSON
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Meta sends parameters as hub.mode, hub.verify_token, hub.challenge
    // PHP converts dots to underscores, so we check both formats
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';
    
    // Log all received parameters for debugging
    error_log("SOPHEA Webhook Verification Request:");
    error_log("  - All GET params: " . json_encode($_GET));
    error_log("  - Mode: {$mode}");
    error_log("  - Token received: {$token}");
    error_log("  - Token expected: " . WHATSAPP_WEBHOOK_VERIFY_TOKEN);
    error_log("  - Challenge: {$challenge}");
    
    // Verify the token matches
    if ($mode === 'subscribe' && $token === WHATSAPP_WEBHOOK_VERIFY_TOKEN) {
        // Meta expects ONLY the challenge string, no JSON, no headers
        // Set plain text header for verification response
        header('Content-Type: text/plain');
        http_response_code(200);
        echo $challenge;
        error_log("SOPHEA Webhook Verified Successfully - Challenge echoed: {$challenge}");
        exit;
    } else {
        // Token mismatch or invalid mode - return JSON error
        header('Content-Type: application/json');
        $reason = '';
        if (empty($mode) && empty($token)) {
            $reason = "No verification parameters provided (this is normal for browser access)";
        } elseif ($mode !== 'subscribe') {
            $reason = "Invalid mode: '{$mode}' (expected 'subscribe')";
        } else {
            $reason = "Token mismatch. Received: '{$token}', Expected: '" . WHATSAPP_WEBHOOK_VERIFY_TOKEN . "'";
        }
        
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'reason' => $reason]);
        error_log("SOPHEA Webhook Verification Failed: {$reason}");
        exit;
    }
}

/**
 * Handle Webhook Events (POST request from Meta)
 * 
 * Meta sends POST requests with event data when:
 * - Messages are received
 * - Message status changes (sent, delivered, read, failed)
 * - Other events
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON response header for POST requests
    header('Content-Type: application/json');
    // Get raw input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log received webhook
    error_log("SOPHEA Webhook Received: " . $input);
    
    // Verify webhook signature (optional but recommended for production)
    // Meta sends X-Hub-Signature-256 header for verification
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!empty($signature) && !empty(WHATSAPP_CERTIFICATE)) {
        // Verify signature (implement if needed for production)
        // $expectedSignature = 'sha256=' . hash_hmac('sha256', $input, WHATSAPP_CERTIFICATE);
        // if (!hash_equals($signature, $expectedSignature)) {
        //     http_response_code(403);
        //     exit;
        // }
    }
    
    // Process webhook data
    try {
        // Meta sends data in 'object' and 'entry' structure
        if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
            // Process each entry
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $entryId = $entry['id'] ?? 'unknown';
                    
                    // Process changes in this entry
                    if (isset($entry['changes']) && is_array($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $field = $change['field'] ?? 'unknown';
                            $value = $change['value'] ?? [];
                            
                            error_log("SOPHEA Webhook: Processing field '{$field}' for entry '{$entryId}'");
                            
                            // Handle different webhook fields based on documentation
                            switch ($field) {
                                case 'messages':
                                    // Messages webhook: incoming messages and message statuses
                                    if (isset($value['messages'])) {
                                        // New message received
                                        processIncomingMessage($value);
                                    }
                                    
                                    if (isset($value['statuses'])) {
                                        // Message status update (sent, delivered, read, failed)
                                        processMessageStatus($value);
                                    }
                                    break;
                                    
                                case 'account_alerts':
                                    // Account alerts: messaging limits, business profile, OBA status
                                    processAccountAlerts($value);
                                    break;
                                    
                                case 'account_review_update':
                                    // Account review status update
                                    processAccountReviewUpdate($value);
                                    break;
                                    
                                case 'account_update':
                                    // Account updates: verification, policy violations, etc.
                                    processAccountUpdate($value);
                                    break;
                                    
                                case 'message_template_status_update':
                                    // Template status changes
                                    processTemplateStatusUpdate($value);
                                    break;
                                    
                                case 'message_template_quality_update':
                                    // Template quality score changes
                                    processTemplateQualityUpdate($value);
                                    break;
                                    
                                case 'phone_number_quality_update':
                                    // Phone number throughput level changes
                                    processPhoneNumberQualityUpdate($value);
                                    break;
                                    
                                default:
                                    // Log unknown field types
                                    error_log("SOPHEA Webhook: Unknown field type '{$field}'");
                                    break;
                            }
                        }
                    }
                }
            }
        } else {
            error_log("SOPHEA Webhook: Unknown object type: " . ($data['object'] ?? 'missing'));
        }
        
        // Always return 200 to acknowledge receipt
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        
    } catch (Exception $e) {
        error_log("SOPHEA Webhook Error: " . $e->getMessage());
        http_response_code(200); // Still return 200 to prevent retries
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit;
}

// If neither GET nor POST, return error
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

/**
 * Process incoming message
 * Handles messages webhook field according to Meta documentation
 */
function processIncomingMessage($value) {
    $messages = $value['messages'] ?? [];
    $contacts = $value['contacts'] ?? [];
    $metadata = $value['metadata'] ?? [];
    
    // Get metadata
    $phoneNumberId = $metadata['phone_number_id'] ?? '';
    $displayPhoneNumber = $metadata['display_phone_number'] ?? '';
    
    foreach ($messages as $message) {
        $messageId = $message['id'] ?? '';
        $from = $message['from'] ?? '';
        $timestamp = $message['timestamp'] ?? time();
        $type = $message['type'] ?? 'unknown';
        $context = $message['context'] ?? null; // For replied messages
        
        // Get contact info
        $contact = null;
        foreach ($contacts as $c) {
            if (($c['wa_id'] ?? '') === $from) {
                $contact = $c;
                break;
            }
        }
        $contactName = $contact['profile']['name'] ?? 'Desconocido';
        
        // Get message content based on type
        $messageText = '';
        $messageData = [];
        
        switch ($type) {
            case 'text':
                $messageText = $message['text']['body'] ?? '';
                break;
            case 'image':
                $caption = $message['image']['caption'] ?? '';
                $messageText = '[Imagen]' . ($caption ? ': ' . $caption : '');
                $messageData['image_id'] = $message['image']['id'] ?? '';
                break;
            case 'document':
                $filename = $message['document']['filename'] ?? 'Documento';
                $messageText = '[Documento] ' . $filename;
                $messageData['document_id'] = $message['document']['id'] ?? '';
                break;
            case 'audio':
                $messageText = '[Audio]';
                $messageData['audio_id'] = $message['audio']['id'] ?? '';
                break;
            case 'video':
                $caption = $message['video']['caption'] ?? '';
                $messageText = '[Video]' . ($caption ? ': ' . $caption : '');
                $messageData['video_id'] = $message['video']['id'] ?? '';
                break;
            case 'location':
                $latitude = $message['location']['latitude'] ?? '';
                $longitude = $message['location']['longitude'] ?? '';
                $messageText = "[Ubicación] Lat: {$latitude}, Lng: {$longitude}";
                break;
            case 'contacts':
                $messageText = '[Contacto compartido]';
                break;
            case 'interactive':
                // Button or list response
                $interactive = $message['interactive'] ?? [];
                $interactiveType = $interactive['type'] ?? '';
                if ($interactiveType === 'button_reply') {
                    $messageText = '[Botón] ' . ($interactive['button_reply']['title'] ?? '');
                } elseif ($interactiveType === 'list_reply') {
                    $messageText = '[Lista] ' . ($interactive['list_reply']['title'] ?? '');
                }
                break;
            default:
                $messageText = "[Tipo: {$type}]";
                break;
        }
        
        // Check if it's a reply
        if ($context) {
            $repliedToId = $context['id'] ?? '';
            $messageText = "[Respuesta a: {$repliedToId}] " . $messageText;
        }
        
        // Log incoming message
        error_log("SOPHEA Incoming WhatsApp: From={$from}, Name={$contactName}, Type={$type}, Message={$messageText}");
        
        // Try to find lead by WhatsApp number
        try {
            $db = Database::getInstance();
            
            // Search for lead with this WhatsApp number
            $whatsappClean = preg_replace('/[^0-9]/', '', $from);
            $stmt = $db->getConnection()->prepare("SELECT * FROM leads WHERE whatsapp LIKE ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute(['%' . $whatsappClean . '%']);
            $lead = $stmt->fetch();
            
            if ($lead) {
                // Update lead status or add note
                $existingNotes = $lead['notes'] ?? '';
                $newNote = date('Y-m-d H:i:s') . " - Mensaje recibido ({$type}): {$messageText}";
                $updatedNotes = $existingNotes ? $existingNotes . "\n" . $newNote : $newNote;
                
                // Only update to 'contactado' if it's 'nuevo'
                $newStatus = $lead['status'] === 'nuevo' ? 'contactado' : $lead['status'];
                $db->updateLeadStatus($lead['id'], $newStatus, $updatedNotes);
                error_log("SOPHEA Lead #{$lead['id']} updated with incoming message");
            } else {
                // Could create a new lead from incoming message if desired
                error_log("SOPHEA Incoming message from unknown number: {$from} ({$contactName})");
            }
        } catch (Exception $e) {
            error_log("SOPHEA Error processing incoming message: " . $e->getMessage());
        }
    }
}

/**
 * Process message status updates
 * Handles status updates for sent messages
 */
function processMessageStatus($value) {
    $statuses = $value['statuses'] ?? [];
    $metadata = $value['metadata'] ?? [];
    
    foreach ($statuses as $status) {
        $messageId = $status['id'] ?? '';
        $recipientId = $status['recipient_id'] ?? '';
        $statusType = $status['status'] ?? 'unknown';
        $timestamp = $status['timestamp'] ?? time();
        $conversation = $status['conversation'] ?? null;
        $pricing = $status['pricing'] ?? null;
        
        // Status types: sent, delivered, read, failed
        error_log("SOPHEA Message Status: ID={$messageId}, Status={$statusType}, Recipient={$recipientId}");
        
        // Log pricing information if available
        if ($pricing) {
            $pricingModel = $pricing['pricing_model'] ?? 'unknown';
            $category = $pricing['category'] ?? 'unknown';
            error_log("SOPHEA Message Pricing: Model={$pricingModel}, Category={$category}");
        }
        
        // Handle failed messages
        if ($statusType === 'failed') {
            $error = $status['errors'] ?? [];
            foreach ($error as $err) {
                $errorCode = $err['code'] ?? '';
                $errorMessage = $err['message'] ?? '';
                $errorTitle = $err['title'] ?? '';
                error_log("SOPHEA Message Failed: Code={$errorCode}, Title={$errorTitle}, Message={$errorMessage}");
            }
        }
        
        // You can update message status in database if you track message IDs
        // This is useful for delivery confirmation and analytics
        try {
            $db = Database::getInstance();
            // Example: Update a messages table if you create one
            // $stmt = $db->getConnection()->prepare("UPDATE whatsapp_messages SET status = ? WHERE message_id = ?");
            // $stmt->execute([$statusType, $messageId]);
        } catch (Exception $e) {
            error_log("SOPHEA Error updating message status: " . $e->getMessage());
        }
    }
}

/**
 * Process contact information
 */
function processContactInfo($value) {
    $contacts = $value['contacts'] ?? [];
    
    foreach ($contacts as $contact) {
        $waId = $contact['wa_id'] ?? '';
        $profile = $contact['profile'] ?? [];
        $name = $profile['name'] ?? 'Desconocido';
        
        error_log("SOPHEA Contact Info: WA_ID={$waId}, Name={$name}");
        
        // You can update contact information in database if needed
    }
}

/**
 * Process account alerts
 */
function processAccountAlerts($value) {
    error_log("SOPHEA Account Alert: " . json_encode($value));
    // Handle account alerts: messaging limits, business profile changes, OBA status
}

/**
 * Process account review update
 */
function processAccountReviewUpdate($value) {
    error_log("SOPHEA Account Review Update: " . json_encode($value));
    // Handle account review status changes
}

/**
 * Process account update
 */
function processAccountUpdate($value) {
    error_log("SOPHEA Account Update: " . json_encode($value));
    // Handle account updates: verification, policy violations, etc.
}

/**
 * Process template status update
 */
function processTemplateStatusUpdate($value) {
    error_log("SOPHEA Template Status Update: " . json_encode($value));
    // Handle template status changes
}

/**
 * Process template quality update
 */
function processTemplateQualityUpdate($value) {
    error_log("SOPHEA Template Quality Update: " . json_encode($value));
    // Handle template quality score changes
}

/**
 * Process phone number quality update
 */
function processPhoneNumberQualityUpdate($value) {
    error_log("SOPHEA Phone Number Quality Update: " . json_encode($value));
    // Handle phone number throughput level changes
}

