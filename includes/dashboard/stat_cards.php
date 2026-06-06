<?php
/**
 * Dashboard Stat Cards
 * Displays the main financial and operational metrics
 */
?>
<!-- Main Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Stat 1: Ingresos del Mes -->
    <div onclick="openDashboardDetailModal('paid_income')" class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Ingresos del Mes</p>
            <span class="material-symbols-outlined text-green-600 bg-green-100 dark:bg-green-900/20 p-1.5 rounded-md">payments</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold text-slate-900 dark:text-white">$<?php echo number_format($monthlyRevenue, 0); ?></p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Meta: $<?php echo number_format($monthlyGoal, 0); ?> (<?php echo $goalProgress; ?>%)
        </div>
    </div>
    
    <!-- Stat 2: Ingresos Esperados -->
    <div onclick="openDashboardDetailModal('pending_income')" class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Ingresos Esperados</p>
            <span class="material-symbols-outlined text-blue-600 bg-blue-100 dark:bg-blue-900/20 p-1.5 rounded-md">account_balance</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold text-slate-900 dark:text-white">$<?php echo number_format($monthlyExpectedIncome, 0); ?></p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Pagos pendientes del mes
        </div>
    </div>
    
    <!-- Stat 3: Gastos del Mes -->
    <div onclick="openDashboardDetailModal('expenses')" class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Gastos del Mes</p>
            <span class="material-symbols-outlined text-orange-600 bg-orange-100 dark:bg-orange-900/20 p-1.5 rounded-md">receipt_long</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold text-slate-900 dark:text-white">$<?php echo number_format($monthlyActualExpenses, 0); ?></p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Gastos operativos pagados
        </div>
        <?php if ($monthlyClientServiceCosts > 0): ?>
            <div class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                + $<?php echo number_format($monthlyClientServiceCosts, 0); ?> en costos de servicios
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Stat 4: Utilidad del Mes -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Utilidad del Mes</p>
            <span class="material-symbols-outlined <?php echo $netProfit >= 0 ? 'text-green-600 bg-green-100 dark:bg-green-900/20' : 'text-red-600 bg-red-100 dark:bg-red-900/20'; ?> p-1.5 rounded-md">trending_up</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold <?php echo $netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">$<?php echo number_format($netProfit, 0); ?></p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Ingresos - Gastos
        </div>
    </div>
</div>

<!-- Ads Financial Metrics (if applicable) -->
<?php 
$grossProfit = 0;
$totalCustodyBalance = 0;

if (isset($projectTransaction) && $projectTransaction) {
    try {
        // Utilidad Real Bruta del mes seleccionado
        $grossProfit = $projectTransaction->getGrossProfit(null, 
            sprintf('%04d-%02d-01', $selectedYear, $selectedMonth),
            sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, date('t', strtotime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth))))
        );
        
        // Inversión Total en Ads (solo inversiones, sin restar consumos)
        $allCustodyBalances = $projectTransaction->getAllCustodyBalances();
        foreach ($allCustodyBalances as $balance) {
            $totalCustodyBalance += $balance['total_investment'];
        }
    } catch (Exception $e) {
        error_log("Error fetching Ads financial metrics: " . $e->getMessage());
    }
}
?>

<?php if ($grossProfit > 0 || $totalCustodyBalance != 0): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Utilidad Real Bruta (Honorarios) -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Utilidad Real Bruta</p>
            <span class="material-symbols-outlined text-purple-600 bg-purple-100 dark:bg-purple-900/20 p-1.5 rounded-md">payments</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">$<?php echo number_format($grossProfit, 0); ?></p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Honorarios de gestión (Ads)
        </div>
    </div>
    
    <!-- Inversión Total en Ads -->
    <div class="bg-white dark:bg-card-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Inversión en Ads</p>
            <span class="material-symbols-outlined text-blue-600 bg-blue-100 dark:bg-blue-900/20 p-1.5 rounded-md">account_balance_wallet</span>
        </div>
        <div class="flex items-baseline gap-2">
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                $<?php echo number_format($totalCustodyBalance, 0); ?>
            </p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Total acumulado en inversión publicitaria
        </div>
    </div>
</div>
<?php endif; ?>
