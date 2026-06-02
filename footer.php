<?php
/**
 * SOPHEA - Footer Template
 * 
 * This file contains the footer section and closing HTML tags.
 * Include config.php before including this file.
 */

// Make sure config is loaded
if (!defined('SITE_NAME')) {
    die('Configuration file not loaded. Please include config.php first.');
}
?>

<!-- FOOTER -->
<footer class="bg-sophea-dark text-gray-400 py-20 px-4 relative border-t border-white/5">
    <!-- Gradient accent line -->
    <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-sophea-accent/50 to-transparent"></div>

    <div class="container mx-auto max-w-6xl">
        <div class="grid md:grid-cols-12 gap-12 mb-16">
            <!-- Column 1: Brand (4 cols) -->
            <div class="md:col-span-4 space-y-6">
                <div class="mb-4">
                    <?php 
                    if (!isset($mainLogo)) {
                        require_once __DIR__ . '/classes/SiteSettings.php';
                        $siteSettingsFooter = new SiteSettings();
                        $mainLogo = $siteSettingsFooter->getMainLogo();
                    }
                    $logoPathFooter = !empty($mainLogo) ? $mainLogo : 'assets/logo_SP1.png';
                    if (!empty($logoPathFooter)): ?>
                        <img src="<?php echo htmlspecialchars($logoPathFooter); ?>" alt="SOPHEA Logo" class="h-12 w-auto object-contain brightness-110">
                    <?php else: ?>
                        <span class="text-3xl font-black text-white"><?php echo SITE_NAME; ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-base text-gray-400 font-medium leading-relaxed max-w-sm">
                    <?php echo SITE_TAGLINE; ?>. Blindaje legal y administrativo para profesionales del sector salud.
                </p>
                <!-- Social links could go here -->
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-sophea-accent/20 hover:text-sophea-accent transition-all cursor-pointer">
                        <i class="ph-bold ph-facebook-logo text-xl"></i>
                    </div>
                    <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-sophea-accent/20 hover:text-sophea-accent transition-all cursor-pointer">
                        <i class="ph-bold ph-instagram-logo text-xl"></i>
                    </div>
                    <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-sophea-accent/20 hover:text-sophea-accent transition-all cursor-pointer">
                        <i class="ph-bold ph-linkedin-logo text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Column 2: Navegación (2 cols) -->
            <div class="md:col-span-2">
                <h4 class="text-white font-black uppercase tracking-widest text-xs mb-8">Compañía</h4>
                <ul class="space-y-4 text-sm font-medium">
                    <?php foreach ($nav_menu as $item): ?>
                        <li>
                            <a href="<?php echo $item['url']; ?>" class="hover:text-sophea-accent transition-colors flex items-center group">
                                <span class="w-0 group-hover:w-2 h-[1px] bg-sophea-accent transition-all mr-0 group-hover:mr-2"></span>
                                <?php echo $item['label']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Column 3: Servicios Destacados (3 cols) -->
            <div class="md:col-span-3">
                <h4 class="text-white font-black uppercase tracking-widest text-xs mb-8">Soluciones</h4>
                <ul class="space-y-4 text-sm font-medium">
                    <li class="flex items-center gap-2">
                        <i class="ph-bold ph-shield-check text-sophea-accent"></i>
                        Compliance COFEPRIS
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ph-bold ph-globe text-blue-400"></i>
                        Estrategia Digital
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ph-bold ph-database text-purple-400"></i>
                        Blindaje Administrativo
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ph-bold ph-lightning text-yellow-400"></i>
                        Automatización Médica
                    </li>
                </ul>
            </div>

            <!-- Column 4: Contacto (3 cols) -->
            <div class="md:col-span-3">
                <h4 class="text-white font-black uppercase tracking-widest text-xs mb-8">Ubicación</h4>
                <ul class="space-y-6 text-sm">
                    <?php $contactInfo = get_contact_info(); ?>
                    
                    <li class="flex items-start gap-4">
                        <i class="ph-fill ph-map-pin text-sophea-accent text-xl"></i>
                        <span class="font-medium text-gray-300 leading-relaxed"><?php echo htmlspecialchars($contactInfo['address']); ?></span>
                    </li>
                    
                    <?php if (!empty($contactInfo['phone_whatsapp'])): ?>
                    <li class="flex items-center gap-4">
                        <i class="ph-fill ph-whatsapp-logo text-green-500 text-xl"></i>
                        <span class="font-bold text-white"><?php echo htmlspecialchars($contactInfo['phone_whatsapp']); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Static Copyright Area -->
        <div class="pt-10 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-xs font-medium text-gray-500">
                &copy; <?php echo CURRENT_YEAR; ?> <span class="text-gray-300"><?php echo SITE_NAME; ?></span>. Todos los derechos reservados.
            </div>
            <div class="flex gap-8 text-xs font-black uppercase tracking-widest">
                <a href="aviso_privacidad.php" class="text-gray-500 hover:text-white transition-colors">Aviso Privacidad</a>
                <a href="politica_cookies.php" class="text-gray-500 hover:text-white transition-colors">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- Cookie Banner Component -->
<?php include 'components/cookie_banner.php'; ?>

<!-- FLOATING WHATSAPP BUTTON -->
<a href="<?php echo get_whatsapp_link(); ?>" 
   target="_blank"
   id="whatsapp-float-btn"
   class="fixed bottom-6 right-6 bg-green-500 text-white w-16 h-16 rounded-full flex items-center justify-center shadow-2xl hover:bg-green-600 hover:scale-110 transition-all z-40"
   aria-label="Contactar por WhatsApp">
    <i class="ph-fill ph-whatsapp-logo text-3xl"></i>
</a>

<!-- JAVASCRIPT -->
<script>
    // Note: Mobile Menu Toggle has been moved to header.php for reliability.
    
    // Close mobile menu when clicking on a link (delegated for stability)
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('mobile-menu');
        if (menu && menu.classList.contains('active') && e.target.closest('a')) {
            if (typeof toggleMobileMenu === 'function') toggleMobileMenu();
        }
    });

    // Note: Form submission is handled by AJAX in sections/contacto.php
    // This ensures data is saved to database before opening WhatsApp

    // Smooth scroll behavior is already handled by CSS (scroll-behavior: smooth)
    // But we can add some animation when scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add scroll effect to header
    let lastScroll = 0;
    const header = document.querySelector('header');

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            header.classList.add('shadow-xl');
        } else {
            header.classList.remove('shadow-xl');
        }

        lastScroll = currentScroll;
    });
    
    // Adjust WhatsApp button position when cookie banner is visible
    function adjustWhatsAppButton() {
        const cookieBanner = document.getElementById('cookie-banner');
        const whatsappBtn = document.getElementById('whatsapp-float-btn');
        
        if (cookieBanner && whatsappBtn) {
            if (!cookieBanner.classList.contains('hidden')) {
                // Banner is visible, move WhatsApp button up
                whatsappBtn.style.bottom = '140px'; // Adjust based on banner height
            } else {
                // Banner is hidden, restore original position
                whatsappBtn.style.bottom = '24px'; // 6 * 4px = 24px (bottom-6)
            }
        }
    }
    
    // Check on load and when banner visibility changes
    adjustWhatsAppButton();
    
    // Watch for changes in banner visibility
    const observer = new MutationObserver(adjustWhatsAppButton);
    const cookieBanner = document.getElementById('cookie-banner');
    if (cookieBanner) {
        observer.observe(cookieBanner, { attributes: true, attributeFilter: ['class'] });
    }
</script>

</body>

</html>
