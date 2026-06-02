# Análisis de Depuración - SOPHEA Admin

**Fecha:** 2024  
**Objetivo:** Identificar archivos PHP, tablas de base de datos y recursos que ya no se usan o están obsoletos

---

## 📋 Resumen Ejecutivo

Este documento identifica:
- ✅ Archivos PHP que se pueden eliminar o mover
- ✅ Archivos PHP que se mantienen por compatibilidad
- ✅ Tablas de base de datos en uso
- ✅ Archivos SQL duplicados o obsoletos
- ✅ Archivos de test/diagnóstico

---

## 🔴 ARCHIVOS PHP - CATEGORIZACIÓN

### ✅ **MANTENER - Archivos Activos en el Sistema**

#### Panel de Administración Principal
- `admin.php` - Panel principal de leads (ACTIVO)
- `admin_dashboard.php` - Dashboard de gestión (ACTIVO)
- `admin_web.php` - Panel unificado de Blog/Banner/Testimonios (ACTIVO)
- `admin_tools.php` - Panel unificado de Herramientas/Configuración (ACTIVO)
- `admin_whatsapp_marketing.php` - Panel de marketing WhatsApp (ACTIVO)

#### Archivos de Procesamiento (Mantener por compatibilidad)
- `admin_blog.php` - **MANTENER** - Procesa formularios de blog (usado por `admin_web.php`)
- `admin_banner.php` - **MANTENER** - Procesa formularios de banner/logo (usado por `admin_web.php`)
- `admin_testimonials.php` - **MANTENER** - Procesa formularios de testimonios (usado por `admin_web.php`)
- `admin_whatsapp_config.php` - **MANTENER** - Procesa configuración WhatsApp (usado por `admin_tools.php`)

#### Includes/Componentes
- `includes/admin_header.php` - Header unificado (ACTIVO)
- `includes/admin_sidebar.php` - Sidebar unificado (ACTIVO)
- `includes/admin_footer.php` - Footer unificado (ACTIVO)
- `includes/admin_web_blog_tab.php` - Tab de blog (ACTIVO)
- `includes/admin_web_banner_tab.php` - Tab de banner (ACTIVO)
- `includes/admin_web_testimonials_tab.php` - Tab de testimonios (ACTIVO)
- `includes/admin_tools_whatsapp_tab.php` - Tab de WhatsApp config (ACTIVO)
- `includes/admin_tools_tests_tab.php` - Tab de tests (ACTIVO)

#### Clases PHP
- `classes/Auth.php` - Sistema de autenticación (ACTIVO)
- `classes/Database.php` - Conexión a BD (ACTIVO)
- `classes/Blog.php` - Gestión de blog (ACTIVO)
- `classes/SiteSettings.php` - Configuración del sitio (ACTIVO)
- `classes/Testimonials.php` - Gestión de testimonios (ACTIVO)
- `classes/Client.php` - Gestión de clientes (ACTIVO)
- `classes/Quote.php` - Gestión de cotizaciones (ACTIVO)
- `classes/Service.php` - Gestión de servicios (ACTIVO)
- `classes/Payment.php` - Gestión de pagos (ACTIVO)
- `classes/DailyTask.php` - Gestión de tareas (ACTIVO)
- `classes/WhatsAppAPI.php` - API de WhatsApp (ACTIVO)
- `classes/WhatsAppMarketing.php` - Marketing WhatsApp (ACTIVO)
- `classes/SchemaGenerator.php` - Generador de Schema.org (ACTIVO)

#### Configuración
- `config.php` - Configuración general (ACTIVO)
- `config_db.php` - Configuración de BD (ACTIVO)
- `config_whatsapp.php` - Configuración WhatsApp (ACTIVO)

#### Frontend Público
- `index.php` - Página principal (ACTIVO)
- `blog.php` - Listado de blog (ACTIVO)
- `post.php` - Detalle de post (ACTIVO)
- `testimonials.php` - Listado de testimonios (ACTIVO)
- `testimonial.php` - Detalle de testimonio (ACTIVO)
- `servicios.php` - Página de servicios (ACTIVO)
- `aviso_privacidad.php` - Aviso de privacidad (ACTIVO)
- `politica_cookies.php` - Política de cookies (ACTIVO)
- `header.php` - Header público (ACTIVO)
- `footer.php` - Footer público (ACTIVO)
- `sitemap.php` - Generador de sitemap (ACTIVO)
- `components/cookie_banner.php` - Banner de cookies (ACTIVO)

