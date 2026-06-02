<?php
/**
 * SOPHEA - Contact Section (Updated with AJAX)
 * 
 * Displays contact form with AJAX submission and director information
 */

// Start session for CSRF token (must be before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- CONTACTO SECTION (Lumina Light Theme) -->
<section id="contacto" class="py-24 px-4 bg-slate-50 text-gray-900 relative overflow-hidden">
    <!-- Subtle Background Elements -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-primary/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-sophea-accent/5 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="container mx-auto max-w-6xl relative z-10">
        <div class="grid lg:grid-cols-2 gap-16 items-start">
            <!-- Left: Form -->
            <div class="space-y-8">
                <div>
                    <span class="text-primary font-black tracking-widest uppercase text-sm">Hablemos hoy</span>
                    <h2 class="text-4xl md:text-5xl font-black mt-2 mb-4 leading-tight text-gray-900">
                        ¿Listo para Blindar <br> <span class="text-gradient">tu Crecimiento?</span>
                    </h2>
                    <p class="text-xl text-gray-600 font-medium">
                        Completa el formulario y recibe una auditoría preliminar de cumplimiento sin costo.
                    </p>
                </div>

                <!-- Success/Error Messages -->
                <div id="form-message" class="hidden mb-6 p-6 rounded-2xl bg-white shadow-xl border-l-4 border-sophea-accent text-gray-800"></div>

                <form class="space-y-6 bg-white p-8 md:p-10 rounded-[2.5rem] shadow-2xl shadow-gray-200/50 border border-gray-100" id="contact-form" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <!-- Honeypot field -->
                    <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <label for="website_url">Si eres humano, deja este campo vacío</label>
                        <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <!-- Timestamp -->
                    <input type="hidden" name="form_timestamp" id="form_timestamp" value="<?php echo time(); ?>">

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="nombre" class="block text-sm font-black uppercase tracking-widest text-gray-400">Nombre Completo *</label>
                            <input type="text" id="nombre" name="nombre" required
                                class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="Dr. Juan Pérez">
                            <span class="error-message text-red-500 text-sm mt-1 hidden" id="error-nombre"></span>
                        </div>

                        <div class="space-y-2">
                            <label for="whatsapp" class="block text-sm font-black uppercase tracking-widest text-gray-400">WhatsApp *</label>
                            <input type="tel" id="whatsapp" name="whatsapp" required
                                class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="<?php echo CONTACT_PHONE; ?>"
                                pattern="[\d\s\+\-\(\)]+"
                                minlength="10"
                                maxlength="20">
                            <span class="error-message text-red-500 text-sm mt-1 hidden" id="error-whatsapp"></span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="especialidad" class="block text-sm font-black uppercase tracking-widest text-gray-400">Especialidad / Negocio *</label>
                        <input type="text" id="especialidad" name="especialidad" required
                            class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                            placeholder="Ej: Odontología, Clínica Médica">
                        <span class="error-message text-red-500 text-sm mt-1 hidden" id="error-especialidad"></span>
                    </div>

                    <div class="space-y-2">
                        <label for="mensaje" class="block text-sm font-black uppercase tracking-widest text-gray-400">Mensaje (Opcional)</label>
                        <textarea id="mensaje" name="mensaje" rows="4"
                            class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-100 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                            placeholder="Cuéntanos sobre tu proyecto..."></textarea>
                        <span class="error-message text-red-500 text-sm mt-1 hidden" id="error-mensaje"></span>
                    </div>

                    <button type="submit" id="submit-btn"
                        class="w-full bg-gradient-primary text-white px-8 py-5 rounded-2xl font-black text-lg shadow-xl shadow-primary/20 hover:shadow-glow-primary transition-all transform hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btn-text" class="flex items-center justify-center gap-2">
                            Enviar Solicitud <i class="ph-bold ph-paper-plane-right"></i>
                        </span>
                        <span id="btn-loading" class="hidden items-center justify-center gap-2">
                            <i class="ph-bold ph-circle-notch animate-spin"></i> Procesando...
                        </span>
                    </button>

                    <p class="text-xs text-gray-400 text-center font-medium">
                        Tu información está protegida. Respondemos en menos de 24 horas hábiles.
                    </p>
                </form>
            </div>

            <!-- Right: Agenda Consultoría & Contact Info -->
            <div class="space-y-8">
                <!-- Botón de Agendar Cita en Cal.com -->
                <div class="bg-white rounded-[2.5rem] p-10 border border-gray-100 shadow-xl shadow-gray-200/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full group-hover:bg-primary/10 transition-colors"></div>
                    <h3 class="text-2xl font-black mb-4 text-gray-900">Agenda Directamente</h3>
                    <p class="text-gray-500 font-medium mb-8 leading-relaxed">
                        ¿Prefieres una llamada estratégica? Reserva un espacio de 30 minutos en nuestra agenda oficial.
                    </p>
                    <a href="https://cal.com/reunion-consultoria/30min" 
                       target="_blank"
                       class="w-full bg-gray-900 text-white px-8 py-5 rounded-2xl font-black text-lg shadow-lg hover:shadow-2xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-3">
                        <i class="ph-fill ph-calendar-check text-2xl"></i>
                        Ver Disponibilidad
                    </a>
                </div>

                <!-- Contact Information -->
                <div class="bg-white rounded-[2.5rem] p-10 border border-gray-100 shadow-xl shadow-gray-200/50">
                    <h3 class="text-2xl font-black mb-8 text-gray-900">Canales Oficiales</h3>

                    <div class="space-y-6">
                        <div class="flex items-center gap-6 group">
                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                                <i class="ph-fill ph-map-pin text-2xl text-primary"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-1">Dirección Central</p>
                                <p class="text-gray-800 font-bold"><?php echo CONTACT_ADDRESS; ?></p>
                            </div>
                        </div>

                        <?php 
                        // Get contact info from SiteSettings or constants
                        $contactInfo = get_contact_info();
                        ?>
                        
                        <?php if (!empty($contactInfo['phone'])): ?>
                        <div class="flex items-start gap-6 group">
                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center group-hover:bg-green-500/10 transition-colors">
                                <i class="ph-fill ph-phone text-2xl text-green-500"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-1">Teléfono</p>
                                <p class="text-gray-800 font-bold"><?php echo htmlspecialchars($contactInfo['phone']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($contactInfo['email'])): ?>
                        <div class="flex items-start gap-6 group">
                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                                <i class="ph-fill ph-envelope text-2xl text-primary"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-1">Email Oficial</p>
                                <p class="text-gray-800 font-bold"><?php echo htmlspecialchars($contactInfo['email']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-start gap-6 group">
                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center group-hover:bg-amber-500/10 transition-colors">
                                <i class="ph-fill ph-clock text-2xl text-amber-500"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-1">Horario Comercial</p>
                                <p class="text-gray-800 font-bold"><?php echo BUSINESS_HOURS; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Additional Styles for Form Buttons -->
<style>
    #submit-btn {
        border: 2px solid transparent;
    }
    #submit-btn:hover:not(:disabled) {
        border-color: rgba(147, 51, 234, 0.3);
    }
    #submit-btn:focus {
        outline: none;
        ring: 2px;
        ring-color: rgba(147, 51, 234, 0.5);
    }
