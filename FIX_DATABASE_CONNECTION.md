# 🔧 Solución: Error de Conexión a Base de Datos

## ❌ Error Actual

```
Database Connection Error: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)
```

Este error significa que el archivo `config_db.php` todavía tiene las credenciales de desarrollo local.

---

## ✅ Solución Paso a Paso

### Paso 1: Encontrar las Credenciales de Base de Datos

Las credenciales están en tu panel de hosting. Dependiendo de tu proveedor:

#### **cPanel (más común)**

1. Accede a **cPanel** de tu hosting
2. Busca la sección **"Bases de Datos MySQL"** o **"MySQL Databases"**
3. Verás una lista de bases de datos creadas
4. Haz clic en el nombre de tu base de datos o busca **"Usuarios de MySQL"**
5. Verás algo como:
   ```
   Usuario: tuusuario_sophea
   Base de datos: tuusuario_sophea
   Host: localhost (o una IP)
   ```

**O también puedes:**
- Ir a **"phpMyAdmin"** en cPanel
- El nombre de usuario y base de datos aparecen en la parte superior

#### **Plesk**

1. Accede a **Plesk**
2. Ve a **"Bases de Datos"** > **"MySQL"**
3. Selecciona tu base de datos
4. Verás las credenciales en la página

#### **Otros Paneles**

Busca la sección de **"Bases de Datos"** o **"MySQL"** en tu panel de control.

---

### Paso 2: Actualizar `config_db.php`

Edita el archivo `config_db.php` en tu servidor con las credenciales correctas:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');  // ← Generalmente 'localhost'
define('DB_NAME', 'tuusuario_sophea');  // ← Nombre de tu BD
define('DB_USER', 'tuusuario_db');  // ← Usuario de BD
define('DB_PASS', 'tu_password_aqui');  // ← Contraseña de BD
define('DB_CHARSET', 'utf8mb4');
```

**Ejemplo real:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'miusuario_sophea');
define('DB_USER', 'miusuario_sophea');
define('DB_PASS', 'MiPassword123!');
```

---

### Paso 3: Verificar la Conexión

Después de actualizar, recarga tu sitio web. Si el error persiste:

#### Opción A: Usar el script de verificación

Accede a: `https://tudominio.com/check_production.php`

Este script te dirá si la conexión funciona.

#### Opción B: Verificar manualmente

1. Accede a phpMyAdmin desde tu cPanel
2. Intenta iniciar sesión con las mismas credenciales
3. Si funciona en phpMyAdmin, debería funcionar en el código

---

## 🔍 Problemas Comunes

### Problema 1: "Access denied" incluso con credenciales correctas

**Solución:**
- Verifica que el usuario tenga permisos sobre la base de datos
- En cPanel, ve a "Usuarios de MySQL" y asegúrate de que el usuario esté asignado a la base de datos

### Problema 2: El host no es "localhost"

**Solución:**
Algunos hostings usan un host diferente:
- `localhost`
- Una IP como `127.0.0.1`
- Un dominio como `mysql.tudominio.com`

Verifica en tu panel de hosting cuál es el host correcto.

### Problema 3: El nombre de la base de datos tiene un prefijo

**Solución:**
Muchos hostings agregan un prefijo al nombre de usuario. Por ejemplo:
- Si tu usuario es `miusuario`
- La base de datos podría ser `miusuario_sophea`
- El usuario de BD también podría ser `miusuario_sophea`

---

## 📝 Ejemplo Completo de Configuración

Si tu hosting te dio esta información:
```
Base de datos: usuario_sophea
Usuario: usuario_sophea
Contraseña: Abc123Xyz!
Host: localhost
```

Tu `config_db.php` debería verse así:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_sophea');
define('DB_USER', 'usuario_sophea');
define('DB_PASS', 'Abc123Xyz!');
define('DB_CHARSET', 'utf8mb4');

// ... resto del archivo ...
```

---

## ⚠️ Importante

1. **No compartas** tu archivo `config_db.php` públicamente
2. **Haz backup** antes de modificar
3. **Verifica** que el archivo tenga permisos `644` (no ejecutable)
4. Si tienes acceso SSH, puedes proteger el archivo moviéndolo fuera del webroot

---

## 🆘 ¿Aún no funciona?

Si después de seguir estos pasos el error persiste:

1. **Verifica que la base de datos existe:**
   - Accede a phpMyAdmin
   - Confirma que la base de datos está creada

2. **Verifica que las tablas existen:**
   - En phpMyAdmin, selecciona tu base de datos
   - Deberías ver las tablas: `leads`, `email_log`, `admin_users`, `whatsapp_messages`

3. **Revisa los logs de errores:**
   - En cPanel, ve a "Error Logs" o "Logs"
   - Busca errores relacionados con MySQL

4. **Contacta a tu proveedor de hosting:**
   - Pregunta por las credenciales correctas
   - Verifica que MySQL esté activo en tu cuenta

---

## ✅ Verificación Final

Una vez configurado correctamente, deberías poder:
- ✅ Acceder al sitio web sin errores
- ✅ Ver el formulario de contacto
- ✅ Acceder al panel de administración (`/admin.php`)
- ✅ Verificar conexión en `/check_production.php`

