<?php
/**
 * WhatsApp Config Tab Content for Admin Tools Panel
 * 
 * This file contains the WhatsApp configuration interface
 * Note: Form processing is handled by admin_whatsapp_config.php for compatibility
 */

// Load WhatsApp configuration
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

// Messages are handled in admin_tools.php
?>

<!-- Token Status Alert -->
<?php if ($tokenStatus): ?>
    <?php if (isset($tokenStatus['expired']) && $tokenStatus['expired']): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
            <div class="flex items-center">
                <span class="material-symbols-outlined text-2xl mr-3">warning</span>
                <div class="flex-1">
                    <p class="font-semibold">⚠️ Token Expirado</p>
                    <p class="text-sm mb-2">Tu token de acceso ha expirado. Necesitas generar uno nuevo.</p>
                    <a href="generate_long_lived_token.php" target="_blank" class="text-sm underline font-medium">
                        Generar Token de Larga Duración (60 días) →
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (isset($tokenStatus['expires_soon']) && $tokenStatus['expires_soon']): ?>
        <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300">
            <div class="flex items-center">
                <span class="material-symbols-outlined text-2xl mr-3">warning</span>
                <div class="flex-1">
                    <p class="font-semibold">⚠️ Token por Expirar</p>
                    <p class="text-sm mb-2">
                        Tu token expirará en aproximadamente <?php echo $tokenStatus['days_until_expiry']; ?> días.
                    </p>
                    <a href="generate_long_lived_token.php" target="_blank" class="text-sm underline font-medium">
                        Generar Nuevo Token de Larga Duración →
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (isset($tokenStatus['valid']) && $tokenStatus['valid']): ?>
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300">
            <div class="flex items-center">
                <span class="material-symbols-outlined text-2xl mr-3">check_circle</span>
                <div>
                    <p class="font-semibold">✅ Token Válido</p>
                    <p class="text-sm">
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
        <div class="mb-6 p-4 rounded-lg bg-slate-50 dark:bg-slate-900/20 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-300">
            <div class="flex items-center">
                <span class="material-symbols-outlined text-2xl mr-3">info</span>
                <div>
                    <p class="font-semibold">Token no verificado</p>
                    <p class="text-sm"><?php echo htmlspecialchars($tokenStatus['error'] ?? 'No se pudo verificar el estado del token'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Configuration Form -->
<div class="space-y-6">
    <!-- Form redirects to admin_whatsapp_config.php for processing, then back to admin_tools.php -->
    <form method="POST" action="admin_whatsapp_config.php" class="space-y-6">
        <input type="hidden" name="redirect_to" value="admin_tools.php?tab=whatsapp_config">
        
        <!-- API Status -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">toggle_on</span>
                <span>Estado de la API</span>
            </h3>
            <div class="flex items-center gap-3">
                <input type="checkbox" id="api_enabled" name="api_enabled" 
                       <?php echo WHATSAPP_API_ENABLED ? 'checked' : ''; ?>
                       class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary">
                <label for="api_enabled" class="text-slate-700 dark:text-slate-300 font-medium">
                    Habilitar WhatsApp Business API
                </label>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 ml-8">
                Desactiva esta opción para deshabilitar temporalmente el envío de mensajes
            </p>
        </div>

        <!-- Basic Configuration -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">settings</span>
                <span>Configuración Básica</span>
            </h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="phone_number_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Phone Number ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="phone_number_id" name="phone_number_id" required
                           value="<?php echo htmlspecialchars(WHATSAPP_PHONE_NUMBER_ID); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">ID del número de teléfono de WhatsApp Business</p>
                </div>

                <div>
                    <label for="business_account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Business Account ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="business_account_id" name="business_account_id" required
                           value="<?php echo htmlspecialchars(WHATSAPP_BUSINESS_ACCOUNT_ID); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">ID de la cuenta de WhatsApp Business</p>
                </div>

                <div>
                    <label for="api_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        API Version
                    </label>
                    <select id="api_version" name="api_version"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="v18.0" <?php echo WHATSAPP_API_VERSION === 'v18.0' ? 'selected' : ''; ?>>v18.0</option>
                        <option value="v19.0" <?php echo WHATSAPP_API_VERSION === 'v19.0' ? 'selected' : ''; ?>>v19.0</option>
                        <option value="v20.0" <?php echo WHATSAPP_API_VERSION === 'v20.0' ? 'selected' : ''; ?>>v20.0</option>
                    </select>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Versión de la API de Meta</p>
                </div>
            </div>
        </div>

        <!-- Access Token -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">key</span>
                <span>Autenticación</span>
            </h3>
            
            <div>
                <label for="access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Access Token <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" id="access_token" name="access_token" required
                           value="<?php echo htmlspecialchars(WHATSAPP_ACCESS_TOKEN); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm">
                </div>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    <span class="material-symbols-outlined text-sm align-middle">warning</span>
                    Este token es sensible. Mantén este archivo seguro.
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Token de acceso obtenido de Meta Business Manager. 
                    <a href="https://business.facebook.com" target="_blank" class="text-primary hover:underline">Obtener token</a>
                </p>
            </div>
        </div>

        <!-- Webhook Configuration -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">webhook</span>
                <span>Configuración de Webhook</span>
            </h3>
            
            <div class="space-y-4">
                <div>
                    <label for="webhook_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        URL de Callback (Webhook URL)
                    </label>
                    <input type="url" id="webhook_url" name="webhook_url"
                           value="<?php echo htmlspecialchars(WHATSAPP_WEBHOOK_URL); ?>"
                           placeholder="https://tudominio.com/webhook_whatsapp.php"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        URL pública donde Meta enviará los eventos. Debe ser HTTPS en producción.
                    </p>
                </div>

                <div>
                    <label for="webhook_verify_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Token de Verificación (Verify Token)
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="webhook_verify_token" name="webhook_verify_token"
                               value="<?php echo htmlspecialchars(WHATSAPP_WEBHOOK_VERIFY_TOKEN); ?>"
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <button type="button" onclick="generateToken()" 
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-medium">
                            <span class="material-symbols-outlined text-sm align-middle">shuffle</span>
                            Generar
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Token que debe coincidir con el configurado en Meta Business Manager
                    </p>
                </div>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div class="pb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                <span class="material-symbols-outlined text-slate-600 dark:text-slate-400">tune</span>
                <span>Configuración Avanzada</span>
            </h3>
            
            <div class="flex items-center gap-3">
                <input type="checkbox" id="log_messages" name="log_messages" 
                       <?php echo WHATSAPP_LOG_MESSAGES ? 'checked' : ''; ?>
                       class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary">
                <label for="log_messages" class="text-slate-700 dark:text-slate-300 font-medium">
                    Registrar mensajes en logs
                </label>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 ml-8">
                Activa el logging detallado de todos los mensajes enviados y recibidos
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
            <button type="submit" name="save_config" 
                    class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                <span class="material-symbols-outlined text-[20px]">save</span>
                <span>Guardar Configuración</span>
            </button>
        </div>
    </form>
</div>

<!-- Information Card -->
<div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 p-6 rounded-lg">
    <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
        <span class="material-symbols-outlined">info</span>
        <span>Información Importante</span>
    </h3>
    <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2 ml-8 list-disc">
        <li>El Access Token puede expirar. Verifica periódicamente que siga siendo válido.</li>
        <li>El Webhook URL debe ser accesible públicamente y usar HTTPS en producción.</li>
        <li>El Token de Verificación debe ser el mismo en el código y en Meta Business Manager.</li>
        <li>Después de cambiar la configuración, verifica el webhook en Meta Business Manager.</li>
    </ul>
</div>

<!-- Quick Links -->
<div class="mt-6 grid md:grid-cols-3 gap-4">
    <a href="https://business.facebook.com" target="_blank"
       class="bg-white dark:bg-card-dark p-4 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">language</span>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Meta Business Manager</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Gestionar configuración</p>
            </div>
        </div>
    </a>
    
    <a href="webhook_whatsapp.php" target="_blank"
       class="bg-white dark:bg-card-dark p-4 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-2xl">webhook</span>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Probar Webhook</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Verificar endpoint</p>
            </div>
        </div>
    </a>
    
    <a href="WHATSAPP_WEBHOOK_SETUP.md" target="_blank"
       class="bg-white dark:bg-card-dark p-4 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl">menu_book</span>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Documentación</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Guía de configuración</p>
            </div>
        </div>
    </a>
</div>

<script>
    function generateToken() {
        // Generate a random secure token
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let token = '';
        for (let i = 0; i < 32; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        document.getElementById('webhook_verify_token').value = token;
    }
</script>

