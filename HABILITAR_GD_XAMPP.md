# Habilitar Extensión GD en XAMPP

## Problema
Al intentar generar PDFs, aparece el error:
```
Error al generar el PDF: The PHP GD extension is required, but is not installed.
```

## Solución

### Paso 1: Localizar php.ini

En XAMPP, el archivo `php.ini` se encuentra en:
```
C:\xampp\php\php.ini
```

### Paso 2: Editar php.ini

1. Abre el archivo `php.ini` con un editor de texto (Notepad++, VS Code, etc.)
2. Busca la línea que contiene `extension=gd` (usa Ctrl+F para buscar)
3. Si la línea está comentada (tiene un `;` al inicio), elimina el `;` para descomentarla

**Busca esta línea:**
```ini
;extension=gd
```

**Cámbiala a:**
```ini
extension=gd
```

### Paso 3: Reiniciar Apache

1. Abre el **XAMPP Control Panel**
2. Detén Apache (Stop)
3. Inicia Apache nuevamente (Start)

### Paso 4: Verificar que GD está habilitado

Crea un archivo temporal `test_gd.php` en la raíz del proyecto:

```php
<?php
if (extension_loaded('gd')) {
    echo "✅ GD está habilitado";
    echo "<br>Versión: " . gd_info()['GD Version'];
} else {
    echo "❌ GD NO está habilitado";
}
phpinfo();
?>
```

Accede a: `http://localhost/sopheaadmin/test_gd.php`

Si ves "✅ GD está habilitado", entonces está funcionando correctamente.

### Paso 5: Eliminar archivo de prueba

Una vez verificado, elimina el archivo `test_gd.php` por seguridad.

## Nota Importante

Si después de seguir estos pasos el error persiste:

1. Verifica que estás editando el `php.ini` correcto. XAMPP puede tener múltiples archivos php.ini.
2. Para confirmar qué php.ini está usando Apache, crea un archivo `phpinfo.php`:
   ```php
   <?php phpinfo(); ?>
   ```
   Y busca la línea "Loaded Configuration File" en la salida.

3. Asegúrate de reiniciar Apache después de cualquier cambio en php.ini.

