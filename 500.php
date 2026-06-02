<?php
/**
 * SOPHEA - 500 Error Page
 * 
 * Página de error 500 (Error interno del servidor)
 */
http_response_code(500);
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
    error_log("Error loading banner in 500: " . $e->getMessage());
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

<!-- 500 ERROR SECTION -->
<section class="<?php echo $mainBanner ? 'pb-20' : 'pt-32 pb-20'; ?> px-4 min-h-screen bg-gradient-to-br from-red-50 via-white to-orange-50">
    <div class="container mx-auto max-w-6xl">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <!-- Left: Image -->
            <div class="flex justify-center md:justify-start">
                <?php if ($errorImage && file_exists($errorImage)): ?>
                    <img src="<?php echo htmlspecialchars($errorImage); ?>" 
                         alt="500 Error - Error interno del servidor" 
                         class="max-w-full h-auto w-full max-w-md">
                <?php else: ?>
                    <div class="w-full max-w-md bg-gradient-to-br from-red-100 to-orange-100 rounded-2xl p-12 flex items-center justify-center">
                        <i class="ph-bold ph-warning-circle text-8xl text-red-500"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Content -->
            <div class="text-center md:text-left">
                <div class="mb-6">
                    <h1 class="text-6xl md:text-7xl font-bold text-gray-800 mb-4">
                        <span class="text-gradient">500</span>
                    </h1>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                        Error interno del servidor
                    </h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Lo sentimos, algo salió mal en nuestro servidor. 
                        Nuestro equipo técnico ha sido notificado y está trabajando para solucionarlo.
                    </p>
                </div>

                <!-- Information Box -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center justify-center md:justify-start space-x-2">
                        <i class="ph-bold ph-info text-red-600"></i>
                        <span>¿Qué puedes hacer?</span>
                    </h3>
                    <ul class="space-y-3 text-left">
                        <li class="flex items-start space-x-3">
                            <i class="ph-bold ph-check-circle text-green-600 text-xl mt-0.5"></i>
                            <span class="text-gray-700">Intenta recargar la página en unos momentos</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="ph-bold ph-check-circle text-green-600 text-xl mt-0.5"></i>
                            <span class="text-gray-700">Vuelve a la página anterior y prueba de nuevo</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="ph-bold ph-check-circle text-green-600 text-xl mt-0.5"></i>
                            <span class="text-gray-700">Si el problema persiste, contáctanos</span>
                        </li>
                    </ul>
                </div>

                <!-- Navigation Menu -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center justify-center md:justify-start space-x-2">
                        <i class="ph-bold ph-compass text-red-600"></i>
                        <span>Navega a otras secciones</span>
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
                    <a href="<?php echo get_whatsapp_link('Hola, estoy experimentando un error 500 en el sitio web'); ?>" 
                       target="_blank"
                       class="bg-green-500 text-white px-8 py-4 rounded-full font-semibold hover:bg-green-600 transition-all transform hover:scale-105 flex items-center justify-center space-x-2">
                        <i class="ph-bold ph-whatsapp-logo"></i>
                        <span>Reportar el Error</span>
                    </a>
                </div>

                <!-- Technical Info -->
                <div class="mt-8 p-4 bg-gray-50 border border-gray-200 rounded-xl">
                    <p class="text-sm text-gray-600">
                        <i class="ph-bold ph-info text-gray-500"></i>
                        <strong>Error 500:</strong> Este es un error del servidor. Si el problema persiste, por favor contacta al administrador del sitio.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

