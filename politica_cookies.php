<?php
/**
 * SOPHEA - Política de Cookies
 * 
 * Página de política de cookies y tecnologías de rastreo
 */

require_once 'config.php';
require_once 'config_db.php';
include 'header.php';
?>

<main class="pt-32 pb-20 px-4 bg-gray-50">
    <div class="container mx-auto max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Política de Cookies</h1>
            <p class="text-gray-600">
                <strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?>
            </p>
            <p class="text-gray-600 mt-2">
                Esta Política de Cookies explica qué son las cookies, cómo las utilizamos en nuestro sitio web 
                y cómo puede gestionarlas.
            </p>
        </div>

        <!-- Contenido -->
        <div class="bg-white rounded-xl shadow-sm p-8 space-y-8">
            
            <!-- 1. Qué son las cookies -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-info text-purple-600"></i>
                    <span>1. ¿Qué son las Cookies?</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed">
                        Las cookies son pequeños archivos de texto que se almacenan en su dispositivo (computadora, 
                        tablet o móvil) cuando visita un sitio web. Las cookies permiten que el sitio web recuerde 
                        sus acciones y preferencias durante un período de tiempo, por lo que no tiene que volver 
                        a configurarlas cada vez que regresa al sitio o navega de una página a otra.
                    </p>
                </div>
            </section>

            <!-- 2. Tipos de cookies -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-list-bullets text-purple-600"></i>
                    <span>2. Tipos de Cookies que Utilizamos</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    
                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">2.1. Cookies Técnicas (Necesarias)</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Estas cookies son esenciales para el funcionamiento del sitio web y no pueden desactivarse. 
                        Incluyen:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li><strong>Cookies de sesión:</strong> Mantienen su sesión activa mientras navega</li>
                        <li><strong>Cookies de seguridad:</strong> Protegen contra accesos no autorizados</li>
                        <li><strong>Cookies de preferencias:</strong> Recuerdan sus configuraciones (idioma, región)</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">2.2. Cookies de Análisis</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Nos ayudan a entender cómo los visitantes interactúan con nuestro sitio web, recopilando 
                        información de forma anónima:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Páginas visitadas y tiempo de permanencia</li>
                        <li>Origen del tráfico</li>
                        <li>Dispositivo y navegador utilizado</li>
                        <li>Errores encontrados</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">2.3. Cookies de Funcionalidad</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Permiten que el sitio web recuerde las elecciones que hace (como su nombre de usuario, 
                        idioma o región) para proporcionar características mejoradas y más personalizadas:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Preferencias de idioma</li>
                        <li>Configuraciones de accesibilidad</li>
                        <li>Información de formularios guardada</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">2.4. Cookies de Marketing</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Se utilizan para rastrear a los visitantes a través de diferentes sitios web con la intención 
                        de mostrar anuncios relevantes. Estas cookies requieren su consentimiento:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Cookies de publicidad personalizada</li>
                        <li>Cookies de redes sociales</li>
                        <li>Cookies de remarketing</li>
                    </ul>
                </div>
            </section>

            <!-- 3. Cookies específicas -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-cookie text-purple-600"></i>
                    <span>3. Cookies Específicas que Utilizamos</span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cookie</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propósito</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">cookie_consent</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Almacena su preferencia de cookies</td>
                                <td class="px-4 py-3 text-sm text-gray-700">1 año</td>
                                <td class="px-4 py-3 text-sm"><span class="px-2 py-1 bg-green-100 text-green-800 rounded">Necesaria</span></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">PHPSESSID</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Mantiene la sesión del usuario</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Sesión</td>
                                <td class="px-4 py-3 text-sm"><span class="px-2 py-1 bg-green-100 text-green-800 rounded">Necesaria</span></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">_ga</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Google Analytics - Análisis de tráfico</td>
                                <td class="px-4 py-3 text-sm text-gray-700">2 años</td>
                                <td class="px-4 py-3 text-sm"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Análisis</span></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">_gid</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Google Analytics - Identificación única</td>
                                <td class="px-4 py-3 text-sm text-gray-700">24 horas</td>
                                <td class="px-4 py-3 text-sm"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Análisis</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 4. Gestión de cookies -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-sliders text-purple-600"></i>
                    <span>4. Cómo Gestionar las Cookies</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Puede gestionar sus preferencias de cookies de las siguientes formas:
                    </p>
                    
                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">4.1. Desde nuestro sitio web</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Puede modificar sus preferencias de cookies en cualquier momento haciendo clic en el botón 
                        "Configurar Cookies" en el banner de cookies que aparece en la parte inferior de la página.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">4.2. Desde su navegador</h3>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        La mayoría de los navegadores permiten gestionar las preferencias de cookies. Puede configurar 
                        su navegador para rechazar cookies o para que le avise cuando un sitio web intenta colocar una cookie:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li><strong>Chrome:</strong> Configuración → Privacidad y seguridad → Cookies</li>
                        <li><strong>Firefox:</strong> Opciones → Privacidad y seguridad → Cookies y datos del sitio</li>
                        <li><strong>Safari:</strong> Preferencias → Privacidad → Cookies y datos de sitios web</li>
                        <li><strong>Edge:</strong> Configuración → Cookies y permisos del sitio</li>
                    </ul>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4 rounded">
                        <p class="text-yellow-800 text-sm">
                            <strong>Nota importante:</strong> Si desactiva las cookies, algunas funcionalidades del sitio 
                            web pueden no estar disponibles o no funcionar correctamente.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 5. Cookies de terceros -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-share-network text-purple-600"></i>
                    <span>5. Cookies de Terceros</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Algunas cookies son colocadas por servicios de terceros que aparecen en nuestras páginas:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li><strong>Google Analytics:</strong> Para análisis de tráfico y comportamiento de usuarios</li>
                        <li><strong>Redes sociales:</strong> Botones de compartir y widgets de redes sociales</li>
                        <li><strong>Servicios de chat:</strong> Para atención al cliente en tiempo real</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        No controlamos las cookies de terceros. Le recomendamos revisar las políticas de privacidad 
                        y cookies de estos terceros para obtener más información.
                    </p>
                </div>
            </section>

            <!-- 6. Actualizaciones -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-clock-clockwise text-purple-600"></i>
                    <span>6. Actualizaciones de esta Política</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed">
                        Podemos actualizar esta Política de Cookies ocasionalmente para reflejar cambios en las cookies 
                        que utilizamos o por otras razones operativas, legales o regulatorias. Le recomendamos revisar 
                        esta página periódicamente para estar informado sobre nuestro uso de cookies.
                    </p>
                </div>
            </section>

            <!-- Contacto -->
            <section class="bg-gradient-to-r from-purple-50 to-blue-50 p-6 rounded-lg border border-purple-200">
                <h2 class="text-xl font-bold text-gray-900 mb-4">¿Tiene preguntas sobre nuestra Política de Cookies?</h2>
                <p class="text-gray-700 mb-4">
                    Si tiene alguna pregunta sobre el uso de cookies en nuestro sitio web, puede contactarnos:
                </p>
                <div class="space-y-2 text-gray-700">
                    <p><i class="ph-fill ph-envelope text-purple-600"></i> <strong>Email:</strong> <?php echo CONTACT_EMAIL; ?></p>
                    <p><i class="ph-fill ph-phone text-purple-600"></i> <strong>Teléfono:</strong> <?php echo CONTACT_PHONE; ?></p>
                    <p><i class="ph-fill ph-map-pin text-purple-600"></i> <strong>Dirección:</strong> <?php echo CONTACT_ADDRESS; ?></p>
                </div>
            </section>

        </div>

        <!-- Botón Volver -->
        <div class="mt-8 text-center">
            <a href="index.php" class="inline-flex items-center space-x-2 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                <i class="ph ph-arrow-left"></i>
                <span>Volver al Inicio</span>
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

