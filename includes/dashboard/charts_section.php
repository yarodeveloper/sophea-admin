<?php
/**
 * Dashboard Graphs Section
 * Contains all Chart.js canvases
 */
?>
<!-- Charts Section -->
<div class="grid grid-cols-1 gap-6 mb-6">
    <!-- Chart Anual Wide: Ventas vs Gastos -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Comparativo Anual: Ventas vs Gastos</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">calendar_today</span>
                <span>Enero - Diciembre</span>
            </div>
        </div>
        <div style="height: 400px; position: relative;">
            <canvas id="yearlyFinanceChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Chart 1: Ingresos vs Egresos (Mejorado) -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Ingresos vs Egresos</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">info</span>
                <span>Mes actual</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="incomeExpensesChart"></canvas>
        </div>
    </div>
    
    <!-- Chart 2: Ingresos vs Gastos por Mes (Comparativo) -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tendencia Financiera</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">trending_up</span>
                <span>Últimos 6 meses</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="revenueExpensesTrendChart"></canvas>
        </div>
    </div>
    
    <!-- Chart 3: Leads por Mes -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Leads por Mes</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">person_add</span>
                <span>Últimos 6 meses</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="leadsChart"></canvas>
        </div>
    </div>
    
    <!-- Chart 4: Conversión de Leads -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Conversión de Leads</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">analytics</span>
                <span>Total histórico</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="conversionChart"></canvas>
        </div>
    </div>
    
    <!-- Chart 5: Distribución de Servicios -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Distribución de Servicios</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">pie_chart</span>
                <span>Por tipo</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="servicesDistributionChart"></canvas>
        </div>
    </div>
    
    <!-- Chart 6: Estado de Pagos -->
    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Estado de Pagos</h3>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-sm">account_balance</span>
                <span>Resumen general</span>
            </div>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="paymentsStatusChart"></canvas>
        </div>
    </div>
</div><?php ?>
