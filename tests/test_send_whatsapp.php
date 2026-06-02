<?php
/**
 * SOPHEA - Test WhatsApp Send
 * 
 * Test page to send WhatsApp messages and see detailed API responses
 */

// Check authentication
require_once '../admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Test Envío WhatsApp - SOPHEA';
$auth_result = requireAdminAuth();

require_once '../config.php';
require_once '../config_db.php';
require_once '../config_whatsapp.php';
require_once '../classes/Database.php';
require_once '../classes/WhatsAppAPI.php';

$testResult = null;
$testError = null;

// Handle test send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_send'])) {
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $message = trim($_POST['message'] ?? 'Hola, este es un mensaje de prueba desde SOPHEA');
    
    if (empty($phoneNumber)) {
        $testError = 'Por favor ingresa un número de teléfono';
    } else {
        try {
            $whatsappAPI = new WhatsAppAPI();
            
            // Normalize phone number manually to show what will be sent
            $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
            $cleaned = ltrim($cleaned, '+');
            if (strlen($cleaned) == 10 && substr($cleaned, 0, 1) != '1') {
                $cleaned = '52' . $cleaned;
            }
            $normalizedPhone = $cleaned;
            
            // Prepare payload info (what will be sent)
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $normalizedPhone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ];
            
            // Send message
            $result = $whatsappAPI->sendMessage($phoneNumber, $message);
            
            $testResult = [
                'success' => true,
                'phone_original' => $phoneNumber,
                'phone_normalized' => $normalizedPhone,
                'message' => $message,
                'message_id' => $result['message_id'] ?? null,
                'response' => $result['response'] ?? null,
                'http_code' => $result['http_code'] ?? null,
                'raw_response' => $result['raw_response'] ?? null,
                'payload_sent' => $payload,
                'api_url' => WHATSAPP_API_BASE_URL . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages'
            ];
        } catch (Exception $e) {
            $testError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Envío WhatsApp - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">📱 Test de Envío WhatsApp</h1>
            
            <!-- Current Configuration -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-800">📋 Configuración Actual</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Phone Number ID:</span>
                        <span class="text-gray-900 font-mono"><?php echo WHATSAPP_PHONE_NUMBER_ID; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">API Version:</span>
                        <span class="text-gray-900 font-mono"><?php echo WHATSAPP_API_VERSION; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">API Enabled:</span>
                        <span class="text-gray-900"><?php echo WHATSAPP_API_ENABLED ? '✅ Sí' : '❌ No'; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Access Token:</span>
                        <span class="text-gray-900 font-mono text-xs break-all"><?php echo substr(WHATSAPP_ACCESS_TOKEN, 0, 30) . '...'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Test Results -->
            <?php if ($testResult): ?>
                <div class="mb-6 p-6 rounded-lg bg-green-50 border border-green-200">
                    <h3 class="text-lg font-semibold mb-3 text-green-800">✅ Mensaje Enviado</h3>
                    <div class="space-y-2 text-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="font-medium text-gray-700">Número Original:</p>
                                <p class="text-gray-900 font-mono"><?php echo htmlspecialchars($testResult['phone_original']); ?></p>
                            </div>
                            <div>
                                <p class="font-medium text-gray-700">Número Normalizado:</p>
                                <p class="text-gray-900 font-mono"><?php echo htmlspecialchars($testResult['phone_normalized']); ?></p>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700">Mensaje:</p>
                            <p class="text-gray-900 bg-white p-3 rounded border"><?php echo nl2br(htmlspecialchars($testResult['message'])); ?></p>
                        </div>
                        <?php if (isset($testResult['api_url'])): ?>
                            <div>
                                <p class="font-medium text-gray-700">URL de la API:</p>
                                <p class="text-gray-900 font-mono bg-white p-3 rounded border text-xs break-all"><?php echo htmlspecialchars($testResult['api_url']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($testResult['payload_sent'])): ?>
                            <div>
                                <p class="font-medium text-gray-700 mb-2">Payload Enviado a la API:</p>
                                <pre class="bg-blue-50 text-blue-900 p-4 rounded text-xs overflow-auto max-h-96 border border-blue-200"><?php echo json_encode($testResult['payload_sent'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-gray-700">Message ID:</p>
                            <p class="text-gray-900 font-mono bg-white p-3 rounded border"><?php echo htmlspecialchars($testResult['message_id'] ?? 'N/A'); ?></p>
                        </div>
                        <?php if (isset($testResult['http_code'])): ?>
                            <div>
                                <p class="font-medium text-gray-700">HTTP Status Code:</p>
                                <p class="text-gray-900 font-mono bg-white p-3 rounded border"><?php echo htmlspecialchars($testResult['http_code']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($testResult['response']): ?>
                            <div>
                                <p class="font-medium text-gray-700 mb-2">Respuesta JSON de la API:</p>
                                <pre class="bg-gray-900 text-green-400 p-4 rounded text-xs overflow-auto max-h-96"><?php echo json_encode($testResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($testResult['raw_response'])): ?>
                            <div>
                                <p class="font-medium text-gray-700 mb-2">Respuesta Raw (sin procesar):</p>
                                <pre class="bg-gray-800 text-yellow-400 p-4 rounded text-xs overflow-auto max-h-96"><?php echo htmlspecialchars($testResult['raw_response']); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-sm text-yellow-800">
                            <strong>⚠️ Nota:</strong> Si el mensaje no llegó al destinatario, puede ser porque:
                        </p>
                        <ul class="text-sm text-yellow-700 mt-2 ml-4 list-disc">
                            <li>El número no está en la ventana de 24 horas (solo puedes enviar mensajes libres dentro de 24h después de que el usuario te escriba)</li>
                            <li>El número no está registrado en WhatsApp</li>
                            <li>El número tiene restricciones o bloqueos</li>
                            <li>Necesitas usar un template aprobado para números fuera de la ventana de 24 horas</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($testError): ?>
                <div class="mb-6 p-6 rounded-lg bg-red-50 border border-red-200">
                    <h3 class="text-lg font-semibold mb-3 text-red-800">❌ Error al Enviar</h3>
                    <p class="text-red-700 font-medium mb-3"><?php echo htmlspecialchars($testError); ?></p>
                    
                    <?php if (strpos($testError, '190') !== false || strpos($testError, 'expired') !== false || strpos($testError, 'Token') !== false): ?>
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-300 rounded">
                            <p class="text-sm text-yellow-900 font-semibold mb-3">🔑 Cómo Generar un Nuevo Token de Acceso:</p>
                            <ol class="text-sm text-yellow-800 ml-4 list-decimal space-y-2">
                                <li>Ve a <a href="https://business.facebook.com" target="_blank" class="text-blue-600 underline font-medium">Meta Business Manager</a></li>
                                <li>Navega a: <strong>Configuración → Recursos empresariales</strong></li>
                                <li>Selecciona tu <strong>App de WhatsApp</strong></li>
                                <li>Ve a <strong>Herramientas → Token de acceso</strong></li>
                                <li>Genera un nuevo token (o renueva el existente)</li>
                                <li>Copia el token y actualízalo en <a href="admin_whatsapp_config.php" class="text-blue-600 underline font-medium">Configuración WhatsApp</a></li>
                            </ol>
                            <p class="text-xs text-yellow-700 mt-3">
                                <strong>Nota:</strong> Los tokens de acceso pueden expirar. Los tokens de larga duración suelen durar 60 días.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 p-4 bg-red-100 rounded">
                            <p class="text-sm text-red-800 font-semibold mb-2">Posibles soluciones:</p>
                            <ul class="text-sm text-red-700 ml-4 list-disc">
                                <li>Verifica que el número esté en formato correcto (con código de país)</li>
                                <li>Verifica que el Access Token sea válido y no haya expirado</li>
                                <li>Revisa los logs del servidor para más detalles</li>
                                <li>Si el error es "ventana de 24 horas", necesitas usar un template aprobado</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Test Form -->
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">🧪 Enviar Mensaje de Prueba</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="phone_number" name="phone_number" required
                               placeholder="Ej: 521234567890 o +52 123 456 7890"
                               value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Formato: código de país + número (ej: 521234567890 para México)
                        </p>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Mensaje
                        </label>
                        <textarea id="message" name="message" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['message'] ?? 'Hola, este es un mensaje de prueba desde SOPHEA'); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Máximo 4096 caracteres
                        </p>
                    </div>

                    <button type="submit" name="test_send" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition">
                        📤 Enviar Mensaje de Prueba
                    </button>
                </form>
            </div>

            <!-- Important Information -->
            <div class="mt-6 border border-yellow-200 bg-yellow-50 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-yellow-800">⚠️ Información Importante sobre WhatsApp Business API</h3>
                <div class="text-sm text-yellow-900 space-y-2">
                    <p><strong>Ventana de 24 horas:</strong></p>
                    <ul class="ml-4 list-disc space-y-1">
                        <li>Solo puedes enviar mensajes libres (text) dentro de 24 horas después de que el usuario te escriba</li>
                        <li>Fuera de esa ventana, debes usar templates aprobados por Meta</li>
                        <li>Si el número nunca te ha escrito, necesitas usar un template</li>
                    </ul>
                    <p class="mt-3"><strong>Números de prueba:</strong></p>
                    <ul class="ml-4 list-disc space-y-1">
                        <li>Puedes agregar números de prueba en Meta Business Manager</li>
                        <li>Los números de prueba pueden recibir mensajes libres sin restricciones</li>
                    </ul>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="admin.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Volver al Admin
                </a>
            </div>
        </div>
    </div>
</body>
</html>

