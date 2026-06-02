# 🔐 Sistema de Autenticación Mejorado - SOPHEA

## ✅ Implementación Completada

Se ha implementado un sistema de autenticación seguro que reemplaza la contraseña hardcodeada anterior.

---

## 📋 Características Implementadas

### 1. **Hash de Contraseñas**
- ✅ Usa `password_hash()` con algoritmo bcrypt (PASSWORD_DEFAULT)
- ✅ Verificación con `password_verify()`
- ✅ Contraseñas nunca se almacenan en texto plano

### 2. **Rate Limiting (Protección contra Fuerza Bruta)**
- ✅ Máximo 5 intentos fallidos por IP
- ✅ Bloqueo de 15 minutos después de exceder el límite
- ✅ Tabla `login_attempts` para rastrear intentos
- ✅ Limpieza automática de intentos antiguos

### 3. **Tokens de Sesión Seguros**
- ✅ Tokens HMAC-SHA256 generados por sesión
- ✅ Validación de tokens en cada request
- ✅ Regeneración periódica de ID de sesión (cada 5 minutos)
- ✅ Prevención de session fixation

### 4. **Logout Automático por Inactividad**
- ✅ Timeout de sesión: 30 minutos
- ✅ Verificación de última actividad
- ✅ Logout automático si se excede el tiempo

### 5. **Configuración de Sesión Segura**
- ✅ Cookies HttpOnly (previene XSS)
- ✅ Cookies Secure (solo HTTPS)
- ✅ SameSite=Strict (previene CSRF)
- ✅ Modo estricto de sesiones

### 6. **Cambio de Contraseña**
- ✅ Página dedicada para cambiar contraseña
- ✅ Validación de contraseña actual
- ✅ Requisitos de contraseña nueva (mínimo 8 caracteres)
- ✅ Regeneración de token después del cambio

---

## 🗂️ Archivos Creados/Modificados

### Nuevos Archivos
1. **`classes/Auth.php`** - Clase principal de autenticación
2. **`admin_auth_helper.php`** - Helper para paneles admin
3. **`setup_admin_user.php`** - Script para crear usuario admin inicial
4. **`admin_change_password.php`** - Página para cambiar contraseña
5. **`database/auth_schema.sql`** - Schema para tabla de intentos de login

### Archivos Modificados
1. **`admin.php`** - Actualizado para usar nueva autenticación
2. **`admin_blog.php`** - Actualizado para usar nueva autenticación
3. **`admin_testimonials.php`** - Actualizado para usar nueva autenticación
4. **`admin_banner.php`** - Actualizado para usar nueva autenticación
5. **`admin_whatsapp_config.php`** - Actualizado para usar nueva autenticación

---

## 🚀 Instalación y Configuración

### Paso 1: Ejecutar Schema de Base de Datos

Ejecuta el script SQL para crear la tabla de intentos de login:

```sql
-- Ejecutar: database/auth_schema.sql
USE sophea_db;
SOURCE database/auth_schema.sql;
```

O ejecuta manualmente:

```sql
USE sophea_db;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Paso 2: Crear Usuario Admin Inicial

1. Accede a: `http://localhost/sopheaadmin/setup_admin_user.php`
2. Completa el formulario:
   - **Usuario**: El nombre de usuario deseado
   - **Email**: Tu email
   - **Nombre Completo**: Tu nombre (opcional)
   - **Contraseña**: Mínimo 8 caracteres
   - **Confirmar Contraseña**: Repite la contraseña
3. Haz clic en "Crear Usuario Admin"
4. **IMPORTANTE**: Elimina `setup_admin_user.php` después de crear el usuario

### Paso 3: Verificar Funcionamiento

1. Accede a cualquier panel admin (ej: `admin.php`)
2. Deberías ver un formulario de login con campos "Usuario" y "Contraseña"
3. Inicia sesión con las credenciales creadas
4. Verifica que puedes acceder al panel

### Paso 4: Cambiar Contraseña (Opcional)

1. Una vez dentro del panel admin, haz clic en "Cambiar Contraseña"
2. Ingresa tu contraseña actual
3. Ingresa tu nueva contraseña (mínimo 8 caracteres)
4. Confirma la nueva contraseña
5. Haz clic en "Cambiar Contraseña"

---

## 🔧 Configuración Avanzada

### Cambiar Timeout de Sesión

En `classes/Auth.php`, línea 15:

```php
private $sessionTimeout = 1800; // 30 minutos en segundos
```

