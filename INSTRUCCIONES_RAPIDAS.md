# 🚨 SOLUCIÓN RÁPIDA: Error de Conexión a Base de Datos

## El Problema

El archivo `config_db.php` en tu servidor todavía tiene las credenciales de desarrollo local:
- Usuario: `root`
- Contraseña: vacía

Estas credenciales NO funcionan en producción.

---

## ✅ SOLUCIÓN EN 3 PASOS

### Paso 1: Sube el script de diagnóstico

Sube el archivo `test_db_config.php` a tu servidor y accede a:
```
https://tudominio.com/test_db_config.php
```

Este script te mostrará exactamente qué valores tiene configurados actualmente.

---

### Paso 2: Obtén las credenciales de producción

En tu panel de hosting (cPanel, Plesk, etc.):

1. Ve a **"Bases de Datos MySQL"** o **"MySQL Databases"**
2. Busca tu base de datos (la que acabas de crear)
3. Anota:
   - **Nombre de la base de datos**: Ejemplo `usuario_sophea`
   - **Usuario de MySQL**: Ejemplo `usuario_sophea` 
   - **Contraseña**: La que configuraste al crear la BD
   - **Host**: Generalmente `localhost`

**También puedes verlo en phpMyAdmin:**
- Al iniciar sesión, verás el usuario y base de datos en la parte superior

---

### Paso 3: Edita config_db.php en el servidor

Tienes 2 opciones:

#### **Opción A: Via cPanel File Manager (Más fácil)**

1. Accede a **cPanel**
2. Ve a **"File Manager"**
3. Navega a la carpeta de tu sitio (donde están los archivos PHP)
4. Busca `config_db.php`
5. Haz **clic derecho** > **"Edit"** o **"Editar"**
6. Cambia estas líneas:

```php
// ANTES:
define('DB_HOST', 'localhost');
define('DB_NAME', 'sophea_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// DESPUÉS (con tus datos reales):
define('DB_HOST', 'localhost');
define('DB_NAME', 'tuusuario_sophea');      // ← Tu nombre de BD
define('DB_USER', 'tuusuario_sophea');       // ← Tu usuario
define('DB_PASS', 'tu_password_real');       // ← Tu contraseña
```

7. Haz clic en **"Save Changes"** o **"Guardar Cambios"**

#### **Opción B: Via FTP**

1. Conecta con **FileZilla** o tu cliente FTP
2. Descarga `config_db.php` del servidor
3. Ábrelo con un editor de texto (Notepad++, VS Code, etc.)
4. Cambia las credenciales como se muestra arriba
5. Guarda el archivo
6. Súbelo de nuevo al servidor (sobrescribe el anterior)

---

## 🔍 Verificar que Funcionó

1. Recarga tu sitio web
2. El error debería desaparecer
3. O accede a `test_db_config.php` para verificar la conexión

---

## ⚠️ IMPORTANTE

- **NO** compartas tu archivo `config_db.php` públicamente
- **NO** subas `config_db.php` a repositorios públicos (GitHub, etc.)
- **Elimina** `test_db_config.php` después de verificar (por seguridad)

---

## 📝 Ejemplo Real

Si tu hosting te dio esta información:
```
Base de datos: miusuario_sophea
Usuario: miusuario_sophea  
Contraseña: MiPass123!
Host: localhost
```

Tu `config_db.php` debería verse así:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'miusuario_sophea');
define('DB_USER', 'miusuario_sophea');
define('DB_PASS', 'MiPass123!');
define('DB_CHARSET', 'utf8mb4');

// ... resto del archivo ...
```

---

## 🆘 ¿Aún no funciona?

1. Verifica que la base de datos existe en phpMyAdmin
2. Verifica que el usuario tiene permisos sobre la base de datos
3. Verifica que el host sea correcto (puede ser `localhost` o una IP)
4. Revisa los logs de errores en cPanel

