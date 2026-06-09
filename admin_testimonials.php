<?php
// Use authentication helper
require_once 'admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Admin Testimonios - SOPHEA';
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

// Load required classes
require_once 'classes/Testimonials.php';

// Upload configuration
define('TESTIMONIAL_UPLOAD_DIR', __DIR__ . '/uploads/testimonials/');
define('TESTIMONIAL_UPLOAD_URL', 'uploads/testimonials/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif']);

if (!file_exists(TESTIMONIAL_UPLOAD_DIR)) {
    mkdir(TESTIMONIAL_UPLOAD_DIR, 0755, true);
}

/**
 * Sanitize and validate file extension based on MIME type
 */
function sanitizeFileExtension($mimeType, $originalExtension) {
    // Map MIME types to allowed extensions
    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    
    // Get valid extension from MIME type
    $validExtension = $mimeToExtension[$mimeType] ?? null;
    
    if (!$validExtension) {
        return null;
    }
    
    // Sanitize original extension (lowercase, alphanumeric only)
    $sanitizedExt = strtolower(preg_replace('/[^a-z0-9]/', '', $originalExtension));
    
    // If original extension matches valid extension, use it; otherwise use MIME-based extension
    // This prevents extension spoofing
    return ($sanitizedExt === $validExtension) ? $validExtension : $validExtension;
}

/**
 * Sanitize filename - remove special characters and ensure safe naming
 */
function sanitizeFilename($filename, $prefix = '') {
    // Remove directory separators and special characters
    $filename = basename($filename);
    
    // Remove extension for sanitization
    $pathInfo = pathinfo($filename);
    $nameWithoutExt = $pathInfo['filename'] ?? '';
    
    // Sanitize name: only alphanumeric, hyphens, and underscores
    $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $nameWithoutExt);
    
    // Generate unique filename with prefix, timestamp, and uniqid
    // We don't use the original filename for security
    return $prefix . time() . '_' . uniqid();
}

function uploadTestimonialImage($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tipo de archivo usando MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Sanitizar extensión basada en MIME type (previene extension spoofing)
    $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $validExtension = sanitizeFileExtension($mimeType, $originalExtension);
    
    if (!$validExtension) {
        return ['error' => 'Extensión de archivo no válida o no coincide con el tipo de archivo'];
    }
    
    // Generar nombre único y seguro
    $sanitizedFilename = sanitizeFilename($file['name'], $prefix);
    $filename = $sanitizedFilename . '.' . $validExtension;
    $filepath = TESTIMONIAL_UPLOAD_DIR . $filename;
    
    // Verificar que no exista (muy improbable pero por seguridad)
    if (file_exists($filepath)) {
        $filename = $sanitizedFilename . '_' . rand(1000, 9999) . '.' . $validExtension;
        $filepath = TESTIMONIAL_UPLOAD_DIR . $filename;
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return TESTIMONIAL_UPLOAD_URL . $filename;
    }
    
    return ['error' => 'Error al subir el archivo'];
}

