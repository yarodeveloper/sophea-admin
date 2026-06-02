# 📊 Documentación: Schema SEO y Rich Snippets - SOPHEA

## 📋 Introducción

Este documento explica el sistema de Schema.org implementado en SOPHEA para generar **rich snippets** (fragmentos enriquecidos) en los resultados de búsqueda de Google y otros motores de búsqueda.

## 🎯 ¿Qué son los Rich Snippets?

Los **rich snippets** son resultados de búsqueda mejorados que muestran información adicional como:
- ⭐ Calificaciones y reseñas
- 📍 Información de contacto y ubicación
- 🕐 Horarios de atención
- 📝 Descripciones mejoradas
- 🏷️ Precios y ofertas
- 👤 Información del autor
- 📚 Breadcrumbs (migas de pan)

## 🏗️ Arquitectura del Sistema

### Archivos Principales

```
sopheaadmin/
├── classes/
│   └── SchemaGenerator.php      # Clase principal para generar schemas
├── includes/
│   └── schema-helpers.php        # Funciones helper para uso fácil
└── header.php                    # Incluye schemas globales
```

### Clase SchemaGenerator

La clase `SchemaGenerator` contiene métodos estáticos para generar diferentes tipos de schemas:

- `organization()` - Schema de organización
- `localBusiness()` - Schema de negocio local
- `professionalService()` - Schema de servicio profesional
- `person()` - Schema de persona (director, autor, etc.)
- `service()` - Schema de servicio individual
- `article()` - Schema de artículo/blog
- `review()` - Schema de reseña
- `aggregateRating()` - Schema de calificación agregada
- `faq()` - Schema de preguntas frecuentes
- `breadcrumbs()` - Schema de breadcrumbs
- `website()` - Schema de sitio web con búsqueda
- `itemList()` - Schema de lista de items
- `howTo()` - Schema de tutorial/paso a paso

## 📝 Schemas Implementados Globalmente

### En `header.php`

Los siguientes schemas se incluyen automáticamente en todas las páginas:

1. **Organization** - Información de la organización SOPHEA
2. **LocalBusiness** - Información del negocio local
3. **ProfessionalService** - Servicios profesionales ofrecidos
4. **Person** - Información del director (Alejandro Montoya)
5. **WebSite** - Información del sitio web con búsqueda

### Ejemplo de Output

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "SOPHEA",
  "url": "https://www.sophea.com.mx",
  "logo": "https://www.sophea.com.mx/logo.png",
  "description": "Especialistas en compliance COFEPRIS...",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+52 961 693 3158",
    "contactType": "customer service"
  }
}
```

## 🎨 Uso en Páginas Específicas

### 1. Página de Blog (blog.php)

Para agregar schema de artículo en posts individuales:

```php
<?php
require_once 'includes/schema-helpers.php';

// En post.php o dentro del loop de posts
add_article_schema([
    'title' => $post['title'],
    'excerpt' => $post['excerpt'],
    'content' => $post['content'],
    'featured_image' => $post['featured_image'],
    'created_at' => $post['created_at'],
    'updated_at' => $post['updated_at'],
    'slug' => $post['slug'],
    'category_name' => $post['category_name'],
    'tags' => $post['tags']
]);
?>
```

### 2. Página de Servicios (servicios.php)

Para agregar schema de servicios:

```php
<?php
require_once 'includes/schema-helpers.php';

// Para cada servicio
add_service_schema([
    'name' => 'Compliance COFEPRIS',
    'description' => 'Auditoría y certificación de compliance regulatorio...',
    'type' => 'Marketing Digital'
]);
?>
```

### 3. Página de Testimonios (testimonials.php)

Para agregar schemas de reseñas:

```php
<?php
require_once 'includes/schema-helpers.php';

// Para cada testimonio
add_review_schema([
    'client_name' => $testimonial['client_name'],
    'testimonial_text' => $testimonial['testimonial_text'],
    'rating' => $testimonial['rating'],
    'created_at' => $testimonial['created_at']
]);

// Calificación agregada (una vez por página)
add_aggregate_rating_schema(4.8, 150);
?>
```

### 4. Breadcrumbs (Navegación)

Para agregar breadcrumbs en páginas internas:

```php
<?php
require_once 'includes/schema-helpers.php';

add_breadcrumbs_schema([
    ['name' => 'Inicio', 'url' => SCHEMA_URL . '/index.php'],
    ['name' => 'Blog', 'url' => SCHEMA_URL . '/blog.php'],
    ['name' => $post['title'], 'url' => SCHEMA_URL . '/post.php?slug=' . $post['slug']]
]);
?>
```

### 5. FAQ (Preguntas Frecuentes)

Si tienes una sección de preguntas frecuentes:

```php
<?php
require_once 'includes/schema-helpers.php';

add_faq_schema([
    [
        'question' => '¿Qué es el compliance COFEPRIS?',
        'answer' => 'El compliance COFEPRIS es el cumplimiento de las regulaciones...'
    ],
    [
        'question' => '¿Cuánto tiempo toma una auditoría?',
        'answer' => 'Una auditoría completa generalmente toma entre 2-4 semanas...'
    ]
]);
?>
```

### 6. Lista de Items (Blog Listing, Servicios, etc.)

Para listas de artículos o servicios:

```php
<?php
require_once 'includes/schema-helpers.php';

