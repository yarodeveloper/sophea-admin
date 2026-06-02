# Importar Posts al Blog SOPHEA

## 📋 Resumen

Este documento explica cómo importar los posts del blog desde los archivos HTML proporcionados.

## 🚀 Pasos para Importar

### 1. Verificar Base de Datos

Asegúrate de que las tablas del blog estén creadas:

```bash
mysql -u root -p sophea_db < database/blog_schema.sql
```

O importa `database/blog_schema.sql` vía phpMyAdmin.

### 2. Ejecutar el Script de Importación

Navega a:
```
http://tudominio.com/create_blog_posts.php
```

El script:
- ✅ Creará 4 posts optimizados para SEO
- ✅ Asignará las categorías correctas
- ✅ Configurará meta tags optimizados
- ✅ Publicará los posts automáticamente

## 📝 Posts que se Crearán

1. **10 Pasos para Digitalizar tu Negocio en 2025**
   - Categoría: Marketing Digital
   - Contenido optimizado con títulos H2/H3 mejorados

2. **Agencia SEO en Tuxtla Gutiérrez**
   - Categoría: Marketing Digital
   - Enfoque en SEO local y Google Maps

3. **¿Cuánto Cuesta una Página Web en Tuxtla?**
   - Categoría: Guías y Tutoriales
   - Guía completa de precios y ROI

4. **Marketing para Restaurantes en Tuxtla**
   - Categoría: Marketing Digital
   - Estrategias de redes sociales para restaurantes

## ✨ Mejoras Aplicadas

### SEO Optimizado
- ✅ Títulos mejorados con palabras clave
- ✅ Meta descriptions optimizadas
- ✅ Meta keywords relevantes
- ✅ Estructura H2/H3 para mejor indexación

### Contenido Mejorado
- ✅ Subtítulos más claros y descriptivos
- ✅ Contenido estructurado y legible
- ✅ Llamadas a la acción integradas
- ✅ Enfoque en beneficios para el lector

## 🔧 Personalización

Si necesitas modificar el contenido:

1. Edita los archivos en `blog_content/`:
   - `post1_10_pasos.html`
   - `post2_seo.html`
   - `post3_cuanto_web.html`
   - `post4_restaurantes.html`

2. Ejecuta nuevamente `create_blog_posts.php`

**Nota:** El script verificará si los posts ya existen antes de crearlos.

## 📊 Verificar Resultados

Después de ejecutar el script:

1. Visita `blog.php` para ver los posts publicados
2. Accede a `admin_blog.php` para gestionar los posts
3. Verifica que las categorías estén asignadas correctamente

## 🐛 Solución de Problemas

### Error: "Class Blog not found"
- Verifica que `classes/Blog.php` existe
- Verifica que `config_db.php` está configurado

### Error: "Table doesn't exist"
- Ejecuta `database/blog_schema.sql` primero

### Los posts no aparecen
- Verifica que el estado sea "published"
- Verifica que `published_at` tenga una fecha válida

---

¡Listo! Tus posts están optimizados y listos para atraer clientes. 🎉
