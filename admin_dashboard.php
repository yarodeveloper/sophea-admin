<?php
/**
 * SOPHEA - Admin Dashboard
 * Refactored Version
 */

require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Panel de Gestión - Sophea';

// Load required classes
require_once 'classes/Client.php';
require_once 'classes/Quote.php';
require_once 'classes/Payment.php';
require_once 'classes/Service.php';
require_once 'classes/Expense.php';
require_once 'classes/DailyTask.php';
require_once 'classes/ProjectTransaction.php';

// Initialize variables
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$currentMonth = date('m');
$currentYear = date('Y');

// Month names helper
$monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

try {
    $client = new Client();
    $quote = new Quote();
    $payment = new Payment();
    $service = new Service();
    $expense = new Expense();
    $dailyTask = new DailyTask();
    $db = Database::getInstance();
    $projectTransaction = new ProjectTransaction();

    // 1. Fetch Basic Metrics
    $activeClientsCount = $client->getActiveCount();
    $pendingQuotesCount = $quote->getPendingCount();
    $pendingQuotesValue = $quote->getPendingValue();
    
    // 2. Financial Metrics for Selected Month
    $monthlyRevenue = $payment->getMonthlyRevenue($selectedYear, $selectedMonth);
    $monthlyExpectedIncome = $service->getTotalExpectedIncome();
    $monthlyActualExpenses = $expense->getMonthlyExpenses($selectedYear, $selectedMonth);
    $monthlyGoal = 35000;
    
    // 3. Lead & Client Metrics
    $newLeadsThisMonth = $db->getNewLeadsCount($selectedYear, $selectedMonth);
    $leadsConversionRate = $db->getLeadsConversionRate();
    $newClientsThisMonth = $client->getNewClientsCount($selectedYear, $selectedMonth);
    
    // 4. Closing Rate Calculation
    $allQuotesFilter = ['limit' => 1000];
    $totalQuotesCount = count($quote->getAllQuotes($allQuotesFilter));
    $acceptedQuotesCount = count($quote->getAllQuotes(array_merge($allQuotesFilter, ['status' => 'accepted'])));
    $closingRate = $totalQuotesCount > 0 ? round(($acceptedQuotesCount / $totalQuotesCount) * 100) : 0;
    
    // 5. Project Progress
    $progressData = $service->getAverageProgress();
    $averageProgress = $progressData['average'];
    
    // 6. Net Profit & Goals
    $netProfit = $monthlyRevenue - $monthlyActualExpenses;
    $goalProgress = $monthlyGoal > 0 ? round(($monthlyRevenue / $monthlyGoal) * 100) : 0;
    
    // 7. Previous Month Comparison
    $prevMonthDate = new DateTime("{$selectedYear}-{$selectedMonth}-01");
    $prevMonthDate->modify('-1 month');
    $prevMonth = intval($prevMonthDate->format('m'));
    $prevYear = intval($prevMonthDate->format('Y'));
    $prevMonthRevenue = $payment->getMonthlyRevenue($prevYear, $prevMonth);
    $prevMonthExpenses = $expense->getMonthlyExpenses($prevYear, $prevMonth);
    
    // 8. Pending Totals
    $totalPendingPayments = $payment->getTotalPending();
    $totalPendingExpenses = 0;
    $pendingExpensesList = $expense->getAllExpenses(['status' => 'pending', 'limit' => 1000]);
    foreach ($pendingExpensesList as $e) { $totalPendingExpenses += floatval($e['amount']); }
    
    // 9. Cost of Services
    $monthlyClientServiceCosts = 0;
    $clientServiceCostsList = $expense->getExpensesWithClientService([
        'is_client_service_cost' => true,
        'status' => 'paid',
        'date_from' => sprintf('%04d-%02d-01', $selectedYear, $selectedMonth),
        'date_to' => sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, date('t', strtotime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth))))
    ]);
    foreach ($clientServiceCostsList as $cost) { $monthlyClientServiceCosts += floatval($cost['amount']); }

    // 10. List Queries
    $recentQuotes = $quote->getAllQuotes(['limit' => 5, 'order_by' => 'q.created_at', 'order_dir' => 'DESC']);
    $activeClients = $client->getAllClients(['status' => 'active', 'limit' => 4, 'order_by' => 'created_at', 'order_dir' => 'DESC']);
    $todayTasks = $dailyTask->getTodayTasks();
    $upcomingTasks = $dailyTask->getUpcomingTasks(7);
    $expiringServices = $service->getServicesExpiringSoon(15);
    
    // 11. Revenue Split Calculation
    $renewalRevenue = 0;
    $newProjectRevenue = 0;
    try {
        $sqlSplit = "SELECT CASE WHEN s.period_number > 1 THEN 'renewal' ELSE 'new' END as type, SUM(p.amount) as total FROM payments p JOIN services s ON p.service_id = s.id WHERE p.status = 'paid' AND MONTH(p.paid_at) = :month AND YEAR(p.paid_at) = :year GROUP BY type";
        $stmtSplit = Database::getInstance()->getConnection()->prepare($sqlSplit);
        $stmtSplit->execute([':month' => $selectedMonth, ':year' => $selectedYear]);
        foreach ($stmtSplit->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['type'] === 'renewal') $renewalRevenue = floatval($row['total']);
            else $newProjectRevenue = floatval($row['total']);
        }
    } catch (Exception $e) {}
    $totalMeasuredRevenue = $renewalRevenue + $newProjectRevenue;
    $retentionRate = $totalMeasuredRevenue > 0 ? round(($renewalRevenue / $totalMeasuredRevenue) * 100) : 0;
    
    // 12. Remaining months
    $monthsRemaining = (12 - intval($selectedMonth)) > 0 ? (12 - intval($selectedMonth)) : 0;

    // 13. Chart Data Preparation
    $revenueByMonth = $payment->getRevenueByMonth(6);
    $leadsByMonth = $db->getLeadsByMonth(6);
    $expensesByMonth = $expense->getExpensesByMonth(6);
    
    // Yearly data for Jan-Dec chart
    $yearlyRevenueData = $payment->getMonthlyRevenueForYear($selectedYear);
    $yearlyExpensesData = $expense->getMonthlyExpensesForYear($selectedYear);
    
    $servicesByType = [];
    foreach ($service->getAllServices(['status' => 'active', 'limit' => 500]) as $svc) {
        $type = $svc['service_type'] ?? 'otro';
        $servicesByType[$type] = ($servicesByType[$type] ?? 0) + 1;
    }
    
    $paymentsStatus = ['paid' => 0, 'pending' => 0, 'overdue' => 0];
    foreach ($payment->getAllPayments(['limit' => 500]) as $pay) {
        $status = $pay['status'] ?? 'pending';
        if (isset($paymentsStatus[$status])) $paymentsStatus[$status]++;
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

// Include layout header
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<div class="relative flex h-screen w-full overflow-hidden">
    <!-- Spacer for sidebar on desktop -->
    <div class="hidden md:block w-64 flex-shrink-0"></div>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10">
        <!-- Mobile Menu Button -->
        <button id="sidebar-toggle-btn" class="md:hidden fixed top-4 left-4 z-30 p-3 bg-white dark:bg-card-dark rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <div class="mx-auto max-w-[1200px] flex flex-col gap-6 mt-16 md:mt-0">
            <!-- 1. Header & Filters -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Panel de Gestión</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Revisa el estado de tus clientes y cotizaciones.</p>
                </div>
                <div class="flex items-center gap-3">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="month" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white text-sm">
                            <?php foreach ($monthNames as $i => $name): ?>
                                <option value="<?php echo $i+1; ?>" <?php echo ($selectedMonth == $i+1) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white text-sm">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($selectedYear == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                    <a href="admin_quotes.php?action=new" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        <span>Nueva Cotización</span>
                    </a>
                </div>
            </div>

            <!-- 2. Period Indicator -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-2xl text-primary">calendar_month</span>
                    <div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Período Seleccionado</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white">
                            <?php echo $monthNames[$selectedMonth - 1] . ' ' . $selectedYear; ?>
                            <?php if ($selectedMonth == $currentMonth && $selectedYear == $currentYear): ?>
                                <span class="text-sm font-normal text-slate-500 ml-2">(Mes Actual)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. Stat Cards Section -->
            <?php include 'includes/dashboard/stat_cards.php'; ?>

            <!-- 4. Payment Reminders Section -->
            <?php include 'includes/dashboard/payment_reminders.php'; ?>

            <!-- 5. Operational Metrics -->
            <?php include 'includes/dashboard/operational_metrics.php'; ?>

            <!-- 6. Charts Section -->
            <?php include 'includes/dashboard/charts_section.php'; ?>

            <!-- 7. Main Grid for Tables & Sidebar -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 flex flex-col gap-6">
                    <!-- Quotes Table -->
                    <?php include 'includes/dashboard/quotes_table.php'; ?>
                    <!-- Active Clients -->
                    <?php include 'includes/dashboard/active_clients.php'; ?>
                </div>
                <!-- Sidebar Widgets -->
                <div class="xl:col-span-1">
                    <?php include 'includes/dashboard/sidebar_widgets.php'; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Scripts Section -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/dashboard_charts.js?v=<?php echo time(); ?>"></script>
<script>
// Initialize Charts with PHP Data
document.addEventListener('DOMContentLoaded', () => {
    const dashboardData = {
        monthLabel: '<?php echo $monthNames[$selectedMonth-1] . " " . $selectedYear; ?>',
        monthlyRevenue: <?php echo (float)$monthlyRevenue; ?>,
        monthlyExpectedIncome: <?php echo (float)$monthlyExpectedIncome; ?>,
        monthlyActualExpenses: <?php echo (float)$monthlyActualExpenses; ?>,
        yearlyRevenueData: <?php echo json_encode($yearlyRevenueData); ?>,
        yearlyExpensesData: <?php echo json_encode($yearlyExpensesData); ?>,
        revenueByMonth: <?php echo json_encode($revenueByMonth); ?>,
        expensesByMonth: <?php echo json_encode($expensesByMonth); ?>,
        leadsByMonth: <?php echo json_encode($leadsByMonth); ?>,
        servicesByType: <?php echo json_encode($servicesByType); ?>,
        paymentsStatus: <?php echo json_encode($paymentsStatus); ?>,
        leadsConversionData: {
            converted: <?php echo count($client->getAllClients(['limit' => 1000])); ?>, // Simplified for demo
            others: <?php echo $newLeadsThisMonth; ?>
        }
    };
    initDashboardCharts(dashboardData);
});

function toggleTask(taskId, isCompleted) {
    fetch('api/daily_tasks_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', task_id: taskId, is_completed: isCompleted })
    })
    .then(response => response.json())
    .catch(error => console.error('Error:', error));
}

function renewService(serviceId, clientName) {
    if (confirm(`¿Deseas renovar el servicio para ${clientName}?`)) {
        window.location.href = `api_service_renewal.php?id=${serviceId}`;
    }
}
</script>

<?php include 'footer.php'; ?>
