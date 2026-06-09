<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Gestión de Cotizaciones - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Quote.php';
require_once 'classes/Client.php';
require_once 'classes/Payment.php';
require_once 'classes/Service.php';
require_once 'classes/SiteSettings.php';

// Initialize classes
try {
    $quote = new Quote();
    $client = new Client();
    $payment = new Payment();
    $service = new Service();
    $siteSettings = new SiteSettings();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $quote = null;
    $client = null;
    $payment = null;
    $service = null;
    $siteSettings = null;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Create quote
        if ($action === 'create_quote' && $quote) {
            $items = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['description']) && !empty($item['unit_price'])) {
                        $items[] = [
                            'service_type' => $item['service_type'] ?? 'otro',
                            'item_type'    => in_array($item['item_type'] ?? 'fee', ['fee','ads_investment']) ? $item['item_type'] : 'fee',
                            'ads_platform' => !empty($item['ads_platform']) ? $item['ads_platform'] : null,
                            'description'  => $item['description'],
                            'quantity'     => floatval($item['quantity'] ?? 1),
                            'unit_price'   => floatval($item['unit_price'])
                        ];
                    }
                }
            }
            
            $quoteData = [
                'client_id' => intval($_POST['client_id']),
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? null,
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0.00),
                'currency' => $_POST['currency'] ?? 'MXN',
                'status' => $_POST['status'] ?? 'draft',
                'valid_until' => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
                'notes' => $_POST['notes'] ?? null,
                'terms_conditions' => $_POST['terms_conditions'] ?? null,
                'items' => $items,
                'created_by' => $currentUser['id']
            ];
            
            $quoteId = $quote->createQuote($quoteData);
            
            if ($quoteId) {
                $message = 'Cotización creada exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al crear la cotización';
                $messageType = 'error';
            }
        }
        
        // Update quote
        elseif ($action === 'update_quote' && $quote) {
            $quoteId = intval($_POST['quote_id']);
            
            // Get existing quote to preserve client_id if not provided
            $existingQuote = $quote->getQuoteById($quoteId);
            
            $items = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['description']) && !empty($item['unit_price'])) {
                        $items[] = [
                            'service_type' => $item['service_type'] ?? 'otro',
                            'item_type'    => in_array($item['item_type'] ?? 'fee', ['fee','ads_investment']) ? $item['item_type'] : 'fee',
                            'ads_platform' => !empty($item['ads_platform']) ? $item['ads_platform'] : null,
                            'description'  => $item['description'],
                            'quantity'     => floatval($item['quantity'] ?? 1),
                            'unit_price'   => floatval($item['unit_price'])
                        ];
                    }
                }
            }
            
            $quoteData = [
                'client_id' => isset($_POST['client_id']) ? intval($_POST['client_id']) : ($existingQuote['client_id'] ?? 0),
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? null,
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0.00),
                'currency' => $_POST['currency'] ?? 'MXN',
                'status' => $_POST['status'] ?? 'draft',
                'valid_until' => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
                'notes' => $_POST['notes'] ?? null,
                'terms_conditions' => $_POST['terms_conditions'] ?? null,
                'items' => $items
            ];
            
            $result = $quote->updateQuote($quoteId, $quoteData);
            
            if ($result) {
                $message = 'Cotización actualizada exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al actualizar la cotización';
                $messageType = 'error';
            }
        }
        
        // Change status
        elseif ($action === 'change_status' && $quote) {
            $quoteId = intval($_POST['quote_id']);
            $newStatus = $_POST['new_status'];
            
            // Use updateStatus method which handles timestamps automatically
            $result = $quote->updateStatus($quoteId, $newStatus);
            
            if ($result) {
                $message = 'Estado de cotización actualizado';
                $messageType = 'success';
            } else {
                $message = 'Error al actualizar el estado';
                $messageType = 'error';
            }
        }
        
        // Convert quote to payment/invoice
        elseif ($action === 'convert_to_payment' && $quote && $payment && $service) {
            $quoteId = intval($_POST['quote_id']);
            
            // Get quote data
            $quoteData = $quote->getQuoteById($quoteId);
            
            if (!$quoteData) {
                $message = 'Cotización no encontrada';
                $messageType = 'error';
            } elseif ($quoteData['status'] !== 'accepted') {
                $message = 'Solo se pueden convertir cotizaciones aceptadas';
                $messageType = 'error';
            } else {
                // Get quote items to create services
                $quoteItems = $quoteData['items'] ?? [];
                
                if (empty($quoteItems)) {
                    $message = 'La cotización no tiene items para crear servicios';
                    $messageType = 'error';
                } else {
                    // Group items by service_type
                    $servicesByType = [];
                    require_once 'classes/AppConstants.php';
                    $serviceTypeLabels = AppConstants::getServiceTypes();
                    
                    foreach ($quoteItems as $item) {
                        $serviceType = $item['service_type'] ?? 'otro';
                        if (!isset($servicesByType[$serviceType])) {
                            $servicesByType[$serviceType] = [
                                'items' => [],
                                'total' => 0,
                                'descriptions' => []
                            ];
                        }
                        $servicesByType[$serviceType]['items'][] = $item;
                        $servicesByType[$serviceType]['total'] += floatval($item['total'] ?? 0);
                        if (!empty($item['description'])) {
                            $servicesByType[$serviceType]['descriptions'][] = $item['description'];
                        }
                    }
                    
                    // Create services for each service type
                    $createdServices = [];
                    $firstServiceId = null;
                    
                    foreach ($servicesByType as $serviceType => $serviceData) {
                        $serviceName = ($serviceTypeLabels[$serviceType] ?? ucfirst($serviceType)) . ' - ' . $quoteData['title'];
                        $serviceDescription = implode("\n", array_unique($serviceData['descriptions']));
                        if (empty($serviceDescription)) {
                            $serviceDescription = $quoteData['description'] ?? null;
                        }
                        
                        // Calculate monthly fee (distribute total across items)
                        $monthlyFee = $serviceData['total'] / max(count($serviceData['items']), 1);
                        
                        // Determine if it's an ads service
                        $isAdsService = in_array($serviceType, ['ads', 'ads_facebook', 'ads_google', 'ads_instagram', 'ads_tiktok', 'ads_linkedin', 'ads_other']);
                        
                        $serviceDataToCreate = [
                            'client_id' => $quoteData['client_id'],
                            'quote_id' => $quoteId,
                            'service_type' => $serviceType,
                            'service_name' => $serviceName,
                            'description' => $serviceDescription,
                            'project_description' => $quoteData['description'] ?? null,
                            'monthly_fee' => $monthlyFee,
                            'setup_fee' => 0.00,
                            'billing_cycle' => 'monthly',
                            'start_date' => date('Y-m-d'),
                            'end_date' => null, // Continuous service
                            'renewal_date' => date('Y-m-d', strtotime('+1 month')),
                            'progress_percentage' => 0,
                            'status' => 'active',
                            'is_ads_service' => $isAdsService,
                            'initial_investment_amount' => $isAdsService ? $serviceData['total'] : 0.00,
                            'created_by' => $currentUser['id']
                        ];
                        
                        $serviceId = $service->createService($serviceDataToCreate);
                        
                        if ($serviceId) {
                            $createdServices[] = $serviceId;
                            if ($firstServiceId === null) {
                                $firstServiceId = $serviceId;
                            }
                        } else {
                            error_log("Error creating service for type: " . $serviceType);
                        }
                    }
                    
                    if (empty($createdServices)) {
                        $message = 'Error al crear los servicios desde la cotización';
                        $messageType = 'error';
                    } else {
                        // Create payment from quote, linked to first service
                        $paymentData = [
                            'client_id' => $quoteData['client_id'],
                            'service_id' => $firstServiceId, // Link to first service
                            'quote_id' => $quoteId,
                            'amount' => $quoteData['total'],
                            'currency' => $quoteData['currency'] ?? 'MXN',
                            'payment_method' => 'transfer',
                            'payment_date' => date('Y-m-d'),
                            'due_date' => date('Y-m-d', strtotime('+30 days')), // 30 days from now
                            'status' => 'pending',
                            'notes' => 'Generado desde cotización: ' . $quoteData['quote_number'],
                            'created_by' => $currentUser['id']
                        ];
                        
                        $paymentId = $payment->createPayment($paymentData);
                        
                        if ($paymentId) {
                            // Get the created payment to get invoice number
                            $createdPayment = $payment->getPaymentById($paymentId);
                            $invoiceNumber = $createdPayment['invoice_number'] ?? '';
                            
                            $servicesCount = count($createdServices);
                            $servicesText = $servicesCount === 1 ? 'servicio' : 'servicios';
                            $message = 'Cotización convertida exitosamente: ' . $servicesCount . ' ' . $servicesText . ' creado(s) y factura generada. ';
                            $message .= '<a href="admin_payments.php?search=' . urlencode($invoiceNumber) . '" class="underline">Ver factura</a>';
                            $messageType = 'success';
                        } else {
                            $message = 'Servicios creados pero error al generar la factura';
                            $messageType = 'error';
                        }
                    }
                }
            }
        }
        
        // Save terms and conditions (AJAX)
        elseif ($action === 'save_terms_conditions' && $siteSettings) {
            header('Content-Type: application/json');
            $terms = $_POST['terms_conditions'] ?? '';
            $result = $siteSettings->setSetting('quote_terms_conditions', $terms, 'text');
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Términos y condiciones guardados exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar los términos y condiciones']);
            }
            exit;
        }
        
        // Save bank details (AJAX)
        elseif ($action === 'save_bank_details' && $siteSettings) {
            header('Content-Type: application/json');
            $bankData = [
                'account_holder' => $_POST['account_holder'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'account_number' => $_POST['account_number'] ?? '',
                'clabe' => $_POST['clabe'] ?? '',
                'debit_card' => $_POST['debit_card'] ?? ''
            ];
            $result = $siteSettings->setSetting('quote_bank_details', json_encode($bankData), 'text');
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Datos bancarios guardados exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar los datos bancarios']);
            }
            exit;
        }
    }
}

