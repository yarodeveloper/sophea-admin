<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'WhatsApp Marketing - Panel de Administración - SOPHEA';

// Load configurations
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/WhatsAppMarketing.php';

// User is logged in, show dashboard
$currentUser = ['username' => 'Admin', 'id' => null]; // Usuario simple para compatibilidad
$marketing = new WhatsAppMarketing();

// Handle campaign actions
$actionMessage = '';
$actionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_campaign':
                $campaignData = [
                    'name' => $_POST['name'] ?? '',
                    'type' => $_POST['type'] ?? 'personalizado',
                    'template_name' => !empty($_POST['template_name']) ? $_POST['template_name'] : null,
                    'message_text' => $_POST['message_text'] ?? '',
                    'send_immediately' => isset($_POST['send_immediately']),
                    'scheduled_at' => !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null,
                    'respect_business_hours' => isset($_POST['respect_business_hours']),
                    'exclude_weekends' => isset($_POST['exclude_weekends']),
                    'filter_criteria' => array_merge([
                        'status' => $_POST['filter_status'] ?? null,
                        'especialidad' => $_POST['filter_especialidad'] ?? null,
                        'date_from' => $_POST['filter_date_from'] ?? null,
                        'date_to' => $_POST['filter_date_to'] ?? null,
                        'tags' => !empty($_POST['filter_tags']) ? $_POST['filter_tags'] : null,
                        'exclude_tags' => !empty($_POST['filter_exclude_tags']) ? $_POST['filter_exclude_tags'] : null,
                        'contact_list_id' => !empty($_POST['filter_list_id']) ? $_POST['filter_list_id'] : null,
                        'exclude_list_id' => !empty($_POST['filter_exclude_list_id']) ? $_POST['filter_exclude_list_id'] : null,
                        'source' => $_POST['filter_source'] ?? null,
                    ], $_POST['filter_criteria'] ?? []),
                    'created_by' => null
                ];
                
                $campaignId = $marketing->createCampaign($campaignData);
                if ($campaignId) {
                    $actionMessage = 'Campaña creada exitosamente';
                    if ($campaignData['send_immediately']) {
                        $actionMessage .= ' y envío iniciado';
                    }
                } else {
                    $actionError = 'Error al crear la campaña';
                }
                break;
                
            case 'delete_campaign':
                $campaignId = $_POST['campaign_id'] ?? 0;
                if ($marketing->deleteCampaign($campaignId)) {
                    $actionMessage = 'Campaña eliminada exitosamente';
                } else {
                    $actionError = 'Error al eliminar la campaña';
                }
                break;
                
            case 'send_campaign':
                $campaignId = $_POST['campaign_id'] ?? 0;
                $results = $marketing->sendCampaign($campaignId);
                if ($results['sent'] > 0 || $results['failed'] > 0) {
                    $actionMessage = "Enviados: {$results['sent']}, Fallidos: {$results['failed']}";
                } else {
                    $actionError = 'Error al enviar la campaña';
                }
                break;
                
            case 'create_tag':
                $tagId = $marketing->createTag(
                    $_POST['tag_name'] ?? '',
                    $_POST['tag_color'] ?? '#667eea',
                    $_POST['tag_description'] ?? ''
                );
                if ($tagId) {
                    $actionMessage = 'Etiqueta creada exitosamente';
                } else {
                    $actionError = 'Error al crear la etiqueta';
                }
                break;
                
            case 'delete_tag':
                $tagId = $_POST['tag_id'] ?? 0;
                if ($marketing->deleteTag($tagId)) {
                    $actionMessage = 'Etiqueta eliminada exitosamente';
                } else {
                    $actionError = 'Error al eliminar la etiqueta';
                }
                break;
                
            case 'create_list':
                $listId = $marketing->createContactList(
                    $_POST['list_name'] ?? '',
                    $_POST['list_description'] ?? '',
                    json_decode($_POST['list_filters'] ?? '{}', true)
                );
                if ($listId) {
                    $actionMessage = 'Lista de contactos creada exitosamente';
                } else {
                    $actionError = 'Error al crear la lista';
                }
                break;
                
            case 'delete_list':
                $listId = $_POST['list_id'] ?? 0;
                if ($marketing->deleteContactList($listId)) {
                    $actionMessage = 'Lista eliminada exitosamente';
                } else {
                    $actionError = 'Error al eliminar la lista';
                }
                break;
                
            case 'create_template':
                $templateData = [
                    'name' => $_POST['template_name'] ?? '',
                    'category' => $_POST['template_category'] ?? 'otro',
                    'template_text' => $_POST['template_text'] ?? '',
                    'variables' => !empty($_POST['template_variables']) ? explode(',', $_POST['template_variables']) : [],
                    'example_data' => !empty($_POST['template_example']) ? json_decode($_POST['template_example'], true) : null,
                    'is_active' => isset($_POST['template_active']),
                    'requires_approval' => isset($_POST['template_requires_approval'])
                ];
                
                $templateId = $marketing->createCustomTemplate($templateData);
                if ($templateId) {
                    $actionMessage = 'Plantilla creada exitosamente';
                } else {
                    $actionError = 'Error al crear la plantilla';
                }
                break;
                
            case 'update_template':
                $templateId = $_POST['template_id'] ?? 0;
                $templateData = [
                    'name' => $_POST['template_name'] ?? '',
                    'category' => $_POST['template_category'] ?? 'otro',
                    'template_text' => $_POST['template_text'] ?? '',
                    'variables' => !empty($_POST['template_variables']) ? explode(',', $_POST['template_variables']) : [],
                    'is_active' => isset($_POST['template_active']),
                    'requires_approval' => isset($_POST['template_requires_approval'])
                ];
                
                if ($marketing->updateCustomTemplate($templateId, $templateData)) {
                    $actionMessage = 'Plantilla actualizada exitosamente';
                } else {
                    $actionError = 'Error al actualizar la plantilla';
                }
                break;
                
            case 'delete_template':
                $templateId = $_POST['template_id'] ?? 0;
                if ($marketing->deleteCustomTemplate($templateId)) {
                    $actionMessage = 'Plantilla eliminada exitosamente';
                } else {
                    $actionError = 'Error al eliminar la plantilla';
                }
                break;
        }
    }
}

