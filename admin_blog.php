<?php
// Use authentication helper
require_once 'admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Admin Blog - SOPHEA';
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

// Load configurations
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Blog.php';

// Logout is handled above

// Configuración de subida de imágenes
define('UPLOAD_DIR', __DIR__ . '/uploads/blog_images/');
define('UPLOAD_URL', 'uploads/blog_images/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif']);

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
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

// Función para subir imagen
function uploadFeaturedImage($file, $postId = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tipo de archivo usando MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['error' => 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, WEBP, GIF'];
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
    $prefix = 'blog_' . ($postId ? $postId . '_' : '');
    $sanitizedFilename = sanitizeFilename($file['name'], $prefix);
    $filename = $sanitizedFilename . '.' . $validExtension;
    $filepath = UPLOAD_DIR . $filename;
    
    // Verificar que no exista (muy improbable pero por seguridad)
    if (file_exists($filepath)) {
        $filename = $sanitizedFilename . '_' . rand(1000, 9999) . '.' . $validExtension;
        $filepath = UPLOAD_DIR . $filename;
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return UPLOAD_URL . $filename;
    }
    
    return ['error' => 'Error al subir el archivo'];
}

// Initialize blog
$blog = new Blog();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                // Procesar imagen destacada
                $featuredImage = $_POST['featured_image'] ?? '';
                
                // Si se subió un archivo, procesarlo (prioridad sobre URL)
                if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
                    $postId = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $uploadResult = uploadFeaturedImage($_FILES['featured_image_file'], $postId);
                    
                    if (is_array($uploadResult) && isset($uploadResult['error'])) {
                        $message = $uploadResult['error'];
                        $messageType = 'error';
                        // Continuar con el proceso aunque haya error en la imagen
                    } elseif (is_string($uploadResult)) {
                        $featuredImage = $uploadResult;
                    }
                }
                
                $data = [
                    'title' => $_POST['title'] ?? '',
                    'slug' => $_POST['slug'] ?? '',
                    'excerpt' => $_POST['excerpt'] ?? '',
                    'content' => $_POST['content'] ?? '',
                    'featured_image' => $featuredImage,
                    'author_name' => $_POST['author_name'] ?? 'SOPHEA',
                    'status' => $_POST['status'] ?? 'draft',
                    'published_at' => $_POST['published_at'] ?? '',
                    'meta_title' => $_POST['meta_title'] ?? '',
                    'meta_description' => $_POST['meta_description'] ?? '',
                    'meta_keywords' => $_POST['meta_keywords'] ?? '',
                    'categories' => isset($_POST['categories']) ? $_POST['categories'] : []
                ];
                
                if ($_POST['action'] === 'create') {
                    $result = $blog->createPost($data);
                    if ($result) {
                        // Si se subió una imagen después de crear, actualizar con el ID
                        if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK && empty($featuredImage)) {
                            $uploadResult = uploadFeaturedImage($_FILES['featured_image_file'], $result);
                            if (is_string($uploadResult)) {
                                $updateData = ['featured_image' => $uploadResult];
                                $blog->updatePost($result, $updateData);
                            }
                        }
                        $message = 'Artículo creado exitosamente';
                        $messageType = 'success';
                        $redirect = $_POST['redirect_to'] ?? 'admin_blog.php?action=edit&id=' . $result;
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode($message));
                        exit;
                    } else {
                        $message = 'Error al crear el artículo';
                        $messageType = 'error';
                    }
                } else {
                    $id = intval($_POST['id']);
                    $result = $blog->updatePost($id, $data);
                    if ($result) {
                        // Redirect to prevent form resubmission and session issues
                        $redirect = $_POST['redirect_to'] ?? 'admin_blog.php?action=edit&id=' . $id;
                        $separator = strpos($redirect, '?') !== false ? '&' : '?';
                        header('Location: ' . $redirect . $separator . 'message=' . urlencode('Artículo actualizado exitosamente') . '&type=success');
                        exit;
                    } else {
                        $message = 'Error al actualizar el artículo';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $result = $blog->deletePost($id);
                if ($result) {
                    $message = 'Artículo eliminado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el artículo';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get message from URL
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = 'success';
}

// Get action
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get posts for listing
$posts = [];
if ($action === 'list') {
    $posts = $blog->getAllPosts(100);
}

// Get post for editing
$editPost = null;
if ($action === 'edit' && $editId) {
    $editPost = $blog->getPostById($editId);
    if (!$editPost) {
        $action = 'list';
        $message = 'Artículo no encontrado';
        $messageType = 'error';
    }
}

// Get all categories
$allCategories = $blog->getAllCategories();

$pageTitle = 'Blog - Panel de Administración - SOPHEA';

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
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Gestión de Blog</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Administra los artículos del blog</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="admin_blog.php?action=create" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    <span>Nuevo Artículo</span>
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

            <?php if ($action === 'list'): ?>
                <!-- Posts List -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Artículos del Blog</h2>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-12">
                        <i class="ph-bold ph-article text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 mb-4">No hay artículos aún</p>
                        <a href="admin_blog.php?action=new" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                            Crear Primer Artículo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Título</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Estado</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Autor</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fecha</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Vistas</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($posts as $post): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="text-purple-600 hover:text-purple-800 font-medium">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                                echo $post['status'] === 'published' ? 'bg-green-100 text-green-700' : 
                                                    ($post['status'] === 'draft' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'); 
                                            ?>">
                                                <?php 
                                                echo $post['status'] === 'published' ? 'Publicado' : 
                                                    ($post['status'] === 'draft' ? 'Borrador' : 'Archivado'); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($post['author_name']); ?></td>
                                        <td class="px-4 py-3 text-gray-600">
                                            <?php echo $post['published_at'] ? date('d/m/Y', strtotime($post['published_at'])) : '-'; ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo number_format($post['views']); ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <a href="admin_blog.php?action=edit&id=<?php echo $post['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800" title="Editar">
                                                    <i class="ph-bold ph-pencil"></i>
                                                </a>
                                                <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                                   target="_blank"
                                                   class="text-green-600 hover:text-green-800" title="Ver">
                                                    <i class="ph-bold ph-eye"></i>
                                                </a>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este artículo?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
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
                    <?php echo $action === 'edit' ? 'Editar Artículo' : 'Nuevo Artículo'; ?>
                </h2>

                <form method="POST" id="postForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $editPost['id']; ?>">
                    <?php endif; ?>

                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                            <input type="text" name="title" required
                                   value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Slug (URL)</label>
                            <input type="text" name="slug" id="slug"
                                   value="<?php echo htmlspecialchars($editPost['slug'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">Se genera automáticamente desde el título si se deja vacío</p>
                        </div>

                        <!-- Excerpt -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Resumen</label>
                            <textarea name="excerpt" rows="3"
                                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editPost['excerpt'] ?? ''); ?></textarea>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contenido *</label>
                            <textarea name="content" id="content" required
                                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editPost['content'] ?? ''); ?></textarea>
                        </div>

                        <!-- Featured Image -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Imagen Destacada</label>
                            
                            <!-- Información de medidas ideales -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start space-x-2">
                                    <i class="ph-bold ph-info text-blue-600 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-blue-800 mb-1">Medidas Ideales para la Imagen:</p>
                                        <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                                            <li><strong>Recomendado:</strong> 1200 x 630 píxeles (ratio 1.91:1)</li>
                                            <li><strong>Alternativa:</strong> 1200 x 800 píxeles (ratio 3:2)</li>
                                            <li><strong>Mínimo:</strong> 800 x 420 píxeles</li>
                                            <li><strong>Formato:</strong> JPG, PNG, WEBP o GIF</li>
                                            <li><strong>Tamaño máximo:</strong> 5MB</li>
                                        </ul>
                                        <p class="text-xs text-blue-600 mt-2">
                                            <strong>Nota:</strong> La imagen se mostrará con altura fija de 192px en el listado del blog.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Opción 1: Subir archivo -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ph-bold ph-upload"></i> Subir Imagen desde tu Computadora
                                </label>
                                <input type="file" 
                                       name="featured_image_file" 
                                       id="featured_image_file"
                                       accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">Selecciona una imagen desde tu escritorio</p>
                                
                                <!-- Preview de imagen subida -->
                                <div id="image-preview" class="mt-3 hidden">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Vista Previa:</p>
                                    <img id="preview-img" src="" alt="Preview" class="max-w-xs rounded-lg border border-gray-300">
                                </div>
                            </div>
                            
                            <!-- Opción 2: URL -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="ph-bold ph-link"></i> O Ingresa una URL de Imagen
                                </label>
                                <input type="url" 
                                       name="featured_image"
                                       id="featured_image_url"
                                       value="<?php echo htmlspecialchars($editPost['featured_image'] ?? ''); ?>"
                                       placeholder="https://ejemplo.com/imagen.jpg"
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">Si prefieres usar una imagen desde internet</p>
                            </div>
                            
                            <!-- Preview de imagen actual (si existe) -->
                            <?php if (!empty($editPost['featured_image'])): ?>
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Imagen Actual:</p>
                                    <img src="<?php echo htmlspecialchars($editPost['featured_image']); ?>" 
                                         alt="Preview" 
                                         class="max-w-xs rounded-lg border border-gray-300"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <p class="text-xs text-red-600 hidden">Error al cargar la imagen</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Author -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Autor</label>
                            <input type="text" name="author_name"
                                   value="<?php echo htmlspecialchars($editPost['author_name'] ?? 'SOPHEA'); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="draft" <?php echo ($editPost['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                <option value="published" <?php echo ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publicado</option>
                                <option value="archived" <?php echo ($editPost['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                            </select>
                        </div>

                        <!-- Published At -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Publicación</label>
                            <input type="datetime-local" name="published_at"
                                   value="<?php echo $editPost['published_at'] ? date('Y-m-d\TH:i', strtotime($editPost['published_at'])) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Categories -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Categorías</label>
                            <div class="space-y-2">
                                <?php 
                                $postCategoryIds = [];
                                if ($editPost && !empty($editPost['categories'])) {
                                    foreach ($editPost['categories'] as $cat) {
                                        $postCategoryIds[] = $cat['id'];
                                    }
                                }
                                foreach ($allCategories as $category): 
                                ?>
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>"
                                               <?php echo in_array($category['id'], $postCategoryIds) ? 'checked' : ''; ?>
                                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- SEO Meta Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Título (SEO)</label>
                            <input type="text" name="meta_title"
                                   value="<?php echo htmlspecialchars($editPost['meta_title'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- SEO Meta Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Descripción (SEO)</label>
                            <textarea name="meta_description" rows="2"
                                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editPost['meta_description'] ?? ''); ?></textarea>
                        </div>

                        <!-- SEO Meta Keywords -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Keywords (SEO)</label>
                            <input type="text" name="meta_keywords"
                                   value="<?php echo htmlspecialchars($editPost['meta_keywords'] ?? ''); ?>"
                                   placeholder="palabra1, palabra2, palabra3"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center space-x-4 pt-4">
                            <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                                <?php echo $action === 'edit' ? 'Actualizar Artículo' : 'Crear Artículo'; ?>
                            </button>
                            <a href="admin_blog.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition font-semibold">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-generate slug from title
        document.querySelector('input[name="title"]')?.addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                const title = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = title;
                slugInput.dataset.autoGenerated = 'true';
            }
        });

        // Image preview when file is selected
        const fileInput = document.getElementById('featured_image_file');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const urlInput = document.getElementById('featured_image_url');
        
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 5MB');
                        fileInput.value = '';
                        return;
                    }
                    
                    // Validar tipo
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, WEBP, GIF');
                        fileInput.value = '';
                        return;
                    }
                    
                    // Mostrar preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        // Limpiar URL si se sube archivo
                        if (urlInput) {
                            urlInput.value = '';
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.classList.add('hidden');
                }
            });
        }
        
        // Si se ingresa URL, ocultar preview de archivo
        if (urlInput) {
            urlInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    imagePreview.classList.add('hidden');
                    if (fileInput) {
                        fileInput.value = '';
                    }
                }
            });
        }

        // Initialize CKEditor
        if (document.getElementById('content')) {
            CKEDITOR.replace('content', {
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
<?php include 'includes/layout_end.php'; ?>
