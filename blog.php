<?php
/**
 * SOPHEA - Blog Listing Page
 * 
 * Displays all published blog posts
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Blog.php';

// Initialize blog
$blog = new Blog();

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Get filters
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

// Get category name if filtered
$categoryName = null;
if ($categoryId) {
    $categories = $blog->getAllCategories();
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $categoryName = $cat['name'];
            break;
        }
    }
}

// Get posts with filters
$posts = $blog->getPublishedPosts($perPage, $offset, $categoryId, $searchQuery, $year, $month);
$totalPosts = $blog->getPublishedCount($categoryId, $searchQuery, $year, $month);
$totalPages = ceil($totalPosts / $perPage);

// Get all categories for sidebar
$allCategories = $blog->getAllCategories();

// Get available dates for filter
$availableDates = $blog->getAvailableDates();

// Helper functions
if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl($updateParams = []) {
        $params = $_GET;
        foreach ($updateParams as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }
        unset($params['page']); // Remove page when changing filters
        return 'blog.php' . (!empty($params) ? '?' . http_build_query($params) : '');
    }
}

if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($pageNum) {
        $params = $_GET;
        $params['page'] = $pageNum;
        return 'blog.php?' . http_build_query($params);
    }
}

// Set page title
$pageTitle = $categoryName ? "Blog - {$categoryName}" : "Blog";
?>
<?php include 'header.php'; ?>

<!-- BLOG HEADER -->
<section class="pt-32 pb-12 px-4 bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="container mx-auto max-w-6xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="text-gradient">Blog SOPHEA</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Artículos sobre compliance COFEPRIS, marketing digital y estrategias de crecimiento para el sector salud
            </p>
        </div>
        
        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-8">
            <form method="GET" action="blog.php" class="flex gap-2">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>"
                       placeholder="Buscar artículos..." 
                       class="flex-1 px-6 py-3 border-2 border-purple-200 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <?php if ($categoryId): ?>
                    <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                <?php endif; ?>
                <?php if ($year): ?>
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                <?php endif; ?>
                <?php if ($month): ?>
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                <?php endif; ?>
                <button type="submit" 
                        class="bg-gradient-primary text-white px-8 py-3 rounded-full font-semibold hover:shadow-glow transition-all transform hover:scale-105 flex items-center space-x-2">
                    <i class="ph-bold ph-magnifying-glass"></i>
                    <span>Buscar</span>
                </button>
            </form>
        </div>
        
        <!-- Active Filters -->
        <?php if ($categoryName || $searchQuery || $year || $month): ?>
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <?php if ($categoryName): ?>
                <span class="inline-flex items-center bg-purple-100 text-purple-700 px-4 py-2 rounded-full font-semibold">
                    <i class="ph-bold ph-folder mr-2"></i>
                    Categoría: <?php echo htmlspecialchars($categoryName); ?>
                    <a href="<?php echo buildFilterUrl(['category' => null]); ?>" class="ml-2 text-purple-600 hover:text-purple-800">
                        <i class="ph-bold ph-x"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if ($searchQuery): ?>
                <span class="inline-flex items-center bg-blue-100 text-blue-700 px-4 py-2 rounded-full font-semibold">
                    <i class="ph-bold ph-magnifying-glass mr-2"></i>
                    Búsqueda: "<?php echo htmlspecialchars($searchQuery); ?>"
                    <a href="<?php echo buildFilterUrl(['search' => null]); ?>" class="ml-2 text-blue-600 hover:text-blue-800">
                        <i class="ph-bold ph-x"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if ($year): ?>
                <span class="inline-flex items-center bg-green-100 text-green-700 px-4 py-2 rounded-full font-semibold">
                    <i class="ph-bold ph-calendar mr-2"></i>
                    <?php echo $year; ?>
                    <?php if ($month): ?>
                        - <?php echo date('F', mktime(0, 0, 0, $month, 1)); ?>
                    <?php endif; ?>
                    <a href="<?php echo buildFilterUrl(['year' => null, 'month' => null]); ?>" class="ml-2 text-green-600 hover:text-green-800">
                        <i class="ph-bold ph-x"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if ($categoryName || $searchQuery || $year || $month): ?>
                <a href="blog.php" class="inline-flex items-center bg-gray-100 text-gray-700 px-4 py-2 rounded-full font-semibold hover:bg-gray-200 transition-colors">
                    <i class="ph-bold ph-x-circle mr-2"></i>
                    Limpiar todos los filtros
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    </div>
</section>

<!-- BLOG CONTENT -->
<section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
        <div class="grid lg:grid-cols-4 gap-8">
            <!-- MAIN CONTENT -->
            <div class="lg:col-span-3">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-16">
                        <i class="ph-bold ph-article text-6xl text-gray-300 mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-700 mb-2">
                            <?php if ($searchQuery || $categoryId || $year): ?>
                                No se encontraron artículos con los filtros seleccionados
                            <?php else: ?>
                                No hay artículos disponibles
                            <?php endif; ?>
                        </h2>
                        <p class="text-gray-500 mb-4">
                            <?php if ($searchQuery || $categoryId || $year): ?>
                                Intenta ajustar tus filtros de búsqueda o <a href="blog.php" class="text-purple-600 hover:text-purple-800 font-semibold">ver todos los artículos</a>.
                            <?php else: ?>
                                Pronto publicaremos contenido interesante.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php if ($searchQuery || $categoryId || $year): ?>
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-blue-800">
                                <i class="ph-bold ph-info text-blue-600"></i>
                                <strong>Resultados encontrados:</strong> <?php echo $totalPosts; ?> artículo<?php echo $totalPosts != 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="grid md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($posts as $post): ?>
                            <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                                <?php if ($post['featured_image']): ?>
                                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                                             class="w-full h-48 object-cover">
                                    </a>
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center">
                                        <i class="ph-bold ph-article text-6xl text-white opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <!-- Categories -->
                                    <?php if (!empty($post['categories'])): ?>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <?php foreach ($post['categories'] as $cat): ?>
                                                <a href="blog.php?category=<?php echo $cat['id']; ?>" 
                                                   class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full hover:bg-purple-200 transition-colors">
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Title -->
                                    <h2 class="text-xl font-bold mb-3">
                                        <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                           class="text-gray-800 hover:text-purple-600 transition-colors">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h2>
                                    
                                    <!-- Excerpt -->
                                    <?php if ($post['excerpt']): ?>
                                        <p class="text-gray-600 mb-4 line-clamp-3">
                                            <?php echo htmlspecialchars($post['excerpt']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Meta -->
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center space-x-4">
                                            <span class="flex items-center space-x-1">
                                                <i class="ph-bold ph-user"></i>
                                                <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                            </span>
                                            <span class="flex items-center space-x-1">
                                                <i class="ph-bold ph-calendar"></i>
                                                <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                                            </span>
                                        </div>
                                        <?php if ($post['views'] > 0): ?>
                                            <span class="flex items-center space-x-1">
                                                <i class="ph-bold ph-eye"></i>
                                                <span><?php echo number_format($post['views']); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Read More -->
                                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                       class="inline-block mt-4 text-purple-600 font-semibold hover:text-purple-800 transition-colors">
                                        Leer más <i class="ph-bold ph-arrow-right"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center items-center space-x-2 mt-8">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPaginationUrl($page - 1); ?>" 
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="ph-bold ph-arrow-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="<?php echo buildPaginationUrl($i); ?>" 
                                   class="px-4 py-2 <?php echo $i == $page ? 'bg-purple-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo buildPaginationUrl($page + 1); ?>" 
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Siguiente <i class="ph-bold ph-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- SIDEBAR -->
            <aside class="lg:col-span-1">
                <!-- Search (Mobile) -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 lg:hidden">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="ph-bold ph-magnifying-glass text-purple-600"></i> Buscar
                    </h3>
                    <form method="GET" action="blog.php">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>"
                               placeholder="Buscar artículos..." 
                               class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <?php if ($categoryId): ?>
                            <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <?php endif; ?>
                        <?php if ($year): ?>
                            <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <?php endif; ?>
                        <?php if ($month): ?>
                            <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <?php endif; ?>
                        <button type="submit" class="w-full mt-2 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            Buscar
                        </button>
                    </form>
                </div>
                
                <!-- Categories -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="ph-bold ph-folder text-purple-600"></i> Categorías
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo buildFilterUrl(['category' => null]); ?>" 
                               class="block text-gray-700 hover:text-purple-600 hover:bg-purple-50 px-3 py-2 rounded-lg transition-colors <?php echo !$categoryId ? 'bg-purple-50 text-purple-600 font-semibold' : ''; ?>">
                                Todas las categorías
                            </a>
                        </li>
                        <?php foreach ($allCategories as $cat): ?>
                            <li>
                                <a href="<?php echo buildFilterUrl(['category' => $cat['id']]); ?>" 
                                   class="block text-gray-700 hover:text-purple-600 hover:bg-purple-50 px-3 py-2 rounded-lg transition-colors <?php echo $categoryId == $cat['id'] ? 'bg-purple-50 text-purple-600 font-semibold' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Date Filter -->
                <?php if (!empty($availableDates)): ?>
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="ph-bold ph-calendar text-purple-600"></i> Filtrar por Fecha
                    </h3>
                    <form method="GET" action="blog.php" class="space-y-3">
                        <?php if ($categoryId): ?>
                            <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <?php endif; ?>
                        <?php if ($searchQuery): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Año</label>
                            <select name="year" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Todos los años</option>
                                <?php foreach (array_keys($availableDates) as $availableYear): ?>
                                    <option value="<?php echo $availableYear; ?>" <?php echo $year == $availableYear ? 'selected' : ''; ?>>
                                        <?php echo $availableYear; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($year && isset($availableDates[$year])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
                            <select name="month" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Todos los meses</option>
                                <?php 
                                $monthNames = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                foreach ($availableDates[$year] as $dateInfo): 
                                ?>
                                    <option value="<?php echo $dateInfo['month']; ?>" <?php echo $month == $dateInfo['month'] ? 'selected' : ''; ?>>
                                        <?php echo $monthNames[$dateInfo['month']]; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- CTA -->
                <div class="bg-gradient-primary rounded-xl shadow-md p-6 text-white text-center">
                    <i class="ph-bold ph-chat-circle text-4xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">¿Necesitas ayuda?</h3>
                    <p class="text-purple-100 mb-4">Agenda una consultoría gratuita</p>
                    <a href="index.php#contacto" 
                       class="inline-block bg-white text-purple-600 px-6 py-3 rounded-full font-semibold hover:shadow-lg transition-all transform hover:scale-105">
                        Contactar
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include 'footer.php'; ?>
