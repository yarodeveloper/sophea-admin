# 📊 Análisis: Sistema de Transacciones de Proyectos (Ads)

## 🎯 Objetivo de la Nueva Propuesta

Implementar un sistema que diferencie claramente entre:
- **Honorarios de Gestión** (`income_fee`): Lo que realmente gana la agencia
- **Inversión Publicitaria** (`income_ads`): Dinero del cliente en "billetera virtual" para pauta
- **Pagos a Plataformas** (`expense_ads_payment`): Salidas cuando se paga a Meta/Google

## 📋 Análisis de la Estructura Actual

### Tablas Existentes Relevantes

1. **`services`**: Proyectos/Servicios activos
   - `id`, `client_id`, `service_type`, `service_name`
   - `monthly_fee`, `setup_fee`
   - Relación: `client_id` → `clients.id`

2. **`payments`**: Pagos de clientes
   - `id`, `client_id`, `service_id`, `amount`, `currency`
   - `payment_date`, `status`, `payment_method`
   - Actualmente: Un pago = un registro simple

3. **`expenses`**: Gastos operativos
   - `id`, `expense_type`, `amount`, `payment_date`
   - Actualmente: Gastos generales de la empresa

### Limitaciones del Sistema Actual

1. ❌ No diferencia entre honorarios e inversión publicitaria
2. ❌ No tiene "billetera virtual" por proyecto
3. ❌ No rastrea inyecciones de capital adicionales
4. ❌ No calcula "Saldo en Custodia" (dinero del cliente no gastado)
5. ❌ No genera "Estado de Cuenta del Proyecto"

## 🏗️ Propuesta de Implementación

### 1. Nueva Tabla: `project_transactions`

```sql
CREATE TABLE IF NOT EXISTS project_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL COMMENT 'Proyecto/Servicio asociado',
    client_id INT NOT NULL COMMENT 'Cliente (redundante pero útil para queries)',
    transaction_type ENUM(
        'income_fee',           -- Honorarios de gestión (va a reporte de ventas)
        'income_ads',           -- Inversión publicitaria (billetera virtual)
        'expense_ads_payment'   -- Pago a plataforma (Meta/Google)
    ) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto positivo o negativo',
    currency VARCHAR(3) DEFAULT 'MXN',
    description TEXT COMMENT 'Descripción de la transacción',
    
    -- Relación con payment original (si aplica)
    payment_id INT NULL COMMENT 'ID del pago que originó esta transacción',
    
    -- Para expense_ads_payment
    platform ENUM('facebook', 'google', 'instagram', 'tiktok', 'linkedin', 'other') NULL,
    campaign_id VARCHAR(255) NULL COMMENT 'ID de campaña en la plataforma',
    billing_period_start DATE NULL COMMENT 'Inicio del período facturado',
    billing_period_end DATE NULL COMMENT 'Fin del período facturado',
    
    -- Metadata
    transaction_date DATE NOT NULL COMMENT 'Fecha de la transacción',
    reference_number VARCHAR(100) NULL COMMENT 'Número de referencia',
    notes TEXT COMMENT 'Notas adicionales',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del usuario que creó la transacción',
    
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    
    INDEX idx_service_id (service_id),
    INDEX idx_client_id (client_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Transacciones financieras de proyectos (honorarios, inversión publicitaria, pagos a plataformas)';
```

### 2. Campos Adicionales en `services`

Agregar campos para rastrear presupuesto total:

```sql
ALTER TABLE services 
ADD COLUMN total_budget DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Presupuesto total acumulado de inversión publicitaria',
ADD COLUMN total_fee DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Total de honorarios de gestión acumulados';
```

### 3. Workflow de Implementación

#### Escenario A: Pago Inicial del Cliente ($4,000)

**Paso 1**: Registrar pago en `payments` (como siempre)
- `amount`: 4000
- `service_id`: [ID del servicio]
- `client_id`: [ID del cliente]

**Paso 2**: Desglosar en transacciones (nuevo)
- Al registrar el pago, mostrar formulario de desglose:
  - Honorarios: $2,000 → `income_fee`
  - Inversión publicitaria: $2,000 → `income_ads`
