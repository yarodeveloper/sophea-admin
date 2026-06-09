# 📊 Propuesta: Sistema de Gestión de Clientes, Cotizaciones y Servicios - SOPHEA

## 🎯 Objetivo

Crear un sistema completo de gestión empresarial que permita:
- ✅ Gestionar clientes y sus datos completos
- ✅ Crear y gestionar cotizaciones
- ✅ Administrar servicios activos por cliente
- ✅ Controlar ingresos, pagos realizados y pendientes
- ✅ Adjuntar documentos (cotizaciones, contratos)
- ✅ Enlazar proyectos externos (Canva, etc.)
- ✅ Dashboard con métricas e ingresos por servicio

---

## 📋 Estructura de Base de Datos

### 1. Tabla: `clients` (Clientes)

```sql
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    status ENUM('prospect', 'active', 'inactive', 'archived') DEFAULT 'prospect',
    notes TEXT COMMENT 'Notas generales del cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del admin que creó el cliente',
    INDEX idx_status (status),
    INDEX idx_company_name (company_name),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Tabla: `quotes` (Cotizaciones)

```sql
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de cotización único',
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
        'otro'
    ) NOT NULL,
    service_name VARCHAR(255) NOT NULL COMMENT 'Nombre específico del servicio',
    description TEXT,
    monthly_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa mensual',
    setup_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Tarifa de configuración inicial',
    billing_cycle ENUM('monthly', 'quarterly', 'yearly', 'one_time') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL si es servicio continuo',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    project_url VARCHAR(500) COMMENT 'URL a Canva, Figma, etc.',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Tabla: `payments` (Pagos)

```sql
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_id INT NULL COMMENT 'Pago asociado a un servicio específico',
    quote_id INT NULL COMMENT 'Pago asociado a una cotización',
    payment_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número de pago único',
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
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. Tabla: `documents` (Documentos Adjuntos)

```sql
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('client', 'quote', 'service', 'payment') NOT NULL,
    entity_id INT NOT NULL COMMENT 'ID del cliente, cotización, servicio o pago',
    document_type ENUM('quote', 'contract', 'invoice', 'receipt', 'other') NOT NULL,
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

### 7. Tabla: `client_notes` (Notas de Cliente)

```sql
CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    note_text TEXT NOT NULL,
    note_type ENUM('general', 'call', 'meeting', 'email', 'task') DEFAULT 'general',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🏗️ Arquitectura del Sistema

### Estructura de Archivos Propuesta

```
sopheaadmin/
├── admin_clients.php          # Panel principal de clientes
├── admin_quotes.php           # Gestión de cotizaciones
├── admin_services.php         # Gestión de servicios activos
├── admin_payments.php         # Gestión de pagos
├── admin_dashboard.php        # Dashboard con métricas
├── classes/
│   ├── Client.php            # Clase para gestión de clientes
│   ├── Quote.php             # Clase para cotizaciones
│   ├── Service.php           # Clase para servicios
│   └── Payment.php           # Clase para pagos
├── api/
│   ├── clients_api.php       # API REST para clientes
│   ├── quotes_api.php        # API REST para cotizaciones
│   └── payments_api.php      # API REST para pagos
├── uploads/
│   ├── documents/            # Documentos adjuntos
│   │   ├── quotes/           # Cotizaciones PDF
│   │   ├── contracts/        # Contratos
│   │   └── invoices/         # Facturas
└── database/
    └── clients_quotes_schema.sql  # Script SQL completo
