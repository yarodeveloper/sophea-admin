<?php
/**
 * SOPHEA - Generate Long-Lived WhatsApp Access Token
 * 
 * Esta utilidad convierte un token de corta duración a uno de larga duración (60 días)
 * 
 * INSTRUCCIONES:
 * 1. Obtén un token de corta duración desde Meta Business Manager
 * 2. Ejecuta este script con el token corto como parámetro
 * 3. Copia el token de larga duración generado
 * 4. Actualízalo en config_whatsapp.php o admin_whatsapp_config.php
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'config_whatsapp.php';

// Configuración
$appId = 'TU_APP_ID'; // Reemplaza con tu App ID de Meta
$appSecret = 'TU_APP_SECRET'; // Reemplaza con tu App Secret de Meta

// Obtener token de corta duración (puede venir de parámetro GET o POST)
$shortLivedToken = $_GET['token'] ?? $_POST['token'] ?? WHATSAPP_ACCESS_TOKEN ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Token de Larga Duración - WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-sm p-8">
            <h1 class="text-2xl font-bold mb-6 flex items-center space-x-2">
                <i class="ph-fill ph-key text-purple-600"></i>
                <span>Generar Token de Larga Duración (60 días)</span>
            </h1>
            
            <?php if (empty($shortLivedToken) || $shortLivedToken === 'TU_TOKEN_AQUI'): ?>
                <!-- Formulario para ingresar token -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                    <p class="text-blue-800 font-semibold mb-2">📋 Instrucciones:</p>
                    <ol class="text-sm text-blue-700 space-y-2 ml-4 list-decimal">
                        <li>Obtén un token de corta duración desde <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="underline font-medium">Graph API Explorer</a></li>
                        <li>Pega el token en el campo de abajo</li>
                        <li>Haz clic en "Generar Token de Larga Duración"</li>
                        <li>Copia el token generado y actualízalo en la configuración</li>
                    </ol>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Token de Corta Duración:
                        </label>
                        <textarea name="token" rows="3" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                                  placeholder="Pega aquí tu token de corta duración obtenido de Meta Business Manager"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Este token debe tener permisos de whatsapp_business_messaging</p>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4 rounded">
                        <p class="text-sm text-yellow-800">
                            <strong>💡 Tip:</strong> El script intentará obtener el App ID automáticamente del token. 
                            Si falla, necesitarás proporcionar el App ID y App Secret manualmente.
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            App ID (Opcional - se intentará obtener automáticamente del token):
                        </label>
                        <input type="text" name="app_id" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="Tu App ID de Meta (opcional)">
                        <p class="text-xs text-gray-500 mt-1">
                            Encuéntralo en: Meta Business Manager → Configuración → Recursos empresariales → Tu App → Configuración básica
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            App Secret (Opcional - requerido si el método automático falla):
                        </label>
                        <input type="password" name="app_secret" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="Tu App Secret de Meta (opcional)">
                        <p class="text-xs text-gray-500 mt-1">
                            Encuéntralo en: Meta Business Manager → Configuración → Recursos empresariales → Tu App → Configuración básica → Mostrar
                        </p>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 font-medium">
                        Generar Token de Larga Duración
                    </button>
                </form>
                
            <?php else: ?>
                <!-- Procesar token -->
                <?php
                $longLivedToken = '';
                $error = '';
                $tokenInfo = null;
                
                try {
                    // Primero, intentar obtener el App ID del token actual
                    $debugUrl = "https://graph.facebook.com/v18.0/debug_token?input_token=" . urlencode($shortLivedToken) . "&access_token=" . urlencode($shortLivedToken);
                    
                    $chDebug = curl_init();
                    curl_setopt($chDebug, CURLOPT_URL, $debugUrl);
                    curl_setopt($chDebug, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chDebug, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($chDebug, CURLOPT_TIMEOUT, 10);
                    
                    $debugResponse = curl_exec($chDebug);
                    $debugHttpCode = curl_getinfo($chDebug, CURLINFO_HTTP_CODE);
                    curl_close($chDebug);
                    
                    $appIdFromToken = null;
                    if ($debugHttpCode >= 200 && $debugHttpCode < 300) {
                        $debugData = json_decode($debugResponse, true);
                        if (isset($debugData['data']['app_id'])) {
                            $appIdFromToken = $debugData['data']['app_id'];
                        }
                    }
                    
                    // Intentar generar token de larga duración
                    $appId = $_POST['app_id'] ?? $appIdFromToken ?? $appId;
                    $appSecret = $_POST['app_secret'] ?? $appSecret;
                    
                    // Método 1: Si tenemos App ID y App Secret, usarlos
                    if (!empty($appId) && !empty($appSecret) && $appId !== 'TU_APP_ID' && $appSecret !== 'TU_APP_SECRET') {
                        $url = "https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$appId}&client_secret={$appSecret}&fb_exchange_token={$shortLivedToken}";
                    } 
                    // Método 2: Si solo tenemos App ID del token, intentar sin secret (puede funcionar)
                    elseif (!empty($appId) && $appId !== 'TU_APP_ID') {
                        $url = "https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$appId}&fb_exchange_token={$shortLivedToken}";
                    }
                    // Método 3: Intentar con el token directamente (menos común pero puede funcionar)
                    else {
                        // Para tokens de WhatsApp Business, a veces necesitamos usar el endpoint específico
                        // Primero intentar obtener el App ID del token
                        if ($appIdFromToken) {
                            $url = "https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$appIdFromToken}&fb_exchange_token={$shortLivedToken}";
                        } else {
                            throw new Exception('No se pudo obtener el App ID del token. Por favor, proporciona el App ID y App Secret manualmente.');
                        }
                    }
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curlError) {
                        throw new Exception('Error de conexión: ' . $curlError);
                    }
                    
                    $responseData = json_decode($response, true);
                    
                    if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['access_token'])) {
                        $longLivedToken = $responseData['access_token'];
                        $expiresIn = $responseData['expires_in'] ?? 'Desconocido';
                        
                        // Obtener información del token
                        $infoUrl = "https://graph.facebook.com/v18.0/debug_token?input_token={$longLivedToken}&access_token={$longLivedToken}";
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $infoUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        $infoResponse = curl_exec($ch);
                        curl_close($ch);
                        $tokenInfo = json_decode($infoResponse, true);
                    } else {
                        $errorMsg = $responseData['error']['message'] ?? 'Error desconocido';
                        throw new Exception($errorMsg);
                    }
                    
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <p class="text-red-800 font-semibold mb-2">❌ Error al generar token:</p>
                        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        <p class="text-sm text-red-600 mt-3">
                            <strong>Sugerencias:</strong>
                        </p>
                        <ul class="text-sm text-red-600 ml-4 list-disc mt-2 space-y-1">
                            <li>Verifica que el token de corta duración sea válido y no haya expirado</li>
                            <li><strong>Proporciona el App ID y App Secret:</strong> Ve a Meta Business Manager → Configuración → Recursos empresariales → Tu App → Configuración básica</li>
                            <li>El token debe tener permisos de <code>whatsapp_business_messaging</code></li>
                            <li>Intenta generar un nuevo token desde <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="underline font-medium">Graph API Explorer</a></li>
                            <li><strong>Alternativa:</strong> Si tienes problemas, puedes usar directamente un token de larga duración generado desde Meta Business Manager</li>
                        </ul>
                        
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                            <p class="text-sm font-semibold text-blue-800 mb-2">📋 Cómo obtener App ID y App Secret:</p>
                            <ol class="text-sm text-blue-700 space-y-1 ml-4 list-decimal">
                                <li>Ve a <a href="https://business.facebook.com" target="_blank" class="underline">Meta Business Manager</a></li>
                                <li>Configuración → Recursos empresariales</li>
                                <li>Selecciona tu App de WhatsApp</li>
                                <li>Ve a "Configuración básica"</li>
                                <li>El <strong>App ID</strong> está visible en la página</li>
                                <li>Para el <strong>App Secret</strong>, haz clic en "Mostrar" y copia el valor</li>
                            </ol>
                        </div>
                    </div>
                    
                    <a href="?" class="inline-block bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        Intentar de Nuevo
                    </a>
                    
                <?php else: ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-6 mb-6 rounded">
                        <p class="text-green-800 font-semibold mb-4 text-lg">✅ Token de Larga Duración Generado Exitosamente</p>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Token de Larga Duración (60 días):</label>
                            <div class="flex items-center space-x-2">
                                <textarea id="long-token" rows="4" readonly
                                          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 font-mono text-sm"><?php echo htmlspecialchars($longLivedToken); ?></textarea>
                                <button onclick="copyToken()" 
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 whitespace-nowrap">
                                    📋 Copiar
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($tokenInfo && isset($tokenInfo['data'])): ?>
                            <div class="mt-4 p-4 bg-white rounded border">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Información del Token:</p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><strong>Expira en:</strong> <?php echo $expiresIn; ?> segundos (aproximadamente <?php echo round($expiresIn / 86400); ?> días)</li>
                                    <li><strong>App ID:</strong> <?php echo htmlspecialchars($tokenInfo['data']['app_id'] ?? 'N/A'); ?></li>
                                    <li><strong>Usuario:</strong> <?php echo htmlspecialchars($tokenInfo['data']['user_id'] ?? 'N/A'); ?></li>
                                    <li><strong>Válido:</strong> <?php echo isset($tokenInfo['data']['is_valid']) && $tokenInfo['data']['is_valid'] ? '✅ Sí' : '❌ No'; ?></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-sm font-semibold text-yellow-800 mb-2">📝 Próximos Pasos:</p>
                            <ol class="text-sm text-yellow-700 space-y-2 ml-4 list-decimal">
                                <li>Copia el token de arriba</li>
                                <li>Ve a <a href="admin_whatsapp_config.php" class="underline font-medium">Configuración WhatsApp</a></li>
                                <li>Pega el token en el campo "Access Token"</li>
                                <li>Guarda la configuración</li>
                                <li>Prueba enviando un mensaje</li>
                            </ol>
                        </div>
                    </div>
                    
                    <script>
                        function copyToken() {
                            const tokenField = document.getElementById('long-token');
                            tokenField.select();
                            tokenField.setSelectionRange(0, 99999); // For mobile devices
                            document.execCommand('copy');
                            alert('Token copiado al portapapeles');
                        }
                    </script>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t">
                <h2 class="text-lg font-semibold mb-3">ℹ️ Información sobre Tokens</h2>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="p-3 bg-gray-50 rounded">
                        <p class="font-semibold text-gray-700 mb-1">Token de Corta Duración:</p>
                        <p>Dura 1-2 horas. Se genera desde Meta Business Manager o Graph API Explorer.</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded">
                        <p class="font-semibold text-gray-700 mb-1">Token de Larga Duración:</p>
                        <p>Dura 60 días. Se genera desde un token de corta duración usando este script.</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded">
                        <p class="font-semibold text-gray-700 mb-1">Token Permanente (System User):</p>
                        <p>No expira. Requiere configuración especial en Meta Business Manager con System Users.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</body>
</html>

