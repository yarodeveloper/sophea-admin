<?php
/**
 * SOPHEA - Testimonials Listing Page
 * 
 * Displays all published testimonials/case studies
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Testimonials.php';

// Initialize testimonials
$testimonials = new Testimonials();

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get sector filter
$sector = isset($_GET['sector']) ? $_GET['sector'] : null;

// Get testimonials
$allTestimonials = $testimonials->getPublishedTestimonials($perPage, $offset);
$totalTestimonials = $testimonials->getPublishedCount();
$totalPages = ceil($totalTestimonials / $perPage);

// Set page title
$pageTitle = "Casos de Éxito | " . SITE_NAME;
?>
<?php include 'header.php'; ?>

<!-- TESTIMONIALS HEADER -->
<section class="pt-32 pb-12 px-4 bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="container mx-auto max-w-6xl">
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="text-gradient">Casos de Éxito</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Historias reales de clientes que transformaron su negocio con SOPHEA
            </p>
        </div>
    </div>
</section>

<!-- TESTIMONIALS CONTENT -->
<section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
        <?php if (empty($allTestimonials)): ?>
            <div class="text-center py-16">
                <i class="ph-bold ph-quote text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">No hay casos de éxito disponibles</h2>
                <p class="text-gray-500">Pronto publicaremos más historias de éxito.</p>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
                <?php foreach ($allTestimonials as $testimonial): ?>
                    <?php
                    $sectorColors = [
                        'salud' => ['from-purple-50', 'to-blue-50', 'border-purple-200', 'bg-purple-600'],
                        'general' => ['from-blue-50', 'to-cyan-50', 'border-blue-200', 'bg-blue-600'],
                        'retail' => ['from-green-50', 'to-emerald-50', 'border-green-200', 'bg-green-600'],
                        'servicios' => ['from-orange-50', 'to-red-50', 'border-orange-200', 'bg-orange-600']
                    ];
                    $colors = $sectorColors[$testimonial['sector']] ?? $sectorColors['general'];
                    $initials = strtoupper(substr($testimonial['client_name'], 0, 2));
                    ?>
                    <article class="bg-gradient-to-br <?php echo $colors[0] . ' ' . $colors[1]; ?> rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow border-2 <?php echo $colors[2]; ?>">
                        <?php if ($testimonial['featured_image']): ?>
                            <a href="testimonial.php?slug=<?php echo htmlspecialchars($testimonial['slug']); ?>">
                                <img src="<?php echo htmlspecialchars($testimonial['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                     class="w-full h-48 object-cover">
                            </a>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-center space-x-3 mb-4">
                                <?php if ($testimonial['client_avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                         class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-md">
                                <?php else: ?>
                                    <div class="<?php echo $colors[3]; ?> text-white w-12 h-12 rounded-full flex items-center justify-center font-bold shadow-md">
                                        <?php echo $initials; ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($testimonial['client_name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?></p>
                                </div>
                            </div>

                            <p class="text-gray-700 italic mb-4 line-clamp-3">
                                "<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"
                            </p>

                            <?php if ($testimonial['metric1_value'] || $testimonial['metric2_value'] || $testimonial['metric3_value']): ?>
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <?php if (!empty($testimonial['metric' . $i . '_value'])): ?>
                                            <div class="bg-white rounded-lg p-2 text-center">
                                                <p class="text-lg font-bold text-purple-600"><?php echo htmlspecialchars($testimonial['metric' . $i . '_value']); ?></p>
                                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($testimonial['metric' . $i . '_label']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>

                            <a href="testimonial.php?slug=<?php echo htmlspecialchars($testimonial['slug']); ?>" 
                               class="inline-block text-purple-600 font-semibold hover:text-purple-800 transition-colors">
                                Ver caso completo <i class="ph-bold ph-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center space-x-2 mt-8">
                    <?php
                    function buildTestimonialPaginationUrl($pageNum) {
                        $params = $_GET;
                        $params['page'] = $pageNum;
                        return 'testimonials.php?' . http_build_query($params);
                    }
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildTestimonialPaginationUrl($page - 1); ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ph-bold ph-arrow-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="<?php echo buildTestimonialPaginationUrl($i); ?>" 
                           class="px-4 py-2 <?php echo $i == $page ? 'bg-purple-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo buildTestimonialPaginationUrl($page + 1); ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Siguiente <i class="ph-bold ph-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include 'footer.php'; ?>