$items = [];
foreach ($posts as $index => $post) {
    $items[] = [
        'position' => $index + 1,
        'name' => $post['title'],
        'url' => SCHEMA_URL . '/post.php?slug=' . $post['slug'],
        'description' => $post['excerpt']
    ];
}

add_item_list_schema($items, 'Artículos del Blog', 'Lista de artículos sobre marketing digital y compliance');
?>
```

## 🔍 Meta Tags Mejorados

### Open Graph (Facebook, LinkedIn)

El `header.php` ahora incluye meta tags Open Graph completos:

```html
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.sophea.com.mx/index.php">
<meta property="og:title" content="SOPHEA - Marketing Digital...">
<meta property="og:description" content="...">
<meta property="og:image" content="https://www.sophea.com.mx/logo.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="es_MX">
<meta property="og:site_name" content="SOPHEA">
```

### Twitter Cards

Meta tags para Twitter Cards:

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="...">
<meta name="twitter:description" content="...">
<meta name="twitter:image" content="...">
```

## ✅ Validación de Schemas

### Herramientas de Validación

1. **Google Rich Results Test**
   - URL: https://search.google.com/test/rich-results
   - Ingresa la URL de tu página
   - Verifica que los schemas sean válidos

2. **Schema.org Validator**
   - URL: https://validator.schema.org/
   - Pega el código JSON-LD
   - Verifica la estructura

3. **Google Search Console**
   - Monitorea rich snippets en "Mejoras"
   - Revisa errores de schema

### Comandos Útiles

```bash
# Verificar schemas en una página
curl -s "https://www.sophea.com.mx/index.php" | grep -o '<script type="application/ld+json">.*</script>'
```

## 📊 Tipos de Rich Snippets Disponibles

### 1. Organization
- Muestra información de la empresa
- Incluye logo, contacto, redes sociales

### 2. LocalBusiness
- Muestra información de negocio local
- Incluye ubicación, horarios, calificaciones

### 3. ProfessionalService
- Muestra servicios profesionales
- Incluye catálogo de servicios

### 4. Person
- Muestra información de personas
- Útil para autores, directores, fundadores

### 5. Article/BlogPosting
- Muestra artículos de blog
- Incluye autor, fecha, categoría

### 6. Review
- Muestra reseñas y testimonios
- Incluye calificación y autor

### 7. FAQPage
- Muestra preguntas frecuentes
- Aparece como acordeón en resultados

### 8. BreadcrumbList
- Muestra navegación (migas de pan)
- Ayuda a entender la estructura del sitio

### 9. WebSite
- Información del sitio web
- Incluye búsqueda integrada

## 🚀 Mejores Prácticas

### 1. Usar Schemas Relevantes
- Solo agrega schemas que realmente aplican
- No dupliques información

### 2. Mantener Datos Actualizados
- Actualiza información de contacto
- Mantén horarios actualizados
- Actualiza calificaciones regularmente

### 3. Validar Regularmente
- Usa Google Rich Results Test
- Revisa Search Console
- Corrige errores inmediatamente

### 4. Optimizar Imágenes
- Usa imágenes de al menos 1200x630px para Open Graph
- Optimiza el tamaño de archivo
- Usa formatos modernos (WebP)

### 5. URLs Canónicas
- Siempre incluye `<link rel="canonical">`
- Evita contenido duplicado

## 📈 Impacto en SEO

### Beneficios

1. **Mayor CTR** - Rich snippets atraen más clics
2. **Mejor Posicionamiento** - Google favorece sitios con schema
3. **Más Información Visible** - Los usuarios ven más antes de hacer clic
4. **Confianza** - Las calificaciones y reseñas aumentan la confianza

### Métricas a Monitorear

- CTR en resultados de búsqueda
- Impresiones de rich snippets
- Errores en Search Console
- Posiciones en resultados

## 🔧 Troubleshooting

### Schema No Aparece en Google

1. **Verifica que el schema sea válido**
   - Usa Google Rich Results Test
   - Corrige errores de sintaxis

2. **Espera a que Google indexe**
   - Puede tardar días o semanas
   - Usa Google Search Console para solicitar indexación

3. **Verifica que el contenido coincida**
   - El schema debe reflejar el contenido real
   - No uses información falsa

### Errores Comunes

**Error: "Missing required field"**
- Solución: Agrega todos los campos requeridos según el tipo de schema

**Error: "Invalid JSON"**
- Solución: Valida el JSON con un validador JSON

**Error: "Duplicate schema"**
- Solución: No dupliques el mismo tipo de schema en una página

## 📚 Recursos Adicionales

- **Schema.org Documentation**: https://schema.org/
- **Google Rich Results Guide**: https://developers.google.com/search/docs/appearance/structured-data
- **Open Graph Protocol**: https://ogp.me/
- **Twitter Cards**: https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards

## 🎯 Próximos Pasos

1. ✅ Schemas globales implementados
2. ✅ Meta tags Open Graph y Twitter Cards
3. ⏳ Agregar schemas en páginas específicas (blog, servicios, testimonios)
4. ⏳ Validar con Google Rich Results Test
5. ⏳ Monitorear en Google Search Console

---

**Última actualización**: 2024
**Versión**: 1.0

