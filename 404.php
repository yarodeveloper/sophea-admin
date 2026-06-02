<?php
/**
 * SOPHEA - 404 Error Page
 * 
 * Página de error 404 con imagen y opciones de navegación
 */
http_response_code(404);
require_once 'config.php';

// Try to load banner, but don't fail if DB is unavailable
$mainBanner = '';
try {
    require_once 'config_db.php';
    require_once 'classes/SiteSettings.php';
    $siteSettings = new SiteSettings();
    $mainBanner = $siteSettings->getMainBanner();
} catch (Exception $e) {
    // If DB fails, just continue without banner
    error_log("Error loading banner in 404: " . $e->getMessage());
    $mainBanner = '';
}

include 'header.php';

// Ruta de la imagen
$errorImage = 'assets/c__Users_dell_AppData_Roaming_Cursor_User_workspaceStorage_ae2598bed9b4aa796a0b14e26c25d266_images_img_404-9f9923dc-a3ed-4a79-935e-9c0635a42e85.png';
// Si la imagen no existe, usar una alternativa
if (!file_exists($errorImage)) {
    $errorImage = null;
}
?>

<!-- BANNER SECTION (if exists) -->
<?php if ($mainBanner): ?>
<section class="pt-32 pb-8 px-4">
    <div class="container mx-auto max-w-7xl">
        <div class="rounded-2xl overflow-hidden shadow-2xl">
            <img src="<?php echo htmlspecialchars($mainBanner); ?>" 
                 alt="Banner Principal - <?php echo SITE_NAME; ?>" 
                 class="w-full h-auto object-cover"
                 onerror="this.parentElement.parentElement.parentElement.style.display='none';">
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 404 ERROR SECTION -->
<section class="<?php echo $mainBanner ? 'pb-20' : 'pt-32 pb-20'; ?> px-4 min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="container mx-auto max-w-6xl">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <!-- Left: Image -->
            <div class="flex justify-center md:justify-start">
                <?php if ($errorImage && file_exists($errorImage)): ?>
                    <img src="<?php echo htmlspecialchars($errorImage); ?>" 
                         alt="404 Error - Página no encontrada" 
                         class="max-w-full h-auto w-full max-w-md">
                <?php else: ?>
                    <div class="w-full max-w-md bg-gradient-to-br from-purple-100 to-blue-100 rounded-2xl p-12 flex items-center justify-center">
                        <i class="ph-bold ph-warning text-8xl text-purple-500"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Content -->
            <div class="text-center md:text-left">
                <div class="mb-6">
                    <h1 class="text-6xl md:text-7xl font-bold text-gray-800 mb-4">
                        <span class="text-gradient">404</span>
                    </h1>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                        No pudimos encontrar lo que estás buscando
                    </h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Lo sentimos, la página que buscas no existe o ha sido movida. 
                        Pero no te preocupes, podemos ayudarte a encontrar lo que necesitas.
                    </p>
                </div>

                <!-- Navigation Menu -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center justify-center md:justify-start space-x-2">
                        <i class="ph-bold ph-compass text-purple-600"></i>
                        <span>¿A dónde quieres ir?</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="index.php" 
                           class="flex items-center space-x-3 p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-all group">
                            <i class="ph-bold ph-house text-purple-600 text-xl group-hover:scale-110 transition-transform"></i>
                            <span class="font-semibold text-gray-800">Inicio</span>
                        </a>
                        <a href="servicios.php" 
                           class="flex items-center space-x-3 p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all group">
                            <i class="ph-bold ph-briefcase text-blue-600 text-xl group-hover:scale-110 transition-transform"></i>
                            <span class="font-semibold text-gray-800">Servicios</span>
                        </a>
                        <a href="blog.php" 
                           class="flex items-center space-x-3 p-4 bg-green-50 hover:bg-green-100 rounded-xl transition-all group">
                            <i class="ph-bold ph-article text-green-600 text-xl group-hover:scale-110 transition-transform"></i>
                            <span class="font-semibold text-gray-800">Blog</span>
                        </a>
                        <a href="index.php#contacto" 
                           class="flex items-center space-x-3 p-4 bg-orange-50 hover:bg-orange-100 rounded-xl transition-all group">
                            <i class="ph-bold ph-envelope text-orange-600 text-xl group-hover:scale-110 transition-transform"></i>
                            <span class="font-semibold text-gray-800">Contacto</span>
                        </a>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="index.php" 
                       class="bg-gradient-primary text-white px-8 py-4 rounded-full font-semibold hover:shadow-glow transition-all transform hover:scale-105 flex items-center justify-center space-x-2">
                        <i class="ph-bold ph-arrow-left"></i>
                        <span>Volver al Inicio</span>
                    </a>
                    <a href="<?php echo get_whatsapp_link('Hola, necesito ayuda para encontrar información en el sitio web'); ?>" 
                       target="_blank"
                       class="bg-green-500 text-white px-8 py-4 rounded-full font-semibold hover:bg-green-600 transition-all transform hover:scale-105 flex items-center justify-center space-x-2">
                        <i class="ph-bold ph-whatsapp-logo"></i>
                        <span>Contactar por WhatsApp</span>
                    </a>
                </div>

                <!-- Search Suggestion -->
                <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                    <p class="text-sm text-yellow-800">
                        <i class="ph-bold ph-lightbulb text-yellow-600"></i>
                        <strong>Tip:</strong> Puedes usar el menú de navegación superior para explorar todas nuestras secciones.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
