# 🚀 Guía Completa de Migración a Servidor - SOPHEA

**Fecha de actualización:** 2025-01-27  
**Versión:** 2.0 (Incluye: Información de Contacto Dinámica, Schema.org, Normalización, Caché Optimizado)

---

## 📋 Índice

1. [Preparación Pre-Migración](#preparación-pre-migración)
2. [Requisitos del Servidor](#requisitos-del-servidor)
3. [Backup y Preparación](#backup-y-preparación)
4. [Migración de Base de Datos](#migración-de-base-de-datos)
5. [Subida de Archivos](#subida-de-archivos)
6. [Configuración Post-Migración](#configuración-post-migración)
7. [Verificación y Pruebas](#verificación-y-pruebas)
8. [Solución de Problemas](#solución-de-problemas)

---

## ⚠️ PREPARACIÓN PRE-MIGRACIÓN

### Checklist Inicial

- [ ] Acceso SSH o FTP/SFTP al servidor
- [ ] Credenciales de base de datos del servidor
- [ ] Acceso a phpMyAdmin o línea de comandos MySQL
- [ ] Backup completo de desarrollo realizado
- [ ] Lista de archivos modificados revisada

---

## 🖥️ REQUISITOS DEL SERVIDOR

### Versiones Mínimas

- **PHP:** 7.4 o superior (recomendado 8.0+)
- **MySQL:** 5.7 o superior (o MariaDB 10.2+)
- **Espacio en disco:** Mínimo 500MB (recomendado 1GB+)

### Extensiones PHP Requeridas

```bash
# Verificar extensiones instaladas
php -m | grep -E "pdo|pdo_mysql|json|curl|gd|mbstring|openssl"
```

**Extensiones necesarias:**
- ✅ `pdo` y `pdo_mysql` - Conexión a base de datos
- ✅ `json` - Manejo de JSON
- ✅ `curl` - Comunicación con APIs (WhatsApp)
- ✅ `gd` - Generación de imágenes/PDFs
- ✅ `mbstring` - Manejo de caracteres especiales
- ✅ `openssl` - Comunicación segura
- ✅ `zip` - Manejo de archivos comprimidos (opcional)

### Verificar Extensiones

```php
<?php
// Crear archivo: check_php_extensions.php
echo "PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n";
echo "JSON: " . (extension_loaded('json') ? '✅' : '❌') . "\n";
echo "cURL: " . (extension_loaded('curl') ? '✅' : '❌') . "\n";
echo "GD: " . (extension_loaded('gd') ? '✅' : '❌') . "\n";
echo "mbstring: " . (extension_loaded('mbstring') ? '✅' : '❌') . "\n";
?>
```

---

## 💾 BACKUP Y PREPARACIÓN

### 1. Backup de Base de Datos Local

```bash
# Desde tu máquina local (XAMPP)
cd C:\xampp2\htdocs\sopheaadmin

# Backup completo
mysqldump -u root -p sophea_db > backup_local_$(date +%Y%m%d_%H%M%S).sql

# Backup solo estructura (sin datos)
mysqldump -u root -p --no-data sophea_db > backup_estructura_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Backup de Archivos

- Copia todo el directorio `sopheaadmin` a una ubicación segura
- O comprime el directorio completo

```bash
# Windows (PowerShell)
Compress-Archive -Path C:\xampp2\htdocs\sopheaadmin -DestinationPath backup_sopheaadmin_$(Get-Date -Format "yyyyMMdd_HHmmss").zip
```

### 3. Documentar Configuraciones Actuales

Anota los valores actuales de:
- URLs del sitio
- Credenciales de base de datos
- Tokens de WhatsApp (si aplica)
- Configuraciones de email

---

## 🗄️ MIGRACIÓN DE BASE DE DATOS

### Paso 1: Crear Base de Datos en el Servidor

**Opción A: Via cPanel**
1. Accede a cPanel → MySQL Databases
2. Crea nueva base de datos: `sophea_db` (o el nombre que prefieras)
3. Crea usuario y asigna todos los privilegios
4. Anota las credenciales

**Opción B: Via Línea de Comandos (SSH)**
```bash
mysql -u root -p
CREATE DATABASE sophea_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sophea_user'@'localhost' IDENTIFIED BY 'password_segura';
GRANT ALL PRIVILEGES ON sophea_db.* TO 'sophea_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 2: Importar Esquema de Base de Datos

**Archivos SQL a Importar (en orden):**

1. **Estructura Base:**
   - `database/schema.sql` o `database/schema_production.sql`

2. **Tablas Adicionales (si no están en schema_production.sql):**
   - `database/auth_schema.sql` - Autenticación
   - `database/clients_quotes_schema.sql` - Clientes y Cotizaciones
   - `database/expenses_schema.sql` - Gastos
   - `database/invoices_schema.sql` - Facturas
   - `database/site_settings_schema.sql` - Configuración del sitio
   - `database/blog_schema.sql` - Blog
   - `database/testimonials_schema.sql` - Testimonios
   - `database/whatsapp_marketing_schema.sql` - WhatsApp Marketing

3. **Migraciones (si ya tienes datos):**
   - `database/migrate_production_2024.sql` - Migración completa
   - `database/add_invoice_fields_to_payments.sql` - Campos de facturación

**Método de Importación:**

**Opción A: Via phpMyAdmin**
1. Accede a phpMyAdmin
2. Selecciona la base de datos `sophea_db`
3. Ve a la pestaña "Importar"
4. Selecciona el archivo SQL
5. Haz clic en "Continuar"
6. Repite para cada archivo SQL necesario

**Opción B: Via Línea de Comandos (SSH)**
```bash
# Conecta por SSH al servidor
ssh usuario@tu-servidor.com

# Navega al directorio del proyecto
cd /ruta/a/sopheaadmin

# Importa cada archivo SQL
mysql -u sophea_user -p sophea_db < database/schema_production.sql
mysql -u sophea_user -p sophea_db < database/site_settings_schema.sql
mysql -u sophea_user -p sophea_db < database/clients_quotes_schema.sql
mysql -u sophea_user -p sophea_db < database/expenses_schema.sql
mysql -u sophea_user -p sophea_db < database/invoices_schema.sql
# ... continúa con los demás
```

**Opción C: Script PHP Temporal**
```php
<?php
// database/import_all.php (ELIMINAR DESPUÉS DE USAR)
require_once '../config_db.php';
require_once '../classes/Database.php';

$sqlFiles = [
    'schema_production.sql',
    'site_settings_schema.sql',
    'clients_quotes_schema.sql',
    'expenses_schema.sql',
    'invoices_schema.sql',
    // ... otros archivos
];

$db = Database::getInstance()->getConnection();

foreach ($sqlFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $sql = file_get_contents($path);
        $db->exec($sql);
        echo "✅ Importado: $file\n";
    }
}
?>
```

### Paso 3: Importar Datos (Opcional)

Si tienes datos en desarrollo que quieres migrar:

```bash
# Exportar solo datos (sin estructura)
mysqldump -u root -p --no-create-info sophea_db > datos_local.sql

# Importar datos en servidor
mysql -u sophea_user -p sophea_db < datos_local.sql
```

**⚠️ IMPORTANTE:** Revisa y ajusta los datos antes de importar (IDs, URLs, etc.)

---

## 📁 SUBIDA DE ARCHIVOS

### Archivos y Directorios a Subir

```
sopheaadmin/
├── admin.php
├── admin_*.php (todos los archivos admin)
├── index.php
├── header.php
├── footer.php
├── config.php ⚠️ ACTUALIZAR URLs
├── config_db.php ⚠️ CONFIGURAR CREDENCIALES
├── config_whatsapp.php ⚠️ ACTUALIZAR TOKEN
├── process_form.php
├── send_*.php
├── generate_*.php
├── webhook_whatsapp.php
├── classes/
│   ├── Database.php
│   ├── Auth.php
│   ├── Client.php
│   ├── Service.php
│   ├── Payment.php
│   ├── Expense.php
│   ├── Invoice.php
│   ├── Quote.php
│   ├── SiteSettings.php ⭐ NUEVO
│   ├── SchemaGenerator.php
│   ├── WhatsAppAPI.php
│   └── (otros archivos)
├── includes/
│   ├── admin_*.php
│   ├── invoice_template.php
│   ├── quote_template.php
│   └── (otros archivos)
├── sections/
│   ├── contacto.php ⚠️ ACTUALIZADO
│   └── (otros archivos)
├── components/
│   └── cookie_banner.php
├── database/
│   └── *.sql (archivos SQL)
└── vendor/ (si usas Composer)
    └── dompdf/
```

### Archivos a NO Subir

- ❌ `.git/` (si usas Git)
- ❌ `node_modules/` (si existe)
- ❌ `*.md` (documentación, opcional)
- ❌ `test_*.php`, `debug_*.php` (archivos de prueba)
- ❌ `backup_*.sql` (backups locales)
- ❌ `check_*.php` (archivos de verificación temporales)

### Método de Subida

**Opción A: FTP/SFTP (FileZilla, WinSCP)**
1. Conecta al servidor
2. Navega al directorio del dominio/subdominio
3. Sube todos los archivos manteniendo la estructura
4. Verifica permisos (644 para archivos, 755 para directorios)

**Opción B: cPanel File Manager**
1. Accede a cPanel → File Manager
2. Navega al directorio público (public_html o similar)
3. Sube archivos (puedes comprimir y descomprimir)
4. Ajusta permisos

**Opción C: Git (si usas control de versiones)**
```bash
# En servidor
cd /ruta/a/sopheaadmin
git pull origin main
```

---

## ⚙️ CONFIGURACIÓN POST-MIGRACIÓN

### 1. Configurar `config_db.php`

Crea o actualiza `config_db.php` con las credenciales del servidor:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost'); // O la IP/host de tu servidor
define('DB_NAME', 'sophea_db'); // Nombre de tu BD en servidor
define('DB_USER', 'tu_usuario_db'); // Usuario de BD del servidor
define('DB_PASS', 'tu_password_db'); // Password de BD del servidor
define('DB_CHARSET', 'utf8mb4');

// Email Configuration
define('ADMIN_EMAIL', 'admin@sophea.com.mx');
define('FROM_EMAIL', 'noreply@sophea.com.mx');
define('FROM_NAME', 'SOPHEA - Sistema de Contacto');

// Form Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_DATABASE_STORAGE', true);
define('ENABLE_WHATSAPP_REDIRECT', true);

// Security Settings
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_TIMEOUT', 3600);
?>
```

### 2. Actualizar `config.php`

Verifica y actualiza estas constantes:

```php
// URLs del servidor
define('SCHEMA_URL', 'https://tudominio.com'); // ⚠️ CAMBIAR
define('SCHEMA_LOGO', 'https://tudominio.com/logo.png'); // ⚠️ CAMBIAR
define('SCHEMA_OG_IMAGE', 'https://tudominio.com/images/og-image.jpg'); // ⚠️ CAMBIAR

// Debug Mode - IMPORTANTE: Desactivar en producción
define('DEBUG_MODE', false); // ⚠️ CAMBIAR A false
```

### 3. Configurar `config_whatsapp.php` (si aplica)

```php
<?php
// WhatsApp Business API Configuration
define('WHATSAPP_ACCESS_TOKEN', 'tu_token_del_servidor'); // ⚠️ ACTUALIZAR
define('WHATSAPP_PHONE_NUMBER_ID', 'tu_phone_number_id'); // ⚠️ ACTUALIZAR
define('WHATSAPP_BUSINESS_ACCOUNT_ID', 'tu_business_account_id'); // ⚠️ ACTUALIZAR
define('WHATSAPP_VERIFY_TOKEN', 'tu_verify_token_seguro'); // ⚠️ ACTUALIZAR
?>
```

### 4. Configurar Permisos de Archivos

```bash
# Via SSH
cd /ruta/a/sopheaadmin

# Archivos PHP
find . -type f -name "*.php" -exec chmod 644 {} \;

# Directorios
find . -type d -exec chmod 755 {} \;

# Directorios que necesitan escritura
chmod 755 uploads/ 2>/dev/null
chmod 755 cache/ 2>/dev/null
chmod 755 logs/ 2>/dev/null
```

### 5. Crear Directorios Necesarios

```bash
# Crear directorios si no existen
mkdir -p uploads
mkdir -p cache
mkdir -p logs
chmod 755 uploads cache logs
```

### 6. Configurar .htaccess (Seguridad)

Crea o actualiza `.htaccess` en la raíz:

```apache
# Proteger archivos sensibles
<FilesMatch "^(config|config_db|config_whatsapp)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloquear archivos SQL
<FilesMatch "\.sql$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloquear acceso a directorios sensibles
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^database/ - [F,L]
    RewriteRule ^vendor/ - [F,L]
    RewriteRule ^\.git/ - [F,L]
</IfModule>

# Habilitar compresión
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache de archivos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## ✅ VERIFICACIÓN Y PRUEBAS

### 1. Verificar Conexión a Base de Datos

Crea un archivo temporal `test_db_connection.php`:

```php
<?php
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Conexión a base de datos: EXITOSA\n";
    
    // Verificar tablas principales
    $tables = ['leads', 'clients', 'services', 'payments', 'site_settings'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$table': EXISTE\n";
        } else {
            echo "❌ Tabla '$table': NO EXISTE\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
```

Accede desde navegador: `https://tudominio.com/test_db_connection.php`  
**⚠️ ELIMINA este archivo después de verificar**

### 2. Verificar Tablas de Base de Datos

```sql
-- Ejecutar en phpMyAdmin o MySQL
SHOW TABLES;

-- Deberías ver estas tablas principales:
-- leads, clients, services, payments, expenses, quotes, invoices
-- site_settings, blog_posts, testimonials
-- whatsapp_campaigns, whatsapp_templates_custom, etc.
```

### 3. Verificar Información de Contacto

1. Accede a: `https://tudominio.com/admin_web.php?tab=contact`
2. Verifica que puedas ver/editar información de contacto
3. Guarda un cambio y verifica que se actualice

### 4. Probar Funcionalidades Principales

- [ ] **Página principal:** `https://tudominio.com/index.php`
- [ ] **Formulario de contacto:** Envía un test
- [ ] **Panel admin:** `https://tudominio.com/admin.php`
- [ ] **Login:** Verifica autenticación
- [ ] **Dashboard:** `https://tudominio.com/admin_dashboard.php`
- [ ] **Clientes:** Ver lista de clientes
- [ ] **Cotizaciones:** Crear/editar cotización
- [ ] **Facturación:** Generar factura
- [ ] **Gastos:** Ver lista de gastos
- [ ] **Admin Web:** Blog, Banner, Testimonios, Información de Contacto

### 5. Verificar Schema.org

1. Abre `https://tudominio.com/index.php`
2. Ver código fuente (Ctrl+U)
3. Busca `<script type="application/ld+json">`
4. Verifica que los schemas estén presentes
5. Valida en: https://search.google.com/test/rich-results

### 6. Verificar WhatsApp (si aplica)

1. Verifica que el webhook esté configurado en Meta Business Manager
2. Prueba enviar un mensaje de prueba
3. Verifica logs en `admin_whatsapp_config.php`

### 7. Verificar Generación de PDFs

1. Genera una cotización: `admin_quotes.php` → Imprimir
2. Genera una factura: `admin_payments.php` → Ver Factura → Descargar PDF
3. Verifica que los PDFs se generen correctamente

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Cannot connect to database"

**Causas posibles:**
- Credenciales incorrectas en `config_db.php`
- Host incorrecto (puede ser `localhost` o una IP)
- Usuario sin permisos

**Solución:**
1. Verifica credenciales en `config_db.php`
2. Prueba conexión con `test_db_connection.php`
3. Verifica permisos del usuario MySQL

### Error: "Class not found" o "Fatal error: Class 'X' not found"

**Causa:** Archivos no subidos o ruta incorrecta

**Solución:**
1. Verifica que todos los archivos en `classes/` estén subidos
2. Verifica permisos de lectura (644)
3. Revisa `require_once` paths en los archivos

### Error: "The PHP GD extension is required"

**Solución:**
```bash
# En servidor (SSH)
# Edita php.ini
nano /etc/php/8.0/apache2/php.ini  # Ajusta versión PHP

# Busca y descomenta:
extension=gd

# Reinicia Apache
sudo systemctl restart apache2
# O
sudo service apache2 restart
```

### Error: "Permission denied" al subir archivos

**Solución:**
```bash
# Ajustar permisos
chmod 755 uploads/
chown www-data:www-data uploads/  # Ajusta usuario según servidor
```

### Información de Contacto no se actualiza

**Causa:** Caché no se está limpiando

**Solución:**
1. Verifica que `clear_contact_info_cache()` se llame en `admin_web.php`
2. Limpia caché manualmente:
```php
// Ejecutar una vez
clear_contact_info_cache();
```
3. Si usas OPcache, reinicia PHP-FPM o Apache

### Schema.org no aparece

**Causa:** `get_contact_info()` falla silenciosamente

**Solución:**
1. Verifica logs de PHP
2. Verifica que `site_settings` tabla exista
3. Verifica conexión a base de datos
4. Revisa consola del navegador (F12)

---

## 📝 CHECKLIST FINAL DE MIGRACIÓN

### Pre-Migración
- [ ] Backup completo realizado
- [ ] Requisitos del servidor verificados
- [ ] Extensiones PHP instaladas
- [ ] Credenciales de servidor documentadas

### Base de Datos
- [ ] Base de datos creada en servidor
- [ ] Usuario de BD creado con permisos
- [ ] Todos los archivos SQL importados
- [ ] Tablas verificadas (SHOW TABLES)
- [ ] Datos migrados (si aplica)

### Archivos
- [ ] Todos los archivos subidos
- [ ] Estructura de directorios correcta
- [ ] Permisos configurados (644/755)
- [ ] Archivos temporales eliminados

### Configuración
- [ ] `config_db.php` configurado con credenciales del servidor
- [ ] `config.php` actualizado con URLs del servidor
- [ ] `DEBUG_MODE` desactivado (false)
- [ ] `config_whatsapp.php` actualizado (si aplica)
- [ ] `.htaccess` configurado para seguridad

### Funcionalidades
- [ ] Conexión a BD funciona
- [ ] Login de admin funciona
- [ ] Dashboard carga correctamente
- [ ] Formulario de contacto funciona
- [ ] Información de contacto se puede editar
- [ ] Cotizaciones funcionan
- [ ] Facturas se generan correctamente
- [ ] PDFs se generan sin errores
- [ ] Schema.org visible en código fuente

### Seguridad
- [ ] Archivos de configuración protegidos (.htaccess)
- [ ] Archivos SQL no accesibles
- [ ] Directorios sensibles bloqueados
- [ ] DEBUG_MODE desactivado
- [ ] Archivos temporales eliminados

---

## 🔒 SEGURIDAD POST-MIGRACIÓN

### 1. Eliminar Archivos Temporales

```bash
# Eliminar archivos de prueba
rm test_*.php
rm check_*.php
rm debug_*.php
rm database/import_all.php  # Si se creó
rm database/run_migration.php  # Si se usó
```

### 2. Verificar Permisos

```bash
# Archivos de configuración NO deben ser ejecutables
chmod 644 config*.php
chmod 644 database/*.sql

# Directorios públicos
chmod 755 uploads/
chmod 755 cache/
```

### 3. Configurar HTTPS (Recomendado)

Asegúrate de que el sitio use HTTPS:
- Configura SSL en el servidor
- Actualiza URLs en `config.php` a `https://`
- Configura redirección HTTP → HTTPS en `.htaccess`

### 4. Configurar Backups Automáticos

Configura backups automáticos de:
- Base de datos (diario recomendado)
- Archivos importantes (semanal recomendado)

---

## 📞 VERIFICACIÓN FINAL

### URLs a Verificar

- [ ] `https://tudominio.com/` - Página principal
- [ ] `https://tudominio.com/index.php` - Página principal (alternativa)
- [ ] `https://tudominio.com/admin.php` - Panel de administración
- [ ] `https://tudominio.com/admin_dashboard.php` - Dashboard
- [ ] `https://tudominio.com/admin_web.php` - Admin Web
- [ ] `https://tudominio.com/admin_web.php?tab=contact` - Información de Contacto

### Funcionalidades Críticas

- [ ] Formulario de contacto guarda leads
- [ ] Login de admin funciona
- [ ] Información de contacto se actualiza desde Admin Web
- [ ] Cotizaciones se generan correctamente
- [ ] Facturas se generan y descargan como PDF
- [ ] WhatsApp funciona (si aplica)

---

## 🎉 ¡Migración Completada!

Una vez completados todos los pasos, tu plataforma debería estar funcionando en el servidor con:

- ✅ Información de contacto dinámica (actualizable desde Admin Web)
- ✅ Schema.org optimizado para SEO
- ✅ Normalización automática de números de teléfono
- ✅ Sistema de caché optimizado
- ✅ Todas las funcionalidades de administración
- ✅ Generación de cotizaciones y facturas
- ✅ Sistema de gastos
- ✅ Integración con WhatsApp (si aplica)

---

## 📚 Documentación Adicional

- `DEPLOYMENT_GUIDE.md` - Guía de despliegue general
- `GUIA_ACTUALIZACION_PRODUCCION.md` - Guía de actualizaciones
- `DOCUMENTACION_MODULO_WHATSAPP.md` - Documentación de WhatsApp

---

**Última actualización:** 2025-01-27  
**Versión:** 2.0

