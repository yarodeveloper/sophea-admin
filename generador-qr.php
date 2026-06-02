<?php
/**
 * SOPHEA - Generador de Códigos QR
 * 
 * Herramienta gratuita para generar códigos QR a partir de URLs o texto.
 */

// Include configuration
require_once 'config.php';

// SEO Settings specific to this page
$customPageTitle = 'Generador de Códigos QR Gratis | Crea tu código QR gratuito';
$customPageDescription = 'Usa nuestro Generador de Códigos QR gratis. Crea tu código QR gratuito para tu negocio, redes sociales o sitio web en segundos y descárgalo en alta calidad.';
$customPageKeywords = 'generador de qr gratis, Generador Códigos QR, Crea tu código QR gratuito, QR para negocios, marketing digital QR';

// Include header
include 'header.php';
?>

<main class="pt-24 pb-20">
    <!-- Hero Section -->
    <section class="bg-gradient-dark text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-purple-600 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-600 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10 text-center">
            <h1 class="text-4xl md:text-6xl font-black mb-6 leading-tight">
                Generador de <span class="text-gradient">Códigos QR</span> Gratis
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto mb-10">
                Potencia tu marketing digital con nuestra herramienta profesional. Crea tu código QR gratuito en segundos y conecta con tus clientes de forma instantánea.
            </p>
        </div>
    </section>

            <!-- Generator Tool Section -->
    <section class="py-20 -mt-20">
        <style>
            #generate-btn, #download-btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
            }
            .qr-input-field {
                background-color: #f9fafb !important;
                border: 1px solid #e5e7eb !important;
            }
            .qr-input-field:focus {
                border-color: #667eea !important;
                box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2) !important;
            }
        </style>
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-100">
                <!-- Input Side -->
                <div class="w-full md:w-1/2 p-8 md:p-12 border-b md:border-b-0 md:border-r border-gray-100">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                        <i class="ph-bold ph-link text-purple-600"></i>
                        Configura tu QR
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="qr-url" class="block text-sm font-semibold text-gray-600 mb-2">URL o Texto para el QR</label>
                            <input type="text" id="qr-url" placeholder="https://tuweb.com" 
                                   class="qr-input-field w-full px-5 py-4 rounded-2xl outline-none transition-all text-gray-800">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="qr-size" class="block text-sm font-semibold text-gray-600 mb-2">Tamaño (px)</label>
                                <select id="qr-size" class="qr-input-field w-full px-4 py-3 rounded-xl outline-none">
                                    <option value="128">Pequeño (128x128)</option>
                                    <option value="256" selected>Mediano (256x256)</option>
                                    <option value="512">Grande (512x512)</option>
                                    <option value="1024">HD (1024x1024)</option>
                                </select>
                            </div>
                            <div>
                                <label for="qr-color" class="block text-sm font-semibold text-gray-600 mb-2">Color del QR</label>
                                <input type="color" id="qr-color" value="#000000" class="qr-input-field w-full h-12 p-1 rounded-xl cursor-pointer">
                            </div>
                        </div>
 
                        <button id="generate-btn" type="button" 
                                class="w-full py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                            <i class="ph-bold ph-gear"></i>
                            Generar mi QR
                        </button>
                    </div>
                </div>
 
                <!-- Preview Side -->
                <div class="w-full md:w-1/2 p-8 md:p-12 bg-gray-50 flex flex-col items-center justify-center text-center">
                    <div id="qr-preview-container" class="bg-white p-6 rounded-2xl shadow-sm mb-8 relative">
                        <div id="qrcode" class="mx-auto"></div>
                        <div id="placeholder-qr" class="w-[200px] h-[200px] flex items-center justify-center border-2 border-dashed border-gray-200 rounded-xl">
                            <i class="ph ph-qr-code text-6xl text-gray-200"></i>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-6" id="qr-instructions">Ingresa una URL para previsualizar tu código QR profesional</p>
                    
                    <div id="download-actions" class="hidden w-full space-y-3">
                        <button id="download-btn" type="button"
                                class="w-full py-4 rounded-2xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="ph-bold ph-download-simple"></i>
                            Descargar Código QR (PNG)
                        </button>
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
                    <h2 class="text-3xl font-black mb-6 text-gray-900 leading-tight">¿Por qué usar nuestro <span class="text-purple-600">Generador Códigos QR</span>?</h2>
                    <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                        Un código QR es la puerta de entrada física al mundo digital. En <span class="font-bold text-purple-700">SOPHEA</span>, entendemos que cada punto de contacto con tu cliente cuenta. Por eso hemos creado este <strong>generador de qr gratis</strong> de alta calidad.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-purple-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Gratis para siempre:</strong> Sin límites de escaneos ni caducidad.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-purple-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Alta Resolución:</strong> Ideal para impresión en lonas, tarjetas o menús.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ph-bold ph-check-circle text-purple-500 text-xl mt-1"></i>
                            <span class="text-gray-700"><strong>Personalizable:</strong> Ajusta colores y tamaños a tu marca.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 p-10 rounded-3xl border border-gray-100 relative shadow-sm">
                    <div class="absolute -top-6 -right-6 bg-purple-600 text-white p-4 rounded-2xl shadow-xl rotate-12">
                        <i class="ph-bold ph-lightning text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Ideas para usar tu QR</h3>
                    <ul class="space-y-3 text-gray-600">
                        <li>• Menús digitales para restaurantes</li>
                        <li>• Conexión WiFi para clientes</li>
                        <li>• Enlaces a perfiles de WhatsApp</li>
                        <li>• Tarjetas de presentación inteligentes</li>
                        <li>• Promociones especiales en tiendas físicas</li>
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
                <h2 class="text-3xl md:text-5xl font-black mb-8 relative z-10">Lleva tu marca al siguiente nivel con IA</h2>
                <p class="text-xl text-purple-100 max-w-2xl mx-auto mb-10 relative z-10">
                    SOPHEA te ayuda a automatizar tu negocio y blindar tu marketing médico y profesional.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center relative z-10">
                    <a href="<?php echo get_whatsapp_link('Hola, vi la herramienta de QR y me interesa una consultoría'); ?>" 
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

