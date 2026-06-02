# SOPHEA - Sitemap.xml Automático

## 📋 Resumen

Sistema de sitemap.xml automático para SEO que incluye:
- ✅ Páginas estáticas del sitio
- ✅ Posts del blog (dinámicos)
- ✅ Prioridades y frecuencias de actualización
- ✅ Fechas de última modificación
- ✅ Generación automática en tiempo real

---

## 🗂️ Archivos Creados

### 1. Generador de Sitemap
- `sitemap.php` - Genera el sitemap.xml dinámicamente

### 2. Configuración
- `.htaccess` - Actualizado para redirigir sitemap.xml a sitemap.php
- `robots.txt` - Creado con instrucciones para crawlers
- `header.php` - Actualizado con referencia al sitemap

---

## 🎯 Características

### URLs Incluidas

1. **Página Principal** (`index.php`)
   - Prioridad: 1.0 (máxima)
   - Frecuencia: Semanal
   - Última modificación: Actual

2. **Página de Servicios** (`servicios.php`)
   - Prioridad: 0.9
   - Frecuencia: Mensual
   - Última modificación: Actual

3. **Listado del Blog** (`blog.php`)
   - Prioridad: 0.9
   - Frecuencia: Diaria
   - Última modificación: Actual

4. **Posts del Blog** (`post.php?slug=...`)
   - Prioridad: 0.8-0.9 (dinámica según fecha de publicación)
   - Frecuencia: Semanal (posts recientes) o Mensual (posts antiguos)
   - Última modificación: Fecha de actualización del post

### Prioridades Dinámicas

Los posts del blog tienen prioridades ajustadas según su fecha de publicación:
- **Posts publicados en los últimos 30 días**: Prioridad 0.9, frecuencia semanal
- **Posts publicados en los últimos 90 días**: Prioridad 0.85, frecuencia mensual
- **Posts más antiguos**: Prioridad 0.8, frecuencia mensual

---

## 🔧 Configuración

### 1. URL Base del Sitemap

El sitemap detecta automáticamente la URL base. Si necesitas ajustarla:

1. Edita `config.php` y actualiza `SCHEMA_URL`:
   ```php
   define('SCHEMA_URL', 'https://www.tudominio.com');
   ```

2. O el sitemap detectará automáticamente desde `$_SERVER['HTTP_HOST']`

### 2. Actualizar robots.txt

Edita `robots.txt` y actualiza la URL del sitemap:
```
Sitemap: https://www.tudominio.com/sitemap.xml
```

### 3. Verificar el Sitemap

Accede a: `https://www.tudominio.com/sitemap.xml`

Deberías ver un XML válido con todas las URLs del sitio.

---

## 📊 Validación

### Google Search Console

1. Accede a [Google Search Console](https://search.google.com/search-console)
2. Agrega tu propiedad (sitio web)
3. Ve a "Sitemaps" en el menú lateral
4. Ingresa: `sitemap.xml`
5. Haz clic en "Enviar"

### Validadores Online

Puedes validar tu sitemap en:
- [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)
- [Sitemap Validator](https://www.sitemaps.org/protocol.html)

---

## 🔄 Actualización Automática

El sitemap se genera automáticamente cada vez que se accede a `sitemap.xml`. Esto significa:

- ✅ Nuevos posts se agregan automáticamente
- ✅ Posts actualizados reflejan su nueva fecha de modificación
- ✅ No necesitas regenerar manualmente el sitemap

---

## 📝 Estructura del XML

El sitemap sigue el estándar XML Sitemap Protocol:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://www.tudominio.com/index.php</loc>
    <lastmod>2025-01-15</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <!-- Más URLs... -->
</urlset>
```

---

## 🚀 Mejoras Futuras (Opcionales)

### Incluir Categorías del Blog

Si deseas incluir las páginas de categorías en el sitemap, descomenta esta sección en `sitemap.php`:

```php
// 5. Blog categories
$categories = $blog->getAllCategories();
foreach ($categories as $category) {
    outputUrl('blog.php?category=' . $category['id'], '0.7', 'monthly', date('Y-m-d'));
}
```

### Sitemap Index (para sitios grandes)

Si tienes más de 50,000 URLs, considera crear un sitemap index que apunte a múltiples sitemaps.

### Imágenes en el Sitemap

Puedes agregar imágenes de los posts usando el protocolo de sitemap de imágenes de Google.

---

## ✅ Checklist de Implementación

- [x] Archivo `sitemap.php` creado
- [x] `.htaccess` actualizado para redirigir sitemap.xml
- [x] `robots.txt` creado con referencia al sitemap
- [x] `header.php` actualizado con link al sitemap
- [ ] Verificar que `sitemap.xml` se genera correctamente
- [ ] Enviar sitemap a Google Search Console
- [ ] Enviar sitemap a Bing Webmaster Tools
- [ ] Verificar que los crawlers pueden acceder al sitemap

---

## 🔍 Troubleshooting

### El sitemap no se genera

1. Verifica que `sitemap.php` existe y tiene permisos de lectura
2. Verifica que `.htaccess` tiene la regla de redirección
3. Verifica que PHP está habilitado en el servidor

### URLs incorrectas en el sitemap

1. Actualiza `SCHEMA_URL` en `config.php`
2. O verifica que la detección automática funciona correctamente

### Posts no aparecen en el sitemap

1. Verifica que los posts tienen estado "published"
2. Verifica que los posts tienen `published_at` establecido
3. Verifica la conexión a la base de datos

---

## 📚 Referencias

- [Sitemap Protocol](https://www.sitemaps.org/protocol.html)
- [Google Search Console](https://search.google.com/search-console)
- [Bing Webmaster Tools](https://www.bing.com/webmasters)
