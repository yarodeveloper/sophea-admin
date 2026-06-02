# 🚀 Guía de Despliegue a Producción - SOPHEA

Esta guía te ayudará a subir el proyecto al subdominio de producción.

## 📋 Checklist Pre-Despliegue

- [ ] Base de datos creada y configurada
- [ ] Archivos subidos al servidor
- [ ] Configuración de base de datos actualizada
- [ ] Configuración de WhatsApp actualizada
- [ ] Permisos de archivos configurados
- [ ] Webhook configurado en Meta Business Manager

---

## 🗄️ Paso 1: Configurar Base de Datos

### Opción A: Base de Datos Nueva (Recomendado)

1. **Accede a tu panel de hosting** (cPanel, Plesk, etc.)

2. **Crea una nueva base de datos:**
   - Nombre sugerido: `sophea_db` o `tuusuario_sophea`
   - Usuario: crea un usuario específico para esta base de datos
   - Contraseña: genera una contraseña segura

3. **Importa el esquema:**
   - Opción 1: Via phpMyAdmin
     - Accede a phpMyAdmin
     - Selecciona tu base de datos
     - Ve a la pestaña "Importar"
     - Selecciona el archivo `database/schema_production.sql`
     - Haz clic en "Continuar"
   
   - Opción 2: Via línea de comandos (SSH)
     ```bash
     mysql -u usuario_db -p nombre_db < database/schema_production.sql
     ```

### Opción B: Base de Datos Existente

Si ya tienes una base de datos con la tabla `leads`, ejecuta el script de migración:

```bash
mysql -u usuario_db -p nombre_db < database/migrate_to_production.sql
```

O importa `database/migrate_to_production.sql` via phpMyAdmin.

---

## 📁 Paso 2: Subir Archivos al Servidor

### Archivos a Subir

Sube todos los archivos del proyecto a tu subdominio, excepto:

**NO subir:**
- `.git/` (si usas Git)
- `node_modules/` (si existe)
- Archivos de desarrollo como `test_*.php`, `debug_*.php`
- `README.md`, `*.md` (opcional, puedes mantenerlos)

**SÍ subir:**
```
sopheaadmin/
├── admin.php
├── admin_whatsapp_config.php
├── config.php
├── config_db.php
├── config_whatsapp.php
├── index.php
├── header.php
├── footer.php
├── process_form.php
├── send_whatsapp.php
├── webhook_whatsapp.php
├── sections/
│   └── contacto.php
├── classes/
│   ├── Database.php
│   └── WhatsAppAPI.php
└── database/
    ├── schema_production.sql
    └── migrate_to_production.sql
```

### Método de Subida

1. **Via FTP/SFTP:**
   - Usa FileZilla, WinSCP, o tu cliente FTP favorito
   - Conecta a tu servidor
   - Sube todos los archivos manteniendo la estructura de carpetas

2. **Via cPanel File Manager:**
   - Accede a cPanel
   - Ve a "File Manager"
   - Navega a la carpeta de tu subdominio
   - Sube los archivos (puedes comprimir y descomprimir)

---

## ⚙️ Paso 3: Configurar Archivos

### 1. Actualizar `config_db.php`

Edita `config_db.php` con los datos de producción:

```php
<?php
// Database Configuration - PRODUCCIÓN
define('DB_HOST', 'localhost'); // O la IP del servidor de BD
define('DB_NAME', 'tuusuario_sophea'); // Nombre de tu BD
define('DB_USER', 'tuusuario_db'); // Usuario de BD
define('DB_PASS', 'tu_password_seguro'); // Contraseña de BD
define('DB_CHARSET', 'utf8mb4');

// Email Configuration
define('ADMIN_EMAIL', 'admin@sophea.com.mx'); // Tu email
define('FROM_EMAIL', 'noreply@sophea.com.mx'); // Email remitente
define('FROM_NAME', 'SOPHEA - Sistema de Contacto');

// Form Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_DATABASE_STORAGE', true);
define('ENABLE_WHATSAPP_REDIRECT', true);

// Security Settings
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_TIMEOUT', 3600);
```

### 2. Actualizar `config.php`

Verifica que las URLs y configuraciones sean correctas:

```php
// Site Configuration
define('SITE_URL', 'https://subdominio.tudominio.com'); // Tu URL de producción
define('CONTACT_PHONE', '+52 961 XXX XXXX');
define('CONTACT_WHATSAPP', '+52961XXXXXXXX'); // Sin espacios ni guiones
```

### 3. Actualizar `config_whatsapp.php`

Configura los parámetros de WhatsApp Business API:

```php
<?php
// WhatsApp Business API Configuration
define('WHATSAPP_API_ENABLED', true);
define('WHATSAPP_PHONE_NUMBER_ID', '619215614617031');
define('WHATSAPP_BUSINESS_ACCOUNT_ID', '130339163500704');
define('WHATSAPP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI'); // ⚠️ IMPORTANTE
define('WHATSAPP_API_VERSION', 'v18.0');

// Webhook Configuration
define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'token_seguro_generado'); // Cambia esto
define('WHATSAPP_WEBHOOK_URL', 'https://subdominio.tudominio.com/webhook_whatsapp.php');
```

