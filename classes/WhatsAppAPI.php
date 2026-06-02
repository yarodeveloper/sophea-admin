<?php
/**
 * SOPHEA - WhatsApp Business API Class
 * 
 * Handles sending messages via WhatsApp Business API
 */

require_once __DIR__ . '/../config_whatsapp.php';

class WhatsAppAPI {
    private $phoneNumberId;
    private $accessToken;
    private $apiBaseUrl;
    
    public function __construct() {
        $this->phoneNumberId = WHATSAPP_PHONE_NUMBER_ID;
        $this->accessToken = WHATSAPP_ACCESS_TOKEN;
        $this->apiBaseUrl = WHATSAPP_API_BASE_URL;
    }
    
    /**
     * Normalize phone number for WhatsApp API
     * WhatsApp requires format: country code + number (without + or spaces)
     */
    private function normalizePhoneNumber($phone) {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove leading +
        $cleaned = ltrim($cleaned, '+');
        
        // If it's a Mexican number without country code, add 52
        if (strlen($cleaned) == 10 && substr($cleaned, 0, 1) != '1') {
            $cleaned = '52' . $cleaned;
        }
        
        return $cleaned;
    }
    
    /**
     * Send text message via WhatsApp Business API
     * 
     * @param string $to Phone number (will be normalized)
     * @param string $message Message text
     * @return array Response from API
     */
    public function sendMessage($to, $message) {
        if (!WHATSAPP_API_ENABLED) {
            throw new Exception('WhatsApp API is disabled');
        }
        
        if (empty($this->accessToken) || $this->accessToken === 'YOUR_ACCESS_TOKEN_HERE') {
            throw new Exception('WhatsApp Access Token not configured. Please set WHATSAPP_ACCESS_TOKEN in config_whatsapp.php');
        }
        
        // Normalize phone number
        $phoneNumber = $this->normalizePhoneNumber($to);
        
        // API endpoint
        $url = $this->apiBaseUrl . '/' . $this->phoneNumberId . '/messages';
        
        // Request payload
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];
        
