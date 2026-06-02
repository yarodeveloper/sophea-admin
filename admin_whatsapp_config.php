<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Configuración WhatsApp - Panel de Administración - SOPHEA';

// Cargar configuración de base de datos para limpiar intentos fallidos
require_once 'config_db.php';
require_once 'classes/Database.php';

// Función para obtener IP del cliente
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Función para limpiar intentos fallidos
function clearFailedLoginAttempts() {
    try {
        $db = Database::getInstance()->getConnection();
        $ip = getClientIP();
        
        $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ip' => $ip]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error limpiando intentos fallidos: " . $e->getMessage());
        return false;
    }
}

// Manejar limpieza de intentos fallidos (para desbloquear)
if (isset($_GET['clear_attempts'])) {
    clearFailedLoginAttempts();
    header('Location: admin_whatsapp_config.php?unlocked=1');
    exit;
}

// Manejar ping para mantener sesión activa
if (isset($_GET['ping']) && $_SERVER['REQUEST_METHOD'] === 'HEAD') {
    // Session is already checked by requireAdminAuth
    http_response_code(200);
    exit;
}

// Load WhatsApp configuration (las otras ya están cargadas arriba)
require_once 'config_whatsapp.php';

// Handle form submission
$save_success = false;
$save_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    try {
        // Cargar configuración actual primero
        require_once 'config_whatsapp.php';
        
        // Get form data
        $phone_number_id = trim($_POST['phone_number_id'] ?? '');
        $business_account_id = trim($_POST['business_account_id'] ?? '');
        $access_token = trim($_POST['access_token'] ?? '');
        $api_version = trim($_POST['api_version'] ?? 'v18.0');
        $webhook_verify_token = trim($_POST['webhook_verify_token'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $api_enabled = isset($_POST['api_enabled']) ? 'true' : 'false';
        $log_messages = isset($_POST['log_messages']) ? 'true' : 'false';
        
        // Si el access_token está vacío (campo password no modificado), mantener el valor actual
        if (empty($access_token) && defined('WHATSAPP_ACCESS_TOKEN')) {
            $access_token = WHATSAPP_ACCESS_TOKEN;
        }
        
        // Validation
        if (empty($phone_number_id)) {
            throw new Exception('El Phone Number ID es requerido');
        }
        
        if (empty($business_account_id)) {
            throw new Exception('El Business Account ID es requerido');
        }
        
        if (empty($access_token)) {
            throw new Exception('El Access Token es requerido');
        }
        
        if (empty($api_version)) {
            $api_version = 'v18.0';
        }
        
        // Read current config file
        $configFile = __DIR__ . '/config_whatsapp.php';
        
        // Verificar que el archivo existe y es escribible
        if (!file_exists($configFile)) {
            throw new Exception('El archivo de configuración no existe: ' . $configFile);
        }
        
        if (!is_writable($configFile)) {
            throw new Exception('El archivo de configuración no tiene permisos de escritura. Verifica los permisos del archivo.');
        }
        
        $configContent = file_get_contents($configFile);
        
        if ($configContent === false) {
            throw new Exception('No se pudo leer el archivo de configuración.');
        }
        
        // Escapar valores para evitar problemas con comillas
        $phone_number_id_escaped = addslashes($phone_number_id);
        $business_account_id_escaped = addslashes($business_account_id);
        $access_token_escaped = addslashes($access_token);
        $api_version_escaped = addslashes($api_version);
        $webhook_verify_token_escaped = addslashes($webhook_verify_token);
        $webhook_url_escaped = addslashes($webhook_url);
        
        // Dividir el contenido en líneas para procesar más fácilmente
        $lines = explode("\n", $configContent);
        $newLines = [];
        $replacements_made = 0;
        
        foreach ($lines as $line) {
            $originalLine = $line;
            
            // Phone Number ID
            if (preg_match("/define\s*\(\s*['\"]WHATSAPP_PHONE_NUMBER_ID['\"]/", $line)) {
                $line = "define('WHATSAPP_PHONE_NUMBER_ID', '{$phone_number_id_escaped}');";
                $replacements_made++;
            }
            // Business Account ID
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_BUSINESS_ACCOUNT_ID['\"]/", $line)) {
                $line = "define('WHATSAPP_BUSINESS_ACCOUNT_ID', '{$business_account_id_escaped}');";
                $replacements_made++;
            }
            // Access Token - usar patrón más flexible para tokens largos
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_ACCESS_TOKEN['\"]/", $line)) {
                $line = "define('WHATSAPP_ACCESS_TOKEN', '{$access_token_escaped}');";
                $replacements_made++;
            }
            // API Version
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_API_VERSION['\"]/", $line)) {
                $line = "define('WHATSAPP_API_VERSION', '{$api_version_escaped}');";
                $replacements_made++;
            }
            // Webhook Verify Token
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_WEBHOOK_VERIFY_TOKEN['\"]/", $line)) {
                $line = "define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', '{$webhook_verify_token_escaped}');";
                $replacements_made++;
            }
            // Webhook URL
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_WEBHOOK_URL['\"]/", $line)) {
                $line = "define('WHATSAPP_WEBHOOK_URL', '{$webhook_url_escaped}');";
                $replacements_made++;
            }
            // API Enabled - boolean
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_API_ENABLED['\"]/", $line)) {
                $line = "define('WHATSAPP_API_ENABLED', {$api_enabled});";
                $replacements_made++;
            }
            // Log Messages - boolean
            elseif (preg_match("/define\s*\(\s*['\"]WHATSAPP_LOG_MESSAGES['\"]/", $line)) {
                $line = "define('WHATSAPP_LOG_MESSAGES', {$log_messages});";
                $replacements_made++;
            }
            
            $newLines[] = $line;
        }
        
        // Reconstruir el contenido
        $configContent = implode("\n", $newLines);
        
        // Verificar que se hicieron los reemplazos
        if ($replacements_made === 0) {
            throw new Exception('No se pudieron actualizar los valores. Verifica el formato del archivo de configuración. Reemplazos realizados: 0');
        }
        
        // Log para debugging
        error_log("WhatsApp Config: Se realizaron {$replacements_made} reemplazos");
        
        // Crear backup antes de escribir
        $backupFile = $configFile . '.backup.' . date('Y-m-d_H-i-s');
        if (!copy($configFile, $backupFile)) {
            // No es crítico, continuar
            error_log("No se pudo crear backup de config_whatsapp.php");
        }
        
        // Write updated config
        $bytes_written = file_put_contents($configFile, $configContent, LOCK_EX);
        
        if ($bytes_written === false) {
            throw new Exception('Error al escribir el archivo de configuración. Verifica permisos del archivo.');
        }
        
        // Verificar que el archivo se escribió correctamente
        clearstatcache();
        if (filesize($configFile) === 0) {
            // Restaurar backup si existe
            if (file_exists($backupFile)) {
                copy($backupFile, $configFile);
            }
            throw new Exception('El archivo se escribió vacío. Se restauró el backup.');
        }
        
        $save_success = true;
        
        // Get redirect URL if provided
        $redirectTo = $_POST['redirect_to'] ?? 'admin_whatsapp_config.php';
        
        // Reload config para verificar que se cargó correctamente
        try {
            // Limpiar cache de opcache si está habilitado
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($configFile, true);
            }
            require_once $configFile;
        } catch (Exception $e) {
            // Si hay error al cargar, restaurar backup
            if (file_exists($backupFile)) {
                copy($backupFile, $configFile);
                throw new Exception('Error al cargar la configuración actualizada. Se restauró el backup. Error: ' . $e->getMessage());
            }
            throw new Exception('Error al cargar la configuración actualizada: ' . $e->getMessage());
        }
        
        // Redirect after successful save
        $redirectTo = $_POST['redirect_to'] ?? 'admin_whatsapp_config.php';
        $separator = strpos($redirectTo, '?') !== false ? '&' : '?';
        header('Location: ' . $redirectTo . $separator . 'save_success=1');
        exit;
        
    } catch (Exception $e) {
        $save_error = $e->getMessage();
        error_log("Error guardando configuración WhatsApp: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Agregar información adicional de debugging
        if (isset($configFile) && file_exists($configFile)) {
            $save_error .= " | Archivo existe: Sí | Permisos: " . (is_writable($configFile) ? 'Escritura OK' : 'Sin escritura');
        }
        
        // Redirect with error if redirect_to is provided
        $redirectTo = $_POST['redirect_to'] ?? null;
        if ($redirectTo) {
            $separator = strpos($redirectTo, '?') !== false ? '&' : '?';
            header('Location: ' . $redirectTo . $separator . 'save_error=' . urlencode($save_error));
            exit;
        }
    }
}

// Reload config to get current values
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

// Check token validity (only if method exists)
$tokenStatus = null;
try {
    $whatsappAPI = new WhatsAppAPI();
    // Check if method exists (for backward compatibility)
    if (method_exists($whatsappAPI, 'checkTokenValidity')) {
        $tokenStatus = $whatsappAPI->checkTokenValidity();
    } else {
        // Fallback: basic token check
        $tokenStatus = [
            'valid' => !empty(WHATSAPP_ACCESS_TOKEN),
            'error' => 'Método de verificación no disponible. Actualiza classes/WhatsAppAPI.php'
        ];
    }
} catch (Exception $e) {
    $tokenStatus = ['valid' => false, 'error' => $e->getMessage()];
} catch (Error $e) {
    // Handle fatal errors (like method not found)
    $tokenStatus = ['valid' => false, 'error' => 'Error al verificar token: ' . $e->getMessage()];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración WhatsApp - SOPHEA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="ph-fill ph-whatsapp-logo text-3xl text-green-600"></i>
                <h1 class="text-2xl font-bold text-gray-800">Configuración WhatsApp Business API</h1>
            </div>
            <div class="flex items-center space-x-4">
                <?php 
                // Calcular tiempo restante de sesión
                $sessionTimeout = 1800; // 30 minutos
                $timeRemaining = 0;
                if (isset($_SESSION['last_activity'])) {
                    $timeRemaining = $sessionTimeout - (time() - $_SESSION['last_activity']);
                    $minutesRemaining = max(0, floor($timeRemaining / 60));
                }
                ?>
                <span class="text-sm text-gray-600 session-time">
                    <i class="ph ph-clock"></i> Sesión: <?php echo $minutesRemaining; ?> min
                </span>
                <a href="admin.php" class="text-purple-600 hover:text-purple-700 font-medium">
                    <i class="ph ph-arrow-left"></i> Volver al Admin
                </a>
                <a href="?logout=1" class="text-red-600 hover:text-red-700 font-medium">
                    <i class="ph ph-sign-out"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($save_success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="ph-fill ph-check-circle text-green-500 text-2xl mr-3"></i>
                    <div>
                        <p class="text-green-800 font-semibold">Configuración guardada exitosamente</p>
                        <p class="text-green-600 text-sm">Los cambios se han aplicado correctamente</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($save_error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="ph-fill ph-warning-circle text-red-500 text-2xl mr-3"></i>
                    <div>
                        <p class="text-red-800 font-semibold">Error al guardar</p>
                        <p class="text-red-600 text-sm"><?php echo htmlspecialchars($save_error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Token Status Alert -->
        <?php if ($tokenStatus): ?>
            <?php if (isset($tokenStatus['expired']) && $tokenStatus['expired']): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="ph-fill ph-warning-circle text-red-500 text-2xl mr-3"></i>
                        <div class="flex-1">
                            <p class="text-red-800 font-semibold">⚠️ Token Expirado</p>
                            <p class="text-red-600 text-sm mb-2">Tu token de acceso ha expirado. Necesitas generar uno nuevo.</p>
                            <a href="generate_long_lived_token.php" target="_blank" class="text-red-700 underline text-sm font-medium">
                                Generar Token de Larga Duración (60 días) →
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($tokenStatus['expires_soon']) && $tokenStatus['expires_soon']): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="ph-fill ph-warning text-yellow-500 text-2xl mr-3"></i>
                        <div class="flex-1">
                            <p class="text-yellow-800 font-semibold">⚠️ Token por Expirar</p>
                            <p class="text-yellow-600 text-sm mb-2">
                                Tu token expirará en aproximadamente <?php echo $tokenStatus['days_until_expiry']; ?> días.
                            </p>
                            <a href="generate_long_lived_token.php" target="_blank" class="text-yellow-700 underline text-sm font-medium">
                                Generar Nuevo Token de Larga Duración →
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($tokenStatus['valid']) && $tokenStatus['valid']): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="ph-fill ph-check-circle text-green-500 text-2xl mr-3"></i>
                        <div>
                            <p class="text-green-800 font-semibold">✅ Token Válido</p>
                            <p class="text-green-600 text-sm">
                                <?php if (isset($tokenStatus['days_until_expiry'])): ?>
                                    Expira en aproximadamente <?php echo $tokenStatus['days_until_expiry']; ?> días
                                <?php else: ?>
                                    Token activo y funcionando
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="ph-fill ph-info text-gray-500 text-2xl mr-3"></i>
                        <div>
                            <p class="text-gray-800 font-semibold">Token no verificado</p>
                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($tokenStatus['error'] ?? 'No se pudo verificar el estado del token'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Configuration Form -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Parámetros de WhatsApp Business API</h2>
                <p class="text-sm text-gray-600 mt-1">Configura los parámetros necesarios para conectar con la API de WhatsApp Business</p>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <!-- API Status -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center space-x-2">
                        <i class="ph-fill ph-toggle-left text-purple-600"></i>
                        <span>Estado de la API</span>
                    </h3>
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="api_enabled" name="api_enabled" 
                               <?php echo WHATSAPP_API_ENABLED ? 'checked' : ''; ?>
                               class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                        <label for="api_enabled" class="text-gray-700 font-medium">
                            Habilitar WhatsApp Business API
                        </label>
                    </div>
                    <p class="text-sm text-gray-500 mt-2 ml-8">
                        Desactiva esta opción para deshabilitar temporalmente el envío de mensajes
                    </p>
                </div>

                <!-- Basic Configuration -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center space-x-2">
                        <i class="ph-fill ph-gear text-blue-600"></i>
                        <span>Configuración Básica</span>
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone_number_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number ID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="phone_number_id" name="phone_number_id" required
                                   value="<?php echo htmlspecialchars(WHATSAPP_PHONE_NUMBER_ID); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">ID del número de teléfono de WhatsApp Business</p>
                        </div>

                        <div>
                            <label for="business_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Account ID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="business_account_id" name="business_account_id" required
                                   value="<?php echo htmlspecialchars(WHATSAPP_BUSINESS_ACCOUNT_ID); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">ID de la cuenta de WhatsApp Business</p>
                        </div>

                        <div>
                            <label for="api_version" class="block text-sm font-medium text-gray-700 mb-2">
                                API Version
                            </label>
                            <select id="api_version" name="api_version"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="v18.0" <?php echo WHATSAPP_API_VERSION === 'v18.0' ? 'selected' : ''; ?>>v18.0</option>
                                <option value="v19.0" <?php echo WHATSAPP_API_VERSION === 'v19.0' ? 'selected' : ''; ?>>v19.0</option>
                                <option value="v20.0" <?php echo WHATSAPP_API_VERSION === 'v20.0' ? 'selected' : ''; ?>>v20.0</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Versión de la API de Meta</p>
                        </div>
                    </div>
                </div>

                <!-- Access Token -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center space-x-2">
                        <i class="ph-fill ph-key text-yellow-600"></i>
                        <span>Autenticación</span>
                    </h3>
                    
                    <div>
                        <label for="access_token" class="block text-sm font-medium text-gray-700 mb-2">
                            Access Token <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="access_token" name="access_token" required
                                   value="<?php echo htmlspecialchars(WHATSAPP_ACCESS_TOKEN); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 font-mono text-sm">
                        </div>
                        <p class="text-xs text-yellow-600 mt-1">
                            <i class="ph ph-warning"></i> Este token es sensible. Mantén este archivo seguro.
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Token de acceso obtenido de Meta Business Manager. 
                            <a href="https://business.facebook.com" target="_blank" class="text-purple-600 hover:underline">Obtener token</a>
                        </p>
                    </div>
                </div>

                <!-- Webhook Configuration -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center space-x-2">
                        <i class="ph-fill ph-webhooks-logo text-indigo-600"></i>
                        <span>Configuración de Webhook</span>
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-2">
                                URL de Callback (Webhook URL)
                            </label>
                            <input type="url" id="webhook_url" name="webhook_url"
                                   value="<?php echo htmlspecialchars(WHATSAPP_WEBHOOK_URL); ?>"
                                   placeholder="https://tudominio.com/webhook_whatsapp.php"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">
                                URL pública donde Meta enviará los eventos. Debe ser HTTPS en producción.
                            </p>
                        </div>

                        <div>
                            <label for="webhook_verify_token" class="block text-sm font-medium text-gray-700 mb-2">
                                Token de Verificación (Verify Token)
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="webhook_verify_token" name="webhook_verify_token"
                                       value="<?php echo htmlspecialchars(WHATSAPP_WEBHOOK_VERIFY_TOKEN); ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <button type="button" onclick="generateToken()" 
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                                    <i class="ph ph-shuffle"></i> Generar
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Token que debe coincidir con el configurado en Meta Business Manager
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="pb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center space-x-2">
                        <i class="ph-fill ph-sliders text-gray-600"></i>
                        <span>Configuración Avanzada</span>
                    </h3>
                    
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="log_messages" name="log_messages" 
                               <?php echo WHATSAPP_LOG_MESSAGES ? 'checked' : ''; ?>
                               class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                        <label for="log_messages" class="text-gray-700 font-medium">
                            Registrar mensajes en logs
                        </label>
                    </div>
                    <p class="text-sm text-gray-500 mt-2 ml-8">
                        Activa el logging detallado de todos los mensajes enviados y recibidos
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                    <a href="admin.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" name="save_config" 
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2">
                        <i class="ph-fill ph-floppy-disk"></i>
                        <span>Guardar Configuración</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Information Card -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
            <h3 class="font-semibold text-blue-800 mb-2 flex items-center space-x-2">
                <i class="ph-fill ph-info text-xl"></i>
                <span>Información Importante</span>
            </h3>
            <ul class="text-sm text-blue-700 space-y-2 ml-8 list-disc">
                <li>El Access Token puede expirar. Verifica periódicamente que siga siendo válido.</li>
                <li>El Webhook URL debe ser accesible públicamente y usar HTTPS en producción.</li>
                <li>El Token de Verificación debe ser el mismo en el código y en Meta Business Manager.</li>
                <li>Después de cambiar la configuración, verifica el webhook en Meta Business Manager.</li>
            </ul>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 grid md:grid-cols-3 gap-4">
            <a href="https://business.facebook.com" target="_blank"
               class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <i class="ph-fill ph-globe text-blue-600 text-2xl"></i>
                    <div>
                        <p class="font-semibold text-gray-800">Meta Business Manager</p>
                        <p class="text-xs text-gray-500">Gestionar configuración</p>
                    </div>
                </div>
            </a>
            
            <a href="webhook_whatsapp.php" target="_blank"
               class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <i class="ph-fill ph-webhooks-logo text-indigo-600 text-2xl"></i>
                    <div>
                        <p class="font-semibold text-gray-800">Probar Webhook</p>
                        <p class="text-xs text-gray-500">Verificar endpoint</p>
                    </div>
                </div>
            </a>
            
            <a href="WHATSAPP_WEBHOOK_SETUP.md" target="_blank"
               class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <i class="ph-fill ph-book text-purple-600 text-2xl"></i>
                    <div>
                        <p class="font-semibold text-gray-800">Documentación</p>
                        <p class="text-xs text-gray-500">Guía de configuración</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ph-eye');
                icon.classList.add('ph-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('ph-eye-slash');
                icon.classList.add('ph-eye');
            }
        }

        function generateToken() {
            // Generate a random secure token
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            for (let i = 0; i < 32; i++) {
                token += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            document.getElementById('webhook_verify_token').value = token;
        }

        // Mantener sesión activa y actualizar tiempo restante
        let sessionTimeRemaining = <?php echo isset($timeRemaining) ? $timeRemaining : 1800; ?>;
        
        function updateSessionTime() {
            if (sessionTimeRemaining > 0) {
                sessionTimeRemaining--;
                const minutes = Math.floor(sessionTimeRemaining / 60);
                const sessionElement = document.querySelector('.session-time');
                if (sessionElement) {
                    sessionElement.textContent = `Sesión: ${minutes} min`;
                }
            } else {
                // Sesión expirada, redirigir al login
                alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
                window.location.href = 'admin_whatsapp_config.php';
            }
        }

        // Actualizar cada minuto
        setInterval(updateSessionTime, 60000);
        
        // Mantener sesión activa con actividad del usuario
        let activityTimer;
        document.addEventListener('mousemove', function() {
            clearTimeout(activityTimer);
            activityTimer = setTimeout(function() {
                // Ping al servidor para mantener sesión activa (cada 5 minutos)
                fetch('admin_whatsapp_config.php?ping=1', {method: 'HEAD', cache: 'no-cache'})
                    .catch(() => {}); // Ignorar errores silenciosamente
            }, 300000); // 5 minutos
        });
    </script>
</body>
</html>
