<?php
/**
 * Segmentation Tab Content for WhatsApp Marketing Panel
 * 
 * This file contains the segmentation content (tags and contact lists)
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

<!-- Segmentation Section -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined">groups</span>
        Segmentación Avanzada
    </h2>
</div>

<!-- Tabs -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 mb-6">
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex -mb-px">
            <button onclick="showTab('tags')" id="tab-tags-btn" 
                    class="px-6 py-4 text-sm font-medium text-primary border-b-2 border-primary">
                <span class="material-symbols-outlined text-base align-middle">sell</span> Etiquetas
            </button>
            <button onclick="showTab('lists')" id="tab-lists-btn" 
                    class="px-6 py-4 text-sm font-medium text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-primary dark:hover:text-primary">
                <span class="material-symbols-outlined text-base align-middle">list</span> Listas de Contactos
            </button>
        </nav>
    </div>
    
    <!-- Tags Tab -->
    <div id="tab-tags" class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gestión de Etiquetas</h3>
            <button onclick="document.getElementById('create-tag-modal').classList.remove('hidden')" 
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-base">add</span> Nueva Etiqueta
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($allTags as $tag): ?>
            <div class="border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md transition dark:bg-card-dark">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"></div>
                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($tag['name']); ?></span>
                    </div>
                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta etiqueta?');">
                        <input type="hidden" name="action" value="delete_tag">
                        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
                <?php if ($tag['description']): ?>
                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($tag['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($allTags)): ?>
            <div class="col-span-full text-center py-8 text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-6xl mb-2">sell</span>
                <p>No hay etiquetas creadas</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lists Tab -->
    <div id="tab-lists" class="p-6 hidden">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Listas de Contactos</h3>
            <a href="admin_whatsapp_marketing_unified.php?tab=schedule" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-base">add</span> Crear Lista desde Campaña
            </a>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($allLists as $list): ?>
            <div class="border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md transition dark:bg-card-dark">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h4 class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($list['name']); ?></h4>
                        <?php if ($list['description']): ?>
                        <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($list['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 rounded text-sm">
                            <?php echo $list['member_count']; ?> contactos
                        </span>
                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta lista?');">
                            <input type="hidden" name="action" value="delete_list">
                            <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($allLists)): ?>
            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-6xl mb-2">list</span>
                <p>No hay listas creadas</p>
                <p class="text-sm mt-2">Crea listas desde el formulario de campañas</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Tag Modal -->
<div id="create-tag-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-card-dark rounded-xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nueva Etiqueta</h3>
            <button onclick="document.getElementById('create-tag-modal').classList.add('hidden')" 
                    class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">
                <span class="material-symbols-outlined text-2xl">close</span>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_tag">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre *</label>
                <input type="text" name="tag_name" required 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Color</label>
                <input type="color" name="tag_color" value="#667eea" 
                       class="w-full h-12 border border-slate-300 dark:border-slate-700 rounded-lg">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Descripción</label>
                <textarea name="tag_description" rows="3" 
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white"></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-tag-modal').classList.add('hidden')" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Crear
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tab) {
    // Hide all tabs
    document.getElementById('tab-tags').classList.add('hidden');
    document.getElementById('tab-lists').classList.add('hidden');
    document.getElementById('tab-tags-btn').classList.remove('text-primary', 'border-primary');
    document.getElementById('tab-tags-btn').classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent');
    document.getElementById('tab-lists-btn').classList.remove('text-primary', 'border-primary');
    document.getElementById('tab-lists-btn').classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent');
    
    // Show selected tab
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.getElementById('tab-' + tab + '-btn').classList.remove('text-slate-500', 'dark:text-slate-400', 'border-transparent');
    document.getElementById('tab-' + tab + '-btn').classList.add('text-primary', 'border-primary');
}
</script>

