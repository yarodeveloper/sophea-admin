<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Gestión de Servicios - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Service.php';
require_once 'classes/Client.php';
require_once 'classes/ServiceCatalog.php';
require_once 'classes/ProjectTransaction.php';

// Initialize classes
try {
    $service = new Service();
    $client = new Client();
    $serviceCatalog = new ServiceCatalog();
    $projectTransaction = new ProjectTransaction();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $service = null;
    $client = null;
    $serviceCatalog = null;
    $projectTransaction = null;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Create service
        if ($action === 'create_service' && $service) {
            $serviceData = [
                'client_id' => intval($_POST['client_id']),
                'quote_id' => !empty($_POST['quote_id']) ? intval($_POST['quote_id']) : null,
                'service_type' => $_POST['service_type'] ?? 'otro',
                'service_name' => $_POST['service_name'] ?? '',
                'description' => $_POST['description'] ?? null,
                'project_description' => $_POST['project_description'] ?? null,
                'monthly_fee' => floatval($_POST['monthly_fee'] ?? 0),
                'setup_fee' => floatval($_POST['setup_fee'] ?? 0),
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'renewal_date' => !empty($_POST['renewal_date']) ? $_POST['renewal_date'] : null,
                'progress_percentage' => intval($_POST['progress_percentage'] ?? 0),
                'status' => $_POST['status'] ?? 'active',
                'project_url' => !empty($_POST['project_url']) ? $_POST['project_url'] : null,
                'legal_coverage' => !empty($_POST['legal_coverage']) ? $_POST['legal_coverage'] : null,
                'is_ads_service' => isset($_POST['is_ads_service']) ? (bool)$_POST['is_ads_service'] : false,
                'initial_investment_amount' => isset($_POST['initial_investment_amount']) ? floatval($_POST['initial_investment_amount']) : 0.00,
                'initial_investment_platform' => isset($_POST['initial_investment_platform']) ? $_POST['initial_investment_platform'] : null,
                'is_recurring' => isset($_POST['is_recurring']) ? (bool)$_POST['is_recurring'] : false,
                'renewal_mode' => $_POST['renewal_mode'] ?? 'manual',
                'created_by' => $currentUser['id']
            ];
            
            $serviceId = $service->createService($serviceData);
            
            // Si es servicio Ads y tiene inversión inicial, crear transacción income_ads
            if ($serviceId && $serviceData['is_ads_service'] && $serviceData['initial_investment_amount'] > 0 && $projectTransaction) {
                try {
                    // Obtener client_id del servicio recién creado
                    $serviceInfo = $service->getServiceById($serviceId);
                    if ($serviceInfo) {
                        $projectTransaction->createTransaction([
                            'service_id' => $serviceId,
                            'client_id' => $serviceInfo['client_id'],
                            'transaction_type' => 'income_ads',
                            'amount' => $serviceData['initial_investment_amount'],
                            'currency' => 'MXN',
                            'description' => 'Inversión publicitaria inicial del servicio',
                            'platform' => $serviceData['initial_investment_platform'] ?? null,
                            'transaction_date' => $serviceData['start_date'] ?? date('Y-m-d'),
                            'created_by' => $currentUser['id']
                        ]);
                        error_log("Created initial income_ads transaction for service {$serviceId}: {$serviceData['initial_investment_amount']}");
                    }
                } catch (Exception $e) {
                    error_log("Error creating initial income_ads transaction: " . $e->getMessage());
                    // No fallar la creación del servicio si falla la transacción
                }
            }
            
            if ($serviceId) {
                $clientId = $serviceData['client_id'];
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=Servicio creado exitosamente');
                exit;
            } else {
                $message = 'Error al crear el servicio';
                $messageType = 'error';
            }
        }
    }
}

// Get action and client_id from URL
$action = $_GET['action'] ?? 'list';
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Get client data if creating service for specific client
$clientData = null;
if ($clientId > 0 && $client) {
    $clientData = $client->getClientById($clientId);
}

// Get all clients for selector
$allClients = [];
if ($client) {
    try {
        $allClients = $client->getAllClients(['limit' => 1000, 'order_by' => 'company_name', 'order_dir' => 'ASC']);
    } catch (Exception $e) {
        error_log("Error fetching clients: " . $e->getMessage());
    }
}

// Get active services from catalog
$catalogServices = [];
if ($serviceCatalog) {
    try {
        $catalogServices = $serviceCatalog->getActiveServices();
    } catch (Exception $e) {
        error_log("Error fetching catalog services: " . $e->getMessage());
    }
}

