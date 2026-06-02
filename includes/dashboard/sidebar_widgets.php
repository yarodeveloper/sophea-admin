<?php
/**
 * Dashboard Sidebar Widgets
 */
?>
<!-- Right Column: Sidebar Widgets -->
<div class="flex flex-col gap-6">
    <!-- Renewal Alerts -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Alertas de Renovación</h3>
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-100 text-[10px] font-bold text-orange-600 dark:bg-orange-500/20 dark:text-orange-400">
                <?php echo count($expiringServices ?? []); ?>
            </span>
        </div>
        <div class="flex flex-col gap-4">
            <?php if (empty($expiringServices)): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">
                    No hay renovaciones próximas
                </p>
            <?php else: ?>
                <?php foreach ($expiringServices as $expSvc): 
                    $renewalDate = new DateTime($expSvc['renewal_date']);
                    $today = new DateTime();
                    $diff = $today->diff($renewalDate)->days;
                    $isExpired = $renewalDate < $today;
                    
                    $colorClass = $isExpired ? 'text-red-500' : ($diff <= 3 ? 'text-orange-500' : 'text-blue-500');
                    $bgClass = $isExpired ? 'bg-red-50 dark:bg-red-500/10' : ($diff <= 3 ? 'bg-orange-50 dark:bg-orange-500/10' : 'bg-blue-50 dark:bg-blue-500/10');
                ?>
                <div class="p-3 rounded-lg <?php echo $bgClass; ?> border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all group">
                    <div class="flex justify-between items-start mb-1">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate pr-2"><?php echo htmlspecialchars($expSvc['company_name']); ?></h4>
                        <span class="text-[10px] font-bold <?php echo $colorClass; ?>">
                            <?php echo $isExpired ? 'Vencido' : 'en ' . $diff . ' días'; ?>
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2 truncate"><?php echo htmlspecialchars($expSvc['service_name']); ?></p>
                    
                    <div class="flex gap-2">
                        <?php if ($expSvc['renewal_mode'] === 'manual'): ?>
                            <button onclick="renewService(<?php echo $expSvc['id']; ?>, '<?php echo addslashes($expSvc['company_name']); ?>')" 
                                    class="flex-1 bg-white dark:bg-card-dark text-[10px] font-bold py-1 px-2 rounded border border-slate-200 dark:border-slate-700 hover:bg-primary hover:text-white hover:border-primary transition-colors flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-xs">sync</span>
                                Renovar
                            </button>
                        <?php else: ?>
                            <span class="flex-1 text-center py-1 text-[10px] font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-500/10 rounded">
                                Auto-renovación
                            </span>
                        <?php endif; ?>
                        <a href="admin_client_detail.php?id=<?php echo $expSvc['client_id']; ?>" 
                           class="p-1 rounded border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-xs">visibility</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Revenue Split / Retention -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Origen de Ingresos</h3>
            <div class="bg-primary/10 text-primary text-[10px] font-bold px-2 py-1 rounded">
                <?php echo $monthNames[$selectedMonth-1]; ?>
            </div>
        </div>
        
        <div class="space-y-6">
            <!-- Renewal Revenue -->
            <div>
                <div class="flex justify-between items-end mb-2">
                    <div class="flex flex-col">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Renovaciones (Retención)</span>
                        <span class="text-base font-bold text-slate-900 dark:text-white">$<?php echo number_format($renewalRevenue, 0); ?></span>
                    </div>
                    <span class="text-xs font-bold text-emerald-500"><?php echo $totalMeasuredRevenue > 0 ? round(($renewalRevenue/$totalMeasuredRevenue)*100) : 0; ?>%</span>
                </div>
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: <?php echo $totalMeasuredRevenue > 0 ? ($renewalRevenue/$totalMeasuredRevenue)*100 : 0; ?>%"></div>
                </div>
            </div>
            
            <!-- New Project Revenue -->
            <div>
                <div class="flex justify-between items-end mb-2">
                    <div class="flex flex-col">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Nuevos Proyectos</span>
                        <span class="text-base font-bold text-slate-900 dark:text-white">$<?php echo number_format($newProjectRevenue, 0); ?></span>
                    </div>
                    <span class="text-xs font-bold text-blue-500"><?php echo $totalMeasuredRevenue > 0 ? round(($newProjectRevenue/$totalMeasuredRevenue)*100) : 0; ?>%</span>
                </div>
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: <?php echo $totalMeasuredRevenue > 0 ? ($newProjectRevenue/$totalMeasuredRevenue)*100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-1">Tasa de Fidelidad</p>
                    <p class="text-lg font-black text-primary"><?php echo $retentionRate; ?>%</p>
                </div>
                <div class="h-10 w-10 rounded-full border-4 border-primary/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-sm">verified</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Follow Up / Activity -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Seguimiento Diario</h3>
        <div class="flex flex-col gap-4">
            <?php 
            $allTasks = [];
            if ($dailyTask) {
                try {
                    $allTasks = array_merge($todayTasks, array_slice($upcomingTasks, 0, 3));
                } catch (Exception $e) {
                    // Silently fail if tasks table doesn't exist
                }
            }
            
            if (empty($allTasks)): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">
                    <?php if (!$dailyTask): ?>
                        Base de datos no configurada
                    <?php else: ?>
                        No hay tareas pendientes
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <?php foreach (array_slice($allTasks, 0, 3) as $task): 
                    try {
                        $dueDate = new DateTime($task['due_date'] ?? date('Y-m-d'));
                        $taskToday = new DateTime();
                        $isToday = $dueDate->format('Y-m-d') === $taskToday->format('Y-m-d');
                        $isUrgent = ($task['priority'] ?? 'normal') === 'urgent';
                        $timeClass = $isUrgent ? 'text-red-400' : ($isToday ? 'text-orange-500' : 'text-slate-400');
                    } catch (Exception $e) {
                        $isToday = false;
                        $isUrgent = false;
                        $timeClass = 'text-slate-400';
                    }
                ?>
                <div class="flex gap-3 items-start group">
                    <input type="checkbox" 
                           class="mt-0.5 w-5 h-5 rounded border-2 border-slate-300 dark:border-slate-600 group-hover:border-primary cursor-pointer" 
                           <?php echo ($task['is_completed'] ?? false) ? 'checked' : ''; ?>
                           onchange="toggleTask(<?php echo $task['id'] ?? 0; ?>, this.checked)">
                    <div class="flex flex-col min-w-0">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight truncate"><?php echo htmlspecialchars($task['task_name'] ?? 'Tarea'); ?></p>
                        <?php if (!empty($task['task_description'])): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate"><?php echo htmlspecialchars($task['task_description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($task['due_date'])): ?>
                            <span class="text-[10px] <?php echo $timeClass; ?> font-bold mt-2">
                                <?php 
                                if ($isToday && !empty($task['due_time'])) {
                                    echo 'Hoy, ' . date('g:i A', strtotime($task['due_time']));
                                } elseif ($isUrgent) {
                                    echo 'Urgente';
                                } else {
                                    echo date('d M', strtotime($task['due_date']));
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($task !== end($allTasks)): ?>
                    <hr class="border-slate-100 dark:border-slate-700/50"/>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="admin_daily_tasks.php?action=new" class="w-full mt-6 py-2 rounded-lg border border-dashed border-slate-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold hover:bg-slate-50 dark:hover:bg-surface-dark transition-colors flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[16px]">add</span> Añadir Tarea
        </a>
    </div>
    
    <!-- Mini Promo/Status -->
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary to-blue-600 p-6 shadow-lg shadow-blue-500/20">
        <!-- Abstract pattern -->
        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
        <div class="absolute -left-4 -bottom-4 h-32 w-32 rounded-full bg-blue-400/20 blur-xl"></div>
        <div class="relative z-10">
            <div class="mb-4 inline-flex items-center justify-center rounded-lg bg-white/20 p-2 text-white backdrop-blur-sm">
                <span class="material-symbols-outlined">rocket_launch</span>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">Objetivo Mensual</h3>
            <p class="text-blue-100 text-sm mb-4">
                Estás al <?php echo $goalProgress; ?>% de tu meta de ventas.
            </p>
            <div class="w-full bg-black/20 rounded-full h-2 mb-1">
                <div class="bg-white h-2 rounded-full transition-all duration-500" style="width: <?php echo min($goalProgress, 100); ?>%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-blue-100 font-medium">
                <span>$<?php echo number_format($monthlyRevenue / 1000, 1); ?>k</span>
                <span>Meta: $<?php echo number_format($monthlyGoal / 1000, 0); ?>k</span>
            </div>
        </div>
    </div>
</div><?php ?>