// Get filters from URL
$statusFilter = $_GET['status'] ?? '';
$clientFilter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$searchQuery = $_GET['search'] ?? '';
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
if (!empty($searchQuery)) {
    $filters['search'] = $searchQuery;
}
$filters['limit'] = $perPage;
$filters['offset'] = $offset;
$filters['order_by'] = 'q.created_at';
$filters['order_dir'] = 'DESC';

// Get quotes
$quotes = [];
$totalQuotes = 0;
$totalPages = 1;

if ($quote) {
    try {
        $quotes = $quote->getAllQuotes($filters);
        $totalQuotes = $quote->getTotalCount($filters);
        $totalPages = ceil($totalQuotes / $perPage);
    } catch (Exception $e) {
        error_log("Error fetching quotes: " . $e->getMessage());
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

// Get quote for editing
$editQuote = null;
$editQuoteId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
if ($editQuoteId > 0 && $quote) {
    try {
        $editQuote = $quote->getQuoteById($editQuoteId);
        if (!$editQuote) {
            $editQuote = null;
        }
    } catch (Exception $e) {
        error_log("Error getting quote for editing: " . $e->getMessage());
        $editQuote = null;
    }
}

// Get client ID for new quote (from URL parameter)
$newClientId = isset($_GET['new_client_id']) ? intval($_GET['new_client_id']) : 0;

// Get default terms and conditions and bank details
$defaultTerms = '';
$bankDetails = [
    'account_holder' => 'Alejandro Montoya Ruiz',
    'bank_name' => 'BBVA',
    'account_number' => '157 304 0456',
    'clabe' => '012 100 01573040456 1',
    'debit_card' => '4152 3143 0071 5342'
];

if ($siteSettings) {
    $defaultTerms = $siteSettings->getSetting('quote_terms_conditions', '');
    if (empty($defaultTerms)) {
        $defaultTerms = "Condiciones Generales del Servicio\nLos precios están expresados en pesos mexicanos (MXN).\nPrecios más IVA, en caso de requerir factura.\nSe requiere un anticipo del 50% para iniciar el servicio.\nManejo de publicidad en META se requiero el pago 100%\nEl 50% restante se liquida a la entrega final del proyecto o según el acuerdo establecido.\nEl tiempo de entrega puede variar según la complejidad del proyecto y se establecerá al confirmar el servicio.\nCambios o ajustes adicionales no contemplados en el alcance inicial pueden generar costos extras.\nLa vigencia de esta propuesta es de 15 días hábiles a partir de su emisión.";
    }
    
    $bankDetailsJson = $siteSettings->getSetting('quote_bank_details', '');
    if (!empty($bankDetailsJson)) {
        $decoded = json_decode($bankDetailsJson, true);
        if ($decoded && is_array($decoded)) {
            $bankDetails = array_merge($bankDetails, $decoded);
        }
    }
}
// Include header
include 'includes/admin_header.php';
?>

<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<style>
    .ql-toolbar {
        background: #f8fafc !important;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        border-color: #cbd5e1 !important;
    }
    .dark .ql-toolbar {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .ql-container {
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        border-color: #cbd5e1 !important;
        font-family: 'Manrope', sans-serif !important;
        font-size: 0.875rem !important;
    }
    .dark .ql-container {
        border-color: #334155 !important;
    }
    .dark .ql-editor.ql-blank::before {
        color: #94a3b8 !important;
    }
    .dark .ql-stroke {
        stroke: #e2e8f0 !important;
    }
    .dark .ql-fill {
        fill: #e2e8f0 !important;
    }
    .dark .ql-picker {
        color: #e2e8f0 !important;
    }
</style>

<?php include 'includes/layout_start.php'; ?>
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">description</span>
                        Gestión de Cotizaciones
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Crea y gestiona cotizaciones para tus clientes</p>
                </div>
                
                <button id="newQuoteButton" 
                        class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Nueva Cotización
                </button>
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

            <!-- Filters -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
                <form method="GET" action="admin_quotes.php" class="flex flex-wrap gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               placeholder="Buscar por número, título o cliente..."
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <select name="status" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los estados</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Enviada</option>
                            <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Aceptada</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rechazada</option>
                            <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expirada</option>
                        </select>
                    </div>
                    
                    <!-- Client Filter -->
                    <div>
                        <select name="client_id" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($allClients as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $clientFilter === $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" 
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition font-medium">
                        Filtrar
                    </button>
                    
                    <!-- Clear Filters -->
                    <?php if ($statusFilter || $clientFilter || $searchQuery): ?>
                        <a href="admin_quotes.php" 
                           class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-6 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium">
                            Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Quotes Table -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p class="text-lg font-medium">No se encontraron cotizaciones</p>
                                            <p class="text-sm"><?php echo $searchQuery || $statusFilter || $clientFilter ? 'Intenta ajustar los filtros' : 'Comienza creando tu primera cotización'; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $q): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($q['quote_number'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($q['company_name'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($q['title'] ?? 'Sin título'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                $<?php echo number_format($q['total'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                                'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                'accepted' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                'expired' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Borrador',
                                                'sent' => 'Enviada',
                                                'accepted' => 'Aceptada',
                                                'rejected' => 'Rechazada',
                                                'expired' => 'Expirada'
                                            ];
                                            $qStatus = $q['status'] ?? 'draft';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$qStatus] ?? $statusColors['draft']; ?>">
                                                <?php echo $statusLabels[$qStatus] ?? ucfirst($qStatus); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                                <?php echo date('d/m/Y', strtotime($q['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick="printQuote(<?php echo $q['id']; ?>)" 
                                                        class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Imprimir">
                                                    <span class="material-symbols-outlined text-lg">print</span>
                                                </button>
                                                <button onclick="exportQuotePDF(<?php echo $q['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Exportar PDF">
                                                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                                </button>
                                                <?php if ($q['status'] === 'accepted'): ?>
                                                    <button onclick="convertQuoteToPayment(<?php echo $q['id']; ?>, '<?php echo htmlspecialchars($q['quote_number'], ENT_QUOTES); ?>')" 
                                                            class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300" title="Convertir a Factura">
                                                        <span class="material-symbols-outlined text-lg">receipt</span>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="openEditQuoteModal(<?php echo $q['id']; ?>)" 
                                                        class="text-primary hover:text-primary/80" title="Editar">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </button>
                                                <button onclick="openChangeStatusModal(<?php echo $q['id']; ?>, '<?php echo $q['status']; ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Cambiar Estado">
                                                    <span class="material-symbols-outlined text-lg">swap_horiz</span>
                                                </button>
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
                            Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalQuotes); ?> de <?php echo $totalQuotes; ?> cotizaciones
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


<!-- New/Edit Quote Modal -->
<div id="quoteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modalTitle">Nueva Cotización</h3>
                <button onclick="closeQuoteModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_quotes.php" id="quoteForm" class="p-6">
            <input type="hidden" name="action" id="quoteAction" value="create_quote">
            <input type="hidden" name="quote_id" id="quoteId" value="">
            
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Cliente *
                    </label>
                    <div class="flex gap-2">
                        <select name="client_id" id="quoteClientId" required
                                class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Selecciona un cliente --</option>
                            <?php foreach ($allClients as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="openQuickClientModal()" 
                                class="bg-primary/10 text-primary hover:bg-primary/20 p-2 rounded-lg transition-colors border border-primary/20"
                                title="Nuevo Cliente">
                            <span class="material-symbols-outlined">person_add</span>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Título *
                    </label>
                    <input type="text" name="title" id="quoteTitle" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="status" id="quoteStatus"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="draft">Borrador</option>
                        <option value="sent">Enviada</option>
                        <option value="accepted">Aceptada</option>
                        <option value="rejected">Rechazada</option>
                        <option value="expired">Expirada</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Válida hasta
                    </label>
                    <input type="date" name="valid_until" id="quoteValidUntil"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Moneda
                    </label>
                    <select name="currency" id="quoteCurrency"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="MXN">MXN - Peso Mexicano</option>
                        <option value="USD">USD - Dólar</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        IVA
                    </label>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="quoteTaxEnabled" checked
                                   onchange="toggleTax()"
                                   class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary focus:ring-2">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Aplicar IVA 16%</span>
                        </label>
                    </div>
                    <input type="hidden" name="tax_rate" id="quoteTaxRate" value="16.00">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Descripción (Formato Enriquecido)
                </label>
                <div id="descriptionEditor" style="height: 150px;" class="bg-white dark:bg-card-dark text-slate-900 dark:text-white"></div>
                <input type="hidden" name="description" id="quoteDescription">
            </div>
            
            <!-- Items Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-slate-900 dark:text-white">Items de la Cotización</h4>
                    <button type="button" onclick="addQuoteItem()" 
                            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Agregar Item
                    </button>
                </div>
                
                <div id="quoteItemsContainer" class="space-y-4">
                    <!-- Items will be added here dynamically -->
                </div>
                
                <!-- Totals -->
                <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Subtotal:</span>
                                <span class="text-slate-900 dark:text-white font-medium" id="quoteSubtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">IVA (<span id="taxRateDisplay">16</span>%):</span>
                                <span class="text-slate-900 dark:text-white font-medium" id="quoteTaxAmount">$0.00</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-slate-300 dark:border-slate-700 pt-2">
                                <span class="text-slate-900 dark:text-white">Total:</span>
                                <span class="text-slate-900 dark:text-white" id="quoteTotal">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Términos y Condiciones
                    </label>
                    <button type="button" onclick="openTermsModal()" 
                            class="text-primary hover:text-primary/80 transition flex items-center gap-1 text-sm"
                            title="Editar términos y condiciones">
                        <span class="material-symbols-outlined text-lg">edit</span>
                        <span>Editar</span>
                    </button>
                </div>
                <textarea name="terms_conditions" id="quoteTerms" rows="4" readonly
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Los términos y condiciones aparecerán aquí..."><?php echo htmlspecialchars($editQuote && isset($editQuote['terms_conditions']) ? $editQuote['terms_conditions'] : $defaultTerms); ?></textarea>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Notas Internas
                </label>
                <textarea name="notes" id="quoteNotes" rows="3"
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            
            <!-- Bank Details Section -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Datos Bancarios</h4>
                    <button type="button" onclick="openBankDetailsModal()" 
                            class="text-primary hover:text-primary/80 transition flex items-center gap-1 text-sm"
                            title="Editar datos bancarios">
                        <span class="material-symbols-outlined text-lg">edit</span>
                        <span>Editar</span>
                    </button>
                </div>
                <div id="bankDetailsDisplay" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                    <!-- Bank details will be displayed here -->
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeQuoteModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Cotización
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Client Modal -->
<div id="quickClientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" style="z-index: 9999;">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Nuevo Cliente Rápido</h3>
                <button onclick="closeQuickClientModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form id="quickClientForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Empresa / Negocio *
                    </label>
                    <input type="text" name="company_name" id="quick_company_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Contacto *
                    </label>
                    <input type="text" name="contact_name" id="quick_contact_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email *
                    </label>
                    <input type="email" name="email" id="quick_email" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        WhatsApp *
                    </label>
                    <div class="flex gap-2">
                        <select name="whatsapp_country_code" 
                                class="w-24 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            <option value="+52" selected>+52 MX</option>
                            <option value="+1">+1 US/CA</option>
                            <option value="+34">+34 ES</option>
                            <option value="+54">+54 AR</option>
                            <option value="+55">+55 BR</option>
                            <option value="+57">+57 CO</option>
                            <option value="+51">+51 PE</option>
                            <option value="+56">+56 CL</option>
                            <option value="+502">+502 GT</option>
                        </select>
                        <input type="tel" name="whatsapp" id="quick_whatsapp" required
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Industria / Especialidad
                    </label>
                    <input type="text" name="industry" id="quick_industry"
                           placeholder="Ej. Medicina, Legal, etc."
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeQuickClientModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" id="submitQuickClient"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">save</span>
                    Crear y Seleccionar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Change Status Modal -->
<div id="changeStatusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Cambiar Estado</h3>
        </div>
        
        <form method="POST" action="admin_quotes.php" class="p-6">
            <input type="hidden" name="action" value="change_status">
            <input type="hidden" name="quote_id" id="statusQuoteId" value="">
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Nuevo Estado
                </label>
                <select name="new_status" id="newStatus" required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="draft">Borrador</option>
                    <option value="sent">Enviada</option>
                    <option value="accepted">Aceptada</option>
                    <option value="rejected">Rechazada</option>
                    <option value="expired">Expirada</option>
                </select>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeChangeStatusModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Cambiar Estado
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Términos y Condiciones</h3>
                <button onclick="closeTermsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form id="termsForm" class="p-6">
            <input type="hidden" name="action" value="save_terms_conditions">
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Términos y Condiciones Generales
                </label>
                <textarea name="terms_conditions" id="termsConditionsInput" rows="12" required
                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($defaultTerms); ?></textarea>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Estos términos se aplicarán por defecto a todas las nuevas cotizaciones.
                </p>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeTermsModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bank Details Modal -->
<div id="bankDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Datos Bancarios</h3>
                <button onclick="closeBankDetailsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form id="bankDetailsForm" class="p-6">
            <input type="hidden" name="action" value="save_bank_details">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Titular de la Cuenta *
                    </label>
                    <input type="text" name="account_holder" id="bankAccountHolder" required
                           value="<?php echo htmlspecialchars($bankDetails['account_holder']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Banco *
                    </label>
                    <input type="text" name="bank_name" id="bankName" required
                           value="<?php echo htmlspecialchars($bankDetails['bank_name']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Número de Cuenta *
                    </label>
                    <input type="text" name="account_number" id="bankAccountNumber" required
                           value="<?php echo htmlspecialchars($bankDetails['account_number']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        CLABE *
                    </label>
                    <input type="text" name="clabe" id="bankClabe" required
                           value="<?php echo htmlspecialchars($bankDetails['clabe']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Tarjeta de Débito
                    </label>
                    <input type="text" name="debit_card" id="bankDebitCard"
                           value="<?php echo htmlspecialchars($bankDetails['debit_card']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeBankDetailsModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
console.log('Admin Quotes Scripts Loading...');
var quill;
document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#descriptionEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ]
        },
        placeholder: 'Escribe una descripción detallada...'
    });
    
    // Sync Quill to hidden input before form submit
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', function() {
            document.getElementById('quoteDescription').value = quill.root.innerHTML;
        });
    }
});

let itemCounter = 0;
const serviceTypeLabels = <?php 
    require_once 'classes/AppConstants.php';
    echo json_encode(AppConstants::getServiceTypes(), JSON_UNESCAPED_UNICODE); 
?>;
const serviceTypes = Object.keys(serviceTypeLabels);

// Modal functions
function openNewQuoteModal(clientId) {
    console.log('openNewQuoteModal called with clientId:', clientId);
    try {
        // Check if modal exists
        const modal = document.getElementById('quoteModal');
        if (!modal) {
            console.error('Modal quoteModal not found');
            alert('Error: No se pudo abrir el modal de cotización');
            return;
        }
        console.log('Modal found, opening...');
        
        // Reset form and modal
        const modalTitle = document.getElementById('modalTitle');
        const quoteAction = document.getElementById('quoteAction');
        const quoteId = document.getElementById('quoteId');
        const quoteForm = document.getElementById('quoteForm');
        const quoteItemsContainer = document.getElementById('quoteItemsContainer');
        
        if (modalTitle) modalTitle.textContent = 'Nueva Cotización';
        if (quoteAction) quoteAction.value = 'create_quote';
        if (quoteId) quoteId.value = '';
        if (quoteForm) quoteForm.reset();
        if (quoteItemsContainer) {
            quoteItemsContainer.innerHTML = '';
        }
        
        // Reset Quill editor
        if (quill) {
            quill.root.innerHTML = '';
        }
        if (document.getElementById('quoteDescription')) {
            document.getElementById('quoteDescription').value = '';
        }
        
        // Reset tax checkbox and rate to default (16%)
        const taxEnabled = document.getElementById('quoteTaxEnabled');
        const taxRateInput = document.getElementById('quoteTaxRate');
        if (taxEnabled) {
            taxEnabled.checked = true;
        }
        if (taxRateInput) {
            taxRateInput.value = '16.00';
        }
        
        // Reset item counter
        if (typeof itemCounter !== 'undefined') {
            itemCounter = 0;
        } else {
            window.itemCounter = 0;
        }
        
        // Add first item
        if (typeof addQuoteItem === 'function') {
            addQuoteItem();
        } else {
            console.warn('addQuoteItem function not found');
        }
        
        // Pre-select client if provided
        if (clientId) {
            const quoteClientId = document.getElementById('quoteClientId');
            if (quoteClientId) {
                quoteClientId.value = clientId;
            }
        }
        
        // Load default terms and conditions (only if field is empty)
        const quoteTerms = document.getElementById('quoteTerms');
        if (quoteTerms && !quoteTerms.value.trim()) {
            quoteTerms.value = <?php echo json_encode($defaultTerms); ?>;
        }
        
        // Update bank details display
        if (typeof updateBankDetailsDisplay === 'function') {
            updateBankDetailsDisplay();
        }
        
        // Show modal
        modal.classList.remove('hidden');
        
        // Update totals if function exists
        if (typeof updateTotals === 'function') {
            updateTotals();
        }
    } catch (error) {
        console.error('Error opening quote modal:', error);
        alert('Error al abrir el modal de cotización: ' + error.message);
    }
}

function openEditQuoteModal(quoteId) {
    // This would load quote data via AJAX or redirect to edit page
    window.location.href = 'admin_quotes.php?edit=' + quoteId;
}

function closeQuoteModal() {
    document.getElementById('quoteModal').classList.add('hidden');
}

function openTermsModal() {
    document.getElementById('termsModal').classList.remove('hidden');
}

function closeTermsModal() {
    document.getElementById('termsModal').classList.add('hidden');
}

function openBankDetailsModal() {
    document.getElementById('bankDetailsModal').classList.remove('hidden');
}

function openQuickClientModal() {
    console.log('Opening Quick Client Modal...');
    const modal = document.getElementById('quickClientModal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        console.error('Quick Client Modal NOT found in DOM!');
    }
}

function closeQuickClientModal() {
    document.getElementById('quickClientModal').classList.add('hidden');
    document.getElementById('quickClientForm').reset();
}

function closeBankDetailsModal() {
    document.getElementById('bankDetailsModal').classList.add('hidden');
}

function printQuote(quoteId) {
    window.open('generate_quote.php?quote_id=' + quoteId + '&format=html', '_blank');
}

function exportQuotePDF(quoteId) {
    window.open('generate_quote.php?quote_id=' + quoteId + '&format=pdf', '_blank');
}

// Convert quote to payment/invoice
function convertQuoteToPayment(quoteId, quoteNumber) {
    if (!confirm(`¿Deseas convertir la cotización "${quoteNumber}" en una factura?\n\nSe creará un pago pendiente con el monto total de la cotización.`)) {
        return;
    }
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin_quotes.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'convert_to_payment';
    form.appendChild(actionInput);
    
    const quoteIdInput = document.createElement('input');
    quoteIdInput.type = 'hidden';
    quoteIdInput.name = 'quote_id';
    quoteIdInput.value = quoteId;
    form.appendChild(quoteIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

function updateBankDetailsDisplay() {
    const display = document.getElementById('bankDetailsDisplay');
    if (!display) return;
    
    const accountHolder = document.getElementById('bankAccountHolder') ? document.getElementById('bankAccountHolder').value : <?php echo json_encode($bankDetails['account_holder']); ?>;
    const bankName = document.getElementById('bankName') ? document.getElementById('bankName').value : <?php echo json_encode($bankDetails['bank_name']); ?>;
    const accountNumber = document.getElementById('bankAccountNumber') ? document.getElementById('bankAccountNumber').value : <?php echo json_encode($bankDetails['account_number']); ?>;
    const clabe = document.getElementById('bankClabe') ? document.getElementById('bankClabe').value : <?php echo json_encode($bankDetails['clabe']); ?>;
    const debitCard = document.getElementById('bankDebitCard') ? document.getElementById('bankDebitCard').value : <?php echo json_encode($bankDetails['debit_card']); ?>;
    
    display.innerHTML = `
        <p><strong>${accountHolder}</strong></p>
        <p>Banco: ${bankName}</p>
        <p>Cuenta: ${accountNumber}</p>
        <p>CLABE: ${clabe}</p>
        ${debitCard ? '<p>Tarjeta de débito: ' + debitCard + '</p>' : ''}
    `;
}

function openChangeStatusModal(quoteId, currentStatus) {
    document.getElementById('statusQuoteId').value = quoteId;
    document.getElementById('newStatus').value = currentStatus;
    document.getElementById('changeStatusModal').classList.remove('hidden');
}

function closeChangeStatusModal() {
    document.getElementById('changeStatusModal').classList.add('hidden');
}

// Quote Items Management
function addQuoteItem(itemData = null) {
    itemCounter++;
    const container = document.getElementById('quoteItemsContainer');
    const itemId = 'item_' + itemCounter;
    
    // Prepare values safely
    const description = itemData && itemData.description ? String(itemData.description).replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
    const quantity = itemData && itemData.quantity ? parseFloat(itemData.quantity) : 1;
    const unitPrice = itemData && itemData.unit_price ? parseFloat(itemData.unit_price) : 0;
    const serviceType = itemData && itemData.service_type ? String(itemData.service_type) : '';
    const itemType = itemData && itemData.item_type ? String(itemData.item_type) : 'fee';
    const adsPlatform = itemData && itemData.ads_platform ? String(itemData.ads_platform) : '';
    
    // ADS service types
    const adsServiceTypes = ['ads','ads_facebook','ads_google','ads_instagram','ads_tiktok','ads_linkedin','ads_other'];
    const isAds = adsServiceTypes.includes(serviceType);
    
    // Build service type options HTML safely
    let optionsHtml = '';
    Object.entries(serviceTypeLabels).forEach(function(entry) {
        const value = entry[0];
        const label = entry[1];
        const selected = serviceType === value ? 'selected' : '';
        optionsHtml += '<option value="' + value + '" ' + selected + '>' + label + '</option>';
    });
    
    // Platform options for ADS
    const platformOptions = [
        {value:'',label:'-- Plataforma --'},
        {value:'meta',label:'Meta (Facebook/Instagram)'},
        {value:'google',label:'Google Ads'},
        {value:'tiktok',label:'TikTok Ads'},
        {value:'linkedin',label:'LinkedIn Ads'},
        {value:'otro',label:'Otra'}
    ];
    let platformHtml = '';
    platformOptions.forEach(function(p) {
        const sel = adsPlatform === p.value ? 'selected' : '';
        platformHtml += '<option value="' + p.value + '" ' + sel + '>' + p.label + '</option>';
    });
    
    const itemHtml = `
        <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4" data-item-id="${itemId}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de Servicio</label>
                    <select name="items[${itemCounter}][service_type]" 
                            class="item-service-type w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                            onchange="onServiceTypeChange(this)">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="md:col-span-5">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción *</label>
                    <input type="text" name="items[${itemCounter}][description]" required
                           value="${description}"
                           class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Cantidad</label>
                    <input type="number" name="items[${itemCounter}][quantity]" step="0.01" value="${quantity}"
                           onchange="updateTotals()" oninput="updateTotals()"
                           class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Precio Unit. *</label>
                    <input type="number" name="items[${itemCounter}][unit_price]" step="0.01" required
                           value="${unitPrice}"
                           onchange="updateTotals()" oninput="updateTotals()"
                           class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="md:col-span-1 flex items-end">
                    <button type="button" data-item-id="${itemId}" 
                            class="w-full px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition remove-item-btn">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
            </div>
            <!-- ADS Fields: item_type + platform (visible only for ADS service types) -->
            <div class="ads-fields mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 p-3 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10 ${isAds ? '' : 'hidden'}">
                <div>
                    <label class="block text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">
                        <span class="material-symbols-outlined text-xs align-middle">campaign</span>
                        Tipo de Concepto ADS
                    </label>
                    <select name="items[${itemCounter}][item_type]" class="item-type-select w-full px-3 py-2 text-sm border border-amber-300 dark:border-amber-700 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="fee" ${itemType === 'fee' ? 'selected' : ''}>💼 Honorario / Fee de manejo</option>
                        <option value="ads_investment" ${itemType === 'ads_investment' ? 'selected' : ''}>📢 Inversión en Plataforma (Pauta)</option>
                    </select>
                </div>
                <div class="ads-platform-field ${itemType === 'ads_investment' ? '' : 'opacity-50'}">
                    <label class="block text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">Plataforma</label>
                    <select name="items[${itemCounter}][ads_platform]" class="ads-platform-select w-full px-3 py-2 text-sm border border-amber-300 dark:border-amber-700 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                        ${platformHtml}
                    </select>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
    
    // Add event listener to the delete button
    const deleteButton = container.querySelector(`button[data-item-id="${itemId}"]`);
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            removeQuoteItem(itemId);
        });
    }
    
    // Listen for item_type change to toggle platform opacity
    const newItem = container.querySelector(`div[data-item-id="${itemId}"]`);
    if (newItem) {
        const itemTypeSelect = newItem.querySelector('.item-type-select');
        const platformField = newItem.querySelector('.ads-platform-field');
        if (itemTypeSelect && platformField) {
            itemTypeSelect.addEventListener('change', function() {
                platformField.classList.toggle('opacity-50', this.value !== 'ads_investment');
            });
        }
    }
    
    updateTotals();
}

// Called when service_type changes: show/hide ADS fields
function onServiceTypeChange(selectEl) {
    const adsTypes = ['ads','ads_facebook','ads_google','ads_instagram','ads_tiktok','ads_linkedin','ads_other'];
    const row = selectEl.closest('[data-item-id]');
    if (!row) return;
    const adsFields = row.querySelector('.ads-fields');
    if (adsFields) {
        if (adsTypes.includes(selectEl.value)) {
            adsFields.classList.remove('hidden');
        } else {
            adsFields.classList.add('hidden');
            // Reset item_type to fee when not an ads service
            const itemTypeSelect = adsFields.querySelector('.item-type-select');
            if (itemTypeSelect) itemTypeSelect.value = 'fee';
        }
    }
    updateTotals();
}


function removeQuoteItem(itemId) {
    // Find the parent container div with the data-item-id attribute
    const item = document.querySelector(`div[data-item-id="${itemId}"]`);
    if (item) {
        item.remove();
        updateTotals();
    }
}

function toggleTax() {
    const taxEnabled = document.getElementById('quoteTaxEnabled');
    const taxRateInput = document.getElementById('quoteTaxRate');
    
    if (taxEnabled.checked) {
        taxRateInput.value = '16.00';
    } else {
        taxRateInput.value = '0.00';
    }
    
    updateTotals();
}

function updateTotals() {
    let subtotal = 0;
    const items = document.querySelectorAll('[data-item-id]');
    
    items.forEach(function(item) {
        const quantityInput = item.querySelector('input[name*="[quantity]"]');
        const unitPriceInput = item.querySelector('input[name*="[unit_price]"]');
        const quantity = parseFloat(quantityInput ? (quantityInput.value || 0) : 0);
        const unitPrice = parseFloat(unitPriceInput ? (unitPriceInput.value || 0) : 0);
        subtotal += quantity * unitPrice;
    });
    
    const taxRateInput = document.getElementById('quoteTaxRate');
    const taxRate = parseFloat(taxRateInput ? (taxRateInput.value || 0) : 0);
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;
    
    document.getElementById('quoteSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('quoteTaxAmount').textContent = '$' + taxAmount.toFixed(2);
    document.getElementById('quoteTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('taxRateDisplay').textContent = taxRate;
}

// Auto-open modal for new quote with pre-selected client
<?php if ($newClientId > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    var clientId = <?php echo json_encode(intval($newClientId)); ?>;
    if (typeof openNewQuoteModal === 'function') {
        openNewQuoteModal(clientId);
    } else {
        console.error('openNewQuoteModal function not found');
        // Retry after a short delay
        setTimeout(function() {
            if (typeof openNewQuoteModal === 'function') {
                openNewQuoteModal(clientId);
            }
        }, 100);
    }
});
<?php endif; ?>

// Load edit quote data if editing
<?php if ($editQuote && is_array($editQuote)): ?>
document.addEventListener('DOMContentLoaded', function() {
    try {
        <?php 
            $json = json_encode($editQuote, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
            if ($json === false || $json === null) {
                echo 'var quoteData = null;';
            } else {
                echo 'var quoteData = ' . $json . ';';
            }
        ?>
        if (!quoteData || typeof quoteData !== 'object' || quoteData === null) {
            console.error('Invalid quote data');
            return;
        }
        const quote = quoteData;
        
        if (quote && typeof quote === 'object') {
            document.getElementById('modalTitle').textContent = 'Editar Cotización';
            document.getElementById('quoteAction').value = 'update_quote';
            document.getElementById('quoteId').value = quote.id || '';
            document.getElementById('quoteClientId').value = quote.client_id || '';
            document.getElementById('quoteTitle').value = quote.title || '';
            if (quill) {
                quill.root.innerHTML = quote.description || '';
            }
            document.getElementById('quoteDescription').value = quote.description || '';
            document.getElementById('quoteStatus').value = quote.status || 'draft';
            document.getElementById('quoteValidUntil').value = quote.valid_until || '';
            document.getElementById('quoteCurrency').value = quote.currency || 'MXN';
            const taxRate = quote.tax_rate || 16.00;
            document.getElementById('quoteTaxRate').value = taxRate;
            const taxEnabled = document.getElementById('quoteTaxEnabled');
            if (taxEnabled) {
                taxEnabled.checked = (taxRate > 0);
            }
            document.getElementById('quoteTerms').value = quote.terms_conditions || '';
            document.getElementById('quoteNotes').value = quote.notes || '';
            
            // Load items
            if (quote.items && Array.isArray(quote.items) && quote.items.length > 0) {
                quote.items.forEach(function(item) {
                    addQuoteItem({
                        service_type: item.service_type || '',
                        item_type:    item.item_type    || 'fee',
                        ads_platform: item.ads_platform || '',
                        description:  item.description  || '',
                        quantity:     item.quantity      || 1,
                        unit_price:   item.unit_price    || 0
                    });
                });
            } else {
                addQuoteItem();
            }
            
            updateTotals();
            document.getElementById('quoteModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading edit quote data:', error);
    }
});
<?php endif; ?>

// Close modals on outside click
var quoteModal = document.getElementById('quoteModal');
if (quoteModal) {
    quoteModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeQuoteModal();
        }
    });
}

var changeStatusModal = document.getElementById('changeStatusModal');
if (changeStatusModal) {
    changeStatusModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeChangeStatusModal();
        }
    });
}

// Close terms modal on outside click
var termsModal = document.getElementById('termsModal');
if (termsModal) {
    termsModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeTermsModal();
        }
    });
}

// Close bank details modal on outside click
var bankDetailsModal = document.getElementById('bankDetailsModal');
if (bankDetailsModal) {
    bankDetailsModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeBankDetailsModal();
        }
    });
}

// Initialize bank details display on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof updateBankDetailsDisplay === 'function') {
        updateBankDetailsDisplay();
    }
    
    // Handle terms form submission
    const termsForm = document.getElementById('termsForm');
    if (termsForm) {
        termsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(termsForm);
            const submitButton = termsForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Guardando...';
            
            fetch('admin_quotes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the quote terms field if modal is open
                    const quoteTerms = document.getElementById('quoteTerms');
                    if (quoteTerms) {
                        quoteTerms.value = document.getElementById('termsConditionsInput').value;
                    }
                    
                    closeTermsModal();
                    alert('Términos y condiciones guardados exitosamente');
                } else {
                    alert('Error: ' + (data.message || 'No se pudieron guardar los términos y condiciones'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar los términos y condiciones');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Handle bank details form submission
    const bankDetailsForm = document.getElementById('bankDetailsForm');
    if (bankDetailsForm) {
        bankDetailsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(bankDetailsForm);
            const submitButton = bankDetailsForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Guardando...';
            
            fetch('admin_quotes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update bank details display
                    if (typeof updateBankDetailsDisplay === 'function') {
                        updateBankDetailsDisplay();
                    }
                    
                    closeBankDetailsModal();
                    alert('Datos bancarios guardados exitosamente');
                } else {
                    alert('Error: ' + (data.message || 'No se pudieron guardar los datos bancarios'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar los datos bancarios');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    // Handle Quick Client form submission
    const quickClientForm = document.getElementById('quickClientForm');
    if (quickClientForm) {
        quickClientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(quickClientForm);
            const submitButton = document.getElementById('submitQuickClient');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">sync</span> Guardando...';
            
            fetch('api_create_client.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 1. Add new option to the client select
                    const clientSelect = document.getElementById('quoteClientId');
                    const option = new Option(data.company_name, data.client_id);
                    clientSelect.add(option);
                    
                    // 2. Select the new client
                    clientSelect.value = data.client_id;
                    
                    // 3. Close modal and reset
                    closeQuickClientModal();
                    
                    // 4. Show success toast (optional, alert for now)
                    alert('Cliente created and selected!');
                } else {
                    alert('Error: ' + (data.error || 'No se pudo crear el cliente'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al conectar con el servidor');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }
});

// Add event listener for new quote button
document.addEventListener('DOMContentLoaded', function() {
    const newQuoteButton = document.getElementById('newQuoteButton');
    if (newQuoteButton) {
        newQuoteButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof openNewQuoteModal === 'function') {
                openNewQuoteModal();
            } else {
                console.error('openNewQuoteModal function not found');
                alert('Error: La función para abrir el modal no está disponible. Por favor, recarga la página.');
            }
        });
    }
});
</script>

<?php include 'includes/layout_end.php'; ?>