- Crear 2 registros en `project_transactions`
- Actualizar `services.total_budget` y `services.total_fee`

#### Escenario B: Pago a Plataforma (Meta/Google)

**Paso 1**: Registrar gasto en `expenses` (opcional, para control general)
- `expense_type`: `ads_facebook` o `ads_google`
- `amount`: 2000

**Paso 2**: Registrar transacción en `project_transactions`
- `transaction_type`: `expense_ads_payment`
- `amount`: -2000 (negativo)
- `service_id`: [ID del servicio]
- `platform`: `facebook` o `google`
- `campaign_id`: [ID de campaña]
- Actualizar `services.total_budget` (reducir)

#### Escenario C: Inyección de Capital Adicional

**Paso 1**: Registrar nuevo pago en `payments`
- `amount`: 1000

**Paso 2**: Registrar transacción
- `transaction_type`: `income_ads`
- `amount`: 1000
- `service_id`: [ID del servicio]
- `description`: "Inyección de capital adicional"
- Actualizar `services.total_budget`

### 4. Clase PHP: `ProjectTransaction`

```php
class ProjectTransaction {
    // Crear transacción
    public function createTransaction($data)
    
    // Obtener transacciones por servicio
    public function getTransactionsByService($serviceId, $filters = [])
    
    // Calcular saldo en custodia
    public function getCustodyBalance($serviceId)
    
    // Obtener utilidad real bruta (suma de income_fee)
    public function getGrossProfit($serviceId = null, $dateFrom = null, $dateTo = null)
    
    // Obtener estado de cuenta del proyecto
    public function getProjectStatement($serviceId)
    
    // Desglosar pago en transacciones
    public function splitPaymentIntoTransactions($paymentId, $feeAmount, $adsAmount)
}
```

### 5. Interfaz de Usuario

#### A. Modificar `admin_payments.php`

Al crear/editar un pago para servicio tipo `ads`:
- Mostrar formulario de desglose:
  ```
  Monto total: $4,000
  ├─ Honorarios de gestión: $2,000
  └─ Inversión publicitaria: $2,000
  ```
- Checkbox: "¿Este pago es para servicio de Ads?"
- Si es Ads, mostrar desglose automático (50/50 o editable)

#### B. Modificar `admin_client_detail.php`

Agregar sección "Estado de Cuenta del Proyecto":
- **Resumen**:
  - Total Honorarios: $X
  - Total Inversión Publicitaria: $X
  - Total Gastado en Plataformas: $X
  - **Saldo en Custodia**: $X (dinero disponible)
- **Historial de Transacciones**:
  - Tabla con todas las transacciones
  - Filtros por tipo
  - Fechas de inyecciones de capital

#### C. Modificar `admin_expenses.php`

Al crear gasto tipo `ads_*`:
- Opción: "¿Este gasto es pago a plataforma de un proyecto?"
- Si sí, mostrar:
  - Selector de servicio/proyecto
  - Campo para ID de campaña
  - Período facturado
  - Checkbox: "Registrar como transacción del proyecto"

#### D. Modificar `admin_dashboard.php`

Agregar métricas:
- **Utilidad Real Bruta**: Suma de todos los `income_fee`
- **Saldo en Custodia Total**: Suma de (income_ads - expense_ads_payment) de todos los proyectos
- **Rentabilidad por Proyecto**: income_fee / costos operativos

### 6. Reportes Necesarios

#### A. Utilidad Real Bruta
```sql
SELECT 
    SUM(amount) as gross_profit
FROM project_transactions
WHERE transaction_type = 'income_fee'
AND transaction_date BETWEEN :date_from AND :date_to;
```

#### B. Saldo en Custodia (por proyecto)
```sql
SELECT 
    service_id,
    SUM(CASE WHEN transaction_type = 'income_ads' THEN amount ELSE 0 END) as total_income_ads,
    SUM(CASE WHEN transaction_type = 'expense_ads_payment' THEN ABS(amount) ELSE 0 END) as total_expenses,
    (SUM(CASE WHEN transaction_type = 'income_ads' THEN amount ELSE 0 END) - 
     SUM(CASE WHEN transaction_type = 'expense_ads_payment' THEN ABS(amount) ELSE 0 END)) as custody_balance
FROM project_transactions
WHERE service_id = :service_id
GROUP BY service_id;
```

