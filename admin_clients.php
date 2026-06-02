<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Gestión de Clientes - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Client.php';
require_once 'classes/Service.php';
require_once 'classes/Quote.php';
require_once 'classes/Payment.php';

// Initialize Client class
try {
    $client = new Client();
    $service = new Service();
    $quote = new Quote();
    $payment = new Payment();
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $client = null;
    $service = null;
    $quote = null;
    $payment = null;
    $db = null;
}

// Function to get states from catalog
function getStatesCatalog($db, $countryCode = 'MX') {
    if (!$db) return [];
    
    try {
        $sql = "SELECT id, state_name, state_code, country_code 
                FROM states_catalog 
                WHERE is_active = TRUE 
                ORDER BY display_order ASC, state_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching states catalog: " . $e->getMessage());
        return [];
    }
}

// Get states catalog
$statesCatalog = getStatesCatalog($db);

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Convert lead to client
        if ($action === 'convert_lead') {
            $leadId = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
            
            if ($leadId > 0 && $client) {
                // Get additional data from form
                $additionalData = [
                    'company_name' => $_POST['company_name'] ?? '',
                    'contact_name' => $_POST['contact_name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'phone_country_code' => $_POST['phone_country_code'] ?? '+52',
                    'whatsapp' => $_POST['whatsapp'] ?? '',
                    'whatsapp_country_code' => $_POST['whatsapp_country_code'] ?? '+52',
                    'address' => $_POST['address'] ?? null,
                    'city' => $_POST['city'] ?? null,
                    'state' => $_POST['state'] ?? null,
                    'industry' => $_POST['industry'] ?? null,
                    'status' => $_POST['status'] ?? 'prospect',
                    'notes' => $_POST['notes'] ?? null
                ];
                
                $result = $client->convertLeadToClient($leadId, $additionalData, $currentUser['id']);
                
                if ($result['success']) {
                    $message = 'Lead convertido a cliente exitosamente. Cliente creado: ' . ($result['client_number'] ?? 'N/A');
                    $messageType = 'success';
                } else {
                    $message = 'Error al convertir lead: ' . ($result['error'] ?? 'Error desconocido');
                    $messageType = 'error';
                }
            }
        }
        
        // Create new client
        elseif ($action === 'create_client') {
            if ($client) {
                // Validate required fields
                $errors = [];
                if (empty($_POST['company_name'])) {
                    $errors[] = 'El nombre de la empresa es requerido';
                }
                if (empty($_POST['contact_name'])) {
                    $errors[] = 'El nombre de contacto es requerido';
                }
                if (empty($_POST['email'])) {
                    $errors[] = 'El email es requerido';
                } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'El email no es válido';
                }
                
                if (!empty($errors)) {
                    $message = 'Error de validación: ' . implode(', ', $errors);
                    $messageType = 'error';
                } else {
                    $clientData = [
                        'company_name' => $_POST['company_name'] ?? '',
                        'contact_name' => $_POST['contact_name'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'phone' => $_POST['phone'] ?? '',
                        'phone_country_code' => $_POST['phone_country_code'] ?? '+52',
                        'whatsapp' => $_POST['whatsapp'] ?? '',
                        'whatsapp_country_code' => $_POST['whatsapp_country_code'] ?? '+52',
                        'address' => $_POST['address'] ?? null,
                        'city' => $_POST['city'] ?? null,
                        'state' => $_POST['state'] ?? null,
                        'country' => $_POST['country'] ?? 'México',
                        'tax_id' => $_POST['tax_id'] ?? null,
                        'website' => $_POST['website'] ?? null,
                        'industry' => $_POST['industry'] ?? null,
                        'client_type' => $_POST['client_type'] ?? 'regular',
                        'legal_risk' => $_POST['legal_risk'] ?? 'low',
                        'status' => $_POST['status'] ?? 'prospect',
                        'notes' => $_POST['notes'] ?? null,
                        'created_by' => $currentUser['id']
                    ];
                    
                    $clientId = $client->createClient($clientData);
                    
                    if ($clientId) {
                        $message = 'Cliente creado exitosamente';
                        $messageType = 'success';
                    } else {
                        $message = 'Error al crear el cliente. Por favor, revisa los logs del servidor o contacta al administrador.';
                        $messageType = 'error';
                        error_log("Failed to create client. User ID: " . $currentUser['id'] . ", Data: " . print_r($clientData, true));
                    }
                }
            } else {
                $message = 'Error: No se pudo inicializar la clase Client';
                $messageType = 'error';
            }
        }
    }
}

