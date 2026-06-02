<?php
/**
 * SOPHEA - Case Studies Section
 * 
 * Displays success stories from clients
 */

// Load testimonials if not already loaded
if (!isset($testimonials)) {
    require_once __DIR__ . '/../config_db.php';
    require_once __DIR__ . '/../classes/Testimonials.php';
    $testimonials = new Testimonials();
}

// Get testimonials for homepage (up to 10 for carousel)
// Priority: 1) Featured and published, 2) Any published
$featuredTestimonials = $testimonials->getPublishedTestimonials(10, 0, true);

// If no featured testimonials, get any published ones
if (empty($featuredTestimonials)) {
    $featuredTestimonials = $testimonials->getPublishedTestimonials(10, 0, false);
}

$testimonialsCount = count($featuredTestimonials);
$showCarousel = $testimonialsCount > 2;
?>

<!-- CASOS DE ÉXITO SECTION -->
<section id="casos" class="py-24 px-4 bg-gray-50 overflow-hidden relative">
    <div class="container mx-auto max-w-6xl relative z-10">
        <div class="text-center mb-20">
            <span class="text-primary font-black tracking-widest uppercase text-sm">Prueba Social Relevante</span>
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black mt-4 mb-6 leading-tight text-gray-900">
                Casos de Éxito <br> <span class="text-gradient">Comprobados.</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto font-medium">
                No confíes solo en nuestra palabra, confía en los resultados de quienes ya confían en nosotros.
            </p>
        </div>

        <?php 
        // Debug info (remove in production)
        // Uncomment to debug:
        // echo "<!-- Debug: Testimonials count = " . count($featuredTestimonials) . " -->";
        // if (!empty($featuredTestimonials)) {
        //     foreach ($featuredTestimonials as $t) {
        //         echo "<!-- Debug: " . $t['client_name'] . " - Status: " . $t['status'] . ", Featured: " . $t['featured'] . " -->";
        //     }
        // }
        ?>
        
        <?php if (empty($featuredTestimonials)): ?>
            <!-- Fallback to static content if no testimonials -->
            <div class="grid md:grid-cols-2 gap-8">
            <!-- Caso 1: Sector Salud -->
            <div
                class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-2xl shadow-xl p-8 border-2 border-purple-200">
                <div class="flex items-center space-x-4 mb-6">
                    <div
                        class="bg-purple-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold">
                        DS
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Dr. Sergio M.</h3>
                        <p class="text-gray-600">Cirujano Plástico - <?php echo GEO_PLACENAME; ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 mb-6">
                    <p class="text-gray-700 italic mb-4">
                        "Antes tenía miedo de invertir en publicidad por las regulaciones. SOPHEA me dio la
                        tranquilidad de crecer sin riesgos legales. En 6 meses triplicamos las consultas."
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-purple-600">+287%</p>
                        <p class="text-sm text-gray-600 mt-1">Citas Mensuales</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-green-600">100%</p>
                        <p class="text-sm text-gray-600 mt-1">Compliance</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-blue-600">0</p>
                        <p class="text-sm text-gray-600 mt-1">Multas</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center space-x-2 text-sm text-gray-600">
                    <i class="ph-fill ph-check-circle text-green-500"></i>
                    <span><strong>Servicios:</strong> Compliance COFEPRIS + Ads + Web</span>
                </div>
            </div>

            <!-- Caso 2: Sector General/Retail -->
            <div
                class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl shadow-xl p-8 border-2 border-blue-200">
                <div class="flex items-center space-x-4 mb-6">
                    <div
                        class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold">
                        VF
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Valentina F.</h3>
                        <p class="text-gray-600">Dueña de Boutique de Moda - Chiapas</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 mb-6">
                    <p class="text-gray-700 italic mb-4">
                        "Necesitaba una tienda online profesional y automatización para atender clientes 24/7. El
                        chatbot con IA aumentó nuestras ventas nocturnas un 400%."
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-blue-600">+420%</p>
                        <p class="text-sm text-gray-600 mt-1">Ventas Online</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-green-600">24/7</p>
                        <p class="text-sm text-gray-600 mt-1">Atención</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow">
                        <p class="text-3xl font-bold text-purple-600">6.2x</p>
                        <p class="text-sm text-gray-600 mt-1">ROI</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center space-x-2 text-sm text-gray-600">
                    <i class="ph-fill ph-check-circle text-green-500"></i>
                    <span><strong>Servicios:</strong> E-commerce + Chatbot IA + Automatización</span>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php if ($showCarousel): ?>
                <!-- Carousel for multiple testimonials -->
                <div class="relative testimonials-carousel-container">
                    <!-- Carousel Wrapper -->
                    <div class="testimonials-carousel overflow-hidden">
                        <div class="testimonials-carousel-track flex transition-transform duration-500 ease-in-out" id="testimonials-track">
                            <?php foreach ($featuredTestimonials as $index => $testimonial): ?>
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
                                <div class="testimonial-slide min-w-full md:min-w-[50%] px-2 sm:px-4" data-index="<?php echo $index; ?>">
                                    <div class="bg-gradient-to-br <?php echo $colors[0] . ' ' . $colors[1]; ?> rounded-2xl shadow-xl p-4 sm:p-6 md:p-8 border-2 <?php echo $colors[2]; ?> hover:shadow-2xl md:hover:scale-[1.02] transition-all duration-300 h-full transform md:hover:-translate-y-1">
                                        <div class="flex items-center space-x-3 sm:space-x-4 mb-4 sm:mb-6">
                                            <?php if ($testimonial['client_avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                                     alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                                     class="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-full object-cover border-2 border-white shadow-md flex-shrink-0">
                                            <?php else: ?>
                                                <div class="<?php echo $colors[3]; ?> text-white w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center text-lg sm:text-xl md:text-2xl font-bold shadow-md flex-shrink-0">
                                                    <?php echo $initials; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-lg sm:text-xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($testimonial['client_name']); ?></h3>
                                                <p class="text-sm sm:text-base text-gray-600 line-clamp-2">
                                                    <?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?>
                                                    <?php if ($testimonial['client_location']): ?>
                                                        - <?php echo htmlspecialchars($testimonial['client_location']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-xl p-4 sm:p-6 mb-4 sm:mb-6">
                                            <p class="text-sm sm:text-base text-gray-700 italic mb-2 sm:mb-4 line-clamp-4 sm:line-clamp-none">
                                                "<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"
                                            </p>
                                        </div>

                                        <?php if ($testimonial['metric1_value'] || $testimonial['metric2_value'] || $testimonial['metric3_value']): ?>
                                        <div class="grid grid-cols-3 gap-2 sm:gap-3 md:gap-4 mb-4 sm:mb-0">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <?php if (!empty($testimonial['metric' . $i . '_value'])): ?>
                                                    <?php
                                                    $colorClasses = [
                                                        'purple' => 'text-purple-600',
                                                        'blue' => 'text-blue-600',
                                                        'green' => 'text-green-600',
                                                        'red' => 'text-red-600',
                                                        'orange' => 'text-orange-600'
                                                    ];
                                                    $color = $colorClasses[$testimonial['metric' . $i . '_color']] ?? 'text-purple-600';
                                                    ?>
                                                    <div class="bg-white rounded-xl p-2 sm:p-3 md:p-4 text-center shadow">
                                                        <p class="text-xl sm:text-2xl md:text-3xl font-bold <?php echo $color; ?>"><?php echo htmlspecialchars($testimonial['metric' . $i . '_value']); ?></p>
                                                        <p class="text-xs sm:text-sm text-gray-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($testimonial['metric' . $i . '_label']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($testimonial['services_used']): ?>
                                        <div class="mt-4 sm:mt-6 flex items-start sm:items-center space-x-2 text-xs sm:text-sm text-gray-600">
                                            <i class="ph-fill ph-check-circle text-green-500 flex-shrink-0 mt-0.5 sm:mt-0"></i>
                                            <span class="line-clamp-2"><strong>Servicios:</strong> <?php echo htmlspecialchars($testimonial['services_used']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-4 sm:mt-6">
                                            <a href="testimonial.php?slug=<?php echo htmlspecialchars($testimonial['slug']); ?>" 
                                               class="group inline-flex items-center text-sm sm:text-base text-purple-600 font-semibold hover:text-purple-800 transition-all duration-300 hover:gap-2 gap-1 active:scale-95">
                                                Ver caso completo 
                                                <i class="ph-bold ph-arrow-right group-hover:translate-x-1 transition-transform duration-300"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Navigation Arrows -->
                    <button class="testimonials-carousel-btn testimonials-carousel-prev absolute left-0 sm:left-2 top-1/2 -translate-y-1/2 bg-white shadow-xl rounded-full p-3 sm:p-4 hover:bg-purple-50 hover:shadow-2xl md:hover:scale-110 active:scale-95 transition-all duration-300 z-10 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 touch-manipulation" 
                            aria-label="Testimonio anterior" 
                            tabindex="0">
                        <i class="ph-bold ph-caret-left text-xl sm:text-2xl text-purple-600"></i>
                    </button>
                    <button class="testimonials-carousel-btn testimonials-carousel-next absolute right-0 sm:right-2 top-1/2 -translate-y-1/2 bg-white shadow-xl rounded-full p-3 sm:p-4 hover:bg-purple-50 hover:shadow-2xl md:hover:scale-110 active:scale-95 transition-all duration-300 z-10 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 touch-manipulation" 
                            aria-label="Testimonio siguiente" 
                            tabindex="0">
                        <i class="ph-bold ph-caret-right text-xl sm:text-2xl text-purple-600"></i>
                    </button>

                    <!-- Progress Indicator -->
                    <div class="flex items-center justify-center mt-4 sm:mt-6 mb-3 sm:mb-4 space-x-3">
                        <span class="text-xs sm:text-sm font-semibold text-gray-600 testimonials-counter">
                            <span class="testimonials-current">1</span> / <span class="testimonials-total"><?php echo $testimonialsCount; ?></span>
                        </span>
                    </div>

                    <!-- Dots Indicator -->
                    <div class="flex justify-center mt-2 space-x-2 sm:space-x-3 testimonials-carousel-dots" role="tablist" aria-label="Navegación de testimonios">
                        <?php 
                        $slidesPerView = 2; // Desktop shows 2, mobile shows 1
                        $totalSlides = $testimonialsCount;
                        $totalDots = ceil($totalSlides / $slidesPerView);
                        for ($i = 0; $i < $totalDots; $i++): 
                        ?>
                            <button class="testimonials-dot w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 sm:focus:ring-offset-2 touch-manipulation <?php echo $i === 0 ? 'bg-purple-600 w-6 sm:w-8' : 'bg-gray-300 hover:bg-purple-400'; ?>" 
                                    data-slide="<?php echo $i; ?>" 
                                    role="tab"
                                    aria-label="Ir al testimonio <?php echo ($i * $slidesPerView) + 1; ?>"
                                    aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                                    tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>"></button>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Static Grid for 1-2 testimonials -->
                <div class="grid md:grid-cols-2 gap-8">
                    <?php foreach ($featuredTestimonials as $testimonial): ?>
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
                        <div class="bg-gradient-to-br <?php echo $colors[0] . ' ' . $colors[1]; ?> rounded-2xl shadow-xl p-8 border-2 <?php echo $colors[2]; ?> hover:shadow-2xl transition-shadow">
                            <div class="flex items-center space-x-4 mb-6">
                                <?php if ($testimonial['client_avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                         class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md">
                                <?php else: ?>
                                    <div class="<?php echo $colors[3]; ?> text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold shadow-md">
                                        <?php echo $initials; ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($testimonial['client_name']); ?></h3>
                                    <p class="text-gray-600">
                                        <?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?>
                                        <?php if ($testimonial['client_location']): ?>
                                            - <?php echo htmlspecialchars($testimonial['client_location']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl p-6 mb-6">
                                <p class="text-gray-700 italic mb-4">
                                    "<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"
                                </p>
                            </div>

                            <?php if ($testimonial['metric1_value'] || $testimonial['metric2_value'] || $testimonial['metric3_value']): ?>
                            <div class="grid grid-cols-3 gap-4">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <?php if (!empty($testimonial['metric' . $i . '_value'])): ?>
                                        <?php
                                        $colorClasses = [
                                            'purple' => 'text-purple-600',
                                            'blue' => 'text-blue-600',
                                            'green' => 'text-green-600',
                                            'red' => 'text-red-600',
                                            'orange' => 'text-orange-600'
                                        ];
                                        $color = $colorClasses[$testimonial['metric' . $i . '_color']] ?? 'text-purple-600';
                                        ?>
                                        <div class="bg-white rounded-xl p-4 text-center shadow">
                                            <p class="text-3xl font-bold <?php echo $color; ?>"><?php echo htmlspecialchars($testimonial['metric' . $i . '_value']); ?></p>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($testimonial['metric' . $i . '_label']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($testimonial['services_used']): ?>
                            <div class="mt-6 flex items-center space-x-2 text-sm text-gray-600">
                                <i class="ph-fill ph-check-circle text-green-500"></i>
                                <span><strong>Servicios:</strong> <?php echo htmlspecialchars($testimonial['services_used']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-6">
                                <a href="testimonial.php?slug=<?php echo htmlspecialchars($testimonial['slug']); ?>" 
                                   class="inline-block text-purple-600 font-semibold hover:text-purple-800 transition-colors">
                                    Ver caso completo <i class="ph-bold ph-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- View All Link -->
            <div class="text-center mt-8 sm:mt-12">
                <a href="testimonials.php" 
                   class="inline-block bg-gradient-primary text-white px-6 py-3 sm:px-8 sm:py-4 rounded-full text-sm sm:text-base font-semibold hover:shadow-glow transition-all transform hover:scale-105 active:scale-95 touch-manipulation">
                    Ver Todos los Casos de Éxito
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($showCarousel): ?>
<style>
.testimonials-carousel-container {
    position: relative;
    padding: 0 1rem;
}

.testimonials-carousel {
    position: relative;
}

.testimonials-carousel-track {
    display: flex;
    will-change: transform;
    -webkit-overflow-scrolling: touch;
}

.testimonial-slide {
    flex-shrink: 0;
    animation: fadeInSlide 0.5s ease-out;
}

@keyframes fadeInSlide {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.testimonials-carousel-btn {
    opacity: 0.95;
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.testimonials-carousel-btn:hover:not(:disabled) {
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
}

.testimonials-carousel-btn:active:not(:disabled) {
    transform: translateY(-50%) scale(0.9);
}

.testimonials-carousel-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    transform: translateY(-50%) scale(1);
}

.testimonials-carousel-btn:focus-visible {
    outline: 2px solid #9333ea;
    outline-offset: 2px;
}

.testimonials-dot {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    min-width: 10px;
    min-height: 10px;
    touch-action: manipulation;
}

.testimonials-dot:hover:not([aria-selected="true"]) {
    background-color: #a855f7 !important;
    transform: scale(1.2);
}

.testimonials-dot:active {
    transform: scale(0.9);
}

.testimonials-dot[aria-selected="true"] {
    background-color: #9333ea !important;
    box-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
}

.testimonials-counter {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 600;
}

/* Smooth scrollbar for carousel track */
.testimonials-carousel-track {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.testimonials-carousel-track::-webkit-scrollbar {
    display: none;
}

/* Loading shimmer effect */
.testimonial-slide.loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Improved card hover effects (desktop only) */
@media (hover: hover) {
    .testimonial-slide > div {
        position: relative;
        overflow: hidden;
    }

    .testimonial-slide > div::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .testimonial-slide > div:hover::before {
        left: 100%;
    }
}

/* Mobile Optimizations */
@media (max-width: 640px) {
    .testimonials-carousel-container {
        padding: 0 0.5rem;
    }
    
    .testimonial-slide {
        min-width: 100% !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .testimonials-carousel-btn {
        padding: 0.75rem !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        opacity: 0.9;
    }
    
    .testimonials-carousel-btn i {
        font-size: 1.25rem !important;
    }
    
    .testimonials-counter {
        font-size: 0.75rem;
    }
    
    .testimonials-dot {
        width: 8px !important;
        height: 8px !important;
        min-width: 8px;
        min-height: 8px;
    }
    
    .testimonials-dot[aria-selected="true"] {
        width: 24px !important;
    }
}

/* Tablet Optimizations */
@media (min-width: 641px) and (max-width: 768px) {
    .testimonials-carousel-container {
        padding: 0 1.5rem;
    }
    
    .testimonials-carousel-btn {
        padding: 0.875rem !important;
    }
}

/* Desktop */
@media (min-width: 769px) {
    .testimonials-carousel-container {
        padding: 0 3rem;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .testimonials-carousel-track,
    .testimonial-slide,
    .testimonials-carousel-btn,
    .testimonials-dot {
        transition: none;
        animation: none;
    }
}

/* Line clamp utilities for mobile text truncation */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Touch optimization */
.touch-manipulation {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}
</style>

<script>
(function() {
    const track = document.getElementById('testimonials-track');
    const slides = document.querySelectorAll('.testimonial-slide');
    const prevBtn = document.querySelector('.testimonials-carousel-prev');
    const nextBtn = document.querySelector('.testimonials-carousel-next');
    const dots = document.querySelectorAll('.testimonials-dot');
    const counterCurrent = document.querySelector('.testimonials-current');
    const counterTotal = document.querySelector('.testimonials-total');
    
    if (!track || slides.length === 0) return;
    
    let currentIndex = 0;
    let isTransitioning = false;
    let autoPlayInterval = null;
    const isMobile = window.innerWidth < 768;
    const slidesPerView = isMobile ? 1 : 2;
    const totalSlides = slides.length;
    const maxIndex = Math.max(0, totalSlides - slidesPerView);
    const autoPlayDelay = 5000; // 5 seconds
    
    function updateCarousel() {
        if (isTransitioning) return;
        
        isTransitioning = true;
        const slideWidth = isMobile ? 100 : 50; // Percentage
        const translateX = -(currentIndex * slideWidth);
        
        // Smooth transition
        track.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        track.style.transform = `translateX(${translateX}%)`;
        
        // Update counter
        if (counterCurrent) {
            const displayIndex = Math.min(currentIndex + slidesPerView, totalSlides);
            counterCurrent.textContent = displayIndex;
        }
        
        // Update dots
        const activeDotIndex = Math.floor(currentIndex / slidesPerView);
        dots.forEach((dot, index) => {
            const isActive = index === activeDotIndex;
            if (isActive) {
                dot.classList.remove('bg-gray-300');
                dot.classList.add('bg-purple-600', 'w-8');
                dot.setAttribute('aria-selected', 'true');
                dot.setAttribute('tabindex', '0');
            } else {
                dot.classList.remove('bg-purple-600', 'w-8');
                dot.classList.add('bg-gray-300');
                dot.setAttribute('aria-selected', 'false');
                dot.setAttribute('tabindex', '-1');
            }
        });
        
        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= maxIndex;
        
        // Reset transition flag after animation
        setTimeout(() => {
            isTransitioning = false;
        }, 500);
    }
    
    function goToSlide(index) {
        if (isTransitioning) return;
        currentIndex = Math.max(0, Math.min(index, maxIndex));
        updateCarousel();
        resetAutoPlay();
    }
    
    function nextSlide() {
        if (isTransitioning || currentIndex >= maxIndex) return;
        currentIndex += slidesPerView;
        if (currentIndex > maxIndex) currentIndex = maxIndex;
        updateCarousel();
        resetAutoPlay();
    }
    
    function prevSlide() {
        if (isTransitioning || currentIndex === 0) return;
        currentIndex -= slidesPerView;
        if (currentIndex < 0) currentIndex = 0;
        updateCarousel();
        resetAutoPlay();
    }
    
    function startAutoPlay() {
        if (totalSlides <= slidesPerView) return; // Don't autoplay if all slides visible
        
        autoPlayInterval = setInterval(() => {
            if (currentIndex >= maxIndex) {
                goToSlide(0); // Loop back to start
            } else {
                nextSlide();
            }
        }, autoPlayDelay);
    }
    
    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }
    
    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }
    
    // Event listeners
    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        nextSlide();
    });
    
    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        prevSlide();
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        const carouselContainer = track.closest('.testimonials-carousel-container');
        if (!carouselContainer) return;
        
        // Only handle if carousel is in viewport or focused
        const rect = carouselContainer.getBoundingClientRect();
        const isInViewport = rect.top < window.innerHeight && rect.bottom > 0;
        
        if (!isInViewport) return;
        
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevSlide();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextSlide();
        }
    });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', (e) => {
            e.preventDefault();
            goToSlide(index * slidesPerView);
        });
        
        dot.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                goToSlide(index * slidesPerView);
            }
        });
    });
    
    // Pause autoplay on hover
    const carouselContainer = track.closest('.testimonials-carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', stopAutoPlay);
        carouselContainer.addEventListener('mouseleave', startAutoPlay);
        carouselContainer.addEventListener('focusin', stopAutoPlay);
        carouselContainer.addEventListener('focusout', startAutoPlay);
    }
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const wasMobile = isMobile;
            const nowMobile = window.innerWidth < 768;
            if (wasMobile !== nowMobile) {
                stopAutoPlay();
                location.reload(); // Reload to recalculate
            }
        }, 250);
    });
    
    // Enhanced touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;
    let isSwiping = false;
    let touchStartTime = 0;
    
    track.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        touchStartTime = Date.now();
        isSwiping = false;
        stopAutoPlay();
    }, { passive: true });
    
    track.addEventListener('touchmove', (e) => {
        if (!touchStartX) return;
        
        const touchMoveX = e.changedTouches[0].screenX;
        const touchMoveY = e.changedTouches[0].screenY;
        const diffX = Math.abs(touchMoveX - touchStartX);
        const diffY = Math.abs(touchMoveY - touchStartY);
        
        // Determine if this is a horizontal swipe
        if (diffX > diffY && diffX > 10) {
            isSwiping = true;
            // Add visual feedback during swipe
            const swipeDistance = touchMoveX - touchStartX;
            const maxSwipe = window.innerWidth * 0.3; // Max 30% of screen width
            const opacity = Math.min(1, Math.abs(swipeDistance) / maxSwipe);
            track.style.opacity = 1 - (opacity * 0.3);
        }
    }, { passive: true });
    
    track.addEventListener('touchend', (e) => {
        if (!touchStartX) return;
        
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        const touchDuration = Date.now() - touchStartTime;
        
        // Reset opacity
        track.style.opacity = '1';
        
        handleSwipe(touchDuration);
        
        // Reset touch values
        touchStartX = 0;
        touchStartY = 0;
        isSwiping = false;
        
        // Resume autoplay after a delay
        setTimeout(() => {
            startAutoPlay();
        }, 1000);
    }, { passive: true });
    
    track.addEventListener('touchcancel', () => {
        track.style.opacity = '1';
        touchStartX = 0;
        touchStartY = 0;
        isSwiping = false;
    }, { passive: true });
    
    function handleSwipe(duration) {
        const swipeThreshold = 50; // Minimum distance for swipe
        const fastSwipeThreshold = 200; // Distance for fast swipe
        const fastSwipeDuration = 300; // Max duration for fast swipe (ms)
        
        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;
        const absDiffX = Math.abs(diffX);
        const absDiffY = Math.abs(diffY);
        
        // Only handle horizontal swipes
        if (absDiffX > absDiffY && absDiffX > swipeThreshold) {
            // Fast swipe detection
            const isFastSwipe = absDiffX > fastSwipeThreshold && duration < fastSwipeDuration;
            
            if (diffX > 0) {
                // Swipe left - next
                nextSlide();
            } else {
                // Swipe right - previous
                prevSlide();
            }
        }
    }
    
    // Intersection Observer for auto-play (only play when visible)
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startAutoPlay();
                } else {
                    stopAutoPlay();
                }
            });
        }, { threshold: 0.5 });
        
        if (carouselContainer) {
            observer.observe(carouselContainer);
        }
    } else {
        // Fallback for browsers without IntersectionObserver
        startAutoPlay();
    }
    
    // Initialize
    updateCarousel();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        stopAutoPlay();
    });
})();
</script>
<?php endif; ?>
