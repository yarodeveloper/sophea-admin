<?php
require_once 'admin_auth_helper.php';

// Include required classes
require_once 'classes/WhatsAppMarketing.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'WhatsApp Marketing - Panel de Administración - SOPHEA';

// Handle form submissions - redirect to original page for processing
// This maintains compatibility with existing form handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Redirect to original admin_whatsapp_marketing.php for processing
    $tab = $_GET['tab'] ?? 'dashboard';
    $sectionMap = [
        'campaigns' => 'campaigns',
        'schedule' => 'schedule',
        'segmentation' => 'segmentation',
        'templates' => 'templates',
        'dashboard' => 'dashboard'
    ];
    $section = $sectionMap[$tab] ?? 'dashboard';
    $redirectUrl = 'admin_whatsapp_marketing.php?section=' . $section;
    if (isset($_GET['view'])) {
        $redirectUrl .= '&view=' . $_GET['view'];
    }
    // Build POST data as query string for redirect
    $postData = http_build_query($_POST);
    header('Location: ' . $redirectUrl . '&' . $postData);
    exit;
}

// Get action messages from URL (passed after form processing)
$actionMessage = isset($_GET['actionMessage']) ? urldecode($_GET['actionMessage']) : '';
$actionError = isset($_GET['actionError']) ? urldecode($_GET['actionError']) : '';

// Get active tab from URL
$activeTab = $_GET['tab'] ?? 'dashboard';

// Ensure it's valid
$validTabs = ['dashboard', 'campaigns', 'schedule', 'segmentation', 'templates', 'reports'];
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], $validTabs) ? $_GET['tab'] : 'dashboard';

// Initialize WhatsAppMarketing class
$marketing = new WhatsAppMarketing();

// Get dashboard data (always needed for credits alert)
$creditsInfo = $marketing->getCreditsInfo();
$metrics = $marketing->getDashboardMetrics();
$recentActivity = $marketing->getRecentActivity(5);
$chartData = $marketing->getUsageChartData(30);
$leadStats = $marketing->getLeadStatistics();
$allEspecialidades = $marketing->getAllEspecialidades();

// Get data based on active tab
$campaigns = [];
$currentCampaign = null;
$campaignRecipients = [];
$scheduledCampaigns = [];
$allTags = [];
$allLists = [];
$allCustomTemplates = [];
$leadsForSegmentation = [];

if ($activeTab === 'campaigns' || $activeTab === 'dashboard') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $statusFilter = $_GET['status'] ?? null;
    $campaigns = $marketing->getCampaigns($limit, $offset, $statusFilter);
    
    // Get campaign details if viewing one
    if (isset($_GET['view']) && is_numeric($_GET['view'])) {
        $currentCampaign = $marketing->getCampaign($_GET['view']);
        if ($currentCampaign) {
            $campaignRecipients = $marketing->getCampaignRecipients($_GET['view'], 50);
        }
    }
}

if ($activeTab === 'schedule') {
    // Get scheduled campaigns using getCampaigns with status filter
    $scheduledCampaigns = $marketing->getCampaigns(100, 0, 'scheduled');
}

if ($activeTab === 'segmentation') {
    // Use getAllTags instead of getTags
    $allTags = $marketing->getAllTags();
    $allLists = $marketing->getAllContactLists();
}

if ($activeTab === 'templates') {
    // Use getAllCustomTemplates instead of getTemplates
    $allCustomTemplates = $marketing->getAllCustomTemplates();
}

// Get leads for segmentation (needed for schedule, campaigns, segmentation, templates)
if (in_array($activeTab, ['schedule', 'campaigns', 'segmentation', 'templates'])) {
    $leadsForSegmentation = $marketing->getLeadsForSegmentation();
    if (empty($allTags)) {
        $allTags = $marketing->getAllTags();
    }
    if (empty($allLists)) {
        $allLists = $marketing->getAllContactLists();
    }
    if (empty($allCustomTemplates)) {
        $allCustomTemplates = $marketing->getAllCustomTemplates();
    }
}

// Include header with sidebar layout
include 'includes/admin_header.php';
?>

<?php include 'includes/layout_start.php'; ?>
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">campaign</span>
                        WhatsApp Marketing
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Gestiona campañas, plantillas y segmentación de WhatsApp</p>
                </div>
            </div>
            
            <!-- Credits Alert (if low) -->
            <?php if ($creditsInfo['percentage_used'] > 80): ?>
                <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3">warning</span>
                        <div>
                            <p class="font-semibold">Créditos de WhatsApp bajos</p>
                            <p class="text-sm">Has usado el <?php echo $creditsInfo['percentage_used']; ?>% de tus créditos disponibles</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Tabs Navigation -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
                <div class="border-b border-slate-200 dark:border-slate-800">
                    <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                        <a href="admin_whatsapp_marketing_unified.php?tab=dashboard" 
                           class="<?php echo $activeTab === 'dashboard' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">dashboard</span>
                            Dashboard
                        </a>
                        <a href="admin_whatsapp_marketing_unified.php?tab=campaigns" 
                           class="<?php echo $activeTab === 'campaigns' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">campaign</span>
                            Campañas
                        </a>
                        <a href="admin_whatsapp_marketing_unified.php?tab=schedule" 
                           class="<?php echo $activeTab === 'schedule' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">schedule</span>
                            Programar
                        </a>
                        <a href="admin_whatsapp_marketing_unified.php?tab=segmentation" 
                           class="<?php echo $activeTab === 'segmentation' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">groups</span>
                            Segmentación
                        </a>
                        <a href="admin_whatsapp_marketing_unified.php?tab=templates" 
                           class="<?php echo $activeTab === 'templates' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">description</span>
                            Plantillas
                        </a>
                        <a href="admin_whatsapp_marketing_unified.php?tab=reports" 
                           class="<?php echo $activeTab === 'reports' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-shrink-0 whitespace-nowrap border-b-2 py-4 px-4 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">bar_chart</span>
                            Reportes
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <?php
                // Include the appropriate tab content
                $tabFile = "includes/admin_whatsapp_marketing_{$activeTab}_tab.php";
                if (file_exists($tabFile)) {
                    include $tabFile;
                } else {
                    echo '<div class="text-center py-12">';
                    echo '<span class="material-symbols-outlined text-6xl text-slate-400 mb-4">error</span>';
                    echo '<p class="text-slate-600 dark:text-slate-400">Contenido no disponible aún</p>';
                    echo '</div>';
                }
                ?>
            </div>
<?php include 'includes/layout_end.php'; ?>

