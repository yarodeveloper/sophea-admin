<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Herramientas y Configuración - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/ServiceCatalog.php';

// Get active tab from URL
$activeTab = $_GET['tab'] ?? 'whatsapp_config';

// Ensure it's valid
$validTabs = ['whatsapp_config', 'tests', 'services_catalog'];
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], $validTabs) ? $_GET['tab'] : 'whatsapp_config';

// Get messages from URL
$save_success = isset($_GET['save_success']) && $_GET['save_success'] === '1';
$save_error = isset($_GET['save_error']) ? urldecode($_GET['save_error']) : '';

// Handle Service Catalog form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'service_catalog') {
    $serviceCatalog = new ServiceCatalog();
    $redirectUrl = 'admin_tools.php?tab=services_catalog';
    
    if (isset($_POST['catalog_action'])) {
        $catalogAction = $_POST['catalog_action'];
        
        if ($catalogAction === 'create' || $catalogAction === 'update') {
            $serviceData = [
                'service_name' => $_POST['service_name'] ?? '',
                'service_type' => $_POST['service_type'] ?? 'otro',
                'suggested_price' => floatval($_POST['suggested_price'] ?? 0),
                'currency' => $_POST['currency'] ?? 'MXN',
                'description' => $_POST['description'] ?? null,
                'observations' => $_POST['observations'] ?? null,
                'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true,
                'display_order' => intval($_POST['display_order'] ?? 0),
                'created_by' => $currentUser['id']
            ];
            
            if ($catalogAction === 'create') {
                $result = $serviceCatalog->createService($serviceData);
                if ($result) {
                    header('Location: ' . $redirectUrl . '&save_success=1');
                } else {
                    header('Location: ' . $redirectUrl . '&save_error=' . urlencode('Error al crear el servicio'));
                }
            } else {
                $serviceId = intval($_POST['service_id'] ?? 0);
                $result = $serviceCatalog->updateService($serviceId, $serviceData);
                if ($result) {
                    header('Location: ' . $redirectUrl . '&save_success=1');
                } else {
                    header('Location: ' . $redirectUrl . '&save_error=' . urlencode('Error al actualizar el servicio'));
                }
            }
            exit;
        } elseif ($catalogAction === 'delete') {
            $serviceId = intval($_POST['service_id'] ?? 0);
            $result = $serviceCatalog->deleteService($serviceId);
            if ($result) {
                header('Location: ' . $redirectUrl . '&save_success=1');
            } else {
                header('Location: ' . $redirectUrl . '&save_error=' . urlencode('Error al eliminar el servicio'));
            }
            exit;
        }
    }
}

// Include header with sidebar layout
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
                        <?php if ($activeTab === 'tests'): ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">science</span>
                        <?php elseif ($activeTab === 'services_catalog'): ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">list</span>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">settings</span>
                        <?php endif; ?>
                        Herramientas y Configuración
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">
                        <?php 
                        if ($activeTab === 'services_catalog') {
                            echo 'Gestiona el catálogo de servicios con precios sugeridos';
                        } elseif ($activeTab === 'tests') {
                            echo 'Herramientas de diagnóstico y pruebas';
                        } else {
                            echo 'Gestiona la configuración de WhatsApp y herramientas de diagnóstico';
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
                <div class="border-b border-slate-200 dark:border-slate-800">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <a href="admin_tools.php?tab=whatsapp_config" 
                           class="<?php echo $activeTab === 'whatsapp_config' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">settings</span>
                            Configuración WhatsApp
                        </a>
                        <a href="admin_tools.php?tab=tests" 
                           class="<?php echo $activeTab === 'tests' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">science</span>
                            Tests
                        </a>
                        <a href="admin_tools.php?tab=services_catalog" 
                           class="<?php echo $activeTab === 'services_catalog' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">list</span>
                            Catálogo de Servicios
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($save_success): ?>
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3">check_circle</span>
                        <div>
                            <p class="font-semibold">Configuración guardada exitosamente</p>
                            <p class="text-sm">Los cambios se han aplicado correctamente</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($save_error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3">error</span>
                        <div>
                            <p class="font-semibold">Error al guardar</p>
                            <p class="text-sm"><?php echo htmlspecialchars($save_error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Tab Content -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <?php if ($activeTab === 'whatsapp_config'): ?>
                    <!-- WhatsApp Config Tab Content -->
                    <?php include 'includes/admin_tools_whatsapp_tab.php'; ?>
                    
                <?php elseif ($activeTab === 'tests'): ?>
                    <!-- Tests Tab Content -->
                    <?php include 'includes/admin_tools_tests_tab.php'; ?>
                    
                <?php elseif ($activeTab === 'services_catalog'): ?>
                    <!-- Services Catalog Tab Content -->
                    <?php include 'includes/admin_tools_services_catalog_tab.php'; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/admin_footer.php'; ?>

