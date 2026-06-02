<?php
/**
 * SOPHEA - Admin Panel
 * 
 * Admin panel to view and manage leads
 * Uses secure authentication system
 */

// Load configurations
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

// Initialize authentication
$auth = new Auth();

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: admin.php');
    exit;
}

// Handle login
$login_error = '';
$login_locked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $result = $auth->login($_POST['username'], $_POST['password']);
    
    if ($result['success']) {
        // Redirect to prevent form resubmission
        // Check if there's a redirect parameter, otherwise stay on admin.php
        $redirect = $_GET['redirect'] ?? 'admin.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $login_error = $result['error'];
        $login_locked = $result['locked'] ?? false;
    }
}

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Administrativo - SOPHEA</title>
        <link href="assets/css/style.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
        <style>
            * { font-family: 'Inter', sans-serif; }
            body {
                margin: 0;
                padding: 0;
                background: #0f172a;
                overflow: hidden;
            }
            .auth-background {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: radial-gradient(circle at 20% 30%, rgba(124, 58, 237, 0.15) 0%, transparent 40%),
                            radial-gradient(circle at 80% 70%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                            linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
                z-index: -1;
            }
            .auth-mesh {
                position: absolute;
                inset: 0;
                background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2394a3b8' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                opacity: 0.5;
            }
            .login-card {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            .input-field {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: white;
                transition: all 0.3s ease;
            }
            .input-field:focus {
                background: rgba(255, 255, 255, 0.08);
                border-color: #8b5cf6;
                box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2);
            }
            .btn-primary {
                background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
                transition: all 0.3s ease;
            }
            .btn-primary:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px -5px rgba(124, 58, 237, 0.4);
            }
            .logo-animation {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body>
        <div class="auth-background">
            <div class="auth-mesh"></div>
        </div>

        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="login-card p-10 rounded-3xl w-full max-w-md">
                <div class="text-center mb-10">
                    <img src="assets/logo_SP1.png" alt="SOPHEA Logo" class="h-16 mx-auto mb-6 logo-animation" style="object-fit: contain;">
                    <h1 class="text-xl font-bold text-white mb-2 tracking-tight">Panel Administrativo</h1>
                    <p class="text-sm text-slate-400">Inicia sesión para gestionar tus leads y servicios</p>
                </div>
                
                <?php if ($login_error): ?>
                    <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3">
                        <i class="ph-bold ph-warning-circle text-lg"></i>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($login_locked): ?>
                    <div class="bg-amber-500/10 border border-amber-500/50 text-amber-400 px-4 py-3 rounded-xl mb-6 text-sm">
                        <strong>Bloqueo de seguridad:</strong> Demasiados intentos. Espera unos minutos.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2 ml-1">Usuario</label>
                        <div class="relative">
                            <i class="ph-bold ph-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" style="font-size: 20px;"></i>
                            <input type="text" name="username" required autocomplete="username"
                                class="input-field w-full py-3 rounded-xl focus:outline-none"
                                style="padding-left: 52px; padding-right: 16px;"
                                placeholder="Ingresa tu usuario"
                                <?php echo $login_locked ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-slate-300 text-sm font-semibold mb-2 ml-1">Contraseña</label>
                        <div class="relative">
                            <i class="ph-bold ph-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" style="font-size: 20px;"></i>
                            <input type="password" name="password" required autocomplete="current-password"
                                class="input-field w-full py-3 rounded-xl focus:outline-none"
                                style="padding-left: 52px; padding-right: 16px;"
                                placeholder="••••••••"
                                <?php echo $login_locked ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                    
                    <button type="submit" 
                        class="w-full btn-primary text-white py-4 rounded-xl font-bold text-lg shadow-lg disabled:opacity-50 disabled:cursor-not-allowed mt-2"
                        <?php echo $login_locked ? 'disabled' : ''; ?>>
                        Acceder al Sistema
                    </button>
                </form>
                
                <div class="mt-8 text-center">
                    <p class="text-xs text-slate-500 mb-1">Protección de Datos Nivel Bancario</p>
                    <div class="flex justify-center gap-2 opacity-30">
                        <i class="ph-fill ph-shield-check"></i>
                        <i class="ph-fill ph-lock-key"></i>
                        <i class="ph-fill ph-fingerprint"></i>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get current user info
$currentUser = $auth->getCurrentUser();

// Handle status update
if (isset($_POST['update_status'])) {
    $db = Database::getInstance();
    $db->updateLeadStatus($_POST['lead_id'], $_POST['status'], $_POST['notes'] ?? null);
    header('Location: admin.php');
    exit;
}

// Handle lead deletion
if (isset($_POST['delete_lead'])) {
    $leadId = intval($_POST['lead_id']);
    if ($leadId > 0) {
        try {
            $db = Database::getInstance();
            $sql = "DELETE FROM leads WHERE id = :id";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([':id' => $leadId]);
            header('Location: admin.php?deleted=1');
            exit;
        } catch (Exception $e) {
            error_log("Error deleting lead: " . $e->getMessage());
            header('Location: admin.php?delete_error=1');
            exit;
        }
    }
}

// Get database instance
try {
    $db = Database::getInstance();
    
    // Get statistics
    $stats = $db->getLeadStats();
    if (!$stats) {
        $stats = [
            'total_leads' => 0,
            'nuevos' => 0,
            'convertidos' => 0,
            'este_mes' => 0
        ];
    }
    
    // Get all leads
    $leads = $db->getAllLeads(100);
    if (!is_array($leads)) {
        $leads = [];
    }
    
    $db_error = false;
} catch (Exception $e) {
    error_log("Admin Panel Database Error: " . $e->getMessage());
    $db_error = true;
    $stats = [
        'total_leads' => 0,
        'nuevos' => 0,
        'convertidos' => 0,
        'este_mes' => 0
    ];
    $leads = [];
}

$pageTitle = 'Leads - Panel de Administración - SOPHEA';

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
        
        <div class="mx-auto max-w-[1400px] mt-16 md:mt-0">
        <?php
        // Handle test section
        $section = $_GET['section'] ?? 'leads';
        if ($section === 'tests'):
        ?>
        <!-- Tests Section -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">🧪 Herramientas de Prueba</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Herramientas de diagnóstico y pruebas del sistema</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Test Webhook -->
                <a href="tests/test_webhook.php" target="_blank" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md hover:border-blue-300 transition-all group">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg group-hover:bg-blue-200 transition">
                            <i class="ph-fill ph-webhooks-logo text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-blue-600">Test Webhook</h3>
                            <p class="text-xs text-gray-500">Verificar webhook</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Prueba la verificación del webhook de WhatsApp y simula solicitudes de Meta.</p>
                </a>

                <!-- Test Send WhatsApp -->
                <a href="tests/test_send_whatsapp.php" target="_blank" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md hover:border-green-300 transition-all group">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-green-100 p-3 rounded-lg group-hover:bg-green-200 transition">
                            <i class="ph-fill ph-paper-plane-tilt text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-green-600">Test Envío WhatsApp</h3>
                            <p class="text-xs text-gray-500">Enviar mensajes</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Prueba el envío de mensajes de WhatsApp y ve la respuesta detallada de la API.</p>
                </a>

                <!-- Test DB Connection -->
                <a href="tests/test_db_connection.php" target="_blank" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md hover:border-purple-300 transition-all group">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg group-hover:bg-purple-200 transition">
                            <i class="ph-fill ph-database text-2xl text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-purple-600">Test Conexión DB</h3>
                            <p class="text-xs text-gray-500">Verificar base de datos</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Verifica la conexión a la base de datos y muestra información de configuración.</p>
                </a>

                <!-- Test DB Config -->
                <a href="tests/test_db_config.php" target="_blank" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md hover:border-orange-300 transition-all group">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-orange-100 p-3 rounded-lg group-hover:bg-orange-200 transition">
                            <i class="ph-fill ph-gear text-2xl text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-orange-600">Test Config DB</h3>
                            <p class="text-xs text-gray-500">Configuración DB</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Muestra y verifica la configuración de la base de datos.</p>
                </a>

                <!-- Test Testimonials -->
                <a href="tests/test_testimonials.php" target="_blank" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md hover:border-amber-300 transition-all group">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-amber-100 p-3 rounded-lg group-hover:bg-amber-200 transition">
                            <i class="ph-fill ph-quote text-2xl text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-amber-600">Test Testimonios</h3>
                            <p class="text-xs text-gray-500">Diagnóstico testimonios</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Verifica el sistema de testimonios: tablas, conexión, permisos y funcionalidad.</p>
                </a>
            </div>

            <!-- Quick Info -->
            <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-2 flex items-center space-x-2">
                    <i class="ph-fill ph-info text-xl"></i>
                    <span>Información sobre los Tests</span>
                </h3>
                <ul class="text-sm text-blue-700 space-y-2 ml-8 list-disc">
                    <li><strong>Test Webhook:</strong> Verifica que el webhook esté configurado correctamente y pueda recibir solicitudes de Meta.</li>
                    <li><strong>Test Envío WhatsApp:</strong> Prueba el envío de mensajes y muestra información detallada de la respuesta de la API.</li>
                    <li><strong>Test Conexión DB:</strong> Verifica que la conexión a la base de datos funcione correctamente.</li>
                    <li><strong>Test Config DB:</strong> Muestra la configuración actual de la base de datos (sin exponer contraseñas).</li>
                    <li><strong>Test Testimonios:</strong> Diagnostica el sistema de testimonios: verifica tablas, conexión, permisos y funcionalidad.</li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <!-- New Leads Notification Banner -->
        <?php 
        $newLeadsCount = $stats['nuevos'] ?? 0;
        if ($newLeadsCount > 0): 
        ?>
        <div class="mb-6 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg border border-blue-400 dark:border-blue-500 p-4 animate-pulse" id="newLeadsBanner">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-white text-3xl">notifications_active</span>
                    <div>
                        <h3 class="text-white font-bold text-lg">
                            ¡Tienes <?php echo $newLeadsCount; ?> lead<?php echo $newLeadsCount > 1 ? 's' : ''; ?> nuevo<?php echo $newLeadsCount > 1 ? 's' : ''; ?>!
                        </h3>
                        <p class="text-blue-100 text-sm">Revisa y actualiza el estado de los leads pendientes</p>
                    </div>
                </div>
                <button onclick="document.getElementById('newLeadsBanner').style.display='none'" 
                        class="text-white hover:text-blue-100 transition">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Heading -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex flex-col gap-1">
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-3xl">groups</span>
                    Gestión de Leads
                </h2>
                <p class="text-slate-500 dark:text-slate-400 text-base">Administra y sigue el estado de tus leads</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Total Leads</p>
                    <span class="material-symbols-outlined text-purple-500 bg-purple-500/10 p-1.5 rounded-md">groups</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_leads'] ?? 0; ?></p>
            </div>

            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Nuevos</p>
                    <span class="material-symbols-outlined text-blue-500 bg-blue-500/10 p-1.5 rounded-md">notifications</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['nuevos'] ?? 0; ?></p>
            </div>

            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Convertidos</p>
                    <span class="material-symbols-outlined text-emerald-500 bg-emerald-500/10 p-1.5 rounded-md">check_circle</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['convertidos'] ?? 0; ?></p>
            </div>

            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Este Mes</p>
                    <span class="material-symbols-outlined text-orange-500 bg-orange-500/10 p-1.5 rounded-md">calendar_month</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['este_mes'] ?? 0; ?></p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 dark:border-green-400 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 dark:text-green-400 text-2xl mr-3">check_circle</span>
                    <div>
                        <p class="text-green-800 dark:text-green-300 font-semibold">Lead eliminado exitosamente</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['delete_error']) && $_GET['delete_error'] == '1'): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-400 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-red-500 dark:text-red-400 text-2xl mr-3">error</span>
                    <div>
                        <p class="text-red-800 dark:text-red-300 font-semibold">Error al eliminar el lead</p>
                        <p class="text-red-600 dark:text-red-400 text-sm">No se pudo eliminar el lead. Por favor, intenta de nuevo.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Database Error Message -->
        <?php if ($db_error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-400 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-red-500 dark:text-red-400 text-2xl mr-3">warning</span>
                    <div>
                        <p class="text-red-800 dark:text-red-300 font-semibold">Error de Conexión a la Base de Datos</p>
                        <p class="text-red-600 dark:text-red-400 text-sm">No se pudo conectar a la base de datos. Por favor, verifica la configuración en <code class="bg-red-100 dark:bg-red-900/30 px-1 rounded">config_db.php</code></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Leads Table -->
        <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-surface-dark/50 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Leads Recientes</h2>
                <div class="flex items-center gap-2">
                    <a href="export_leads.php?format=csv" 
                       class="text-primary hover:text-primary/80 font-medium text-sm flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-primary/10 transition"
                       title="Exportar a CSV">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        <span>Exportar CSV</span>
                    </a>
                    <a href="export_leads.php?format=excel" 
                       class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-medium text-sm flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition"
                       title="Exportar a Excel">
                        <span class="material-symbols-outlined text-[18px]">table_chart</span>
                        <span>Exportar Excel</span>
                    </a>
                    <button onclick="location.reload()" class="text-primary hover:text-primary/80 font-medium text-sm flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                        <span>Actualizar</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-surface-dark/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Nombre</th>
                            <th class="px-6 py-4">Especialidad</th>
                            <th class="px-6 py-4">WhatsApp</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <?php if (empty($leads) && !$db_error): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-2 block">inbox</span>
                                    <p class="font-medium">No hay leads registrados aún</p>
                                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Los nuevos leads aparecerán aquí automáticamente</p>
                                </td>
                            </tr>
                        <?php elseif ($db_error): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-red-500 dark:text-red-400">
                                    <span class="material-symbols-outlined text-5xl mb-2 block">warning</span>
                                    <p class="font-medium">Error al cargar los leads</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Verifica la conexión a la base de datos</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <tr class="group hover:bg-slate-50 dark:hover:bg-surface-dark/40 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">#<?php echo $lead['id']; ?></td>
                                    <td class="px-6 py-4 text-slate-900 dark:text-white"><?php echo htmlspecialchars($lead['nombre']); ?></td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($lead['especialidad']); ?></td>
                                    <td class="px-6 py-4">
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['whatsapp']); ?>" 
                                           target="_blank" 
                                           class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">chat</span>
                                            <span><?php echo htmlspecialchars($lead['whatsapp']); ?></span>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                        <?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            'nuevo' => ['bg' => 'bg-blue-100 dark:bg-blue-500/20', 'text' => 'text-blue-700 dark:text-blue-400'],
                                            'contactado' => ['bg' => 'bg-amber-100 dark:bg-amber-500/20', 'text' => 'text-amber-700 dark:text-amber-400'],
                                            'calificado' => ['bg' => 'bg-purple-100 dark:bg-purple-500/20', 'text' => 'text-purple-700 dark:text-purple-400'],
                                            'convertido' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/20', 'text' => 'text-emerald-700 dark:text-emerald-400'],
                                            'descartado' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-700 dark:text-slate-300']
                                        ];
                                        $statusInfo = $statusColors[$lead['status']] ?? ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-700 dark:text-slate-300'];
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo str_replace(['/20', 'bg-'], ['', 'bg-'], $statusInfo['bg']); ?>"></span>
                                            <?php echo ucfirst($lead['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button onclick="viewLead(<?php echo $lead['id']; ?>)" 
                                                    class="text-primary hover:text-primary/80 font-medium text-sm flex items-center gap-1"
                                                    title="Ver detalles">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </button>
                                            <button onclick="openWhatsAppModal(<?php echo $lead['id']; ?>, '<?php echo htmlspecialchars($lead['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($lead['whatsapp'], ENT_QUOTES); ?>')" 
                                                    class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium text-sm flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px]">send</span>
                                                <span>WhatsApp</span>
                                            </button>
                                            <button onclick="deleteLead(<?php echo $lead['id']; ?>, '<?php echo htmlspecialchars($lead['nombre'], ENT_QUOTES); ?>')" 
                                                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium text-sm flex items-center gap-1"
                                                    title="Eliminar lead">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </main>
</div>

    <!-- Lead Detail Modal (Simple) -->
    <div id="lead-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-card-dark rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Detalle del Lead</h3>
                <button onclick="closeModal()" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
            <div id="lead-details"></div>
        </div>
    </div>

    <!-- WhatsApp Send Message Modal -->
    <div id="whatsapp-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <i class="ph-fill ph-whatsapp-logo text-3xl text-green-600"></i>
                    <h3 class="text-2xl font-bold">Enviar Mensaje por WhatsApp</h3>
                </div>
                <button onclick="closeWhatsAppModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="ph ph-x text-2xl"></i>
                </button>
            </div>
            
            <div id="whatsapp-message-content" class="space-y-4">
                <!-- Lead info will be inserted here -->
            </div>
            
            <form id="whatsapp-form" class="mt-6">
                <input type="hidden" id="whatsapp-lead-id" name="lead_id">
                <input type="hidden" id="whatsapp-use-template" name="use_template" value="0">
                <input type="hidden" id="whatsapp-template-name" name="template_name" value="">
                <input type="hidden" id="whatsapp-template-params" name="template_params" value="[]">
                
                <!-- Toggle between free message and template -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de mensaje:</label>
                            <p class="text-xs text-gray-500">Mensaje libre solo funciona dentro de 24h después del último mensaje del usuario</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="whatsapp-template-toggle" class="sr-only peer" onchange="toggleWhatsAppMessageType()">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700" id="whatsapp-type-label">Mensaje Libre</span>
                        </label>
                    </div>
                </div>
                
                <!-- Template selection (hidden by default) -->
                <div id="whatsapp-template-section" class="hidden mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Plantilla:</label>
                    <select id="whatsapp-template-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">-- Selecciona una plantilla --</option>
                        <option value="recordatorio">Recordatorio</option>
                        <option value="tes_unomedic">tes_unomedic</option>
                        <option value="recordatorio_cita">recordatorio_cita</option>
                        <option value="appointment_confirmation_1">appointment_confirmation_1</option>
                        <option value="appointment_cancellation_1">appointment_cancellation_1</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Las plantillas funcionan fuera de la ventana de 24 horas</p>
                    
                    <!-- Template name override (for exact name matching) -->
                    <div class="mt-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Nombre exacto de la plantilla (editar si es diferente):
                            <span class="text-gray-400 font-normal">(Debe coincidir exactamente con el nombre en Meta Business Manager)</span>
                        </label>
                        <input type="text" id="whatsapp-template-name-exact" 
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Se llenará automáticamente al seleccionar una plantilla">
                        <p class="text-xs text-gray-500 mt-1">⚠️ El nombre debe coincidir EXACTAMENTE (mayúsculas, minúsculas, guiones) con el nombre en Meta Business Manager</p>
                    </div>
                    
                    <!-- Template preview -->
                    <div id="whatsapp-template-preview" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                        <p class="text-xs font-semibold text-blue-800 mb-1">Vista previa de la plantilla:</p>
                        <p class="text-sm text-blue-700 whitespace-pre-line" id="whatsapp-template-preview-text"></p>
                    </div>
                    
                    <!-- Dynamic template parameters -->
                    <div id="whatsapp-template-params-section" class="mt-3 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parámetros requeridos:</label>
                        <div id="whatsapp-template-params-fields" class="space-y-2">
                            <!-- Dynamic fields will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <!-- Free message textarea (shown by default) -->
                <div id="whatsapp-free-message-section" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje:</label>
                    <textarea id="whatsapp-message" name="message" rows="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Escribe tu mensaje aquí..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Máximo 4096 caracteres. Solo funciona dentro de 24h después del último mensaje del usuario.</p>
                    <p class="text-xs text-gray-500" id="whatsapp-char-count">0 / 4096 caracteres</p>
                </div>
                
                <div id="whatsapp-message-status" class="hidden mb-4 p-4 rounded-lg"></div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeWhatsAppModal()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" id="whatsapp-send-btn"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="ph-fill ph-paper-plane-tilt"></i>
                        <span id="whatsapp-btn-text">Enviar Mensaje</span>
                        <span id="whatsapp-btn-loading" class="hidden">
                            <i class="ph ph-circle-notch animate-spin"></i> Enviando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewLead(id) {
            // In a real implementation, fetch lead details via AJAX
            const lead = <?php echo json_encode($leads); ?>.find(l => l.id == id);
            
            if (lead) {
                const detailsHtml = `
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Nombre:</label>
                            <p class="text-lg font-semibold">${lead.nombre}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Especialidad/Giro:</label>
                            <p class="text-lg">${lead.especialidad}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">WhatsApp:</label>
                            <p class="text-lg">
                                <a href="https://wa.me/${lead.whatsapp.replace(/[^0-9]/g, '')}" 
                                   target="_blank" 
                                   class="text-green-600 hover:underline">
                                    ${lead.whatsapp}
                                </a>
                            </p>
                        </div>
                        <div class="pt-4 border-t">
                            <button onclick="openWhatsAppModal(${lead.id}, '${lead.nombre.replace(/'/g, "\\'")}', '${lead.whatsapp.replace(/'/g, "\\'")}')" 
                                    class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center justify-center space-x-2">
                                <i class="ph-fill ph-whatsapp-logo text-xl"></i>
                                <span>Enviar Mensaje por WhatsApp</span>
                            </button>
                        </div>
                        ${lead.mensaje ? `
                        <div>
                            <label class="text-sm font-medium text-gray-600">Mensaje:</label>
                            <p class="text-gray-700 bg-gray-50 p-4 rounded-lg">${lead.mensaje}</p>
                        </div>
                        ` : ''}
                        <div>
                            <label class="text-sm font-medium text-gray-600">Fecha:</label>
                            <p>${new Date(lead.created_at).toLocaleString('es-MX')}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">IP:</label>
                            <p class="text-sm text-gray-600">${lead.ip_address || 'N/A'}</p>
                        </div>
                        
                        <form method="POST" class="mt-6 space-y-4 border-t pt-4">
                            <input type="hidden" name="lead_id" value="${lead.id}">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cambiar Estado:</label>
                                <select name="status" class="w-full px-4 py-2 border rounded-lg">
                                    <option value="nuevo" ${lead.status === 'nuevo' ? 'selected' : ''}>Nuevo</option>
                                    <option value="contactado" ${lead.status === 'contactado' ? 'selected' : ''}>Contactado</option>
                                    <option value="calificado" ${lead.status === 'calificado' ? 'selected' : ''}>Calificado</option>
                                    <option value="convertido" ${lead.status === 'convertido' ? 'selected' : ''}>Convertido</option>
                                    <option value="descartado" ${lead.status === 'descartado' ? 'selected' : ''}>Descartado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas:</label>
                                <textarea name="notes" rows="3" class="w-full px-4 py-2 border rounded-lg">${lead.notes || ''}</textarea>
                            </div>
                            <button type="submit" name="update_status" 
                                    class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
                                Actualizar
                            </button>
                        </form>
                    </div>
                `;
                
                document.getElementById('lead-details').innerHTML = detailsHtml;
                document.getElementById('lead-modal').classList.remove('hidden');
            }
        }

        function closeModal() {
            document.getElementById('lead-modal').classList.add('hidden');
        }
        
        // Delete lead function
        function deleteLead(leadId, leadName) {
            if (!confirm(`¿Estás seguro de que deseas eliminar el lead "${leadName}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admin.php';
            
            const leadIdInput = document.createElement('input');
            leadIdInput.type = 'hidden';
            leadIdInput.name = 'lead_id';
            leadIdInput.value = leadId;
            form.appendChild(leadIdInput);
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_lead';
            deleteInput.value = '1';
            form.appendChild(deleteInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // WhatsApp Modal Functions
        function openWhatsAppModal(leadId, leadName, leadWhatsApp) {
            document.getElementById('whatsapp-lead-id').value = leadId;
            document.getElementById('whatsapp-message').value = '';
            document.getElementById('whatsapp-message-status').classList.add('hidden');
            document.getElementById('whatsapp-char-count').textContent = '0 / 4096 caracteres';
            
            // Reset template selection
            document.getElementById('whatsapp-template-toggle').checked = false;
            document.getElementById('whatsapp-template-select').value = '';
            document.getElementById('whatsapp-template-preview').classList.add('hidden');
            document.getElementById('whatsapp-template-params-section').classList.add('hidden');
            toggleWhatsAppMessageType();
            
            // Show lead info
            const contentHtml = `
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Enviar a:</p>
                    <p class="font-semibold text-lg">${leadName}</p>
                    <p class="text-sm text-gray-600">${leadWhatsApp}</p>
                </div>
            `;
            document.getElementById('whatsapp-message-content').innerHTML = contentHtml;
            
            document.getElementById('whatsapp-modal').classList.remove('hidden');
            document.getElementById('whatsapp-message').focus();
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.add('hidden');
            document.getElementById('whatsapp-form').reset();
            // Reset to free message mode
            document.getElementById('whatsapp-template-toggle').checked = false;
            toggleWhatsAppMessageType();
        }
        
        function toggleWhatsAppMessageType() {
            const useTemplate = document.getElementById('whatsapp-template-toggle').checked;
            const templateSection = document.getElementById('whatsapp-template-section');
            const freeMessageSection = document.getElementById('whatsapp-free-message-section');
            const typeLabel = document.getElementById('whatsapp-type-label');
            const useTemplateInput = document.getElementById('whatsapp-use-template');
            
            if (useTemplate) {
                templateSection.classList.remove('hidden');
                freeMessageSection.classList.add('hidden');
                typeLabel.textContent = 'Usar Plantilla';
                useTemplateInput.value = '1';
                document.getElementById('whatsapp-message').removeAttribute('required');
            } else {
                templateSection.classList.add('hidden');
                freeMessageSection.classList.remove('hidden');
                typeLabel.textContent = 'Mensaje Libre';
                useTemplateInput.value = '0';
                document.getElementById('whatsapp-message').setAttribute('required', 'required');
            }
        }
        
        // Template definitions with their structure and parameters
        const whatsappTemplates = {
            'recordatorio': {
                name: 'Recordatorio',
                preview: 'Recordatorio: nuestro técnico visitará su ubicación el {{1}} a las {{2}} para su instalación de banda ancha. Por favor, esté disponible.',
                params: [
                    { label: 'Fecha', placeholder: 'Ej: 15 de enero', key: 'fecha' },
                    { label: 'Hora', placeholder: 'Ej: 10:00 AM', key: 'hora' }
                ]
            },
            'tes_unomedic': {
                name: 'tes_unomedic',
                preview: 'Hola, Bienvenido a UNOmedic',
                params: []
            },
            'recordatorio_cita': {
                name: 'recordatorio_cita',
                preview: 'Te recordamos que tu cita con el Dr. Méndez es:',
                params: []
            },
            'appointment_confirmation_1': {
                name: 'appointment_confirmation_1',
                preview: 'Buen día {{1}},\n\nGracias por reservar con {{2}}.\n\nSe confirma su cita para {{3}} el {{4}} a las {{5}}.\n\nGracias',
                params: [
                    { label: 'Nombre del cliente', placeholder: 'Ej: Juan Pérez', key: 'nombre_cliente' },
                    { label: 'Nombre del doctor', placeholder: 'Ej: Dr. Méndez', key: 'nombre_dr' },
                    { label: 'Motivo de cita', placeholder: 'Ej: Consulta general', key: 'motivo_cita' },
                    { label: 'Fecha', placeholder: 'Ej: 20 de enero', key: 'fecha' },
                    { label: 'Hora', placeholder: 'Ej: 2:00 PM', key: 'hora' }
                ]
            },
            'appointment_cancellation_1': {
                name: 'appointment_cancellation_1',
                preview: 'Buen día {{1}},\n\nTu próxima cita con {{2}} el {{3}} a las {{4}} ha sido cancelada.\n\nHáganos saber si tiene alguna pregunta o necesita reprogramarla al teléfono {{5}}.\n\nGracias',
                params: [
                    { label: 'Nombre del cliente', placeholder: 'Ej: Juan Pérez', key: 'nombre_cliente' },
                    { label: 'Nombre del doctor', placeholder: 'Ej: Dr. Méndez', key: 'nombre_dr' },
                    { label: 'Fecha', placeholder: 'Ej: 20 de enero', key: 'fecha' },
                    { label: 'Hora', placeholder: 'Ej: 2:00 PM', key: 'hora' },
                    { label: 'Número de teléfono', placeholder: 'Ej: 555-1234', key: 'num_telefono' }
                ]
            }
        };
        
        // Handle template selection change
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('whatsapp-template-select');
            if (templateSelect) {
                templateSelect.addEventListener('change', function() {
                    const templateName = this.value;
                    const templateNameInput = document.getElementById('whatsapp-template-name');
                    const paramsSection = document.getElementById('whatsapp-template-params-section');
                    const paramsFields = document.getElementById('whatsapp-template-params-fields');
                    const previewDiv = document.getElementById('whatsapp-template-preview');
                    const previewText = document.getElementById('whatsapp-template-preview-text');
                    
                    templateNameInput.value = templateName;
                    
                    // Update exact template name field
                    const exactNameInput = document.getElementById('whatsapp-template-name-exact');
                    if (exactNameInput) {
                        exactNameInput.value = templateName; // Default to selected template name, user can edit
                    }
                    
                    // Show params section if template is selected
                    if (templateName && whatsappTemplates[templateName]) {
                        const template = whatsappTemplates[templateName];
                        
                        // Show preview
                        if (template.preview) {
                            previewText.textContent = template.preview;
                            previewDiv.classList.remove('hidden');
                        } else {
                            previewDiv.classList.add('hidden');
                        }
                        
                        // Generate parameter fields
                        paramsFields.innerHTML = '';
                        if (template.params && template.params.length > 0) {
                            template.params.forEach((param, index) => {
                                const fieldDiv = document.createElement('div');
                                fieldDiv.className = 'mb-2';
                                fieldDiv.innerHTML = `
                                    <label class="block text-xs font-medium text-gray-600 mb-1">${param.label} ({{${index + 1}}}):</label>
                                    <input type="text" 
                                           class="template-param-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                           data-param-index="${index}"
                                           placeholder="${param.placeholder || ''}"
                                           required>
                                `;
                                paramsFields.appendChild(fieldDiv);
                            });
                            paramsSection.classList.remove('hidden');
                        } else {
                            paramsSection.classList.add('hidden');
                        }
                    } else {
                        previewDiv.classList.add('hidden');
                        paramsSection.classList.add('hidden');
                    }
                });
            }
        });

        // Character counter and form submission
        document.addEventListener('DOMContentLoaded', function() {
            const messageTextarea = document.getElementById('whatsapp-message');
            const charCount = document.getElementById('whatsapp-char-count');
            
            if (messageTextarea && charCount) {
                messageTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    charCount.textContent = `${length} / 4096 caracteres`;
                    
                    if (length > 4096) {
                        charCount.classList.add('text-red-600');
                        charCount.classList.remove('text-gray-500');
                    } else {
                        charCount.classList.remove('text-red-600');
                        charCount.classList.add('text-gray-500');
                    }
                });
            }

            // WhatsApp form submission
            const whatsappForm = document.getElementById('whatsapp-form');
            if (whatsappForm) {
                whatsappForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const sendBtn = document.getElementById('whatsapp-send-btn');
                    const btnText = document.getElementById('whatsapp-btn-text');
                    const btnLoading = document.getElementById('whatsapp-btn-loading');
                    const statusDiv = document.getElementById('whatsapp-message-status');
                    const messageTextarea = document.getElementById('whatsapp-message');
                    
                    // Disable button
                    sendBtn.disabled = true;
                    btnText.classList.add('hidden');
                    btnLoading.classList.remove('hidden');
                    statusDiv.classList.add('hidden');
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    // Add template parameters if using template
                    const useTemplate = document.getElementById('whatsapp-template-toggle').checked;
                    if (useTemplate) {
                        // Get exact template name (user may have edited it)
                        const exactTemplateName = document.getElementById('whatsapp-template-name-exact').value.trim();
                        if (!exactTemplateName) {
                            statusDiv.className = 'mb-4 p-4 rounded-lg bg-yellow-100 border border-yellow-500 text-yellow-800';
                            statusDiv.innerHTML = '<i class="ph-fill ph-warning-circle mr-2"></i>Por favor ingresa el nombre exacto de la plantilla.';
                            statusDiv.classList.remove('hidden');
                            sendBtn.disabled = false;
                            btnText.classList.remove('hidden');
                            btnLoading.classList.add('hidden');
                            return;
                        }
                        
                        // Update template name with exact name
                        formData.set('template_name', exactTemplateName);
                        
                        // Get parameters from dynamic fields
                        const paramInputs = document.querySelectorAll('.template-param-input');
                        const params = [];
                        paramInputs.forEach(input => {
                            const value = input.value.trim();
                            if (value) {
                                params.push(value);
                            }
                        });
                        
                        // Validate that all required parameters are filled
                        const templateName = document.getElementById('whatsapp-template-select').value;
                        if (templateName && whatsappTemplates[templateName]) {
                            const template = whatsappTemplates[templateName];
                            if (template.params && template.params.length > 0) {
                                if (params.length !== template.params.length) {
                                    statusDiv.className = 'mb-4 p-4 rounded-lg bg-yellow-100 border border-yellow-500 text-yellow-800';
                                    statusDiv.innerHTML = '<i class="ph-fill ph-warning-circle mr-2"></i>Por favor completa todos los parámetros requeridos.';
                                    statusDiv.classList.remove('hidden');
                                    sendBtn.disabled = false;
                                    btnText.classList.remove('hidden');
                                    btnLoading.classList.add('hidden');
                                    return;
                                }
                            }
                        }
                        
                        formData.set('template_params', JSON.stringify(params));
                    }
                    
                    try {
                        const response = await fetch('send_whatsapp.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            statusDiv.className = 'mb-4 p-4 rounded-lg bg-green-100 border border-green-500 text-green-800';
                            statusDiv.innerHTML = '<i class="ph-fill ph-check-circle mr-2"></i>' + result.message;
                            statusDiv.classList.remove('hidden');
                            
                            // Clear message
                            messageTextarea.value = '';
                            if (charCount) {
                                charCount.textContent = '0 / 4096 caracteres';
                            }
                            
                            // Close modal after 2 seconds and reload
                            setTimeout(() => {
                                closeWhatsAppModal();
                                location.reload();
                            }, 2000);
                        } else {
                            statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-100 border border-red-500 text-red-800';
                            statusDiv.innerHTML = '<i class="ph-fill ph-warning-circle mr-2"></i>' + result.message;
                            statusDiv.classList.remove('hidden');
                        }
                    } catch (error) {
                        statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-100 border border-red-500 text-red-800';
                        statusDiv.innerHTML = '<i class="ph-fill ph-warning-circle mr-2"></i>Error al enviar mensaje. Por favor, intenta de nuevo.';
                        statusDiv.classList.remove('hidden');
                        console.error('WhatsApp send error:', error);
                    } finally {
                        // Re-enable button
                        sendBtn.disabled = false;
                        btnText.classList.remove('hidden');
                        btnLoading.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Auto-update new leads count badge -->
    <script>
    // Function to update the leads badge
    function updateLeadsBadge() {
        fetch('api_get_new_leads_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('leadsBadge');
                    const count = data.count || 0;
                    
                    if (count > 0) {
                        if (badge) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.style.display = 'flex';
                        } else {
                            // Create badge if it doesn't exist
                            const leadsLink = document.querySelector('a[href="admin.php"]');
                            if (leadsLink && !leadsLink.querySelector('#leadsBadge')) {
                                const newBadge = document.createElement('span');
                                newBadge.id = 'leadsBadge';
                                newBadge.className = 'bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5';
                                newBadge.textContent = count > 99 ? '99+' : count;
                                leadsLink.appendChild(newBadge);
                            }
                        }
                    } else {
                        if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                    
                    // Update banner if on leads page
                    const banner = document.getElementById('newLeadsBanner');
                    if (banner) {
                        if (count > 0) {
                            const bannerText = banner.querySelector('h3');
                            if (bannerText) {
                                bannerText.innerHTML = `¡Tienes ${count} lead${count > 1 ? 's' : ''} nuevo${count > 1 ? 's' : ''}!`;
                            }
                            banner.style.display = 'block';
                        } else {
                            banner.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error updating leads badge:', error);
            });
    }
    
    // Update badge on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateLeadsBadge();
        
        // Update badge every 5 minutes
        setInterval(updateLeadsBadge, 300000); // 300000 ms = 5 minutes
    });
    </script>

<?php include 'includes/admin_footer.php'; ?>