</style>

<!-- AJAX Form Submission Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const formMessage = document.getElementById('form-message');

    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
        formMessage.classList.add('hidden');

        // Client-side validation for WhatsApp
        const whatsappInput = document.getElementById('whatsapp');
        const whatsappValue = whatsappInput.value.trim();
        const whatsappClean = whatsappValue.replace(/[^0-9+]/g, '');
        const whatsappDigits = whatsappClean.replace(/^\+/, '');
        
        let hasValidationError = false;
        
        if (whatsappDigits.length < 10) {
            const errorEl = document.getElementById('error-whatsapp');
            errorEl.textContent = 'El número de WhatsApp debe tener al menos 10 dígitos';
            errorEl.classList.remove('hidden');
            whatsappInput.focus();
            hasValidationError = true;
        } else if (whatsappDigits.length > 15) {
            const errorEl = document.getElementById('error-whatsapp');
            errorEl.textContent = 'El número de WhatsApp no puede tener más de 15 dígitos';
            errorEl.classList.remove('hidden');
            whatsappInput.focus();
            hasValidationError = true;
        } else if (!/^[\d\s\+\-\(\)]+$/.test(whatsappValue)) {
            const errorEl = document.getElementById('error-whatsapp');
            errorEl.textContent = 'Formato inválido. Solo se permiten números, espacios, +, - y paréntesis';
            errorEl.classList.remove('hidden');
            whatsappInput.focus();
            hasValidationError = true;
        }
        
        // If validation failed, don't submit
        if (hasValidationError) {
            return;
        }

        // Disable submit button (only if validation passed)
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        // Get form data
        const formData = new FormData(contactForm);
        
        // Update timestamp to current time (for time-based validation)
        const timestampField = document.getElementById('form_timestamp');
        if (timestampField) {
            timestampField.value = Math.floor(Date.now() / 1000);
        }

        try {
            // Send AJAX request to process form and save to database
            const response = await fetch('process_form.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Check if response is OK
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }

            const result = await response.json();

            if (result.success) {
                // Show success message
                formMessage.className = 'mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500 text-green-100';
                let successMsg = result.message;
                if (result.lead_id) {
                    successMsg += ' (ID: #' + result.lead_id + ')';
                }
                formMessage.innerHTML = '<i class="ph-fill ph-check-circle mr-2"></i>' + successMsg;
                formMessage.classList.remove('hidden');

                // Scroll to message
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Reset form
                contactForm.reset();

                // Show WhatsApp link option (optional - user can choose)
                if (result.whatsapp_url) {
                    // Add WhatsApp button option
                    const whatsappBtn = document.createElement('div');
                    whatsappBtn.className = 'mt-4 p-4 bg-green-500/20 border border-green-500 rounded-xl';
                    whatsappBtn.innerHTML = `
                        <p class="text-green-100 mb-3">¿Deseas contactarnos por WhatsApp ahora?</p>
                        <a href="${result.whatsapp_url}" 
                           target="_blank"
                           class="inline-flex items-center space-x-2 bg-green-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-green-600 transition-all">
                            <i class="ph-fill ph-whatsapp-logo text-xl"></i>
                            <span>Abrir WhatsApp</span>
                        </a>
                    `;
                    formMessage.appendChild(whatsappBtn);
                    
                    // Optional: Auto-open after 3 seconds (commented out - uncomment if desired)
                    // setTimeout(() => {
                    //     window.open(result.whatsapp_url, '_blank');
                    // }, 3000);
                }
            } else {
                // Show error message
                formMessage.className = 'mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500 text-red-100';
                formMessage.textContent = result.message;
                formMessage.classList.remove('hidden');

                // Show field-specific errors
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const errorEl = document.getElementById(`error-${field}`);
                        if (errorEl) {
                            errorEl.textContent = result.errors[field];
                            errorEl.classList.remove('hidden');
                        }
                    });
                }
            }
        } catch (error) {
            // Show error message
            formMessage.className = 'mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500 text-red-100';
            formMessage.textContent = 'Error al enviar el formulario. Por favor, intenta de nuevo.';
            formMessage.classList.remove('hidden');
            console.error('Form submission error:', error);
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        }
    });
});
</script>
