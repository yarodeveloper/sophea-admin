<?php
/**
 * Schedule Tab Content for WhatsApp Marketing Panel
 * 
 * This file contains the schedule campaign creation form
 * Extracted and adapted from admin_whatsapp_marketing.php
 */
?>

<!-- Action Messages -->
<?php if ($actionMessage): ?>
<div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300">
    <div class="flex items-center">
        <span class="material-symbols-outlined text-2xl mr-3">check_circle</span>
        <p><?php echo htmlspecialchars($actionMessage); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($actionError): ?>
<div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
    <div class="flex items-center">
        <span class="material-symbols-outlined text-2xl mr-3">error</span>
        <p><?php echo htmlspecialchars($actionError); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Schedule Section -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined">schedule</span>
        Crear Nueva Campaña
    </h2>
</div>

<form method="POST" action="" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
    <input type="hidden" name="action" value="create_campaign">
    
    <!-- Step 1: Basic Info -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">1. Información Básica</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre de la Campaña *</label>
                <input type="text" name="name" required 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"
                       placeholder="Ej: Promoción Enero 2024">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de Campaña *</label>
                <select name="type" id="campaign_type" required 
                        onchange="updateCampaignTypeFields()"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="cita">Cita</option>
                    <option value="cancelacion">Cancelación</option>
                    <option value="promocion">Promoción</option>
                    <option value="seguimiento">Seguimiento</option>
                    <option value="personalizado" selected>Personalizado</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Step 2: Message -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">2. Mensaje</h3>
        
        <!-- Type-specific fields (hidden by default) -->
        <div id="type-specific-fields" class="mb-4 space-y-4 hidden">
            <!-- Cita fields -->
            <div id="fields-cita" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha de Cita</label>
                    <input type="date" name="filter_criteria[fecha_cita]" 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Hora de Cita</label>
                    <input type="time" name="filter_criteria[hora_cita]" 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Doctor/Especialista</label>
                    <input type="text" name="filter_criteria[doctor]" 
                           placeholder="Ej: Dr. Méndez"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Motivo de Cita</label>
                    <input type="text" name="filter_criteria[motivo]" 
                           placeholder="Ej: Consulta general"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <!-- Cancelación fields -->
            <div id="fields-cancelacion" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha de Cita Cancelada</label>
                    <input type="date" name="filter_criteria[fecha_cita]" 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Hora de Cita Cancelada</label>
                    <input type="time" name="filter_criteria[hora_cita]" 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Doctor/Especialista</label>
                    <input type="text" name="filter_criteria[doctor]" 
                           placeholder="Ej: Dr. Méndez"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Teléfono de Contacto</label>
                    <input type="text" name="filter_criteria[telefono]" 
                           placeholder="Ej: 555-1234"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <!-- Promoción fields -->
            <div id="fields-promocion" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Descuento/Oferta</label>
                    <input type="text" name="filter_criteria[descuento]" 
                           placeholder="Ej: 20% de descuento"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Validez de la Oferta</label>
                    <input type="date" name="filter_criteria[validez]" 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Descripción de la Oferta</label>
                    <input type="text" name="filter_criteria[oferta]" 
                           placeholder="Ej: Promoción especial de enero"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Usar Plantilla</label>
            <select name="template_name" id="template_select" 
                    onchange="updateTemplatePreview()"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                <option value="">-- Selecciona una plantilla (opcional) --</option>
                <optgroup label="Plantillas Predefinidas - Citas" id="templates-cita" class="hidden">
                    <option value="appointment_confirmation_1">Confirmación de Cita</option>
                    <option value="recordatorio_cita">Recordatorio de Cita</option>
                </optgroup>
                <optgroup label="Plantillas Predefinidas - Cancelaciones" id="templates-cancelacion" class="hidden">
                    <option value="appointment_cancellation_1">Cancelación de Cita</option>
                </optgroup>
                <optgroup label="Plantillas Predefinidas - Promociones" id="templates-promocion" class="hidden">
                    <option value="recordatorio">Promoción General</option>
                </optgroup>
                <optgroup label="Plantillas Predefinidas - Seguimiento" id="templates-seguimiento" class="hidden">
                    <option value="tes_unomedic">Bienvenida UNOmedic</option>
                </optgroup>
                <?php if (!empty($allCustomTemplates)): ?>
                <?php
                $templatesByCategory = [];
                foreach ($allCustomTemplates as $template) {
                    $cat = $template['category'];
                    if (!isset($templatesByCategory[$cat])) {
                        $templatesByCategory[$cat] = [];
                    }
                    $templatesByCategory[$cat][] = $template;
                }
                foreach ($templatesByCategory as $category => $templates):
                    $categoryLabels = [
                        'cita' => 'Citas',
                        'cancelacion' => 'Cancelaciones',
                        'promocion' => 'Promociones',
                        'seguimiento' => 'Seguimiento',
                        'bienvenida' => 'Bienvenida',
                        'otro' => 'Otras'
                    ];
                ?>
                <optgroup label="Personalizadas - <?php echo $categoryLabels[$category] ?? ucfirst($category); ?>" id="templates-custom-<?php echo $category; ?>">
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo htmlspecialchars($template['name']); ?>">
                        <?php echo htmlspecialchars($template['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Las plantillas funcionan fuera de la ventana de 24 horas. 
                <a href="admin_whatsapp_marketing_unified.php?tab=templates" class="text-primary hover:underline">Gestionar plantillas</a>
            </p>
            
            <!-- Template Preview -->
            <div id="template-preview" class="mt-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg hidden">
                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Vista Previa:</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line" id="template-preview-text"></p>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Mensaje de Texto *</label>
            <textarea name="message_text" id="message_text" rows="6" required
                      class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"
                      placeholder="Escribe tu mensaje aquí. Puedes usar variables según el tipo de campaña."><?php echo htmlspecialchars($_POST['message_text'] ?? ''); ?></textarea>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                <span id="variables-hint">Variables disponibles: {nombre}, {especialidad}</span>
            </p>
        </div>
    </div>
    
    <!-- Step 3: Segmentation -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">3. Segmentación Avanzada</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado del Lead</label>
                <select name="filter_status" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="">Todos</option>
                    <option value="nuevo">Nuevo</option>
                    <option value="contactado">Contactado</option>
                    <option value="calificado">Calificado</option>
                    <option value="convertido">Convertido</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Especialidad</label>
                <div class="flex space-x-2">
                    <select id="especialidad_select" 
                            onchange="document.getElementById('especialidad_input').value = this.value;"
                            class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="">Seleccionar especialidad...</option>
                        <?php foreach ($allEspecialidades as $esp): ?>
                        <option value="<?php echo htmlspecialchars($esp); ?>">
                            <?php echo htmlspecialchars($esp); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="filter_especialidad" id="especialidad_input"
                           placeholder="O escribir nueva"
                           class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"
                           oninput="document.getElementById('especialidad_select').value = '';">
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Selecciona de la lista o escribe una nueva especialidad</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha Desde</label>
                <input type="date" name="filter_date_from" 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha Hasta</label>
                <input type="date" name="filter_date_to" 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Origen (Source)</label>
                <select name="filter_source" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="">Todos</option>
                    <option value="website">Website</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="referido">Referido</option>
                </select>
            </div>
        </div>
        
        <!-- Etiquetas -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Incluir Leads con Etiquetas</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allTags as $tag): ?>
                <label class="flex items-center space-x-2 px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                    <input type="checkbox" name="filter_tags[]" value="<?php echo $tag['id']; ?>"
                           class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800">
                    <span class="text-sm" style="color: <?php echo htmlspecialchars($tag['color']); ?>">
                        <span class="material-symbols-outlined text-base">sell</span> <?php echo htmlspecialchars($tag['name']); ?>
                    </span>
                </label>
                <?php endforeach; ?>
                <?php if (empty($allTags)): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">No hay etiquetas creadas. <a href="admin_whatsapp_marketing_unified.php?tab=segmentation" class="text-primary hover:underline">Crear etiquetas</a></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Excluir Leads con Etiquetas</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allTags as $tag): ?>
                <label class="flex items-center space-x-2 px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                    <input type="checkbox" name="filter_exclude_tags[]" value="<?php echo $tag['id']; ?>"
                           class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 dark:bg-slate-800">
                    <span class="text-sm" style="color: <?php echo htmlspecialchars($tag['color']); ?>">
                        <span class="material-symbols-outlined text-base">sell</span> <?php echo htmlspecialchars($tag['name']); ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Listas de Contactos -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Usar Lista de Contactos</label>
            <select name="filter_list_id" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                <option value="">Ninguna (usar filtros manuales)</option>
                <?php foreach ($allLists as $list): ?>
                <option value="<?php echo $list['id']; ?>">
                    <?php echo htmlspecialchars($list['name']); ?> (<?php echo $list['member_count']; ?> contactos)
                </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">O crea una nueva lista en la sección de Segmentación</p>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Excluir Lista de Contactos</label>
            <select name="filter_exclude_list_id" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                <option value="">Ninguna</option>
                <?php foreach ($allLists as $list): ?>
                <option value="<?php echo $list['id']; ?>">
                    <?php echo htmlspecialchars($list['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Vista Previa -->
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">
                        <span class="material-symbols-outlined text-base align-middle">info</span> Vista Previa de Destinatarios
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">
                        Los destinatarios se calcularán automáticamente basándose en los filtros seleccionados.
                    </p>
                </div>
                <button type="button" onclick="previewRecipients()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                    <span class="material-symbols-outlined text-base align-middle">visibility</span> Ver Previa
                </button>
            </div>
            <div id="preview-result" class="mt-3 hidden">
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">
                    Destinatarios estimados: <span id="preview-count" class="text-blue-600 dark:text-blue-400">-</span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Step 4: Scheduling -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">4. Programación</h3>
        
        <div class="space-y-4">
            <label class="flex items-center space-x-3 cursor-pointer">
                <input type="checkbox" name="send_immediately" value="1" 
                       class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800"
                       onchange="document.getElementById('scheduled_at').disabled = this.checked; if(this.checked) document.getElementById('scheduled_at').value = '';">
                <span class="text-slate-700 dark:text-slate-300 font-medium">Enviar inmediatamente</span>
            </label>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">O programar para:</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="respect_business_hours" value="1" checked
                           class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800">
                    <span class="text-slate-700 dark:text-slate-300">Respetar horarios de atención (9 AM - 6 PM)</span>
                </label>
                
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="exclude_weekends" value="1"
                           class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800">
                    <span class="text-slate-700 dark:text-slate-300">Excluir fines de semana</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Submit -->
    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200 dark:border-slate-700">
        <a href="admin_whatsapp_marketing_unified.php?tab=campaigns" 
           class="px-6 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
            Cancelar
        </a>
        <button type="submit" 
                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 font-medium">
            <span class="material-symbols-outlined text-base align-middle">check</span> Crear Campaña
        </button>
    </div>
</form>

<script>
// Campaign type definitions
const campaignTypes = {
    'cita': {
        variables: '{nombre}, {especialidad}, {fecha_cita}, {hora_cita}, {doctor}, {motivo}',
        templates: ['appointment_confirmation_1', 'recordatorio_cita'],
        templateNames: {
            'appointment_confirmation_1': 'Confirmación de Cita',
            'recordatorio_cita': 'Recordatorio de Cita'
        }
    },
    'cancelacion': {
        variables: '{nombre}, {especialidad}, {fecha_cita}, {hora_cita}, {doctor}, {telefono}',
        templates: ['appointment_cancellation_1'],
        templateNames: {
            'appointment_cancellation_1': 'Cancelación de Cita'
        }
    },
    'promocion': {
        variables: '{nombre}, {especialidad}, {descuento}, {oferta}, {validez}',
        templates: ['recordatorio'],
        templateNames: {
            'recordatorio': 'Promoción General'
        }
    },
    'seguimiento': {
        variables: '{nombre}, {especialidad}',
        templates: ['tes_unomedic'],
        templateNames: {
            'tes_unomedic': 'Bienvenida/Seguimiento'
        }
    },
    'personalizado': {
        variables: '{nombre}, {especialidad}',
        templates: [],
        templateNames: {}
    }
};

const templatePreviews = {
    'appointment_confirmation_1': 'Buen día {nombre}, Gracias por reservar con {doctor}. Se confirma su cita para {motivo} el {fecha_cita} a las {hora_cita}. Gracias',
    'recordatorio_cita': 'Te recordamos que tu cita con {doctor} es:',
    'appointment_cancellation_1': 'Buen día {nombre}, Tu próxima cita con {doctor} el {fecha_cita} a las {hora_cita} ha sido cancelada. Háganos saber si tiene alguna pregunta o necesita reprogramarla al teléfono {telefono}. Gracias',
    'recordatorio': 'Recordatorio: nuestro técnico visitará su ubicación el {fecha_cita} a las {hora_cita} para su instalación de banda ancha. Por favor, esté disponible.',
    'tes_unomedic': 'Hola, Bienvenido a UNOmedic'
};

// Add custom templates to previews
<?php if (!empty($allCustomTemplates)): ?>
<?php foreach ($allCustomTemplates as $template): ?>
templatePreviews['<?php echo htmlspecialchars($template['name']); ?>'] = <?php echo json_encode($template['template_text']); ?>;
<?php endforeach; ?>
<?php endif; ?>

function updateCampaignTypeFields() {
    const type = document.getElementById('campaign_type').value;
    const typeFields = document.getElementById('type-specific-fields');
    const templateSelect = document.getElementById('template_select');
    const variablesHint = document.getElementById('variables-hint');
    const messageText = document.getElementById('message_text');
    
    // Hide all type-specific fields
    document.querySelectorAll('[id^="fields-"]').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Hide/show type-specific fields
    if (type !== 'personalizado' && type !== 'seguimiento') {
        typeFields.classList.remove('hidden');
        const fieldsId = 'fields-' + type;
        const fields = document.getElementById(fieldsId);
        if (fields) {
            fields.classList.remove('hidden');
        }
    } else {
        typeFields.classList.add('hidden');
    }
    
    // Update template options
    const typeConfig = campaignTypes[type] || campaignTypes['personalizado'];
    
    // Clear predefined template options but keep custom templates
    const customOptgroups = templateSelect.querySelectorAll('optgroup[label^="Personalizadas"]');
    templateSelect.innerHTML = '<option value="">-- Selecciona una plantilla (opcional) --</option>';
    
    // Re-add custom templates
    customOptgroups.forEach(optgroup => {
        templateSelect.appendChild(optgroup.cloneNode(true));
    });
    
    // Add predefined templates for this type
    if (typeConfig.templates.length > 0) {
        const optgroup = document.createElement('optgroup');
        optgroup.label = 'Plantillas Predefinidas - ' + (type.charAt(0).toUpperCase() + type.slice(1));
        typeConfig.templates.forEach(templateName => {
            const option = document.createElement('option');
            option.value = templateName;
            option.textContent = typeConfig.templateNames[templateName] || templateName;
            optgroup.appendChild(option);
        });
        templateSelect.insertBefore(optgroup, templateSelect.firstChild.nextSibling);
    }
    
    // Update variables hint
    variablesHint.textContent = 'Variables disponibles: ' + typeConfig.variables;
    
    // Update message placeholder
    if (type === 'cita') {
        messageText.placeholder = 'Ej: Hola {nombre}, tu cita con {doctor} está programada para el {fecha_cita} a las {hora_cita}. Motivo: {motivo}';
    } else if (type === 'cancelacion') {
        messageText.placeholder = 'Ej: Hola {nombre}, lamentamos informarte que tu cita con {doctor} del {fecha_cita} a las {hora_cita} ha sido cancelada. Contacta al {telefono} para reprogramar.';
    } else if (type === 'promocion') {
        messageText.placeholder = 'Ej: ¡Hola {nombre}! Tenemos una oferta especial: {descuento}. {oferta}. Válido hasta {validez}';
    } else {
        messageText.placeholder = 'Escribe tu mensaje aquí. Puedes usar variables según el tipo de campaña.';
    }
}

function updateTemplatePreview() {
    const templateSelect = document.getElementById('template_select');
    const previewDiv = document.getElementById('template-preview');
    const previewText = document.getElementById('template-preview-text');
    
    const selectedTemplate = templateSelect.value;
    
    if (selectedTemplate && templatePreviews[selectedTemplate]) {
        previewDiv.classList.remove('hidden');
        previewText.textContent = templatePreviews[selectedTemplate];
    } else {
        previewDiv.classList.add('hidden');
    }
}

function previewRecipients() {
    // Collect filter data
    const filters = {
        status: document.querySelector('[name="filter_status"]').value,
        especialidad: document.querySelector('[name="filter_especialidad"]').value,
        date_from: document.querySelector('[name="filter_date_from"]').value,
        date_to: document.querySelector('[name="filter_date_to"]').value,
        source: document.querySelector('[name="filter_source"]').value,
        tags: Array.from(document.querySelectorAll('[name="filter_tags[]"]:checked')).map(c => c.value),
        exclude_tags: Array.from(document.querySelectorAll('[name="filter_exclude_tags[]"]:checked')).map(c => c.value),
        contact_list_id: document.querySelector('[name="filter_list_id"]').value,
        exclude_list_id: document.querySelector('[name="filter_exclude_list_id"]').value
    };
    
    // Show loading
    document.getElementById('preview-result').classList.remove('hidden');
    document.getElementById('preview-count').textContent = 'Calculando...';
    
    // Make AJAX request (simplified - would need an endpoint)
    // For now, just show a placeholder
    setTimeout(() => {
        document.getElementById('preview-count').textContent = 'Usa los filtros y crea la campaña para ver el conteo exacto';
    }, 500);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCampaignTypeFields();
});
</script>