#### Webhooks y APIs
- `webhook_whatsapp.php` - Webhook de WhatsApp (ACTIVO)
- `send_whatsapp.php` - Envío de WhatsApp (ACTIVO)
- `process_form.php` - Procesamiento de formularios (ACTIVO)

#### Utilidades
- `admin_change_password.php` - Cambio de contraseña (ACTIVO)
- `generate_long_lived_token.php` - Generador de tokens (ACTIVO)

---

### ⚠️ **EVALUAR - Archivos de Test/Diagnóstico**

Estos archivos son útiles para desarrollo pero podrían moverse a una carpeta `tests/` o eliminarse en producción:

#### Tests de Sistema
- `test_webhook.php` - Test de webhook WhatsApp
- `test_send_whatsapp.php` - Test de envío WhatsApp
- `test_db_connection.php` - Test de conexión BD
- `test_db_config.php` - Test de configuración BD
- `test_testimonials.php` - Test de testimonios

**Recomendación:** 
- ✅ **MANTENER** - Son accesibles desde `admin_tools.php?tab=tests`
- Considerar mover a carpeta `tests/` para mejor organización

#### Diagnósticos
- `diagnostic.php` - Diagnóstico general
- `debug_testimonials.php` - Debug de testimonios
- `debug_csrf.php` - Debug de CSRF
- `check_production.php` - Verificación de producción
- `verificar_credenciales.php` - Verificación de credenciales

**Recomendación:**
- ⚠️ **EVALUAR** - Si no se usan, mover a `tests/` o eliminar

---

### 🟡 **EVALUAR - Archivos de Setup/Migración**

Estos archivos se ejecutan una vez y luego pueden archivarse:

#### Setup Inicial
- `setup_admin_user.php` - Crear usuario admin inicial
- `setup_whatsapp_marketing_db.php` - Setup de BD WhatsApp Marketing
- `check_admin_users.php` - Verificar usuarios admin
- `reset_admin_password.php` - Reset de contraseña admin

**Recomendación:**
- ✅ **MANTENER** - Útiles para mantenimiento y recuperación
- Considerar mover a carpeta `setup/` o `maintenance/`

#### Migraciones
- `database/run_migration.php` - Ejecutor de migraciones

**Recomendación:**
- ✅ **MANTENER** - Útil para futuras migraciones

---

### 🔴 **CANDIDATOS A ELIMINAR - Archivos Obsoletos**

#### Archivos de Importación/Migración de Datos (Ya ejecutados)
- `create_blog_posts.php` - Crear posts de ejemplo
- `check_posts_created.php` - Verificar posts creados
- `verify_posts.php` - Verificar posts
- `test_create_posts.php` - Test de creación de posts
- `import_blog_posts.php` - Importar posts
- `execute_import.php` - Ejecutar importación

**Recomendación:**
- 🔴 **ELIMINAR** - Ya cumplieron su función

#### Archivos de Ejemplo/Documentación
- `EJEMPLO_USO_SCHEMA.php` - Ejemplo de uso de Schema

**Recomendación:**
- ⚠️ **EVALUAR** - Mover a carpeta `examples/` o eliminar si no se necesita

#### Secciones del Frontend
- `sections/casos.php` - Sección de casos ✅ **MANTENER** (usado en `index.php`)
- `sections/contacto.php` - Sección de contacto ✅ **MANTENER** (usado en `index.php` y `servicios.php`)
- `sections/servicios.php` - Sección de servicios ✅ **MANTENER** (usado en `index.php`)

**Recomendación:**
- ✅ **MANTENER** - Archivos activos en el frontend

#### Helpers
- `admin_auth_helper.php` - Helper de autenticación ✅ **MANTENER** (usado en varios archivos)

**Recomendación:**
- ✅ **MANTENER** - Usado en:
  - `admin_banner.php`
  - `admin_change_password.php`
  - `test_testimonials.php`
  - `test_webhook.php`
  - `test_send_whatsapp.php`
  - `test_db_connection.php`
  - `test_db_config.php`

#### Archivos de Error
- `404.php` - Página 404
- `405.php` - Página 405
- `500.php` - Página 500

**Recomendación:**
- ✅ **MANTENER** - Útiles para manejo de errores

---

## 🗄️ TABLAS DE BASE DE DATOS

