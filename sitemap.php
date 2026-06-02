<?php
/**
 * SOPHEA - Dynamic Sitemap Generator
 * 
 * Generates sitemap.xml automatically for SEO
 * Includes all static pages and blog posts
 */

// Load configuration
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Blog.php';

// Get base URL from config or detect automatically
$baseUrl = rtrim(SCHEMA_URL, '/');

// Auto-detect base URL if SCHEMA_URL is not set or is default
if (empty($baseUrl) || $baseUrl === 'https://www.sophea.com.mx' || $baseUrl === 'https://sopheamkt.com') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove /sopheaadmin if in subdirectory (for development)
    $path = str_replace('/sopheaadmin', '', $path);
    
    $baseUrl = $protocol . '://' . $host . ($path !== '/' ? rtrim($path, '/') : '');
}

// Initialize blog and testimonials
$blog = new Blog();
require_once __DIR__ . '/classes/Testimonials.php';
$testimonials = new Testimonials();

// Get all published blog posts
$allPosts = $blog->getPublishedPosts(1000, 0); // Get up to 1000 posts

// Get all published testimonials
$allTestimonials = $testimonials->getPublishedTestimonials(1000, 0);

// Set content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

// Helper function to output URL entry
function outputUrl($loc, $priority = '0.8', $changefreq = 'monthly', $lastmod = null) {
    global $baseUrl;
    
    $url = $baseUrl . '/' . ltrim($loc, '/');
    $lastmod = $lastmod ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d');
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . $changefreq . "</changefreq>\n";
    echo "    <priority>" . $priority . "</priority>\n";
    echo "  </url>\n";
}

// 1. Homepage (highest priority)
outputUrl('index.php', '1.0', 'weekly', date('Y-m-d'));

// 2. Services page
outputUrl('servicios.php', '0.9', 'monthly', date('Y-m-d'));

// 3. Blog listing page
outputUrl('blog.php', '0.9', 'daily', date('Y-m-d'));

// 4. Testimonials listing page
outputUrl('testimonials.php', '0.8', 'monthly', date('Y-m-d'));

// 5. Tools (Free tools for SEO)
outputUrl('generador-qr.php', '0.9', 'monthly', date('Y-m-d'));
outputUrl('generador-link-whatsapp.php', '0.9', 'monthly', date('Y-m-d'));

// 6. Legal pages
outputUrl('aviso_privacidad.php', '0.5', 'yearly', date('Y-m-d'));
outputUrl('politica_cookies.php', '0.5', 'yearly', date('Y-m-d'));

// 6. Blog posts (dynamic)
foreach ($allPosts as $post) {
    $lastmod = $post['updated_at'] ? $post['updated_at'] : $post['published_at'];
    $priority = '0.8'; // Blog posts have good priority
    
    // Posts published in the last 30 days get higher priority
    if ($post['published_at']) {
        $publishedDate = strtotime($post['published_at']);
        $daysSincePublished = (time() - $publishedDate) / (60 * 60 * 24);
        
        if ($daysSincePublished < 30) {
            $priority = '0.9';
            $changefreq = 'weekly';
        } elseif ($daysSincePublished < 90) {
            $priority = '0.85';
            $changefreq = 'monthly';
        } else {
            $changefreq = 'monthly';
        }
    } else {
        $changefreq = 'monthly';
    }
    
    outputUrl('post.php?slug=' . urlencode($post['slug']), $priority, $changefreq, $lastmod);
}

// 7. Testimonials (case studies)
foreach ($allTestimonials as $testimonial) {
    $lastmod = $testimonial['updated_at'] ? $testimonial['updated_at'] : $testimonial['published_at'];
    $priority = $testimonial['featured'] ? '0.85' : '0.75';
    $changefreq = 'monthly';
    
    outputUrl('testimonial.php?slug=' . urlencode($testimonial['slug']), $priority, $changefreq, $lastmod);
}

// 8. Blog categories (optional - uncomment if you want category pages in sitemap)
/*
$categories = $blog->getAllCategories();
foreach ($categories as $category) {
    outputUrl('blog.php?category=' . $category['id'], '0.7', 'monthly', date('Y-m-d'));
}
*/

// Close XML
echo '</urlset>';
