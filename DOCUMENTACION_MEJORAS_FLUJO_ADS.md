# 📊 Documentación: Mejoras al Flujo de Servicios e Inversiones ADS

**Fecha:** 2026-06-02  
**Versión de Implementación:** 3.0  
**Autor:** Antigravity (AI Coding Assistant)  
**Propósito:** Detallar los cambios y la arquitectura del sistema de desglose manual entre Honorarios de Gestión (Fee) e Inversión Publicitaria (Ads) en el flujo de cotizaciones, servicios, pagos y recibos.

---

## 📋 Contexto y Problemática Anterior

Anteriormente, el sistema de cotizaciones no distinguía explícitamente entre el cobro por **honorarios** (lo que ingresa al negocio) y la **inversión para pauta** (que va a Meta, Google, etc.). Al convertir una cotización a servicio:
1. El sistema intentaba descifrar mediante palabras clave (heurísticas) qué parte era honorarios y qué parte era inversión, lo cual era propenso a errores.
2. Se generaba un cargo pendiente automático por el total de manera incondicional, dejando al cliente con deuda inmediata cuando tal vez la pauta se pagaba directamente en la plataforma.
3. Al editar un pago desde `admin_payments.php`, se perdía el desglose de transacciones en la tabla `project_transactions`.
4. El recibo en PDF/HTML no desglosaba los montos cobrados en conceptos separados, mostrando un cobro único e ilegible para el cliente.

---

## ⚙️ Cambios Realizados y Arquitectura

### 1. Base de Datos (`database/add_item_type_to_quote_items.sql`)
Se agregaron nuevos campos a la tabla `quote_items` para permitir una clasificación de conceptos desde el origen:
* **`item_type`**: ENUM(`fee`, `ads_investment`) - Determina si el ítem es un honorario de servicio o un fondo de inversión para plataformas de anuncios.
* **`ads_platform`**: VARCHAR(50) - Plataforma de destino (Meta, Google, TikTok, etc.), aplicable cuando `item_type` es `ads_investment`.

### 2. Clases PHP Modificadas
* **`classes/Quote.php`**: 
  * Modificado `addQuoteItems` para soportar e insertar los campos `item_type` y `ads_platform` en la base de datos al guardar o actualizar cotizaciones.
* **`classes/Payment.php`**:
  * **`updatePayment`**: Al actualizar un pago correspondiente a un servicio de Ads, ahora elimina las transacciones previas asociadas en la tabla `project_transactions` y ejecuta un re-split llamando a `splitPaymentIntoTransactions()` utilizando los nuevos valores `fee_amount` y `ads_amount`.
  * **`getPaymentById` y `getAllPayments`**: Modificados para calcular y devolver dinámicamente en tiempo real los campos virtuales `fee_amount` y `ads_amount` usando subconsultas correlacionadas sobre la tabla `project_transactions`:
    ```sql
    COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_fee'), 0) as fee_amount,
    COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_ads'), 0) as ads_amount
    ```
* **`classes/Invoice.php`**:
  * Modificado para que, al procesar los pagos de un servicio Ads, si detecta valores de `fee_amount > 0` y `ads_amount > 0`, se generen **dos filas independientes en el recibo**:
    * **Honorario de Servicio / Feed** (con su estado correspondiente).
    * **Inversión en Plataforma ADS** (con su estado correspondiente).

### 3. Vistas y Controladores (Panel Admin)
* **`admin_quotes.php`**:
  * Integración en el formulario de ítems de cotización de un selector de concepto ("Honorario de manejo" vs "Inversión en plataforma") y un selector de plataforma (Meta, Google, TikTok, LinkedIn, etc.).
  * Comportamiento dinámico (JavaScript) para mostrar/ocultar estos campos exclusivos de Ads únicamente cuando el tipo de servicio es un servicio publicitario.
* **`admin_client_detail.php`**:
  * **Proceso de Conversión**: Se actualizó la lógica PHP de `convert_quote_to_service` para leer directamente los nuevos campos `item_type` en lugar de aplicar heurísticas sobre las descripciones de texto.
  * **Modal de Confirmación**: Se reemplazó el submit directo de conversión por un modal de confirmación en JavaScript/HTML. Permite al administrador decidir si se generará un pago/cargo pendiente inicial y si éste debe incluir los fondos de la inversión ADS o solo los honorarios del servicio.
* **`admin_payments.php`**:
  * Modificado el handler PHP para pasar los valores `fee_amount` y `ads_amount` al actualizar pagos.
  * Actualización de la función JS `loadPaymentServices()` para aceptar parámetros opcionales de pre-selección de servicio e importación de montos fee y ads.
  * El script del modal de edición de pagos ahora precarga y muestra de manera correcta los inputs del desglose si el pago que se está editando pertenece a un servicio Ads.

---

## 🚀 Proceso de Despliegue en Servidores

### Comandos de Actualización de Código (Git):
```bash
# 1. Moverse a la carpeta raíz del proyecto en producción
cd /home/sopheamkt.com/public_html

# 2. Descargar los cambios más recientes desde GitHub
git fetch origin
git reset --hard origin/master
```

### Comandos de Base de Datos:
Ejecutar el archivo de migración en la base de datos de producción:
```bash
mysql -u [usuario_db] -p [base_de_datos] < database/add_item_type_to_quote_items.sql
```
