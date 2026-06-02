# 🔧 Crear Usuario de MySQL y Asignarlo a la Base de Datos

## El Problema

Tienes la base de datos creada, pero no tienes un usuario de MySQL asignado a ella. Necesitas crear un usuario y darle permisos sobre la base de datos.

---

## ✅ SOLUCIÓN: Crear Usuario en cPanel

### Paso 1: Crear el Usuario de MySQL

1. **Accede a cPanel** de tu hosting

2. **Ve a la sección "Bases de Datos MySQL"** o **"MySQL Databases"**

3. **Desplázate hasta la sección "Usuarios de MySQL"** o **"MySQL Users"**

4. **Crea un nuevo usuario:**
   - **Nombre de usuario**: Puede ser el mismo nombre que tu base de datos o diferente
     - Ejemplo: Si tu BD se llama `usuario_sophea`, el usuario puede ser `usuario_sophea` o `usuario_sophea_user`
   - **Contraseña**: 
     - Haz clic en "Generar contraseña" para crear una segura
     - O escribe una contraseña fuerte (mínimo 8 caracteres, con mayúsculas, números y símbolos)
     - **⚠️ IMPORTANTE: Anota esta contraseña, la necesitarás**
   - Haz clic en **"Crear usuario"** o **"Create User"**

---

### Paso 2: Asignar el Usuario a la Base de Datos

1. **En la misma página, busca la sección "Asignar Usuario a Base de Datos"** o **"Add User To Database"**

2. **Selecciona:**
   - **Usuario**: El que acabas de crear
   - **Base de datos**: Tu base de datos `sophea_db` (o el nombre que le diste)

3. Haz clic en **"Agregar"** o **"Add"**

4. **Selecciona los permisos:**
   - Marca **"ALL PRIVILEGES"** (todos los privilegios)
   - O al menos marca:
     - ✅ SELECT
     - ✅ INSERT
     - ✅ UPDATE
     - ✅ DELETE
     - ✅ CREATE
     - ✅ ALTER
     - ✅ INDEX

5. Haz clic en **"Hacer cambios"** o **"Make Changes"**

---

### Paso 3: Anotar las Credenciales Completas

Después de crear el usuario y asignarlo, deberías tener:

```
Base de datos: usuario_sophea (o el nombre que le diste)
Usuario: usuario_sophea_user (o el que creaste)
Contraseña: La que generaste o escribiste
Host: localhost (generalmente)
```

**⚠️ IMPORTANTE:** En muchos hostings, el nombre completo del usuario incluye un prefijo. Por ejemplo:
- Si tu usuario de cPanel es `miusuario`
- Y creaste un usuario MySQL llamado `sophea_user`
- El nombre completo del usuario será: `miusuario_sophea_user`

Lo mismo pasa con la base de datos:
- Si tu usuario de cPanel es `miusuario`
- Y creaste una BD llamada `sophea`
- El nombre completo será: `miusuario_sophea`

---

### Paso 4: Actualizar config_db.php

Ahora edita `config_db.php` en el servidor con las credenciales que acabas de crear:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'miusuario_sophea');        // ← Nombre completo de tu BD
define('DB_USER', 'miusuario_sophea_user');   // ← Nombre completo de tu usuario
define('DB_PASS', 'TuPassword123!');          // ← La contraseña que creaste
define('DB_CHARSET', 'utf8mb4');
```

---

## 🔍 Verificar las Credenciales

### Opción 1: En cPanel

1. Ve a **"Bases de Datos MySQL"**
2. Busca tu base de datos en la lista
3. Verás el usuario asignado junto a ella

### Opción 2: En phpMyAdmin

1. Accede a **phpMyAdmin** desde cPanel
2. En la parte superior izquierda verás:
   - El nombre del usuario con el que estás conectado
   - Las bases de datos disponibles
3. Si puedes ver tu base de datos `sophea_db`, el usuario que aparece es el que debes usar

### Opción 3: Probar la Conexión

1. Sube `test_db_config.php` a tu servidor
2. Accede a `https://tudominio.com/test_db_config.php`
3. Te mostrará si la conexión funciona

---

## 📝 Ejemplo Completo

**Escenario:**
- Usuario de cPanel: `miusuario`
- Base de datos creada: `sophea`
- Usuario MySQL creado: `sophea_user`
- Contraseña generada: `Abc123Xyz!`

**Credenciales completas:**
```
DB_HOST: localhost
DB_NAME: miusuario_sophea
DB_USER: miusuario_sophea_user
DB_PASS: Abc123Xyz!
```

**config_db.php:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'miusuario_sophea');
define('DB_USER', 'miusuario_sophea_user');
define('DB_PASS', 'Abc123Xyz!');
```

---

## ⚠️ Problemas Comunes

### Problema 1: "Usuario ya existe"

**Solución:** 
- Usa un nombre diferente para el usuario
- O elimina el usuario existente y créalo de nuevo

### Problema 2: No puedo asignar el usuario a la BD

**Solución:**
- Verifica que ambos existan (usuario y BD)
- Asegúrate de estar en la sección correcta de cPanel
- Intenta refrescar la página

### Problema 3: No sé cuál es mi usuario de cPanel

**Solución:**
- Generalmente es el nombre que aparece en la URL de cPanel
- O el email con el que te registraste
- O pregunta a tu proveedor de hosting

---

## 🆘 ¿Aún no funciona?

Si después de seguir estos pasos sigue sin funcionar:

1. **Contacta a tu proveedor de hosting**
   - Pregunta por las credenciales correctas
   - Pide ayuda para crear el usuario

2. **Verifica en phpMyAdmin**
   - Intenta iniciar sesión con las credenciales que creaste
   - Si funciona ahí, debería funcionar en el código

3. **Revisa los logs de errores**
   - En cPanel, ve a "Error Logs"
   - Busca mensajes relacionados con MySQL

