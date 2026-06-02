<?php
/**
 * Templates Tab Content for WhatsApp Marketing Panel
 * 
 * This file contains the templates management content
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

<!-- Templates Section -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined">description</span>
        Gestión de Plantillas
    </h2>
    <p class="text-slate-600 dark:text-slate-400 mt-2">Gestiona las plantillas autorizadas por META para WhatsApp Business</p>
</div>

<!-- Plantillas Predefinidas del Sistema -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 mb-6">
    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">check_circle</span>
        Plantillas Predefinidas del Sistema
    </h3>
    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Estas plantillas ya están configuradas y listas para usar:</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $predefinedTemplates = [
            ['name' => 'appointment_confirmation_1', 'category' => 'cita', 'display' => 'Confirmación de Cita'],
            ['name' => 'appointment_cancellation_1', 'category' => 'cancelacion', 'display' => 'Cancelación de Cita'],
            ['name' => 'recordatorio_cita', 'category' => 'cita', 'display' => 'Recordatorio de Cita'],
            ['name' => 'recordatorio', 'category' => 'promocion', 'display' => 'Recordatorio/Promoción'],
            ['name' => 'tes_unomedic', 'category' => 'seguimiento', 'display' => 'Bienvenida UNOmedic']
        ];
        
        foreach ($predefinedTemplates as $template):
        ?>
        <div class="border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md transition dark:bg-card-dark">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($template['display']); ?></h4>
                <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 rounded text-xs">Predefinida</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Nombre: <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded"><?php echo htmlspecialchars($template['name']); ?></code></p>
            <p class="text-xs text-slate-600 dark:text-slate-400">Categoría: <?php echo htmlspecialchars($template['category']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Plantillas Personalizadas -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">add_circle</span>
            Plantillas Personalizadas
        </h3>
        <button onclick="document.getElementById('create-template-modal').classList.remove('hidden')" 
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-base">add</span> Nueva Plantilla
        </button>
    </div>
    
    <?php if (empty($allCustomTemplates)): ?>
    <div class="text-center py-12 text-slate-500 dark:text-slate-400">
        <span class="material-symbols-outlined text-6xl mb-2">description</span>
        <p>No hay plantillas personalizadas creadas</p>
        <p class="text-sm mt-2">Crea una nueva plantilla autorizada por META</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($allCustomTemplates as $template): ?>
        <div class="border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md transition dark:bg-card-dark">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($template['name']); ?></h4>
                <div class="flex items-center space-x-2">
                    <?php if ($template['is_active']): ?>
                    <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 rounded text-xs">Activa</span>
                    <?php else: ?>
                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 rounded text-xs">Inactiva</span>
                    <?php endif; ?>
                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta plantilla?');">
                        <input type="hidden" name="action" value="delete_template">
                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Categoría: <?php echo htmlspecialchars($template['category']); ?></p>
            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2"><?php echo htmlspecialchars(substr($template['template_text'], 0, 100)); ?>...</p>
            <?php if ($template['variables'] && is_array($template['variables'])): ?>
            <div class="mt-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">Variables: <?php echo implode(', ', $template['variables']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Create Template Modal -->
<div id="create-template-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nueva Plantilla Autorizada por META</h3>
            <button onclick="document.getElementById('create-template-modal').classList.add('hidden')" 
                    class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">
                <span class="material-symbols-outlined text-2xl">close</span>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_template">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre de la Plantilla *</label>
                    <input type="text" name="template_name" required 
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white"
                           placeholder="Ej: appointment_confirmation_1">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Debe coincidir exactamente con el nombre en META</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Categoría *</label>
                    <select name="template_category" required 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
                        <option value="cita">Cita</option>
                        <option value="cancelacion">Cancelación</option>
                        <option value="promocion">Promoción</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="bienvenida">Bienvenida</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Texto de la Plantilla *</label>
                <textarea name="template_text" rows="6" required
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white"
                          placeholder="Escribe el texto de la plantilla. Usa {{1}}, {{2}}, etc. para parámetros"></textarea>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Usa {{1}}, {{2}}, {{3}} para indicar parámetros dinámicos</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Variables (separadas por comas)</label>
                <input type="text" name="template_variables" 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white"
                       placeholder="Ej: nombre_cliente, fecha, hora">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Lista de variables que acepta esta plantilla</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="template_active" value="1" checked
                           class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800">
                    <span class="text-slate-700 dark:text-slate-300">Plantilla Activa</span>
                </label>
                
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="template_requires_approval" value="1"
                           class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-800">
                    <span class="text-slate-700 dark:text-slate-300">Requiere Aprobación de META</span>
                </label>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="document.getElementById('create-template-modal').classList.add('hidden')" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Crear Plantilla
                </button>
            </div>
        </form>
    </div>
</div>

