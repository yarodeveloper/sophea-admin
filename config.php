<?php
/**
 * SOPHEA - Global Configuration File
 * 
 * This file contains all global settings, constants, and configuration
 * for the SOPHEA website. Include this file at the top of every page.
 */

// Site Information
define('SITE_NAME', 'SOPHEA');
define('SITE_TAGLINE', 'Estrategia Digital, IA y Compliance Regulatorio');
define('SITE_DESCRIPTION', 'Impulsa tu crecimiento con SOPHEA. Expertos en Chat Bots con IA, eCommerce, Apps, Publicidad Digital y Blindaje COFEPRIS.');
define('SITE_DESCRIPTION_LONG', 'SOPHEA es tu aliado estratégico. Brindamos soluciones en Chat Bots + IA, Desarrollo de Apps, eCommerce, Publicidad Digital y Auditoría Legal/TI. Líderes en Compliance COFEPRIS para un crecimiento médico sin riesgos.');

// Contact Information
define('CONTACT_PHONE', '+52 961 693 3158');
define('CONTACT_WHATSAPP', '529616933158'); // Without + or spaces
define('CONTACT_WHATSAPP_CHATBOT', '525636753133'); // Chatbot WhatsApp
define('CONTACT_EMAIL', 'amontoyar108@gmail.com');
define('CONTACT_EMAIL_PUBLIC', 'contacto@sopheamkt.com'); // Email público
define('CONTACT_ADDRESS', 'Blvd. Antonio Pariente Algarín, Segundo piso, Col. 24, Tuxtla Gutiérrez, Chiapas, México');
define('CONTACT_STREET', 'Blvd. Antonio Pariente Algarín, Segundo piso');
define('CONTACT_COLONY', 'Col. 24');
define('CONTACT_CITY', 'Tuxtla Gutiérrez');
define('CONTACT_STATE', 'Chiapas');
define('CONTACT_COUNTRY', 'México');
define('CONTACT_GOOGLE_MAPS', 'https://maps.app.goo.gl/vuUDtK9m3ZwRtoyk8');

// Business Hours
define('BUSINESS_HOURS', 'Lun - Vie: 9:00 AM - 6:00 PM');

// Social Media
define('SOCIAL_FACEBOOK', 'https://www.facebook.com/sophea.marketing');
define('SOCIAL_INSTAGRAM', 'https://www.instagram.com/sophea_mkt/');
define('SOCIAL_LINKEDIN', 'https://www.linkedin.com/company/sophea-mkt/');
define('SOCIAL_TWITTER', '');
define('SOCIAL_YOUTUBE', 'https://www.youtube.com/@sophea_mk');

// SEO Settings
define('SEO_KEYWORDS', 'marketing digital salud, COFEPRIS, compliance médico, publicidad médica, marketing médico, automatización IA, chatbots IA, desarrollo de apps, ecommerce méxico, auditoría TI legal, publicidad digital google meta, Tuxtla Gutiérrez, Chiapas, crecimiento digital');
define('SEO_AUTHOR', 'SOPHEA - Alejandro Montoya');
define('SEO_TITLE', 'SOPHEA | IA, Desarrollo Web & Compliance Médico COFEPRIS');
define('SEO_DESCRIPTION_SHORT', 'Impulsa tu crecimiento con SOPHEA. Expertos en IA, eCommerce, Apps, Publicidad Digital y cumplimiento COFEPRIS.');
define('SEO_DESCRIPTION_LONG', 'SOPHEA ofrece soluciones integrales en Chat Bots + IA, eCommerce, Desarrollo de Sistemas y Publicidad Digital. Líderes en Blindaje Regulatorio COFEPRIS.');

// GEO Location
define('GEO_REGION', 'MX-CHP');
define('GEO_PLACENAME', 'Tuxtla Gutiérrez');
define('GEO_LATITUDE', '16.7516');
define('GEO_LONGITUDE', '-93.1029');