**⚠️ IMPORTANTE:** 
- Obtén el Access Token desde Meta Business Manager
- Genera un Verify Token seguro (puedes usar el generador en el admin)
- Actualiza la URL del webhook con tu dominio real

---

## 🔒 Paso 4: Configurar Permisos

### Permisos de Archivos

Los archivos PHP deben tener permisos `644`:
```bash
find . -type f -name "*.php" -exec chmod 644 {} \;
```

Las carpetas deben tener permisos `755`:
```bash
find . -type d -exec chmod 755 {} \;
```

### Proteger Archivos Sensibles

Crea un archivo `.htaccess` en la raíz para proteger archivos de configuración:

```apache
# Proteger archivos de configuración
<FilesMatch "^(config|config_db|config_whatsapp)\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# O mejor aún, mover estos archivos fuera del webroot
```

**Mejor práctica:** Mueve `config_db.php` y `config_whatsapp.php` fuera del directorio público si es posible.

---

## 🔐 Paso 5: Configurar Seguridad

### 1. Cambiar Contraseña del Admin

Edita `admin.php` y `admin_whatsapp_config.php`:

```php
// Cambia esta contraseña en producción
$admin_password = 'tu_password_seguro_aqui'; // ⚠️ CAMBIAR
```

### 2. Deshabilitar Debug Mode

En `config.php`:

```php
// Enable Error Reporting (Set to false in production)
define('DEBUG_MODE', false); // ⚠️ Cambiar a false

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

### 3. Configurar HTTPS

Asegúrate de que tu sitio use HTTPS. Si usas Let's Encrypt:

```bash
# En cPanel, ve a SSL/TLS Status y activa SSL
# O configura Let's Encrypt automáticamente
```

---

## 📱 Paso 6: Configurar Webhook de WhatsApp

1. **Accede a Meta Business Manager:**
   - Ve a https://business.facebook.com
   - Selecciona tu cuenta de WhatsApp Business

2. **Configura el Webhook:**
   - Ve a "Configuración" > "WhatsApp" > "Configuración"
   - En "Webhooks", haz clic en "Configurar"
   - Callback URL: `https://subdominio.tudominio.com/webhook_whatsapp.php`
   - Verify Token: El mismo que configuraste en `config_whatsapp.php`
   - Haz clic en "Verificar y guardar"

3. **Suscríbete a los campos:**
   - Selecciona los campos que quieres recibir:
     - ✅ `messages` (obligatorio)
     - ✅ `message_template_status_update` (opcional)
     - ✅ `account_alerts` (opcional)

4. **Verifica el webhook:**
   - Meta enviará una petición GET para verificar
   - Revisa los logs del servidor para confirmar

---

## ✅ Paso 7: Verificar Instalación

### Checklist de Verificación

1. **Base de Datos:**
   - [ ] Tablas creadas correctamente
   - [ ] Conexión funciona desde el sitio
   - [ ] Vista `lead_stats` existe

2. **Formulario de Contacto:**
   - [ ] El formulario se muestra correctamente
   - [ ] Los datos se guardan en la base de datos
   - [ ] Los emails se envían (verifica spam)

3. **Panel de Administración:**
   - [ ] Acceso a `/admin.php` funciona
   - [ ] Puedes ver los leads
   - [ ] Puedes actualizar estados

4. **WhatsApp:**
   - [ ] Configuración guardada en admin
   - [ ] Webhook verificado en Meta
   - [ ] Puedes enviar mensajes desde el admin

---

## 🐛 Solución de Problemas

### Error: "Database Connection Error"

**Solución:**
- Verifica credenciales en `config_db.php`
- Verifica que el usuario de BD tenga permisos
- Verifica que el host de BD sea correcto (puede ser `localhost` o una IP)

### Error: "Token de seguridad inválido"

**Solución:**
- Verifica que las sesiones funcionen en el servidor
- Limpia la caché del navegador
- Verifica permisos de escritura en `/tmp` (para sesiones)

### Webhook no funciona

**Solución:**
- Verifica que la URL sea accesible públicamente (HTTPS)
- Verifica que el Verify Token coincida
- Revisa los logs del servidor
- Prueba accediendo directamente: `https://tudominio.com/webhook_whatsapp.php?hub_mode=subscribe&hub_verify_token=TU_TOKEN&hub_challenge=test`

### Emails no se envían

**Solución:**
- Verifica configuración SMTP del servidor
- Revisa logs de errores de PHP
- Verifica que `ADMIN_EMAIL` sea válido
- En algunos hosts necesitas usar SMTP externo (Gmail, SendGrid, etc.)

---

## 📞 Soporte

Si tienes problemas durante el despliegue:

1. Revisa los logs de errores del servidor
2. Verifica los permisos de archivos
3. Confirma que todas las configuraciones sean correctas
4. Prueba cada componente individualmente

---

## 🎉 ¡Listo!

Una vez completados todos los pasos, tu sitio debería estar funcionando en producción.

**Próximos pasos:**
- Monitorea los logs regularmente
- Haz backups periódicos de la base de datos
- Actualiza las contraseñas regularmente
- Revisa el panel de administración regularmente

