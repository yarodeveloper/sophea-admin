# Propuesta: Sistema de Envío de Facturas/Recibos

## Objetivo
Implementar funcionalidad para generar y enviar facturas/recibos de pago que incluyan:
- Resumen de pagos realizados
- Pagos pendientes (si los hay)
- Por proyecto específico o todos los proyectos del cliente
- Formato similar al PDF proporcionado

## Análisis del Formato del Recibo

### Estructura del Recibo (basado en PDF):
1. **Encabezado:**
   - Logo de la empresa (SOPHEA)
   - Título: "RECIBO PAGO DE SERVICIO"
   - Fecha
   - Número de Recibo (formato: `web-122025-1`)

2. **Sección "De" (Cliente):**
   - Nombre
   - Contacto (teléfono)
   - Ubicación

3. **Sección "Beneficiario" (Empresa):**
   - Nombre: Sophea Marketing
   - Contacto: Alejandro Montoya - 9616933158
   - Ubicación: Tuxtla Gutiérrez

4. **Tabla de Servicios:**
   - Descripción
   - Fecha
   - Cantidad
   - Precio/Costo
   - Total

5. **Totales:**
   - Total de servicio creación página web
   - Total restante
   - IVA (0 en el ejemplo)
   - Total restante final

6. **Pie de página:**
   - Servicios ofrecidos (Diseño de Páginas Web, Publicidad Digital, etc.)
   - Dirección de la empresa

## Propuesta de Implementación

### 1. Archivos a Crear

#### 1.1 `generate_invoice.php`
- Genera el PDF/HTML del recibo
- Recibe parámetros: `client_id`, `service_id` (opcional), `format` (pdf/html)
- Genera número de recibo único
- Formato: `{tipo}-{mes}{año}-{secuencial}` (ej: `web-122025-1`)

#### 1.2 `send_invoice.php`
- Maneja el envío del recibo
- Opciones: WhatsApp, Email, o ambos
- Integración con WhatsApp API existente

#### 1.3 `includes/invoice_template.php`
- Template HTML del recibo
- Estilos CSS para impresión/PDF
- Responsive y compatible con generadores de PDF

### 2. Funcionalidad del Botón "Enviar Factura"

#### 2.1 Modal de Configuración
Al hacer clic en "Enviar Factura", se abre un modal con:

```
┌─────────────────────────────────────────┐
│  Enviar Factura/Recibo                  │
├─────────────────────────────────────────┤
│                                         │
│  Tipo de Resumen:                       │
│  ○ Todos los proyectos                 │
│  ● Proyecto específico                 │
│                                         │
│  [Selector de Proyecto]                │
│  ┌─────────────────────────────────┐   │
│  │ Construcción de página web...  │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Incluir:                               │
│  ☑ Pagos realizados                    │
│  ☑ Pagos pendientes                    │
│  ☑ Detalle de servicios                │
│                                         │
│  Formato:                               │
│  ○ PDF                                 │
│  ● HTML (para WhatsApp)                │
│                                         │
│  Método de envío:                      │
│  ☑ WhatsApp                            │
│  ☐ Email                                │
│                                         │
│  [Cancelar]  [Generar y Enviar]        │
└─────────────────────────────────────────┘
```

#### 2.2 Proceso de Generación

1. **Recopilar Datos:**
   ```php
   - Datos del cliente (nombre, contacto, ubicación)
   - Logo de la empresa (desde SiteSettings)
   - Datos de la empresa (desde config.php)
   - Servicios activos del cliente (o servicio específico)
   - Pagos realizados (filtrados por servicio si aplica)
   - Pagos pendientes (filtrados por servicio si aplica)
   ```

2. **Generar Número de Recibo:**
   ```php
   // Formato: {tipo}-{mes}{año}-{secuencial}
   // Ejemplo: web-122025-1
   $invoiceNumber = generateInvoiceNumber($serviceType, $year, $month);
   ```

3. **Calcular Totales:**
   ```php
   - Total de servicios: Suma de monthly_fee de servicios activos
   - Total pagado: Suma de pagos con status 'paid'
   - Total pendiente: Suma de pagos con status 'pending' o 'overdue'
   - Saldo restante: Total servicios - Total pagado - Total pendiente
   ```

4. **Generar HTML/PDF:**
   - Usar template HTML con estilos
   - Convertir a PDF usando librería (TCPDF, DomPDF, o similar)
   - O enviar HTML directamente para WhatsApp

### 3. Estructura de Datos

#### 3.1 Datos del Cliente
```php
$clientData = [
    'name' => $clientData['company_name'] ?? $clientData['contact_name'],
    'contact' => $clientData['phone'] ?? $clientData['whatsapp'],
    'location' => $clientData['city'] . ', ' . $clientData['state']
];
```

#### 3.2 Datos de la Empresa
```php
$companyData = [
    'name' => 'Sophea Marketing',
    'contact' => DIRECTOR_NAME . ' - ' . CONTACT_PHONE,
    'location' => CONTACT_CITY,
    'address' => CONTACT_ADDRESS,
    'logo' => SiteSettings::getMainLogo()
];
```

