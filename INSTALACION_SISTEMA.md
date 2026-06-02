# Instalación del Sistema de Gestión de Clientes y Cotizaciones

## Paso 1: Ejecutar el Script SQL

Antes de usar el dashboard, necesitas crear las tablas en la base de datos.

### Opción A: Via phpMyAdmin (Recomendado)

1. Abre phpMyAdmin en tu navegador
2. Selecciona la base de datos `sophea_db` (o la que uses)
3. Ve a la pestaña "Importar"
4. Selecciona el archivo `database/clients_quotes_schema.sql`
5. Haz clic en "Continuar"

### Opción B: Via Línea de Comandos

```bash
mysql -u tu_usuario -p sophea_db < database/clients_quotes_schema.sql
```

## Paso 2: Verificar la Configuración

Asegúrate de que `config_db.php` tenga las credenciales correctas:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sophea_db');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

## Paso 3: Acceder al Dashboard

1. Inicia sesión en `admin.php`
2. Una vez autenticado, serás redirigido a `admin_dashboard.php`

## Notas Importantes

- Si las tablas no existen, el dashboard mostrará mensajes indicando que la base de datos no está configurada
- El sistema manejará errores gracefully, mostrando estados vacíos en lugar de errores fatales
- La advertencia de Tailwind CSS en la consola es normal en desarrollo (no afecta la funcionalidad)

## Solución de Problemas

### Error: "Base de datos no configurada"
- Ejecuta el script SQL primero (Paso 1)

### Error: "Could not establish connection"
- Este es un error de extensión del navegador, no del código
- Puedes ignorarlo o desactivar extensiones que interfieran

### Error: "Cannot redeclare class"
- Limpia el caché de PHP
- Verifica que no estés incluyendo archivos dos veces

