# SOPHEA Website - PHP Modular Structure

## 📁 Estructura de Archivos

```
web_admin/
├── config.php              # Configuración global del sitio
├── header.php              # Encabezado y navegación
├── footer.php              # Pie de página y scripts
├── index.php               # Página principal (modular)
├── index.html              # Versión HTML estática (backup)
└── sections/               # Secciones modulares
    ├── servicios.php       # Sección de servicios
    ├── casos.php           # Casos de éxito
    └── contacto.php        # Formulario de contacto
```

---

## 🚀 Cómo Usar la Estructura Modular

### 1. **Configuración Inicial**

Edita `config.php` para actualizar la información del sitio:

```php
// Información de Contacto
define('CONTACT_PHONE', '+52 961 XXX XXXX');      // ← Actualiza con tu teléfono
define('CONTACT_WHATSAPP', '52961XXXXXXX');       // ← Sin + ni espacios
define('CONTACT_EMAIL', 'contacto@sophea.com.mx'); // ← Tu email
```

### 2. **Despliegue en Servidor PHP**

1. Sube todos los archivos a tu servidor web
2. Asegúrate de que PHP esté habilitado (versión 7.0+)
3. Accede a `index.php` en tu navegador

**Ejemplo**: `https://tudominio.com/index.php`

### 3. **Configurar URL Amigable (Opcional)**

Crea un archivo `.htaccess` para usar URLs limpias:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

Ahora puedes acceder con: `https://tudominio.com/`

---

## 📝 Archivos Principales

### `config.php`
**Propósito**: Almacena todas las configuraciones globales del sitio.

**Contiene**:
- Información del sitio (nombre, tagline, descripción)
- Datos de contacto (teléfono, WhatsApp, email, dirección)
- Configuración SEO (keywords, autor)
- Datos de geolocalización (GEO tags)
- Información del director
- Menú de navegación
- Funciones auxiliares

**Ventajas**:
- ✅ Cambios centralizados (actualiza una vez, aplica en todo el sitio)
- ✅ Fácil mantenimiento
- ✅ Consistencia de datos

---

### `header.php`
**Propósito**: Encabezado HTML, meta tags y navegación.

**Incluye**:
- DOCTYPE y etiquetas `<html>`, `<head>`
- Meta tags SEO (dinámicos desde `config.php`)
- Schema.org markup (JSON-LD)
- Estilos CSS (Tailwind CDN, Google Fonts, custom styles)
- Navegación fija con logo y menú
- Menú móvil responsive

**Uso**:
```php
<?php include 'header.php'; ?>
```

---

### `footer.php`
**Propósito**: Pie de página, botón de WhatsApp y scripts JavaScript.

**Incluye**:
- Footer con 4 columnas (Brand, Enlaces, Servicios, Contacto)
- Botón flotante de WhatsApp
- JavaScript para:
  - Toggle del menú móvil
  - Smooth scroll
  - Validación y envío del formulario
  - Efectos de scroll en el header
- Cierre de etiquetas `</body>` y `</html>`

**Uso**:
```php
<?php include 'footer.php'; ?>
```

---

### `index.php`
**Propósito**: Página principal que ensambla todos los componentes.

**Estructura**:
```php
<?php
require_once 'config.php';    // Carga configuración
include 'header.php';          // Incluye encabezado

// Secciones de contenido
// - Hero Section (inline)
// - Método Section (inline)

include 'sections/servicios.php';  // Incluye servicios
include 'sections/casos.php';      // Incluye casos de éxito
include 'sections/contacto.php';   // Incluye contacto

include 'footer.php';          // Incluye pie de página
?>
```

---

### Secciones Modulares (`sections/`)

#### `servicios.php`
- Muestra los 4 pilares de servicios duales
- Tarjetas con hover effects
- Diferenciación Salud vs. General

#### `casos.php`
- 2 casos de éxito (Salud y General/Retail)
- Métricas visuales
- Testimonios de clientes

#### `contacto.php`
- Formulario de captura de leads
- Perfil del director (Alejandro Montoya)
- Información de contacto

---

## 🔧 Cómo Agregar Nuevas Páginas

### Ejemplo: Crear página "Sobre Nosotros"

1. **Crea el archivo `sobre-nosotros.php`**:

