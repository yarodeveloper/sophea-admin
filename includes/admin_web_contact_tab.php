<?php
/**
 * Contact Info Tab Content for Admin Web Panel
 * 
 * This file contains the contact information management interface
 */

// Ensure variables are set
$contactInfo = $contactInfo ?? [];
?>

<div class="space-y-8">
    <!-- Information Notice -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
            <div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Información de Contacto de SOPHEA</p>
                <p class="text-xs text-blue-700 dark:text-blue-400">
                    Esta información se utilizará en cotizaciones, facturas y otros documentos. También se puede usar para actualizar la página web.
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Information Form -->
    <form method="POST" action="admin_web.php" class="space-y-6">
        <input type="hidden" name="action" value="save_contact_info">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Address -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Dirección de la Empresa *
                </label>
                <textarea name="company_address" rows="3" required
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Ej: Blvd. Antonio Pariente Algarín, Segundo piso, Col. 24, Tuxtla Gutiérrez, Chiapas, México"><?php echo htmlspecialchars($contactInfo['company_address'] ?? ''); ?></textarea>
            </div>
            
            <!-- Company Phone -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Teléfono Móvil *
                </label>
                <input type="text" name="company_phone" required
                       value="<?php echo htmlspecialchars($contactInfo['company_phone'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ej: +52 961 693 3158">
            </div>
            
            <!-- Company Phone WhatsApp -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    <span class="material-symbols-outlined align-middle text-green-600">chat</span>
                    Teléfono WhatsApp
                </label>
                <input type="text" name="company_phone_whatsapp"
                       value="<?php echo htmlspecialchars($contactInfo['company_phone_whatsapp'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ej: +52 961 693 3158">
            </div>
            
            <!-- Company Phone Landline -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    <span class="material-symbols-outlined align-middle">phone</span>
                    Teléfono Fijo
                </label>
                <input type="text" name="company_phone_landline"
                       value="<?php echo htmlspecialchars($contactInfo['company_phone_landline'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ej: +52 961 123 4567">
            </div>
            
            <!-- Company Email -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Email *
                </label>
                <input type="email" name="company_email" required
                       value="<?php echo htmlspecialchars($contactInfo['company_email'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ej: contacto@sopheamkt.com">
            </div>
            
            <!-- Chatbot -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    <span class="material-symbols-outlined align-middle text-purple-600">smart_toy</span>
                    Chatbot (URL o código)
                </label>
                <input type="text" name="company_chatbot"
                       value="<?php echo htmlspecialchars($contactInfo['company_chatbot'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ej: https://chatbot.example.com o código de integración">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">URL del chatbot o código de integración</p>
            </div>
        </div>
        
        <!-- Social Media Section -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">share</span>
                Redes Sociales
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        <span class="material-symbols-outlined align-middle text-blue-600">facebook</span>
                        Facebook
                    </label>
                    <input type="url" name="social_facebook"
                           value="<?php echo htmlspecialchars($contactInfo['social_facebook'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="https://www.facebook.com/sophea.marketing">
                </div>
                
                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        <span class="material-symbols-outlined align-middle text-pink-600">photo_camera</span>
                        Instagram
                    </label>
                    <input type="url" name="social_instagram"
                           value="<?php echo htmlspecialchars($contactInfo['social_instagram'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="https://www.instagram.com/sophea_mkt/">
                </div>
                
                <!-- LinkedIn -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        <span class="material-symbols-outlined align-middle text-blue-700">work</span>
                        LinkedIn
                    </label>
                    <input type="url" name="social_linkedin"
                           value="<?php echo htmlspecialchars($contactInfo['social_linkedin'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="https://www.linkedin.com/company/sophea-mkt/">
                </div>
                
                <!-- YouTube -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        <span class="material-symbols-outlined align-middle text-red-600">play_circle</span>
                        YouTube
                    </label>
                    <input type="url" name="social_youtube"
                           value="<?php echo htmlspecialchars($contactInfo['social_youtube'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="https://www.youtube.com/@sophea_mk">
                </div>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
            <button type="submit" 
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium flex items-center gap-2">
                <span class="material-symbols-outlined">save</span>
                Guardar Información de Contacto
            </button>
        </div>
    </form>
</div>

