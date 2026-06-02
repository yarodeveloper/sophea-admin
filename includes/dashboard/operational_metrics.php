<?php
/**
 * Dashboard Operational Metrics
 */
?>
<!-- Operational Metrics Section -->
<div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-xl text-primary">dashboard</span>
        Métricas Operativas
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Nuevos Leads -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Nuevos Leads</p>
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-lg">person_add</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $newLeadsThisMonth; ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Conversión: <?php echo $leadsConversionRate; ?>%</p>
        </div>
        
        <!-- Tasa de Cierre -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tasa de Cierre</p>
                <span class="material-symbols-outlined text-purple-400 text-lg">analytics</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $closingRate; ?>%</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cotizaciones aceptadas</p>
        </div>
        
        <!-- Clientes Activos -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Clientes Activos</p>
                <span class="material-symbols-outlined text-primary text-lg">groups</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $activeClientsCount; ?></p>
            <?php if ($newClientsThisMonth > 0): ?>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">+<?php echo $newClientsThisMonth; ?> este mes</p>
            <?php else: ?>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total activos</p>
            <?php endif; ?>
        </div>
        
        <!-- Cotizaciones Pendientes -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Cotizaciones Pendientes</p>
                <span class="material-symbols-outlined text-orange-400 text-lg">pending_actions</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $pendingQuotesCount; ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Valor: $<?php echo number_format($pendingQuotesValue, 0); ?></p>
        </div>
        
        <!-- Avance de Proyectos -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Avance de Proyectos</p>
                <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-lg">trending_up</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $averageProgress; ?>%</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Promedio de proyectos</p>
        </div>
    </div>
</div>

<!-- Previous Month Summary & Remaining Balances (Compact) -->
<div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Mes Anterior: Ingresos -->
        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-xl">history</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Mes Anterior</p>
                <p class="text-base font-bold text-green-600 dark:text-green-400 truncate">
                    $<?php echo number_format($prevMonthRevenue, 0); ?>
                </p>
            </div>
        </div>
        
        <!-- Mes Anterior: Utilidad -->
        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined <?php echo ($prevMonthRevenue - $prevMonthExpenses) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?> text-xl">trending_up</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">M. Ant: Utilidad</p>
                <p class="text-base font-bold <?php echo ($prevMonthRevenue - $prevMonthExpenses) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?> truncate">
                    $<?php echo number_format($prevMonthRevenue - $prevMonthExpenses, 0); ?>
                </p>
            </div>
        </div>
        
        <!-- Pagos Pendientes -->
        <div class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-xl">schedule</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-yellow-700 dark:text-yellow-400 mb-0.5">Pagos Pendientes</p>
                <p class="text-base font-bold text-yellow-900 dark:text-yellow-200 truncate">
                    $<?php echo number_format($totalPendingPayments, 0); ?>
                </p>
            </div>
        </div>
        
        <!-- Costos de Servicios del Mes -->
        <?php if ($monthlyClientServiceCosts > 0): ?>
        <div class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-xl">campaign</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-purple-700 dark:text-purple-400 mb-0.5">Costos Servicios</p>
                <p class="text-base font-bold text-purple-900 dark:text-purple-200 truncate">
                    $<?php echo number_format($monthlyClientServiceCosts, 0); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Gastos Pendientes -->
        <div class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined text-orange-600 dark:text-orange-400 text-xl">receipt_long</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-orange-700 dark:text-orange-400 mb-0.5">Gastos Pendientes</p>
                <p class="text-base font-bold text-orange-900 dark:text-orange-200 truncate">
                    $<?php echo number_format($totalPendingExpenses, 0); ?>
                </p>
            </div>
        </div>
        
        <!-- Meses Restantes (solo si es mes actual) -->
        <?php if ($selectedMonth == $currentMonth && $selectedYear == $currentYear): ?>
            <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-xl">calendar_today</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-blue-700 dark:text-blue-400 mb-0.5">Meses Restantes</p>
                    <p class="text-base font-bold text-blue-900 dark:text-blue-200">
                        <?php echo $monthsRemaining; ?> meses
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Mes Anterior: Gastos (si no es mes actual) -->
            <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-orange-600 dark:text-orange-400 text-xl">receipt</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Gastos Período</p>
                    <p class="text-base font-bold text-orange-600 dark:text-orange-400 truncate">
                        $<?php echo number_format($monthlyActualExpenses, 0); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
