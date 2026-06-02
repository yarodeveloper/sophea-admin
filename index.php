<?php
/**
 * SOPHEA - Home Page
 * 
 * Main landing page for SOPHEA website
 */

// Start session early (before any output) for CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/SiteSettings.php';

// Get site settings
$siteSettings = new SiteSettings();
$mainBanner = $siteSettings->getMainBanner();

// Include header
include 'header.php';
?>

<!-- HERO SECTION WITH BANNER -->
<?php if ($mainBanner): ?>
<section class="relative pt-32 pb-20 px-4 min-h-[600px] md:min-h-[800px] flex items-center overflow-hidden">
    <!-- Banner Background Image -->
    <div class="absolute inset-0 z-0">
        <img src="<?php echo htmlspecialchars($mainBanner); ?>" 
             alt="Banner Principal - <?php echo SITE_NAME; ?>" 
             class="w-full h-full object-cover scale-110 animate-slow-zoom"
             onerror="this.parentElement.parentElement.style.display='none';">
        <!-- Overlay dinámico para mejor legibilidad -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
    </div>
    
    <style>
        @keyframes slow-zoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        .animate-slow-zoom {
            animation: slow-zoom 20s linear infinite alternate;
        }
        .glass-tag {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>

    <!-- Content Overlay -->
    <div class="container mx-auto max-w-6xl relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 items-center">
            <!-- Left Content -->
            <div class="lg:col-span-7 space-y-8 text-white">
                <div class="inline-flex items-center gap-2 px-4 py-1 glass-tag rounded-full text-sm font-bold tracking-wide">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    MÉTODO SOPHEA 2.0
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-7xl font-black leading-tight drop-shadow-2xl">
                    El Único Marketing <br> que Crece con
                    <span class="text-sophea-accent block md:inline mt-2">Total Blindaje.</span>
                </h1>

                <p class="text-lg md:text-2xl text-white/90 leading-relaxed font-medium max-w-2xl">
                    Especialistas en la normativa <span class="bg-white/10 px-2 py-0.5 rounded">COFEPRIS</span>. Multiplica tus citas mientras eliminamos el riesgo legal de tu consultorio.
                </p>

                <div class="flex flex-col sm:flex-row gap-5 pt-4">
                    <a href="#contacto"
                        class="bg-gradient-primary text-white px-10 py-5 rounded-2xl font-black text-center hover:shadow-glow transition-all transform hover:scale-105 shadow-2xl flex items-center justify-center gap-3">
                        Solicitar Auditoría Gratis
                        <i class="ph-bold ph-arrow-right"></i>
                    </a>
                    <a href="#metodo"
                        class="glass-card text-white px-10 py-5 rounded-2xl font-bold text-center hover:bg-white/20 transition-all flex items-center justify-center">
                        <?php echo CTA_SECONDARY; ?>
                    </a>
                </div>

                <!-- Trust Indicators con mejor diseño -->
                <div class="flex flex-wrap gap-4 pt-8">
                    <div class="flex items-center space-x-3 glass-card px-5 py-3 rounded-2xl">
                        <i class="ph-fill ph-check-circle text-sophea-accent text-2xl"></i>
                        <span class="text-sm font-bold">0% Riesgo Regulatorio</span>
                    </div>
                    <div class="flex items-center space-x-3 glass-card px-5 py-3 rounded-2xl">
                        <i class="ph-fill ph-chart-line-up text-blue-400 text-2xl"></i>
                        <span class="text-sm font-bold">+300% Crecimiento</span>
                    </div>
                </div>
            </div>

            <!-- Right Content - Dashboard Mockup Premium -->
            <div class="lg:col-span-5 relative hidden lg:block">
                <div class="glass-card-light rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] p-8 transform hover:-rotate-2 transition-transform duration-700">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 italic">Resultados Reales</h3>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Dashboard 24/7</p>
                        </div>
                        <span class="bg-green-100 text-green-700 px-4 py-1.5 rounded-full text-xs font-black animate-pulse">
                            LIVE DATA
                        </span>
                    </div>

                    <!-- Metrics -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover-lift">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-black uppercase tracking-widest">Growth</p>
                                    <p class="text-4xl font-black text-gray-900 mt-1">+347%</p>
                                    <p class="text-sm text-green-600 font-bold mt-1">Citas Mensuales</p>
                                </div>
                                <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-green-600">
                                    <i class="ph-fill ph-trend-up text-4xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover-lift">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-black uppercase tracking-widest">Compliance</p>
                                    <p class="text-4xl font-black text-gray-900 mt-1">0.0%</p>
                                    <p class="text-sm text-purple-600 font-bold mt-1">Riesgo legal</p>
                                </div>
                                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600">
                                    <i class="ph-fill ph-shield-check text-4xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Floating Elements -->
                <div class="absolute -bottom-6 -left-6 bg-sophea-accent text-gray-900 px-6 py-4 rounded-2xl font-black shadow-xl animate-bounce">
                    5.8x ROI
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<!-- HERO SECTION (without banner) con Gradient Moderno -->
<section class="pt-32 pb-24 px-4 bg-gradient-to-br from-indigo-50 via-white to-purple-50 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-primary opacity-5 rounded-l-full blur-3xl"></div>
    
    <div class="container mx-auto max-w-6xl relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 items-center">
            <!-- Left Content -->
            <div class="lg:col-span-7 space-y-8">
                <div class="inline-flex items-center gap-2 px-4 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-bold tracking-wide">
                    SOPHEA AGENCY
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-7xl font-black leading-tight text-gray-900">
                    El Único Marketing que Crece con
                    <span class="text-gradient block mt-2">Total Blindaje.</span>
                </h1>

                <p class="text-lg md:text-2xl text-gray-600 leading-relaxed font-medium">
                    Especialistas en <strong>COFEPRIS</strong>. Multiplica tus ventas mientras eliminamos el miedo a las multas con estrategias 100% legales.
                </p>

                <div class="flex flex-col sm:flex-row gap-5">
                    <a href="#contacto"
                        class="bg-gradient-primary text-white px-10 py-5 rounded-2xl font-black text-center hover:shadow-glow transition-all transform hover:scale-105 shadow-xl flex items-center justify-center gap-3">
                        Auditoría Gratuita
                        <i class="ph-bold ph-arrow-right"></i>
                    </a>
                    <a href="#metodo"
                        class="border-2 border-gray-200 text-gray-700 px-10 py-5 rounded-2xl font-bold text-center hover:bg-gray-50 transition-all justify-center">
                        <?php echo CTA_SECONDARY; ?>
                    </a>
                </div>
            </div>

            <!-- Right Content - Mockup Simplified -->
            <div class="lg:col-span-5 relative">
                <div class="bg-white rounded-[2.5rem] shadow-2xl p-8 border border-gray-100">
                    <div class="space-y-6">
                        <div class="h-12 bg-gray-50 rounded-xl w-3/4"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="h-32 bg-purple-50 rounded-2xl p-4">
                                <p class="text-3xl font-black text-purple-600">+347%</p>
                                <p class="text-xs font-bold text-purple-400 mt-2 uppercase">Citas</p>
                            </div>
                            <div class="h-32 bg-green-50 rounded-2xl p-4">
                                <p class="text-3xl font-black text-green-600">0%</p>
                                <p class="text-xs font-bold text-green-400 mt-2 uppercase">Multas</p>
                            </div>
                        </div>
                        <div class="h-24 bg-blue-50 rounded-2xl p-4">
                            <p class="text-3xl font-black text-blue-600">5.8x</p>
                            <p class="text-xs font-bold text-blue-400 mt-2 uppercase">Retorno</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MÉTODO SECTION - LUMINA LIGHT THEME -->
<section id="metodo" class="py-24 px-4 bg-white overflow-hidden relative">
    <div class="absolute top-1/2 left-0 w-96 h-96 bg-red-500/5 rounded-full blur-3xl -translate-y-1/2 -translate-x-1/2"></div>
    <div class="absolute top-1/2 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

    <div class="container mx-auto max-w-6xl relative z-10">
        <div class="text-center mb-20">
            <span class="text-primary font-black tracking-widest uppercase text-sm">El Gran Diferenciador</span>
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black mt-4 mb-6 leading-tight text-gray-900">
                El Problema que <br> <span class="text-red-600 underline decoration-red-500/30">Nadie Más Resuelve</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto font-medium">
                En el sector salud, un solo error publicitario puede costarte tu patrimonio y tu cédula profesional.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 lg:gap-12 items-stretch">
            <!-- Problem -->
            <div class="bg-red-50/50 border border-red-100 rounded-[2.5rem] p-8 md:p-12 hover:bg-red-50 transition-all duration-300">
                <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mb-8">
                    <i class="ph-fill ph-warning-circle text-4xl text-red-600"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-6">El Riesgo de lo "Tradicional"</h3>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-x-circle text-red-500 text-xl mt-1"></i>
                        <span class="font-medium">Agencias tradicionales ignoran las regulaciones <strong class="text-red-700">COFEPRIS</strong></span>
                    </li>
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-x-circle text-red-500 text-xl mt-1"></i>
                        <span class="font-medium">Multas de hasta <strong class="text-red-700">$2,000,000 MXN</strong> por publicidad no conforme</span>
                    </li>
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-x-circle text-red-500 text-xl mt-1"></i>
                        <span class="font-medium">Riesgo inminente de suspensión de cédula profesional</span>
                    </li>
                </ul>
            </div>

            <!-- Solution -->
            <div class="bg-primary/5 border border-primary/10 rounded-[2.5rem] p-8 md:p-12 hover:bg-primary/10 transition-all duration-300 shadow-xl shadow-primary/5">
                <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mb-8 shadow-lg shadow-primary/20">
                    <i class="ph-fill ph-shield-check text-4xl text-white"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-6">El Blindaje SOPHEA</h3>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-check-circle text-primary text-xl mt-1"></i>
                        <span class="font-medium">Auditoría completa de compliance sanitario antes de publicar</span>
                    </li>
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-check-circle text-primary text-xl mt-1"></i>
                        <span class="font-medium">Estrategias de crecimiento acelerado <strong class="text-primary">100% legales</strong></span>
                    </li>
                    <li class="flex items-start gap-4">
                        <i class="ph-bold ph-check-circle text-primary text-xl mt-1"></i>
                        <span class="font-medium">Protección permanente de tu marca y prestigio médico</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="text-center mt-16">
            <a href="#contacto"
                class="inline-flex items-center gap-3 bg-gradient-primary text-white px-10 py-5 rounded-2xl font-black text-lg shadow-xl hover:shadow-glow-primary transition-all transform hover:scale-105 active:scale-95">
                Solicitar Análisis de Riesgo Sin Costo
                <i class="ph-bold ph-shield-warning"></i>
            </a>
        </div>
    </div>
</section>


<?php
// Include services section
include 'sections/servicios.php';

// Include specialized medical marketing section (Mockup 2)
include 'sections/medico_featured.php';

// Include case studies section
include 'sections/casos.php';

// Include contact section
include 'sections/contacto.php';

// Include footer
include 'footer.php';
?>