        // Headers
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];
        
        // Send request using Graph API standards
        // According to Graph API docs: HTTPS required, version in URL, Bearer token in header
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Verify hostname matches certificate
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects (Graph API doesn't redirect)
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        // Log request (following Graph API best practices)
        if (WHATSAPP_LOG_MESSAGES) {
            error_log("SOPHEA WhatsApp API Request: " . json_encode([
                'method' => 'POST',
                'url' => $url,
                'api_version' => WHATSAPP_API_VERSION,
                'to' => $phoneNumber,
                'message_length' => strlen($message),
                'http_code' => $httpCode,
                'total_time' => $curlInfo['total_time'] ?? null
            ]));
        }
        
        if ($error) {
            error_log("SOPHEA WhatsApp API cURL Error: " . $error);
            throw new Exception('Error al conectar con WhatsApp API: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        // Always log full response for debugging
        if (WHATSAPP_LOG_MESSAGES) {
            error_log("SOPHEA WhatsApp API Full Response: " . json_encode([
                'http_code' => $httpCode,
                'response' => $responseData,
                'raw_response' => $response
            ]));
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            // Check if response actually contains a message ID (success)
            if (isset($responseData['messages']) && isset($responseData['messages'][0]['id'])) {
                // Success - message was accepted
                if (WHATSAPP_LOG_MESSAGES) {
                    error_log("SOPHEA WhatsApp API Success: Message ID = " . $responseData['messages'][0]['id']);
                }
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['id'],
                    'response' => $responseData,
                    'http_code' => $httpCode,
                    'raw_response' => $response
                ];
            } else {
                // HTTP 200 but no message ID - something is wrong
                error_log("SOPHEA WhatsApp API Warning: HTTP 200 but no message ID in response: " . json_encode($responseData));
                throw new Exception('La API respondió con éxito pero no se generó un ID de mensaje. Verifica los logs para más detalles.');
            }
        } else {
            // Error response
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
            $errorCode = $responseData['error']['code'] ?? $httpCode;
            $errorType = $responseData['error']['type'] ?? 'unknown';
            $errorSubcode = $responseData['error']['error_subcode'] ?? null;
            
            // Provide more helpful error messages
            $detailedError = $errorMessage;
            
            // Common error codes and their meanings
            if ($errorCode == 190) {
                // Token expired or invalid
                if (strpos($errorMessage, 'expired') !== false || strpos($errorMessage, 'Session has expired') !== false) {
                    $detailedError = '⚠️ TOKEN EXPIRADO: Tu token de acceso ha expirado. Necesitas generar un nuevo token en Meta Business Manager. Ve a: Configuración → Recursos empresariales → Tu App → Herramientas → Token de acceso.';
                } else {
                    $detailedError = 'Token de acceso inválido. Verifica que el token sea correcto en config_whatsapp.php.';
                }
            } elseif ($errorCode == 131047) {
                $detailedError = 'El número de teléfono no está registrado en WhatsApp. Verifica que el número sea correcto y esté activo en WhatsApp.';
            } elseif ($errorCode == 131026) {
                $detailedError = 'No puedes enviar mensajes libres fuera de la ventana de 24 horas. Debes usar un template aprobado.';
            } elseif ($errorCode == 131031) {
                $detailedError = 'El número de teléfono no es válido. Verifica el formato del número.';
            } elseif ($errorCode == 100) {
                $detailedError = 'Parámetros inválidos en la solicitud. Verifica la configuración.';
            }
            
            error_log("SOPHEA WhatsApp API Error: " . json_encode([
                'http_code' => $httpCode,
                'error_code' => $errorCode,
                'error_type' => $errorType,
                'error_subcode' => $errorSubcode,
                'error_message' => $errorMessage,
                'full_error' => $responseData['error'] ?? $response
            ]));
            
            throw new Exception($detailedError . ' (Código: ' . $errorCode . ')');
        }
    }
    
    /**
     * Send template message (for approved templates)
     * 
     * @param string $to Phone number
     * @param string $templateName Template name
     * @param array $parameters Template parameters
     * @param string $languageCode Language code (default: 'es', can be 'es_MX', 'es_ES', 'es', etc.)
     * @return array Response from API
     */
    public function sendTemplateMessage($to, $templateName, $parameters = [], $languageCode = 'es') {
        if (!WHATSAPP_API_ENABLED) {
            throw new Exception('WhatsApp API is disabled');
        }
        
        $phoneNumber = $this->normalizePhoneNumber($to);
        $url = $this->apiBaseUrl . '/' . $this->phoneNumberId . '/messages';
        
        // Normalize template name (remove spaces, ensure lowercase for consistency)
        $normalizedTemplateName = strtolower(trim($templateName));
        
        // Try different language codes if the first one fails
        $languageCodes = [$languageCode, 'es_MX', 'es_ES', 'es', 'en_US', 'en'];
        
        $lastError = null;
        
        foreach ($languageCodes as $langCode) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phoneNumber,
                'type' => 'template',
                'template' => [
                    'name' => $normalizedTemplateName,
                    'language' => [
                        'code' => $langCode
                    ],
                    'components' => []
                ]
            ];
        
            // Add parameters if provided
            if (!empty($parameters)) {
                $payload['template']['components'][] = [
                    'type' => 'body',
                    'parameters' => array_map(function($param) {
                        return ['type' => 'text', 'text' => $param];
                    }, $parameters)
                ];
            }
            
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ];
            
            // Send request using Graph API standards
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("SOPHEA WhatsApp Template API cURL Error: " . $curlError);
                throw new Exception('Error al conectar con WhatsApp API: ' . $curlError);
            }
            
            $responseData = json_decode($response, true);
            
            // Log response
            if (WHATSAPP_LOG_MESSAGES) {
                error_log("SOPHEA WhatsApp Template API Response (Lang: {$langCode}): " . json_encode([
                    'http_code' => $httpCode,
                    'template' => $normalizedTemplateName,
                    'language' => $langCode,
                    'response' => $responseData
                ]));
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                if (isset($responseData['messages']) && isset($responseData['messages'][0]['id'])) {
                    return [
                        'success' => true,
                        'message_id' => $responseData['messages'][0]['id'],
                        'response' => $responseData,
                        'language_used' => $langCode
                    ];
                } else {
                    error_log("SOPHEA WhatsApp Template API Warning: HTTP 200 but no message ID");
                    throw new Exception('La API respondió con éxito pero no se generó un ID de mensaje.');
                }
            } else {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
                $errorCode = $responseData['error']['code'] ?? $httpCode;
                
                // Store error for potential retry
                $lastError = [
                    'code' => $errorCode,
                    'message' => $errorMessage,
                    'language' => $langCode
                ];
                
                // If it's a template translation error (132001), try next language code
                if ($errorCode == 132001 && $langCode !== end($languageCodes)) {
                    error_log("SOPHEA WhatsApp Template API: Template not found with language '{$langCode}', trying next language code...");
                    continue; // Try next language code
                }
                
                // If we've tried all language codes or it's a different error, throw exception
                if ($langCode === end($languageCodes) || $errorCode != 132001) {
                    // Provide helpful error messages
                    $detailedError = $errorMessage;
                    if ($errorCode == 132000) {
                        $detailedError = 'La plantilla no existe o no está aprobada. Verifica el nombre de la plantilla en Meta Business Manager.';
                    } elseif ($errorCode == 132001) {
                        $detailedError = "La plantilla '{$normalizedTemplateName}' no existe en ninguna traducción disponible. Verifica:\n1. El nombre exacto de la plantilla en Meta Business Manager\n2. Que la plantilla esté aprobada\n3. Que el nombre coincida exactamente (mayúsculas/minúsculas)";
                    } elseif ($errorCode == 132005) {
                        $detailedError = 'Parámetros de plantilla inválidos. Verifica que los parámetros coincidan con la plantilla.';
                    }
                    
                    error_log("SOPHEA WhatsApp Template API Error: Code={$errorCode}, Message={$errorMessage}, Template={$normalizedTemplateName}, Languages tried: " . implode(', ', $languageCodes));
                    throw new Exception($detailedError . ' (Código: ' . $errorCode . ')');
                }
            }
        }
        
        // Should not reach here, but just in case
        throw new Exception('Error inesperado al enviar plantilla');
    }
    
    /**
     * Validate phone number format
     */
    public function validatePhoneNumber($phone) {
        $normalized = $this->normalizePhoneNumber($phone);
        return strlen($normalized) >= 10 && strlen($normalized) <= 15;
    }
    
    /**
     * Check token validity and expiration
     * 
     * @return array Token information including validity and expiration
     */
    public function checkTokenValidity() {
        if (empty($this->accessToken)) {
            return [
                'valid' => false,
                'error' => 'Token no configurado'
            ];
        }
        
        try {
            $url = $this->apiBaseUrl . '/debug_token?input_token=' . urlencode($this->accessToken) . '&access_token=' . urlencode($this->accessToken);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                if (isset($data['data'])) {
                    $tokenData = $data['data'];
                    $isValid = $tokenData['is_valid'] ?? false;
                    $expiresAt = $tokenData['expires_at'] ?? null;
                    $type = $tokenData['type'] ?? 'unknown';
                    
                    $result = [
                        'valid' => $isValid,
                        'type' => $type,
                        'expires_at' => $expiresAt
                    ];
                    
                    if ($expiresAt) {
                        $expiresTimestamp = $expiresAt;
                        $currentTimestamp = time();
                        $daysUntilExpiry = ($expiresTimestamp - $currentTimestamp) / 86400;
                        
                        $result['days_until_expiry'] = round($daysUntilExpiry, 1);
                        $result['expires_soon'] = $daysUntilExpiry < 7; // Warn if less than 7 days
                        $result['expired'] = $expiresTimestamp < $currentTimestamp;
                    }
                    
                    return $result;
                }
            }
            
            return [
                'valid' => false,
                'error' => 'No se pudo verificar el token'
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

