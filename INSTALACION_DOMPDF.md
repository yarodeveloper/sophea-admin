# Instalación de DomPDF para Generación de Recibos

## Opción 1: Instalación con Composer (Recomendado)

Si tienes Composer instalado, ejecuta desde la raíz del proyecto:

```bash
composer require dompdf/dompdf
```

Esto creará automáticamente:
- `vendor/autoload.php`
- `vendor/dompdf/dompdf/`

## Opción 2: Instalación Manual

1. Descarga DomPDF desde: https://github.com/dompdf/dompdf/releases
2. Extrae el archivo en la carpeta `vendor/dompdf/dompdf`
3. Asegúrate de que la estructura sea: `vendor/dompdf/dompdf/src/Dompdf.php`
4. Si no tienes Composer, también necesitarás instalar las dependencias de DomPDF:
   - `phenx/php-font-lib`
   - `phenx/php-svg-lib`
   - `sabberworm/php-css-parser`

## Verificación

Después de la instalación, verifica que el archivo existe:
- `vendor/dompdf/dompdf/src/Dompdf.php`
- `vendor/autoload.php` (si usas Composer)

## Uso

Una vez instalado, el sistema automáticamente detectará DomPDF y permitirá:
- Generar PDFs desde el botón "Descargar PDF" en los modales de factura
- Descargar recibos en formato PDF profesional

## Notas

- DomPDF requiere PHP 7.1 o superior
- Para imágenes remotas, asegúrate de que `allow_url_fopen` esté habilitado en PHP
- El logo de la empresa se cargará desde la URL configurada en SiteSettings

