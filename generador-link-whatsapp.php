<?php
/**
 * SOPHEA - Generador de Links de WhatsApp
 * 
 * Herramienta gratuita para crear enlaces personalizados de WhatsApp.
 */

// Include configuration
require_once 'config.php';

// SEO Settings specific to this page
$customPageTitle = 'Generador de Link de WhatsApp Gratis | Crea tu link personalizado';
$customPageDescription = 'Usa nuestro Generador de Link de WhatsApp gratis. Crea enlaces personalizados con mensajes predeterminados para tu negocio y mejora tu conversión en segundos.';
$customPageKeywords = 'generador de link whatsapp, crear link whatsapp gratis, enlace personalizado whatsapp, link de whatsapp para instagram, marketing whatsapp gratis, generador de mensajes whatsapp';

// Include header
include 'header.php';
?>

<main class="pt-24 pb-20">
    <!-- Hero Section -->
    <section class="bg-gradient-dark text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-green-500 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-emerald-600 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10 text-center">
            <h1 class="text-4xl md:text-6xl font-black mb-6 leading-tight">
                Generador de <span class="text-gradient">Link de WhatsApp</span> Gratis
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto mb-10">
                Crea enlaces directos a tu chat con mensajes personalizados. Una herramienta esencial para captar leads y mejorar la atención al cliente.
            </p>
        </div>
    </section>

    <!-- Generator Tool Section -->
    <section class="py-20 -mt-20">
        <style>
            #generate-link-btn, #copy-link-btn {
                background: linear-gradient(135deg, #128C7E 0%, #25D366 100%) !important;
                color: white !important;
            }
            .wa-input-field {
                background-color: #f9fafb !important;
                border: 1px solid #e5e7eb !important;
            }
            .wa-input-field:focus {
                border-color: #25D366 !important;
                box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2) !important;
            }
        </style>
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-100">
                <!-- Input Side -->
                <div class="w-full md:w-1/2 p-8 md:p-12 border-b md:border-b-0 md:border-r border-gray-100">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                        <i class="ph-bold ph-whatsapp-logo text-green-600"></i>
                        Configura tu Enlace
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="wa-phone" class="block text-sm font-semibold text-gray-600 mb-2">Número de WhatsApp (con código de país)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">+</span>
                                <input type="tel" id="wa-phone" placeholder="529611234567" 
                                       class="wa-input-field w-full pl-8 pr-5 py-4 rounded-2xl outline-none transition-all text-gray-800"
                                       value="52">
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Ejemplo: 52 (México) + tu número de 10 dígitos</p>
                        </div>
                        
                        <div>
                            <label for="wa-message" class="block text-sm font-semibold text-gray-600 mb-2">Mensaje Personalizado (Opcional)</label>
                            <textarea id="wa-message" rows="4" placeholder="Hola, me interesa recibir más información sobre..." 
                                      class="wa-input-field w-full px-5 py-4 rounded-2xl outline-none transition-all text-gray-800 resize-none"></textarea>
                        </div>
 
                        <button id="generate-link-btn" type="button" 
                                class="w-full py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                            <i class="ph-bold ph-link"></i>
                            Generar mi Link
                        </button>
                    </div>
                </div>
 
                <!-- Preview Side -->
                <div class="w-full md:w-1/2 p-8 md:p-12 bg-gray-50 flex flex-col items-center justify-center text-center">
                    <div id="link-preview-container" class="bg-white p-8 rounded-2xl shadow-sm mb-8 w-full border border-gray-100">
                        <div class="flex items-center justify-center mb-4">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="ph-fill ph-whatsapp-logo text-4xl text-green-600"></i>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">Tu enlace está listo</h3>
                        <div id="wa-link-text" class="text-sm text-gray-500 break-all bg-gray-50 p-4 rounded-xl border border-gray-100 mb-4 select-all">
                            https://wa.me/52...
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-6" id="wa-instructions">Configura tu número para generar el enlace de WhatsApp profesional.</p>
                    
                    <div id="link-actions" class="hidden w-full space-y-3">
                        <button id="copy-link-btn" type="button"
                                class="w-full py-4 rounded-2xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="ph-bold ph-copy"></i>
                            Copiar Enlace
                        </button>
                        <a id="test-link-btn" href="#" target="_blank"
                           class="w-full py-4 rounded-2xl font-bold border-2 border-green-600 text-green-600 hover:bg-green-50 transition-all flex items-center justify-center gap-2">
                            <i class="ph-bold ph-arrow-square-out"></i>
                            Probar Enlace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SEO Content Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4 max-w-5xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center mb-20">
                <div>
                    <h2 class="text-3xl font-black mb-6 text-gray-900 leading-tight">¿Por qué crear un <span class="text-green-600">Link de WhatsApp</span> personalizado?</h2>
                    <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                        En la era de la inmediatez, eliminar fricciones es clave. Con nuestro <strong>generador de link whatsapp gratis</strong>, permites que tus clientes te contacten con un solo clic, sin necesidad de guardar tu número.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-green-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Aumenta tus conversiones:</strong> Facilita el inicio de la conversación con mensajes ya escritos.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-green-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Ideal para Bio:</strong> Úsalo en Instagram, Facebook, TikTok o en tus campañas de publicidad digital.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-green-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Profesionalismo:</strong> Envía a tus clientes directamente a tu WhatsApp Business con una bienvenida personalizada.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 p-10 rounded-3xl border border-gray-100 relative shadow-sm">
                    <div class="absolute -top-6 -right-6 bg-green-600 text-white p-4 rounded-2xl shadow-xl rotate-12">
                        <i class="ph-bold ph-chat-circle text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Ventajas del link directo</h3>
                    <ul class="space-y-3 text-gray-600">
                        <li>• No requiere guardar contactos</li>
                        <li>• Funciona en móviles y WhatsApp Web</li>
                        <li>• Puedes identificar la fuente del lead por el mensaje</li>
                        <li>• Enlaces cortos y fáciles de compartir</li>
                        <li>• Herramienta 100% gratuita y sin publicidad</li>
                    </ul>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-gradient-primary rounded-[3rem] p-10 md:p-20 text-center text-white shadow-glow relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 pointer-events-none">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white"></path>
                    </svg>
                </div>
                <h2 class="text-3xl md:text-5xl font-black mb-8 relative z-10">Automatiza tu atención con Chatbots de IA</h2>
                <p class="text-xl text-purple-100 max-w-2xl mx-auto mb-10 relative z-10">
                    SOPHEA integra inteligencia artificial en tu WhatsApp para que nunca pierdas una venta, atendiendo 24/7 con total blindaje regulatorio.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center relative z-10">
                    <a href="<?php echo get_whatsapp_link('Hola, vi la herramienta de Link de WhatsApp y me interesa una consultoría sobre automatización'); ?>" 
                       class="bg-white text-purple-700 px-10 py-5 rounded-full font-black text-lg hover:bg-gray-100 transition-all shadow-xl">
                        Agendar Consultoría Gratuita
                    </a>
                    <a href="servicios.php" class="bg-purple-800/30 backdrop-blur-md text-white border border-white/30 px-10 py-5 rounded-full font-black text-lg hover:bg-purple-800/50 transition-all">
                        Ver Nuestros Servicios
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('wa-phone');
        const messageInput = document.getElementById('wa-message');
        const generateBtn = document.getElementById('generate-link-btn');
        const copyBtn = document.getElementById('copy-link-btn');
        const testLinkBtn = document.getElementById('test-link-btn');
        const linkText = document.getElementById('wa-link-text');
        const linkActions = document.getElementById('link-actions');
        const instructions = document.getElementById('wa-instructions');

        function generateWALink() {
            let phone = phoneInput.value.replace(/\D/g, '');
            const message = messageInput.value.trim();
            
            if (!phone || phone.length < 10) {
                alert('Por favor ingresa un número de teléfono válido con código de país (ej. 52 para México).');
                return;
            }

            let url = `https://wa.me/${phone}`;
            if (message) {
                url += `?text=${encodeURIComponent(message)}`;
            }

            linkText.innerText = url;
            testLinkBtn.href = url;
            linkActions.classList.remove('hidden');
            instructions.innerText = '¡Felicidades! Tu enlace personalizado ha sido creado.';
            
            // Subtle animation scroll to results on mobile
            if (window.innerWidth < 768) {
                linkText.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        generateBtn.addEventListener('click', generateWALink);

        copyBtn.addEventListener('click', function() {
            const text = linkText.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="ph-bold ph-check"></i> ¡Copiado!';
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Error al copiar: ', err);
                alert('No se pudo copiar el enlace. Por favor selecciónalo manualmente.');
            });
        });

        // Allow Enter key to generate
        phoneInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') generateWALink(); });
    });
</script>

<?php include 'footer.php'; ?>
