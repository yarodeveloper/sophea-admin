# 📊 Propuesta: Sistema de Gestión de Clientes, Cotizaciones y Servicios - SOPHEA
## Versión 2.0 - Basada en Diseño Personalizado

## 🎯 Objetivo

Crear un sistema completo de gestión empresarial con diseño moderno y oscuro que permita:
- ✅ Dashboard principal con métricas y resúmenes
- ✅ Gestión completa de clientes
- ✅ Crear y gestionar cotizaciones
- ✅ Administrar servicios activos por cliente
- ✅ Controlar ingresos, pagos realizados y pendientes
- ✅ Adjuntar documentos (cotizaciones, contratos)
- ✅ Enlazar proyectos externos (Canva, etc.)
- ✅ Vista detallada de cliente con todos sus servicios y pagos

---

## 🎨 Diseño de Interfaz

### Tema Visual
- **Modo Oscuro**: Diseño principal con fondo oscuro (`#101c22`)
- **Color Primario**: Azul (`#13a4ec`)
- **Tipografía**: Manrope (Google Fonts)
- **Iconos**: Material Symbols Outlined
- **Framework**: Tailwind CSS con configuración personalizada

### Componentes de Diseño
- Cards con bordes sutiles y sombras
- Tablas con hover effects
- Badges de estado con colores semánticos
- Sidebar de navegación fija
- Layout responsive con grid system

---

## 📋 Estructura de Base de Datos

### 1. Tabla: `clients` (Clientes)

