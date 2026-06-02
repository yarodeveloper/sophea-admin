<?php
/**
 * Campaigns Tab Content for WhatsApp Marketing Panel
 * 
 * This file contains the campaigns management content
 */

// Get status filter from URL
$statusFilter = $_GET['status'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get campaigns with filter
$campaigns = $marketing->getCampaigns($limit, $offset, $statusFilter);

// Get campaign details if viewing one
$currentCampaign = null;
$campaignRecipients = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $currentCampaign = $marketing->getCampaign($_GET['view']);
    if ($currentCampaign) {
        $campaignRecipients = $marketing->getCampaignRecipients($_GET['view'], 50);
    }
}
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

<!-- Header with New Campaign Button -->
<div class="mb-6 flex items-center justify-between">
    <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined">campaign</span>
        Gestión de Campañas
    </h3>
    <a href="admin_whatsapp_marketing_unified.php?tab=schedule" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 font-medium flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">add</span>
        Nueva Campaña
    </a>
</div>

<?php if (isset($_GET['view']) && $currentCampaign): ?>
<!-- Campaign Details -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($currentCampaign['name']); ?></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?php 
                $statusLabels = [
                    'draft' => 'Borrador',
                    'scheduled' => 'Programada',
                    'sending' => 'Enviando',
                    'completed' => 'Completada',
                    'paused' => 'Pausada',
                    'cancelled' => 'Cancelada'
                ];
                echo $statusLabels[$currentCampaign['status']] ?? $currentCampaign['status'];
                ?>
            </p>
        </div>
        <a href="admin_whatsapp_marketing_unified.php?tab=campaigns" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            <span class="material-symbols-outlined text-2xl">close</span>
        </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Destinatarios</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($currentCampaign['total_recipients']); ?></p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <p class="text-sm text-blue-600 dark:text-blue-400 mb-1">Enviados</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($currentCampaign['total_sent']); ?></p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
            <p class="text-sm text-emerald-600 dark:text-emerald-400 mb-1">Entregados</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($currentCampaign['total_delivered']); ?></p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">Leídos</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($currentCampaign['total_read']); ?></p>
        </div>
    </div>
    
    <div class="mb-6">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3">Destinatarios</h4>
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 text-left text-slate-700 dark:text-slate-300">Nombre</th>
                        <th class="px-4 py-2 text-left text-slate-700 dark:text-slate-300">Teléfono</th>
                        <th class="px-4 py-2 text-left text-slate-700 dark:text-slate-300">Estado</th>
                        <th class="px-4 py-2 text-left text-slate-700 dark:text-slate-300">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaignRecipients as $recipient): ?>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <td class="px-4 py-2 text-slate-900 dark:text-white"><?php echo htmlspecialchars($recipient['nombre'] ?? 'N/A'); ?></td>
                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($recipient['phone_number']); ?></td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $recipient['status'] === 'sent' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : 
                                    ($recipient['status'] === 'delivered' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300' : 
                                    ($recipient['status'] === 'failed' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300'));
                            ?>">
                                <?php echo htmlspecialchars($recipient['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-slate-500 dark:text-slate-400">
                            <?php echo $recipient['sent_at'] ? date('d/m/Y H:i', strtotime($recipient['sent_at'])) : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Campaigns List -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Filtrar por estado:</label>
                <select onchange="window.location.href='admin_whatsapp_marketing_unified.php?tab=campaigns&status='+this.value" 
                        class="border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    <option value="">Todos</option>
                    <option value="draft" <?php echo ($statusFilter ?? '') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                    <option value="scheduled" <?php echo ($statusFilter ?? '') === 'scheduled' ? 'selected' : ''; ?>>Programada</option>
                    <option value="sending" <?php echo ($statusFilter ?? '') === 'sending' ? 'selected' : ''; ?>>Enviando</option>
                    <option value="completed" <?php echo ($statusFilter ?? '') === 'completed' ? 'selected' : ''; ?>>Completada</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="p-6">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-12">
                <span class="material-symbols-outlined text-6xl text-slate-400 mb-4">campaign</span>
                <p class="text-slate-600 dark:text-slate-400 mb-4">No hay campañas creadas aún</p>
                <a href="admin_whatsapp_marketing_unified.php?tab=schedule" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Crear Primera Campaña
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($campaigns as $campaign): ?>
                <div class="border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($campaign['name']); ?></h3>
                                <span class="px-2 py-1 rounded text-xs <?php 
                                    echo $campaign['status'] === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300' : 
                                        ($campaign['status'] === 'sending' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : 
                                        ($campaign['status'] === 'scheduled' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300'));
                                ?>">
                                    <?php 
                                    $statusLabels = [
                                        'draft' => 'Borrador',
                                        'scheduled' => 'Programada',
                                        'sending' => 'Enviando',
                                        'completed' => 'Completada',
                                        'paused' => 'Pausada',
                                        'cancelled' => 'Cancelada'
                                    ];
                                    echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                                    ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-slate-600 dark:text-slate-400">
                                <div>
                                    <span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($campaign['type']); ?>
                                </div>
                                <div>
                                    <span class="font-medium">Destinatarios:</span> <?php echo number_format($campaign['total_recipients']); ?>
                                </div>
                                <div>
                                    <span class="font-medium">Enviados:</span> <?php echo number_format($campaign['total_sent']); ?>
                                </div>
                                <div>
                                    <span class="font-medium">Creada:</span> <?php echo date('d/m/Y', strtotime($campaign['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-4">
                            <a href="admin_whatsapp_marketing_unified.php?tab=campaigns&view=<?php echo $campaign['id']; ?>" 
                               class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 text-sm">
                                Ver
                            </a>
                            <?php if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled'): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Enviar esta campaña ahora?');">
                                <input type="hidden" name="action" value="send_campaign">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <button type="submit" class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded hover:bg-emerald-200 dark:hover:bg-emerald-900/50 text-sm">
                                    Enviar
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta campaña?');">
                                <input type="hidden" name="action" value="delete_campaign">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <button type="submit" class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 text-sm">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