### Cambiar Límite de Intentos

En `classes/Auth.php`, línea 16-17:

```php
private $maxLoginAttempts = 5; // Máximo de intentos
private $lockoutTime = 900; // 15 minutos en segundos
```

### Cambiar Clave Secreta para Tokens

En `config.php`, agrega:

```php
define('AUTH_SECRET_KEY', 'tu_clave_secreta_muy_larga_y_aleatoria_aqui');
```

**IMPORTANTE**: Cambia esta clave en producción por una clave aleatoria fuerte.

---

## 🔒 Seguridad

### Características de Seguridad Implementadas

1. ✅ **Hash de contraseñas**: bcrypt con salt automático
2. ✅ **Rate limiting**: Protección contra fuerza bruta
3. ✅ **Tokens seguros**: HMAC-SHA256
4. ✅ **Session fixation**: Regeneración periódica de ID
5. ✅ **Cookies seguras**: HttpOnly, Secure, SameSite
6. ✅ **Timeout automático**: Logout por inactividad
7. ✅ **Validación de tokens**: Verificación en cada request

### Recomendaciones Adicionales

1. **Cambiar clave secreta en producción**: Define `AUTH_SECRET_KEY` en `config.php`
2. **Usar HTTPS**: Obligatorio para cookies Secure
3. **Monitorear intentos fallidos**: Revisar tabla `login_attempts` periódicamente
4. **Backup de base de datos**: Incluir tabla `admin_users` en backups
5. **Eliminar `setup_admin_user.php`**: Después de crear el usuario inicial

---

## 📊 Estructura de Base de Datos

### Tabla: `admin_users`
```sql
- id (INT, PRIMARY KEY)
- username (VARCHAR(100), UNIQUE)
- password_hash (VARCHAR(255)) - Hash bcrypt
- email (VARCHAR(255))
- full_name (VARCHAR(255))
- created_at (TIMESTAMP)
- last_login (TIMESTAMP)
- is_active (BOOLEAN)
```

### Tabla: `login_attempts`
```sql
- id (INT, PRIMARY KEY)
- ip_address (VARCHAR(45))
- attempt_time (TIMESTAMP)
```

---

## 🐛 Solución de Problemas

### Error: "Class 'Auth' not found"
- Verifica que `classes/Auth.php` existe
- Verifica que `require_once 'classes/Auth.php'` está presente

### Error: "Table 'login_attempts' doesn't exist"
- Ejecuta `database/auth_schema.sql`
- O crea la tabla manualmente (ver Paso 1)

### No puedo iniciar sesión
- Verifica que el usuario existe en `admin_users`
- Verifica que `is_active = 1`
- Verifica que la contraseña es correcta
- Revisa si la IP está bloqueada (tabla `login_attempts`)

### Sesión se cierra muy rápido
- Ajusta `$sessionTimeout` en `Auth.php`
- Verifica que no hay problemas con cookies del navegador

### Limpiar Intentos de Login Bloqueados

```sql
-- Limpiar todos los intentos
DELETE FROM login_attempts;

-- Limpiar intentos de una IP específica
DELETE FROM login_attempts WHERE ip_address = 'TU_IP';
```

---

## 📝 Migración desde Sistema Anterior

Si ya tenías usuarios con el sistema anterior:

1. **Crear nuevo usuario admin** usando `setup_admin_user.php`
2. **Iniciar sesión** con el nuevo usuario
3. **Eliminar usuarios antiguos** si es necesario (desde base de datos)
4. **Actualizar todos los paneles admin** (ya hecho automáticamente)

---

## ✅ Checklist de Implementación

- [x] Clase Auth.php creada
- [x] Tabla login_attempts creada
- [x] Helper admin_auth_helper.php creado
- [x] admin.php actualizado
- [x] admin_blog.php actualizado
- [x] admin_testimonials.php actualizado
- [x] admin_banner.php actualizado
- [x] admin_whatsapp_config.php actualizado
- [x] Script setup_admin_user.php creado
- [x] Página admin_change_password.php creada
- [x] Documentación creada

---

## 🎯 Próximos Pasos

1. ✅ Ejecutar `auth_schema.sql`
2. ✅ Crear usuario admin con `setup_admin_user.php`
3. ✅ Probar login en todos los paneles
4. ✅ Eliminar `setup_admin_user.php`
5. ✅ Cambiar `AUTH_SECRET_KEY` en producción

---

**Última actualización**: 2025-01-27  
**Versión**: 1.0
