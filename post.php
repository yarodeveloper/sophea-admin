<?php
/**
 * SOPHEA - Blog Post Detail Page
 * 
 * Displays a single blog post
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

// Get post slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Get post
$post = $blog->getPostBySlug($slug);

if (!$post || $post['status'] !== 'published') {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get related posts (same category)
$relatedPosts = [];
if (!empty($post['categories'])) {
    $categoryId = $post['categories'][0]['id'];
    $relatedPosts = $blog->getPublishedPosts(3, 0, $categoryId);
    // Remove current post from related
    $relatedPosts = array_filter($relatedPosts, function($p) use ($post) {
        return $p['id'] != $post['id'];
    });
    $relatedPosts = array_slice($relatedPosts, 0, 3);
}

// Set page title
$pageTitle = $post['meta_title'] ?? $post['title'];
?>
<?php include 'header.php'; ?>

<!-- POST HEADER -->
<section class="pt-32 pb-12 px-4 bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="container mx-auto max-w-4xl">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <a href="index.php" class="text-gray-600 hover:text-purple-600">Inicio</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="blog.php" class="text-gray-600 hover:text-purple-600">Blog</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars($post['title']); ?></span>
        </nav>
        
        <!-- Categories -->
        <?php if (!empty($post['categories'])): ?>
            <div class="flex flex-wrap gap-2 mb-4">
                <?php foreach ($post['categories'] as $cat): ?>
                    <a href="blog.php?category=<?php echo $cat['id']; ?>" 
                       class="text-sm bg-purple-100 text-purple-700 px-3 py-1 rounded-full hover:bg-purple-200 transition-colors">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-6 text-gray-800">
            <?php echo htmlspecialchars($post['title']); ?>
        </h1>
        
        <!-- Meta -->
        <div class="flex flex-wrap items-center gap-4 text-gray-600 mb-6">
            <span class="flex items-center space-x-2">
                <i class="ph-bold ph-user text-purple-600"></i>
                <span><?php echo htmlspecialchars($post['author_name']); ?></span>
            </span>
            <span class="flex items-center space-x-2">
                <i class="ph-bold ph-calendar text-purple-600"></i>
                <span><?php echo date('d \d\e F \d\e Y', strtotime($post['published_at'])); ?></span>
            </span>
            <?php if ($post['views'] > 0): ?>
                <span class="flex items-center space-x-2">
                    <i class="ph-bold ph-eye text-purple-600"></i>
                    <span><?php echo number_format($post['views']); ?> vistas</span>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Featured Image -->
        <?php if ($post['featured_image']): ?>
            <div class="mb-8 rounded-xl overflow-hidden shadow-lg">
                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                     class="w-full h-auto">
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- POST CONTENT -->
<article class="py-12 px-4">
    <div class="container mx-auto max-w-4xl">
        <div class="grid lg:grid-cols-4 gap-8">
            <!-- MAIN CONTENT -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-md p-8 md:p-12 prose prose-lg max-w-none">
                    <?php 
                    // Display content (assuming it's stored as HTML)
                    echo $post['content']; 
                    ?>
                </div>
                
                <!-- Share Buttons -->
                <div class="mt-8 bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Compartir artículo</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="ph-bold ph-facebook-logo"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($post['title']); ?>" 
                           target="_blank"
                           class="flex items-center space-x-2 bg-sky-500 text-white px-4 py-2 rounded-lg hover:bg-sky-600 transition-colors">
                            <i class="ph-bold ph-twitter-logo"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' - ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="flex items-center space-x-2 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                            <i class="ph-bold ph-whatsapp-logo"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="flex items-center space-x-2 bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">
                            <i class="ph-bold ph-linkedin-logo"></i>
                            <span>LinkedIn</span>
                        </a>
                    </div>
                </div>
                
                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <div class="mt-12">
                        <h2 class="text-2xl font-bold mb-6 text-gray-800">Artículos relacionados</h2>
                        <div class="grid md:grid-cols-3 gap-6">
                            <?php foreach ($relatedPosts as $related): ?>
                                <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                                    <?php if ($related['featured_image']): ?>
                                        <a href="post.php?slug=<?php echo htmlspecialchars($related['slug']); ?>">
                                            <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                 class="w-full h-32 object-cover">
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="p-4">
                                        <h3 class="font-bold mb-2">
                                            <a href="post.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" 
                                               class="text-gray-800 hover:text-purple-600 transition-colors">
                                                <?php echo htmlspecialchars($related['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('d/m/Y', strtotime($related['published_at'])); ?>
                                        </p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SIDEBAR -->
            <aside class="lg:col-span-1">
                <!-- CTA -->
                <div class="bg-gradient-primary rounded-xl shadow-md p-6 text-white text-center mb-6">
                    <i class="ph-bold ph-chat-circle text-4xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">¿Necesitas ayuda?</h3>
                    <p class="text-purple-100 mb-4 text-sm">Agenda una consultoría gratuita</p>
                    <a href="index.php#contacto" 
                       class="inline-block bg-white text-purple-600 px-6 py-3 rounded-full font-semibold hover:shadow-lg transition-all transform hover:scale-105">
                        Contactar
                    </a>
                </div>
                
                <!-- Back to Blog -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <a href="blog.php" 
                       class="flex items-center space-x-2 text-purple-600 hover:text-purple-800 transition-colors">
                        <i class="ph-bold ph-arrow-left"></i>
                        <span class="font-semibold">Volver al blog</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</article>

<style>
    /* Prose styles for blog content */
    .prose {
        color: #374151;
    }
    .prose h2 {
        font-size: 1.875rem;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #1f2937;
    }
    .prose h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #374151;
    }
    .prose p {
        margin-bottom: 1.25rem;
        line-height: 1.75;
    }
    .prose ul, .prose ol {
        margin-bottom: 1.25rem;
        padding-left: 1.625rem;
    }
    .prose li {
        margin-bottom: 0.5rem;
    }
    .prose a {
        color: #9333ea;
        text-decoration: underline;
    }
    .prose a:hover {
        color: #7e22ce;
    }
    .prose img {
        border-radius: 0.5rem;
        margin: 1.5rem 0;
    }
    .prose blockquote {
        border-left: 4px solid #9333ea;
        padding-left: 1rem;
        margin: 1.5rem 0;
        font-style: italic;
        color: #6b7280;
    }
</style>

<?php include 'footer.php'; ?>