// Schema.org Data
define('SCHEMA_URL', 'https://sopheamkt.com');
define('SCHEMA_LOGO', 'https://sopheamkt.com/logo.png');
define('SCHEMA_LOGO_SQUARE', 'https://sopheamkt.com/logo-square.png');
define('SCHEMA_FAVICON', 'https://sopheamkt.com/assets/ico_sp192x192.png');
define('SCHEMA_OG_IMAGE', 'https://sopheamkt.com/images/og-image.jpg');
define('SCHEMA_POSTAL_CODE', '29045');

// Director Information
define('DIRECTOR_NAME', 'Alejandro Montoya');
define('DIRECTOR_TITLE', 'Director General & Fundador');
define('DIRECTOR_BIO', 'Especialista en compliance regulatorio y crecimiento digital con más de 8 años de experiencia ayudando a profesionales de la salud y empresas a escalar sin riesgos legales.');
define('DIRECTOR_CLIENTS', '+150 Clientes Satisfechos');
define('DIRECTOR_CERTIFICATION', 'Certificado en Compliance COFEPRIS');

// Navigation Menu Items
$nav_menu = [
    ['label' => 'Método', 'url' => 'index.php#metodo'],
    ['label' => 'Servicios', 'url' => 'servicios.php'],
    ['label' => 'Herramientas', 'url' => '#', 'sub_menu' => [
        ['label' => 'Generador de QR', 'url' => 'generador-qr.php'],
        ['label' => 'Generador de Link WhatsApp', 'url' => 'generador-link-whatsapp.php']
    ]],
    ['label' => 'Blog', 'url' => 'blog.php'],
    ['label' => 'Casos de Éxito', 'url' => 'index.php#casos'],
    ['label' => 'Contacto', 'url' => 'index.php#contacto']
];

// CTA Button Text
define('CTA_PRIMARY', 'Agendar Consultoría Gratuita');
define('CTA_SECONDARY', 'Conocer el Método');

// WhatsApp Default Message
define('WHATSAPP_DEFAULT_MESSAGE', 'Hola, me interesa una consultoría gratuita');

// Page Title Function
function get_page_title($page_name = '') {
    if (empty($page_name)) {
        return defined('SEO_TITLE') ? SEO_TITLE : (SITE_NAME . ' | Soluciones Integrales de Marketing | Marketing médico');
    }
    return $page_name . ' | ' . SITE_NAME;
}

/**
 * Normalize phone number to consistent format
 * Formats: +52 961 123 4567, 9611234567, (961) 123-4567, etc.
 * Output: +52 961 123 4567 (with country code) or 961 123 4567 (without country code)
 * 
 * @param string $phone Phone number in any format
 * @param bool $includeCountryCode Whether to include country code (+52) if missing
 * @return string Normalized phone number
 */
function normalize_phone_number($phone, $includeCountryCode = false) {
    if (empty($phone)) {
        return '';
    }
    
    // Remove all non-digit characters except +
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    
    // Remove leading + if present (we'll add it back if needed)
    $cleaned = ltrim($cleaned, '+');
    
    // If empty after cleaning, return original
    if (empty($cleaned)) {
        return $phone;
    }
    
    // Check if it starts with country code (52 for Mexico)
    $hasCountryCode = false;
    if (strlen($cleaned) >= 12 && substr($cleaned, 0, 2) === '52') {
        $hasCountryCode = true;
        $cleaned = substr($cleaned, 2); // Remove country code
    }
    
    // Format: Add spaces for readability
    // Format: XXX XXX XXXX (10 digits) or XXXX XXX XXXX (11 digits)
    if (strlen($cleaned) === 10) {
        // Format: 961 123 4567
        $formatted = substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6);
    } elseif (strlen($cleaned) === 11) {
        // Format: 1 961 123 4567
        $formatted = substr($cleaned, 0, 1) . ' ' . substr($cleaned, 1, 3) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7);
    } else {
        // If not standard length, return cleaned version
        return $phone;
    }
    
    // Add country code if requested and not present
    if ($includeCountryCode && !$hasCountryCode) {
        return '+52 ' . $formatted;
    } elseif ($hasCountryCode) {
        return '+52 ' . $formatted;
    }
    
    return $formatted;
}

/**
 * Get cache key for contact info based on last update timestamp
 */