$serviceTypeLabels = ServiceCatalog::getServiceTypeLabels();

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
        <!-- Mobile Menu Button -->
        <button id="sidebar-toggle-btn" class="md:hidden fixed top-4 left-4 z-30 p-3 bg-white dark:bg-card-dark rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" aria-label="Abrir menú">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <div class="mx-auto max-w-[1400px] mt-16 md:mt-0">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">rocket_launch</span>
                        <?php echo $action === 'new' ? 'Nuevo Servicio' : 'Gestión de Servicios'; ?>
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">
                        <?php echo $action === 'new' ? 'Crea un nuevo servicio para un cliente' : 'Gestiona los servicios activos de tus clientes'; ?>
                    </p>
                </div>
                
                <?php if ($action === 'new'): ?>
                    <a href="admin_services.php" 
                       class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Volver
                    </a>
                <?php else: ?>
                    <a href="admin_services.php?action=new" 
                       class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Nuevo Servicio
                    </a>
                <?php endif; ?>
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

            <?php if ($action === 'new'): ?>
                <!-- New Service Form -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <form method="POST" action="admin_services.php" id="serviceForm">
                        <input type="hidden" name="action" value="create_service">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Cliente *
                                </label>
                                <select name="client_id" id="serviceClientId" required onchange="loadClientQuotes(this.value)"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Selecciona un cliente --</option>
                                    <?php foreach ($allClients as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $clientId === $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Servicio del Catálogo
                                </label>
                                <select id="catalogServiceSelect" onchange="loadCatalogService(this.value)"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Selecciona un servicio del catálogo (opcional) --</option>
                                    <?php foreach ($catalogServices as $catService): ?>
                                        <option value="<?php echo $catService['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($catService['service_name']); ?>"
                                                data-type="<?php echo htmlspecialchars($catService['service_type']); ?>"
                                                data-price="<?php echo $catService['suggested_price']; ?>"
                                                data-currency="<?php echo htmlspecialchars($catService['currency']); ?>"
                                                data-description="<?php echo htmlspecialchars($catService['description'] ?? ''); ?>"
                                                data-observations="<?php echo htmlspecialchars($catService['observations'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($catService['service_name']); ?> - $<?php echo number_format($catService['suggested_price'], 2); ?> <?php echo htmlspecialchars($catService['currency']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Selecciona un servicio del catálogo para pre-llenar los datos. Puedes editarlos después.
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Tipo de Servicio *
                                </label>
                                <select name="service_type" id="serviceType" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                    <?php foreach ($serviceTypeLabels as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Nombre del Servicio *
                                </label>
                                <input type="text" name="service_name" id="serviceName" required
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Tarifa Mensual
                                </label>
                                <input type="number" name="monthly_fee" id="monthlyFee" step="0.01" min="0" value="0"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Tarifa de Configuración
                                </label>
                                <input type="number" name="setup_fee" id="setupFee" step="0.01" min="0" value="0"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Ciclo de Facturación
                                </label>
                                <select name="billing_cycle" id="billingCycle"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="monthly">Mensual</option>
                                    <option value="quarterly">Trimestral</option>
                                    <option value="yearly">Anual</option>
                                    <option value="one_time">Una sola vez</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Fecha de Inicio *
                                </label>
                                <input type="date" name="start_date" id="startDate" required
                                       value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Fecha de Fin (Opcional)
                                </label>
                                <input type="date" name="end_date" id="endDate"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Estado
                                </label>
                                <select name="status" id="status"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="active">Activo</option>
                                    <option value="paused">Pausado</option>
                                    <option value="completed">Completado</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    URL del Proyecto (Canva, Figma, etc.)
                                </label>
                                <input type="url" name="project_url" id="projectUrl"
                                       placeholder="https://..."
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Descripción
                                </label>
                                <textarea name="description" id="description" rows="3"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Descripción del Proyecto y Alcance
                                </label>
                                <textarea name="project_description" id="projectDescription" rows="4"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Observaciones
                                </label>
                                <textarea name="observations" id="observations" rows="3"
                                          placeholder="Notas adicionales, consideraciones especiales, etc."
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Observaciones del servicio (se mostrarán desde el catálogo si seleccionaste uno)
                                </p>
                                </p>
                            </div>

                            <!-- Recurrencia -->
                            <div class="md:col-span-2 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-center gap-3 mb-4">
                                    <input type="checkbox" name="is_recurring" id="isRecurring" value="1" 
                                           onchange="document.getElementById('renewalModeCard').classList.toggle('hidden', !this.checked)"
                                           class="w-5 h-5 text-blue-600 border-blue-300 rounded focus:ring-blue-500">
                                    <label for="isRecurring" class="text-sm font-semibold text-blue-900 dark:text-blue-200 cursor-pointer">
                                        Servicio Recurrente (Renovación automática cada mes/periodo)
                                    </label>
                                </div>
                                <div id="renewalModeCard" class="hidden ml-8 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">
                                            Modo de Renovación
                                        </label>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="renewal_mode" value="manual" checked 
                                                       class="w-4 h-4 text-blue-600 border-blue-300 focus:ring-blue-500">
                                                <span class="text-sm text-slate-700 dark:text-slate-300">Previa Autorización (Crea borrador)</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="renewal_mode" value="automatic" 
                                                       class="w-4 h-4 text-blue-600 border-blue-300 focus:ring-blue-500">
                                                <span class="text-sm text-slate-700 dark:text-slate-300">Automática (Crea servicio activo)</span>
                                            </label>
                                        </div>
                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                            <strong>Nota:</strong> El nuevo periodo se creará cuando el servicio actual llegue al 100% de progreso y esté totalmente pagado.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Servicio de Ads -->
                            <div class="md:col-span-2 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                <div class="flex items-center gap-3 mb-4">
                                    <input type="checkbox" name="is_ads_service" id="isAdsService" value="1" 
                                           onchange="toggleAdsFields()"
                                           class="w-5 h-5 text-purple-600 border-purple-300 rounded focus:ring-purple-500">
                                    <label for="isAdsService" class="text-sm font-semibold text-purple-900 dark:text-purple-200 cursor-pointer">
                                        Servicio de Ads (con inversión de terceros)
                                    </label>
                                </div>
                                <p class="text-xs text-purple-700 dark:text-purple-300 mb-4">
                                    Marca esta opción si este servicio requiere desglose entre Honorarios de Gestión e Inversión Publicitaria.
                                </p>
                                
                                <!-- Campos de Ads (ocultos por defecto) -->
                                <div id="adsFields" class="hidden space-y-4">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">
                                            <strong>Nota:</strong> La tarifa mensual del servicio representa los honorarios de gestión.
                                        </p>
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            El desglose entre honorarios e inversión publicitaria se realiza al registrar cada pago, no al crear el servicio.
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2">
                                            Monto de Inversión Inicial (Opcional)
                                        </label>
                                        <input type="number" name="initial_investment_amount" id="initialInvestmentAmount" 
                                               step="0.01" min="0" value="0"
                                               class="w-full px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                            Inversión publicitaria inicial (opcional, se creará una transacción income_ads)
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2">
                                            Plataforma (Opcional)
                                        </label>
                                        <select name="initial_investment_platform" id="initialInvestmentPlatform"
                                                class="w-full px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                            <option value="">-- Sin especificar --</option>
                                            <option value="meta">META</option>
                                            <option value="whatsapp">WhatsApp META</option>
                                            <option value="google">Google</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="other">Otra</option>
                                        </select>
                                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                            Plataforma donde se realizará la inversión inicial
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                            <a href="<?php echo $clientId > 0 ? 'admin_client_detail.php?id=' . $clientId : 'admin_services.php'; ?>" 
                               class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                                Crear Servicio
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Services List (placeholder for future implementation) -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-slate-400 mb-4">rocket_launch</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Listado de Servicios</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        El listado completo de servicios estará disponible próximamente.
                    </p>
                    <a href="admin_services.php?action=new" 
                       class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition">
                        <span class="material-symbols-outlined">add</span>
                        Crear Nuevo Servicio
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Load catalog service data
function loadCatalogService(catalogServiceId) {
    if (!catalogServiceId) return;
    
    const select = document.getElementById('catalogServiceSelect');
    const option = select.options[select.selectedIndex];
    
    if (option && option.value) {
        // Fill form fields with catalog service data
        document.getElementById('serviceName').value = option.getAttribute('data-name') || '';
        document.getElementById('serviceType').value = option.getAttribute('data-type') || 'otro';
        document.getElementById('monthlyFee').value = option.getAttribute('data-price') || '0';
        
        const description = option.getAttribute('data-description') || '';
        const observations = option.getAttribute('data-observations') || '';
        
        document.getElementById('description').value = description;
        document.getElementById('observations').value = observations;
        
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-sm';
        notification.textContent = 'Datos del catálogo cargados. Puedes editarlos según sea necesario.';
        document.getElementById('serviceForm').insertBefore(notification, document.getElementById('serviceForm').firstChild);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Load client quotes (for future use)
function loadClientQuotes(clientId) {
    // This could be used to load quotes for the selected client
    // Implementation depends on requirements
}

// Toggle Ads fields visibility
function toggleAdsFields() {
    const checkbox = document.getElementById('isAdsService');
    const adsFields = document.getElementById('adsFields');
    
    if (checkbox.checked) {
        adsFields.classList.remove('hidden');
        if (typeof updateAdsTotal === 'function') updateAdsTotal();
    } else {
        adsFields.classList.add('hidden');
        // Reset values
        document.getElementById('initialInvestmentAmount').value = '0';
        if (document.getElementById('adsTotal')) document.getElementById('adsTotal').textContent = '$0.00';
    }
}

// Auto-check Ads checkbox when service type is Ads
document.getElementById('serviceType').addEventListener('change', function() {
    const isAds = this.value === 'ads' || this.value.startsWith('ads_');
    if (isAds) {
        const checkbox = document.getElementById('isAdsService');
        if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            toggleAdsFields();
        }
    }
});

// Update Ads total (solo para mostrar inversión inicial, ya que monthly_fee es independiente)
function updateAdsTotal() {
    const input = document.getElementById('initialInvestmentAmount');
    const display = document.getElementById('adsTotal');
    if (!input || !display) return;
    
    const investment = parseFloat(input.value) || 0;
    display.textContent = '$' + investment.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>

<?php include 'includes/admin_footer.php'; ?>

