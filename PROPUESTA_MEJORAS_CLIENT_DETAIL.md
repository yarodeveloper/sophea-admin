# Propuesta de Mejoras: Detalle de Cliente - Centro de Operaciones

## Objetivo
Convertir `admin_client_detail.php` en el centro de operaciones completo para gestionar todos los aspectos de un cliente desde un solo lugar.

## Mejoras Propuestas

### 1. Cálculo Correcto de "Total Pendiente"
**Problema Actual:**
- El "Total Pendiente" se calcula sumando pagos pendientes/vencidos
- No refleja el importe real de los servicios activos del cliente

**Solución:**
- **Total Pendiente = Suma de `monthly_fee` de todos los servicios activos del cliente**
- Mostrar también: "Pagos Registrados Pendientes" (suma de pagos pendientes)
- Mostrar diferencia: "Por Registrar" = Total Pendiente - Pagos Registrados Pendientes

**Cálculo:**
```php
// Total esperado de servicios activos
$totalExpectedFromServices = suma de monthly_fee de servicios activos

// Pagos ya registrados (pendientes)
$totalPendingPayments = suma de pagos con status 'pending' o 'overdue'

// Diferencia (lo que falta por registrar)
$difference = $totalExpectedFromServices - $totalPendingPayments
```

### 2. Asignación de Pagos desde el Detalle del Cliente

#### 2.1 Botón "Registrar Pago" en cada Servicio
- Cada servicio activo tendrá un botón "Registrar Pago/Adelanto"
- Al hacer clic, se abre un modal con:
  - Servicio pre-seleccionado
  - Monto sugerido: `monthly_fee` del servicio
  - Opción de "Pago Completo" o "Adelanto" (monto editable)
  - Fecha de pago
  - Fecha de vencimiento (opcional)
  - Método de pago
  - Notas

#### 2.2 Sección "Pagos por Proyecto"
- Mostrar cada servicio con:
  - Nombre del servicio
  - Tarifa mensual
  - Pagos realizados (suma)
  - Pagos pendientes (suma)
  - Saldo pendiente = Tarifa mensual - Pagos realizados - Pagos pendientes
  - Botón "Registrar Pago" para ese servicio específico

#### 2.3 Modal "Registrar Pago Rápido"
- Botón flotante o en header: "Registrar Pago"
- Modal con:
  - Selector de servicio (opcional, puede ser pago general)
  - Monto
  - Tipo: "Pago Completo", "Adelanto", "Pago Parcial"
  - Fecha de pago
  - Fecha de vencimiento
  - Método de pago
  - Notas
  - Checkbox: "Marcar como pagado" (si ya se recibió)

### 3. Mejoras en Visualización de Servicios

#### 3.1 Tarjetas de Servicios Mejoradas
Cada servicio mostrará:
- **Header:**
  - Nombre del servicio
  - Estado (Activo, Pausado, Completado)
  - Progreso (barra de progreso)
  
- **Información Financiera:**
  - Tarifa mensual: $X,XXX.XX
  - Pagos recibidos: $X,XXX.XX
  - Pagos pendientes: $X,XXX.XX
  - Saldo pendiente: $X,XXX.XX
  
- **Acciones Rápidas:**
  - Botón "Registrar Pago"
  - Botón "Ver Detalles"
  - Botón "Ver Pagos del Proyecto"
  - Link a URL del proyecto (si existe)

#### 3.2 Tabs en Sección de Servicios
- Tab "Activos" (por defecto)
- Tab "Finalizados"
- Tab "Todos"

### 4. Sección de Pagos Mejorada

#### 4.1 Vista Expandida de Pagos
- Tabla con más información:
  - Factura/Número de pago
  - Servicio asociado (si aplica)
  - Monto
  - Fecha de pago
  - Vencimiento
  - Estado
  - Acciones: Ver, Editar, Marcar como Pagado

#### 4.2 Filtros de Pagos
- Por servicio
- Por estado
- Por rango de fechas

#### 4.3 Resumen de Pagos
- Total recibido (este mes)
- Total recibido (este año)
- Total pendiente
- Próximos vencimientos (próximos 7 días)

### 5. Estructura Propuesta del Layout