function get_contact_info_cache_key() {
    static $cacheKey = null;
    
    if ($cacheKey !== null) {
        return $cacheKey;
    }
    
    try {
        if (file_exists(__DIR__ . '/config_db.php')) {
            require_once __DIR__ . '/config_db.php';
            require_once __DIR__ . '/classes/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Get max updated_at from contact-related settings
            $stmt = $db->query("
                SELECT MAX(updated_at) as last_update 
                FROM site_settings 
                WHERE setting_key IN (
                    'company_phone', 'company_phone_whatsapp', 'company_phone_landline',
                    'company_email', 'company_address', 'company_chatbot',
                    'social_facebook', 'social_instagram', 'social_linkedin', 'social_youtube'
                )
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['last_update'])) {
                $cacheKey = 'contact_info_' . md5($result['last_update']);
            } else {
                $cacheKey = 'contact_info_default';
            }
        } else {
            $cacheKey = 'contact_info_default';
        }
    } catch (Exception $e) {
        error_log("Error getting cache key: " . $e->getMessage());
        $cacheKey = 'contact_info_default';
    }
    
    return $cacheKey;
}

/**
 * Clear contact info cache
 * Call this when contact info is updated in Admin Web
 */
function clear_contact_info_cache() {
    // Clear static cache
    if (function_exists('get_contact_info')) {
        // Use reflection to clear static variable (if possible)
        // For now, we'll use a cache file approach
    }
    
    // Clear file cache if exists
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $cacheFile = $cacheDir . '/contact_info.cache';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
    
    // Clear OPcache if available
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
}

// Helper function to get contact info from SiteSettings with fallback to constants
// Uses optimized caching based on database update timestamps
function get_contact_info() {
    static $contactInfo = null;
    static $cacheKey = null;
    
    // Get current cache key
    $currentCacheKey = get_contact_info_cache_key();
    
    // If cache key changed, invalidate cache
    if ($cacheKey !== null && $cacheKey !== $currentCacheKey) {
        $contactInfo = null;
    }
    
    // Set new cache key
    $cacheKey = $currentCacheKey;
    
    // Return cached data if available
    if ($contactInfo !== null) {
        return $contactInfo;
    }
    
    // Initialize with constants as defaults (normalized)
    $defaultPhone = defined('CONTACT_PHONE') ? normalize_phone_number(CONTACT_PHONE, true) : '';
    $defaultWhatsapp = defined('CONTACT_WHATSAPP') ? preg_replace('/[^0-9]/', '', CONTACT_WHATSAPP) : '';
    
    $contactInfo = [
        'phone' => $defaultPhone,
        'phone_raw' => defined('CONTACT_PHONE') ? CONTACT_PHONE : '',
        'phone_whatsapp' => $defaultWhatsapp,
        'phone_whatsapp_formatted' => defined('CONTACT_WHATSAPP') ? normalize_phone_number(CONTACT_WHATSAPP, true) : '',
        'phone_landline' => '',
        'email' => defined('CONTACT_EMAIL_PUBLIC') ? CONTACT_EMAIL_PUBLIC : (defined('CONTACT_EMAIL') ? CONTACT_EMAIL : ''),
        'address' => defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : '',
        'chatbot' => defined('CONTACT_WHATSAPP_CHATBOT') ? CONTACT_WHATSAPP_CHATBOT : '',
        'social_facebook' => defined('SOCIAL_FACEBOOK') ? SOCIAL_FACEBOOK : '',
        'social_instagram' => defined('SOCIAL_INSTAGRAM') ? SOCIAL_INSTAGRAM : '',
        'social_linkedin' => defined('SOCIAL_LINKEDIN') ? SOCIAL_LINKEDIN : '',
        'social_youtube' => defined('SOCIAL_YOUTUBE') ? SOCIAL_YOUTUBE : ''
    ];
    
    // Try to load from SiteSettings (with error handling)
    try {
        if (file_exists(__DIR__ . '/config_db.php')) {
            require_once __DIR__ . '/config_db.php';
            require_once __DIR__ . '/classes/SiteSettings.php';
            $siteSettings = new SiteSettings();
            
            // Get WhatsApp number (normalized format)
            $whatsappFromDB = $siteSettings->getSetting('company_phone_whatsapp', '');
            if (!empty($whatsappFromDB)) {
                // Normalize: clean format for WhatsApp links (digits only)
                $contactInfo['phone_whatsapp'] = preg_replace('/[^0-9]/', '', $whatsappFromDB);
                // Also store formatted version for display
                $contactInfo['phone_whatsapp_formatted'] = normalize_phone_number($whatsappFromDB, true);
            }
            
            // Get other contact info (normalized)
            $phoneFromDB = $siteSettings->getSetting('company_phone', '');
            if (!empty($phoneFromDB)) {
                $contactInfo['phone'] = normalize_phone_number($phoneFromDB, true);
                // Also store raw for WhatsApp extraction
                $contactInfo['phone_raw'] = $phoneFromDB;
            }
            
            $landlineFromDB = $siteSettings->getSetting('company_phone_landline', '');
            if (!empty($landlineFromDB)) {
                $contactInfo['phone_landline'] = normalize_phone_number($landlineFromDB, true);
            }
            
            $emailFromDB = $siteSettings->getSetting('company_email', '');
            if (!empty($emailFromDB)) {
                $contactInfo['email'] = $emailFromDB;
            }
            
            $addressFromDB = $siteSettings->getSetting('company_address', '');
            if (!empty($addressFromDB)) {
                $contactInfo['address'] = $addressFromDB;
            }
            
            $chatbotFromDB = $siteSettings->getSetting('company_chatbot', '');
            if (!empty($chatbotFromDB)) {
                $contactInfo['chatbot'] = $chatbotFromDB;
            }
            
            // Get social media from SiteSettings
            $facebookFromDB = $siteSettings->getSetting('social_facebook', '');
            if (!empty($facebookFromDB)) {
                $contactInfo['social_facebook'] = $facebookFromDB;
            }
            
            $instagramFromDB = $siteSettings->getSetting('social_instagram', '');
            if (!empty($instagramFromDB)) {
                $contactInfo['social_instagram'] = $instagramFromDB;
            }
            
            $linkedinFromDB = $siteSettings->getSetting('social_linkedin', '');
            if (!empty($linkedinFromDB)) {
                $contactInfo['social_linkedin'] = $linkedinFromDB;
            }
            
            $youtubeFromDB = $siteSettings->getSetting('social_youtube', '');
            if (!empty($youtubeFromDB)) {
                $contactInfo['social_youtube'] = $youtubeFromDB;
            }
        }
    } catch (Exception $e) {
        // If DB fails, use constants (already set as defaults)
        error_log("Error loading contact info from SiteSettings: " . $e->getMessage());
    }
    
    return $contactInfo;
}