$testimonials = new Testimonials();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                // Process featured image
                $featuredImage = $_POST['featured_image'] ?? '';
                if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadTestimonialImage($_FILES['featured_image_file'], 'testimonial_');
                    if (is_string($uploadResult)) {
                        $featuredImage = $uploadResult;
                    }
                }
                
                // Process avatar
                $avatar = $_POST['client_avatar'] ?? '';
                if (isset($_FILES['client_avatar_file']) && $_FILES['client_avatar_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadTestimonialImage($_FILES['client_avatar_file'], 'avatar_');
                    if (is_string($uploadResult)) {
                        $avatar = $uploadResult;
                    }
                }
                
                // Process gallery images
                $galleryImages = [];
                if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
                    foreach ($_FILES['gallery_images']['name'] as $index => $name) {
                        if ($_FILES['gallery_images']['error'][$index] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['gallery_images']['name'][$index],
                                'type' => $_FILES['gallery_images']['type'][$index],
                                'tmp_name' => $_FILES['gallery_images']['tmp_name'][$index],
                                'error' => $_FILES['gallery_images']['error'][$index],
                                'size' => $_FILES['gallery_images']['size'][$index]
                            ];
                            $uploadResult = uploadTestimonialImage($file, 'gallery_');
                            if (is_string($uploadResult)) {
                                $galleryImages[] = [
                                    'path' => $uploadResult,
                                    'alt' => $_POST['gallery_alt'][$index] ?? '',
                                    'order' => $index
                                ];
                            }
                        }
                    }
                }
                
                // Add existing gallery images from form
                if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
                    $existingImagesAlt = isset($_POST['existing_images_alt']) && is_array($_POST['existing_images_alt']) ? $_POST['existing_images_alt'] : [];
                    foreach ($_POST['existing_images'] as $index => $imagePath) {
                        if (!empty($imagePath)) {
                            $galleryImages[] = [
                                'path' => $imagePath,
                                'alt' => $existingImagesAlt[$index] ?? '',
                                'order' => count($galleryImages) + $index
                            ];
                        }
                    }
                }
                
                // Validate required fields
                if (empty($_POST['client_name']) || empty($_POST['testimonial_text'])) {
                    $message = 'El nombre del cliente y el testimonio son obligatorios';
                    $messageType = 'error';
                } else {
                    // Process published_at date
                    $publishedAt = '';
                    if (!empty($_POST['published_at'])) {
                        // Convert datetime-local format to MySQL format
                        $publishedAt = date('Y-m-d H:i:s', strtotime($_POST['published_at']));
                    }
                    
                    $data = [
                        'client_name' => trim($_POST['client_name']),
                        'client_title' => trim($_POST['client_title'] ?? ''),
                        'client_company' => trim($_POST['client_company'] ?? ''),
                        'client_location' => trim($_POST['client_location'] ?? ''),
                        'client_avatar' => $avatar,
                        'testimonial_text' => trim($_POST['testimonial_text']),
                        'full_story' => trim($_POST['full_story'] ?? ''),
                        'slug' => trim($_POST['slug'] ?? ''),
                        'featured_image' => $featuredImage,
                        'status' => $_POST['status'] ?? 'draft',
                        'featured' => isset($_POST['featured']) ? 1 : 0,
                        'display_order' => intval($_POST['display_order'] ?? 0),
                        'metric1_label' => trim($_POST['metric1_label'] ?? ''),
                        'metric1_value' => trim($_POST['metric1_value'] ?? ''),
                        'metric1_color' => $_POST['metric1_color'] ?? 'purple',
                        'metric2_label' => trim($_POST['metric2_label'] ?? ''),
                        'metric2_value' => trim($_POST['metric2_value'] ?? ''),
                        'metric2_color' => $_POST['metric2_color'] ?? 'blue',
                        'metric3_label' => trim($_POST['metric3_label'] ?? ''),
                        'metric3_value' => trim($_POST['metric3_value'] ?? ''),
                        'metric3_color' => $_POST['metric3_color'] ?? 'green',
                        'services_used' => trim($_POST['services_used'] ?? ''),
                        'sector' => $_POST['sector'] ?? 'general',
                        'meta_title' => trim($_POST['meta_title'] ?? ''),
                        'meta_description' => trim($_POST['meta_description'] ?? ''),
                        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                        'published_at' => $publishedAt,
                        'images' => $galleryImages
                    ];
                    
                    try {
                        if ($_POST['action'] === 'create') {
                            $result = $testimonials->createTestimonial($data);
                            if ($result) {
                                $message = 'Testimonio creado exitosamente';
                                $messageType = 'success';
                                $redirect = $_POST['redirect_to'] ?? 'admin_testimonials.php?action=edit&id=' . $result;
                                $separator = strpos($redirect, '?') !== false ? '&' : '?';
                                header('Location: ' . $redirect . $separator . 'message=' . urlencode($message));
                                exit;
                            } else {
                                $message = 'Error al crear el testimonio. No se pudo insertar en la base de datos.';
                                $messageType = 'error';
                            }
                        } else {
                            $id = intval($_POST['id']);
                            if (empty($id)) {
                                throw new Exception('ID de testimonio no válido');
                            }
                            $result = $testimonials->updateTestimonial($id, $data);
                            if ($result) {
                                // Redirect to prevent form resubmission and session issues
                                $redirect = $_POST['redirect_to'] ?? 'admin_testimonials.php?action=edit&id=' . $id;
                                $separator = strpos($redirect, '?') !== false ? '&' : '?';
                                header('Location: ' . $redirect . $separator . 'message=' . urlencode('Testimonio actualizado exitosamente') . '&type=success');
                                exit;
                            } else {
                                $message = 'Error al actualizar el testimonio. No se pudo actualizar en la base de datos.';
                                $messageType = 'error';
                            }
                        }
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $messageType = 'error';
                        error_log("Testimonial Error: " . $e->getMessage());
                        error_log("POST Data: " . print_r($_POST, true));
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $result = $testimonials->deleteTestimonial($id);
                if ($result) {
                    $message = 'Testimonio eliminado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el testimonio';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get action
$action = $_GET['action'] ?? 'list';
$editTestimonial = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $editTestimonial = $testimonials->getTestimonialById(intval($_GET['id']));
    if (!$editTestimonial) {
        $action = 'list';
        $message = 'Testimonio no encontrado';
        $messageType = 'error';
    }
}

// Get message from URL
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Get all testimonials for list
$allTestimonials = $testimonials->getAllTestimonials(100);

$pageTitle = 'Testimonios - Panel de Administración - SOPHEA';

// Include header with sidebar layout
include 'includes/admin_header.php';
?>

<div class="relative flex h-screen w-full overflow-hidden">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10">
        <div class="mx-auto max-w-[1400px]">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-3xl">quote</span>
                        Gestión de Testimonios
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Administra los casos de éxito y testimonios del sitio</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="admin_testimonials.php?action=new" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    <span>Nuevo Testimonio</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- CKEditor Script -->
            <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
            
            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                            <i class="ph-bold ph-arrow-left text-2xl"></i>
                        </a>
                        <a href="?logout=1" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
                            <?php if ($messageType === 'error'): ?>
                                <p class="text-sm mt-2 opacity-90">
                                    Si el problema persiste, verifica:
                                    <ul class="list-disc list-inside mt-1 ml-4">
                                        <li>Que la tabla "testimonials" exista en la base de datos</li>
                                        <li>Que los campos obligatorios estén completos (Nombre del Cliente y Testimonio)</li>
                                        <li>Los logs de PHP para más detalles</li>
                                    </ul>
                                </p>
                                <a href="tests/test_testimonials.php" target="_blank" class="text-sm underline mt-2 inline-block">
                                    Ejecutar diagnóstico de testimonios
                                </a>
                            <?php endif; ?>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-600 hover:text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-800">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- List View -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Lista de Testimonios</h2>
                        <a href="?action=new" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                            <i class="ph-bold ph-plus"></i> Nuevo Testimonio
                        </a>
                    </div>

                    <?php if (empty($allTestimonials)): ?>
                        <div class="text-center py-16">
                            <i class="ph-bold ph-quote text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">No hay testimonios</h3>
                            <p class="text-gray-500 mb-6">Crea tu primer testimonio para comenzar</p>
                            <a href="?action=new" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                                Crear Testimonio
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Cliente</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Sector</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Estado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Destacado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Orden</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($allTestimonials as $testimonial): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center space-x-3">
                                                    <?php if ($testimonial['client_avatar']): ?>
                                                        <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                                             alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                                             class="w-10 h-10 rounded-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">
                                                            <?php echo strtoupper(substr($testimonial['client_name'], 0, 2)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($testimonial['client_name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                                    <?php echo ucfirst($testimonial['sector']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php 
                                                    echo $testimonial['status'] === 'published' ? 'bg-green-100 text-green-700' : 
                                                        ($testimonial['status'] === 'draft' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700');
                                                ?>">
                                                    <?php 
                                                    echo $testimonial['status'] === 'published' ? 'Publicado' : 
                                                        ($testimonial['status'] === 'draft' ? 'Borrador' : 'Archivado');
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if ($testimonial['featured']): ?>
                                                    <i class="ph-bold ph-star-fill text-yellow-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="ph-bold ph-star text-gray-300 text-xl"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                <?php echo $testimonial['display_order']; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center space-x-2">
                                                    <a href="testimonial.php?slug=<?php echo htmlspecialchars($testimonial['slug']); ?>" 
                                                       target="_blank"
                                                       class="text-green-600 hover:text-green-800" title="Ver">
                                                        <i class="ph-bold ph-eye"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?php echo $testimonial['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-800" title="Editar">
                                                        <i class="ph-bold ph-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este testimonio?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                                            <i class="ph-bold ph-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($action === 'new' || $action === 'edit'): ?>
                <!-- Create/Edit Form -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <?php echo $action === 'edit' ? 'Editar Testimonio' : 'Nuevo Testimonio'; ?>
                    </h2>

                    <form method="POST" id="testimonialForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                        <?php if ($action === 'edit' && isset($editTestimonial['id'])): ?>
                            <input type="hidden" name="id" value="<?php echo $editTestimonial['id']; ?>">
                        <?php endif; ?>

                        <div class="space-y-6">
                            <!-- Client Information -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Información del Cliente</h3>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Cliente *</label>
                                        <input type="text" name="client_name" required
                                               value="<?php echo htmlspecialchars($editTestimonial['client_name'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Título/Cargo</label>
                                        <input type="text" name="client_title"
                                               value="<?php echo htmlspecialchars($editTestimonial['client_title'] ?? ''); ?>"
                                               placeholder="Ej: Cirujano Plástico"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Empresa</label>
                                        <input type="text" name="client_company"
                                               value="<?php echo htmlspecialchars($editTestimonial['client_company'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ubicación</label>
                                        <input type="text" name="client_location"
                                               value="<?php echo htmlspecialchars($editTestimonial['client_location'] ?? ''); ?>"
                                               placeholder="Ej: Tuxtla Gutiérrez, Chiapas"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                </div>
                                
                                <!-- Avatar -->
                                <div class="mt-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Avatar del Cliente</label>
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-1">
                                            <input type="file" name="client_avatar_file" id="client_avatar_file"
                                                   accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                            <p class="text-xs text-gray-500 mt-1">O ingresa una URL:</p>
                                            <input type="url" name="client_avatar" id="client_avatar_url"
                                                   value="<?php echo htmlspecialchars($editTestimonial['client_avatar'] ?? ''); ?>"
                                                   placeholder="https://ejemplo.com/avatar.jpg"
                                                   class="w-full px-4 py-2 border rounded-lg mt-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        </div>
                                        <?php if (isset($editTestimonial) && !empty($editTestimonial['client_avatar'])): ?>
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Actual:</p>
                                                <img src="<?php echo htmlspecialchars($editTestimonial['client_avatar']); ?>" 
                                                     alt="Avatar" class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonial Content -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Contenido del Testimonio</h3>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Testimonio Corto (Resumen) *</label>
                                    <textarea name="testimonial_text" rows="4" required
                                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editTestimonial['testimonial_text'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Este texto se mostrará en la página de inicio</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Historia Completa</label>
                                    <textarea name="full_story" id="full_story" rows="10"
                                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editTestimonial['full_story'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Esta historia se mostrará en la página de detalle</p>
                                </div>
                            </div>

                            <!-- Metrics -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Métricas de Resultados</h3>
                                
                                <div class="grid md:grid-cols-3 gap-4">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <h4 class="font-semibold text-gray-700 mb-3">Métrica <?php echo $i; ?></h4>
                                            <div class="space-y-2">
                                                <input type="text" name="metric<?php echo $i; ?>_label"
                                                       value="<?php echo htmlspecialchars($editTestimonial['metric' . $i . '_label'] ?? ''); ?>"
                                                       placeholder="Etiqueta (ej: Citas Mensuales)"
                                                       class="w-full px-3 py-2 border rounded-lg text-sm">
                                                <input type="text" name="metric<?php echo $i; ?>_value"
                                                       value="<?php echo htmlspecialchars($editTestimonial['metric' . $i . '_value'] ?? ''); ?>"
                                                       placeholder="Valor (ej: +287%)"
                                                       class="w-full px-3 py-2 border rounded-lg text-sm">
                                                <select name="metric<?php echo $i; ?>_color" class="w-full px-3 py-2 border rounded-lg text-sm">
                                                    <option value="purple" <?php echo ($editTestimonial['metric' . $i . '_color'] ?? 'purple') === 'purple' ? 'selected' : ''; ?>>Morado</option>
                                                    <option value="blue" <?php echo ($editTestimonial['metric' . $i . '_color'] ?? '') === 'blue' ? 'selected' : ''; ?>>Azul</option>
                                                    <option value="green" <?php echo ($editTestimonial['metric' . $i . '_color'] ?? '') === 'green' ? 'selected' : ''; ?>>Verde</option>
                                                    <option value="red" <?php echo ($editTestimonial['metric' . $i . '_color'] ?? '') === 'red' ? 'selected' : ''; ?>>Rojo</option>
                                                    <option value="orange" <?php echo ($editTestimonial['metric' . $i . '_color'] ?? '') === 'orange' ? 'selected' : ''; ?>>Naranja</option>
                                                </select>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <!-- Images and Settings -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Imágenes</h3>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Imagen Destacada</label>
                                    <input type="file" name="featured_image_file" id="featured_image_file"
                                           accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <p class="text-xs text-gray-500 mt-1">O ingresa una URL:</p>
                                    <input type="url" name="featured_image" id="featured_image_url"
                                           value="<?php echo htmlspecialchars($editTestimonial['featured_image'] ?? ''); ?>"
                                           placeholder="https://ejemplo.com/imagen.jpg"
                                           class="w-full px-4 py-2 border rounded-lg mt-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Galería de Imágenes</label>
                                    <input type="file" name="gallery_images[]" id="gallery_images" multiple
                                           accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <p class="text-xs text-gray-500 mt-1">Puedes seleccionar múltiples imágenes</p>
                                    
                                    <?php if ($action === 'edit' && isset($editTestimonial) && !empty($editTestimonial['images'])): ?>
                                        <div class="mt-4 space-y-2">
                                            <p class="text-sm font-medium text-gray-700">Imágenes Actuales:</p>
                                            <?php foreach ($editTestimonial['images'] as $index => $image): ?>
                                                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded">
                                                    <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image['image_path']); ?>">
                                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($image['image_alt']); ?>"
                                                         class="w-16 h-16 object-cover rounded">
                                                    <input type="text" name="existing_images_alt[]" 
                                                           value="<?php echo htmlspecialchars($image['image_alt']); ?>"
                                                           placeholder="Texto alternativo"
                                                           class="flex-1 px-3 py-2 border rounded text-sm">
                                                    <button type="button" onclick="this.parentElement.remove()" 
                                                            class="text-red-600 hover:text-red-800">
                                                        <i class="ph-bold ph-x"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Settings -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Configuración</h3>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Slug (URL)</label>
                                        <input type="text" name="slug" id="slug"
                                               value="<?php echo htmlspecialchars($editTestimonial['slug'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <p class="text-xs text-gray-500 mt-1">Se genera automáticamente desde el nombre si se deja vacío</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sector</label>
                                        <select name="sector" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                            <option value="salud" <?php echo ($editTestimonial['sector'] ?? '') === 'salud' ? 'selected' : ''; ?>>Salud</option>
                                            <option value="general" <?php echo ($editTestimonial['sector'] ?? 'general') === 'general' ? 'selected' : ''; ?>>General</option>
                                            <option value="retail" <?php echo ($editTestimonial['sector'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail</option>
                                            <option value="servicios" <?php echo ($editTestimonial['sector'] ?? '') === 'servicios' ? 'selected' : ''; ?>>Servicios</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                                        <select name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                            <option value="draft" <?php echo ($editTestimonial['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                            <option value="published" <?php echo ($editTestimonial['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publicado</option>
                                            <option value="archived" <?php echo ($editTestimonial['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Publicación</label>
                                        <input type="datetime-local" name="published_at"
                                               value="<?php echo (isset($editTestimonial['published_at']) && $editTestimonial['published_at']) ? date('Y-m-d\TH:i', strtotime($editTestimonial['published_at'])) : ''; ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Orden de Visualización</label>
                                        <input type="number" name="display_order"
                                               value="<?php echo isset($editTestimonial) ? ($editTestimonial['display_order'] ?? 0) : 0; ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <p class="text-xs text-gray-500 mt-1">Menor número = aparece primero</p>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" name="featured" id="featured" value="1"
                                               <?php echo (isset($editTestimonial) && ($editTestimonial['featured'] ?? 0)) ? 'checked' : ''; ?>
                                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <label for="featured" class="ml-2 text-sm font-semibold text-gray-700">
                                            Destacado (aparece en inicio)
                                        </label>
                                    </div>
                                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-xs text-blue-700">
                                            <i class="ph-bold ph-info"></i>
                                            <strong>Nota:</strong> Si no está marcado como "Destacado", el testimonio aún aparecerá en el inicio si no hay otros testimonios destacados. 
                                            Para asegurar que aparezca siempre, márcalo como "Destacado" y "Publicado".
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Servicios Utilizados</label>
                                    <input type="text" name="services_used"
                                           value="<?php echo htmlspecialchars($editTestimonial['services_used'] ?? ''); ?>"
                                           placeholder="Ej: Compliance COFEPRIS + Ads + Web"
                                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>

                            <!-- SEO -->
                            <div class="border-b pb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">SEO</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Título</label>
                                        <input type="text" name="meta_title"
                                               value="<?php echo htmlspecialchars($editTestimonial['meta_title'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Descripción</label>
                                        <textarea name="meta_description" rows="2"
                                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editTestimonial['meta_description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Keywords</label>
                                        <input type="text" name="meta_keywords"
                                               value="<?php echo htmlspecialchars($editTestimonial['meta_keywords'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center space-x-4 pt-4">
                                <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                                    <?php echo $action === 'edit' ? 'Actualizar Testimonio' : 'Crear Testimonio'; ?>
                                </button>
                                <a href="admin_testimonials.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition font-semibold">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-generate slug from client name
        document.querySelector('input[name="client_name"]')?.addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                const name = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = name;
                slugInput.dataset.autoGenerated = 'true';
            }
        });

        // Initialize CKEditor for full story
        if (document.getElementById('full_story')) {
            CKEDITOR.replace('full_story', {
                height: 400,
                toolbar: [
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'insert', items: ['Image'] },
                    { name: 'styles', items: ['Format'] },
                    { name: 'colors', items: ['TextColor', 'BGColor'] },
                    { name: 'tools', items: ['Source'] }
                ]
            });
        }
    </script>
            </div>
<?php include 'includes/layout_end.php'; ?>
