<?php
/**
 * SOPHEA - Aviso de Privacidad
 * 
 * Página de aviso de privacidad conforme a la Ley Federal de Protección de Datos Personales
 */

require_once 'config.php';
require_once 'config_db.php';
include 'header.php';
?>

<main class="pt-32 pb-20 px-4 bg-gray-50">
    <div class="container mx-auto max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Aviso de Privacidad</h1>
            <p class="text-gray-600">
                <strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?>
            </p>
            <p class="text-gray-600 mt-2">
                En cumplimiento con la <strong>Ley Federal de Protección de Datos Personales en Posesión de los Particulares</strong> 
                (LFPDPPP), ponemos a su disposición el presente Aviso de Privacidad.
            </p>
        </div>

        <!-- Contenido -->
        <div class="bg-white rounded-xl shadow-sm p-8 space-y-8">
            
            <!-- 1. Responsable -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-buildings text-purple-600"></i>
                    <span>1. Responsable del Tratamiento de sus Datos Personales</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed">
                        <strong><?php echo SITE_NAME; ?></strong>, con domicilio en <?php echo CONTACT_ADDRESS; ?>, 
                        es el responsable del tratamiento de sus datos personales.
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        <strong>Datos de contacto:</strong><br>
                        Teléfono: <?php echo CONTACT_PHONE; ?><br>
                        Correo electrónico: <?php echo CONTACT_EMAIL; ?><br>
                        Dirección: <?php echo CONTACT_ADDRESS; ?>
                    </p>
                </div>
            </section>

            <!-- 2. Datos Recopilados -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-database text-purple-600"></i>
                    <span>2. Datos Personales que Recopilamos</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Para las finalidades señaladas en el presente aviso, recopilamos los siguientes datos personales:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li><strong>Datos de identificación:</strong> Nombre completo, edad, fecha de nacimiento</li>
                        <li><strong>Datos de contacto:</strong> Número telefónico, correo electrónico, dirección</li>
                        <li><strong>Datos de navegación:</strong> Dirección IP, tipo de navegador, páginas visitadas, tiempo de permanencia</li>
                        <li><strong>Datos financieros:</strong> Información de facturación (cuando aplique)</li>
                        <li><strong>Datos profesionales:</strong> Ocupación, empresa, área de especialización (cuando aplique)</li>
                    </ul>
                </div>
            </section>

            <!-- 3. Finalidades -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-target text-purple-600"></i>
                    <span>3. Finalidades del Tratamiento de sus Datos Personales</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Sus datos personales serán utilizados para las siguientes finalidades:
                    </p>
                    
                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Finalidades Primarias (Necesarias):</h3>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Prestar los servicios solicitados y dar seguimiento a los mismos</li>
                        <li>Procesar y responder a sus solicitudes de información</li>
                        <li>Establecer comunicación para brindar atención al cliente</li>
                        <li>Realizar el proceso de facturación y cobro de servicios</li>
                        <li>Cumplir con obligaciones contractuales y legales</li>
                        <li>Enviar información sobre nuestros servicios y promociones</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Finalidades Secundarias (Opcionales):</h3>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Realizar estudios de mercado y análisis estadísticos</li>
                        <li>Enviar publicidad y promociones comerciales</li>
                        <li>Realizar encuestas de satisfacción</li>
                        <li>Compartir información con socios comerciales (con su consentimiento)</li>
                    </ul>
                </div>
            </section>

            <!-- 4. Transferencias -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-share-network text-purple-600"></i>
                    <span>4. Transferencias de Datos Personales</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Sus datos personales podrán ser transferidos y tratados por personas distintas a esta empresa 
                        en los siguientes casos:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Proveedores de servicios de hosting y almacenamiento en la nube</li>
                        <li>Proveedores de servicios de email marketing y CRM</li>
                        <li>Autoridades competentes cuando sea requerido por ley</li>
                        <li>Asesores legales y contables para el cumplimiento de obligaciones</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Todas las transferencias se realizan bajo estrictos acuerdos de confidencialidad y protección de datos.
                    </p>
                </div>
            </section>

            <!-- 5. ARCO -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-shield-check text-purple-600"></i>
                    <span>5. Derechos ARCO</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Usted tiene derecho a ejercer en cualquier momento sus derechos de <strong>Acceso, Rectificación, 
                        Cancelación u Oposición</strong> (ARCO) respecto de sus datos personales, mediante:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li><strong>Acceso:</strong> Conocer qué datos personales tenemos de usted y para qué los utilizamos</li>
                        <li><strong>Rectificación:</strong> Solicitar la corrección de sus datos personales si son inexactos o incompletos</li>
                        <li><strong>Cancelación:</strong> Solicitar que eliminemos sus datos personales de nuestros registros</li>
                        <li><strong>Oposición:</strong> Oponerse al tratamiento de sus datos personales para finalidades específicas</li>
                    </ul>
                    <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mt-6 rounded">
                        <p class="text-gray-800 font-semibold mb-2">Para ejercer sus derechos ARCO:</p>
                        <p class="text-gray-700">
                            Envíe su solicitud al correo: <strong><?php echo CONTACT_EMAIL; ?></strong><br>
                            O comuníquese al teléfono: <strong><?php echo CONTACT_PHONE; ?></strong>
                        </p>
                        <p class="text-gray-600 text-sm mt-2">
                            Su solicitud será atendida en un plazo máximo de 20 días hábiles.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 6. Cookies -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-cookie text-purple-600"></i>
                    <span>6. Uso de Cookies y Tecnologías de Rastreo</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Nuestro sitio web utiliza cookies y tecnologías similares. Para más información, 
                        consulte nuestra <a href="politica_cookies.php" class="text-purple-600 hover:underline font-medium">Política de Cookies</a>.
                    </p>
                </div>
            </section>

            <!-- 7. Seguridad -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-lock text-purple-600"></i>
                    <span>7. Medidas de Seguridad</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Implementamos medidas de seguridad técnicas, administrativas y físicas para proteger sus datos personales 
                        contra daño, pérdida, alteración, destrucción o uso no autorizado. Estas medidas incluyen:
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700">
                        <li>Cifrado de datos en tránsito y en reposo</li>
                        <li>Control de acceso mediante autenticación</li>
                        <li>Monitoreo continuo de sistemas</li>
                        <li>Copias de seguridad regulares</li>
                        <li>Capacitación del personal en protección de datos</li>
                    </ul>
                </div>
            </section>

            <!-- 8. Modificaciones -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-clock-clockwise text-purple-600"></i>
                    <span>8. Modificaciones al Aviso de Privacidad</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed">
                        Nos reservamos el derecho de modificar este Aviso de Privacidad. Cualquier cambio será publicado 
                        en esta página con la fecha de última actualización. Le recomendamos revisar periódicamente este aviso.
                    </p>
                </div>
            </section>

            <!-- 9. Consentimiento -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center space-x-2">
                    <i class="ph-fill ph-handshake text-purple-600"></i>
                    <span>9. Consentimiento</span>
                </h2>
                <div class="prose prose-gray max-w-none">
                    <p class="text-gray-700 leading-relaxed">
                        Al proporcionar sus datos personales y utilizar nuestros servicios, usted manifiesta que ha leído, 
                        entendido y acepta los términos de este Aviso de Privacidad, otorgando su consentimiento para el 
                        tratamiento de sus datos personales conforme a lo establecido en el mismo.
                    </p>
                </div>
            </section>

            <!-- Contacto -->
            <section class="bg-gradient-to-r from-purple-50 to-blue-50 p-6 rounded-lg border border-purple-200">
                <h2 class="text-xl font-bold text-gray-900 mb-4">¿Tiene preguntas sobre este Aviso de Privacidad?</h2>
                <p class="text-gray-700 mb-4">
                    Si tiene alguna duda o solicitud relacionada con el tratamiento de sus datos personales, 
                    puede contactarnos:
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

