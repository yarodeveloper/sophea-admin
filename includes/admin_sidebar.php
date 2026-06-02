<?php
/**
 * SOPHEA - Admin Panel Sidebar
 * 
 * Sidebar navigation component
 */

// Ensure $currentUser is available
if (!isset($currentUser)) {
    $currentUser = ['username' => 'Admin', 'full_name' => 'Administrador'];
}

// Get main logo for sidebar
$mainLogo = '';
try {
    require_once __DIR__ . '/../classes/SiteSettings.php';
    $siteSettings = new SiteSettings();
    $mainLogo = $siteSettings->getMainLogo();
} catch (Exception $e) {
    error_log("Error getting logo in sidebar: " . $e->getMessage());
    $mainLogo = '';
}

// Get new leads count for notification badge
$newLeadsCount = 0;
try {
    // Always get a fresh Database instance to avoid conflicts with $db being PDO connection
    require_once __DIR__ . '/../classes/Database.php';
    $database = Database::getInstance();
    $newLeadsCount = $database->getNewLeadsCount();
} catch (Exception $e) {
    error_log("Error getting new leads count in sidebar: " . $e->getMessage());
    $newLeadsCount = 0;
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
$activeClass = "bg-primary text-white shadow-md shadow-primary/20";
$inactiveClass = "text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark hover:text-primary dark:hover:text-primary";

$menuItems = [
    // Panel de Control
    [
        'icon' => 'dashboard',
        'label' => 'Panel de Control',
        'url' => 'admin_dashboard.php',
        'pages' => ['admin_dashboard.php']
    ],
    [
        'icon' => 'group',
        'label' => 'Clientes',
        'url' => 'admin_clients.php',
        'pages' => ['admin_clients.php', 'admin_client_detail.php']
    ],
    [
        'icon' => 'description',
        'label' => 'Cotizaciones',
        'url' => 'admin_quotes.php',
        'pages' => ['admin_quotes.php']
    ],
    [
        'icon' => 'payments',
        'label' => 'Facturación',
        'url' => 'admin_payments.php',
        'pages' => ['admin_payments.php', 'admin_invoice_history.php']
    ],
    [
        'icon' => 'receipt_long',
        'label' => 'Gastos',
        'url' => 'admin_expenses.php',
        'pages' => ['admin_expenses.php']
    ],
    [
        'icon' => 'group',
        'label' => 'Leads',
        'url' => 'admin.php',
        'pages' => ['admin.php']
    ],
    [
        'icon' => 'chat',
        'label' => 'WhatsApp Marketing',
        'url' => 'admin_whatsapp_marketing_unified.php?tab=dashboard',
        'pages' => ['admin_whatsapp_marketing_unified.php', 'admin_whatsapp_marketing.php']
    ],
    
    // Separador
    ['separator' => true],
    
    // Admin Web
    [
        'icon' => 'web',
        'label' => 'Admin Web',
        'url' => 'admin_web.php?tab=blog',
        'pages' => ['admin_web.php', 'admin_blog.php', 'admin_banner.php', 'admin_testimonials.php']
    ],
    
    // Separador
    ['separator' => true],
    
    // Separador
    ['separator' => true],
    
    // Herramientas y Configuración
    [
        'icon' => 'settings',
        'label' => 'Herramientas y Configuración',
        'url' => 'admin_tools.php?tab=whatsapp_config',
        'pages' => ['admin_tools.php', 'admin_whatsapp_config.php'],
        'check_section' => false
    ]
];

// Determine active menu item
$isActive = function($item) use ($currentPage) {
    $pages = $item['pages'] ?? [];
    
    // Check if this item has a special section check
    if (isset($item['check_section']) && $item['check_section']) {
        $section = $_GET['section'] ?? '';
        return ($currentPage === 'admin.php' && $section === 'tests');
    }
    
    return in_array($currentPage, $pages);
};
?>
<!-- Mobile Overlay (only visible when sidebar is open on mobile) -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="flex w-64 flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark transition-colors duration-200
    fixed md:fixed inset-y-0 left-0 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out h-screen">
    <div class="flex flex-col h-full p-4">
        <!-- Mobile Close Button -->
        <div class="flex items-center justify-between mb-4 md:hidden">
            <div class="flex items-center gap-3">
                <?php if (!empty($mainLogo)): ?>
                    <img src="<?php echo htmlspecialchars($mainLogo); ?>" 
                         alt="SOPHEA Logo" 
                         class="h-10 w-10 rounded-lg object-contain shadow-sm ring-2 ring-primary/20"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <?php endif; ?>
                <div class="bg-center bg-no-repeat bg-cover rounded-full h-10 w-10 shadow-sm ring-2 ring-primary/20 bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center <?php echo !empty($mainLogo) ? 'hidden' : ''; ?>">
                    <span class="text-white font-bold text-lg"><?php echo strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?></span>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-slate-900 dark:text-white text-sm font-bold leading-tight">Sophea</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin'); ?></p>
                </div>
            </div>
            <button id="sidebar-close-btn" class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark transition-colors" aria-label="Cerrar menú">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Desktop Profile / Header (hidden on mobile, shown above) -->
        <div class="hidden md:flex items-center gap-3 mb-8 px-2">
            <?php if (!empty($mainLogo)): ?>
                <img src="<?php echo htmlspecialchars($mainLogo); ?>" 
                     alt="SOPHEA Logo" 
                     class="h-10 w-10 rounded-lg object-contain shadow-sm ring-2 ring-primary/20"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <?php endif; ?>
            <div class="bg-center bg-no-repeat bg-cover rounded-full h-10 w-10 shadow-sm ring-2 ring-primary/20 bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center <?php echo !empty($mainLogo) ? 'hidden' : ''; ?>">
                <span class="text-white font-bold text-lg"><?php echo strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?></span>
            </div>
            <div class="flex flex-col">
                <h1 class="text-slate-900 dark:text-white text-sm font-bold leading-tight">Sophea</h1>
                <p class="text-slate-500 dark:text-slate-400 text-xs font-medium"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin'); ?></p>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex flex-col gap-2 flex-1">
            <?php 
            $currentGroup = '';
            foreach ($menuItems as $item): 
                // Handle separators
                if (isset($item['separator']) && $item['separator']): ?>
                    <div class="my-2 border-t border-slate-200 dark:border-slate-800"></div>
                <?php continue; 
                endif;
                
                $active = $isActive($item);
            ?>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $active ? $activeClass : $inactiveClass; ?> transition-all relative sidebar-link" 
                   href="<?php echo $item['url']; ?>">
                    <span class="material-symbols-outlined <?php echo $active ? 'icon-filled' : ''; ?>" style="font-variation-settings: 'FILL' <?php echo $active ? '1' : '0'; ?>, 'wght' 400, 'GRAD' 0, 'opsz' 24;"><?php echo htmlspecialchars($item['icon']); ?></span>
                    <p class="text-sm <?php echo $active ? 'font-semibold' : 'font-medium'; ?> flex-1"><?php echo $item['label']; ?></p>
                    <?php if ($item['label'] === 'Leads' && $newLeadsCount > 0): ?>
                        <span class="bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5" id="leadsBadge">
                            <?php echo $newLeadsCount > 99 ? '99+' : $newLeadsCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            
            <div class="my-2 border-t border-slate-200 dark:border-slate-800"></div>
            
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-card-dark hover:text-primary dark:hover:text-primary transition-all sidebar-link" 
               href="admin.php?logout=1">
                <span class="material-symbols-outlined">logout</span>
                <p class="text-sm font-medium">Cerrar Sesión</p>
            </a>
        </nav>
    </div>
</aside>