// Get filters from URL
$statusFilter = $_GET['status'] ?? '';
$clientTypeFilter = $_GET['client_type'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build filters
$filters = [];
if (!empty($statusFilter)) {
    $filters['status'] = $statusFilter;
}
if (!empty($clientTypeFilter)) {
    $filters['client_type'] = $clientTypeFilter;
}
if (!empty($searchQuery)) {
    $filters['search'] = $searchQuery;
}
$filters['limit'] = $perPage;
$filters['offset'] = $offset;
$filters['order_by'] = 'active_projects_count';
$filters['order_dir'] = 'DESC';

// Get clients
$clients = [];
$totalClients = 0;
$totalPages = 1;

if ($client) {
    try {
        $clients = $client->getAllClients($filters);
        $totalClients = $client->getTotalCount($filters);
        $totalPages = ceil($totalClients / $perPage);
    } catch (Exception $e) {
        error_log("Error fetching clients: " . $e->getMessage());
    }
}

// Get available leads for conversion
$availableLeads = [];
if ($client) {
    try {
        $availableLeads = $client->getAvailableLeads(50);
    } catch (Exception $e) {
        error_log("Error fetching available leads: " . $e->getMessage());
    }
}

// Calculate statistics for indicators
$pendingQuotesCount = 0;
$pendingQuotesValue = 0;
$completedProjectsCount = 0;
$completedProjectsValue = 0;

if ($quote && $db) {
    try {
        // Get pending quotes (draft, sent, accepted) and their total value
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total_value 
                FROM quotes 
                WHERE status IN ('draft', 'sent', 'accepted')";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pendingQuotesCount = intval($result['count'] ?? 0);
        $pendingQuotesValue = floatval($result['total_value'] ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating pending quotes: " . $e->getMessage());
    }
}

if ($service && $payment && $db) {
    try {
        // Get completed projects (services with status='completed') and their total value
        $sql = "SELECT COUNT(*) as count 
                FROM services 
                WHERE status = 'completed'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $completedProjectsCount = intval($result['count'] ?? 0);
        
        // Calculate total value of completed projects (sum of payments paid for completed services)
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as total_value 
                FROM payments p
                INNER JOIN services s ON p.service_id = s.id
                WHERE s.status = 'completed' AND p.status = 'paid'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $completedProjectsValue = floatval($result['total_value'] ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating completed projects: " . $e->getMessage());
    }
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
        <div class="mx-auto max-w-[1400px]">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">group</span>
                        Gestión de Clientes
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Administra tus clientes y convierte leads en clientes</p>
                </div>
                
                <div class="flex gap-2">
                    <!-- Monthly Report Button -->
                    <button onclick="openReportModal()" 
                            class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base">assessment</span>
                        Reporte
                    </button>
                    
                    <!-- Convert Lead Button -->
                    <button onclick="openConvertLeadModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base">swap_horiz</span>
                        Convertir Lead
                    </button>
                    
                    <!-- New Client Button -->
                    <button onclick="openNewClientModal()" 
                            class="bg-primary hover:bg-primary/90 text-white px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base">add</span>
                        Nuevo Cliente
                    </button>
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Clients -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Clientes</span>
                        <span class="material-symbols-outlined text-2xl text-primary">group</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo number_format($totalClients); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Según filtros aplicados</div>
                </div>

                <!-- Pending Quotes -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Cotizaciones Pendientes</span>
                        <span class="material-symbols-outlined text-2xl text-orange-500">pending_actions</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo number_format($pendingQuotesCount); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Valor: $<?php echo number_format($pendingQuotesValue, 2); ?>
                    </div>
                </div>

                <!-- Completed Projects -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Proyectos Concluidos</span>
                        <span class="material-symbols-outlined text-2xl text-green-500">check_circle</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo number_format($completedProjectsCount); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Valor: $<?php echo number_format($completedProjectsValue, 2); ?>
                    </div>
                </div>

                <!-- Active Services -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Servicios Activos</span>
                        <span class="material-symbols-outlined text-2xl text-blue-500">rocket_launch</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php 
                        $totalActiveServices = 0;
                        if ($service && $db) {
                            try {
                                $sql = "SELECT COUNT(*) as count FROM services WHERE status = 'active'";
                                $stmt = $db->prepare($sql);
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $totalActiveServices = intval($result['count'] ?? 0);
                            } catch (Exception $e) {
                                error_log("Error calculating active services: " . $e->getMessage());
                            }
                        }
                        echo number_format($totalActiveServices);
                        ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">En todos los clientes</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
                <form method="GET" action="admin_clients.php" class="flex flex-wrap gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               placeholder="Buscar por nombre, empresa o email..."
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <select name="status" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los estados</option>
                            <option value="prospect" <?php echo $statusFilter === 'prospect' ? 'selected' : ''; ?>>Prospecto</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                        </select>
                    </div>
                    
                    <!-- Client Type Filter -->
                    <div>
                        <select name="client_type" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los tipos</option>
                            <option value="prospect" <?php echo $clientTypeFilter === 'prospect' ? 'selected' : ''; ?>>Prospecto</option>
                            <option value="regular" <?php echo $clientTypeFilter === 'regular' ? 'selected' : ''; ?>>Regular</option>
                            <option value="strategic_partner" <?php echo $clientTypeFilter === 'strategic_partner' ? 'selected' : ''; ?>>Socio Estratégico</option>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" 
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition font-medium">
                        Filtrar
                    </button>
                    
                    <!-- Clear Filters -->
                    <?php if ($statusFilter || $clientTypeFilter || $searchQuery): ?>
                        <a href="admin_clients.php" 
                           class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-6 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium">
                            Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Clients Table -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contacto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p class="text-lg font-medium">No se encontraron clientes</p>
                                            <p class="text-sm"><?php echo $searchQuery || $statusFilter || $clientTypeFilter ? 'Intenta ajustar los filtros' : 'Comienza creando tu primer cliente'; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clients as $c): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <?php 
                                                $overdueCount = intval($c['overdue_services_count'] ?? 0);
                                                $expiringCount = intval($c['expiring_services_count'] ?? 0);
                                                $activeCount = intval($c['active_services_count'] ?? 0);
                                                
                                                if ($overdueCount > 0): ?>
                                                    <div class="flex flex-col items-center gap-0.5" title="<?php echo $overdueCount; ?> servicio(s) vencidos">
                                                        <span class="material-symbols-outlined text-red-500 dark:text-red-400 text-2xl animate-pulse" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">
                                                            warning
                                                        </span>
                                                        <span class="text-[10px] text-red-600 dark:text-red-400 font-black uppercase leading-none">Vencido</span>
                                                    </div>
                                                <?php elseif ($expiringCount > 0): ?>
                                                    <div class="flex flex-col items-center gap-0.5" title="<?php echo $expiringCount; ?> servicio(s) por vencer próximamente">
                                                        <span class="material-symbols-outlined text-amber-500 dark:text-amber-400 text-2xl" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">
                                                            notifications_active
                                                        </span>
                                                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-black uppercase leading-none">Renovar</span>
                                                    </div>
                                                <?php elseif ($activeCount > 0): ?>
                                                    <div class="flex flex-col items-center gap-0.5" title="<?php echo $activeCount; ?> servicio(s) activo(s)">
                                                        <span class="material-symbols-outlined text-green-500 dark:text-green-400 text-2xl" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">
                                                            check_circle
                                                        </span>
                                                        <span class="text-[10px] text-green-600 dark:text-green-400 font-black uppercase leading-none">Activo</span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <a href="admin_client_detail.php?id=<?php echo $c['id']; ?>" 
                                                       class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary dark:hover:text-primary transition-colors cursor-pointer">
                                                        <?php echo htmlspecialchars($c['company_name']); ?>
                                                    </a>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                                        <?php echo htmlspecialchars($c['client_number']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="admin_client_detail.php?id=<?php echo $c['id']; ?>" 
                                               class="text-sm text-slate-900 dark:text-white hover:text-primary dark:hover:text-primary transition-colors cursor-pointer">
                                                <?php echo htmlspecialchars($c['contact_name']); ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-600 dark:text-slate-300">
                                                <?php echo htmlspecialchars($c['phone'] ?? '-'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'prospect' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                                'archived' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                            ];
                                            $statusLabels = [
                                                'prospect' => 'Prospecto',
                                                'active' => 'Activo',
                                                'inactive' => 'Inactivo',
                                                'archived' => 'Archivado'
                                            ];
                                            $status = $c['status'] ?? 'prospect';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$status] ?? $statusColors['prospect']; ?>">
                                                <?php echo $statusLabels[$status] ?? ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-600 dark:text-slate-300">
                                                <?php
                                                $typeLabels = [
                                                    'prospect' => 'Prospecto',
                                                    'regular' => 'Regular',
                                                    'strategic_partner' => 'Socio Estratégico'
                                                ];
                                                echo $typeLabels[$c['client_type']] ?? ucfirst($c['client_type']);
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_client_detail.php?id=<?php echo $c['id']; ?>" 
                                               class="bg-slate-100 dark:bg-slate-800 p-2 rounded-lg text-primary hover:bg-primary hover:text-white transition-all inline-flex items-center justify-center shadow-sm border border-slate-200 dark:border-slate-700"
                                               title="Ver Detalle">
                                                <span class="material-symbols-outlined text-xl">visibility</span>
                                            </a>
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
                            Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalClients); ?> de <?php echo $totalClients; ?> clientes
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

<!-- Convert Lead Modal -->
<div id="convertLeadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Convertir Lead a Cliente</h3>
                <button onclick="closeConvertLeadModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_clients.php" class="p-6">
            <input type="hidden" name="action" value="convert_lead">
            
            <!-- Lead Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Seleccionar Lead
                </label>
                <select name="lead_id" id="lead_select" required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        onchange="loadLeadData(this.value)">
                    <option value="">-- Selecciona un lead --</option>
                    <?php foreach ($availableLeads as $lead): ?>
                        <option value="<?php echo $lead['id']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($lead['nombre']); ?>"
                                data-especialidad="<?php echo htmlspecialchars($lead['especialidad']); ?>"
                                data-whatsapp="<?php echo htmlspecialchars($lead['whatsapp']); ?>"
                                data-mensaje="<?php echo htmlspecialchars($lead['mensaje'] ?? ''); ?>">
                            <?php echo htmlspecialchars($lead['nombre'] . ' - ' . $lead['especialidad'] . ' (' . $lead['whatsapp'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableLeads)): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">No hay leads disponibles para convertir</p>
                <?php endif; ?>
            </div>
            
            <!-- Client Data Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre de Empresa *
                    </label>
                    <input type="text" name="company_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre de Contacto *
                    </label>
                    <input type="text" name="contact_name" id="contact_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email *
                    </label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Teléfono
                    </label>
                    <div class="flex gap-2">
                        <select name="phone_country_code" id="phoneCountryCode"
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
                            <option value="+503">+503 SV</option>
                            <option value="+504">+504 HN</option>
                            <option value="+505">+505 NI</option>
                            <option value="+506">+506 CR</option>
                            <option value="+507">+507 PA</option>
                        </select>
                        <input type="tel" name="phone" id="phoneInput" 
                               pattern="[0-9]{10,15}" 
                               placeholder="1234567890"
                               oninput="validatePhone(this)"
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="phoneError"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        WhatsApp *
                    </label>
                    <div class="flex gap-2">
                        <select name="whatsapp_country_code" id="whatsappCountryCode"
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
                            <option value="+503">+503 SV</option>
                            <option value="+504">+504 HN</option>
                            <option value="+505">+505 NI</option>
                            <option value="+506">+506 CR</option>
                            <option value="+507">+507 PA</option>
                        </select>
                        <input type="tel" name="whatsapp" id="whatsappInput" required
                               pattern="[0-9]{10,15}" 
                               placeholder="1234567890"
                               oninput="validateWhatsApp(this)"
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="whatsappError"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Industria
                    </label>
                    <input type="text" name="industry" id="industry"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Dirección
                    </label>
                    <input type="text" name="address"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Ciudad
                    </label>
                    <input type="text" name="city"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="state" id="stateSelectConvert"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Selecciona un estado --</option>
                        <?php foreach ($statesCatalog as $state): ?>
                            <option value="<?php echo htmlspecialchars($state['state_name']); ?>">
                                <?php echo htmlspecialchars($state['state_name']); ?>
                                <?php if ($state['country_code'] !== 'XX'): ?>
                                    (<?php echo htmlspecialchars($state['country_code']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado del Cliente
                    </label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="prospect">Prospecto</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notas Adicionales
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeConvertLeadModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Convertir a Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<!-- New Client Modal -->
<div id="newClientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Nuevo Cliente</h3>
                <button onclick="closeNewClientModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_clients.php" class="p-6">
            <input type="hidden" name="action" value="create_client">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre de Empresa *
                    </label>
                    <input type="text" name="company_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre de Contacto *
                    </label>
                    <input type="text" name="contact_name" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email *
                    </label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Teléfono
                    </label>
                    <div class="flex gap-2">
                        <select name="phone_country_code" id="phoneCountryCodeNew"
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
                            <option value="+503">+503 SV</option>
                            <option value="+504">+504 HN</option>
                            <option value="+505">+505 NI</option>
                            <option value="+506">+506 CR</option>
                            <option value="+507">+507 PA</option>
                        </select>
                        <input type="tel" name="phone" id="phoneInputNew" 
                               pattern="[0-9]{10,15}" 
                               placeholder="1234567890"
                               oninput="validatePhone(this)"
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="phoneErrorNew"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        WhatsApp
                    </label>
                    <div class="flex gap-2">
                        <select name="whatsapp_country_code" id="whatsappCountryCodeNew"
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
                            <option value="+503">+503 SV</option>
                            <option value="+504">+504 HN</option>
                            <option value="+505">+505 NI</option>
                            <option value="+506">+506 CR</option>
                            <option value="+507">+507 PA</option>
                        </select>
                        <input type="tel" name="whatsapp" id="whatsappInputNew"
                               pattern="[0-9]{10,15}" 
                               placeholder="1234567890"
                               oninput="validateWhatsApp(this)"
                               class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="whatsappErrorNew"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Industria
                    </label>
                    <input type="text" name="industry"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Dirección
                    </label>
                    <input type="text" name="address"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Ciudad
                    </label>
                    <input type="text" name="city"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="state" id="stateSelectNew"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Selecciona un estado --</option>
                        <?php foreach ($statesCatalog as $state): ?>
                            <option value="<?php echo htmlspecialchars($state['state_name']); ?>">
                                <?php echo htmlspecialchars($state['state_name']); ?>
                                <?php if ($state['country_code'] !== 'XX'): ?>
                                    (<?php echo htmlspecialchars($state['country_code']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        País
                    </label>
                    <input type="text" name="country" value="México"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        RFC / ID Fiscal
                    </label>
                    <input type="text" name="tax_id"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Sitio Web
                    </label>
                    <input type="url" name="website"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Tipo de Cliente
                    </label>
                    <select name="client_type"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="prospect">Prospecto</option>
                        <option value="regular" selected>Regular</option>
                        <option value="strategic_partner">Socio Estratégico</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado del Cliente
                    </label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="prospect">Prospecto</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notas
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeNewClientModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Crear Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openConvertLeadModal() {
    document.getElementById('convertLeadModal').classList.remove('hidden');
}

function closeConvertLeadModal() {
    document.getElementById('convertLeadModal').classList.add('hidden');
}

function openNewClientModal() {
    document.getElementById('newClientModal').classList.remove('hidden');
}

function closeNewClientModal() {
    document.getElementById('newClientModal').classList.add('hidden');
}

// Load lead data into form when lead is selected
function loadLeadData(leadId) {
    const select = document.getElementById('lead_select');
    const option = select.options[select.selectedIndex];
    
    if (option && option.value) {
        // Fill form fields with lead data
        document.getElementById('contact_name').value = option.getAttribute('data-nombre') || '';
        document.getElementById('whatsapp').value = option.getAttribute('data-whatsapp') || '';
        document.getElementById('industry').value = option.getAttribute('data-especialidad') || '';
        
        // Set company name to contact name if not provided
        const companyNameInput = document.querySelector('input[name="company_name"]');
        if (companyNameInput && !companyNameInput.value) {
            companyNameInput.value = option.getAttribute('data-nombre') || '';
        }
    }
}

// Close modals on outside click
document.getElementById('convertLeadModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeConvertLeadModal();
    }
});

document.getElementById('newClientModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeNewClientModal();
    }
});

// Phone validation functions
function validatePhone(input) {
    const value = input.value.replace(/\D/g, ''); // Remove non-digits
    const errorElement = input.id === 'phoneInput' ? document.getElementById('phoneError') : document.getElementById('phoneErrorNew');
    
    // Update input with only digits
    input.value = value;
    
    // Validate length (10-15 digits)
    if (value.length > 0 && (value.length < 10 || value.length > 15)) {
        input.classList.add('border-red-500');
        if (errorElement) {
            errorElement.textContent = 'El número debe tener entre 10 y 15 dígitos';
            errorElement.classList.add('text-red-600', 'dark:text-red-400');
        }
        return false;
    } else {
        input.classList.remove('border-red-500');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('text-red-600', 'dark:text-red-400');
        }
        return true;
    }
}

function validateWhatsApp(input) {
    const value = input.value.replace(/\D/g, ''); // Remove non-digits
    const errorElement = input.id === 'whatsappInput' ? document.getElementById('whatsappError') : document.getElementById('whatsappErrorNew');
    
    // Update input with only digits
    input.value = value;
    
    // Validate length (10-15 digits)
    if (value.length > 0 && (value.length < 10 || value.length > 15)) {
        input.classList.add('border-red-500');
        if (errorElement) {
            errorElement.textContent = 'El número debe tener entre 10 y 15 dígitos';
            errorElement.classList.add('text-red-600', 'dark:text-red-400');
        }
        return false;
    } else if (value.length === 0 && input.hasAttribute('required')) {
        input.classList.add('border-red-500');
        if (errorElement) {
            errorElement.textContent = 'El número de WhatsApp es requerido';
            errorElement.classList.add('text-red-600', 'dark:text-red-400');
        }
        return false;
    } else {
        input.classList.remove('border-red-500');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('text-red-600', 'dark:text-red-400');
        }
        return true;
    }
}

// Add validation on form submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate phone if present
        const phoneInput = form.querySelector('input[name="phone"]');
        if (phoneInput && phoneInput.value) {
            if (!validatePhone(phoneInput)) {
                isValid = false;
            }
        }
        
        // Validate WhatsApp
        const whatsappInput = form.querySelector('input[name="whatsapp"]');
        if (whatsappInput) {
            if (!validateWhatsApp(whatsappInput)) {
                isValid = false;
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
});
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
    
    // We call the AJAX endpoint in admin_payments.php
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

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    const reportModal = document.getElementById('report-modal');
    if (e.target === reportModal) {
        reportModal.classList.add('hidden');
    }
});
</script>

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

<?php include 'includes/admin_footer.php'; ?>