<!-- QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlInput = document.getElementById('qr-url');
        const generateBtn = document.getElementById('generate-btn');
        const qrcodeContainer = document.getElementById('qrcode');
        const placeholderQr = document.getElementById('placeholder-qr');
        const downloadActions = document.getElementById('download-actions');
        const downloadBtn = document.getElementById('download-btn');
        const sizeSelect = document.getElementById('qr-size');
        const colorInput = document.getElementById('qr-color');
        const instructions = document.getElementById('qr-instructions');

        let qrInstance = null;

        function generateQR() {
            const url = urlInput.value.trim();
            if (!url) {
                alert('Por favor ingresa una URL o texto');
                return;
            }

            if (typeof QRCode === 'undefined') {
                alert('Error: La librería QRCode no está disponible.');
                return;
            }

            instructions.innerText = 'Generando tu código QR...';
            qrcodeContainer.innerHTML = '';
            
            const size = parseInt(sizeSelect.value);
            const color = colorInput.value;

            try {
                qrInstance = new QRCode(qrcodeContainer, {
                    text: url,
                    width: size,
                    height: size,
                    colorDark : color,
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
                
                placeholderQr.classList.add('hidden');
                downloadActions.classList.remove('hidden');
                instructions.innerText = '¡Listo! Tu código QR ha sido generado.';
            } catch (err) {
                console.error(err);
                alert('Error al generar el QR');
            }
        }

        if (generateBtn) generateBtn.addEventListener('click', (e) => {
            e.preventDefault();
            generateQR();
        });

        if (downloadBtn) downloadBtn.addEventListener('click', () => {
            const canvas = qrcodeContainer.querySelector('canvas');
            const img = qrcodeContainer.querySelector('img');
            
            try {
                let link = document.createElement('a');
                link.download = 'codigo-qr-sophea.png';
                link.href = canvas ? canvas.toDataURL("image/png") : img.src;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                alert('Error al descargar');
            }
        });

        urlInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') generateQR(); });
    });
</script>

<?php include 'footer.php'; ?>
