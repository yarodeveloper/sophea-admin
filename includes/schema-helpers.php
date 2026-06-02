<?php
/**
 * SOPHEA - Schema Helper Functions
 * 
 * Helper functions to add page-specific schemas
 */

// Prevent direct access
if (!defined('SITE_NAME')) {
    die('Direct access not allowed');
}

/**
 * Add Article Schema for blog posts
 */
function add_article_schema($post) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::article([
        'title' => $post['title'],
        'description' => $post['excerpt'] ?? substr(strip_tags($post['content']), 0, 160),
        'image' => $post['featured_image'] ?? SCHEMA_LOGO,
        'date_published' => date('c', strtotime($post['created_at'])),
        'date_modified' => date('c', strtotime($post['updated_at'] ?? $post['created_at'])),
        'author_name' => SEO_AUTHOR,
        'author_url' => SCHEMA_URL,
        'publisher_name' => SITE_NAME,
        'publisher_logo' => SCHEMA_LOGO,
        'url' => SCHEMA_URL . '/post.php?slug=' . $post['slug'],
        'category' => $post['category_name'] ?? '',
        'keywords' => $post['tags'] ?? SEO_KEYWORDS
    ]);
    
    echo SchemaGenerator::output($schema);
}

/**
 * Add Breadcrumbs Schema
 */
function add_breadcrumbs_schema($items) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::breadcrumbs($items);
    echo SchemaGenerator::output($schema);
}

/**
 * Add FAQ Schema
 */
function add_faq_schema($faqs) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::faq($faqs);
    echo SchemaGenerator::output($schema);
}

/**
 * Add Service Schema
 */
function add_service_schema($service) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::service([
        'name' => $service['name'],
        'description' => $service['description'],
        'provider_name' => SITE_NAME,
        'area_served' => 'Chiapas',
        'service_type' => $service['type'] ?? 'Marketing Digital'
    ]);
    
    echo SchemaGenerator::output($schema);
}

/**
 * Add Review Schema
 */
function add_review_schema($review) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::review([
        'organization_name' => SITE_NAME,
        'author_name' => $review['client_name'],
        'review_text' => $review['testimonial_text'],
        'rating' => $review['rating'] ?? '5',
        'date_published' => date('c', strtotime($review['created_at'] ?? 'now'))
    ]);
    
    echo SchemaGenerator::output($schema);
}

/**
 * Add Aggregate Rating Schema
 */
function add_aggregate_rating_schema($rating, $reviewCount) {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::aggregateRating([
        'name' => SITE_NAME,
        'rating' => $rating,
        'review_count' => $reviewCount
    ]);
    
    echo SchemaGenerator::output($schema);
}

/**
 * Add ItemList Schema (for blog listings, services, etc.)
 */
function add_item_list_schema($items, $name, $description = '') {
    require_once __DIR__ . '/../classes/SchemaGenerator.php';
    
    $schema = SchemaGenerator::itemList([
        'name' => $name,
        'description' => $description,
        'items' => $items,
        'item_type' => 'ListItem'
    ]);
    
    echo SchemaGenerator::output($schema);
}

