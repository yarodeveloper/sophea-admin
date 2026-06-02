<?php
/**
 * SOPHEA - Cookie Banner Component
 * 
 * Banner de cookies que se muestra en todas las páginas
 */

// Check if user has already accepted/rejected cookies
$cookieConsent = isset($_COOKIE['cookie_consent']) ? $_COOKIE['cookie_consent'] : null;
$showBanner = $cookieConsent === null;
?>

<!-- Cookie Banner - Compacto y No Invasivo -->
<div id="cookie-banner" class="<?php echo $showBanner ? '' : 'hidden'; ?> fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 transform transition-transform duration-300">
    <div class="container mx-auto px-4 py-2.5 max-w-6xl">
        <div class="flex items-center justify-between gap-3">
            <!-- Texto Compacto -->
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <i class="ph-fill ph-cookie text-xl text-purple-600 flex-shrink-0"></i>
                <p class="text-xs text-gray-600 leading-tight">
                    Usamos cookies para mejorar tu experiencia. 
                    <a href="politica_cookies.php" class="text-purple-600 hover:text-purple-700 underline">Más info</a>
                </p>
            </div>
            
            <!-- Botones Compactos -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button id="cookie-settings" 
                        class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 transition-colors whitespace-nowrap">
                    Configurar
                </button>
                <button id="cookie-accept-all" 
                        class="px-4 py-1.5 bg-purple-600 text-white text-xs rounded-md hover:bg-purple-700 transition-colors font-medium whitespace-nowrap">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cookie Settings Modal -->
<div id="cookie-settings-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-5">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center space-x-2">
                    <i class="ph-fill ph-sliders text-purple-600"></i>
                    <span>Configuración de Cookies</span>
                </h2>
                <button id="cookie-settings-close" class="text-gray-400 hover:text-gray-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Selecciona qué tipos de cookies deseas aceptar. Las cookies necesarias no se pueden desactivar.
                </p>
                
                <!-- Necesarias -->
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-sm">Cookies Necesarias</h3>
                            <p class="text-xs text-gray-600 mt-0.5">Siempre activas - Requeridas para el funcionamiento</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium ml-3">Siempre activo</span>
                    </div>
                </div>
                
                <!-- Análisis -->
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-sm">Cookies de Análisis</h3>
                            <p class="text-xs text-gray-600 mt-0.5">Nos ayudan a entender cómo usas nuestro sitio</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-3">
                            <input type="checkbox" id="cookie-analytics" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
                
                <!-- Marketing -->
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-sm">Cookies de Marketing</h3>
                            <p class="text-xs text-gray-600 mt-0.5">Para mostrar anuncios relevantes</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-3">
                            <input type="checkbox" id="cookie-marketing" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-4 flex flex-col sm:flex-row gap-2 justify-end pt-4 border-t">
                <button id="cookie-reject-all" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                    Rechazar Todas
                </button>
                <button id="cookie-save-settings" 
                        class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Cookie Banner Management
(function() {
    // Check if banner should be shown
    const cookieConsent = getCookie('cookie_consent');
    const banner = document.getElementById('cookie-banner');
    const settingsModal = document.getElementById('cookie-settings-modal');
    
    // Show banner if no consent has been given
    if (!cookieConsent && banner) {
        banner.classList.remove('hidden');
    }
    
    // Accept All
    const acceptAllBtn = document.getElementById('cookie-accept-all');
    if (acceptAllBtn) {
        acceptAllBtn.addEventListener('click', function() {
            setCookie('cookie_consent', 'all', 365);
            setCookie('cookie_analytics', 'true', 365);
            setCookie('cookie_marketing', 'true', 365);
            // Activate Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted',
                    'ad_storage': 'granted'
                });
            }
            hideBanner();
        });
    }
    
    // Reject All (handled in settings modal)
    
    // Open Settings
    const settingsBtn = document.getElementById('cookie-settings');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', function() {
            if (settingsModal) {
                settingsModal.classList.remove('hidden');
                // Load current preferences
                const analytics = getCookie('cookie_analytics') === 'true';
                const marketing = getCookie('cookie_marketing') === 'true';
                const analyticsCheckbox = document.getElementById('cookie-analytics');
                const marketingCheckbox = document.getElementById('cookie-marketing');
                if (analyticsCheckbox) analyticsCheckbox.checked = analytics;
                if (marketingCheckbox) marketingCheckbox.checked = marketing;
            }
        });
    }
    
    // Close Settings
    const closeSettingsBtn = document.getElementById('cookie-settings-close');
    if (closeSettingsBtn) {
        closeSettingsBtn.addEventListener('click', function() {
            if (settingsModal) {
                settingsModal.classList.add('hidden');
            }
        });
    }
    
    // Reject All (in settings modal)
    const rejectAllBtn = document.getElementById('cookie-reject-all');
    if (rejectAllBtn) {
        rejectAllBtn.addEventListener('click', function() {
            setCookie('cookie_consent', 'necessary', 365);
            setCookie('cookie_analytics', 'false', 365);
            setCookie('cookie_marketing', 'false', 365);
            // Deny Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied'
                });
            }
            if (settingsModal) {
                settingsModal.classList.add('hidden');
            }
            hideBanner();
        });
    }
    
    // Save Settings
    const saveSettingsBtn = document.getElementById('cookie-save-settings');
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', function() {
            const analytics = document.getElementById('cookie-analytics')?.checked || false;
            const marketing = document.getElementById('cookie-marketing')?.checked || false;
            
            setCookie('cookie_consent', 'custom', 365);
            setCookie('cookie_analytics', analytics ? 'true' : 'false', 365);
            setCookie('cookie_marketing', marketing ? 'true' : 'false', 365);
            
            // Update Google Analytics consent
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': analytics ? 'granted' : 'denied',
                    'ad_storage': marketing ? 'granted' : 'denied'
                });
            }
            
            if (settingsModal) {
                settingsModal.classList.add('hidden');
            }
            hideBanner();
        });
    }
    
    // Close modal when clicking outside
    if (settingsModal) {
        settingsModal.addEventListener('click', function(e) {
            if (e.target === settingsModal) {
                settingsModal.classList.add('hidden');
            }
        });
    }
    
    function hideBanner() {
        if (banner) {
            banner.classList.add('hidden');
        }
    }
    
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Initialize Google Analytics consent based on saved preferences
    if (typeof gtag !== 'undefined') {
        const analyticsConsent = getCookie('cookie_analytics') === 'true' || getCookie('cookie_consent') === 'all';
        const marketingConsent = getCookie('cookie_marketing') === 'true' || getCookie('cookie_consent') === 'all';
        
        if (analyticsConsent || marketingConsent) {
            gtag('consent', 'update', {
                'analytics_storage': analyticsConsent ? 'granted' : 'denied',
                'ad_storage': marketingConsent ? 'granted' : 'denied'
            });
        }
    }
})();
</script>