#### C. Estado de Cuenta del Proyecto
```sql
SELECT 
    pt.*,
    s.service_name,
    c.company_name
FROM project_transactions pt
JOIN services s ON pt.service_id = s.id
JOIN clients c ON pt.client_id = c.id
WHERE pt.service_id = :service_id
ORDER BY pt.transaction_date DESC, pt.created_at DESC;
```

## 🔄 Cambios Necesarios en el Sistema Actual

### Archivos a Modificar

1. **`database/migrate_project_transactions.sql`** (NUEVO)
   - Script de creación de tabla
   - Script de migración de datos existentes (opcional)

2. **`classes/ProjectTransaction.php`** (NUEVO)
   - Clase para manejar transacciones

3. **`classes/Payment.php`** (MODIFICAR)
   - Agregar método `splitPaymentIntoTransactions()`
   - Integrar con `ProjectTransaction`

4. **`classes/Service.php`** (MODIFICAR)
   - Agregar métodos para actualizar `total_budget` y `total_fee`
   - Agregar método `getProjectStatement()`

5. **`admin_payments.php`** (MODIFICAR)
   - Agregar formulario de desglose para servicios Ads
   - Integrar creación de transacciones

6. **`admin_client_detail.php`** (MODIFICAR)
   - Agregar sección "Estado de Cuenta del Proyecto"
   - Mostrar saldo en custodia
   - Mostrar historial de transacciones

7. **`admin_expenses.php`** (MODIFICAR)
   - Agregar opción para registrar como transacción de proyecto
   - Integrar con `ProjectTransaction`

8. **`admin_dashboard.php`** (MODIFICAR)
   - Agregar métricas de utilidad real bruta
   - Agregar saldo en custodia total
   - Agregar rentabilidad por proyecto

## ✅ Ventajas de Esta Propuesta

1. ✅ **Separación clara**: Honorarios vs Inversión publicitaria
2. ✅ **Trazabilidad completa**: Historial de todas las transacciones
3. ✅ **Control financiero**: Saldo en custodia visible
4. ✅ **Flexibilidad**: Inyecciones de capital adicionales
5. ✅ **Reportes precisos**: Utilidad real bruta separada
6. ✅ **Integración**: Compatible con sistema actual de pagos

## ⚠️ Consideraciones

1. **Migración de datos existentes**: 
   - ¿Cómo manejar pagos ya registrados?
   - Opción: Crear transacciones retroactivas o empezar desde cero

2. **Servicios no-Ads**:
   - ¿Aplicar este sistema solo a servicios tipo `ads`?
   - ¿O extender a otros servicios?

3. **Validaciones**:
   - No permitir `expense_ads_payment` mayor al saldo en custodia
   - Alertas cuando saldo en custodia es negativo

4. **UI/UX**:
   - Hacer el desglose intuitivo
   - Mostrar claramente el saldo disponible

## 📝 Próximos Pasos

1. ✅ Crear script SQL de migración
2. ✅ Crear clase `ProjectTransaction`
3. ✅ Modificar `Payment` para integrar desglose
4. ✅ Modificar `admin_payments.php` para formulario de desglose
5. ✅ Modificar `admin_client_detail.php` para estado de cuenta
6. ✅ Modificar `admin_expenses.php` para transacciones de proyectos
7. ✅ Modificar `admin_dashboard.php` para nuevas métricas
8. ✅ Agregar validaciones y alertas

## 🎯 Preguntas para el Usuario

1. ¿Aplicar este sistema solo a servicios tipo `ads` o a todos?
2. ¿Cómo manejar pagos ya registrados? ¿Crear transacciones retroactivas?
3. ¿Qué porcentaje por defecto para desglose? (50/50, 30/70, etc.)
4. ¿Mostrar alertas cuando saldo en custodia es negativo?
5. ¿Permitir `expense_ads_payment` mayor al saldo disponible?

