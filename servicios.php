<?php
/**
 * SOPHEA - Detailed Services Page
 * 
 * Individual page showcasing all services in detail
 */

// Load configuration
require_once 'config.php';

// Include header
include 'header.php';
?>

<!-- SERVICES HERO -->
<section class="pt-32 pb-20 px-4 bg-gradient-to-br from-purple-50 via-white to-blue-50 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-purple-100/30 to-transparent pointer-events-none"></div>
    <div class="container mx-auto max-w-6xl text-center relative z-10">
        <span class="inline-block px-4 py-1.5 mb-6 text-sm font-bold tracking-widest text-purple-600 uppercase bg-purple-100 rounded-full">Servicios integrales de Marketing.</span>
        <h1 class="text-5xl md:text-6xl lg:text-7xl font-black mb-8 leading-tight text-gray-900">
            Estrategia, Tecnología y <br><span class="text-gradient">Crecimiento Blindado</span>
        </h1>
        <p class="text-xl md:text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
            Nuestro portafolio de especialidades está diseñado para escalar tu marca con la seguridad de un blindaje regulatorio total y tecnología de vanguardia.
        </p>
    </div>
</section>

<!-- SERVICES GRID DETAILED -->
<section class="py-20 bg-white">
    <div class="container mx-auto max-w-7xl px-4">
        
        <!-- Service 1: Chat Bot + IA -->
        <div class="mb-32 grid md:grid-cols-2 gap-16 items-center">
            <div class="order-2 md:order-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-bold tracking-widest text-indigo-600 uppercase bg-indigo-50 rounded-lg">
                    Automatización Inteligente
                </div>
                <h2 class="text-4xl font-black mb-6 text-gray-900">Chat Bot + IA</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    No solo automatizamos respuestas, creamos asistentes virtuales con "cerebro" capaces de entender el contexto, calificar leads y cerrar citas mientras tú descansas.
                </p>
                <div class="space-y-4">
                    <div class="flex items-start gap-4 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                        <i class="ph-bold ph-lightning text-2xl text-indigo-600"></i>
                        <div>
                            <h4 class="font-bold text-gray-900">Respuesta Instantánea</h4>
                            <p class="text-sm text-gray-500">Reduce el tiempo de respuesta de minutos a milisegundos.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                        <i class="ph-bold ph-funnel text-2xl text-indigo-600"></i>
                        <div>
                            <h4 class="font-bold text-gray-900">Calificación de Leads</h4>
                            <p class="text-sm text-gray-500">Filtra automáticamente a tus prospectos más valiosos.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="order-1 md:order-2 bg-gradient-to-br from-indigo-500 to-purple-600 p-1 rounded-[3rem] shadow-2xl">
                <div class="bg-gray-900 rounded-[2.8rem] p-10 h-full flex flex-col items-center justify-center text-center">
                    <i class="ph-fill ph-robot text-8xl text-indigo-400 mb-6"></i>
                    <p class="text-white text-xl font-bold">"Hola, ¿cómo puedo ayudarte hoy?"</p>
                    <div class="mt-8 flex gap-2">
                        <div class="w-3 h-3 bg-indigo-500 rounded-full animate-bounce"></div>
                        <div class="w-3 h-3 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                        <div class="w-3 h-3 bg-indigo-300 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service 2: eCommerce & Diseño Web -->
        <div class="mb-32 grid md:grid-cols-2 gap-16 items-center">
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 p-1 rounded-[3rem] shadow-2xl">
                <div class="bg-white rounded-[2.8rem] p-10 h-full overflow-hidden relative">
                    <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,#fff,rgba(255,255,255,0.6))]"></div>
                    <div class="relative z-10 flex flex-col items-center">
                        <i class="ph-fill ph-shopping-bag-open text-8xl text-purple-600 mb-6"></i>
                        <div class="w-full h-40 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center">
                            <span class="text-gray-400 font-bold italic">Preview UI/UX Moderno</span>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-bold tracking-widest text-purple-600 uppercase bg-purple-50 rounded-lg">
                    Experiencia Digital
                </div>
                <h2 class="text-4xl font-black mb-6 text-gray-900">eCommerce & Diseño Web</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    Un sitio web lento es dinero perdido. Diseñamos plataformas ultrarrápidas, Mobile-First y optimizadas para que el usuario nunca quiera irse.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-purple-50 rounded-2xl">
                        <p class="text-2xl font-black text-purple-600">0.8s</p>
                        <p class="text-xs text-purple-900/60 font-bold uppercase tracking-tighter">Carga Promedio</p>
                    </div>
                    <div class="p-4 bg-purple-50 rounded-2xl">
                        <p class="text-2xl font-black text-purple-600">+45%</p>
                        <p class="text-xs text-purple-900/60 font-bold uppercase tracking-tighter">Tasa de Conversión</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service 3: Desarrollo de Apps & Sistemas -->
        <div class="mb-32 grid md:grid-cols-2 gap-16 items-center">
            <div class="order-2 md:order-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 rounded-lg">
                    Ingeniería de Software
                </div>
                <h2 class="text-4xl font-black mb-6 text-gray-900">Apps & Sistemas</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    Desde apps para iOS y Android hasta sistemas administrativos que controlan inventarios y dashboards en tiempo real. Si puedes soñarlo, nosotros lo programamos.
                </p>
                <ul class="space-y-4 text-gray-700 font-medium">
                    <li class="flex items-center gap-3"><i class="ph-bold ph-cpu text-blue-600"></i> Arquitectura en la Nube Escalable</li>
                    <li class="flex items-center gap-3"><i class="ph-bold ph-database text-blue-600"></i> Seguridad Bancaria de Grado</li>
                    <li class="flex items-center gap-3"><i class="ph-bold ph-app-window text-blue-600"></i> Integraciones API de Terceros</li>
                </ul>
            </div>
            <div class="order-1 md:order-2 bg-gray-900 rounded-[3rem] p-12 shadow-2xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-transparent"></div>
                <div class="relative z-10">
                    <code class="text-blue-400 text-sm block mb-4">// Compiling excellence...</code>
                    <div class="space-y-2">
                        <div class="h-2 w-full bg-blue-900/50 rounded-full overflow-hidden">
                            <div class="h-full w-3/4 bg-blue-500 rounded-full group-hover:w-full transition-all duration-1000"></div>
                        </div>
                        <div class="h-2 w-full bg-blue-900/50 rounded-full overflow-hidden">
                            <div class="h-full w-1/2 bg-blue-500 rounded-full group-hover:w-4/5 transition-all duration-1000"></div>
                        </div>
                    </div>
                    <i class="ph-fill ph-code-block text-9xl text-white/10 absolute -right-4 -bottom-4"></i>
                </div>
            </div>
        </div>

        <!-- Service 4: Publicidad Digital -->
        <div class="mb-32 grid md:grid-cols-2 gap-16 items-center">
            <div class="bg-emerald-600 rounded-[3rem] p-1 shadow-2xl">
                <div class="bg-white rounded-[2.8rem] p-12 text-center h-full">
                    <div class="relative inline-block mb-6">
                        <i class="ph-fill ph-chart-line-up text-8xl text-emerald-600"></i>
                    </div>
                    <h4 class="text-3xl font-black text-gray-900">$ Multiplicador de ROI</h4>
                    <p class="text-gray-500 mt-2">Maximizamos cada peso de inversión</p>
                </div>
            </div>
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-bold tracking-widest text-emerald-600 uppercase bg-emerald-50 rounded-lg">
                    Growth Marketing
                </div>
                <h2 class="text-4xl font-black mb-6 text-gray-900">Publicidad Digital</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    Invertir en Ads no es gastar, es comprar clientes. Dominamos Google Ads, Meta Ads y TikTok Ads con estrategias que realmente traen retorno.
                </p>
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <span class="font-bold text-gray-700">Google Search Ads</span>
                        <div class="flex gap-1 text-emerald-500"><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i></div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <span class="font-bold text-gray-700">Meta Video Ads</span>
                        <div class="flex gap-1 text-emerald-500"><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Spotlight: Marketing Médico & COFEPRIS -->
        <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-[4rem] p-12 md:p-20 text-white shadow-3xl relative overflow-hidden mb-32">
            <div class="absolute top-0 right-0 w-1/2 h-full opacity-10 pointer-events-none translate-x-1/4">
                <i class="ph-fill ph-stethoscope text-[30rem]"></i>
            </div>
            <div class="relative z-10 max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-5 py-2 mb-8 text-sm font-black tracking-widest text-red-600 uppercase bg-white rounded-full shadow-xl">
                    <i class="ph-fill ph-shield-check"></i> El Único Servicio 100% Blindado
                </div>
                <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                    Marketing Médico y Regulación <span class="bg-white text-red-700 px-4">COFEPRIS</span>
                </h2>
                <p class="text-xl md:text-2xl text-red-50 leading-relaxed mb-12">
                    Somos expertos navegando la compleja normativa de salud en México. Publicidad ética, profesional y, sobre todo, legal. Evita multas millonarias y crece con paz mental.
                </p>
                <div class="grid md:grid-cols-3 gap-8 mb-12">
                    <div class="bg-white/10 backdrop-blur-lg p-6 rounded-3xl border border-white/20">
                        <i class="ph ph-mask-happy text-4xl mb-4"></i>
                        <h4 class="font-bold">Ética Médica</h4>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg p-6 rounded-3xl border border-white/20">
                        <i class="ph ph-hand-coins text-4xl mb-4"></i>
                        <h4 class="font-bold">Cero Multas</h4>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg p-6 rounded-3xl border border-white/20">
                        <i class="ph ph-users-four text-4xl mb-4"></i>
                        <h4 class="font-bold">Captación Segura</h4>
                    </div>
                </div>
                <a href="#contacto" class="inline-flex items-center gap-4 bg-white text-red-700 px-12 py-6 rounded-full font-black text-xl hover:bg-gray-100 transition-all transform hover:scale-105 shadow-2xl">
                    Solicitar Auditoría de Riesgo Gratuita
                    <i class="ph-bold ph-file-search"></i>
                </a>
            </div>
        </div>

    </div>
</section>

<!-- FINAL CALL TO ACTION -->
<section class="py-24 px-4 bg-gray-900 text-white text-center">
    <div class="container mx-auto max-w-4xl">
        <h2 class="text-4xl md:text-6xl font-black mb-8">¿Cuál es tu próximo gran paso?</h2>
        <p class="text-xl text-gray-400 mb-12 leading-relaxed">
            Ya sea que necesites un chatbot con IA o un blindaje regulatorio total, estamos listos para construir el futuro de tu marca hoy mismo.
        </p>
        <div class="flex flex-col sm:flex-row gap-6 justify-center">
            <a href="#contacto" class="bg-gradient-primary px-10 py-5 rounded-full font-black text-xl shadow-lg hover:shadow-purple-500/50 transition-all transform hover:scale-105">
                Empezar Proyecto
            </a>
            <a href="<?php echo get_whatsapp_link('Hola, me interesan sus servicios profesionales'); ?>" target="_blank" class="border-2 border-slate-700 px-10 py-5 rounded-full font-black text-xl hover:bg-slate-800 transition-all">
                Hablar con un Experto
            </a>
        </div>
    </div>
</section>

<?php
// Include contact section
include 'sections/contacto.php';

// Include footer
include 'footer.php';
?>
