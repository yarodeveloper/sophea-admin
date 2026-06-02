# SOPHEA Blog - Guía de Configuración

## 📋 Resumen

Sistema completo de blog para SOPHEA con:
- ✅ Base de datos para posts y categorías
- ✅ Páginas públicas (listado y detalle)
- ✅ Panel de administración completo
- ✅ Editor WYSIWYG (CKEditor)
- ✅ SEO integrado
- ✅ Sistema de categorías
- ✅ Contador de vistas

---

## 🗂️ Archivos Creados

### 1. Base de Datos
- `database/blog_schema.sql` - Script SQL para crear las tablas del blog

### 2. Clases PHP
- `classes/Blog.php` - Clase para manejar todas las operaciones del blog (CRUD)

### 3. Páginas Públicas
- `blog.php` - Página de listado de artículos
- `post.php` - Página de detalle de artículo individual

### 4. Panel de Administración
- `admin_blog.php` - Panel completo para gestionar artículos

### 5. Otros
- `404.php` - Página de error 404
- `config.php` - Actualizado con enlace a Blog en el menú

---

## 🚀 Instalación

### Paso 1: Crear las Tablas en la Base de Datos

1. **Accede a MySQL/phpMyAdmin**

2. **Ejecuta el script SQL**:
   ```bash
   mysql -u root -p sophea_db < database/blog_schema.sql
   ```
   
   O importa `database/blog_schema.sql` vía phpMyAdmin

3. **Verifica que se crearon las tablas**:
   - `blog_posts` - Artículos del blog
   - `blog_categories` - Categorías
   - `blog_post_categories` - Relación muchos-a-muchos entre posts y categorías

### Paso 2: Verificar Configuración

Asegúrate de que `config_db.php` tenga la configuración correcta de la base de datos.

---

## 📝 Uso del Panel de Administración

### Acceder al Panel

1. Navega a: `http://tudominio.com/admin_blog.php`
2. Ingresa la contraseña (por defecto: `sophea2025` - **cámbiala en producción**)

### Crear un Nuevo Artículo

1. Haz clic en "Nuevo Artículo"
2. Completa el formulario:
   - **Título**: Título del artículo
   - **Slug**: URL amigable (se genera automáticamente)
   - **Resumen**: Breve descripción del artículo
   - **Contenido**: Contenido completo (usa el editor WYSIWYG)
   - **Imagen Destacada**: URL de la imagen
   - **Autor**: Nombre del autor
   - **Estado**: Borrador, Publicado o Archivado
   - **Fecha de Publicación**: Cuándo se publicará
   - **Categorías**: Selecciona una o más categorías
   - **SEO**: Meta título, descripción y keywords

3. Haz clic en "Crear Artículo"

### Editar un Artículo

1. En la lista de artículos, haz clic en el ícono de editar (lápiz)
2. Modifica los campos necesarios
3. Haz clic en "Actualizar Artículo"

### Eliminar un Artículo

1. En la lista de artículos, haz clic en el ícono de eliminar (papelera)
2. Confirma la eliminación

---

## 🌐 Páginas Públicas

### Listado de Artículos
- **URL**: `blog.php`
- Muestra todos los artículos publicados
- Filtrado por categoría
- Paginación
- Sidebar con categorías y CTA

### Detalle de Artículo
- **URL**: `post.php?slug=nombre-del-articulo`
- Muestra el artículo completo
- Botones de compartir en redes sociales
- Artículos relacionados
- Contador de vistas automático

---

## 🎨 Características

### Editor WYSIWYG
- Usa CKEditor 4
- Formato de texto (negrita, cursiva, etc.)
- Listas
- Enlaces
- Imágenes
- Código fuente (HTML)

### SEO
- Meta título personalizable
- Meta descripción
- Meta keywords
- URLs amigables (slugs)

### Categorías
- Sistema de categorías predefinidas:
  - Compliance COFEPRIS
  - Marketing Digital
  - Sector Salud
  - Casos de Éxito
  - Guías y Tutoriales

### Estadísticas
- Contador de vistas por artículo
- Filtrado por estado (publicado, borrador, archivado)

---

## 🔧 Personalización

### Agregar Más Categorías

Puedes agregar más categorías directamente en la base de datos:

```sql
INSERT INTO blog_categories (name, slug, description) 
VALUES ('Nueva Categoría', 'nueva-categoria', 'Descripción de la categoría');
```

### Cambiar el Editor

Si quieres usar otro editor WYSIWYG, modifica `admin_blog.php` y reemplaza CKEditor con tu editor preferido.

### Modificar el Diseño

Las páginas públicas (`blog.php` y `post.php`) usan Tailwind CSS. Puedes personalizar los estilos modificando las clases de Tailwind.

---

## 🔒 Seguridad

**IMPORTANTE**: El panel de administración usa autenticación básica. Para producción:

1. **Cambia la contraseña** en `admin_blog.php`:
   ```php
   $admin_password = 'tu_contraseña_segura';
   ```

2. **Implementa autenticación más robusta**:
   - Usa sesiones con tokens CSRF
   - Implementa rate limiting
   - Usa HTTPS
   - Considera usar un sistema de autenticación más completo

---

## 📊 Estructura de Base de Datos

### Tabla: blog_posts
- `id` - ID único
- `title` - Título del artículo
- `slug` - URL amigable
- `excerpt` - Resumen
- `content` - Contenido completo (HTML)
- `featured_image` - URL de imagen destacada
- `author_name` - Nombre del autor
- `status` - Estado (draft, published, archived)
- `published_at` - Fecha de publicación
- `views` - Contador de vistas
- `meta_title`, `meta_description`, `meta_keywords` - SEO
- `created_at`, `updated_at` - Timestamps

### Tabla: blog_categories
- `id` - ID único
- `name` - Nombre de la categoría
- `slug` - URL amigable
- `description` - Descripción

### Tabla: blog_post_categories
- `post_id` - ID del artículo
- `category_id` - ID de la categoría
- Relación muchos-a-muchos

---

## 🐛 Solución de Problemas

### Error: "Table doesn't exist"
- Ejecuta el script SQL `database/blog_schema.sql`

### Error: "Class Blog not found"
- Verifica que `classes/Blog.php` existe
- Verifica que `require_once 'classes/Blog.php'` está en los archivos que lo usan

### El editor no aparece
- Verifica que tienes conexión a internet (CKEditor se carga desde CDN)
- Revisa la consola del navegador para errores JavaScript

### Los artículos no se muestran
- Verifica que el estado del artículo sea "published"
- Verifica que `published_at` tenga una fecha válida

---

## 📚 Próximos Pasos

Posibles mejoras futuras:
- [ ] Sistema de comentarios
- [ ] Etiquetas (tags) además de categorías
- [ ] Búsqueda avanzada
- [ ] RSS Feed
- [ ] Exportar/Importar artículos
- [ ] Editor de imágenes integrado
- [ ] Vista previa antes de publicar
- [ ] Programación de publicaciones

---

## ✅ Checklist de Configuración

- [ ] Ejecutar `database/blog_schema.sql`
- [ ] Verificar que las tablas se crearon correctamente
- [ ] Cambiar la contraseña del admin en `admin_blog.php`
- [ ] Crear tu primer artículo de prueba
- [ ] Verificar que se muestra en `blog.php`
- [ ] Verificar que el detalle funciona en `post.php`
- [ ] Probar el filtrado por categorías
- [ ] Verificar que el menú de navegación muestra "Blog"

---

¡Listo! Tu blog está configurado y listo para usar. 🎉
