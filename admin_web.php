<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Admin Web - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Blog.php';
require_once 'classes/SiteSettings.php';
require_once 'classes/Testimonials.php';

// Get active tab from URL - ensure it's valid
$validTabs = ['blog', 'banner', 'testimonials', 'contact'];
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], $validTabs) ? $_GET['tab'] : 'blog';

// Initialize classes
$blog = new Blog();
$settings = new SiteSettings();
$testimonials = new Testimonials();

// Handle form submissions - redirect to original pages for processing
// This maintains compatibility with existing form handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Blog actions
        if (in_array($_POST['action'], ['create', 'update', 'delete'])) {
            header('Location: admin_blog.php?' . http_build_query(array_merge($_GET, $_POST)));
            exit;
        }
    }
    
    if (isset($_POST['banner_action']) || isset($_POST['logo_action'])) {
        header('Location: admin_banner.php?' . http_build_query(array_merge($_GET, $_POST)));
        exit;
    }
    
    if (isset($_POST['testimonial_action'])) {
        header('Location: admin_testimonials.php?' . http_build_query(array_merge($_GET, $_POST)));
        exit;
    }
    
    // Handle contact info save
    if (isset($_POST['action']) && $_POST['action'] === 'save_contact_info') {
        $companyAddress = $_POST['company_address'] ?? '';
        $companyPhone = $_POST['company_phone'] ?? '';
        $companyPhoneWhatsapp = $_POST['company_phone_whatsapp'] ?? '';
        $companyPhoneLandline = $_POST['company_phone_landline'] ?? '';
        $companyEmail = $_POST['company_email'] ?? '';
        $companyChatbot = $_POST['company_chatbot'] ?? '';
        $socialFacebook = $_POST['social_facebook'] ?? '';
        $socialInstagram = $_POST['social_instagram'] ?? '';
        $socialLinkedIn = $_POST['social_linkedin'] ?? '';
        $socialYouTube = $_POST['social_youtube'] ?? '';
        
        $result = true;
        $errors = [];
        
        // Save each setting
        if (!$settings->setSetting('company_address', $companyAddress, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar la dirección';
        }
        if (!$settings->setSetting('company_phone', $companyPhone, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar el teléfono';
        }
        if (!$settings->setSetting('company_phone_whatsapp', $companyPhoneWhatsapp, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar el teléfono WhatsApp';
        }
        if (!$settings->setSetting('company_phone_landline', $companyPhoneLandline, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar el teléfono fijo';
        }
        if (!$settings->setSetting('company_email', $companyEmail, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar el email';
        }
        if (!$settings->setSetting('company_chatbot', $companyChatbot, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar el chatbot';
        }
        if (!$settings->setSetting('social_facebook', $socialFacebook, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar Facebook';
        }
        if (!$settings->setSetting('social_instagram', $socialInstagram, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar Instagram';
        }
        if (!$settings->setSetting('social_linkedin', $socialLinkedIn, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar LinkedIn';
        }
        if (!$settings->setSetting('social_youtube', $socialYouTube, 'text')) {
            $result = false;
            $errors[] = 'Error al guardar YouTube';
        }
        
        if ($result) {
            // Clear contact info cache after successful update
            if (function_exists('clear_contact_info_cache')) {
                clear_contact_info_cache();
            }
            
            header('Location: admin_web.php?tab=contact&message=' . urlencode('Información de contacto guardada exitosamente') . '&type=success');
        } else {
            header('Location: admin_web.php?tab=contact&message=' . urlencode('Error: ' . implode(', ', $errors)) . '&type=error');
        }
        exit;
    }
}

// Get data for each tab
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Blog data
$posts = [];
$editPost = null;
$allCategories = [];
if ($activeTab === 'blog') {
    if ($action === 'list') {
        $posts = $blog->getAllPosts(100);
    }
    if ($action === 'edit' && $editId) {
        $editPost = $blog->getPostById($editId);
    }
    $allCategories = $blog->getAllCategories();
}

// Banner/Logo data
$currentBanner = '';
$currentLogo = '';
if ($activeTab === 'banner') {
    $currentBanner = $settings->getMainBanner();
    $currentLogo = $settings->getMainLogo();
}

// Testimonials data
$allTestimonials = [];
$editTestimonial = null;
if ($activeTab === 'testimonials') {
    if ($action === 'list') {
        $allTestimonials = $testimonials->getAllTestimonials(100);
    }
    if ($action === 'edit' && $editId) {
        $editTestimonial = $testimonials->getTestimonialById($editId);
    }
}

// Contact info data
$contactInfo = [];
if ($activeTab === 'contact') {
    $contactInfo = [
        'company_address' => $settings->getSetting('company_address', defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : ''),
        'company_phone' => $settings->getSetting('company_phone', defined('CONTACT_PHONE') ? CONTACT_PHONE : ''),
        'company_phone_whatsapp' => $settings->getSetting('company_phone_whatsapp', ''),
        'company_phone_landline' => $settings->getSetting('company_phone_landline', ''),
        'company_email' => $settings->getSetting('company_email', defined('CONTACT_EMAIL_PUBLIC') ? CONTACT_EMAIL_PUBLIC : ''),
        'company_chatbot' => $settings->getSetting('company_chatbot', ''),
        'social_facebook' => $settings->getSetting('social_facebook', defined('SOCIAL_FACEBOOK') ? SOCIAL_FACEBOOK : ''),
        'social_instagram' => $settings->getSetting('social_instagram', defined('SOCIAL_INSTAGRAM') ? SOCIAL_INSTAGRAM : ''),
        'social_linkedin' => $settings->getSetting('social_linkedin', defined('SOCIAL_LINKEDIN') ? SOCIAL_LINKEDIN : ''),
        'social_youtube' => $settings->getSetting('social_youtube', defined('SOCIAL_YOUTUBE') ? SOCIAL_YOUTUBE : '')
    ];
}

// Get message from URL
$message = '';
$messageType = '';
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Include header with sidebar layout
include 'includes/admin_header.php';
?>

<?php include 'includes/layout_start.php'; ?>
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <?php if ($activeTab === 'testimonials'): ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">format_quote</span>
                        <?php elseif ($activeTab === 'banner'): ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">image</span>
                        <?php elseif ($activeTab === 'contact'): ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">contact_mail</span>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">article</span>
                        <?php endif; ?>
                        Admin Web
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">
                        <?php if ($activeTab === 'contact'): ?>
                            Gestiona la información de contacto de SOPHEA
                        <?php else: ?>
                            Gestiona el contenido web: Blog, Banner/Logo y Testimonios
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs Navigation -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
                <div class="border-b border-slate-200 dark:border-slate-800">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <a href="admin_web.php?tab=blog" 
                           class="<?php echo $activeTab === 'blog' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2">article</span>
                            Blog
                        </a>
                        <a href="admin_web.php?tab=banner" 
                           class="<?php echo $activeTab === 'banner' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2">image</span>
                            Banner y Logo
                        </a>
                        <a href="admin_web.php?tab=testimonials" 
                           class="<?php echo $activeTab === 'testimonials' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">format_quote</span>
                            Testimonios
                        </a>
                        <a href="admin_web.php?tab=contact" 
                           class="<?php echo $activeTab === 'contact' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'; ?> flex-1 whitespace-nowrap border-b-2 py-4 px-6 text-center text-sm font-medium transition-colors">
                            <span class="material-symbols-outlined align-middle mr-2" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">contact_mail</span>
                            Información de Contacto
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <?php if ($activeTab === 'blog'): ?>
                    <!-- Blog Tab Content -->
                    <?php 
                    // Include blog tab content
                    // For now, redirect to admin_blog.php but maintain tab context
                    // Or include the content directly
                    $blogAction = $action;
                    $blogEditId = $editId;
                    include 'includes/admin_web_blog_tab.php'; 
                    ?>
                    
                <?php elseif ($activeTab === 'banner'): ?>
                    <!-- Banner/Logo Tab Content -->
                    <?php include 'includes/admin_web_banner_tab.php'; ?>
                    
                <?php elseif ($activeTab === 'testimonials'): ?>
                    <!-- Testimonials Tab Content -->
                    <?php 
                    $testimonialAction = $action;
                    $testimonialEditId = $editId;
                    include 'includes/admin_web_testimonials_tab.php'; 
                    ?>
                    
                <?php elseif ($activeTab === 'contact'): ?>
                    <!-- Contact Info Tab Content -->
                    <?php include 'includes/admin_web_contact_tab.php'; ?>
                    
                <?php endif; ?>
            </div>
<?php include 'includes/layout_end.php'; ?>
