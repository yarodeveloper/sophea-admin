<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Facturación y Pagos - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Payment.php';
require_once 'classes/Client.php';
require_once 'classes/Service.php';

// Initialize classes
try {
    $payment = new Payment();
    $client = new Client();
    $service = new Service();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $payment = null;
    $client = null;
    $service = null;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Create payment
        if ($action === 'create_payment' && $payment) {
            $paymentData = [
                'client_id' => intval($_POST['client_id']),
                'service_id' => !empty($_POST['service_id']) ? intval($_POST['service_id']) : null,
                'quote_id' => !empty($_POST['quote_id']) ? intval($_POST['quote_id']) : null,
                'amount' => floatval($_POST['amount']),
                'currency' => $_POST['currency'] ?? 'MXN',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'payment_date' => $_POST['payment_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status' => $_POST['status'] ?? 'pending',
                'paid_at' => ($_POST['status'] === 'paid' && !empty($_POST['paid_at'])) ? $_POST['paid_at'] : null,
                'reference_number' => !empty($_POST['reference_number']) ? $_POST['reference_number'] : null,
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'created_by' => $currentUser['id']
            ];
            
            // Si es servicio Ads, agregar desglose
            if (!empty($_POST['service_id']) && $service) {
                $serviceData = $service->getServiceById(intval($_POST['service_id']));
                $isAdsService = $serviceData && (
                    !empty($serviceData['is_ads_service']) && (
                        $serviceData['is_ads_service'] == 1 || 
                        $serviceData['is_ads_service'] === true || 
                        $serviceData['is_ads_service'] === '1' ||
                        intval($serviceData['is_ads_service']) === 1
                    )
                );
                
                if ($isAdsService) {
                    $paymentData['fee_amount'] = isset($_POST['fee_amount']) ? floatval($_POST['fee_amount']) : 0;
                    $paymentData['ads_amount'] = isset($_POST['ads_amount']) ? floatval($_POST['ads_amount']) : 0;
                    
                    // Debug log
                    error_log("Payment creation - Service ID: " . intval($_POST['service_id']) . ", is_ads_service: " . var_export($serviceData['is_ads_service'] ?? 'NOT SET', true) . ", fee_amount: {$paymentData['fee_amount']}, ads_amount: {$paymentData['ads_amount']}");
                } else {
                    error_log("Payment creation - Service ID: " . intval($_POST['service_id']) . " is NOT an Ads service. is_ads_service: " . var_export($serviceData['is_ads_service'] ?? 'NOT SET', true));
                }
            }
            
            $paymentId = $payment->createPayment($paymentData);
            
            if ($paymentId) {
                // Check if service is now completed (100% progress and fully paid)
                if (!empty($_POST['service_id']) && $service) {
                    $svcId = intval($_POST['service_id']);
                    try {
                        $svcData = $service->getServiceById($svcId);
                        if ($svcData && intval($svcData['progress_percentage'] ?? 0) == 100) {
                            $monthlyFee = floatval($svcData['monthly_fee']);
                            $initialInvestment = floatval($svcData['initial_investment_amount'] ?? 0);
                            $expectedTotal = $monthlyFee + $initialInvestment;

                            // Check total paid and pending status
                            $payments = $payment->getPaymentsByService($svcId);
                            $totalPaid = 0;
                            $hasPending = false;

                            foreach($payments as $p) {
                                $pStatus = $p['status'];
                                if ($pStatus == 'paid') {
                                    $totalPaid += floatval($p['amount']);
                                } else if ($pStatus == 'pending' || $pStatus == 'overdue') {
                                    $hasPending = true;
                                }
                            }
                            
                            if (!$hasPending && $totalPaid >= $expectedTotal) {
                                $db = Database::getInstance()->getConnection();
                                $finishedStmt = $db->prepare("UPDATE services SET status = 'completed' WHERE id = ?");
                                $finishedStmt->execute([$svcId]);

                                // Logic for Recurring Services
                                if (isset($svcData['is_recurring']) && $svcData['is_recurring'] == 1) {
                                    $nextPeriodSql = "SELECT id FROM services WHERE base_service_id = ? AND period_number = ?";
                                    $nextPeriodStmt = $db->prepare($nextPeriodSql);
                                    $baseId = $svcData['base_service_id'] ?? $svcData['id'];
                                    $nextNum = ($svcData['period_number'] ?? 1) + 1;
                                    $nextPeriodStmt->execute([$baseId, $nextNum]);
                                    $alreadyExists = $nextPeriodStmt->fetch();

                                    if (!$alreadyExists) {
                                        $service->renewService($svcId);
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error checking service completion in admin_payments: " . $e->getMessage());
                    }
                }
                $message = 'Pago registrado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al registrar el pago';
                $messageType = 'error';
            }
        }
        
        // Mark as paid
        elseif ($action === 'mark_as_paid' && $payment) {
            $paymentId = intval($_POST['payment_id']);
            $referenceNumber = !empty($_POST['reference_number']) ? $_POST['reference_number'] : null;
            
            $result = $payment->markAsPaid($paymentId, $referenceNumber);
            
            if ($result) {
                // Check if service is now completed
                try {
                    $payData = $payment->getPaymentById($paymentId);
                    if ($payData && !empty($payData['service_id']) && $service) {
                        $svcId = intval($payData['service_id']);
                        $svcData = $service->getServiceById($svcId);
                        if ($svcData && intval($svcData['progress_percentage'] ?? 0) == 100) {
                            $monthlyFee = floatval($svcData['monthly_fee']);
                            $initialInvestment = floatval($svcData['initial_investment_amount'] ?? 0);
                            $expectedTotal = $monthlyFee + $initialInvestment;

                            $payments = $payment->getPaymentsByService($svcId);
                            $totalPaid = 0;
                            $hasPending = false;

                            foreach($payments as $p) {
                                if ($p['status'] == 'paid') {
                                    $totalPaid += floatval($p['amount']);
                                } else if ($p['status'] == 'pending' || $p['status'] == 'overdue') {
                                    $hasPending = true;
                                }
                            }
                            
                            if (!$hasPending && $totalPaid >= $expectedTotal) {
                                $db = Database::getInstance()->getConnection();
                                $finishedStmt = $db->prepare("UPDATE services SET status = 'completed' WHERE id = ?");
                                $finishedStmt->execute([$svcId]);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error checking service completion after mark_as_paid: " . $e->getMessage());
                }
                
                $message = 'Pago marcado como recibido';
                $messageType = 'success';
            } else {
                $message = 'Error al marcar el pago';
                $messageType = 'error';
            }
        }
        
        elseif ($action === 'update_payment' && $payment) {
            $paymentId = intval($_POST['payment_id']);
            
            $paymentData = [
                'client_id' => intval($_POST['client_id']),
                'service_id' => !empty($_POST['service_id']) ? intval($_POST['service_id']) : null,
                'amount' => floatval($_POST['amount']),
                'currency' => $_POST['currency'] ?? 'MXN',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'payment_date' => $_POST['payment_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status' => $_POST['status'] ?? 'pending',
                'paid_at' => ($_POST['status'] === 'paid' && !empty($_POST['paid_at'])) ? $_POST['paid_at'] : null,
                'reference_number' => !empty($_POST['reference_number']) ? $_POST['reference_number'] : null,
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'created_by' => $currentUser['id']
            ];
            
            // Si es servicio Ads, agregar desglose
            if (!empty($_POST['service_id']) && $service) {
                $serviceData = $service->getServiceById(intval($_POST['service_id']));
                $isAdsService = $serviceData && (
                    !empty($serviceData['is_ads_service']) && (
                        $serviceData['is_ads_service'] == 1 || 
                        $serviceData['is_ads_service'] === true || 
                        $serviceData['is_ads_service'] === '1' ||
                        intval($serviceData['is_ads_service']) === 1
                    )
                );
                
                if ($isAdsService) {
                    $paymentData['fee_amount'] = isset($_POST['fee_amount']) ? floatval($_POST['fee_amount']) : 0;
                    $paymentData['ads_amount'] = isset($_POST['ads_amount']) ? floatval($_POST['ads_amount']) : 0;
                }
            }
            
            $result = $payment->updatePayment($paymentId, $paymentData);
            
            if ($result) {
                // Check if service is now completed
                if (!empty($_POST['service_id']) && $service && $_POST['status'] === 'paid') {
                    $svcId = intval($_POST['service_id']);
                    try {
                        $svcData = $service->getServiceById($svcId);
                        if ($svcData && intval($svcData['progress_percentage'] ?? 0) == 100) {
                            $monthlyFee = floatval($svcData['monthly_fee']);
                            $initialInvestment = floatval($svcData['initial_investment_amount'] ?? 0);
                            $expectedTotal = $monthlyFee + $initialInvestment;

                            $payments = $payment->getPaymentsByService($svcId);
                            $totalPaid = 0;
                            $hasPending = false;

                            foreach($payments as $p) {
                                if ($p['status'] == 'paid') {
                                    $totalPaid += floatval($p['amount']);
                                } else if ($p['status'] == 'pending' || $p['status'] == 'overdue') {
                                    $hasPending = true;
                                }
                            }
                            
                            if (!$hasPending && $totalPaid >= $expectedTotal) {
                                $db = Database::getInstance()->getConnection();
                                $finishedStmt = $db->prepare("UPDATE services SET status = 'completed' WHERE id = ?");
                                $finishedStmt->execute([$svcId]);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error checking service completion after update_payment: " . $e->getMessage());
                    }
                }
                $message = 'Pago actualizado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al actualizar el pago';
                $messageType = 'error';
            }
        }
        
        // Delete payment
        elseif ($action === 'delete_payment' && $payment) {
            $paymentId = intval($_POST['payment_id']);
            
            // Check if it's deletable to show a specific message
            $check = $payment->canDelete($paymentId);
            if (!$check['can_delete']) {
                $message = 'No se puede eliminar: ' . $check['reason'];
                $messageType = 'error';
            } else {
                $result = $payment->deletePaymentPermanent($paymentId);
                if ($result) {
                    $message = 'Pago eliminado permanentemente';
                    $messageType = 'success';
                } else {
                    $message = 'Error al intentar eliminar el pago';
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get filters from URL
$statusFilter = $_GET['status'] ?? '';
$clientFilter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$serviceFilter = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Month filters (default to current month)
$monthFrom = $_GET['month_from'] ?? date('Y-m');
$monthTo = $_GET['month_to'] ?? date('Y-m');

// If dateFrom does not match monthFrom, it means monthFrom was updated, so recalculate dateFrom
if (!empty($dateFrom) && !empty($monthFrom) && strpos($dateFrom, $monthFrom) !== 0) {
    $dateFrom = '';
}
if (!empty($dateTo) && !empty($monthTo) && strpos($dateTo, $monthTo) !== 0) {
    $dateTo = '';
}

// Convert month filters to date_from and date_to if not explicitly set
if (empty($dateFrom) && !empty($monthFrom)) {
    $dateFrom = $monthFrom . '-01';
}
if (empty($dateTo) && !empty($monthTo)) {
    // Get last day of the month
    $lastDay = date('t', strtotime($monthTo . '-01'));
    $dateTo = $monthTo . '-' . $lastDay;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build filters
$filters = [];
if (!empty($statusFilter)) {
    $filters['status'] = $statusFilter;
}
if ($clientFilter > 0) {
    $filters['client_id'] = $clientFilter;
}
if ($serviceFilter > 0) {
    $filters['service_id'] = $serviceFilter;
}
if (!empty($dateFrom)) {
    $filters['date_from'] = $dateFrom;
}
if (!empty($dateTo)) {
    $filters['date_to'] = $dateTo;
}
$filters['limit'] = $perPage;
$filters['offset'] = $offset;
$filters['order_by'] = 'p.payment_date';
$filters['order_dir'] = 'DESC';

// Get payments
$payments = [];
$totalPayments = 0;
$totalPages = 1;
$totalPending = 0;
$totalPaid = 0;
$totalAmount = 0;

if ($payment) {
    try {
        $payments = $payment->getAllPayments($filters);
        $totalPayments = $payment->getTotalCount($filters);
        $totalPages = ceil($totalPayments / $perPage);
        
        // Calculate totals based on filters
        // Create filter arrays for totals
        $totalPendingFilters = array_merge($filters, ['status' => 'pending']);
        $totalPaidFilters = array_merge($filters, ['status' => 'paid']);
        
        // Get totals using filtered payments
        $pendingPayments = $payment->getAllPayments($totalPendingFilters);
        $paidPayments = $payment->getAllPayments($totalPaidFilters);
        
        $totalPending = 0;
        foreach ($pendingPayments as $pay) {
            $totalPending += floatval($pay['amount']);
        }
        
        $totalPaid = 0;
        foreach ($paidPayments as $pay) {
            $totalPaid += floatval($pay['amount']);
        }
        
        // Also include overdue in pending
        $overdueFilters = array_merge($filters, ['status' => 'overdue']);
        $overduePayments = $payment->getAllPayments($overdueFilters);
        foreach ($overduePayments as $pay) {
            $totalPending += floatval($pay['amount']);
        }
        
        // Calculate total amount of filtered payments
        $totalAmount = 0;
        foreach ($payments as $pay) {
            $totalAmount += floatval($pay['amount']);
        }
    } catch (Exception $e) {
        error_log("Error fetching payments: " . $e->getMessage());
    }
}

// Get all clients for filter
$allClients = [];
if ($client) {
    try {
        $allClients = $client->getAllClients(['limit' => 1000, 'order_by' => 'company_name', 'order_dir' => 'ASC']);
    } catch (Exception $e) {
        error_log("Error fetching clients: " . $e->getMessage());
    }
}

// Get services for selected client
$clientServices = [];
if ($clientFilter > 0 && $service) {
    try {
        $clientServices = $service->getServicesByClient($clientFilter, 'active');
    } catch (Exception $e) {
        error_log("Error fetching services: " . $e->getMessage());
    }
}

// Get payment for editing
$editPayment = null;
$editPaymentId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
if ($editPaymentId > 0 && $payment) {
    $editPayment = $payment->getPaymentById($editPaymentId);
}


// Handle AJAX request for services
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_services' && isset($_GET['client_id'])) {
    header('Content-Type: application/json');
    $clientId = intval($_GET['client_id']);
    $services = [];
    
    if ($service && $clientId > 0) {
        try {
            $servicesData = $service->getServicesByClient($clientId, 'active');
            // Ensure is_ads_service is included in response
            foreach ($servicesData as $s) {
                $services[] = [
                    'id' => $s['id'],
                    'service_name' => $s['service_name'],
                    'is_ads_service' => !empty($s['is_ads_service']) ? true : false
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching services: " . $e->getMessage());
        }
    }
    
    echo json_encode($services);
    exit;
}

// Handle AJAX request for client report
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_client_report' && isset($_GET['month'])) {
    header('Content-Type: application/json');
    $monthStr = $_GET['month']; // YYYY-MM
    $parts = explode('-', $monthStr);
    $year = intval($parts[0]);
    $month = intval($parts[1]);
    
    $reportData = [];
    if ($payment) {
        $reportData = $payment->getMonthlyPaymentsByClient($year, $month);
    }
    
    echo json_encode($reportData);
    exit;
}

// Include header
include 'includes/admin_header.php';
?>

<!-- Sidebar (outside flex container for mobile, inside for desktop) -->
<?php include 'includes/admin_sidebar.php'; ?>

<div class="relative flex h-screen w-full overflow-hidden">
    <!-- Spacer for sidebar on desktop -->
    <div class="hidden md:block w-64 flex-shrink-0"></div>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10">
        <!-- Mobile Menu Button -->
        <button id="sidebar-toggle-btn" class="md:hidden fixed top-4 left-4 z-30 p-3 bg-white dark:bg-card-dark rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" aria-label="Abrir menú">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <div class="mx-auto max-w-[1400px] mt-16 md:mt-0">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">payments</span>
                        Facturación y Pagos
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Registra y gestiona los pagos de tus clientes</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button onclick="openReportModal()" 
                            class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition font-medium flex items-center gap-2 border border-purple-200 dark:border-purple-800">
                        <span class="material-symbols-outlined text-lg">assessment</span>
                        Reporte Mensual
                    </button>
                    <button onclick="openInvoiceModal()" 
                            class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">receipt</span>
                        Enviar Factura
                    </button>
                    <button onclick="openNewPaymentModal()" 
                            class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Registrar Pago
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Pendiente</span>
                        <span class="material-symbols-outlined text-2xl text-yellow-600">schedule</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        $<?php echo number_format($totalPending, 2); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Según filtros aplicados</div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Pagado</span>
                        <span class="material-symbols-outlined text-2xl text-green-600">check_circle</span>
                    </div>
                    <div class="text-2xl font-bold text-green-600">
                        $<?php echo number_format($totalPaid, 2); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Según filtros aplicados</div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Importe</span>
                        <span class="material-symbols-outlined text-2xl text-primary">payments</span>
                    </div>
                    <div class="text-2xl font-bold text-primary">
                        $<?php echo number_format($totalAmount, 2); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pagos mostrados</div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Registros</span>
                        <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo $totalPayments; ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pagos encontrados</div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                        <div>
                            <p class="font-semibold"><?php echo $messageType === 'success' ? 'Éxito' : 'Error'; ?></p>
                            <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Payment Reminders -->
            <?php
            $overduePayments = [];
            $paymentsDueSoon = [];
            
            if ($payment) {
                try {
                    $overduePayments = $payment->getOverduePayments();
                    $paymentsDueSoon = $payment->getPaymentsDueSoon(7);
                } catch (Exception $e) {
                    error_log("Error fetching payment reminders: " . $e->getMessage());
                }
            }
            
            $totalReminders = count($overduePayments) + count($paymentsDueSoon);
            ?>
            <?php if ($totalReminders > 0): ?>
            <div class="mb-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl border-l-4 border-yellow-500 dark:border-yellow-400 shadow-sm p-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-outlined text-2xl text-yellow-600 dark:text-yellow-400">notifications_active</span>
                    <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-200">Recordatorios de Pagos</h3>
                </div>
                
                <?php if (count($overduePayments) > 0): ?>
                    <div class="mb-3">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-lg">warning</span>
                            <span class="font-semibold text-red-900 dark:text-red-200"><?php echo count($overduePayments); ?> pago<?php echo count($overduePayments) > 1 ? 's' : ''; ?> vencido<?php echo count($overduePayments) > 1 ? 's' : ''; ?></span>
                        </div>
                        <p class="text-sm text-red-700 dark:text-red-300">Revisa y actualiza el estado de estos pagos urgentemente.</p>
                    </div>
                <?php endif; ?>
                
                <?php if (count($paymentsDueSoon) > 0): ?>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-lg">schedule</span>
                            <span class="font-semibold text-yellow-900 dark:text-yellow-200"><?php echo count($paymentsDueSoon); ?> pago<?php echo count($paymentsDueSoon) > 1 ? 's' : ''; ?> próximo<?php echo count($paymentsDueSoon) > 1 ? 's' : ''; ?> a vencer (próximos 7 días)</span>
                        </div>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">Contacta a los clientes para recordarles estos pagos.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
                <form method="GET" action="admin_payments.php" class="space-y-4">
                    <div class="flex flex-wrap gap-4 items-end">
                        <!-- Client Filter -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Cliente</label>
                            <select name="client_id" id="filterClientId" onchange="loadClientServices(this.value)"
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Todos los clientes</option>
                                <?php foreach ($allClients as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $clientFilter === $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Service Filter -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Servicio</label>
                            <select name="service_id" id="filterServiceId"
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Todos los servicios</option>
                                <?php foreach ($clientServices as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $serviceFilter === $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['service_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Estado</label>
                            <select name="status" 
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Todos los estados</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                                <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Vencido</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <!-- Month From Filter -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Mes Inicial</label>
                            <input type="month" name="month_from" value="<?php echo htmlspecialchars($monthFrom); ?>"
                                   class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <!-- Month To Filter -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Mes Final</label>
                            <input type="month" name="month_to" value="<?php echo htmlspecialchars($monthTo); ?>"
                                   class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" 
                                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition font-medium h-[42px]">
                            Filtrar
                        </button>
                        
                        <!-- Clear Filters -->
                        <?php if ($statusFilter || $clientFilter || $serviceFilter || $monthFrom !== date('Y-m') || $monthTo !== date('Y-m')): ?>
                            <a href="admin_payments.php" 
                               class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-6 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium h-[42px] flex items-center">
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden date fields for backward compatibility -->
                    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </form>
            </div>

            <!-- Payments Table -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Factura</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vencimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p class="text-lg font-medium">No se encontraron pagos</p>
                                            <p class="text-sm"><?php echo $statusFilter || $clientFilter || $serviceFilter || $dateFrom || $dateTo ? 'Intenta ajustar los filtros' : 'Comienza registrando tu primer pago'; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $pay): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($pay['invoice_number'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                <?php echo htmlspecialchars($pay['payment_number'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($pay['company_name'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-600 dark:text-slate-300">
                                                <?php echo htmlspecialchars($pay['service_name'] ?? '-'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                $<?php echo number_format($pay['amount'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                                <?php echo date('d/m/Y', strtotime($pay['payment_date'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            if ($pay['due_date']) {
                                                $dueDate = new DateTime($pay['due_date']);
                                                $now = new DateTime();
                                                $diff = $now->diff($dueDate);
                                                
                                                $dateClass = 'text-slate-600 dark:text-slate-400';
                                                if ($dueDate < $now && $pay['status'] !== 'paid') {
                                                    $dateClass = 'text-red-600 dark:text-red-400 font-semibold';
                                                } elseif ($diff->days <= 7 && $pay['status'] === 'pending') {
                                                    $dateClass = 'text-yellow-600 dark:text-yellow-400';
                                                }
                                            ?>
                                                <div class="text-sm <?php echo $dateClass; ?>">
                                                    <?php echo $dueDate->format('d/m/Y'); ?>
                                                </div>
                                            <?php } else { ?>
                                                <div class="text-sm text-slate-400">-</div>
                                            <?php } ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Pendiente',
                                                'overdue' => 'Vencido',
                                                'paid' => 'Pagado',
                                                'cancelled' => 'Cancelado'
                                            ];
                                            $payStatus = $pay['status'] ?? 'pending';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$payStatus] ?? $statusColors['pending']; ?>">
                                                <?php echo $statusLabels[$payStatus] ?? ucfirst($payStatus); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick="viewPaymentInvoice(<?php echo $pay['id']; ?>, <?php echo $pay['client_id']; ?>, <?php echo !empty($pay['service_id']) ? $pay['service_id'] : 'null'; ?>)" 
                                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" 
                                                        title="Ver Factura">
                                                    <span class="material-symbols-outlined text-lg">receipt</span>
                                                </button>
                                                <button onclick="downloadPaymentInvoicePDF(<?php echo $pay['id']; ?>, <?php echo $pay['client_id']; ?>, <?php echo !empty($pay['service_id']) ? $pay['service_id'] : 'null'; ?>)" 
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                                        title="Descargar PDF">
                                                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                                </button>
                                                <?php if ($pay['status'] === 'pending' || $pay['status'] === 'overdue'): ?>
                                                    <button onclick="openMarkAsPaidModal(<?php echo $pay['id']; ?>, '<?php echo htmlspecialchars($pay['invoice_number']); ?>', <?php echo $pay['amount']; ?>)" 
                                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Marcar como Pagado">
                                                        <span class="material-symbols-outlined text-lg">check_circle</span>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="openEditPaymentModal(<?php echo $pay['id']; ?>)" 
                                                        class="text-primary hover:text-primary/80" title="Editar">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </button>
                                                <?php 
                                                $canDeleteUI = in_array($pay['status'], ['pending', 'overdue', 'cancelled']) || 
                                                              ($pay['status'] === 'paid' && (!isset($pay['service_status']) || !in_array($pay['service_status'], ['completed', 'finished'])));
                                                
                                                if ($canDeleteUI): 
                                                ?>
                                                    <button onclick="confirmDeletePayment(<?php echo $pay['id']; ?>, '<?php echo htmlspecialchars($pay['invoice_number']); ?>')" 
                                                            class="text-red-500 hover:text-red-700" title="Eliminar">
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalPayments); ?> de <?php echo $totalPayments; ?> pagos
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
                                   class="px-3 py-1 border rounded text-sm <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800'; ?>">
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

<!-- New/Edit Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modalTitle">Registrar Pago</h3>
                <button onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_payments.php" id="paymentForm" class="p-6">
            <input type="hidden" name="action" id="paymentAction" value="create_payment">
            <input type="hidden" name="payment_id" id="paymentId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Cliente *
                    </label>
                    <select name="client_id" id="paymentClientId" required onchange="loadPaymentServices(this.value)"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Selecciona un cliente --</option>
                        <?php foreach ($allClients as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Servicio (Opcional)
                    </label>
                    <select name="service_id" id="paymentServiceId"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Sin servicio específico --</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Monto *
                    </label>
                    <input type="number" name="amount" id="paymentAmount" step="0.01" required
                           oninput="updateSplitTotal()"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <!-- Desglose para Servicios Ads -->
                <div id="adsSplitContainer" class="md:col-span-2 hidden bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">campaign</span>
                        <h4 class="font-semibold text-purple-900 dark:text-purple-200">Desglose para Servicio de Ads</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2">
                                Honorarios de Gestión *
                            </label>
                            <input type="number" name="fee_amount" id="feeAmount" step="0.01" min="0"
                                   oninput="updateSplitTotal()"
                                   class="w-full px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Va a reporte de ingresos brutos</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2">
                                Fondo para Inversión *
                            </label>
                            <input type="number" name="ads_amount" id="adsAmount" step="0.01" min="0"
                                   oninput="updateSplitTotal()"
                                   class="w-full px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Acumula en Saldo en Custodia</p>
                        </div>
                    </div>
                    <div class="mt-3 p-2 bg-white dark:bg-slate-800 rounded border border-purple-200 dark:border-purple-700">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-purple-700 dark:text-purple-300 font-medium">Total del desglose:</span>
                            <span id="splitTotal" class="font-bold text-purple-900 dark:text-purple-100">$0.00</span>
                        </div>
                        <div id="splitWarning" class="hidden mt-2 text-xs text-red-600 dark:text-red-400">
                            ⚠️ El total del desglose no coincide con el monto del pago
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Moneda
                    </label>
                    <select name="currency" id="paymentCurrency"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="MXN">MXN - Peso Mexicano</option>
                        <option value="USD">USD - Dólar</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Método de Pago
                    </label>
                    <select name="payment_method" id="paymentMethod"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="transfer">Transferencia</option>
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="check">Cheque</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Pago *
                    </label>
                    <input type="date" name="payment_date" id="paymentDate" required
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Vencimiento
                    </label>
                    <input type="date" name="due_date" id="paymentDueDate"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="status" id="paymentStatus" onchange="togglePaidAt()"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="pending">Pendiente</option>
                        <option value="paid">Pagado</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>
                
                <div id="paidAtContainer" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Pago Realizado
                    </label>
                    <input type="datetime-local" name="paid_at" id="paymentPaidAt"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Número de Referencia
                    </label>
                    <input type="text" name="reference_number" id="paymentReference"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notas
                    </label>
                    <textarea name="notes" id="paymentNotes" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closePaymentModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div id="markAsPaidModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Marcar como Pagado</h3>
        </div>
        
        <form method="POST" action="admin_payments.php" class="p-6">
            <input type="hidden" name="action" value="mark_as_paid">
            <input type="hidden" name="payment_id" id="markPaidPaymentId" value="">
            
            <div class="mb-4">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                    Factura: <span class="font-medium text-slate-900 dark:text-white" id="markPaidInvoiceNumber"></span>
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    Monto: <span class="font-medium text-slate-900 dark:text-white" id="markPaidAmount"></span>
                </p>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Número de Referencia (Opcional)
                </label>
                <input type="text" name="reference_number" 
                       placeholder="Número de transferencia, cheque, etc."
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMarkAsPaidModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Marcar como Pagado
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openNewPaymentModal() {
    document.getElementById('modalTitle').textContent = 'Registrar Pago';
    document.getElementById('paymentAction').value = 'create_payment';
    document.getElementById('paymentId').value = '';
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentDate').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('paidAtContainer').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('hidden');
}

function openEditPaymentModal(paymentId) {
    window.location.href = 'admin_payments.php?edit=' + paymentId;
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function openMarkAsPaidModal(paymentId, invoiceNumber, amount) {
    document.getElementById('markPaidPaymentId').value = paymentId;
    document.getElementById('markPaidInvoiceNumber').textContent = invoiceNumber;
    document.getElementById('markPaidAmount').textContent = '$' + parseFloat(amount).toFixed(2);
    document.getElementById('markAsPaidModal').classList.remove('hidden');
}

function closeMarkAsPaidModal() {
    document.getElementById('markAsPaidModal').classList.add('hidden');
}

function togglePaidAt() {
    const status = document.getElementById('paymentStatus').value;
    const container = document.getElementById('paidAtContainer');
    if (status === 'paid') {
        container.classList.remove('hidden');
        if (!document.getElementById('paymentPaidAt').value) {
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            document.getElementById('paymentPaidAt').value = localDateTime;
        }
    } else {
        container.classList.add('hidden');
    }
}

// Load services for selected client
function loadClientServices(clientId) {
    if (!clientId || clientId === '') {
        const serviceSelect = document.getElementById('filterServiceId');
        if (serviceSelect) {
            serviceSelect.innerHTML = '<option value="">Todos los servicios</option>';
        }
        return;
    }
    
    fetch('?ajax=get_services&client_id=' + clientId)
        .then(response => response.json())
        .then(services => {
            const serviceSelect = document.getElementById('filterServiceId');
            if (serviceSelect) {
                let html = '<option value="">Todos los servicios</option>';
                services.forEach(service => {
                    html += `<option value="${service.id}">${service.service_name}</option>`;
                });
                serviceSelect.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}

function loadPaymentServices(clientId, selectedServiceId = null, feeAmountVal = null, adsAmountVal = null) {
    if (!clientId) {
        document.getElementById('paymentServiceId').innerHTML = '<option value="">-- Sin servicio específico --</option>';
        toggleAdsSplit();
        return;
    }
    
    fetch('?ajax=get_services&client_id=' + clientId)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('paymentServiceId');
            select.innerHTML = '<option value="">-- Sin servicio específico --</option>';
            data.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                option.textContent = service.service_name;
                option.setAttribute('data-is-ads', service.is_ads_service ? '1' : '0');
                select.appendChild(option);
            });
            
            if (selectedServiceId) {
                select.value = selectedServiceId;
            }
            
            toggleAdsSplit();
            
            if (feeAmountVal !== null && feeAmountVal !== undefined) {
                const feeInput = document.getElementById('feeAmount');
                if (feeInput) feeInput.value = feeAmountVal;
            }
            if (adsAmountVal !== null && adsAmountVal !== undefined) {
                const adsInput = document.getElementById('adsAmount');
                if (adsInput) adsInput.value = adsAmountVal;
            }
            
            updateSplitTotal();
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}

// Toggle Ads split section
function toggleAdsSplit() {
    const serviceSelect = document.getElementById('paymentServiceId');
    const splitContainer = document.getElementById('adsSplitContainer');
    const feeAmount = document.getElementById('feeAmount');
    const adsAmount = document.getElementById('adsAmount');
    
    if (!serviceSelect || !splitContainer) return;
    
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    const isAdsService = selectedOption && selectedOption.getAttribute('data-is-ads') === '1';
    
    if (isAdsService) {
        splitContainer.classList.remove('hidden');
        if (feeAmount) {
            feeAmount.required = true;
            feeAmount.setAttribute('required', 'required');
        }
        if (adsAmount) {
            adsAmount.required = true;
            adsAmount.setAttribute('required', 'required');
        }
    } else {
        splitContainer.classList.add('hidden');
        if (feeAmount) {
            feeAmount.required = false;
            feeAmount.removeAttribute('required');
            feeAmount.value = '';
        }
        if (adsAmount) {
            adsAmount.required = false;
            adsAmount.removeAttribute('required');
            adsAmount.value = '';
        }
        updateSplitTotal();
    }
}

// Update split total
function updateSplitTotal() {
    const feeAmount = parseFloat(document.getElementById('feeAmount')?.value || 0);
    const adsAmount = parseFloat(document.getElementById('adsAmount')?.value || 0);
    const totalAmount = parseFloat(document.getElementById('paymentAmount')?.value || 0);
    const splitTotal = feeAmount + adsAmount;
    
    const splitTotalEl = document.getElementById('splitTotal');
    const warningEl = document.getElementById('splitWarning');
    
    if (splitTotalEl) {
        splitTotalEl.textContent = '$' + splitTotal.toFixed(2);
    }
    
    if (warningEl) {
        const diff = Math.abs(splitTotal - totalAmount);
        if (diff > 0.01 && totalAmount > 0) {
            warningEl.classList.remove('hidden');
        } else {
            warningEl.classList.add('hidden');
        }
    }
}

// Add event listener to service select in payment form
document.addEventListener('DOMContentLoaded', function() {
    const paymentServiceSelect = document.getElementById('paymentServiceId');
    if (paymentServiceSelect) {
        paymentServiceSelect.addEventListener('change', toggleAdsSplit);
    }
});

// Load edit payment data if editing
<?php if ($editPayment): ?>
document.addEventListener('DOMContentLoaded', function() {
    const payment = <?php echo json_encode($editPayment); ?>;
    
    document.getElementById('modalTitle').textContent = 'Editar Pago';
    document.getElementById('paymentAction').value = 'update_payment';
    document.getElementById('paymentId').value = payment.id;
    document.getElementById('paymentClientId').value = payment.client_id;
    document.getElementById('paymentAmount').value = payment.amount || '';
    document.getElementById('paymentCurrency').value = payment.currency || 'MXN';
    document.getElementById('paymentMethod').value = payment.payment_method || 'transfer';
    document.getElementById('paymentDate').value = payment.payment_date || '';
    document.getElementById('paymentDueDate').value = payment.due_date || '';
    document.getElementById('paymentStatus').value = payment.status || 'pending';
    document.getElementById('paymentReference').value = payment.reference_number || '';
    document.getElementById('paymentNotes').value = payment.notes || '';
    
    if (payment.status === 'paid' && payment.paid_at) {
        const paidAt = new Date(payment.paid_at);
        const localDateTime = paidAt.toISOString().slice(0, 16);
        document.getElementById('paymentPaidAt').value = localDateTime;
        document.getElementById('paidAtContainer').classList.remove('hidden');
    }
    
    togglePaidAt();
    
    // Load services and pass the selected service and split amounts
    loadPaymentServices(payment.client_id, payment.service_id, payment.fee_amount, payment.ads_amount);
    
    document.getElementById('paymentModal').classList.remove('hidden');
});
<?php endif; ?>

// Close modals on outside click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

document.getElementById('markAsPaidModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMarkAsPaidModal();
    }
});

// View Payment Invoice
function viewPaymentInvoice(paymentId, clientId, serviceId) {
    let url = `generate_invoice.php?client_id=${clientId}&format=html`;
    if (paymentId) {
        url += `&payment_id=${paymentId}`;
    }
    if (serviceId && serviceId !== 'null' && serviceId !== null) {
        url += `&service_id=${serviceId}`;
    }
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes,resizable=yes');
}

// Download Payment Invoice PDF
function downloadPaymentInvoicePDF(paymentId, clientId, serviceId) {
    let url = `generate_invoice.php?client_id=${clientId}&format=pdf`;
    if (paymentId) {
        url += `&payment_id=${paymentId}`;
    }
    if (serviceId && serviceId !== 'null' && serviceId !== null) {
        url += `&service_id=${serviceId}`;
    }
    // Download PDF
    window.location.href = url;
}

// Invoice Modal Functions
function openInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.remove('hidden');
        // Pre-select client if filter is set
        const clientFilter = document.getElementById('filterClientId');
        if (clientFilter && clientFilter.value) {
            document.getElementById('invoice-client-id').value = clientFilter.value;
            loadInvoiceServices(clientFilter.value);
        }
    }
}

function previewInvoice() {
    const form = document.getElementById('send-invoice-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const clientId = formData.get('client_id');
    const invoiceType = formData.get('invoice_type');
    const serviceId = invoiceType === 'service' ? formData.get('service_id') : '';
    
    if (!clientId) {
        alert('Por favor, selecciona un cliente primero');
        return;
    }
    
    // Build preview URL (HTML format)
    let previewUrl = `generate_invoice.php?client_id=${clientId}&format=html`;
    if (serviceId) {
        previewUrl += `&service_id=${serviceId}`;
    }
    
    // Open in new window
    window.open(previewUrl, '_blank', 'width=900,height=800,scrollbars=yes,resizable=yes');
}

function downloadInvoicePDF() {
    const form = document.getElementById('send-invoice-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const clientId = formData.get('client_id');
    const invoiceType = formData.get('invoice_type');
    const serviceId = invoiceType === 'service' ? formData.get('service_id') : '';
    
    if (!clientId) {
        alert('Por favor, selecciona un cliente primero');
        return;
    }
    
    // Build PDF download URL
    let pdfUrl = `generate_invoice.php?client_id=${clientId}&format=pdf`;
    if (serviceId) {
        pdfUrl += `&service_id=${serviceId}`;
    }
    
    // Download PDF
    window.location.href = pdfUrl;
}

function closeInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function loadInvoiceServices(clientId) {
    const serviceSelect = document.getElementById('invoice-service-id');
    
    if (!serviceSelect || !clientId) return;
    
    fetch(`admin_payments.php?ajax=get_services&client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            serviceSelect.innerHTML = '<option value="">Todos los proyectos</option>';
            data.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                option.textContent = service.service_name || 'Servicio';
                serviceSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}

// Send invoice
document.getElementById('send-invoice-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const sendBtn = document.getElementById('send-invoice-btn');
    const btnText = sendBtn.querySelector('.btn-text');
    const btnLoading = sendBtn.querySelector('.btn-loading');
    const statusDiv = document.getElementById('invoice-status');
    
    // Disable button and show loading
    sendBtn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    statusDiv.classList.add('hidden');
    
    fetch('send_invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message;
            if (data.payments_updated !== undefined) {
                if (data.payments_found > 0) {
                    message += ` (${data.payments_updated} de ${data.payments_found} pagos actualizados)`;
                } else {
                    message += ` (No se encontraron pagos para actualizar)`;
                }
            }
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
            statusDiv.innerHTML = `<p class="text-green-800 dark:text-green-300">${message}</p>`;
            statusDiv.classList.remove('hidden');
            
            // Reset form and reload page after 2 seconds to show updated data
            setTimeout(() => {
                closeInvoiceModal();
                this.reset();
                // Reload page to show updated invoice status
                window.location.reload();
            }, 2000);
        } else {
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
            statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">${data.message || 'Error al enviar el recibo'}</p>`;
            statusDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error sending invoice:', error);
        statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
        statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">Error al enviar el recibo. Por favor, intenta de nuevo.</p>`;
        statusDiv.classList.remove('hidden');
    })
    .finally(() => {
        sendBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    });
});

document.getElementById('invoice-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeInvoiceModal();
    }
});
</script>

<!-- Invoice Send Modal -->
<div id="invoice-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-slate-600 dark:text-slate-400">receipt</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Enviar Factura/Recibo</h3>
                </div>
                <button onclick="closeInvoiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
        </div>
        
        <form id="send-invoice-form" class="p-6 space-y-4">
            <div>
                <label for="invoice-client-id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Cliente *
                </label>
                <select id="invoice-client-id" name="client_id" required onchange="loadInvoiceServices(this.value)" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($allClients as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($clientFilter == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['company_name'] ?? $c['contact_name'] ?? 'Cliente'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Tipo de Resumen
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="invoice_type" value="all" checked class="mr-2" onchange="document.getElementById('invoice-service-id').disabled = true;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Todos los proyectos</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="invoice_type" value="service" class="mr-2" onchange="document.getElementById('invoice-service-id').disabled = false;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Proyecto específico</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label for="invoice-service-id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Proyecto
                </label>
                <select id="invoice-service-id" name="service_id" disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Selecciona un cliente primero</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Incluir
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="include_paid" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Pagos realizados</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_pending" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Pagos pendientes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_services" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Detalle de servicios</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Método de envío
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="whatsapp" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">WhatsApp</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="email" class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Email</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="both" class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Ambos</span>
                    </label>
                </div>
            </div>
            
            <div id="invoice-status" class="hidden"></div>
            
            <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex gap-2">
                    <button type="button" onclick="previewInvoice()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">visibility</span>
                        Vista Previa
                    </button>
                    <button type="button" onclick="downloadInvoicePDF()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        Descargar PDF
                    </button>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" id="send-invoice-btn" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="btn-text">Generar y Enviar</span>
                        <span class="btn-loading hidden">
                            <span class="material-symbols-outlined animate-spin text-lg">sync</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Monthly Report Modal -->
<div id="report-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-purple-600 dark:text-purple-400">assessment</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Reporte de Pagos por Cliente</h3>
                </div>
                <button onclick="closeReportModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Seleccionar Mes</label>
                    <input type="month" id="report-month" value="<?php echo date('Y-m'); ?>" 
                           class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button onclick="loadClientReport()" class="mt-7 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">sync</span>
                    Generar
                </button>
            </div>
            
            <div id="report-results" class="space-y-4">
                <p class="text-center text-slate-500 py-8">Selecciona un mes y haz clic en Generar para ver el reporte.</p>
            </div>
        </div>
        
        <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button onclick="closeReportModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Forms for hidden actions -->
<form id="delete-payment-form" method="POST" action="admin_payments.php" class="hidden">
    <input type="hidden" name="action" value="delete_payment">
    <input type="hidden" name="payment_id" id="delete-payment-id">
</form>

<script>
// Report Modal Functions
function openReportModal() {
    const modal = document.getElementById('report-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeReportModal() {
    const modal = document.getElementById('report-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function loadClientReport() {
    const month = document.getElementById('report-month').value;
    const resultsDiv = document.getElementById('report-results');
    
    if (!month) return;
    
    resultsDiv.innerHTML = `
        <div class="flex justify-center py-12">
            <span class="material-symbols-outlined animate-spin text-4xl text-purple-600">sync</span>
        </div>
    `;
    
    fetch(`admin_payments.php?ajax=get_client_report&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                resultsDiv.innerHTML = '<p class="text-center text-slate-500 py-8">No se encontraron pagos pagados para este mes.</p>';
                return;
            }
            
            let html = `
                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-slate-600 dark:text-slate-400">Cliente</th>
                                <th class="px-4 py-2 text-right text-slate-600 dark:text-slate-400">Total Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            `;
            
            let grandTotal = 0;
            data.forEach(item => {
                grandTotal += parseFloat(item.total_amount);
                html += `
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">${item.company_name}</td>
                        <td class="px-4 py-3 text-right text-slate-900 dark:text-white font-bold">$${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(item.total_amount)}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                        <tfoot class="bg-purple-50 dark:bg-purple-900/20">
                            <tr>
                                <td class="px-4 py-3 font-bold text-purple-900 dark:text-purple-300">TOTAL MENSUAL</td>
                                <td class="px-4 py-3 text-right font-black text-purple-900 dark:text-purple-300">$${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(grandTotal)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
            resultsDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading report:', error);
            resultsDiv.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar el reporte.</p>';
        });
}

// Delete Confirmation
function confirmDeletePayment(id, invoice) {
    if (confirm(`¿Estás seguro de que deseas ELIMINAR PERMANENTEMENTE el pago ${invoice}? Esta acción no se puede deshacer.`)) {
        document.getElementById('delete-payment-id').value = id;
        document.getElementById('delete-payment-form').submit();
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>

