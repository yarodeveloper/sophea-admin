<?php
/**
 * SOPHEA - Services Catalog Tab Content
 * 
 * Management interface for services catalog
 */

$serviceCatalog = new ServiceCatalog();

// Get all services
$allCatalogServices = $serviceCatalog->getAllServices(['order_by' => 'display_order', 'order_dir' => 'ASC']);

// Get service for editing
$editService = null;
$editServiceId = isset($_GET['edit_service']) ? intval($_GET['edit_service']) : 0;
if ($editServiceId > 0) {
    $editService = $serviceCatalog->getServiceById($editServiceId);
}

$serviceTypeLabels = ServiceCatalog::getServiceTypeLabels();
?>

<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Catálogo de Servicios</h3>
        <button onclick="openNewServiceModal()" 
                class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-lg">add</span>
            Nuevo Servicio
        </button>
    </div>
    <p class="text-sm text-slate-600 dark:text-slate-400">
        Gestiona el catálogo de servicios con precios sugeridos y observaciones. Estos servicios estarán disponibles al crear nuevos servicios para clientes.
    </p>
</div>

<!-- Services Table -->
<div class="overflow-x-auto bg-white dark:bg-card-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
    <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Orden</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Sugerido</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php if (empty($allCatalogServices)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-4xl">inbox</span>
                            <p class="text-lg font-medium">No hay servicios en el catálogo</p>
                            <p class="text-sm">Comienza agregando tu primer servicio</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($allCatalogServices as $service): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                            <?php echo $service['display_order']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </div>
                            <?php if ($service['description']): ?>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    <?php echo htmlspecialchars(substr($service['description'], 0, 60)) . (strlen($service['description']) > 60 ? '...' : ''); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <?php echo $serviceTypeLabels[$service['service_type']] ?? ucfirst($service['service_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                $<?php echo number_format($service['suggested_price'], 2); ?> <?php echo htmlspecialchars($service['currency']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $service['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'; ?>">
                                <?php echo $service['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditServiceModal(<?php echo $service['id']; ?>)" 
                                        class="text-primary hover:text-primary/80" title="Editar">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>')" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Eliminar">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New/Edit Service Modal -->
<div id="serviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modalTitle">Nuevo Servicio</h3>
                <button onclick="closeServiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_tools.php?tab=services_catalog" id="serviceForm" class="p-6">
            <input type="hidden" name="action" value="service_catalog">
            <input type="hidden" name="catalog_action" id="catalogAction" value="create">
            <input type="hidden" name="service_id" id="serviceId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre del Servicio *
                    </label>
                    <input type="text" name="service_name" id="serviceName" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Tipo de Servicio *
                    </label>
                    <select name="service_type" id="serviceType" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($serviceTypeLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Precio Sugerido *
                    </label>
                    <div class="flex gap-2">
                        <input type="number" name="suggested_price" id="suggestedPrice" step="0.01" min="0" required
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <select name="currency" id="currency"
                                class="w-24 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            <option value="MXN">MXN</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Orden de Visualización
                    </label>
                    <input type="number" name="display_order" id="displayOrder" min="0" value="0"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="isActive" value="1" checked
                               class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Servicio Activo</span>
                    </label>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Descripción
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Observaciones
                    </label>
                    <textarea name="observations" id="observations" rows="4"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Notas adicionales sobre el servicio, requisitos, consideraciones especiales, etc.
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeServiceModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Servicio
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openNewServiceModal() {
    document.getElementById('modalTitle').textContent = 'Nuevo Servicio';
    document.getElementById('catalogAction').value = 'create';
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceForm').reset();
    document.getElementById('isActive').checked = true;
    document.getElementById('serviceModal').classList.remove('hidden');
}

function openEditServiceModal(serviceId) {
    window.location.href = 'admin_tools.php?tab=services_catalog&edit_service=' + serviceId;
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.add('hidden');
}

function deleteService(serviceId, serviceName) {
    if (confirm('¿Estás seguro de que deseas eliminar el servicio "' + serviceName + '"? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_tools.php?tab=services_catalog';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'service_catalog';
        form.appendChild(actionInput);
        
        const catalogActionInput = document.createElement('input');
        catalogActionInput.type = 'hidden';
        catalogActionInput.name = 'catalog_action';
        catalogActionInput.value = 'delete';
        form.appendChild(catalogActionInput);
        
        const serviceIdInput = document.createElement('input');
        serviceIdInput.type = 'hidden';
        serviceIdInput.name = 'service_id';
        serviceIdInput.value = serviceId;
        form.appendChild(serviceIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Load edit service data if editing
<?php if ($editService): ?>
document.addEventListener('DOMContentLoaded', function() {
    const service = <?php echo json_encode($editService); ?>;
    
    document.getElementById('modalTitle').textContent = 'Editar Servicio';
    document.getElementById('catalogAction').value = 'update';
    document.getElementById('serviceId').value = service.id;
    document.getElementById('serviceName').value = service.service_name || '';
    document.getElementById('serviceType').value = service.service_type || 'otro';
    document.getElementById('suggestedPrice').value = service.suggested_price || 0;
    document.getElementById('currency').value = service.currency || 'MXN';
    document.getElementById('displayOrder').value = service.display_order || 0;
    document.getElementById('isActive').checked = service.is_active == 1;
    document.getElementById('description').value = service.description || '';
    document.getElementById('observations').value = service.observations || '';
    
    document.getElementById('serviceModal').classList.remove('hidden');
});
<?php endif; ?>

// Close modal on outside click
document.getElementById('serviceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});
</script>

