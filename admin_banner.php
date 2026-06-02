<?php
// Use authentication helper
require_once 'admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Admin Banner - SOPHEA';
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

// Load SiteSettings class
require_once 'classes/SiteSettings.php';

// Initialize settings
$settings = new SiteSettings();
$message = '';
$messageType = '';

// Configuración de subida de imágenes
define('BANNER_UPLOAD_DIR', __DIR__ . '/uploads/banner/');
define('LOGO_UPLOAD_DIR', __DIR__ . '/uploads/logo/');
define('BANNER_UPLOAD_URL', 'uploads/banner/');
define('LOGO_UPLOAD_URL', 'uploads/logo/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif', 'image/svg+xml']);

// Crear directorios de uploads si no existen
if (!file_exists(BANNER_UPLOAD_DIR)) {
    mkdir(BANNER_UPLOAD_DIR, 0755, true);
}
if (!file_exists(LOGO_UPLOAD_DIR)) {
    mkdir(LOGO_UPLOAD_DIR, 0755, true);
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
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg'
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

// Función para subir imagen
function uploadImage($file, $uploadDir, $uploadUrl, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tipo de archivo usando MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['error' => 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, WEBP, GIF, SVG'];
    }
    
    // Validar tamaño
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
    $filepath = $uploadDir . $filename;
    
    // Verificar que no exista (muy improbable pero por seguridad)
    if (file_exists($filepath)) {
        $filename = $sanitizedFilename . '_' . rand(1000, 9999) . '.' . $validExtension;
        $filepath = $uploadDir . $filename;
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $uploadUrl . $filename;
    }
    
    return ['error' => 'Error al subir el archivo'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_banner') {
        // Procesar banner
        $hasFile = isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK;
        $hasUrl = !empty(trim($_POST['banner_url'] ?? ''));
        
        // Verificar errores de subida de archivo
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            $errorCode = $_FILES['banner_image']['error'];
            $message = $uploadErrors[$errorCode] ?? 'Error desconocido al subir el archivo (código: ' . $errorCode . ')';
            $messageType = 'error';
        } elseif ($hasFile) {
            $uploadResult = uploadImage($_FILES['banner_image'], BANNER_UPLOAD_DIR, BANNER_UPLOAD_URL, 'banner_');
            
            if (is_array($uploadResult) && isset($uploadResult['error'])) {
                $message = $uploadResult['error'];
                $messageType = 'error';
            } elseif (is_string($uploadResult)) {
                // Eliminar imagen anterior si existe
                $oldBanner = $settings->getMainBanner();
                if ($oldBanner && file_exists(__DIR__ . '/' . $oldBanner) && strpos($oldBanner, 'uploads/banner/') !== false) {
                    @unlink(__DIR__ . '/' . $oldBanner);
                }
                
                try {
                    if ($settings->setMainBanner($uploadResult)) {
                        // Redirect to prevent form resubmission and session issues
                        $redirect = $_POST['redirect_to'] ?? 'admin_banner.php';
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode('Banner actualizado exitosamente') . '&type=success');
                        exit;
                    } else {
                        $message = 'Error al guardar el banner en la base de datos. Por favor, verifica los logs del servidor.';
                        $messageType = 'error';
                        error_log("Error al guardar banner: setMainBanner retornó false");
                    }
                } catch (Exception $e) {
                    $message = 'Error al guardar el banner: ' . $e->getMessage();
                    $messageType = 'error';
                    error_log("Excepción al guardar banner: " . $e->getMessage());
                }
            } else {
                $message = 'Error desconocido al procesar la imagen del banner';
                $messageType = 'error';
            }
        } elseif ($hasUrl) {
            // Validar URL
            $url = trim($_POST['banner_url']);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                try {
                    if ($settings->setMainBanner($url)) {
                        // Redirect to prevent form resubmission and session issues
                        $redirect = $_POST['redirect_to'] ?? 'admin_banner.php';
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode('Banner actualizado exitosamente') . '&type=success');
                        exit;
                    } else {
                        $message = 'Error al guardar el banner en la base de datos. Por favor, verifica los logs del servidor.';
                        $messageType = 'error';
                        error_log("Error al guardar banner URL: setMainBanner retornó false");
                    }
                } catch (Exception $e) {
                    $message = 'Error al guardar el banner: ' . $e->getMessage();
                    $messageType = 'error';
                    error_log("Excepción al guardar banner URL: " . $e->getMessage());
                }
            } else {
                $message = 'La URL proporcionada no es válida';
                $messageType = 'error';
            }
        } else {
            $message = 'Por favor, selecciona una imagen o ingresa una URL válida';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'update_logo') {
        // Procesar logo
        $hasFile = isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK;
        $hasUrl = !empty(trim($_POST['logo_url'] ?? ''));
        
        // Verificar errores de subida de archivo
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] !== UPLOAD_ERR_OK && $_FILES['logo_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            $errorCode = $_FILES['logo_image']['error'];
            $message = $uploadErrors[$errorCode] ?? 'Error desconocido al subir el archivo (código: ' . $errorCode . ')';
            $messageType = 'error';
        } elseif ($hasFile) {
            $uploadResult = uploadImage($_FILES['logo_image'], LOGO_UPLOAD_DIR, LOGO_UPLOAD_URL, 'logo_');
            
            if (is_array($uploadResult) && isset($uploadResult['error'])) {
                $message = $uploadResult['error'];
                $messageType = 'error';
            } elseif (is_string($uploadResult)) {
                // Eliminar imagen anterior si existe
                $oldLogo = $settings->getMainLogo();
                if ($oldLogo && file_exists(__DIR__ . '/' . $oldLogo) && strpos($oldLogo, 'uploads/logo/') !== false) {
                    @unlink(__DIR__ . '/' . $oldLogo);
                }
                
                try {
                    if ($settings->setMainLogo($uploadResult)) {
                        // Redirect to prevent form resubmission and session issues
                        $redirect = $_POST['redirect_to'] ?? 'admin_banner.php';
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode('Logo actualizado exitosamente') . '&type=success');
                        exit;
                    } else {
                        $message = 'Error al guardar el logo en la base de datos. Por favor, verifica los logs del servidor.';
                        $messageType = 'error';
                        error_log("Error al guardar logo: setMainLogo retornó false");
                    }
                } catch (Exception $e) {
                    $message = 'Error al guardar el logo: ' . $e->getMessage();
                    $messageType = 'error';
                    error_log("Excepción al guardar logo: " . $e->getMessage());
                }
            } else {
                $message = 'Error desconocido al procesar la imagen del logo';
                $messageType = 'error';
            }
        } elseif ($hasUrl) {
            // Validar URL
            $url = trim($_POST['logo_url']);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                try {
                    if ($settings->setMainLogo($url)) {
                        // Redirect to prevent form resubmission and session issues
                        $redirect = $_POST['redirect_to'] ?? 'admin_banner.php';
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode('Logo actualizado exitosamente') . '&type=success');
                        exit;
                    } else {
                        $message = 'Error al guardar el logo en la base de datos. Por favor, verifica los logs del servidor.';
                        $messageType = 'error';
                        error_log("Error al guardar logo URL: setMainLogo retornó false");
                    }
                } catch (Exception $e) {
                    $message = 'Error al guardar el logo: ' . $e->getMessage();
                    $messageType = 'error';
                    error_log("Excepción al guardar logo URL: " . $e->getMessage());
                }
            } else {
                $message = 'La URL proporcionada no es válida';
                $messageType = 'error';
            }
        } else {
            $message = 'Por favor, selecciona una imagen o ingresa una URL válida';
            $messageType = 'error';
        }
    }
}