```php
<?php
require_once 'config.php';
include 'header.php';
?>

<section class="pt-32 pb-20 px-4">
    <div class="container mx-auto max-w-6xl">
        <h1 class="text-4xl font-bold mb-8">Sobre Nosotros</h1>
        <p>Contenido de la página...</p>
    </div>
</section>

<?php include 'footer.php'; ?>
```

2. **Agrega el enlace al menú en `config.php`**:

```php
$nav_menu = [
    ['label' => 'Método', 'url' => '#metodo'],
    ['label' => 'Servicios', 'url' => '#servicios'],
    ['label' => 'Casos de Éxito', 'url' => '#casos'],
    ['label' => 'Sobre Nosotros', 'url' => 'sobre-nosotros.php'], // ← Nuevo
    ['label' => 'Contacto', 'url' => '#contacto']
];
```

---

## 🎨 Personalización

### Cambiar Colores del Tema

Edita los gradientes en `header.php` (sección `<style>`):

```css
.text-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Cambia los colores aquí */
}
```

### Modificar el Menú de Navegación

Edita el array `$nav_menu` en `config.php`:

```php
$nav_menu = [
    ['label' => 'Inicio', 'url' => '/'],
    ['label' => 'Servicios', 'url' => '#servicios'],
    // Agrega más elementos aquí
];
```

### Actualizar Información del Director

Edita las constantes en `config.php`:

```php
define('DIRECTOR_NAME', 'Tu Nombre');
define('DIRECTOR_TITLE', 'Tu Cargo');
define('DIRECTOR_BIO', 'Tu biografía...');
```

---

## ✅ Ventajas de la Estructura Modular

| Ventaja | Descripción |
|---------|-------------|
| **Mantenibilidad** | Cambios en header/footer se aplican a todas las páginas |
| **Reutilización** | Componentes se pueden usar en múltiples páginas |
| **Organización** | Código limpio y fácil de navegar |
| **Escalabilidad** | Fácil agregar nuevas páginas y secciones |
| **Consistencia** | Diseño y datos uniformes en todo el sitio |
| **Configuración Centralizada** | Un solo archivo para actualizar información global |

---

## 🔄 Comparación: HTML vs PHP

### Antes (HTML Estático)
```html
<!-- index.html -->
<header>
    <span>SOPHEA</span>
    <a href="tel:+52961XXXXXXX">+52 961 XXX XXXX</a>
</header>

<!-- sobre-nosotros.html -->
<header>
    <span>SOPHEA</span>
    <a href="tel:+52961XXXXXXX">+52 961 XXX XXXX</a> <!-- Duplicado -->
</header>
```

**Problema**: Si cambias el teléfono, debes actualizarlo en CADA archivo.

### Ahora (PHP Modular)
```php
<!-- config.php -->
define('CONTACT_PHONE', '+52 961 XXX XXXX');

<!-- header.php -->
<a href="tel:<?php echo CONTACT_PHONE; ?>"><?php echo CONTACT_PHONE; ?></a>

<!-- index.php -->
<?php include 'header.php'; ?>

<!-- sobre-nosotros.php -->
<?php include 'header.php'; ?>
```

**Solución**: Cambias el teléfono UNA VEZ en `config.php` y se actualiza en TODO el sitio.

---

## 🚨 Importante: Antes de Publicar

### Actualiza estos valores en `config.php`:

```php
// ⚠️ ACTUALIZAR ANTES DE PUBLICAR
define('CONTACT_PHONE', '+52 961 XXX XXXX');        // ← Tu teléfono real
define('CONTACT_WHATSAPP', '52961XXXXXXX');         // ← Tu WhatsApp (sin +)
define('CONTACT_EMAIL', 'contacto@sophea.com.mx');  // ← Tu email
define('SCHEMA_URL', 'https://www.sophea.com.mx');  // ← Tu dominio
define('SCHEMA_LOGO', 'https://www.sophea.com.mx/logo.png'); // ← URL del logo

// Desactiva el modo debug en producción
define('DEBUG_MODE', false); // ← Cambiar a false
```

---

## 📞 Soporte

Si necesitas ayuda con la implementación:

1. Revisa este README
2. Consulta los comentarios en cada archivo PHP
3. Verifica que PHP esté habilitado en tu servidor
4. Asegúrate de que todos los archivos estén en el directorio correcto

---

## 📄 Licencia

© 2025 SOPHEA. Todos los derechos reservados.

---

**¡Tu sitio web modular está listo para usar!** 🎉

Simplemente actualiza `config.php` con tu información y despliega en tu servidor PHP.
