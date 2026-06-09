<?php
/**
 * SOPHEA - Service Types Tab Content
 * 
 * Management interface for service types (categories)
 */

$db = Database::getInstance()->getConnection();

// Get all service types
$stmt = $db->query("SELECT * FROM service_types ORDER BY display_order ASC, name ASC");
$allServiceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get type for editing
$editType = null;
$editTypeId = isset($_GET['edit_type']) ? intval($_GET['edit_type']) : 0;
if ($editTypeId > 0) {
    $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ?");
    $stmt->execute([$editTypeId]);
    $editType = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Tipos de Servicio</h3>
        <button onclick="openNewTypeModal()" 
                class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-lg">add</span>
            Nuevo Tipo
        </button>
    </div>
    <p class="text-sm text-slate-600 dark:text-slate-400">
        Gestiona las categorías o tipos de servicios generales (ej. Hosting, Desarrollo Web, Redes Sociales).
    </p>
</div>

<!-- Types Table -->
<div class="overflow-x-auto bg-white dark:bg-card-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
    <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Orden</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Identificador (Slug)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php if (empty($allServiceTypes)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-4xl">category</span>
                            <p class="text-lg font-medium">No hay tipos de servicio configurados</p>
                            <p class="text-sm">Comienza agregando tu primer tipo</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($allServiceTypes as $type): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                            <?php echo $type['display_order']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($type['name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                            <code><?php echo htmlspecialchars($type['slug']); ?></code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $type['is_active'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'; ?>">
                                <?php echo $type['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditTypeModal(<?php echo $type['id']; ?>)" 
                                        class="text-primary hover:text-primary/80" title="Editar">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <?php if ($type['slug'] !== 'otro'): // Prevent deleting 'otro' ?>
                                <button onclick="deleteType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name']); ?>')" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Eliminar">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New/Edit Type Modal -->
<div id="typeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="typeModalTitle">Nuevo Tipo de Servicio</h3>
                <button onclick="closeTypeModal()" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_tools.php?tab=service_types" id="typeForm" class="p-6">
            <input type="hidden" name="action" value="service_types">
            <input type="hidden" name="type_action" id="typeAction" value="create">
            <input type="hidden" name="id" id="typeId" value="">
            
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre *
                    </label>
                    <input type="text" name="name" id="typeName" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ej. Hosting">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Identificador (Slug)
                    </label>
                    <input type="text" name="slug" id="typeSlug"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"
                           placeholder="Opcional. Ej. hosting_dominio">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Se generará automáticamente si se deja en blanco. Solo minúsculas y guiones bajos.
                    </p>
                </div>
                
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Orden
                        </label>
                        <input type="number" name="display_order" id="typeOrder" min="0" value="0"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div class="flex-1 flex items-end pb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="typeIsActive" value="1" checked
                                   class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Activo</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeTypeModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openNewTypeModal() {
    document.getElementById('typeModalTitle').textContent = 'Nuevo Tipo de Servicio';
    document.getElementById('typeAction').value = 'create';
    document.getElementById('typeId').value = '';
    document.getElementById('typeForm').reset();
    document.getElementById('typeIsActive').checked = true;
    document.getElementById('typeModal').classList.remove('hidden');
}

function openEditTypeModal(id) {
    window.location.href = 'admin_tools.php?tab=service_types&edit_type=' + id;
}

function closeTypeModal() {
    document.getElementById('typeModal').classList.add('hidden');
}

function deleteType(id, name) {
    if (confirm('¿Estás seguro de que deseas eliminar el tipo "' + name + '"? Si hay servicios usando este tipo, podrían no mostrarse correctamente.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_tools.php?tab=service_types';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'service_types';
        form.appendChild(actionInput);
        
        const typeActionInput = document.createElement('input');
        typeActionInput.type = 'hidden';
        typeActionInput.name = 'type_action';
        typeActionInput.value = 'delete';
        form.appendChild(typeActionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Load edit data if editing
<?php if ($editType): ?>
document.addEventListener('DOMContentLoaded', function() {
    const type = <?php echo json_encode($editType); ?>;
    
    document.getElementById('typeModalTitle').textContent = 'Editar Tipo';
    document.getElementById('typeAction').value = 'update';
    document.getElementById('typeId').value = type.id;
    document.getElementById('typeName').value = type.name || '';
    document.getElementById('typeSlug').value = type.slug || '';
    document.getElementById('typeOrder').value = type.display_order || 0;
    document.getElementById('typeIsActive').checked = type.is_active == 1;
    
    document.getElementById('typeModal').classList.remove('hidden');
});
<?php endif; ?>

// Close modal on outside click
document.getElementById('typeModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTypeModal();
    }
});
</script>
