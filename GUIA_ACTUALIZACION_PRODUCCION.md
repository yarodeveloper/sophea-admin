# 🚀 Guía de Actualización a Producción - SOPHEA

## 📋 Resumen

Esta guía te ayudará a subir todos los cambios nuevos al servidor de producción, incluyendo:
- ✅ Nuevas tablas de base de datos
- ✅ Archivos nuevos (Schema SEO, mejoras de cookies, etc.)
- ✅ Actualizaciones de archivos existentes

---

## ⚠️ ANTES DE COMENZAR

### 1. Hacer Backup

**IMPORTANTE:** Siempre haz un backup completo antes de actualizar:

```bash
# Backup de base de datos
mysqldump -u usuario -p sophea_db > backup_sophea_$(date +%Y%m%d_%H%M%S).sql

# Backup de archivos (si usas FTP/SFTP, descarga todo primero)
```

### 2. Verificar Requisitos

- ✅ PHP 7.4 o superior
- ✅ MySQL 5.7 o superior (o MariaDB 10.2+)
- ✅ Extensiones PHP: PDO, JSON, cURL
- ✅ Permisos de escritura en directorios de uploads

---

## 📦 PASO 1: Subir Archivos al Servidor

### Archivos Nuevos a Subir

```
sopheaadmin/
├── classes/
│   ├── SchemaGenerator.php          ← NUEVO
│   └── (otros archivos existentes)
├── includes/
│   └── schema-helpers.php            ← NUEVO
├── database/
│   ├── migrate_production_2024.sql   ← NUEVO
│   └── run_migration.php             ← NUEVO (temporal)
├── components/
│   └── cookie_banner.php             ← ACTUALIZADO
├── header.php                        ← ACTUALIZADO
├── footer.php                        ← ACTUALIZADO
├── DOCUMENTACION_MODULO_WHATSAPP.md  ← NUEVO
├── DOCUMENTACION_SCHEMA_SEO.md       ← NUEVO
└── (otros archivos)
```

### Método 1: FTP/SFTP

1. Conecta a tu servidor con FileZilla o similar
2. Sube todos los archivos nuevos
3. Sobrescribe los archivos actualizados
4. Verifica permisos (644 para archivos, 755 para directorios)

### Método 2: Git (si usas control de versiones)

```bash
git add .
git commit -m "Actualización: Schema SEO, mejoras cookies, nuevas tablas"
git push origin main

# En el servidor
git pull origin main
```

---

## 🗄️ PASO 2: Actualizar Base de Datos

### Opción A: Desde Línea de Comandos (Recomendado)

```bash
# Conecta por SSH a tu servidor
ssh usuario@tu-servidor.com

# Navega al directorio del proyecto
cd /ruta/a/sopheaadmin

# Ejecuta la migración
mysql -u usuario_db -p sophea_db < database/migrate_production_2024.sql
```

### Opción B: Desde phpMyAdmin

1. Accede a phpMyAdmin
2. Selecciona la base de datos `sophea_db`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `database/migrate_production_2024.sql`
5. Haz clic en "Ejecutar"

**Nota:** Si solo necesitas crear la vista de campañas activas, usa el archivo más simple:
- `database/CREAR_VISTA_AHORA.sql` - Solo crea la vista (más rápido)

### Opción C: Script PHP (Solo si no tienes acceso SSH)

1. Sube el archivo `database/run_migration.php` al servidor
2. Accede desde el navegador: `https://tudominio.com/database/run_migration.php`
3. Ingresa la contraseña (configurada en el archivo)
4. Ejecuta la migración
5. **IMPORTANTE:** Elimina `run_migration.php` después de usarlo

---

## ✅ PASO 3: Verificar la Migración

### Verificar Tablas Creadas

Ejecuta esta consulta en phpMyAdmin o MySQL:

```sql
SHOW TABLES;
```

Deberías ver estas tablas nuevas:

**WhatsApp Marketing:**
- `whatsapp_campaigns`
- `whatsapp_campaign_recipients`
- `whatsapp_credits`
- `whatsapp_templates_custom`
- `whatsapp_automation_rules`
- `whatsapp_automation_log`
- `whatsapp_contact_lists`
- `whatsapp_contact_list_members`
- `whatsapp_lead_tags`
- `whatsapp_lead_tag_assignments`
- `whatsapp_message_log`
- `whatsapp_ab_tests`
- `whatsapp_scheduled_jobs`

**Testimonios:**
- `testimonials`
- `testimonial_images`

**Configuración:**
- `site_settings`

**Blog:**
- `blog_posts`
- `blog_categories`
- `blog_post_categories`

**Autenticación:**
- `login_attempts`

### Verificar Datos Iniciales

```sql
-- Verificar etiquetas de WhatsApp
SELECT * FROM whatsapp_lead_tags;

-- Verificar categorías del blog
SELECT * FROM blog_categories;

-- Verificar configuración del sitio
SELECT * FROM site_settings;
```

