# 🚀 PLAN MAESTRO ACTIVO - SOPHEA ADMIN
**IMPORTANTE PARA LA IA:** ¡LEE ESTE ARCHIVO AL INICIAR CUALQUIER NUEVA SESIÓN! ESTA ES LA ÚNICA FUENTE DE VERDAD SOBRE EL ROADMAP ACTUAL.

## 🎯 Plan de Refactorización y Mejora del Sistema SOPHEA

Este documento detalla el plan de acción estructurado en 4 fases para implementar las mejoras técnicas y arquitectónicas en el panel de administración.

### Fase 1: Arquitectura y Reutilización de Código (DRY & Layouts) ✅ COMPLETADA
- Centralización de catálogos y diccionarios (`AppConstants.php`).
- Sistema unificado de Layouts para todo el proyecto (`includes/layout_start.php` y `layout_end.php`).

### Fase 2: Tipos de Servicio Administrables (Base de Datos) ✅ COMPLETADA
Actualmente los "Tipos de Servicios" están fijos en código dentro de la clase `AppConstants.php`. 
- **Objetivo:** Hacer que sean administrables dinámicamente desde la interfaz mediante una tabla de base de datos.
- **Acciones Completadas:**
  - [x] Crear la tabla `service_types` en la base de datos (id, slug, name, is_active, display_order).
  - [x] Migrar los tipos de servicios actuales a esta tabla.
  - [x] Refactorizar `AppConstants::getServiceTypes()` para que lea directamente desde la base de datos (con caché estático).
  - [x] Crear una sección de UI (probablemente en la pestaña "Herramientas y Configuración") para hacer el CRUD de Tipos de Servicio.

### Fase 3: Experiencia de Usuario (UX) e Interfaz ⏳ POSPUESTA
- **Objetivo:** Implementar mejoras de UI/UX para evitar recargas completas de la página que entorpecen la navegación.
- **Acciones Pospuestas (Plan de Implementación Propuesto):**
  - [ ] Crear un sistema global de notificaciones tipo "Toast" en `layout_end.php`.
  - [ ] Crear el archivo `api/admin_tools_api.php` para centralizar la lógica de guardado asíncrono.
  - [ ] Interceptar con JavaScript los formularios en `admin_tools.php` (Tipos de Servicio, Catálogo, WhatsApp) para enviar los datos vía API Fetch (AJAX) sin "flash" ni recarga completa de la pantalla.
  - [ ] Actualizar el DOM y el HTML de las tablas usando JS para reflejar cambios (nuevos registros, eliminaciones) de inmediato.

### Fase 4: Mejoras en Cálculos de Ingresos y Adeudos (Facturación) ⏳ PENDIENTE
- **Objetivo:** Mejorar y optimizar la forma en la que se calculan los ingresos y adeudos en cotizaciones y servicios.
- **Acciones Pendientes:**
  - [ ] Eliminar los cálculos "al vuelo" que dependen de lógicas frágiles.
  - [ ] Implementar un registro sólido y separado de cargos mensuales y partidas contables (facturas y recibos).
  - [ ] Generación precisa de PDFs con información detallada de abonos y saldos pendientes reales.

---
*Última actualización: Junio 2026*
