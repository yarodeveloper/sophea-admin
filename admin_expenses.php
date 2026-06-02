<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Gestión de Gastos - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Expense.php';

// Initialize classes
try {
    $expense = new Expense();
    require_once 'classes/Client.php';
    require_once 'classes/Service.php';
    $client = new Client();
    $service = new Service();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $expense = null;
    $client = null;
    $service = null;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Create expense
        if ($action === 'create_expense' && $expense) {
            $isClientServiceCost = isset($_POST['is_client_service_cost']) && $_POST['is_client_service_cost'] === '1';
            
            $expenseData = [
                'expense_type' => $_POST['expense_type'],
                'category' => $_POST['category'],
                'description' => !empty($_POST['description']) ? $_POST['description'] : null,
                'amount' => floatval($_POST['amount']),
                'currency' => $_POST['currency'] ?? 'MXN',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'payment_date' => $_POST['payment_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'vendor' => !empty($_POST['vendor']) ? $_POST['vendor'] : null,
                'invoice_number' => !empty($_POST['invoice_number']) ? $_POST['invoice_number'] : null,
                'receipt_url' => !empty($_POST['receipt_url']) ? $_POST['receipt_url'] : null,
                'is_recurring' => isset($_POST['is_recurring']) && $_POST['is_recurring'] === '1',
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'created_by' => $currentUser['id'],
                'is_client_service_cost' => $isClientServiceCost,
                'client_id' => $isClientServiceCost && !empty($_POST['client_id']) ? intval($_POST['client_id']) : null,
                'service_id' => $isClientServiceCost && !empty($_POST['service_id']) ? intval($_POST['service_id']) : null,
                'campaign_id' => !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : null,
                'billing_period_start' => !empty($_POST['billing_period_start']) ? $_POST['billing_period_start'] : null,
                'billing_period_end' => !empty($_POST['billing_period_end']) ? $_POST['billing_period_end'] : null,
                'reimbursement_status' => !empty($_POST['reimbursement_status']) ? $_POST['reimbursement_status'] : 'not_required'
            ];
            
            $expenseId = $expense->createExpense($expenseData);
            
            if ($expenseId) {
                $message = 'Gasto registrado exitosamente';
                $messageType = 'success';
            } else {
                $errorMessage = $expense->getLastError();
                $message = 'Error al registrar el gasto';
                if ($errorMessage) {
                    $message .= ': ' . $errorMessage;
                }
                $messageType = 'error';
                error_log("Error creating expense in admin_expenses.php: " . $errorMessage);
            }
        }
        
        // Update expense
        elseif ($action === 'update_expense' && $expense) {
            $expenseId = intval($_POST['expense_id']);
            $isClientServiceCost = isset($_POST['is_client_service_cost']) && $_POST['is_client_service_cost'] === '1';
            
            $expenseData = [
                'expense_type' => $_POST['expense_type'],
                'category' => $_POST['category'],
                'description' => !empty($_POST['description']) ? $_POST['description'] : null,
                'amount' => floatval($_POST['amount']),
                'currency' => $_POST['currency'] ?? 'MXN',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'payment_date' => $_POST['payment_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'vendor' => !empty($_POST['vendor']) ? $_POST['vendor'] : null,
                'invoice_number' => !empty($_POST['invoice_number']) ? $_POST['invoice_number'] : null,
                'receipt_url' => !empty($_POST['receipt_url']) ? $_POST['receipt_url'] : null,
                'status' => $_POST['status'] ?? 'pending',
                'is_recurring' => isset($_POST['is_recurring']) && $_POST['is_recurring'] === '1',
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'is_client_service_cost' => $isClientServiceCost,
                'client_id' => $isClientServiceCost && !empty($_POST['client_id']) ? intval($_POST['client_id']) : null,
                'service_id' => $isClientServiceCost && !empty($_POST['service_id']) ? intval($_POST['service_id']) : null,
                'campaign_id' => !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : null,
                'billing_period_start' => !empty($_POST['billing_period_start']) ? $_POST['billing_period_start'] : null,
                'billing_period_end' => !empty($_POST['billing_period_end']) ? $_POST['billing_period_end'] : null,
                'reimbursement_status' => !empty($_POST['reimbursement_status']) ? $_POST['reimbursement_status'] : 'not_required'
            ];
            
            $result = $expense->updateExpense($expenseId, $expenseData);
            
            if ($result) {
                $message = 'Gasto actualizado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al actualizar el gasto';
                $messageType = 'error';
            }
        }
        
        // Delete expense
        elseif ($action === 'delete_expense' && $expense) {
            $expenseId = intval($_POST['expense_id']);
            $result = $expense->deleteExpense($expenseId);
            
            if ($result) {
                $message = 'Gasto eliminado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar el gasto';
                $messageType = 'error';
            }
        }
    }
}

// Get filters from URL
$typeFilter = $_GET['expense_type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$cycleFilter = $_GET['billing_cycle'] ?? '';

// Set default dates to current month (first day to last day)
$currentYear = date('Y');
$currentMonth = date('m');
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t'); // 't' returns the number of days in the month

$dateFrom = $_GET['date_from'] ?? $firstDayOfMonth;
$dateTo = $_GET['date_to'] ?? $lastDayOfMonth;

$search = $_GET['search'] ?? '';
$clientFilter = $_GET['client_id'] ?? '';
$serviceCostFilter = $_GET['is_client_service_cost'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build filters
$filters = [];
if (!empty($typeFilter)) {
    $filters['expense_type'] = $typeFilter;
}
if (!empty($statusFilter)) {
    $filters['status'] = $statusFilter;
}
if (!empty($cycleFilter)) {
    $filters['billing_cycle'] = $cycleFilter;
}
// Always include date filters (default to current month)
$filters['date_from'] = $dateFrom;
$filters['date_to'] = $dateTo;
if (!empty($search)) {
    $filters['search'] = $search;
}
if (!empty($clientFilter)) {
    $filters['client_id'] = intval($clientFilter);
}
if ($serviceCostFilter !== '') {
    $filters['is_client_service_cost'] = $serviceCostFilter === '1';
}
$filters['limit'] = $perPage;
$filters['offset'] = $offset;
$filters['order_by'] = 'e.payment_date';
$filters['order_dir'] = 'DESC';

// Get clients and services for dropdowns
$allClients = [];
$allServices = [];
if ($client) {
    try {
        $allClients = $client->getAllClients(['limit' => 1000, 'order_by' => 'company_name', 'order_dir' => 'ASC']);
    } catch (Exception $e) {
        error_log("Error fetching clients: " . $e->getMessage());
    }
}

// Get expenses
$expenses = [];
$totalExpenses = 0;
$totalPages = 1;
$totalPending = 0;
$totalPaid = 0;
$monthlyExpenses = 0;

if ($expense) {
    try {
        $expenses = $expense->getExpensesWithClientService($filters);
        $totalExpenses = $expense->getTotalCount($filters);
        $totalPages = ceil($totalExpenses / $perPage);
        
        // Debug: Log expenses count and filters
        error_log("Expenses found: " . count($expenses) . ", Total: " . $totalExpenses);
        error_log("Filters applied: " . print_r($filters, true));
        if (empty($expenses) && $totalExpenses > 0) {
            error_log("WARNING: Total count is {$totalExpenses} but no expenses returned. This might be a pagination issue.");
        }
        
        // Get totals
        $pendingExpenses = $expense->getAllExpenses(['status' => 'pending', 'limit' => 10000]);
        $paidExpenses = $expense->getAllExpenses(['status' => 'paid', 'limit' => 10000]);
        
        foreach ($pendingExpenses as $e) {
            $totalPending += floatval($e['amount']);
        }
        foreach ($paidExpenses as $e) {
            $totalPaid += floatval($e['amount']);
        }
        
        // Get monthly expenses
        $currentMonth = date('m');
        $currentYear = date('Y');
        $monthlyExpenses = $expense->getMonthlyExpenses($currentYear, $currentMonth);
    } catch (Exception $e) {
        error_log("Error fetching expenses: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
    }
} else {
    error_log("Expense class is null - check if Expense.php is loaded correctly");
}

// Get expense for editing
$editExpense = null;
if (isset($_GET['edit']) && $expense) {
    $editId = intval($_GET['edit']);
    $editExpense = $expense->getExpenseById($editId);
}

// Get expense type labels
$expenseTypeLabels = $expense ? $expense->getExpenseTypeLabels() : [];
$billingCycleLabels = $expense ? $expense->getBillingCycleLabels() : [];

// Include header
include 'includes/admin_header.php';
?>

<!-- Sidebar (outside flex container for mobile, inside for desktop) -->
<?php include 'includes/admin_sidebar.php'; ?>

<div class="relative flex h-screen w-full overflow-hidden">
    <!-- Spacer for sidebar on desktop -->
    <div class="hidden md:block w-64 flex-shrink-0"></div>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10 md:ml-0">
        <div class="mx-auto max-w-[1400px]">
            <!-- Mobile Menu Button -->
            <button id="sidebar-toggle-btn" class="md:hidden fixed top-4 left-4 z-30 p-3 bg-white dark:bg-card-dark rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" aria-label="Abrir menú">
                <span class="material-symbols-outlined text-2xl">menu</span>
            </button>
            
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 mt-16 md:mt-0">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">receipt_long</span>
                        Gestión de Gastos
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Registra y gestiona tus gastos operativos</p>
                </div>
                
                <button onclick="openNewExpenseModal()" 
                        class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Registrar Gasto
                </button>
            </div>

            <!-- Message -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Gastos del Mes</span>
                        <span class="material-symbols-outlined text-2xl text-orange-600">receipt_long</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        $<?php echo number_format($monthlyExpenses, 2); ?>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Gastos Pagados</span>
                        <span class="material-symbols-outlined text-2xl text-green-600">check_circle</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        $<?php echo number_format($totalPaid, 2); ?>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Gastos Pendientes</span>
                        <span class="material-symbols-outlined text-2xl text-yellow-600">pending</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        $<?php echo number_format($totalPending, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
                <form method="GET" action="admin_expenses.php" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo</label>
                        <select name="expense_type" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <?php foreach ($expenseTypeLabels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Vencido</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ciclo</label>
                        <select name="billing_cycle" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <?php foreach ($billingCycleLabels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $cycleFilter === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Desde</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Hasta</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Buscar</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Categoría, descripción..."
                               class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cliente</label>
                        <select name="client_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <?php if (!empty($allClients)): ?>
                                <?php foreach ($allClients as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $clientFilter == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de Gasto</label>
                        <select name="is_client_service_cost" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <option value="0" <?php echo $serviceCostFilter === '0' ? 'selected' : ''; ?>>Solo Gastos Operativos</option>
                            <option value="1" <?php echo $serviceCostFilter === '1' ? 'selected' : ''; ?>>Solo Costos de Servicios</option>
                        </select>
                    </div>
                    
                    <div class="lg:col-span-6 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            Filtrar
                        </button>
                        <a href="admin_expenses.php" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente/Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ciclo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p class="text-lg font-medium">No se encontraron gastos</p>
                                            <p class="text-sm">
                                                <?php if ($totalExpenses > 0): ?>
                                                    Se encontraron <?php echo $totalExpenses; ?> gasto(s) pero no se pueden mostrar. 
                                                    Filtros aplicados: Desde <?php echo htmlspecialchars($dateFrom); ?> hasta <?php echo htmlspecialchars($dateTo); ?>
                                                <?php else: ?>
                                                    Registra tu primer gasto usando el botón "Registrar Gasto"
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $exp): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                            <?php echo htmlspecialchars($exp['expense_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                            <?php echo htmlspecialchars($expenseTypeLabels[$exp['expense_type']] ?? $exp['expense_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">
                                            <div class="font-medium"><?php echo htmlspecialchars($exp['category']); ?></div>
                                            <?php if ($exp['description']): ?>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                    <?php echo htmlspecialchars(substr($exp['description'], 0, 50)) . (strlen($exp['description']) > 50 ? '...' : ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <?php if (!empty($exp['company_name'])): ?>
                                                <div class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($exp['company_name']); ?></div>
                                                <?php if (!empty($exp['service_name'])): ?>
                                                    <div class="text-xs"><?php echo htmlspecialchars($exp['service_name']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($exp['campaign_id'])): ?>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400">Campaña: <?php echo htmlspecialchars($exp['campaign_id']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white">
                                            $<?php echo number_format($exp['amount'], 2); ?> <?php echo htmlspecialchars($exp['currency']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                            <?php echo date('d/m/Y', strtotime($exp['payment_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                            <?php echo htmlspecialchars($billingCycleLabels[$exp['billing_cycle']] ?? $exp['billing_cycle']); ?>
                                            <?php if ($exp['is_recurring']): ?>
                                                <span class="material-symbols-outlined text-xs align-middle ml-1" title="Recurrente">repeat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                    if ($exp['status'] === 'paid') echo 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
                                                    else if ($exp['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
                                                    else if ($exp['status'] === 'overdue') echo 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
                                                    else echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                                ?>">
                                                <?php 
                                                $statusLabels = [
                                                    'paid' => 'Pagado',
                                                    'pending' => 'Pendiente',
                                                    'overdue' => 'Vencido',
                                                    'cancelled' => 'Cancelado'
                                                ];
                                                echo htmlspecialchars($statusLabels[$exp['status']] ?? ucfirst($exp['status'])); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="openEditExpenseModal(<?php echo htmlspecialchars(json_encode($exp)); ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                                Editar
                                            </button>
                                            <button onclick="confirmDeleteExpense(<?php echo $exp['id']; ?>, '<?php echo htmlspecialchars(addslashes($exp['category'])); ?>')" 
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalExpenses); ?> de <?php echo $totalExpenses; ?> gastos
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded text-sm <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- New/Edit Expense Modal -->
<div id="expenseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modalTitle">Registrar Gasto</h3>
                <button onclick="closeExpenseModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_expenses.php" id="expenseForm" class="p-6">
            <input type="hidden" name="action" id="expenseAction" value="create_expense">
            <input type="hidden" name="expense_id" id="expenseId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Tipo de Gasto *
                    </label>
                    <select name="expense_type" id="expenseType" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Selecciona un tipo --</option>
                        <?php foreach ($expenseTypeLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Categoría * (ej: Hosting, Canva Pro, Cursor IA)
                    </label>
                    <input type="text" name="category" id="expenseCategory" required
                           placeholder="Ej: Hosting, Canva Pro, Cursor IA, Sueldo..."
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Descripción
                    </label>
                    <textarea name="description" id="expenseDescription" rows="2"
                              placeholder="Descripción detallada del gasto..."
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Monto *
                    </label>
                    <input type="number" name="amount" id="expenseAmount" step="0.01" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Moneda
                    </label>
                    <select name="currency" id="expenseCurrency"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="MXN">MXN - Peso Mexicano</option>
                        <option value="USD">USD - Dólar</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Método de Pago
                    </label>
                    <select name="payment_method" id="expensePaymentMethod"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="transfer">Transferencia</option>
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Pago *
                    </label>
                    <input type="date" name="payment_date" id="expensePaymentDate" required
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Vencimiento
                    </label>
                    <input type="date" name="due_date" id="expenseDueDate"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Ciclo de Facturación
                    </label>
                    <select name="billing_cycle" id="expenseBillingCycle"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($billingCycleLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $value === 'monthly' ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Proveedor/Vendor
                    </label>
                    <input type="text" name="vendor" id="expenseVendor"
                           placeholder="Ej: Hostinger, Canva, OpenAI..."
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Número de Factura
                    </label>
                    <input type="text" name="invoice_number" id="expenseInvoiceNumber"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        URL del Comprobante
                    </label>
                    <input type="url" name="receipt_url" id="expenseReceiptUrl"
                           placeholder="https://..."
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="status" id="expenseStatus"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="pending">Pendiente</option>
                        <option value="paid">Pagado</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_recurring" id="expenseIsRecurring" value="1" checked
                           class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
                    <label for="expenseIsRecurring" class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                        Gasto recurrente
                    </label>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notas
                    </label>
                    <textarea name="notes" id="expenseNotes" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <!-- Separator for Client Service Cost Section -->
            <div class="border-t border-slate-200 dark:border-slate-700 my-6"></div>
            <div class="mb-4">
                <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Costo de Servicio de Cliente</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    Marca esta opción si este gasto está asociado a un servicio de un cliente (ej: Facebook Ads, Google Ads)
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_client_service_cost" id="expenseIsClientServiceCost" value="1"
                               onchange="toggleClientServiceFields()"
                               class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
                        <label for="expenseIsClientServiceCost" class="ml-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            Es costo de servicio de cliente
                        </label>
                    </div>
                </div>
                
                <!-- Client and Service Fields (hidden by default) -->
                <div id="clientServiceFields" class="md:col-span-2 hidden">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <span class="material-symbols-outlined text-sm align-middle">info</span>
                            Estos campos son obligatorios cuando es costo de servicio de cliente.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Cliente *
                            </label>
                            <select name="client_id" id="expenseClientId"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Selecciona un cliente --</option>
                                <?php if (!empty($allClients)): ?>
                                    <?php foreach ($allClients as $c): ?>
                                        <option value="<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['company_name'] . ' (' . $c['client_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Servicio/Proyecto *
                            </label>
                            <select name="service_id" id="expenseServiceId"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Selecciona un servicio --</option>
                                <!-- Will be populated via JavaScript based on selected client -->
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                ID de Campaña (Plataforma)
                            </label>
                            <input type="text" name="campaign_id" id="expenseCampaignId"
                                   placeholder="Ej: fb_campaign_12345, google_ads_67890"
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Estado de Reembolso
                            </label>
                            <select name="reimbursement_status" id="expenseReimbursementStatus"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="not_required">No requerido</option>
                                <option value="pending">Pendiente</option>
                                <option value="billed">Facturado</option>
                                <option value="paid">Pagado</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Período Facturado - Inicio
                            </label>
                            <input type="date" name="billing_period_start" id="expenseBillingPeriodStart"
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Período Facturado - Fin
                            </label>
                            <input type="date" name="billing_period_end" id="expenseBillingPeriodEnd"
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeExpenseModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Gasto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Confirmar Eliminación</h3>
        </div>
        <div class="p-6">
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                ¿Estás seguro de que deseas eliminar el gasto "<span id="deleteExpenseName" class="font-medium"></span>"?
            </p>
            <form method="POST" action="admin_expenses.php" id="deleteForm">
                <input type="hidden" name="action" value="delete_expense">
                <input type="hidden" name="expense_id" id="deleteExpenseId" value="">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openNewExpenseModal() {
    document.getElementById('modalTitle').textContent = 'Registrar Gasto';
    document.getElementById('expenseAction').value = 'create_expense';
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseForm').reset();
    document.getElementById('expensePaymentDate').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('expenseIsRecurring').checked = true;
    document.getElementById('expenseIsClientServiceCost').checked = false;
    toggleClientServiceFields();
    document.getElementById('expenseModal').classList.remove('hidden');
}

function openEditExpenseModal(expense) {
    document.getElementById('modalTitle').textContent = 'Editar Gasto';
    document.getElementById('expenseAction').value = 'update_expense';
    document.getElementById('expenseId').value = expense.id;
    document.getElementById('expenseType').value = expense.expense_type || '';
    document.getElementById('expenseCategory').value = expense.category || '';
    document.getElementById('expenseDescription').value = expense.description || '';
    document.getElementById('expenseAmount').value = expense.amount || '';
    document.getElementById('expenseCurrency').value = expense.currency || 'MXN';
    document.getElementById('expensePaymentMethod').value = expense.payment_method || 'transfer';
    document.getElementById('expensePaymentDate').value = expense.payment_date || '';
    document.getElementById('expenseDueDate').value = expense.due_date || '';
    document.getElementById('expenseBillingCycle').value = expense.billing_cycle || 'monthly';
    document.getElementById('expenseVendor').value = expense.vendor || '';
    document.getElementById('expenseInvoiceNumber').value = expense.invoice_number || '';
    document.getElementById('expenseReceiptUrl').value = expense.receipt_url || '';
    document.getElementById('expenseStatus').value = expense.status || 'pending';
    document.getElementById('expenseIsRecurring').checked = expense.is_recurring == 1;
    document.getElementById('expenseNotes').value = expense.notes || '';
    
    // Client service cost fields
    const isClientServiceCost = expense.is_client_service_cost == 1 || expense.is_client_service_cost === true;
    document.getElementById('expenseIsClientServiceCost').checked = isClientServiceCost;
    if (isClientServiceCost) {
        document.getElementById('expenseClientId').value = expense.client_id || '';
        if (expense.client_id) {
            loadServicesForClient(expense.client_id, expense.service_id || '');
        }
        document.getElementById('expenseServiceId').value = expense.service_id || '';
        document.getElementById('expenseCampaignId').value = expense.campaign_id || '';
        document.getElementById('expenseBillingPeriodStart').value = expense.billing_period_start || '';
        document.getElementById('expenseBillingPeriodEnd').value = expense.billing_period_end || '';
        document.getElementById('expenseReimbursementStatus').value = expense.reimbursement_status || 'not_required';
    }
    toggleClientServiceFields();
    document.getElementById('expenseModal').classList.remove('hidden');
}

function closeExpenseModal() {
    document.getElementById('expenseModal').classList.add('hidden');
    // Reset client service fields
    document.getElementById('expenseIsClientServiceCost').checked = false;
    document.getElementById('expenseClientId').value = '';
    document.getElementById('expenseServiceId').innerHTML = '<option value="">-- Selecciona un servicio --</option>';
    toggleClientServiceFields();
}

// Toggle client service fields visibility
function toggleClientServiceFields() {
    const checkbox = document.getElementById('expenseIsClientServiceCost');
    const fieldsDiv = document.getElementById('clientServiceFields');
    const clientSelect = document.getElementById('expenseClientId');
    const serviceSelect = document.getElementById('expenseServiceId');
    
    if (checkbox.checked) {
        fieldsDiv.classList.remove('hidden');
        clientSelect.required = true;
        serviceSelect.required = true;
    } else {
        fieldsDiv.classList.add('hidden');
        clientSelect.required = false;
        serviceSelect.required = false;
        clientSelect.value = '';
        serviceSelect.innerHTML = '<option value="">-- Selecciona un servicio --</option>';
    }
}

// Load services when client is selected
function loadServicesForClient(clientId, selectedServiceId = '') {
    if (!clientId) {
        document.getElementById('expenseServiceId').innerHTML = '<option value="">-- Selecciona un servicio --</option>';
        return;
    }
    
    fetch(`admin_client_detail.php?ajax=get_client_services&client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            const serviceSelect = document.getElementById('expenseServiceId');
            serviceSelect.innerHTML = '<option value="">-- Selecciona un servicio --</option>';
            
            if (data.success && data.services) {
                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = service.service_name || 'Servicio';
                    if (selectedServiceId && service.id == selectedServiceId) {
                        option.selected = true;
                    }
                    serviceSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}

// Auto-detect Ads type and toggle client service cost
document.addEventListener('DOMContentLoaded', function() {
    const expenseTypeSelect = document.getElementById('expenseType');
    const isClientServiceCostCheckbox = document.getElementById('expenseIsClientServiceCost');
    
    if (expenseTypeSelect && isClientServiceCostCheckbox) {
        expenseTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            // If it's an Ads type, auto-check the client service cost checkbox
            if (selectedType && selectedType.startsWith('ads_')) {
                isClientServiceCostCheckbox.checked = true;
                toggleClientServiceFields();
            }
        });
    }
    
    // Load services when client changes
    const clientSelect = document.getElementById('expenseClientId');
    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            loadServicesForClient(this.value);
        });
    }
});

function confirmDeleteExpense(expenseId, expenseName) {
    document.getElementById('deleteExpenseId').value = expenseId;
    document.getElementById('deleteExpenseName').textContent = expenseName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Auto-open edit modal if editing
<?php if ($editExpense): ?>
    window.addEventListener('DOMContentLoaded', function() {
        openEditExpenseModal(<?php echo json_encode($editExpense); ?>);
    });
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>

