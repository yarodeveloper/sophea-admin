<?php
/**
 * Dashboard Recent Quotes Table
 */
?>
<!-- Section: Quotes Table -->
<div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-4 items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Cotizaciones Recientes</h3>
        <!-- Filters/Chips -->
        <div class="flex gap-2">
            <button class="px-3 py-1.5 rounded-full bg-slate-100 dark:bg-surface-dark text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Todas</button>
            <button class="px-3 py-1.5 rounded-full bg-transparent border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold hover:text-primary transition-colors">Pendientes</button>
            <button class="px-3 py-1.5 rounded-full bg-transparent border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold hover:text-primary transition-colors">Aprobadas</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-surface-dark/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold">
                <tr>
                    <th class="px-6 py-4">Cliente</th>
                    <th class="px-6 py-4">Servicio</th>
                    <th class="px-6 py-4">Fecha Envío</th>
                    <th class="px-6 py-4">Monto</th>
                    <th class="px-6 py-4 text-right">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <?php if (empty($recentQuotes)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                            <?php if (!$quote): ?>
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-300">database</span>
                                    <p>Base de datos no configurada</p>
                                    <p class="text-xs">Ejecuta el script SQL primero</p>
                                </div>
                            <?php else: ?>
                                No hay cotizaciones recientes
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $statusColors = [
                        'sent' => 'amber',
                        'draft' => 'amber',
                        'accepted' => 'emerald',
                        'rejected' => 'red'
                    ];
                    $statusLabels = [
                        'sent' => 'Pendiente',
                        'draft' => 'Borrador',
                        'accepted' => 'Aprobada',
                        'rejected' => 'Rechazada'
                    ];
                    
                    foreach ($recentQuotes as $quoteItem): 
                        $status = $quoteItem['status'] ?? 'draft';
                        $color = $statusColors[$status] ?? 'slate';
                        $label = $statusLabels[$status] ?? ucfirst($status);
                        
                        // Get client initials
                        $clientName = $quoteItem['company_name'] ?? 'Cliente';
                        $initials = strtoupper(substr($clientName, 0, 2));
                        
                        // Get service type icon (simplified)
                        $serviceIcon = 'description';
                        $createdAt = $quoteItem['created_at'] ?? date('Y-m-d');
                        $total = $quoteItem['total'] ?? 0;
                    ?>
                    <tr class="group hover:bg-slate-50 dark:hover:bg-surface-dark/40 transition-colors cursor-pointer" onclick="window.location.href='admin_quotes.php?id=<?php echo $quoteItem['id'] ?? ''; ?>'">
                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
                            <div class="h-8 w-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xs"><?php echo $initials; ?></div>
                            <?php echo htmlspecialchars($clientName); ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]"><?php echo $serviceIcon; ?></span>
                                <?php echo htmlspecialchars($quoteItem['title'] ?? 'Sin título'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                            <?php echo date('d M Y', strtotime($createdAt)); ?>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-200">
                            $<?php echo number_format($total, 0); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700 dark:bg-<?php echo $color; ?>-500/10 dark:text-<?php echo $color; ?>-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-<?php echo $color; ?>-500"></span>
                                <?php echo $label; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 bg-slate-50 dark:bg-surface-dark/20 border-t border-slate-200 dark:border-slate-800 flex justify-center">
        <a href="admin_quotes.php" class="text-xs font-bold text-primary hover:text-primary/80 flex items-center gap-1">
            Ver todas las cotizaciones <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>
</div><?php ?>