```

---

## 🎨 Interfaz Propuesta

### 1. Dashboard Principal (`admin_dashboard.php`)

**Secciones:**

#### A. Métricas Principales (Cards)
- 💰 **Ingresos del Mes**: Total de pagos recibidos este mes
- 📊 **Ingresos Pendientes**: Total de pagos pendientes
- 👥 **Clientes Activos**: Número de clientes con servicios activos
- 📝 **Cotizaciones Pendientes**: Cotizaciones enviadas sin respuesta

#### B. Gráficos
- **Ingresos por Mes** (últimos 12 meses) - Gráfico de líneas
- **Ingresos por Tipo de Servicio** - Gráfico de barras
  - Redes Sociales
  - Community Manager
  - Diseño Web
  - Ads
  - Branding
  - Chatbot
  - Otros
- **Estado de Pagos** - Gráfico de pastel
  - Pagados
  - Pendientes
  - Vencidos

#### C. Tablas Resumen
- **Últimos Pagos Recibidos** (10 más recientes)
- **Pagos Próximos a Vencer** (próximos 7 días)
- **Servicios que Vencen Pronto** (próximos 30 días)
- **Cotizaciones Recientes** (últimas 5)

---

### 2. Gestión de Clientes (`admin_clients.php`)

**Funcionalidades:**

#### Vista de Listado
- Tabla con todos los clientes
- Filtros:
  - Por estado (Prospect, Activo, Inactivo, Archivado)
  - Por industria
  - Por búsqueda (nombre, email, empresa)
- Columnas:
  - Nombre de empresa
  - Contacto
  - Email
  - Teléfono
  - Estado
  - Servicios activos (contador)
  - Ingresos totales
  - Acciones (Ver, Editar, Eliminar)

#### Vista de Detalle de Cliente
- **Información General**
  - Datos de contacto completos
  - Dirección
  - RFC/ID Fiscal
  - Website
  - Industria
  
- **Servicios Activos**
  - Lista de servicios con estado
  - Botón para agregar nuevo servicio
  - Enlaces a proyectos (Canva, etc.)
  
- **Historial de Cotizaciones**
  - Lista de todas las cotizaciones
  - Estado de cada una
  - Botón para crear nueva cotización
  
- **Historial de Pagos**
  - Pagos realizados
  - Pagos pendientes
  - Total de ingresos
  
- **Documentos**
  - Lista de documentos adjuntos
  - Botón para subir nuevo documento
  - Descargar/Ver documentos
  
- **Notas**
  - Timeline de notas y actividades
  - Agregar nueva nota

#### Formulario de Crear/Editar Cliente
- Campos del formulario
- Validación
- Guardar cambios

---

### 3. Gestión de Cotizaciones (`admin_quotes.php`)

**Funcionalidades:**

#### Vista de Listado
- Tabla con todas las cotizaciones
- Filtros:
  - Por estado (Borrador, Enviada, Aceptada, Rechazada, Expirada)
  - Por cliente
  - Por rango de fechas
- Columnas:
  - Número de cotización
  - Cliente
  - Total
  - Estado
  - Fecha de creación
  - Válida hasta
  - Acciones

#### Vista de Detalle de Cotización
- **Información General**
  - Número de cotización
  - Cliente
  - Fecha de creación
  - Válida hasta
  - Estado
  
- **Items de la Cotización**
  - Tabla con items
  - Descripción
  - Cantidad
  - Precio unitario
  - Total por item
  - Subtotal
  - IVA
  - Total
  
- **Acciones**
  - Editar (si es borrador)
  - Enviar por email
  - Generar PDF
  - Convertir a servicio activo (si está aceptada)
  - Duplicar
  - Eliminar

#### Formulario de Crear/Editar Cotización
- Seleccionar cliente
- Agregar items dinámicamente
- Calcular totales automáticamente
- Guardar como borrador o enviar

---

### 4. Gestión de Servicios (`admin_services.php`)

**Funcionalidades:**

#### Vista de Listado
- Tabla con todos los servicios
- Filtros:
  - Por tipo de servicio
  - Por estado (Activo, Pausado, Completado, Cancelado)
  - Por cliente
- Columnas:
  - Cliente
  - Tipo de servicio
  - Nombre del servicio
  - Tarifa mensual
  - Fecha de inicio
  - Fecha de fin
  - Estado
  - URL del proyecto
  - Acciones

#### Vista de Detalle de Servicio
- **Información del Servicio**
  - Cliente
  - Tipo y nombre
  - Descripción
  - Tarifas (mensual, setup)
  - Ciclo de facturación
  - Fechas (inicio, fin)
  - Estado
  - URL del proyecto (Canva, etc.)
  
- **Pagos Asociados**
  - Lista de pagos relacionados
  - Historial de pagos
  
- **Documentos**
  - Contratos
  - Otros documentos

#### Formulario de Crear/Editar Servicio
- Seleccionar cliente
- Tipo de servicio
- Configurar tarifas
- Fechas
- URL del proyecto
- Guardar

---

### 5. Gestión de Pagos (`admin_payments.php`)

**Funcionalidades:**

#### Vista de Listado
- Tabla con todos los pagos
- Filtros:
  - Por estado (Pendiente, Pagado, Vencido, Cancelado)
  - Por cliente
  - Por servicio
  - Por rango de fechas
- Columnas:
  - Número de pago
  - Cliente
  - Servicio
  - Monto
  - Fecha de pago / Vencimiento
  - Estado
  - Método de pago
  - Acciones

#### Vista de Detalle de Pago
- **Información del Pago**
  - Cliente
  - Servicio asociado
  - Monto
  - Fecha de pago / Vencimiento
  - Estado
  - Método de pago
  - Número de referencia
  - Notas
  
- **Documentos**
  - Comprobante de pago
  - Factura

#### Formulario de Crear/Editar Pago
- Seleccionar cliente
- Seleccionar servicio (opcional)
- Monto
- Fecha
- Método de pago
- Marcar como pagado
- Guardar

---

## 📊 Funcionalidades Clave

### 1. Generación de Números Únicos
- **Cotizaciones**: `COT-YYYY-MM-XXXX` (ej: COT-2025-01-0001)
- **Pagos**: `PAY-YYYY-MM-XXXX` (ej: PAY-2025-01-0001)

### 2. Cálculos Automáticos
- Subtotal de cotización
- IVA calculado automáticamente
- Total de cotización
- Total de ingresos por cliente
- Total de ingresos por servicio

### 3. Generación de PDFs
- Cotizaciones en PDF
- Contratos en PDF
- Facturas en PDF
- Reportes de ingresos

### 4. Envío de Emails
- Enviar cotización por email
- Recordatorios de pagos pendientes
- Notificaciones de cambios de estado

### 5. Búsqueda y Filtros
- Búsqueda global
- Filtros avanzados
- Exportar a Excel/CSV

### 6. Dashboard con Métricas
- Ingresos totales
- Ingresos por mes
- Ingresos por tipo de servicio
- Clientes activos
- Cotizaciones pendientes
- Pagos pendientes

---

## 🚀 Plan de Implementación

### Fase 1: Base de Datos y Clases (Semana 1)
- [ ] Crear script SQL con todas las tablas
- [ ] Crear clase `Client.php`
- [ ] Crear clase `Quote.php`
- [ ] Crear clase `Service.php`
- [ ] Crear clase `Payment.php`

### Fase 2: Gestión de Clientes (Semana 2)
- [ ] Crear `admin_clients.php`
- [ ] Listado de clientes
- [ ] Crear/Editar cliente
- [ ] Vista de detalle de cliente
- [ ] Subir documentos

### Fase 3: Gestión de Cotizaciones (Semana 3)
- [ ] Crear `admin_quotes.php`
- [ ] Crear/Editar cotización
- [ ] Agregar items dinámicamente
- [ ] Generar PDF de cotización
- [ ] Enviar cotización por email

### Fase 4: Gestión de Servicios (Semana 4)
- [ ] Crear `admin_services.php`
- [ ] Crear/Editar servicio
- [ ] Vincular servicios con clientes
- [ ] Gestión de URLs de proyectos

### Fase 5: Gestión de Pagos (Semana 5)
- [ ] Crear `admin_payments.php`
- [ ] Registrar pagos
- [ ] Marcar pagos como recibidos
- [ ] Generar facturas

### Fase 6: Dashboard y Reportes (Semana 6)
- [ ] Crear `admin_dashboard.php`
- [ ] Implementar gráficos (Chart.js)
- [ ] Métricas y estadísticas
- [ ] Exportar reportes

---

## 💡 Mejoras Futuras (Opcional)

1. **Integración con WhatsApp**
   - Enviar cotizaciones por WhatsApp
   - Recordatorios de pagos por WhatsApp

2. **Facturación Electrónica**
   - Integración con CFDI
   - Generación de XML

3. **Reportes Avanzados**
   - Reportes personalizados
   - Exportar a Excel
   - Gráficos avanzados

4. **Notificaciones**
   - Recordatorios automáticos
   - Alertas de pagos vencidos
   - Notificaciones de servicios que vencen

5. **API REST**
   - API para integraciones externas
   - Webhooks

---

## 📝 Notas Técnicas

### Seguridad
- Validación de todos los inputs
- Sanitización de datos
- Protección CSRF
- Autenticación requerida
- Permisos de archivos

### Rendimiento
- Índices en base de datos
- Paginación en listados
- Caché de consultas frecuentes
- Optimización de consultas

### UX/UI
- Diseño responsive
- Interfaz intuitiva
- Feedback visual
- Confirmaciones antes de eliminar
- Mensajes de éxito/error claros

---

## ✅ Checklist de Implementación

- [ ] Crear estructura de base de datos
- [ ] Crear clases PHP
- [ ] Implementar gestión de clientes
- [ ] Implementar gestión de cotizaciones
- [ ] Implementar gestión de servicios
- [ ] Implementar gestión de pagos
- [ ] Crear dashboard con métricas
- [ ] Implementar subida de documentos
- [ ] Generar PDFs
- [ ] Envío de emails
- [ ] Testing completo
- [ ] Documentación

---

**¿Te parece bien esta propuesta? ¿Quieres que ajuste algo o que comience con la implementación?**