#### 3.3 Datos de Servicios y Pagos
```php
$invoiceData = [
    'invoice_number' => 'web-122025-1',
    'date' => date('d/M/Y'),
    'services' => [
        [
            'description' => 'Anticipo de inicio de construcción',
            'date' => '17/Dic/25',
            'quantity' => 1,
            'price' => 1000,
            'total' => 1000
        ],
        // ... más servicios
    ],
    'totals' => [
        'service_total' => 5000,
        'paid_total' => 1000,
        'pending_total' => 0,
        'remaining_total' => 4000,
        'iva' => 0
    ],
    'payments_made' => [...], // Pagos realizados
    'payments_pending' => [...] // Pagos pendientes
];
```

### 4. Template HTML del Recibo

```html
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Estilos para el recibo */
        body { font-family: Arial, sans-serif; }
        .header { display: flex; justify-content: space-between; }
        .logo { max-width: 150px; }
        .section { margin: 20px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        /* ... más estilos */
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Encabezado con logo -->
        <!-- Sección De/Beneficiario -->
        <!-- Tabla de servicios -->
        <!-- Totales -->
        <!-- Pie de página con servicios -->
    </div>
</body>
</html>
```

### 5. Integración con WhatsApp

#### 5.1 Envío de Recibo por WhatsApp
- Si es HTML: Enviar como mensaje con enlace a vista previa
- Si es PDF: Generar PDF, subirlo a servidor, enviar enlace o archivo
- Usar WhatsApp API existente (`send_whatsapp.php`)

#### 5.2 Mensaje de WhatsApp
```
📄 *Recibo de Pago - SOPHEA*

Hola [Nombre del Cliente],

Adjunto encontrarás el resumen de pagos de tu proyecto.

*Resumen:*
• Total del servicio: $5,000
• Pagos realizados: $1,000
• Saldo pendiente: $4,000

[Ver Recibo Completo] [Link]

¿Tienes alguna pregunta? Estamos para ayudarte.

SOPHEA Marketing
```

### 6. Opciones de Implementación

#### Opción A: Solo HTML (Recomendada para WhatsApp)
- ✅ Más rápido de generar
- ✅ Compatible con WhatsApp
- ✅ No requiere librerías adicionales
- ❌ No se puede descargar como PDF directamente

#### Opción B: HTML + PDF
- ✅ HTML para WhatsApp
- ✅ PDF para descarga/email
- ✅ Más profesional
- ❌ Requiere librería PDF (TCPDF, DomPDF)

#### Opción C: Solo PDF
- ✅ Formato profesional
- ❌ Más difícil de enviar por WhatsApp
- ❌ Requiere conversión para vista previa

### 7. Flujo de Usuario

1. Usuario hace clic en "Enviar Factura" en `admin_client_detail.php`
2. Se abre modal con opciones (proyecto, formato, método de envío)
3. Usuario selecciona opciones y hace clic en "Generar y Enviar"
4. Sistema genera el recibo
5. Sistema envía por WhatsApp/Email según selección
6. Usuario recibe confirmación de envío

### 8. Consideraciones Técnicas

#### 8.1 Generación de Número de Recibo
```php
function generateInvoiceNumber($serviceType = 'web', $clientId = null) {
    $year = date('y');
    $month = date('m');
    
    // Obtener último número del mes
    $lastNumber = getLastInvoiceNumber($year, $month, $serviceType);
    $nextNumber = $lastNumber + 1;
    
    return strtolower($serviceType) . '-' . $month . $year . '-' . $nextNumber;
}
```

#### 8.2 Almacenamiento de Recibos
- Opcional: Guardar recibos generados en tabla `invoices` o `receipts`
- Guardar: número de recibo, fecha, cliente, servicio, archivo PDF/HTML

#### 8.3 Seguridad
- Validar que el usuario tenga permisos
- Validar que el cliente pertenezca al usuario
- Sanitizar todos los datos antes de generar

### 9. Archivos a Modificar

1. **admin_client_detail.php:**
   - Agregar funcionalidad al botón "Enviar Factura"
   - Agregar modal de configuración
   - Agregar JavaScript para manejar el modal

2. **Nuevos archivos:**
   - `generate_invoice.php` - Generador de recibos
   - `send_invoice.php` - Enviador de recibos
   - `includes/invoice_template.php` - Template HTML
   - `classes/Invoice.php` - Clase para manejo de recibos (opcional)

### 10. Base de Datos (Opcional)

Si se desea guardar historial de recibos:

```sql
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    service_id INT NULL,
    invoice_type ENUM('all_services', 'single_service') DEFAULT 'all_services',
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    pending_amount DECIMAL(10, 2) DEFAULT 0,
    remaining_amount DECIMAL(10, 2) NOT NULL,
    invoice_date DATE NOT NULL,
    format ENUM('html', 'pdf') DEFAULT 'html',
    file_path VARCHAR(500) NULL,
    sent_via ENUM('whatsapp', 'email', 'both') DEFAULT 'whatsapp',
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_client_id (client_id),
    INDEX idx_invoice_date (invoice_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Recomendación Final

**Implementar Opción A (HTML) inicialmente:**
- Más rápido de desarrollar
- Compatible con WhatsApp
- Fácil de mantener
- Se puede agregar PDF después si es necesario

**Estructura propuesta:**
1. Modal en `admin_client_detail.php`
2. `generate_invoice.php` para generar HTML
3. `send_invoice.php` para enviar por WhatsApp
4. Template HTML con estilos inline para compatibilidad

¿Procedemos con esta implementación?