### ✅ **TABLAS ACTIVAS - En Uso**

#### Sistema de Leads
- `leads` - Leads del formulario de contacto ✅
- `email_log` - Log de emails enviados ✅
- `login_attempts` - Intentos de login (seguridad) ✅

#### Sistema de Autenticación
- `admin_users` - Usuarios administradores ✅

#### Sistema de Blog
- `blog_posts` - Posts del blog ✅
- `blog_categories` - Categorías del blog ✅
- `blog_post_categories` - Relación posts-categorías ✅

#### Sistema de Testimonios
- `testimonials` - Testimonios ✅
- `testimonial_images` - Imágenes de testimonios ✅

#### Sistema de Configuración
- `site_settings` - Configuración del sitio ✅

#### Sistema de Clientes y Cotizaciones
- `clients` - Clientes ✅
- `quotes` - Cotizaciones ✅
- `quote_items` - Items de cotizaciones ✅
- `services` - Servicios activos ✅
- `service_tasks` - Tareas de servicios ✅
- `payments` - Pagos ✅
- `documents` - Documentos adjuntos ✅
- `daily_tasks` - Tareas diarias ✅
- `client_notes` - Notas de clientes ✅

#### Sistema de WhatsApp Marketing
- `whatsapp_campaigns` - Campañas de marketing ✅
- `whatsapp_campaign_recipients` - Destinatarios de campañas ✅
- `whatsapp_credits` - Créditos de WhatsApp ✅
- `whatsapp_templates_custom` - Plantillas personalizadas ✅
- `whatsapp_automation_rules` - Reglas de automatización ✅
- `whatsapp_automation_log` - Log de automatización ✅
- `whatsapp_contact_lists` - Listas de contactos ✅
- `whatsapp_contact_list_members` - Miembros de listas ✅
- `whatsapp_lead_tags` - Tags de leads ✅
- `whatsapp_lead_tag_assignments` - Asignación de tags ✅
- `whatsapp_message_log` - Log de mensajes ✅
- `whatsapp_ab_tests` - Tests A/B ✅
- `whatsapp_scheduled_jobs` - Trabajos programados ✅

#### Sistema de Mensajes (Legacy)
- `whatsapp_messages` - Mensajes de WhatsApp (legacy) ⚠️

**Recomendación:**
- ⚠️ **EVALUAR** - Parece ser legacy, pero está referenciado en:
  - `send_whatsapp.php` (comentario)
  - `webhook_whatsapp.php` (comentario)
  - `check_production.php` (verificación)
  - Scripts SQL de migración
- **Acción:** Verificar si realmente se usa o si fue completamente reemplazado por `whatsapp_message_log`
- Si no se usa, considerar eliminarla después de verificar que no hay datos importantes

---

## 📁 ARCHIVOS SQL - CATEGORIZACIÓN

### ✅ **MANTENER - Schemas Principales**

- `database/schema.sql` - Schema base (leads, admin_users) ✅
- `database/schema_production.sql` - Schema de producción ✅
- `database/auth_schema.sql` - Schema de autenticación ✅
- `database/blog_schema.sql` - Schema de blog ✅
- `database/testimonials_schema.sql` - Schema de testimonios ✅
- `database/site_settings_schema.sql` - Schema de configuración ✅
- `database/clients_quotes_schema.sql` - Schema de clientes/cotizaciones ✅
- `database/whatsapp_marketing_schema.sql` - Schema de WhatsApp Marketing ✅

### ⚠️ **EVALUAR - Migraciones y Scripts Temporales**

#### Migraciones
- `database/migrate_production_2024.sql` - Migración completa ✅ **MANTENER** (referencia)
- `database/migrate_to_production.sql` - Migración a producción ⚠️ **EVALUAR** (¿duplicado?)

#### Scripts de Vistas
- `database/CREAR_VISTA_AHORA.sql` - Crear vista de campañas ⚠️
- `database/CREAR_VISTA_COMPATIBLE.sql` - Vista compatible ⚠️
- `database/create_view_alternative.sql` - Vista alternativa ⚠️
- `database/create_view_now.sql` - Crear vista ahora ⚠️
- `database/test_view_campaigns.sql` - Test de vista ⚠️
- `database/VISTA_CAMPAÑAS_ACTIVAS.sql` - Vista de campañas activas ⚠️
- `database/fix_view_campaigns.sql` - Fix de vista ⚠️