```sql
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'ID único: C-YYYY-XXX',
    company_name VARCHAR(255) NOT NULL COMMENT 'Nombre de la empresa',
    contact_name VARCHAR(255) NOT NULL COMMENT 'Nombre del contacto principal',
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    whatsapp VARCHAR(50),
    address TEXT COMMENT 'Dirección completa',
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'México',
    tax_id VARCHAR(50) COMMENT 'RFC o ID fiscal',
    website VARCHAR(255),
    industry VARCHAR(100) COMMENT 'Industria o sector',
    client_type ENUM('prospect', 'regular', 'strategic_partner') DEFAULT 'regular',
    legal_risk ENUM('low', 'medium', 'high') DEFAULT 'low',
    legal_compliance DECIMAL(5, 2) DEFAULT 100.00 COMMENT 'Porcentaje de cumplimiento legal',
    last_audit_date DATE NULL COMMENT 'Fecha de última auditoría',
    status ENUM('prospect', 'active', 'inactive', 'archived') DEFAULT 'prospect',
    notes TEXT COMMENT 'Notas generales del cliente',
    logo_url VARCHAR(500) COMMENT 'URL del logo del cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del admin que creó el cliente',
    INDEX idx_status (status),
    INDEX idx_client_number (client_number),
    INDEX idx_company_name (company_name),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Tabla: `quotes` (Cotizaciones)

```sql
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número único: COT-YYYY-MM-XXXX',
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Título de la cotización',
    description TEXT COMMENT 'Descripción general',
    subtotal DECIMAL(10, 2) DEFAULT 0.00,
    tax_rate DECIMAL(5, 2) DEFAULT 16.00 COMMENT 'IVA en porcentaje',
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'MXN',
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    valid_until DATE COMMENT 'Fecha de validez',
    sent_at DATETIME NULL,
    accepted_at DATETIME NULL,
    rejected_at DATETIME NULL,
    notes TEXT COMMENT 'Notas internas',
    terms_conditions TEXT COMMENT 'Términos y condiciones',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_quote_number (quote_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Tabla: `quote_items` (Items de Cotización)

```sql
CREATE TABLE IF NOT EXISTS quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL COMMENT 'Tipo de servicio',
    description TEXT NOT NULL COMMENT 'Descripción del item',
    quantity DECIMAL(10, 2) DEFAULT 1.00,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL COMMENT 'quantity * unit_price',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_quote_id (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. Tabla: `services` (Servicios Activos)

```sql
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    quote_id INT NULL COMMENT 'Cotización que originó este servicio',
    service_type ENUM(
        'redes_sociales', 
        'community_manager', 
        'diseno_web', 
        'ads', 
        'branding', 
        'chatbot', 
        'seo', 
        'content_marketing',
        'email_marketing',
        'consultoria_legal',
        'auditoria_datos',
        'otro'
    ) NOT NULL,
    service_name VARCHAR(255) NOT NULL COMMENT 'Nombre específico del servicio',
    description TEXT,
    project_description TEXT COMMENT 'Descripción completa del proyecto y alcance',
    monthly_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa mensual',
    setup_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa de configuración inicial',
    billing_cycle ENUM('monthly', 'quarterly', 'yearly', 'one_time') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL si es servicio continuo',
    renewal_date DATE NULL COMMENT 'Fecha de renovación para contratos mensuales',
    progress_percentage INT DEFAULT 0 COMMENT 'Porcentaje de avance del proyecto',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    project_url VARCHAR(500) COMMENT 'URL a Canva, Figma, etc.',
    legal_coverage TEXT COMMENT 'Información sobre cobertura de riesgo legal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_renewal_date (renewal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Tabla: `service_tasks` (Tareas del Proyecto)

```sql
CREATE TABLE IF NOT EXISTS service_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    is_completed BOOLEAN DEFAULT FALSE,
    due_date DATE NULL,
    completed_at DATETIME NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_service_id (service_id),
    INDEX idx_is_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. Tabla: `payments` (Pagos)

```sql
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_id INT NULL COMMENT 'Pago asociado a un servicio específico',
    quote_id INT NULL COMMENT 'Pago asociado a una cotización',
    invoice_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de factura: #XXXX',
    payment_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de pago único: PAY-YYYY-MM-XXXX',
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    payment_method ENUM('transfer', 'cash', 'card', 'check', 'other') DEFAULT 'transfer',
    payment_date DATE NOT NULL,
    due_date DATE NULL COMMENT 'Fecha de vencimiento si es pago pendiente',
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    paid_at DATETIME NULL,
    reference_number VARCHAR(100) COMMENT 'Número de referencia bancaria',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_id (service_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_due_date (due_date),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. Tabla: `documents` (Documentos Adjuntos)

```sql
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('client', 'quote', 'service', 'payment') NOT NULL,
    entity_id INT NOT NULL COMMENT 'ID del cliente, cotización, servicio o pago',
    document_type ENUM('quote', 'contract', 'invoice', 'receipt', 'confidentiality_agreement', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'Ruta relativa al archivo',
    file_size INT COMMENT 'Tamaño en bytes',
    mime_type VARCHAR(100),
    description TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. Tabla: `daily_tasks` (Tareas Diarias / Seguimiento)

```sql
CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    task_type ENUM('call', 'meeting', 'email', 'invoice', 'follow_up', 'other') DEFAULT 'follow_up',
    related_client_id INT NULL,
    related_service_id INT NULL,
    due_date DATE NOT NULL,
    due_time TIME NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at DATETIME NULL,
    priority ENUM('low', 'normal', 'urgent') DEFAULT 'normal',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (related_client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (related_service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_due_date (due_date),
    INDEX idx_is_completed (is_completed),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 9. Tabla: `client_notes` (Notas de Cliente)

```sql
CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    note_text TEXT NOT NULL,
    note_type ENUM('general', 'call', 'meeting', 'email', 'task', 'alert') DEFAULT 'general',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🏗️ Estructura de Archivos

```
sopheaadmin/
├── admin_dashboard.php          # Dashboard principal (Panel de Control)
├── admin_clients.php            # Listado de clientes
├── admin_client_detail.php      # Vista detallada de cliente
├── admin_quotes.php             # Gestión de cotizaciones
├── admin_services.php           # Gestión de servicios
├── admin_payments.php           # Gestión de pagos
├── admin_daily_tasks.php        # Gestión de tareas diarias
├── classes/
│   ├── Client.php              # Clase para gestión de clientes
│   ├── Quote.php               # Clase para cotizaciones
│   ├── Service.php              # Clase para servicios
│   ├── Payment.php              # Clase para pagos
│   └── DailyTask.php           # Clase para tareas diarias
├── includes/
│   ├── admin_header.php         # Header del panel admin
│   ├── admin_sidebar.php        # Sidebar de navegación
│   └── admin_footer.php         # Footer del panel admin
├── api/
│   ├── clients_api.php          # API REST para clientes
│   ├── quotes_api.php           # API REST para cotizaciones
│   └── payments_api.php         # API REST para pagos
├── uploads/
│   ├── documents/               # Documentos adjuntos
│   │   ├── quotes/              # Cotizaciones PDF
│   │   ├── contracts/           # Contratos
│   │   └── invoices/            # Facturas
│   └── client_logos/            # Logos de clientes
└── database/
    └── clients_quotes_schema.sql # Script SQL completo
```

---

## 🎨 Páginas y Diseño

### 1. Dashboard Principal (`admin_dashboard.php`)

**Basado en: `panel admin.html`**

#### Sidebar de Navegación (Izquierda)
- Logo y perfil de admin
- Menú de navegación:
  - 🏠 Panel de Control (activo)
  - 👥 Clientes
  - 📄 Cotizaciones
  - 💰 Facturación
  - ⚙️ Configuración
- Botón de cerrar sesión

#### Contenido Principal

**Header:**
- Título: "Panel de Gestión"
- Subtítulo: "Bienvenido de nuevo, revisa el estado de tus clientes y cotizaciones."
- Botón de notificaciones
- Botón: "+ Nueva Cotización" (azul primario)

**Cards de Métricas (3 columnas):**
1. **Clientes Activos**
   - Número: 24
   - Indicador: "+2 mes" (verde, trending up)
   - Icono: groups (azul)

2. **Cotizaciones Pendientes**
   - Número: 8
   - Texto: "Valor est: $12.5k"
   - Icono: pending_actions (naranja)

3. **Tasa de Cierre**
   - Porcentaje: 65%
   - Indicador: "+5%" (verde, arrow up)
   - Icono: analytics (morado)

**Layout Grid (2 columnas principales + sidebar):**

**Columna Izquierda (2/3):**

1. **Tabla: Cotizaciones Recientes**
   - Header con filtros: "Todas", "Pendientes", "Aprobadas"
   - Columnas:
     - Cliente (con avatar circular con iniciales)
     - Servicio (con icono)
     - Fecha Envío
     - Monto
     - Estado (badge con color)
   - Footer: "Ver todas las cotizaciones →"

2. **Cartera de Clientes Activos**
   - Grid de 2 columnas con cards de clientes
   - Cada card muestra:
     - Logo del cliente
     - Nombre de la empresa
     - Tipo de contrato y fecha de renovación
     - Tags de servicios (SMM, ADS, WEB, etc.)
     - Botón de menú (3 puntos verticales)

**Columna Derecha (1/3):**

1. **Seguimiento Diario**
   - Lista de tareas con checkboxes
   - Cada tarea muestra:
     - Nombre de la tarea
     - Descripción
     - Fecha/hora (con colores según urgencia)
   - Botón: "+ Añadir Tarea"

2. **Objetivo Mensual**
   - Card con gradiente azul
   - Texto motivacional
   - Barra de progreso
   - Montos: actual vs meta

---

### 2. Vista Detallada de Cliente (`admin_client_detail.php`)

**Basado en: `detalle cliente.html`**

#### Header Superior
- Logo "Sophea Internal" con icono de seguridad
- Barra de búsqueda: "Buscar cliente o proyecto..."
- Icono de notificaciones (con badge rojo)
- Avatar del usuario

#### Header del Cliente
- **Nombre del Cliente**: Título grande
- **Badge de Estado**: "Socio Estratégico" (verde) o "Cliente Regular"
- **Información secundaria**:
  - ID: C-2024-889
  - Riesgo Legal: Bajo (con icono de escudo)
- **Botones de acción**:
  - "Enviar Factura" (gris)
  - "+ Añadir Servicio" (azul primario)

#### Cards de Resumen (4 columnas)
1. **Total Pendiente**
   - Monto: $4,500.00
   - Icono: payments

2. **Proyectos Activos**
   - Número: 2
   - Icono: rocket_launch

3. **Cumplimiento Legal**
   - Porcentaje: 100% (verde)
   - Icono: gavel

4. **Última Auditoría**
   - Texto: "Hace 2 días"
   - Icono: history

#### Layout Grid (2 columnas principales + sidebar)

**Columna Izquierda (2/3):**

1. **Pagos Pendientes**
   - Tabla con columnas:
     - Factura ID (#1023, #1045, etc.)
     - Monto
     - Vencimiento (con colores según estado)
     - Estado (badge: Vencido/rojo, Por Vencer/amarillo, Pendiente/azul)
     - Acción (icono de ojo para ver)
   - Link: "Ver Historial"

2. **Descripción del Proyecto y Alcance**
   - Tabs: "Activos" | "Finalizados"
   - Título del proyecto
   - Descripción completa
   - Card de "Cobertura de Riesgo Legal" con:
     - Icono de verificación
     - Título y descripción

**Columna Derecha (1/3):**

1. **Nuevos Servicios**
   - Lista de servicios activados recientemente
   - Cada servicio muestra:
     - Icono con fondo azul claro
     - Nombre del servicio
     - Fecha de activación
     - Check verde
   - Botón: "+ Agregar registro"

2. **Estatus del Proyecto**
   - Nombre del proyecto
   - Fecha de fin
   - Barra de progreso con porcentaje
   - Sección "PRÓXIMOS PASOS":
     - Checklist con checkboxes
     - Items completados (tachados)
     - Items pendientes
   - Alerta amarilla al final (si hay pendientes)

---

## 📊 Funcionalidades Clave

### Dashboard
- ✅ Métricas en tiempo real
- ✅ Gráficos de ingresos (Chart.js)
- ✅ Filtros de cotizaciones
- ✅ Tareas diarias con prioridades
- ✅ Objetivo mensual con progreso

### Gestión de Clientes
- ✅ Listado con filtros y búsqueda
- ✅ Vista detallada completa
- ✅ Cards de resumen financiero
- ✅ Historial de pagos
- ✅ Servicios activos
- ✅ Documentos adjuntos
- ✅ Notas y seguimiento

### Cotizaciones
- ✅ Crear/Editar con items dinámicos
- ✅ Estados visuales (Pendiente, Aprobada, Rechazada)
- ✅ Filtros por estado
- ✅ Generar PDF
- ✅ Enviar por email

### Servicios
- ✅ Tipos de servicio predefinidos
- ✅ URLs a proyectos (Canva, Figma)
- ✅ Progreso del proyecto
- ✅ Tareas del proyecto (checklist)
- ✅ Cobertura de riesgo legal
- ✅ Fechas de renovación

### Pagos
- ✅ Estados: Pendiente, Pagado, Vencido
- ✅ Colores semánticos
- ✅ Historial completo
- ✅ Generar facturas

### Tareas Diarias
- ✅ Checklist interactivo
- ✅ Prioridades (Normal, Urgente)
- ✅ Fechas y horas
- ✅ Relación con clientes/servicios

---

## 🚀 Plan de Implementación

### Fase 1: Base de Datos y Clases (Semana 1)
- [ ] Crear script SQL completo
- [ ] Crear clase `Client.php`
- [ ] Crear clase `Quote.php`
- [ ] Crear clase `Service.php`
- [ ] Crear clase `Payment.php`
- [ ] Crear clase `DailyTask.php`

### Fase 2: Componentes Base (Semana 2)
- [ ] Crear `includes/admin_header.php`
- [ ] Crear `includes/admin_sidebar.php`
- [ ] Crear `includes/admin_footer.php`
- [ ] Configurar Tailwind CSS con tema personalizado
- [ ] Integrar Material Symbols

### Fase 3: Dashboard Principal (Semana 3)
- [ ] Crear `admin_dashboard.php`
- [ ] Implementar cards de métricas
- [ ] Tabla de cotizaciones recientes
- [ ] Cards de clientes activos
- [ ] Sección de seguimiento diario
- [ ] Card de objetivo mensual

### Fase 4: Gestión de Clientes (Semana 4)
- [ ] Crear `admin_clients.php` (listado)
- [ ] Crear `admin_client_detail.php` (vista detallada)
- [ ] Formulario crear/editar cliente
- [ ] Tabla de pagos pendientes
- [ ] Sección de servicios activos
- [ ] Descripción de proyectos

### Fase 5: Cotizaciones y Servicios (Semana 5)
- [ ] Crear `admin_quotes.php`
- [ ] Formulario de cotización con items dinámicos
- [ ] Crear `admin_services.php`
- [ ] Gestión de tareas del proyecto
- [ ] URLs a proyectos externos

### Fase 6: Pagos y Tareas (Semana 6)
- [ ] Crear `admin_payments.php`
- [ ] Registrar y gestionar pagos
- [ ] Crear `admin_daily_tasks.php`
- [ ] Sistema de tareas con prioridades

### Fase 7: Documentos y Finalización (Semana 7)
- [ ] Sistema de subida de documentos
- [ ] Generación de PDFs
- [ ] Envío de emails
- [ ] Testing completo
- [ ] Optimización y ajustes finales

---

## 💡 Características Especiales

### Generación de Números Únicos
- **Clientes**: `C-YYYY-XXX` (ej: C-2024-889)
- **Cotizaciones**: `COT-YYYY-MM-XXXX` (ej: COT-2024-01-0001)
- **Pagos**: `PAY-YYYY-MM-XXXX` (ej: PAY-2024-01-0001)
- **Facturas**: `#XXXX` (ej: #1023)

### Estados y Colores
- **Cotizaciones**:
  - Pendiente: Amarillo (`bg-amber-100 text-amber-700`)
  - Aprobada: Verde (`bg-emerald-100 text-emerald-700`)
  - Rechazada: Rojo (`bg-red-100 text-red-700`)

- **Pagos**:
  - Pendiente: Azul (`bg-primary/10 text-primary`)
  - Pagado: Verde
  - Vencido: Rojo (`bg-red-500/10 text-red-500`)
  - Por Vencer: Amarillo (`bg-yellow-500/10 text-yellow-500`)

### Tipos de Servicio con Iconos
- Redes Sociales: `share`
- Community Manager: `people`
- Diseño Web: `web`
- Ads: `campaign`
- Branding: `palette`
- Chatbot: `smart_toy`
- SEO: `search`
- Consultoría Legal: `policy`
- Auditoría: `dataset`

---

## 📝 Notas Técnicas

### Tecnologías
- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: Tailwind CSS 3.x
- **Iconos**: Material Symbols Outlined
- **Fuentes**: Manrope (Google Fonts)
- **Gráficos**: Chart.js (para dashboard)

### Seguridad
- Autenticación con sesiones
- Validación de todos los inputs
- Sanitización de datos
- Protección CSRF
- Permisos de archivos seguros

### Rendimiento
- Índices en base de datos
- Paginación en listados
- Caché de consultas frecuentes
- Lazy loading de imágenes

---

## ✅ Checklist de Implementación

### Base de Datos
- [ ] Crear todas las tablas
- [ ] Agregar índices necesarios
- [ ] Crear relaciones (foreign keys)
- [ ] Datos de prueba

### Clases PHP
- [ ] Client.php
- [ ] Quote.php
- [ ] Service.php
- [ ] Payment.php
- [ ] DailyTask.php

### Interfaz
- [ ] Dashboard principal
- [ ] Listado de clientes
- [ ] Vista detallada de cliente
- [ ] Gestión de cotizaciones
- [ ] Gestión de servicios
- [ ] Gestión de pagos
- [ ] Tareas diarias

### Funcionalidades
- [ ] CRUD completo de clientes
- [ ] CRUD completo de cotizaciones
- [ ] CRUD completo de servicios
- [ ] CRUD completo de pagos
- [ ] Subida de documentos
- [ ] Generación de PDFs
- [ ] Envío de emails
- [ ] Sistema de tareas

---

**¿Te parece bien esta propuesta ajustada al diseño que te gusta? ¿Quieres que comience con la implementación?**

