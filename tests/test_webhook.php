<?php
/**
 * SOPHEA - Webhook Test Page
 * 
 * Test page to verify webhook configuration and simulate Meta verification
 */

// Check authentication
require_once '../admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Test Webhook - SOPHEA';
$auth_result = requireAdminAuth();

require_once '../config.php';
require_once '../config_whatsapp.php';

$testResult = null;
$testError = null;

// Handle manual test
if (isset($_POST['test_verification'])) {
    $testToken = $_POST['test_token'] ?? '';
    $expectedToken = WHATSAPP_WEBHOOK_VERIFY_TOKEN;
    
    if ($testToken === $expectedToken) {
        $testResult = [
            'success' => true,
            'message' => 'Token coincide correctamente',
            'expected' => $expectedToken,
            'received' => $testToken
        ];
    } else {
        $testResult = [
            'success' => false,
            'message' => 'Token NO coincide',
            'expected' => $expectedToken,
            'received' => $testToken
        ];
    }
}

// Simulate Meta verification request
if (isset($_GET['simulate'])) {
    $challenge = bin2hex(random_bytes(16));
    $token = WHATSAPP_WEBHOOK_VERIFY_TOKEN;
    
    // Build test URL
    $testUrl = "https://ia.sopheamkt.com/webhook_whatsapp.php?hub.mode=subscribe&hub.verify_token={$token}&hub.challenge={$challenge}";
    
    // Make request
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $testError = "Error cURL: " . $error;
    } else {
        $testResult = [
            'success' => ($httpCode === 200 && $response === $challenge),
            'http_code' => $httpCode,
            'response' => $response,
            'expected_challenge' => $challenge,
            'matches' => ($response === $challenge)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Webhook - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">🔍 Test de Webhook WhatsApp</h1>
            
            <!-- Current Configuration -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-800">📋 Configuración Actual</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Webhook URL:</span>
                        <span class="text-gray-900 font-mono"><?php echo WHATSAPP_WEBHOOK_URL; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Verify Token:</span>
                        <span class="text-gray-900 font-mono break-all"><?php echo WHATSAPP_WEBHOOK_VERIFY_TOKEN; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Access Token:</span>
                        <span class="text-gray-900 font-mono break-all text-xs"><?php echo substr(WHATSAPP_ACCESS_TOKEN, 0, 50) . '...'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Test Results -->
            <?php if ($testResult): ?>
                <div class="mb-6 p-6 rounded-lg <?php echo $testResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <h3 class="text-lg font-semibold mb-3 <?php echo $testResult['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                        <?php echo $testResult['success'] ? '✅ Prueba Exitosa' : '❌ Prueba Fallida'; ?>
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p class="<?php echo $testResult['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php echo $testResult['message']; ?>
                        </p>
                        <?php if (isset($testResult['http_code'])): ?>
                            <div class="mt-4 space-y-1">
                                <p><strong>HTTP Code:</strong> <?php echo $testResult['http_code']; ?></p>
                                <p><strong>Response:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($testResult['response']); ?></code></p>
                                <p><strong>Expected Challenge:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($testResult['expected_challenge']); ?></code></p>
                                <p><strong>Matches:</strong> <?php echo $testResult['matches'] ? '✅ Sí' : '❌ No'; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 space-y-1">
                                <p><strong>Expected:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($testResult['expected']); ?></code></p>
                                <p><strong>Received:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($testResult['received']); ?></code></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($testError): ?>
                <div class="mb-6 p-6 rounded-lg bg-red-50 border border-red-200">
                    <h3 class="text-lg font-semibold mb-3 text-red-800">❌ Error</h3>
                    <p class="text-red-700"><?php echo htmlspecialchars($testError); ?></p>
                </div>
            <?php endif; ?>

            <!-- Test Options -->
            <div class="space-y-4">
                <!-- Test 1: Simulate Meta Verification -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">🧪 Prueba 1: Simular Verificación de Meta</h2>
                    <p class="text-gray-600 mb-4 text-sm">
                        Esta prueba simula la solicitud GET que Meta envía para verificar el webhook.
                        Debería devolver el challenge y código HTTP 200.
                    </p>
                    <form method="GET">
                        <input type="hidden" name="simulate" value="1">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition">
                            Ejecutar Prueba de Verificación
                        </button>
                    </form>
                </div>

                <!-- Test 2: Manual Token Check -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">🔑 Prueba 2: Verificar Token Manualmente</h2>
                    <p class="text-gray-600 mb-4 text-sm">
                        Ingresa el token de verificación para compararlo con el configurado.
                    </p>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Token de Verificación:</label>
                            <input type="text" name="test_token" value="<?php echo htmlspecialchars(WHATSAPP_WEBHOOK_VERIFY_TOKEN); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                        </div>
                        <button type="submit" name="test_verification" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition">
                            Verificar Token
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="border border-yellow-200 bg-yellow-50 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 text-yellow-800">📝 Instrucciones para Verificar en Meta</h2>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-yellow-900">
                        <li>Ve a <a href="https://business.facebook.com" target="_blank" class="text-blue-600 underline">Meta Business Manager</a></li>
                        <li>Navega a: <strong>Configuración → Recursos empresariales → Tu cuenta de WhatsApp → Configuración → Webhooks</strong></li>
                        <li>Configura el webhook:
                            <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                <li><strong>Callback URL:</strong> <code class="bg-yellow-100 px-2 py-1 rounded"><?php echo WHATSAPP_WEBHOOK_URL; ?></code></li>
                                <li><strong>Verify Token:</strong> <code class="bg-yellow-100 px-2 py-1 rounded"><?php echo WHATSAPP_WEBHOOK_VERIFY_TOKEN; ?></code></li>
                            </ul>
                        </li>
                        <li>Haz clic en <strong>"Verificar y guardar"</strong></li>
                        <li>Meta enviará automáticamente una solicitud GET con los parámetros correctos</li>
                        <li>Si la verificación es exitosa, verás un mensaje de confirmación en Meta</li>
                    </ol>
                </div>

                <!-- Important Note -->
                <div class="border border-gray-300 bg-gray-50 rounded-lg p-6">
                    <h3 class="font-semibold mb-2 text-gray-800">⚠️ Nota Importante</h3>
                    <p class="text-sm text-gray-700">
                        Si accedes directamente a <code class="bg-gray-200 px-2 py-1 rounded">/webhook_whatsapp.php</code> desde tu navegador, 
                        verás un error 403. Esto es <strong>normal y esperado</strong>, ya que el webhook está diseñado para recibir 
                        solicitudes de Meta, no de navegadores. La verificación debe hacerse desde Meta Business Manager.
                    </p>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="admin_whatsapp_config.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Volver a Configuración de WhatsApp
                </a>
            </div>
        </div>
    </div>
</body>
</html>