**Recomendación:**
- 🔴 **CONSOLIDAR** - Muchos scripts hacen lo mismo (crear `v_whatsapp_active_campaigns`)
- Mantener solo el script final que funciona
- Eliminar los scripts de prueba/temporales

---

## 📊 RESUMEN DE RECOMENDACIONES

### ✅ **ELIMINADOS** (2024)

1. **Archivos de importación ya ejecutados:** ✅ ELIMINADOS
   - ~~`create_blog_posts.php`~~ ✅
   - ~~`check_posts_created.php`~~ ✅
   - ~~`verify_posts.php`~~ ✅
   - ~~`test_create_posts.php`~~ ✅
   - ~~`import_blog_posts.php`~~ ✅
   - ~~`execute_import.php`~~ ✅

2. **Scripts SQL duplicados/temporales:** ✅ ELIMINADOS
   - ~~`database/CREAR_VISTA_AHORA.sql`~~ ✅
   - ~~`database/CREAR_VISTA_COMPATIBLE.sql`~~ ✅
   - ~~`database/create_view_alternative.sql`~~ ✅
   - ~~`database/create_view_now.sql`~~ ✅
   - ~~`database/test_view_campaigns.sql`~~ ✅
   - ~~`database/VISTA_CAMPAÑAS_ACTIVAS.sql`~~ ✅
   - ~~`database/fix_view_campaigns.sql`~~ ✅

### ⚠️ **EVALUAR ANTES DE ELIMINAR**

1. **Archivos de diagnóstico:**
   - `diagnostic.php`
   - `debug_testimonials.php`
   - `debug_csrf.php`
   - `check_production.php`
   - `verificar_credenciales.php`

2. **Tabla legacy:**
   - `whatsapp_messages` - Verificar si se usa o fue reemplazada por `whatsapp_message_log`

### ✅ **ORGANIZADOS** (2024)

1. **Movidos a carpeta `tests/`:** ✅ COMPLETADO
   - `tests/test_webhook.php` ✅
   - `tests/test_send_whatsapp.php` ✅
   - `tests/test_db_connection.php` ✅
   - `tests/test_db_config.php` ✅
   - `tests/test_testimonials.php` ✅
   - **Nota:** Rutas actualizadas a `../` para acceder a archivos del directorio raíz
   - **Referencias actualizadas en:** `admin.php`, `admin_testimonials.php`, `includes/admin_tools_tests_tab.php`

2. **Movidos a carpeta `setup/`:** ✅ COMPLETADO
   - `setup/setup_admin_user.php` ✅
   - `setup/setup_whatsapp_marketing_db.php` ✅
   - `setup/check_admin_users.php` ✅
   - `setup/reset_admin_password.php` ✅
   - **Nota:** Rutas actualizadas a `../` para acceder a archivos del directorio raíz

3. **Movido a carpeta `examples/`:** ✅ COMPLETADO
   - `examples/EJEMPLO_USO_SCHEMA.php` ✅

---

## 🎯 PLAN DE ACCIÓN SUGERIDO

### Fase 1: Verificación (Antes de eliminar)
1. Buscar referencias a archivos candidatos a eliminación
2. Verificar uso de tablas legacy
3. Confirmar que los scripts SQL duplicados ya no se necesitan

### Fase 2: Organización
1. Crear carpetas: `tests/`, `setup/`, `examples/`
2. Mover archivos según categoría
3. Actualizar referencias si es necesario

### Fase 3: Limpieza
1. Eliminar archivos de importación ya ejecutados
2. Eliminar scripts SQL duplicados
3. Eliminar archivos obsoletos confirmados

### Fase 4: Documentación
1. Actualizar README con nueva estructura
2. Documentar archivos en carpetas de utilidades

---

## 📝 NOTAS IMPORTANTES

⚠️ **ANTES DE ELIMINAR CUALQUIER ARCHIVO:**
1. Hacer backup completo del proyecto
2. Verificar que no hay referencias en el código
3. Probar en entorno de desarrollo primero
4. Documentar qué se eliminó y por qué

✅ **ARCHIVOS QUE NUNCA DEBEN ELIMINARSE:**
- Archivos de configuración (`config*.php`)
- Clases principales (`classes/*.php`)
- Includes activos (`includes/*.php`)
- Archivos de procesamiento usados por `admin_web.php` y `admin_tools.php`

---

**Última actualización:** 2024  
**Próxima revisión:** Después de implementar cambios