// Get WhatsApp number (cleaned, digits only)
function get_whatsapp_number() {
    $contactInfo = get_contact_info();
    $whatsapp = $contactInfo['phone_whatsapp'];
    
    // If empty, try to extract from phone number
    if (empty($whatsapp) && !empty($contactInfo['phone'])) {
        // Try raw phone if available, otherwise use formatted
        $phoneSource = $contactInfo['phone_raw'] ?? $contactInfo['phone'];
        $whatsapp = preg_replace('/[^0-9]/', '', $phoneSource);
    }
    
    // Fallback to constant
    if (empty($whatsapp) && defined('CONTACT_WHATSAPP')) {
        $whatsapp = preg_replace('/[^0-9]/', '', CONTACT_WHATSAPP);
    }
    
    return $whatsapp;
}

// WhatsApp Link Generator
function get_whatsapp_link($message = '') {
    $msg = empty($message) ? WHATSAPP_DEFAULT_MESSAGE : $message;
    
    // Get WhatsApp number from SiteSettings or constants
    $whatsapp_number = get_whatsapp_number();
    
    // Build WhatsApp link
    $whatsapp_url = 'https://wa.me/' . $whatsapp_number . '?text=' . urlencode($msg);
    
    return $whatsapp_url;
}

// Current Year (for footer copyright)
define('CURRENT_YEAR', date('Y'));

// Enable Error Reporting (Set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Mexico_City');

// Google Analytics
define('GOOGLE_ANALYTICS_ID', 'G-0M43GSQVX8');
define('GOOGLE_ANALYTICS_ENABLED', true);
