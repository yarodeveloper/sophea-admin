<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Historial de Facturas - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Invoice.php';
require_once 'classes/Client.php';
require_once 'classes/Service.php';

// Initialize classes
try {
    $invoice = new Invoice();
    $client = new Client();
    $service = new Service();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $invoice = null;
    $client = null;
    $service = null;
}

// Get filters from URL
$clientFilter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$serviceFilter = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$formatFilter = $_GET['format'] ?? '';
$sentViaFilter = $_GET['sent_via'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build filters
$filters = [];
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
if (!empty($formatFilter)) {
    $filters['format'] = $formatFilter;
}
if (!empty($sentViaFilter)) {
    $filters['sent_via'] = $sentViaFilter;
}

// Get invoice history
$invoices = [];
$totalInvoices = 0;
$totalPages = 1;

if ($invoice) {
    try {
        $invoices = $invoice->getInvoiceHistory($filters, $perPage, $offset);
        $totalInvoices = $invoice->getInvoiceHistoryCount($filters);
        $totalPages = ceil($totalInvoices / $perPage);
    } catch (Exception $e) {
        error_log("Error fetching invoice history: " . $e->getMessage());
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

// Handle AJAX request for services
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_services' && isset($_GET['client_id'])) {
    header('Content-Type: application/json');
    $clientId = intval($_GET['client_id']);
    $services = [];
    
    if ($service && $clientId > 0) {
        try {
            $services = $service->getServicesByClient($clientId, 'active');
        } catch (Exception $e) {
            error_log("Error fetching services: " . $e->getMessage());
        }
    }
    
    echo json_encode($services);
    exit;
}

// Include header
include 'includes/admin_header.php';
?>

<div class="relative flex h-screen w-full overflow-hidden">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10">
        <div class="mx-auto max-w-[1400px]">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">history</span>
                        Historial de Facturas
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Consulta el historial de facturas y recibos enviados</p>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total de Facturas Enviadas</span>
                        <div class="text-3xl font-bold text-slate-900 dark:text-white mt-1">
                            <?php echo $totalInvoices; ?>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-primary">receipt_long</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
                <form method="GET" action="admin_invoice_history.php" class="flex flex-wrap gap-4">
                    <!-- Client Filter -->
                    <div>
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
                    
                    <!-- Format Filter -->
                    <div>
                        <select name="format" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los formatos</option>
                            <option value="html" <?php echo $formatFilter === 'html' ? 'selected' : ''; ?>>HTML</option>
                            <option value="pdf" <?php echo $formatFilter === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        </select>
                    </div>
                    
                    <!-- Sent Via Filter -->
                    <div>
                        <select name="sent_via" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los métodos</option>
                            <option value="whatsapp" <?php echo $sentViaFilter === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                            <option value="email" <?php echo $sentViaFilter === 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="both" <?php echo $sentViaFilter === 'both' ? 'selected' : ''; ?>>Ambos</option>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                               placeholder="Fecha desde"
                               class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <!-- Date To -->
                    <div>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                               placeholder="Fecha hasta"
                               class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <!-- Submit Button -->
                    <div>
                        <button type="submit" 
                                class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                            <span class="material-symbols-outlined text-lg">search</span>
                            Filtrar
                        </button>
                    </div>
                    
                    <!-- Clear Button -->
                    <div>
                        <a href="admin_invoice_history.php" 
                           class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">clear</span>
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Invoice History Table -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha Envío</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">No. Factura</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Servicio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Monto Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pendiente</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Formato</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Enviado Por</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="10" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">receipt_long</span>
                                            <p>No se encontraron facturas en el historial</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                            <?php 
                                            $sentAt = $inv['sent_at'] ?? null;
                                            if ($sentAt) {
                                                echo date('d/M/Y H:i', strtotime($sentAt));
                                            } else {
                                                echo date('d/M/Y', strtotime($inv['invoice_date']));
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                            <?php echo htmlspecialchars($inv['invoice_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                            <?php echo htmlspecialchars($inv['company_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                            <?php 
                                            if ($inv['service_name']) {
                                                echo htmlspecialchars($inv['service_name']);
                                            } else {
                                                echo '<span class="text-slate-400">Todos los servicios</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-slate-900 dark:text-white">
                                            $<?php echo number_format($inv['total_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">
                                            $<?php echo number_format($inv['paid_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-yellow-600 dark:text-yellow-400">
                                            $<?php echo number_format($inv['remaining_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $inv['format'] === 'pdf' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; ?>">
                                                <?php echo strtoupper($inv['format']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <?php
                                            $sentViaLabels = [
                                                'whatsapp' => 'WhatsApp',
                                                'email' => 'Email',
                                                'both' => 'Ambos'
                                            ];
                                            $sentVia = $inv['sent_via'] ?? 'whatsapp';
                                            $label = $sentViaLabels[$sentVia] ?? $sentVia;
                                            ?>
                                            <span class="inline-flex items-center gap-1 text-xs">
                                                <?php if ($sentVia === 'whatsapp' || $sentVia === 'both'): ?>
                                                    <span class="material-symbols-outlined text-sm">chat</span>
                                                <?php endif; ?>
                                                <?php if ($sentVia === 'email' || $sentVia === 'both'): ?>
                                                    <span class="material-symbols-outlined text-sm">mail</span>
                                                <?php endif; ?>
                                                <?php echo $label; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <div class="flex items-center justify-center gap-2">
                                                <?php if (!empty($inv['invoice_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($inv['invoice_url']); ?>" 
                                                       target="_blank"
                                                       class="text-primary hover:text-primary/80 transition-colors"
                                                       title="Ver factura">
                                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($inv['format'] === 'pdf' && !empty($inv['invoice_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($inv['invoice_url']); ?>&format=pdf" 
                                                       target="_blank"
                                                       class="text-red-600 hover:text-red-800 transition-colors"
                                                       title="Descargar PDF">
                                                        <span class="material-symbols-outlined text-lg">download</span>
                                                    </a>
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
                            Mostrando <?php echo count($invoices); ?> de <?php echo $totalInvoices; ?> facturas
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-1 border rounded-lg <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800'; ?> transition">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">
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

<script>
function loadClientServices(clientId) {
    if (!clientId || clientId === '') {
        document.getElementById('filterServiceId').innerHTML = '<option value="">Todos los servicios</option>';
        return;
    }
    
    fetch('?ajax=get_services&client_id=' + clientId)
        .then(response => response.json())
        .then(services => {
            let html = '<option value="">Todos los servicios</option>';
            services.forEach(service => {
                html += `<option value="${service.id}">${service.service_name}</option>`;
            });
            document.getElementById('filterServiceId').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}
</script>

<?php include 'includes/admin_footer.php'; ?>