// Get current values
$currentBanner = $settings->getMainBanner();
$currentLogo = $settings->getMainLogo();

// Get message from URL if exists
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}

$pageTitle = 'Banner y Logo - Panel de Administración - SOPHEA';

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
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Gestión de Banner y Logo</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Administra el banner principal y el logo del sitio</p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
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
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Banner Section -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center space-x-2">
                    <i class="ph-bold ph-image text-purple-600"></i>
                    <span>Banner Principal</span>
                </h2>
                
                <!-- Información de medidas -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start space-x-2">
                        <i class="ph-bold ph-info text-blue-600 text-xl mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold text-blue-800 mb-1">Medidas Ideales para el Banner:</p>
                            <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                                <li><strong>Recomendado:</strong> 1920 x 600 píxeles (ratio 16:5)</li>
                                <li><strong>Alternativa:</strong> 1920 x 800 píxeles (ratio 12:5)</li>
                                <li><strong>Mínimo:</strong> 1200 x 400 píxeles</li>
                                <li><strong>Formato:</strong> JPG, PNG, WEBP o GIF</li>
                                <li><strong>Tamaño máximo:</strong> 5MB</li>
                            </ul>
                            <p class="text-xs text-blue-600 mt-2">
                                <strong>Nota:</strong> El banner se mostrará en la sección principal (Hero) de la página de inicio.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Banner Preview -->
                <?php if ($currentBanner): ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Banner Actual:</p>
                        <img src="<?php echo htmlspecialchars($currentBanner); ?>" 
                             alt="Banner Actual" 
                             class="max-w-full h-auto rounded-lg border border-gray-300"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <p class="text-xs text-red-600 hidden">Error al cargar la imagen</p>
                    </div>
                <?php endif; ?>

                <!-- Banner Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_banner">
                    
                    <!-- Upload File -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ph-bold ph-upload"></i> Subir Banner desde tu Computadora
                        </label>
                        <input type="file" 
                               name="banner_image" 
                               id="banner_image"
                               accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Selecciona una imagen desde tu escritorio</p>
                        
                        <!-- Preview -->
                        <div id="banner-preview" class="mt-3 hidden">
                            <p class="text-sm font-medium text-gray-700 mb-2">Vista Previa:</p>
                            <img id="banner-preview-img" src="" alt="Preview" class="max-w-full h-auto rounded-lg border border-gray-300">
                        </div>
                    </div>
                    
                    <!-- Or URL -->
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ph-bold ph-link"></i> O Ingresa una URL de Imagen
                        </label>
                        <input type="url" 
                               name="banner_url"
                               id="banner_url"
                               value="<?php echo htmlspecialchars($currentBanner && strpos($currentBanner, 'http') === 0 ? $currentBanner : ''); ?>"
                               placeholder="https://ejemplo.com/banner.jpg"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Si prefieres usar una imagen desde internet</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                        <i class="ph-bold ph-floppy-disk"></i> Guardar Banner
                    </button>
                </form>
            </div>

            <!-- Logo Section -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center space-x-2">
                    <i class="ph-bold ph-image-square text-purple-600"></i>
                    <span>Logo Principal</span>
                </h2>
                
                <!-- Información de medidas -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start space-x-2">
                        <i class="ph-bold ph-info text-green-600 text-xl mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold text-green-800 mb-1">Medidas Ideales para el Logo:</p>
                            <ul class="text-xs text-green-700 space-y-1 list-disc list-inside">
                                <li><strong>Recomendado:</strong> 200 x 60 píxeles (ratio 3.33:1)</li>
                                <li><strong>Alternativa:</strong> 300 x 90 píxeles o 400 x 120 píxeles</li>
                                <li><strong>Formato:</strong> PNG con fondo transparente (recomendado) o SVG</li>
                                <li><strong>También acepta:</strong> JPG, WEBP, GIF</li>
                                <li><strong>Tamaño máximo:</strong> 2MB</li>
                            </ul>
                            <p class="text-xs text-green-600 mt-2">
                                <strong>Nota:</strong> El logo se mostrará en el header de todas las páginas del sitio.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Logo Preview -->
                <?php if ($currentLogo): ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Logo Actual:</p>
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo htmlspecialchars($currentLogo); ?>" 
                                 alt="Logo Actual" 
                                 class="max-h-20 w-auto rounded-lg border border-gray-300 bg-white p-2"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <p class="text-xs text-red-600 hidden">Error al cargar la imagen</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Logo Actual:</p>
                        <div class="flex items-center space-x-4">
                            <img src="assets/logo_SP1.png" alt="Logo por defecto" class="max-h-20 w-auto rounded-lg border border-gray-300 bg-white p-2">
                            <div>
                                <span class="text-sm text-gray-500 block">(Logo por defecto - assets/logo_SP1.png)</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Logo Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_logo">
                    
                    <!-- Upload File -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ph-bold ph-upload"></i> Subir Logo desde tu Computadora
                        </label>
                        <input type="file" 
                               name="logo_image" 
                               id="logo_image"
                               accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/svg+xml"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Selecciona una imagen desde tu escritorio</p>
                        
                        <!-- Preview -->
                        <div id="logo-preview" class="mt-3 hidden">
                            <p class="text-sm font-medium text-gray-700 mb-2">Vista Previa:</p>
                            <img id="logo-preview-img" src="" alt="Preview" class="max-h-20 w-auto rounded-lg border border-gray-300 bg-white p-2">
                        </div>
                    </div>
                    
                    <!-- Or URL -->
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ph-bold ph-link"></i> O Ingresa una URL de Imagen
                        </label>
                        <input type="url" 
                               name="logo_url"
                               id="logo_url"
                               value="<?php echo htmlspecialchars($currentLogo && strpos($currentLogo, 'http') === 0 ? $currentLogo : ''); ?>"
                               placeholder="https://ejemplo.com/logo.png"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Si prefieres usar una imagen desde internet</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
                        <i class="ph-bold ph-floppy-disk"></i> Guardar Logo
                    </button>
                </form>
            </div>

            <!-- Back to Admin -->
            <div class="mt-6 text-center">
                <a href="admin.php" class="text-purple-600 hover:text-purple-800 font-medium">
                    <i class="ph-bold ph-arrow-left"></i> Volver al Panel de Administración
                </a>
            </div>
        </div>
    </div>

    <script>
        // Suppress Chrome extension errors (these are harmless and come from browser extensions)
        if (typeof chrome !== 'undefined' && chrome.runtime) {
            const originalError = console.error;
            console.error = function(...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('runtime.lastError')) {
                    // Silently ignore Chrome extension errors
                    return;
                }
                originalError.apply(console, args);
            };
        }
        
        // Banner preview
        const bannerInput = document.getElementById('banner_image');
        const bannerPreview = document.getElementById('banner-preview');
        const bannerPreviewImg = document.getElementById('banner-preview-img');
        const bannerUrlInput = document.getElementById('banner_url');
        
        if (bannerInput) {
            bannerInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 5MB');
                        bannerInput.value = '';
                        return;
                    }
                    
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, WEBP, GIF');
                        bannerInput.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        bannerPreviewImg.src = e.target.result;
                        bannerPreview.classList.remove('hidden');
                        if (bannerUrlInput) {
                            bannerUrlInput.value = '';
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    bannerPreview.classList.add('hidden');
                }
            });
        }
        
        if (bannerUrlInput) {
            bannerUrlInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    bannerPreview.classList.add('hidden');
                    if (bannerInput) {
                        bannerInput.value = '';
                    }
                }
            });
        }

        // Logo preview
        const logoInput = document.getElementById('logo_image');
        const logoPreview = document.getElementById('logo-preview');
        const logoPreviewImg = document.getElementById('logo-preview-img');
        const logoUrlInput = document.getElementById('logo_url');
        
        if (logoInput) {
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 2MB');
                        logoInput.value = '';
                        return;
                    }
                    
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif', 'image/svg+xml'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, WEBP, GIF, SVG');
                        logoInput.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        logoPreviewImg.src = e.target.result;
                        logoPreview.classList.remove('hidden');
                        if (logoUrlInput) {
                            logoUrlInput.value = '';
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    logoPreview.classList.add('hidden');
                }
            });
        }
        
        if (logoUrlInput) {
            logoUrlInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    logoPreview.classList.add('hidden');
                    if (logoInput) {
                        logoInput.value = '';
                    }
                }
            });
        }
    </script>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/admin_footer.php'; ?>