---

## 🔧 PASO 4: Configurar Permisos

### Permisos de Archivos

```bash
# En el servidor (SSH)
chmod 644 *.php
chmod 644 classes/*.php
chmod 644 includes/*.php
chmod 644 components/*.php
chmod 755 database/
chmod 644 database/*.sql
```

### Permisos de Directorios

```bash
chmod 755 classes/
chmod 755 includes/
chmod 755 components/
```

---

## ⚙️ PASO 5: Verificar Configuración

### 1. Verificar config.php

Asegúrate de que `config.php` tenga los valores correctos:

```php
define('SCHEMA_URL', 'https://www.sophea.com.mx'); // Tu URL real
define('SCHEMA_LOGO', 'https://www.sophea.com.mx/logo.png'); // URL de tu logo
```

### 2. Verificar config_db.php

```php
define('DB_HOST', 'localhost'); // O tu host de BD
define('DB_NAME', 'sophea_db');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Verificar config_whatsapp.php

Si usas WhatsApp, verifica que el token esté actualizado:

```php
define('WHATSAPP_ACCESS_TOKEN', 'tu_token_actual');
```

---

## 🧪 PASO 6: Pruebas Post-Migración

### 1. Probar Página Principal

- ✅ Visita: `https://tudominio.com/index.php`
- ✅ Verifica que el banner de cookies aparezca (más pequeño ahora)
- ✅ Verifica que no haya errores en la consola del navegador

### 2. Probar Schema SEO

- ✅ Abre el código fuente de la página
- ✅ Busca `<script type="application/ld+json">`
- ✅ Verifica que los schemas estén presentes
- ✅ Valida en: https://search.google.com/test/rich-results

### 3. Probar Funcionalidades

- ✅ Formulario de contacto
- ✅ Panel de administración
- ✅ Blog (si está activo)
- ✅ Testimonios (si están activos)

---

## 🐛 Solución de Problemas

### Error: "Table already exists"

**Solución:** Este error es normal si las tablas ya existen. El script usa `CREATE TABLE IF NOT EXISTS`, así que es seguro.

### Error: "Foreign key constraint fails"

**Solución:** Verifica que la tabla `leads` exista primero. Si no existe, ejecuta primero `database/schema.sql`.

### Error: "Access denied for user"

**Solución:** Verifica las credenciales en `config_db.php` y los permisos del usuario de MySQL.

### Error: "Class SchemaGenerator not found"

**Solución:** Verifica que `classes/SchemaGenerator.php` esté subido y tenga permisos de lectura (644).

### Banner de cookies no aparece

**Solución:** 
1. Limpia las cookies del navegador
2. Verifica que `components/cookie_banner.php` esté subido
3. Revisa la consola del navegador para errores JavaScript

---

## 📝 Checklist Final

Antes de considerar la actualización completa:

- [ ] Backup de base de datos realizado
- [ ] Todos los archivos subidos al servidor
- [ ] Migración de base de datos ejecutada
- [ ] Tablas nuevas verificadas
- [ ] Permisos de archivos correctos
- [ ] Configuración verificada
- [ ] Página principal funciona
- [ ] Schema SEO visible en código fuente
- [ ] Banner de cookies funciona
- [ ] Panel de administración accesible
- [ ] Sin errores en logs del servidor
- [ ] Archivo `run_migration.php` eliminado (si se usó)

---

## 🔒 Seguridad Post-Migración

### 1. Eliminar Archivos Temporales

```bash
# Eliminar script de migración PHP
rm database/run_migration.php
```

### 2. Verificar .htaccess

Asegúrate de que `.htaccess` bloquee acceso a archivos sensibles:

```apache
# Bloquear acceso a archivos SQL
<FilesMatch "\.sql$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloquear acceso a archivos de configuración
<FilesMatch "config.*\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Verificar Permisos

```bash
# Archivos de configuración no deben ser ejecutables
chmod 644 config*.php
chmod 644 database/*.sql
```

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs del servidor: `/var/log/apache2/error.log` o similar
2. Revisa los logs de PHP: `php_error.log`
3. Verifica la consola del navegador (F12)
4. Consulta la documentación:
   - `DOCUMENTACION_MODULO_WHATSAPP.md`
   - `DOCUMENTACION_SCHEMA_SEO.md`

---

## 🎉 ¡Actualización Completada!

Una vez completados todos los pasos, tu sitio debería estar funcionando con:

- ✅ Schema SEO completo para rich snippets
- ✅ Banner de cookies mejorado y menos invasivo
- ✅ Todas las tablas de WhatsApp Marketing
- ✅ Sistema de testimonios
- ✅ Sistema de blog
- ✅ Configuración del sitio

**¡Felicidades! Tu sitio está actualizado y listo para producción.**

---

**Última actualización:** 2024
**Versión:** 1.0

