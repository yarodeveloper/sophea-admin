<?php
/**
 * SOPHEA - Header Template
 */

if (!defined('SITE_NAME')) {
    die('Configuration file not loaded.');
}

function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (!headers_sent()) {
    setSecurityHeaders();
}

$mainLogo = '';
try {
    require_once __DIR__ . '/config_db.php';
    require_once __DIR__ . '/classes/SiteSettings.php';
    $siteSettings = new SiteSettings();
    $mainLogo = $siteSettings->getMainLogo();
} catch (Exception $e) {
    $mainLogo = '';
}

require_once __DIR__ . '/classes/SchemaGenerator.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($customPageTitle) ? $customPageTitle : get_page_title();
$pageDescription = isset($customPageDescription) ? $customPageDescription : (defined('SEO_DESCRIPTION_SHORT') ? SEO_DESCRIPTION_SHORT : SITE_DESCRIPTION);
$pageImage = defined('SCHEMA_OG_IMAGE') ? SCHEMA_OG_IMAGE : ($mainLogo ? $mainLogo : SCHEMA_LOGO);
$pageUrl = SCHEMA_URL . '/' . $currentPage;
$contactInfo = get_contact_info();
$socialFacebook = !empty($contactInfo['social_facebook'] ?? '') ? $contactInfo['social_facebook'] : (defined('SOCIAL_FACEBOOK') ? SOCIAL_FACEBOOK : null);
$socialInstagram = !empty($contactInfo['social_instagram'] ?? '') ? $contactInfo['social_instagram'] : (defined('SOCIAL_INSTAGRAM') ? SOCIAL_INSTAGRAM : null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <link rel="icon" type="image/png" href="<?php echo SCHEMA_FAVICON; ?>">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --sophea-dark: #0B1120;
            --sophea-card: #131C30;
            --sophea-accent: #2DD4BF;
            --sophea-light: #F8FAFC;
        }

        * { font-family: 'Inter', sans-serif; }
        html { scroll-behavior: smooth; }
        
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .bg-gradient-dark {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .bg-gradient-light {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .bg-sophea-dark { background-color: var(--sophea-dark) !important; }
        .bg-sophea-card { background-color: var(--sophea-card) !important; }
        .text-sophea-accent { color: var(--sophea-accent) !important; }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-card-light {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        }

        .shadow-glow-accent {
            box-shadow: 0 0 30px rgba(45, 212, 191, 0.15);
        }

        .shadow-glow-primary {
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.15);
        }

        .hover-lift {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
        }

        /* 1. SEPARACIÓN TOTAL PC vs MOVIL */
        @media (max-width: 767px) {
            .nav-desktop { display: none !important; }
            .btn-burger { display: flex !important; }
        }
        @media (min-width: 768px) {
            .nav-desktop { display: flex !important; }
            .btn-burger { display: none !important; }
            #capa-menu-v5 { display: none !important; } /* No mostrar overlay en PC */
        }

        /* 2. OVERLAY MEJORADO */
        #capa-menu-v5.activo {
            transform: translateX(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        /* 3. FIX VISIBILIDAD HEADER (Asegurar que no se esconda) */
        header { 
            opacity: 1 !important; 
            transform: none !important; 
            visibility: visible !important;
        }

        /* Submenu Dropdown for Desktop */
        .desktop-nav-item:hover .desktop-submenu {
            display: block !important;
        }

        /* Responsive Improvements */
        @media (max-width: 640px) {
            h1 { font-size: 2.5rem !important; line-height: 1.1 !important; }
            .section-padding { padding-top: 4rem; padding-bottom: 4rem; }
        }
    </style>
    <?php if (defined('GOOGLE_ANALYTICS_ENABLED') && GOOGLE_ANALYTICS_ENABLED && defined('GOOGLE_ANALYTICS_ID')): ?>
    <!-- Google Analytics (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        
        // Modo de consentimiento por defecto (denegado hasta que el usuario acepte)
        gtag('consent', 'default', {
            'analytics_storage': 'denied',
            'ad_storage': 'denied',
            'wait_for_update': 500
        });

        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars(GOOGLE_ANALYTICS_ID); ?>', {
            'anonymize_ip': true,
            'cookie_flags': 'SameSite=None;Secure'
        });
    </script>
    <?php endif; ?>
</head>

<body class="bg-gray-50 text-gray-900">

    <!-- MENÚ OVERLAY (Solo Movil) -->
    <div id="capa-menu-v5" 
         style="position: fixed; inset: 0; background: white; z-index: 2147483647 !important; transform: translateX(100%); transition: 0.4s ease-in-out; display: flex; flex-direction: column; opacity: 0; pointer-events: none;">
        
        <!-- Header del Menú -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #eee;">
            <?php 
            $logoPathMobile = !empty($mainLogo) ? $mainLogo : 'assets/logo_SP1.png';
            if (!empty($logoPathMobile)): ?>
                <img src="<?php echo htmlspecialchars($logoPathMobile); ?>" alt="SOPHEA Logo" style="height: 32px; width: auto; object-fit: contain;">
            <?php else: ?>
                <span style="font-weight: 900; color: #7c3aed; font-size: 20px;">MENÚ</span>
            <?php endif; ?>
            <button onclick="cerrarMenuV5()" 
                    style="width: 48px; height: 48px; border-radius: 12px; border: none; background: #f5f3ff; color: #7c3aed; cursor: pointer;">
                <i class="ph-bold ph-x" style="font-size: 24px;"></i>
            </button>
        </div>

        <!-- Links -->
        <div style="flex: 1; overflow-y: auto; padding: 30px 24px; display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($nav_menu as $item): ?>
                <?php if (isset($item['sub_menu'])): ?>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 900; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.1em;"><?php echo $item['label']; ?></span>
                        <div style="margin-top: 15px; padding-left: 15px; border-left: 2px solid #7c3aed; display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach ($item['sub_menu'] as $sub_item): ?>
                                <a href="<?php echo $sub_item['url']; ?>" onclick="cerrarMenuV5()" style="text-decoration: none; color: #374151; font-weight: 600; font-size: 17px;"><?php echo $sub_item['label']; ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $item['url']; ?>" onclick="cerrarMenuV5()" 
                       style="text-decoration: none; color: #111827; font-weight: 800; font-size: 20px; border-bottom: 1px solid #f9fafb; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <?php echo $item['label']; ?>
                        <i class="ph ph-arrow-right" style="color: #ddd6fe;"></i>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div style="margin-top: 20px;">
                <a href="#contacto" onclick="cerrarMenuV5()"
                   style="display: block; background: #7c3aed; color: white; padding: 18px; border-radius: 15px; font-weight: 900; text-align: center; text-decoration: none; font-size: 18px;">
                   AUDITORÍA GRATUITA
                </a>
            </div>
        </div>
    </div>

    <!-- CABECERA (Fixed) -->
    <header style="position: fixed; top: 0; left: 0; right: 0; height: 80px; background: white; z-index: 2147483646 !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center;">
        <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            
            <!-- Logo Section -->
            <a href="index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                <?php 
                $logoPath = !empty($mainLogo) ? $mainLogo : 'assets/logo_SP1.png';
                if (!empty($logoPath)): ?>
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="SOPHEA Logo" style="height: 48px; width: auto; max-width: 200px; object-fit: contain;">
                <?php else: ?>
                    <span class="text-gradient" style="font-weight: 900; font-size: 24px; letter-spacing: -0.5px;"><?php echo SITE_NAME; ?></span>
                <?php endif; ?>
            </a>

            <!-- NAV PC (Solo visible en Desktop) -->
            <div class="nav-desktop" style="align-items: center; gap: 32px;">
                <?php foreach ($nav_menu as $item): ?>
                    <?php if (isset($item['sub_menu'])): ?>
                        <div class="desktop-nav-item" style="position: relative; cursor: pointer;">
                            <span style="font-weight: 700; color: #4b5563; font-size: 15px; display: flex; align-items: center; gap: 4px;">
                                <?php echo $item['label']; ?>
                                <i class="ph ph-caret-down" style="font-size: 12px;"></i>
                            </span>
                            <!-- Dropdown -->
                            <div class="desktop-submenu" style="display: none; position: absolute; top: 100%; left: 0; background: white; min-width: 220px; padding: 12px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #f3f4f6; margin-top: 10px;">
                                <?php foreach ($item['sub_menu'] as $sub_item): ?>
                                    <a href="<?php echo $sub_item['url']; ?>" 
                                       style="display: block; padding: 10px 12px; color: #4b5563; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 8px; transition: 0.2s;"
                                       onmouseover="this.style.background='#f5f3ff'; this.style.color='#7c3aed';"
                                       onmouseout="this.style.background='transparent'; this.style.color='#4b5563';">
                                        <?php echo $sub_item['label']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $item['url']; ?>" 
                           style="text-decoration: none; font-weight: 700; color: #4b5563; font-size: 15px; transition: 0.2s;"
                           onmouseover="this.style.color='#7c3aed';"
                           onmouseout="this.style.color='#4b5563';">
                            <?php echo $item['label']; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <a href="#contacto" 
                   style="background: #7c3aed; color: white; border: none; padding: 12px 24px; border-radius: 100px; cursor: pointer; font-weight: 800; font-size: 14px; text-decoration: none; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3); transition: 0.3s;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(124, 58, 237, 0.4)';"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(124, 58, 237, 0.3)';"
                   class="bg-gradient-primary">
                    AUDITORÍA GRATIS
                </a>
            </div>

            <!-- BOTÓN BURGER (Solo visible en Movil) -->
            <button class="btn-burger" onclick="abrirMenuV5()" 
                    style="align-items: center; justify-content: center; width: 48px; height: 48px; background: #f5f3ff; color: #7c3aed; border: none; border-radius: 12px; cursor: pointer; transition: 0.2s;">
                <i class="ph-bold ph-list" style="font-size: 24px;"></i>
            </button>
        </div>
    </header>

    <div style="height: 80px;"></div> <!-- Espaciador para no tapar el contenido -->

    <script>
        function abrirMenuV5() {
            document.getElementById('capa-menu-v5').classList.add('activo');
            document.body.style.overflow = 'hidden';
        }
        function cerrarMenuV5() {
            document.getElementById('capa-menu-v5').classList.remove('activo');
            document.body.style.overflow = '';
        }
    </script>


