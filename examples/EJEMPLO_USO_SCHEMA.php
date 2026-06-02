<?php
/**
 * EJEMPLO: Uso de Schemas en Páginas Específicas
 * 
 * Este archivo muestra ejemplos de cómo usar los schemas
 * en diferentes tipos de páginas
 */

require_once '../config.php';
require_once '../includes/schema-helpers.php';

// ============================================
// EJEMPLO 1: Página de Blog Post Individual
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo: Blog Post con Schema</title>
    
    <?php
    // Simular datos de un post
    $post = [
        'title' => 'Cómo Implementar Compliance COFEPRIS en tu Consultorio',
        'excerpt' => 'Guía completa para implementar compliance regulatorio...',
        'content' => 'Contenido completo del artículo...',
        'featured_image' => 'https://www.sophea.com.mx/images/post-compliance.jpg',
        'created_at' => '2024-01-15 10:00:00',
        'updated_at' => '2024-01-20 14:30:00',
        'slug' => 'compliance-cofepris-consultorio',
        'category_name' => 'Compliance',
        'tags' => 'COFEPRIS, Compliance, Salud, Regulaciones'
    ];
    
    // Agregar schema de artículo
    add_article_schema($post);
    
    // Agregar breadcrumbs
    add_breadcrumbs_schema([
        ['name' => 'Inicio', 'url' => SCHEMA_URL . '/index.php'],
        ['name' => 'Blog', 'url' => SCHEMA_URL . '/blog.php'],
        ['name' => $post['title'], 'url' => SCHEMA_URL . '/post.php?slug=' . $post['slug']]
    ]);
    ?>
</head>
<body>
    <h1><?php echo $post['title']; ?></h1>
    <p><?php echo $post['excerpt']; ?></p>
</body>
</html>

<?php
// ============================================
// EJEMPLO 2: Página de Testimonios
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo: Testimonios con Schema</title>
    
    <?php
    // Simular testimonios
    $testimonials = [
        [
            'client_name' => 'Dr. Sergio M.',
            'testimonial_text' => 'SOPHEA me ayudó a implementar compliance COFEPRIS y aumenté mis citas en 287%.',
            'rating' => '5',
            'created_at' => '2024-01-10'
        ],
        [
            'client_name' => 'Valentina F.',
            'testimonial_text' => 'El chatbot con IA aumentó nuestras ventas nocturnas un 400%.',
            'rating' => '5',
            'created_at' => '2024-01-12'
        ]
    ];
    
    // Agregar schema de calificación agregada (una vez)
    add_aggregate_rating_schema(4.9, 150);
    
    // Agregar schema de reseña para cada testimonio
    foreach ($testimonials as $testimonial) {
        add_review_schema($testimonial);
    }
    ?>
</head>
<body>
    <h1>Testimonios de Clientes</h1>
    <?php foreach ($testimonials as $testimonial): ?>
        <div class="testimonial">
            <h3><?php echo $testimonial['client_name']; ?></h3>
            <p><?php echo $testimonial['testimonial_text']; ?></p>
            <p>Calificación: <?php echo $testimonial['rating']; ?>/5</p>
        </div>
    <?php endforeach; ?>
</body>
</html>

<?php
// ============================================
// EJEMPLO 3: Página de Servicios
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo: Servicios con Schema</title>
    
    <?php
    // Simular servicios
    $services = [
        [
            'name' => 'Compliance COFEPRIS',
            'description' => 'Auditoría completa y certificación de compliance regulatorio para el sector salud.',
            'type' => 'Marketing Digital'
        ],
        [
            'name' => 'Desarrollo Web',
            'description' => 'Sitios web profesionales con compliance regulatorio integrado.',
            'type' => 'Desarrollo'
        ]
    ];
    
    // Agregar schema para cada servicio
    foreach ($services as $service) {
        add_service_schema($service);
    }
    
    // Agregar ItemList para lista de servicios
    $items = [];
    foreach ($services as $index => $service) {
        $items[] = [
            'position' => $index + 1,
            'name' => $service['name'],
            'url' => SCHEMA_URL . '/servicios.php#' . strtolower(str_replace(' ', '-', $service['name'])),
            'description' => $service['description']
        ];
    }
    add_item_list_schema($items, 'Servicios de SOPHEA', 'Lista completa de servicios de marketing digital y compliance');
    ?>
</head>
<body>
    <h1>Nuestros Servicios</h1>
    <?php foreach ($services as $service): ?>
        <div class="service">
            <h2><?php echo $service['name']; ?></h2>
            <p><?php echo $service['description']; ?></p>
        </div>
    <?php endforeach; ?>
</body>
</html>

<?php
// ============================================
// EJEMPLO 4: Página con FAQ
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo: FAQ con Schema</title>
    
    <?php
    // Simular preguntas frecuentes
    $faqs = [
        [
            'question' => '¿Qué es el compliance COFEPRIS?',
            'answer' => 'El compliance COFEPRIS es el cumplimiento de las regulaciones establecidas por la Comisión Federal para la Protección contra Riesgos Sanitarios para la publicidad y promoción de servicios de salud.'
        ],
        [
            'question' => '¿Cuánto tiempo toma una auditoría de compliance?',
            'answer' => 'Una auditoría completa generalmente toma entre 2-4 semanas, dependiendo del tamaño del proyecto y la cantidad de contenido a revisar.'
        ],
        [
            'question' => '¿Ofrecen servicios para empresas fuera del sector salud?',
            'answer' => 'Sí, además de servicios especializados para el sector salud, ofrecemos desarrollo web, publicidad digital y automatización para empresas generales.'
        ]
    ];
    
    // Agregar schema de FAQ
    add_faq_schema($faqs);
    ?>
</head>
<body>
    <h1>Preguntas Frecuentes</h1>
    <?php foreach ($faqs as $faq): ?>
        <div class="faq-item">
            <h3><?php echo $faq['question']; ?></h3>
            <p><?php echo $faq['answer']; ?></p>
        </div>
    <?php endforeach; ?>
</body>
</html>

<?php
// ============================================
// EJEMPLO 5: Lista de Blog Posts
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo: Lista de Posts con Schema</title>
    
    <?php
    // Simular lista de posts
    $posts = [
        [
            'title' => 'Guía Completa de Compliance COFEPRIS',
            'slug' => 'guia-compliance-cofepris',
            'excerpt' => 'Aprende todo sobre compliance regulatorio...'
        ],
        [
            'title' => 'Cómo Aumentar Citas con Marketing Digital',
            'slug' => 'aumentar-citas-marketing-digital',
            'excerpt' => 'Estrategias probadas para aumentar citas...'
        ],
        [
            'title' => 'Automatización con IA para Consultorios',
            'slug' => 'automatizacion-ia-consultorios',
            'excerpt' => 'Implementa chatbots y automatización...'
        ]
    ];
    
    // Agregar ItemList schema
    $items = [];
    foreach ($posts as $index => $post) {
        $items[] = [
            'position' => $index + 1,
            'name' => $post['title'],
            'url' => SCHEMA_URL . '/post.php?slug=' . $post['slug'],
            'description' => $post['excerpt']
        ];
    }
    add_item_list_schema($items, 'Artículos del Blog', 'Lista de artículos sobre marketing digital y compliance regulatorio');
    ?>
</head>
<body>
    <h1>Blog</h1>
    <?php foreach ($posts as $post): ?>
        <article>
            <h2><a href="post.php?slug=<?php echo $post['slug']; ?>"><?php echo $post['title']; ?></a></h2>
            <p><?php echo $post['excerpt']; ?></p>
        </article>
    <?php endforeach; ?>
</body>
</html>