// Get lead statistics
$leadStats = $marketing->getLeadStatistics();
$allEspecialidades = $marketing->getAllEspecialidades();


// Get dashboard data
$creditsInfo = $marketing->getCreditsInfo();
$metrics = $marketing->getDashboardMetrics();
$recentActivity = $marketing->getRecentActivity(5);
$chartData = $marketing->getUsageChartData(30);

// Get campaigns if in campaigns section
$campaigns = [];
$currentCampaign = null;
$campaignRecipients = [];

if (isset($_GET['section']) && $_GET['section'] === 'campaigns') {
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

// Get leads for segmentation
$leadsForSegmentation = [];
$allTags = [];
$allLists = [];
$allCustomTemplates = [];
if (isset($_GET['section']) && ($_GET['section'] === 'schedule' || $_GET['section'] === 'campaigns' || $_GET['section'] === 'segmentation' || $_GET['section'] === 'templates')) {
    $leadsForSegmentation = $marketing->getLeadsForSegmentation();
    $allTags = $marketing->getAllTags();
    $allLists = $marketing->getAllContactLists();
    $allCustomTemplates = $marketing->getAllCustomTemplates();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Marketing Dashboard | SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="ph-fill ph-whatsapp-logo text-3xl text-green-600"></i>
                <h1 class="text-2xl font-bold text-gray-800">WhatsApp Marketing</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                    <i class="ph ph-user"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                </span>
                <a href="admin.php" class="text-purple-600 hover:text-purple-700 font-medium">
                    <i class="ph ph-arrow-left"></i> Volver
                </a>
                <a href="?logout=1" class="text-red-600 hover:text-red-700 font-medium">
                    <i class="ph ph-sign-out"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="bg-white border-b shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex space-x-1 overflow-x-auto">
                <a href="admin_whatsapp_marketing.php" class="px-6 py-4 text-green-600 bg-green-50 border-b-2 border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-gauge"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_whatsapp_marketing.php?section=campaigns" class="px-6 py-4 text-gray-700 hover:text-green-600 hover:bg-green-50 border-b-2 border-transparent hover:border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-megaphone"></i>
                    <span>Campañas</span>
                </a>
                <a href="admin_whatsapp_marketing.php?section=schedule" class="px-6 py-4 text-gray-700 hover:text-green-600 hover:bg-green-50 border-b-2 border-transparent hover:border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-calendar"></i>
                    <span>Programar</span>
                </a>
                <a href="admin_whatsapp_marketing.php?section=segmentation" class="px-6 py-4 text-gray-700 hover:text-green-600 hover:bg-green-50 border-b-2 border-transparent hover:border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-users-three"></i>
                    <span>Segmentación</span>
                </a>
                <a href="admin_whatsapp_marketing.php?section=templates" class="px-6 py-4 text-gray-700 hover:text-green-600 hover:bg-green-50 border-b-2 border-transparent hover:border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-file-text"></i>
                    <span>Plantillas</span>
                </a>
                <a href="admin_whatsapp_marketing.php?section=reports" class="px-6 py-4 text-gray-700 hover:text-green-600 hover:bg-green-50 border-b-2 border-transparent hover:border-green-600 font-medium transition-colors flex items-center space-x-2 whitespace-nowrap">
                    <i class="ph-fill ph-chart-line"></i>
                    <span>Reportes</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <?php
        $section = $_GET['section'] ?? 'dashboard';
        
        if ($section === 'dashboard'):
        ?>
        <!-- Dashboard Section -->
        
        <!-- Credits Alert -->
        <?php if ($creditsInfo['percentage_used'] > 80): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="ph-fill ph-warning text-2xl text-yellow-600 mr-3"></i>
                <div>
                    <p class="font-semibold text-yellow-800">⚠️ Créditos Bajos</p>
                    <p class="text-sm text-yellow-700">
                        Has usado el <?php echo $creditsInfo['percentage_used']; ?>% de tus créditos disponibles. 
                        Considera recargar para evitar interrupciones.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Credits Overview -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 mb-8 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Créditos Disponibles</h2>
                    <p class="text-green-100">Control de créditos de WhatsApp Business API</p>
                </div>
                <i class="ph-fill ph-credit-card text-5xl opacity-80"></i>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
                    <p class="text-green-100 text-sm mb-1">Disponibles</p>
                    <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['available']); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
                    <p class="text-green-100 text-sm mb-1">Usados (Mes)</p>
                    <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['used_month']); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
                    <p class="text-green-100 text-sm mb-1">Restantes</p>
                    <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['remaining']); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
                    <p class="text-green-100 text-sm mb-1">Porcentaje Usado</p>
                    <p class="text-3xl font-bold"><?php echo $creditsInfo['percentage_used']; ?>%</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="mt-6">
                <div class="flex justify-between text-sm mb-2">
                    <span>Uso de Créditos</span>
                    <span><?php echo $creditsInfo['percentage_used']; ?>%</span>
                </div>
                <div class="w-full bg-white bg-opacity-30 rounded-full h-3">
                    <div class="bg-white rounded-full h-3 transition-all duration-500" 
                         style="width: <?php echo min(100, $creditsInfo['percentage_used']); ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Messages Sent Today -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 metric-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="ph-fill ph-paper-plane-tilt text-2xl text-blue-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">Hoy</span>
                </div>
                <h3 class="text-gray-600 text-sm font-medium mb-1">Mensajes Enviados</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['sent_today']); ?></p>
            </div>
            
            <!-- Delivery Rate -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 metric-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="ph-fill ph-check-circle text-2xl text-green-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">30 días</span>
                </div>
                <h3 class="text-gray-600 text-sm font-medium mb-1">Tasa de Entrega</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['delivery_rate'], 1); ?>%</p>
            </div>
            
            <!-- Read Rate -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 metric-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="ph-fill ph-eye text-2xl text-purple-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">30 días</span>
                </div>
                <h3 class="text-gray-600 text-sm font-medium mb-1">Tasa de Lectura</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['read_rate'], 1); ?>%</p>
            </div>
            
            <!-- Reply Rate -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 metric-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-amber-100 p-3 rounded-lg">
                        <i class="ph-fill ph-chat-circle text-2xl text-amber-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">30 días</span>
                </div>
                <h3 class="text-gray-600 text-sm font-medium mb-1">Tasa de Respuesta</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['reply_rate'], 1); ?>%</p>
            </div>
        </div>
        
        <!-- Charts and Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Usage Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="ph-fill ph-chart-line"></i> Uso de Créditos (Últimos 30 días)
                </h3>
                <canvas id="usageChart" height="200"></canvas>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="ph-fill ph-clock"></i> Actividad Reciente
                </h3>
                <div class="space-y-4">
                    <?php if (empty($recentActivity['campaigns'])): ?>
                        <p class="text-gray-500 text-center py-8">No hay actividad reciente</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recentActivity['campaigns'], 0, 5) as $campaign): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="bg-green-100 p-2 rounded">
                                    <i class="ph-fill ph-megaphone text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($campaign['name']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php 
                                        $statusLabels = [
                                            'draft' => 'Borrador',
                                            'scheduled' => 'Programada',
                                            'sending' => 'Enviando',
                                            'completed' => 'Completada',
                                            'paused' => 'Pausada',
                                            'cancelled' => 'Cancelada'
                                        ];
                                        echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">
                                <?php echo date('d/m/Y', strtotime($campaign['created_at'])); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Additional Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Mensajes Esta Semana</h3>
                    <i class="ph-fill ph-calendar-week text-2xl text-blue-600"></i>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['sent_week']); ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Mensajes Este Mes</h3>
                    <i class="ph-fill ph-calendar text-2xl text-purple-600"></i>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($metrics['sent_month']); ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Campañas Activas</h3>
                    <i class="ph-fill ph-play text-2xl text-green-600"></i>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $metrics['active_campaigns']; ?></p>
                <?php if ($metrics['pending_campaigns'] > 0): ?>
                    <p class="text-sm text-gray-500 mt-2"><?php echo $metrics['pending_campaigns']; ?> programadas</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lead Statistics -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="ph-fill ph-users"></i> Estadísticas de Leads
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($leadStats['total']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Total Leads</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($leadStats['with_whatsapp']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Con WhatsApp</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($leadStats['recent_30_days']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Últimos 30 días</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($leadStats['by_status']['nuevo'] ?? 0); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Nuevos</p>
                </div>
                <div class="text-center p-4 bg-amber-50 rounded-lg">
                    <p class="text-2xl font-bold text-amber-600"><?php echo number_format($leadStats['by_status']['convertido'] ?? 0); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Convertidos</p>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <a href="admin.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        <i class="ph-fill ph-arrow-right"></i> Ver todos los leads
                    </a>
                    <span class="text-xs text-gray-500">
                        <?php echo count($allEspecialidades); ?> especialidades únicas
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Lead Statistics -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="ph-fill ph-users"></i> Estadísticas de Leads
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($leadStats['total']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Total Leads</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($leadStats['with_whatsapp']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Con WhatsApp</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($leadStats['recent_30_days']); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Últimos 30 días</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($leadStats['by_status']['nuevo'] ?? 0); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Nuevos</p>
                </div>
                <div class="text-center p-4 bg-amber-50 rounded-lg">
                    <p class="text-2xl font-bold text-amber-600"><?php echo number_format($leadStats['by_status']['convertido'] ?? 0); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Convertidos</p>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <a href="admin.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        <i class="ph-fill ph-arrow-right"></i> Ver todos los leads
                    </a>
                    <span class="text-xs text-gray-500">
                        <?php echo count($allEspecialidades); ?> especialidades únicas
                    </span>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'campaigns'): ?>
        <!-- Campaigns Section -->
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="ph-fill ph-megaphone text-green-600"></i> Gestión de Campañas
            </h2>
            <a href="admin_whatsapp_marketing.php?section=schedule" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium flex items-center space-x-2">
                <i class="ph-fill ph-plus"></i>
                <span>Nueva Campaña</span>
            </a>
        </div>
        
        <?php if ($actionMessage): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-check-circle"></i> <?php echo htmlspecialchars($actionMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($actionError): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-x-circle"></i> <?php echo htmlspecialchars($actionError); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['view']) && $currentCampaign): ?>
        <!-- Campaign Details -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($currentCampaign['name']); ?></h3>
                    <p class="text-sm text-gray-500">
                        <?php 
                        $statusLabels = [
                            'draft' => 'Borrador',
                            'scheduled' => 'Programada',
                            'sending' => 'Enviando',
                            'completed' => 'Completada',
                            'paused' => 'Pausada',
                            'cancelled' => 'Cancelada'
                        ];
                        echo $statusLabels[$currentCampaign['status']] ?? $currentCampaign['status'];
                        ?>
                    </p>
                </div>
                <a href="admin_whatsapp_marketing.php?section=campaigns" class="text-gray-600 hover:text-gray-800">
                    <i class="ph-fill ph-x text-2xl"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Destinatarios</p>
                    <p class="text-2xl font-bold"><?php echo number_format($currentCampaign['total_recipients']); ?></p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-600 mb-1">Enviados</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($currentCampaign['total_sent']); ?></p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-600 mb-1">Entregados</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($currentCampaign['total_delivered']); ?></p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-600 mb-1">Leídos</p>
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($currentCampaign['total_read']); ?></p>
                </div>
            </div>
            
            <div class="mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Destinatarios</h4>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left">Nombre</th>
                                <th class="px-4 py-2 text-left">Teléfono</th>
                                <th class="px-4 py-2 text-left">Estado</th>
                                <th class="px-4 py-2 text-left">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaignRecipients as $recipient): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($recipient['nombre'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($recipient['phone_number']); ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs <?php 
                                        echo $recipient['status'] === 'sent' ? 'bg-blue-100 text-blue-800' : 
                                            ($recipient['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                            ($recipient['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                    ?>">
                                        <?php echo htmlspecialchars($recipient['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-500">
                                    <?php echo $recipient['sent_at'] ? date('d/m/Y H:i', strtotime($recipient['sent_at'])) : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Campaigns List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm font-medium text-gray-700">Filtrar por estado:</label>
                        <select onchange="window.location.href='?section=campaigns&status='+this.value" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Todos</option>
                            <option value="draft" <?php echo ($statusFilter ?? '') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="scheduled" <?php echo ($statusFilter ?? '') === 'scheduled' ? 'selected' : ''; ?>>Programada</option>
                            <option value="sending" <?php echo ($statusFilter ?? '') === 'sending' ? 'selected' : ''; ?>>Enviando</option>
                            <option value="completed" <?php echo ($statusFilter ?? '') === 'completed' ? 'selected' : ''; ?>>Completada</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (empty($campaigns)): ?>
                    <div class="text-center py-12">
                        <i class="ph-fill ph-megaphone text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 mb-4">No hay campañas creadas aún</p>
                        <a href="admin_whatsapp_marketing.php?section=schedule" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                            Crear Primera Campaña
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($campaigns as $campaign): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($campaign['name']); ?></h3>
                                        <span class="px-2 py-1 rounded text-xs <?php 
                                            echo $campaign['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                ($campaign['status'] === 'sending' ? 'bg-blue-100 text-blue-800' : 
                                                ($campaign['status'] === 'scheduled' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
                                        ?>">
                                            <?php 
                                            $statusLabels = [
                                                'draft' => 'Borrador',
                                                'scheduled' => 'Programada',
                                                'sending' => 'Enviando',
                                                'completed' => 'Completada',
                                                'paused' => 'Pausada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                                            ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-4 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($campaign['type']); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Destinatarios:</span> <?php echo number_format($campaign['total_recipients']); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Enviados:</span> <?php echo number_format($campaign['total_sent']); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Creada:</span> <?php echo date('d/m/Y', strtotime($campaign['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <a href="?section=campaigns&view=<?php echo $campaign['id']; ?>" class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                                        Ver
                                    </a>
                                    <?php if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('¿Enviar esta campaña ahora?');">
                                        <input type="hidden" name="action" value="send_campaign">
                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm">
                                            Enviar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta campaña?');">
                                        <input type="hidden" name="action" value="delete_campaign">
                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php elseif ($section === 'schedule'): ?>
        <!-- Schedule Section -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="ph-fill ph-calendar text-green-600"></i> Crear Nueva Campaña
            </h2>
        </div>
        
        <?php if ($actionMessage): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-check-circle"></i> <?php echo htmlspecialchars($actionMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($actionError): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-x-circle"></i> <?php echo htmlspecialchars($actionError); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <input type="hidden" name="action" value="create_campaign">
            
            <!-- Step 1: Basic Info -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">1. Información Básica</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Campaña *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Ej: Promoción Enero 2024">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Campaña *</label>
                        <select name="type" id="campaign_type" required 
                                onchange="updateCampaignTypeFields()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="cita">Cita</option>
                            <option value="cancelacion">Cancelación</option>
                            <option value="promocion">Promoción</option>
                            <option value="seguimiento">Seguimiento</option>
                            <option value="personalizado" selected>Personalizado</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Message -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">2. Mensaje</h3>
                
                <!-- Type-specific fields (hidden by default) -->
                <div id="type-specific-fields" class="mb-4 space-y-4 hidden">
                    <!-- Cita fields -->
                    <div id="fields-cita" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Cita</label>
                            <input type="date" name="filter_criteria[fecha_cita]" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Cita</label>
                            <input type="time" name="filter_criteria[hora_cita]" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor/Especialista</label>
                            <input type="text" name="filter_criteria[doctor]" 
                                   placeholder="Ej: Dr. Méndez"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de Cita</label>
                            <input type="text" name="filter_criteria[motivo]" 
                                   placeholder="Ej: Consulta general"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <!-- Cancelación fields -->
                    <div id="fields-cancelacion" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-red-50 rounded-lg">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Cita Cancelada</label>
                            <input type="date" name="filter_criteria[fecha_cita]" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Cita Cancelada</label>
                            <input type="time" name="filter_criteria[hora_cita]" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor/Especialista</label>
                            <input type="text" name="filter_criteria[doctor]" 
                                   placeholder="Ej: Dr. Méndez"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono de Contacto</label>
                            <input type="text" name="filter_criteria[telefono]" 
                                   placeholder="Ej: 555-1234"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <!-- Promoción fields -->
                    <div id="fields-promocion" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-green-50 rounded-lg">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descuento/Oferta</label>
                            <input type="text" name="filter_criteria[descuento]" 
                                   placeholder="Ej: 20% de descuento"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Validez de la Oferta</label>
                            <input type="date" name="filter_criteria[validez]" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción de la Oferta</label>
                            <input type="text" name="filter_criteria[oferta]" 
                                   placeholder="Ej: Promoción especial de enero"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usar Plantilla</label>
                    <select name="template_name" id="template_select" 
                            onchange="updateTemplatePreview()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- Selecciona una plantilla (opcional) --</option>
                        <optgroup label="Plantillas Predefinidas - Citas" id="templates-cita" class="hidden">
                            <option value="appointment_confirmation_1">Confirmación de Cita</option>
                            <option value="recordatorio_cita">Recordatorio de Cita</option>
                        </optgroup>
                        <optgroup label="Plantillas Predefinidas - Cancelaciones" id="templates-cancelacion" class="hidden">
                            <option value="appointment_cancellation_1">Cancelación de Cita</option>
                        </optgroup>
                        <optgroup label="Plantillas Predefinidas - Promociones" id="templates-promocion" class="hidden">
                            <option value="recordatorio">Promoción General</option>
                        </optgroup>
                        <optgroup label="Plantillas Predefinidas - Seguimiento" id="templates-seguimiento" class="hidden">
                            <option value="tes_unomedic">Bienvenida UNOmedic</option>
                        </optgroup>
                        <?php if (!empty($allCustomTemplates)): ?>
                        <?php
                        $templatesByCategory = [];
                        foreach ($allCustomTemplates as $template) {
                            $cat = $template['category'];
                            if (!isset($templatesByCategory[$cat])) {
                                $templatesByCategory[$cat] = [];
                            }
                            $templatesByCategory[$cat][] = $template;
                        }
                        foreach ($templatesByCategory as $category => $templates):
                            $categoryLabels = [
                                'cita' => 'Citas',
                                'cancelacion' => 'Cancelaciones',
                                'promocion' => 'Promociones',
                                'seguimiento' => 'Seguimiento',
                                'bienvenida' => 'Bienvenida',
                                'otro' => 'Otras'
                            ];
                        ?>
                        <optgroup label="Personalizadas - <?php echo $categoryLabels[$category] ?? ucfirst($category); ?>" id="templates-custom-<?php echo $category; ?>">
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo htmlspecialchars($template['name']); ?>">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Las plantillas funcionan fuera de la ventana de 24 horas. 
                        <a href="?section=templates" class="text-green-600 hover:underline">Gestionar plantillas</a>
                    </p>
                    
                    <!-- Template Preview -->
                    <div id="template-preview" class="mt-3 p-3 bg-gray-50 rounded-lg hidden">
                        <p class="text-xs font-medium text-gray-700 mb-1">Vista Previa:</p>
                        <p class="text-sm text-gray-600 whitespace-pre-line" id="template-preview-text"></p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje de Texto *</label>
                    <textarea name="message_text" id="message_text" rows="6" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Escribe tu mensaje aquí. Puedes usar variables según el tipo de campaña."><?php echo htmlspecialchars($_POST['message_text'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <span id="variables-hint">Variables disponibles: {nombre}, {especialidad}</span>
                    </p>
                </div>
            </div>
            
            <!-- Step 3: Segmentation -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">3. Segmentación Avanzada</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado del Lead</label>
                        <select name="filter_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="nuevo">Nuevo</option>
                            <option value="contactado">Contactado</option>
                            <option value="calificado">Calificado</option>
                            <option value="convertido">Convertido</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Especialidad</label>
                        <div class="flex space-x-2">
                            <select id="especialidad_select" 
                                    onchange="document.getElementById('especialidad_input').value = this.value;"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Seleccionar especialidad...</option>
                                <?php foreach ($allEspecialidades as $esp): ?>
                                <option value="<?php echo htmlspecialchars($esp); ?>">
                                    <?php echo htmlspecialchars($esp); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="filter_especialidad" id="especialidad_input"
                                   placeholder="O escribir nueva"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   oninput="document.getElementById('especialidad_select').value = '';">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Selecciona de la lista o escribe una nueva especialidad</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input type="date" name="filter_date_from" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input type="date" name="filter_date_to" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Origen (Source)</label>
                        <select name="filter_source" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Todos</option>
                            <option value="website">Website</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="referido">Referido</option>
                        </select>
                    </div>
                </div>
                
                <!-- Etiquetas -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Incluir Leads con Etiquetas</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($allTags as $tag): ?>
                        <label class="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="filter_tags[]" value="<?php echo $tag['id']; ?>"
                                   class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <span class="text-sm" style="color: <?php echo htmlspecialchars($tag['color']); ?>">
                                <i class="ph-fill ph-tag"></i> <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($allTags)): ?>
                        <p class="text-sm text-gray-500">No hay etiquetas creadas. <a href="?section=segmentation" class="text-green-600 hover:underline">Crear etiquetas</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excluir Leads con Etiquetas</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($allTags as $tag): ?>
                        <label class="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="filter_exclude_tags[]" value="<?php echo $tag['id']; ?>"
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            <span class="text-sm" style="color: <?php echo htmlspecialchars($tag['color']); ?>">
                                <i class="ph-fill ph-tag"></i> <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Listas de Contactos -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usar Lista de Contactos</label>
                    <select name="filter_list_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Ninguna (usar filtros manuales)</option>
                        <?php foreach ($allLists as $list): ?>
                        <option value="<?php echo $list['id']; ?>">
                            <?php echo htmlspecialchars($list['name']); ?> (<?php echo $list['member_count']; ?> contactos)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">O crea una nueva lista en la sección de Segmentación</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excluir Lista de Contactos</label>
                    <select name="filter_exclude_list_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Ninguna</option>
                        <?php foreach ($allLists as $list): ?>
                        <option value="<?php echo $list['id']; ?>">
                            <?php echo htmlspecialchars($list['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Vista Previa -->
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800 mb-1">
                                <i class="ph-fill ph-info"></i> Vista Previa de Destinatarios
                            </p>
                            <p class="text-xs text-blue-600">
                                Los destinatarios se calcularán automáticamente basándose en los filtros seleccionados.
                            </p>
                        </div>
                        <button type="button" onclick="previewRecipients()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            <i class="ph-fill ph-eye"></i> Ver Previa
                        </button>
                    </div>
                    <div id="preview-result" class="mt-3 hidden">
                        <p class="text-sm font-semibold text-blue-900">
                            Destinatarios estimados: <span id="preview-count" class="text-blue-600">-</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Scheduling -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">4. Programación</h3>
                
                <div class="space-y-4">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="send_immediately" value="1" 
                               class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                               onchange="document.getElementById('scheduled_at').disabled = this.checked; if(this.checked) document.getElementById('scheduled_at').value = '';">
                        <span class="text-gray-700 font-medium">Enviar inmediatamente</span>
                    </label>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">O programar para:</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="respect_business_hours" value="1" checked
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <span class="text-gray-700">Respetar horarios de atención (9 AM - 6 PM)</span>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="exclude_weekends" value="1"
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <span class="text-gray-700">Excluir fines de semana</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="admin_whatsapp_marketing.php?section=campaigns" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                    <i class="ph-fill ph-check"></i> Crear Campaña
                </button>
            </div>
        </form>
        
        <script>
        // Campaign type definitions
        const campaignTypes = {
            'cita': {
                variables: '{nombre}, {especialidad}, {fecha_cita}, {hora_cita}, {doctor}, {motivo}',
                templates: ['appointment_confirmation_1', 'recordatorio_cita'],
                templateNames: {
                    'appointment_confirmation_1': 'Confirmación de Cita',
                    'recordatorio_cita': 'Recordatorio de Cita'
                }
            },
            'cancelacion': {
                variables: '{nombre}, {especialidad}, {fecha_cita}, {hora_cita}, {doctor}, {telefono}',
                templates: ['appointment_cancellation_1'],
                templateNames: {
                    'appointment_cancellation_1': 'Cancelación de Cita'
                }
            },
            'promocion': {
                variables: '{nombre}, {especialidad}, {descuento}, {oferta}, {validez}',
                templates: ['recordatorio'],
                templateNames: {
                    'recordatorio': 'Promoción General'
                }
            },
            'seguimiento': {
                variables: '{nombre}, {especialidad}',
                templates: ['tes_unomedic'],
                templateNames: {
                    'tes_unomedic': 'Bienvenida/Seguimiento'
                }
            },
            'personalizado': {
                variables: '{nombre}, {especialidad}',
                templates: [],
                templateNames: {}
            }
        };
        
        const templatePreviews = {
            'appointment_confirmation_1': 'Buen día {nombre}, Gracias por reservar con {doctor}. Se confirma su cita para {motivo} el {fecha_cita} a las {hora_cita}. Gracias',
            'recordatorio_cita': 'Te recordamos que tu cita con {doctor} es:',
            'appointment_cancellation_1': 'Buen día {nombre}, Tu próxima cita con {doctor} el {fecha_cita} a las {hora_cita} ha sido cancelada. Háganos saber si tiene alguna pregunta o necesita reprogramarla al teléfono {telefono}. Gracias',
            'recordatorio': 'Recordatorio: nuestro técnico visitará su ubicación el {fecha_cita} a las {hora_cita} para su instalación de banda ancha. Por favor, esté disponible.',
            'tes_unomedic': 'Hola, Bienvenido a UNOmedic'
        };
        
        // Add custom templates to previews
        <?php if (!empty($allCustomTemplates)): ?>
        <?php foreach ($allCustomTemplates as $template): ?>
        templatePreviews['<?php echo htmlspecialchars($template['name']); ?>'] = <?php echo json_encode($template['template_text']); ?>;
        <?php endforeach; ?>
        <?php endif; ?>
        
        function updateCampaignTypeFields() {
            const type = document.getElementById('campaign_type').value;
            const typeFields = document.getElementById('type-specific-fields');
            const templateSelect = document.getElementById('template_select');
            const variablesHint = document.getElementById('variables-hint');
            const messageText = document.getElementById('message_text');
            
            // Hide all type-specific fields
            document.querySelectorAll('[id^="fields-"]').forEach(el => {
                el.classList.add('hidden');
            });
            
            // Hide/show type-specific fields
            if (type !== 'personalizado' && type !== 'seguimiento') {
                typeFields.classList.remove('hidden');
                const fieldsId = 'fields-' + type;
                const fields = document.getElementById(fieldsId);
                if (fields) {
                    fields.classList.remove('hidden');
                }
            } else {
                typeFields.classList.add('hidden');
            }
            
            // Update template options
            const typeConfig = campaignTypes[type] || campaignTypes['personalizado'];
            
            // Clear predefined template options but keep custom templates
            const customOptgroups = templateSelect.querySelectorAll('optgroup[label^="Personalizadas"]');
            templateSelect.innerHTML = '<option value="">-- Selecciona una plantilla (opcional) --</option>';
            
            // Re-add custom templates
            customOptgroups.forEach(optgroup => {
                templateSelect.appendChild(optgroup.cloneNode(true));
            });
            
            // Add predefined templates for this type
            if (typeConfig.templates.length > 0) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = 'Plantillas Predefinidas - ' + (type.charAt(0).toUpperCase() + type.slice(1));
                typeConfig.templates.forEach(templateName => {
                    const option = document.createElement('option');
                    option.value = templateName;
                    option.textContent = typeConfig.templateNames[templateName] || templateName;
                    optgroup.appendChild(option);
                });
                templateSelect.insertBefore(optgroup, templateSelect.firstChild.nextSibling);
            }
            
            // Update variables hint
            variablesHint.textContent = 'Variables disponibles: ' + typeConfig.variables;
            
            // Update message placeholder
            if (type === 'cita') {
                messageText.placeholder = 'Ej: Hola {nombre}, tu cita con {doctor} está programada para el {fecha_cita} a las {hora_cita}. Motivo: {motivo}';
            } else if (type === 'cancelacion') {
                messageText.placeholder = 'Ej: Hola {nombre}, lamentamos informarte que tu cita con {doctor} del {fecha_cita} a las {hora_cita} ha sido cancelada. Contacta al {telefono} para reprogramar.';
            } else if (type === 'promocion') {
                messageText.placeholder = 'Ej: ¡Hola {nombre}! Tenemos una oferta especial: {descuento}. {oferta}. Válido hasta {validez}';
            } else {
                messageText.placeholder = 'Escribe tu mensaje aquí. Puedes usar variables según el tipo de campaña.';
            }
        }
        
        function updateTemplatePreview() {
            const templateSelect = document.getElementById('template_select');
            const previewDiv = document.getElementById('template-preview');
            const previewText = document.getElementById('template-preview-text');
            
            const selectedTemplate = templateSelect.value;
            
            if (selectedTemplate && templatePreviews[selectedTemplate]) {
                previewDiv.classList.remove('hidden');
                previewText.textContent = templatePreviews[selectedTemplate];
            } else {
                previewDiv.classList.add('hidden');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCampaignTypeFields();
        });
        </script>
        
        <?php elseif ($section === 'segmentation'): ?>
        <!-- Segmentation Section -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="ph-fill ph-users-three text-green-600"></i> Segmentación Avanzada
            </h2>
        </div>
        
        <?php if ($actionMessage): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-check-circle"></i> <?php echo htmlspecialchars($actionMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($actionError): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-x-circle"></i> <?php echo htmlspecialchars($actionError); ?>
        </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="showTab('tags')" id="tab-tags-btn" 
                            class="px-6 py-4 text-sm font-medium text-green-600 border-b-2 border-green-600">
                        <i class="ph-fill ph-tag"></i> Etiquetas
                    </button>
                    <button onclick="showTab('lists')" id="tab-lists-btn" 
                            class="px-6 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-green-600">
                        <i class="ph-fill ph-list"></i> Listas de Contactos
                    </button>
                </nav>
            </div>
            
            <!-- Tags Tab -->
            <div id="tab-tags" class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Gestión de Etiquetas</h3>
                    <button onclick="document.getElementById('create-tag-modal').classList.remove('hidden')" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                        <i class="ph-fill ph-plus"></i> Nueva Etiqueta
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($allTags as $tag): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"></div>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($tag['name']); ?></span>
                            </div>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta etiqueta?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="ph-fill ph-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php if ($tag['description']): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($tag['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($allTags)): ?>
                    <div class="col-span-full text-center py-8 text-gray-500">
                        <i class="ph-fill ph-tag text-4xl mb-2"></i>
                        <p>No hay etiquetas creadas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lists Tab -->
            <div id="tab-lists" class="p-6 hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Listas de Contactos</h3>
                    <a href="?section=schedule" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                        <i class="ph-fill ph-plus"></i> Crear Lista desde Campaña
                    </a>
                </div>
                
                <div class="space-y-4">
                    <?php foreach ($allLists as $list): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($list['name']); ?></h4>
                                <?php if ($list['description']): ?>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($list['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                    <?php echo $list['member_count']; ?> contactos
                                </span>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta lista?');">
                                    <input type="hidden" name="action" value="delete_list">
                                    <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        <i class="ph-fill ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($allLists)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="ph-fill ph-list text-4xl mb-2"></i>
                        <p>No hay listas creadas</p>
                        <p class="text-sm mt-2">Crea listas desde el formulario de campañas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Create Tag Modal -->
        <div id="create-tag-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Nueva Etiqueta</h3>
                    <button onclick="document.getElementById('create-tag-modal').classList.add('hidden')" 
                            class="text-gray-500 hover:text-gray-700">
                        <i class="ph-fill ph-x text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_tag">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                        <input type="text" name="tag_name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="color" name="tag_color" value="#667eea" 
                               class="w-full h-12 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="tag_description" rows="3" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('create-tag-modal').classList.add('hidden')" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function showTab(tab) {
            // Hide all tabs
            document.getElementById('tab-tags').classList.add('hidden');
            document.getElementById('tab-lists').classList.add('hidden');
            document.getElementById('tab-tags-btn').classList.remove('text-green-600', 'border-green-600');
            document.getElementById('tab-tags-btn').classList.add('text-gray-500', 'border-transparent');
            document.getElementById('tab-lists-btn').classList.remove('text-green-600', 'border-green-600');
            document.getElementById('tab-lists-btn').classList.add('text-gray-500', 'border-transparent');
            
            // Show selected tab
            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab + '-btn').classList.remove('text-gray-500', 'border-transparent');
            document.getElementById('tab-' + tab + '-btn').classList.add('text-green-600', 'border-green-600');
        }
        
        function previewRecipients() {
            // Collect filter data
            const filters = {
                status: document.querySelector('[name="filter_status"]').value,
                especialidad: document.querySelector('[name="filter_especialidad"]').value,
                date_from: document.querySelector('[name="filter_date_from"]').value,
                date_to: document.querySelector('[name="filter_date_to"]').value,
                source: document.querySelector('[name="filter_source"]').value,
                tags: Array.from(document.querySelectorAll('[name="filter_tags[]"]:checked')).map(c => c.value),
                exclude_tags: Array.from(document.querySelectorAll('[name="filter_exclude_tags[]"]:checked')).map(c => c.value),
                contact_list_id: document.querySelector('[name="filter_list_id"]').value,
                exclude_list_id: document.querySelector('[name="filter_exclude_list_id"]').value
            };
            
            // Show loading
            document.getElementById('preview-result').classList.remove('hidden');
            document.getElementById('preview-count').textContent = 'Calculando...';
            
            // Make AJAX request (simplified - would need an endpoint)
            // For now, just show a placeholder
            setTimeout(() => {
                document.getElementById('preview-count').textContent = 'Usa los filtros y crea la campaña para ver el conteo exacto';
            }, 500);
        }
        </script>
        
        <?php elseif ($section === 'templates'): ?>
        <!-- Templates Section -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="ph-fill ph-file-text text-green-600"></i> Gestión de Plantillas
            </h2>
            <p class="text-gray-600 mt-2">Gestiona las plantillas autorizadas por META para WhatsApp Business</p>
        </div>
        
        <?php if ($actionMessage): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-check-circle"></i> <?php echo htmlspecialchars($actionMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($actionError): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="ph-fill ph-x-circle"></i> <?php echo htmlspecialchars($actionError); ?>
        </div>
        <?php endif; ?>
        
        <!-- Plantillas Predefinidas del Sistema -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="ph-fill ph-check-circle text-green-600"></i> Plantillas Predefinidas del Sistema
            </h3>
            <p class="text-sm text-gray-600 mb-4">Estas plantillas ya están configuradas y listas para usar:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $predefinedTemplates = [
                    ['name' => 'appointment_confirmation_1', 'category' => 'cita', 'display' => 'Confirmación de Cita'],
                    ['name' => 'appointment_cancellation_1', 'category' => 'cancelacion', 'display' => 'Cancelación de Cita'],
                    ['name' => 'recordatorio_cita', 'category' => 'cita', 'display' => 'Recordatorio de Cita'],
                    ['name' => 'recordatorio', 'category' => 'promocion', 'display' => 'Recordatorio/Promoción'],
                    ['name' => 'tes_unomedic', 'category' => 'seguimiento', 'display' => 'Bienvenida UNOmedic']
                ];
                
                foreach ($predefinedTemplates as $template):
                ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($template['display']); ?></h4>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Predefinida</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Nombre: <code class="bg-gray-100 px-1 rounded"><?php echo htmlspecialchars($template['name']); ?></code></p>
                    <p class="text-xs text-gray-600">Categoría: <?php echo htmlspecialchars($template['category']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Plantillas Personalizadas -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="ph-fill ph-file-plus text-green-600"></i> Plantillas Personalizadas
                </h3>
                <button onclick="document.getElementById('create-template-modal').classList.remove('hidden')" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    <i class="ph-fill ph-plus"></i> Nueva Plantilla
                </button>
            </div>
            
            <?php if (empty($allCustomTemplates)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="ph-fill ph-file-text text-4xl mb-2"></i>
                <p>No hay plantillas personalizadas creadas</p>
                <p class="text-sm mt-2">Crea una nueva plantilla autorizada por META</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($allCustomTemplates as $template): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($template['name']); ?></h4>
                        <div class="flex items-center space-x-2">
                            <?php if ($template['is_active']): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Activa</span>
                            <?php else: ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Inactiva</span>
                            <?php endif; ?>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta plantilla?');">
                                <input type="hidden" name="action" value="delete_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="ph-fill ph-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Categoría: <?php echo htmlspecialchars($template['category']); ?></p>
                    <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($template['template_text'], 0, 100)); ?>...</p>
                    <?php if ($template['variables'] && is_array($template['variables'])): ?>
                    <div class="mt-2">
                        <p class="text-xs text-gray-500">Variables: <?php echo implode(', ', $template['variables']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Create Template Modal -->
        <div id="create-template-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Nueva Plantilla Autorizada por META</h3>
                    <button onclick="document.getElementById('create-template-modal').classList.add('hidden')" 
                            class="text-gray-500 hover:text-gray-700">
                        <i class="ph-fill ph-x text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_template">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Plantilla *</label>
                            <input type="text" name="template_name" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                   placeholder="Ej: appointment_confirmation_1">
                            <p class="text-xs text-gray-500 mt-1">Debe coincidir exactamente con el nombre en META</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Categoría *</label>
                            <select name="template_category" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="cita">Cita</option>
                                <option value="cancelacion">Cancelación</option>
                                <option value="promocion">Promoción</option>
                                <option value="seguimiento">Seguimiento</option>
                                <option value="bienvenida">Bienvenida</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Texto de la Plantilla *</label>
                        <textarea name="template_text" rows="6" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                  placeholder="Escribe el texto de la plantilla. Usa {{1}}, {{2}}, etc. para parámetros"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Usa {{1}}, {{2}}, {{3}} para indicar parámetros dinámicos</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Variables (separadas por comas)</label>
                        <input type="text" name="template_variables" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                               placeholder="Ej: nombre_cliente, fecha, hora">
                        <p class="text-xs text-gray-500 mt-1">Lista de variables que acepta esta plantilla</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="template_active" value="1" checked
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <span class="text-gray-700">Plantilla Activa</span>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="template_requires_approval" value="1"
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <span class="text-gray-700">Requiere Aprobación de META</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('create-template-modal').classList.add('hidden')" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Crear Plantilla
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif ($section === 'reports'): ?>
        <!-- Reports Section (Placeholder for Phase 5) -->
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200 text-center">
            <i class="ph-fill ph-chart-line text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Reportes y Analytics</h2>
            <p class="text-gray-600 mb-6">Esta funcionalidad estará disponible en la Fase 5</p>
            <a href="admin_whatsapp_marketing.php" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                Volver al Dashboard
            </a>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script>
        // Usage Chart
        const ctx = document.getElementById('usageChart');
        if (ctx) {
            const chartData = <?php echo json_encode($chartData); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => {
                        const date = new Date(d.date);
                        return date.toLocaleDateString('es-MX', { month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Créditos Usados',
                        data: chartData.map(d => d.credits),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Mensajes Enviados',
                        data: chartData.map(d => d.messages),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