```
┌─────────────────────────────────────────────────────────┐
│ Header del Cliente                                       │
│ [Logo] Nombre | Tipo | ID | Acciones                    │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Cards de Resumen (4 cards)                              │
│ [Total Pendiente] [Proyectos Activos] [Cumplimiento]   │
│ [Última Auditoría]                                      │
└─────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────┐
│ Columna Izquierda (2/3)  │ Columna Derecha (1/3)        │
│                          │                              │
│ ┌──────────────────────┐ │ ┌──────────────────────────┐ │
│ │ Servicios Activos    │ │ │ Información de Contacto  │ │
│ │ [Tab: Activos/Fin]   │ │ │                          │ │
│ │                      │ │ │ - Email                  │ │
│ │ [Servicio 1]         │ │ │ - Teléfono               │ │
│ │ - Tarifa: $X,XXX     │ │ │ - WhatsApp               │ │
│ │ - Pagos: $X,XXX      │ │ │ - Dirección              │ │
│ │ - Saldo: $X,XXX      │ │ │                          │ │
│ │ [Registrar Pago]     │ │ ┌──────────────────────────┐ │
│ │                      │ │ │ Cotizaciones Recientes   │ │
│ │ [Servicio 2]         │ │ │                          │ │
│ │ ...                  │ │ │ [COT-2025-01-0001]       │ │
│ └──────────────────────┘ │ │ [COT-2025-01-0002]       │ │
│                          │ │                          │ │
│ ┌──────────────────────┐ │ └──────────────────────────┘ │
│ │ Pagos Pendientes     │ │                              │
│ │ [Filtros]            │ │ ┌──────────────────────────┐ │
│ │                      │ │ │ Notas del Cliente        │ │
│ │ [Tabla de Pagos]     │ │ │                          │ │
│ │ - Factura | Monto    │ │ │ [Notas aquí]             │ │
│ │ - Vencimiento        │ │ │                          │ │
│ │ - Estado | Acciones  │ │ └──────────────────────────┘ │
│ └──────────────────────┘ │                              │
└──────────────────────────┴──────────────────────────────┘
```

### 6. Funcionalidades Adicionales

#### 6.1 Quick Actions (Acciones Rápidas)
Panel flotante o sección con:
- Registrar Pago Rápido
- Nueva Cotización
- Añadir Servicio
- Enviar Factura
- Agregar Nota

#### 6.2 Timeline de Actividad
- Historial de acciones recientes:
  - Servicio creado
  - Pago registrado
  - Cotización enviada
  - Nota agregada
  - etc.

### 7. Implementación Técnica

#### 7.1 Nuevos Métodos en Clases

**Client.php:**
```php
// Calcular total esperado de servicios activos
public function getTotalExpectedFromServices($clientId)

// Obtener servicios con resumen de pagos
public function getServicesWithPaymentSummary($clientId)
```

**Service.php:**
```php
// Obtener pagos asociados a un servicio
public function getServicePayments($serviceId)

// Calcular saldo pendiente de un servicio
public function getServicePendingBalance($serviceId)
```

**Payment.php:**
```php
// Crear pago desde servicio (método mejorado)
public function createPaymentFromService($serviceId, $paymentData)
```

#### 7.2 Nuevos Archivos
- `includes/client_detail_payment_modal.php` - Modal para registrar pagos
- `includes/client_detail_service_card.php` - Componente de tarjeta de servicio

## Prioridades de Implementación

### Fase 1 (Crítica):
1. ✅ Corregir cálculo de "Total Pendiente" basado en servicios activos
2. ✅ Agregar botón "Registrar Pago" en cada servicio
3. ✅ Modal para registrar pago desde servicio

### Fase 2 (Importante):
4. ✅ Mejorar visualización de servicios con información financiera
5. ✅ Sección de pagos por proyecto
6. ✅ Filtros y búsqueda en pagos

### Fase 3 (Mejoras):
7. ⏳ Timeline de actividad
8. ⏳ Quick Actions panel
9. ⏳ Exportar reportes

## Beneficios

1. **Visibilidad Completa**: Todo lo relacionado con el cliente en un solo lugar
2. **Eficiencia**: Registrar pagos sin salir de la página
3. **Precisión**: Cálculo correcto de totales basado en servicios reales
4. **Trazabilidad**: Ver claramente qué pagos corresponden a qué servicios
5. **Control Financiero**: Saber exactamente cuánto se debe por cada proyecto

