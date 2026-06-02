# SOPHEA - Páginas de Error Personalizadas

## 📋 Resumen

Sistema de páginas de error personalizadas para SOPHEA con:
- ✅ Página 404 (Not Found) con imagen y opciones de navegación
- ✅ Página 405 (Method Not Allowed) con imagen y opciones de navegación
- ✅ Menú de navegación integrado
- ✅ Botones de acción (Volver al inicio, Contactar por WhatsApp)
- ✅ Diseño responsive y atractivo

---

## 🗂️ Archivos Creados

### 1. Páginas de Error
- `404.php` - Página de error 404 (Página no encontrada)
- `405.php` - Página de error 405 (Método no permitido)

### 2. Configuración
- `.htaccess` - Actualizado con redirecciones de error personalizadas

---

## 🎨 Características

### Página 404
- **Imagen personalizada**: Muestra la imagen proporcionada del error 404
- **Mensaje claro**: "No pudimos encontrar lo que estás buscando"
- **Menú de navegación**: Acceso rápido a:
  - Inicio
  - Servicios
  - Blog
  - Contacto
- **Botones de acción**:
  - Volver al Inicio
  - Contactar por WhatsApp
- **Tip útil**: Sugerencia para usar el menú de navegación

### Página 405
- **Mensaje específico**: Explica el error de método no permitido
- **Misma estructura**: Menú de navegación y botones de acción
- **Información educativa**: Explica qué significa el error y cómo solucionarlo
- **Diseño diferenciado**: Colores rojo/naranja para diferenciarlo del 404

---

## 🔧 Configuración

### Archivo .htaccess

El archivo `.htaccess` ha sido actualizado para redirigir automáticamente los errores:

```apache
ErrorDocument 404 /sopheaadmin/404.php
ErrorDocument 405 /sopheaadmin/405.php
ErrorDocument 403 /sopheaadmin/404.php
ErrorDocument 500 /sopheaadmin/404.php
```

**Nota**: Ajusta la ruta `/sopheaadmin/` según la ubicación de tu proyecto en el servidor.

### Imagen del Error

La imagen se encuentra en:
```
assets/c__Users_dell_AppData_Roaming_Cursor_User_workspaceStorage_ae2598bed9b4aa796a0b14e26c25d266_images_img_404-9f9923dc-a3ed-4a79-935e-9c0635a42e85.png
```

Si deseas mover la imagen a una ubicación más simple:
1. Crea una carpeta `images/` en la raíz del proyecto
2. Copia la imagen allí con un nombre más simple (ej: `error-404.png`)
3. Actualiza la ruta en `404.php` y `405.php`

---

## 📱 Diseño Responsive

Ambas páginas están completamente optimizadas para:
- **Desktop**: Diseño de dos columnas (imagen a la izquierda, contenido a la derecha)
- **Tablet**: Diseño adaptativo con mejor uso del espacio
- **Mobile**: Diseño de una columna con elementos apilados verticalmente

---

## 🎯 Opciones de Navegación

Cada página de error incluye un menú visual con acceso rápido a:

1. **Inicio** - Volver a la página principal
2. **Servicios** - Ver nuestros servicios
3. **Blog** - Explorar artículos del blog
4. **Contacto** - Ir a la sección de contacto

Cada opción tiene:
- Icono distintivo
- Color de fondo único
- Efecto hover
- Animación suave

---

## 🔗 Botones de Acción

### Botón Principal
- **404**: Gradiente púrpura (marca SOPHEA)
- **405**: Gradiente rojo/naranja (indica error)
- Texto: "Volver al Inicio"
- Icono de flecha hacia atrás

### Botón Secundario
- Color verde (WhatsApp)
- Texto: "Contactar por WhatsApp"
- Abre WhatsApp con mensaje predefinido
- Se abre en nueva pestaña

---

## 🐛 Solución de Problemas

### La imagen no se muestra
1. Verifica que la ruta de la imagen sea correcta
2. Verifica permisos del archivo
3. Si la imagen no existe, se mostrará un icono alternativo

### Los errores no redirigen a las páginas personalizadas
1. Verifica que el módulo `mod_rewrite` esté habilitado en Apache
2. Verifica que la ruta en `.htaccess` sea correcta para tu servidor
3. Asegúrate de que el archivo `.htaccess` esté en la raíz del proyecto

### Error 500 al acceder a las páginas de error
1. Verifica que `config.php` esté accesible
2. Verifica que `header.php` y `footer.php` existan
3. Revisa los logs de error de PHP

---

## 📝 Personalización

### Cambiar Colores
Edita las clases de Tailwind en los archivos:
- `404.php`: Colores púrpura/azul
- `405.php`: Colores rojo/naranja

### Agregar Más Opciones de Navegación
Edita la sección del menú de navegación en ambos archivos:

```php
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <!-- Agrega más enlaces aquí -->
</div>
```

### Modificar Mensajes
Los mensajes están en las variables `$errorMessage` y `$errorDescription` en cada archivo.

---

## ✅ Checklist de Verificación

- [x] Página 404 creada con imagen
- [x] Página 405 creada con imagen
- [x] Menú de navegación integrado
- [x] Botones de acción funcionales
- [x] Diseño responsive
- [x] .htaccess configurado
- [x] Integración con WhatsApp
- [x] Estilos consistentes con el sitio

---

## 🚀 Próximos Pasos

Posibles mejoras futuras:
- [ ] Página 403 (Forbidden)
- [ ] Página 500 (Internal Server Error)
- [ ] Búsqueda integrada en la página 404
- [ ] Estadísticas de errores (qué páginas generan más 404)
- [ ] Sugerencias automáticas basadas en la URL

---

¡Las páginas de error están listas y funcionando! 🎉
