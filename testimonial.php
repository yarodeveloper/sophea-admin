<?php
/**
 * SOPHEA - Testimonial Detail Page
 * 
 * Displays full testimonial/case study details
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Testimonials.php';

// Get testimonial by slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php#casos');
    exit;
}

$testimonials = new Testimonials();
$testimonial = $testimonials->getTestimonialBySlug($slug);

if (!$testimonial || $testimonial['status'] !== 'published') {
    header('Location: index.php#casos');
    exit;
}

// Increment views
$testimonials->incrementViews($testimonial['id']);

// Set page title
$pageTitle = $testimonial['meta_title'] ?? $testimonial['client_name'] . ' - Caso de Éxito | ' . SITE_NAME;

// Include header
include 'header.php';
?>

<!-- TESTIMONIAL HEADER -->
<section class="pt-32 pb-12 px-4 bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="container mx-auto max-w-6xl">
        <div class="text-center mb-8">
            <a href="index.php#casos" class="inline-flex items-center text-purple-600 hover:text-purple-800 mb-4 transition-colors">
                <i class="ph-bold ph-arrow-left mr-2"></i>
                <span>Volver a Casos de Éxito</span>
            </a>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="text-gradient"><?php echo htmlspecialchars($testimonial['client_name']); ?></span>
            </h1>
            <?php if ($testimonial['client_title'] || $testimonial['client_company']): ?>
                <p class="text-xl text-gray-600">
                    <?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?>
                    <?php if ($testimonial['client_company']): ?>
                        <?php echo $testimonial['client_title'] ? ' - ' : ''; ?>
                        <?php echo htmlspecialchars($testimonial['client_company']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if ($testimonial['client_location']): ?>
                <p class="text-gray-500 mt-2">
                    <i class="ph-bold ph-map-pin"></i> <?php echo htmlspecialchars($testimonial['client_location']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIAL CONTENT -->
<section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- MAIN CONTENT -->
            <div class="lg:col-span-2">
                <!-- Featured Image -->
                <?php if ($testimonial['featured_image']): ?>
                    <div class="mb-8 rounded-2xl overflow-hidden shadow-xl">
                        <img src="<?php echo htmlspecialchars($testimonial['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($testimonial['client_name']); ?> - Caso de Éxito"
                             class="w-full h-auto object-cover">
                    </div>
                <?php endif; ?>

                <!-- Testimonial Quote -->
                <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-2xl p-8 mb-8 border-2 border-purple-200">
                    <div class="flex items-start space-x-4">
                        <i class="ph-bold ph-quote text-4xl text-purple-600"></i>
                        <blockquote class="flex-1">
                            <p class="text-xl text-gray-700 italic leading-relaxed">
                                "<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"
                            </p>
                        </blockquote>
                    </div>
                </div>

                <!-- Full Story -->
                <?php if ($testimonial['full_story']): ?>
                    <div class="prose max-w-none mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-6">La Historia Completa</h2>
                        <div class="text-gray-700 leading-relaxed">
                            <?php echo $testimonial['full_story']; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Metrics -->
                <?php if ($testimonial['metric1_value'] || $testimonial['metric2_value'] || $testimonial['metric3_value']): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Resultados Alcanzados</h2>
                        <div class="grid md:grid-cols-3 gap-6">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <?php if (!empty($testimonial['metric' . $i . '_value'])): ?>
                                    <?php
                                    $colorClasses = [
                                        'purple' => ['text-purple-600', 'bg-purple-50', 'border-purple-200'],
                                        'blue' => ['text-blue-600', 'bg-blue-50', 'border-blue-200'],
                                        'green' => ['text-green-600', 'bg-green-50', 'border-green-200'],
                                        'red' => ['text-red-600', 'bg-red-50', 'border-red-200'],
                                        'orange' => ['text-orange-600', 'bg-orange-50', 'border-orange-200']
                                    ];
                                    $color = $colorClasses[$testimonial['metric' . $i . '_color']] ?? $colorClasses['purple'];
                                    ?>
                                    <div class="bg-gradient-to-br <?php echo $color[1]; ?> rounded-xl p-6 border-2 <?php echo $color[2]; ?> text-center">
                                        <p class="text-4xl font-bold <?php echo $color[0]; ?> mb-2">
                                            <?php echo htmlspecialchars($testimonial['metric' . $i . '_value']); ?>
                                        </p>
                                        <p class="text-gray-700 font-semibold">
                                            <?php echo htmlspecialchars($testimonial['metric' . $i . '_label']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Gallery -->
                <?php if (!empty($testimonial['images'])): ?>
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Galería de Imágenes</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($testimonial['images'] as $image): ?>
                                <div class="rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($image['image_alt'] ?: $testimonial['client_name']); ?>"
                                         class="w-full h-64 object-cover cursor-pointer"
                                         onclick="openLightbox('<?php echo htmlspecialchars($image['image_path']); ?>', '<?php echo htmlspecialchars($image['image_alt'] ?: $testimonial['client_name']); ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Services Used -->
                <?php if ($testimonial['services_used']): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center space-x-2">
                            <i class="ph-bold ph-check-circle text-green-500"></i>
                            <span>Servicios Utilizados</span>
                        </h3>
                        <p class="text-gray-700 text-lg"><?php echo htmlspecialchars($testimonial['services_used']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SIDEBAR -->
            <aside class="lg:col-span-1">
                <!-- Client Info Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 sticky top-24">
                    <div class="text-center mb-6">
                        <?php if ($testimonial['client_avatar']): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                 class="w-24 h-24 rounded-full object-cover mx-auto border-4 border-purple-200 shadow-md mb-4">
                        <?php else: ?>
                            <div class="w-24 h-24 rounded-full bg-purple-600 text-white flex items-center justify-center text-3xl font-bold mx-auto mb-4 shadow-md">
                                <?php echo strtoupper(substr($testimonial['client_name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($testimonial['client_name']); ?></h3>
                        <?php if ($testimonial['client_title']): ?>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($testimonial['client_title']); ?></p>
                        <?php endif; ?>
                        <?php if ($testimonial['client_company']): ?>
                            <p class="text-gray-500 text-sm mt-1"><?php echo htmlspecialchars($testimonial['client_company']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($testimonial['client_location']): ?>
                        <div class="flex items-center justify-center space-x-2 text-gray-600 mb-4">
                            <i class="ph-bold ph-map-pin"></i>
                            <span><?php echo htmlspecialchars($testimonial['client_location']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="border-t pt-4">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span>Sector:</span>
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full font-semibold">
                                <?php echo ucfirst($testimonial['sector']); ?>
                            </span>
                        </div>
                        <?php if ($testimonial['published_at']): ?>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span>Publicado:</span>
                                <span><?php echo date('d/m/Y', strtotime($testimonial['published_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($testimonial['views'] > 0): ?>
                            <div class="flex items-center justify-between text-sm text-gray-600 mt-2">
                                <span>Vistas:</span>
                                <span><?php echo number_format($testimonial['views']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-primary rounded-2xl shadow-lg p-6 text-white text-center">
                    <i class="ph-bold ph-chat-circle text-4xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">¿Quieres resultados similares?</h3>
                    <p class="text-purple-100 mb-4">Agenda una consultoría gratuita</p>
                    <a href="index.php#contacto" 
                       class="inline-block bg-white text-purple-600 px-6 py-3 rounded-full font-semibold hover:shadow-lg transition-all transform hover:scale-105">
                        Contactar
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Lightbox Modal -->
<div id="lightbox" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4" onclick="closeLightbox()">
    <div class="max-w-4xl max-h-full">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300 text-4xl">
            <i class="ph-bold ph-x"></i>
        </button>
        <img id="lightbox-img" src="" alt="" class="max-w-full max-h-screen object-contain">
        <p id="lightbox-caption" class="text-white text-center mt-4"></p>
    </div>
</div>

<script>
function openLightbox(src, alt) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-caption').textContent = alt;
    document.getElementById('lightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>

<?php include 'footer.php'; ?>
