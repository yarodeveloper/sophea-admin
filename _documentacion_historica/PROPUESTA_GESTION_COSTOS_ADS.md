# 📊 Propuesta: Gestión de Costos de Servicios Externos (Ads, Facebook, etc.)

**Fecha:** 2025-01-27  
**Objetivo:** Implementar un sistema para gestionar costos directos de servicios como Facebook Ads, Google Ads, etc., que se pagan directamente a plataformas externas pero están asociados a proyectos/servicios de clientes.

---

## 🎯 Contexto del Problema

Actualmente, cuando se ofrece un servicio de **Gestión de Publicidad en Facebook/Instagram Ads**:
- El cliente contrata el servicio de gestión
- SOPHEA paga directamente a Facebook/Meta por la campaña publicitaria
- Este costo debe ser:
  - Registrado como gasto
  - Asociado al cliente/proyecto específico
  - Rastreado para facturación y rentabilidad
  - Visible en reportes del proyecto

**Ejemplo:**
- Cliente: "Empresa X"
- Servicio: "Gestión de Campañas Facebook Ads"
- Costo de campaña: $5,000 MXN (pagado directamente a Facebook)
- Este costo debe aparecer en el proyecto del cliente

---

## 📋 Opciones Propuestas

### **Opción 1: Extender Sistema de Gastos con Asociación a Proyectos**

**Descripción:** Agregar campos `client_id` y `service_id` a la tabla `expenses` para asociar gastos a proyectos específicos.

#### Estructura Propuesta:
```sql
ALTER TABLE expenses 
ADD COLUMN client_id INT NULL COMMENT 'ID del cliente asociado',
ADD COLUMN service_id INT NULL COMMENT 'ID del servicio/proyecto asociado',
ADD COLUMN expense_category ENUM('operational', 'client_service') DEFAULT 'operational' COMMENT 'Tipo de gasto',
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
ADD INDEX idx_client_id (client_id),
ADD INDEX idx_service_id (service_id);
```

#### Ventajas:
✅ **Reutiliza infraestructura existente** - No requiere crear nuevos módulos  
✅ **Unificación de reportes** - Todos los gastos en un solo lugar  
✅ **Fácil seguimiento** - Ver gastos operativos y de servicios en el mismo dashboard  
✅ **Flexibilidad** - Permite gastos sin asociación (operacionales) y con asociación (servicios)  
✅ **Implementación rápida** - Cambios mínimos en código existente  

#### Desventajas:
❌ **Mezcla conceptos** - Gastos operativos vs costos de servicios del cliente  
❌ **Filtros necesarios** - Requiere filtrar por `expense_category` en reportes  
❌ **Posible confusión** - Puede ser confuso distinguir entre tipos de gastos  

#### Casos de Uso:
- ✅ Gastos operativos (hosting, software, etc.) - `expense_category = 'operational'`
- ✅ Costos de servicios de clientes (Facebook Ads, Google Ads) - `expense_category = 'client_service'`

---

### **Opción 2: Sistema Separado para Costos de Servicios**

**Descripción:** Crear una nueva tabla `service_costs` o `campaign_costs` específica para costos asociados a servicios de clientes.

#### Estructura Propuesta:
```sql
CREATE TABLE service_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número único: COST-YYYY-MM-XXXX',
    client_id INT NOT NULL,
    service_id INT NOT NULL,
    cost_type ENUM('ads_facebook', 'ads_google', 'ads_instagram', 'platform_fee', 'other') NOT NULL,
    platform VARCHAR(100) NOT NULL COMMENT 'Facebook, Google, etc.',
    campaign_name VARCHAR(255) COMMENT 'Nombre de la campaña',
    campaign_id VARCHAR(255) COMMENT 'ID de la campaña en la plataforma',
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    payment_date DATE NOT NULL,
    billing_period_start DATE COMMENT 'Inicio del período facturado',
    billing_period_end DATE COMMENT 'Fin del período facturado',
    invoice_number VARCHAR(100) COMMENT 'Número de factura de la plataforma',
    receipt_url VARCHAR(500) COMMENT 'URL del comprobante',
    status ENUM('pending', 'paid', 'reimbursed', 'cancelled') DEFAULT 'pending',
    reimbursement_status ENUM('not_required', 'pending', 'billed', 'paid') DEFAULT 'not_required',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_service_id (service_id),
    INDEX idx_cost_type (cost_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Ventajas:
✅ **Separación clara** - Distingue claramente costos de servicios vs gastos operativos  
✅ **Campos específicos** - Campos dedicados para campañas (campaign_id, billing_period, etc.)  
✅ **Rastreo de reembolso** - Campo específico para rastrear si el cliente ya pagó  
✅ **Reportes especializados** - Reportes específicos para costos de servicios  
✅ **Escalabilidad** - Fácil agregar nuevos tipos de costos (TikTok Ads, LinkedIn Ads, etc.)  

#### Desventajas:
❌ **Duplicación de funcionalidad** - Similar a expenses pero separado  
❌ **Más complejidad** - Dos sistemas para gestionar  
❌ **Más desarrollo** - Requiere crear nueva interfaz y lógica  
❌ **Reportes separados** - Necesita consolidar datos de dos fuentes  

#### Casos de Uso:
- ✅ Costos de campañas publicitarias (Facebook, Google, Instagram)
- ✅ Comisiones de plataformas
- ✅ Costos específicos de servicios del cliente

---

## 🔄 Alternativas Adicionales

### **Opción 3: Sistema Híbrido - Gastos con Categoría Especial**

**Descripción:** Extender `expenses` con campos opcionales de asociación y una categoría especial "client_service_cost".

#### Estructura:
```sql
ALTER TABLE expenses 
ADD COLUMN client_id INT NULL,
ADD COLUMN service_id INT NULL,
ADD COLUMN is_client_service_cost BOOLEAN DEFAULT FALSE,
ADD COLUMN campaign_id VARCHAR(255) NULL COMMENT 'ID de campaña en plataforma externa',
ADD COLUMN billing_period_start DATE NULL,
ADD COLUMN billing_period_end DATE NULL,
ADD COLUMN reimbursement_status ENUM('not_required', 'pending', 'billed', 'paid') DEFAULT 'not_required',
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;
```

#### Ventajas:
✅ **Mejor de ambos mundos** - Unifica gastos pero permite diferenciación  
✅ **Campos específicos** - Agrega campos necesarios para campañas  
✅ **Filtros simples** - `WHERE is_client_service_cost = TRUE`  
✅ **Reportes unificados** - Un solo sistema con filtros  

#### Desventajas:
❌ **Tabla más compleja** - Más campos y lógica condicional  
❌ **Validación necesaria** - Si `is_client_service_cost = TRUE`, debe tener `client_id` y `service_id`  

---

### **Opción 4: Extender Servicios con "Costos Externos"**

**Descripción:** Agregar una tabla de relación `service_external_costs` que vincula servicios con gastos externos.

#### Estructura:
```sql
CREATE TABLE service_external_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    expense_id INT NOT NULL,
    allocation_percentage DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Porcentaje del gasto asignado a este servicio',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_service_expense (service_id, expense_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Ventajas:
✅ **Flexibilidad máxima** - Un gasto puede distribuirse entre múltiples servicios  
✅ **No modifica expenses** - Mantiene expenses puro  
✅ **Asociación flexible** - Permite asignar porcentajes  

#### Desventajas:
❌ **Más complejo** - Requiere JOINs para ver costos  
❌ **Lógica adicional** - Calcular costos totales por servicio  

---

### **Opción 5: Sistema de Presupuestos de Campaña**

**Descripción:** Crear un módulo de "Presupuestos de Campaña" que rastrea presupuesto asignado vs gastado.

#### Estructura:
```sql
CREATE TABLE campaign_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    client_id INT NOT NULL,
    platform ENUM('facebook', 'google', 'instagram', 'tiktok', 'linkedin', 'other') NOT NULL,
    budget_amount DECIMAL(10, 2) NOT NULL COMMENT 'Presupuesto asignado',
    spent_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Monto gastado',
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    campaign_id VARCHAR(255) COMMENT 'ID en la plataforma',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE campaign_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_budget_id INT NOT NULL,
    expense_id INT NOT NULL COMMENT 'Referencia al gasto en expenses',
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    FOREIGN KEY (campaign_budget_id) REFERENCES campaign_budgets(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Ventajas:
✅ **Control de presupuesto** - Rastrea presupuesto vs gastado  
✅ **Visibilidad clara** - Dashboard de campañas activas  
✅ **Alertas** - Puede alertar cuando se acerca al límite  
✅ **Reportes especializados** - ROI, eficiencia de campañas  

#### Desventajas:
❌ **Más complejo** - Sistema completo nuevo  
❌ **Más desarrollo** - Requiere más tiempo de implementación  
❌ **Puede ser excesivo** - Si solo necesitas registrar costos simples  

---

### **Opción 6: Integración con Facturación - Costos Reembolsables**

**Descripción:** Los costos se registran como gastos pero se marcan como "reembolsables" y se agregan automáticamente a la factura del cliente.

#### Estructura:
```sql
ALTER TABLE expenses 
ADD COLUMN client_id INT NULL,
ADD COLUMN service_id INT NULL,
ADD COLUMN is_reimbursable BOOLEAN DEFAULT FALSE,
ADD COLUMN reimbursement_rate DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Porcentaje a reembolsar',
ADD COLUMN reimbursed_in_invoice_id INT NULL COMMENT 'ID de factura donde se reembolsó',
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;
```

#### Ventajas:
✅ **Automatización** - Los costos reembolsables se agregan a facturas  
✅ **Rastreo completo** - Sabe qué factura incluyó qué costos  
✅ **Flexibilidad** - Permite marcar porcentaje de reembolso  

#### Desventajas:
❌ **Requiere sistema de facturación** - Depende de tener facturas implementadas  
❌ **Lógica compleja** - Calcular qué costos incluir en cada factura  

---

## 📊 Comparativa de Opciones

| Criterio | Opción 1 | Opción 2 | Opción 3 | Opción 4 | Opción 5 | Opción 6 |
|----------|----------|----------|----------|----------|----------|----------|
| **Facilidad de Implementación** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ |
| **Separación de Conceptos** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Flexibilidad** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Mantenibilidad** | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| **Escalabilidad** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Reportes** | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |

---

## 💡 Recomendación

### **Recomendación Principal: Opción 3 (Sistema Híbrido)**

**Razones:**
1. ✅ **Balance perfecto** - Unifica gastos pero permite diferenciación clara
2. ✅ **Implementación rápida** - Extiende sistema existente sin duplicar código
3. ✅ **Campos específicos** - Incluye campos necesarios para campañas (campaign_id, billing_period)
4. ✅ **Flexibilidad** - Permite gastos operativos y costos de servicios
5. ✅ **Reportes claros** - Fácil filtrar por `is_client_service_cost`
6. ✅ **Escalable** - Fácil agregar más campos específicos si se necesitan

### **Implementación Sugerida:**

#### 1. Modificar Tabla `expenses`:
```sql
ALTER TABLE expenses 
ADD COLUMN client_id INT NULL COMMENT 'ID del cliente (si es costo de servicio)',
ADD COLUMN service_id INT NULL COMMENT 'ID del servicio/proyecto (si es costo de servicio)',
ADD COLUMN is_client_service_cost BOOLEAN DEFAULT FALSE COMMENT 'Si es costo asociado a servicio de cliente',
ADD COLUMN campaign_id VARCHAR(255) NULL COMMENT 'ID de campaña en plataforma externa',
ADD COLUMN billing_period_start DATE NULL COMMENT 'Inicio del período facturado',
ADD COLUMN billing_period_end DATE NULL COMMENT 'Fin del período facturado',
ADD COLUMN reimbursement_status ENUM('not_required', 'pending', 'billed', 'paid') DEFAULT 'not_required' COMMENT 'Estado de reembolso al cliente',
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
ADD INDEX idx_client_id (client_id),
ADD INDEX idx_service_id (service_id),
ADD INDEX idx_is_client_service_cost (is_client_service_cost);

-- Agregar nuevo tipo de gasto
ALTER TABLE expenses 
MODIFY COLUMN expense_type ENUM(
    'hosting',
    'domain',
    'platform',
    'software',
    'salary',
    'freelancer',
    'marketing',
    'ads_facebook',
    'ads_google',
    'ads_instagram',
    'ads_other',
    'office',
    'utilities',
    'other'
) NOT NULL;
```

#### 2. Validación en Código:
- Si `is_client_service_cost = TRUE`, entonces `client_id` y `service_id` son obligatorios
- Si `expense_type` es 'ads_*', sugerir automáticamente `is_client_service_cost = TRUE`

#### 3. Interfaz de Usuario:
- **Al crear gasto:**
  - Checkbox: "¿Es costo de servicio de cliente?"
  - Si se marca: Mostrar selector de Cliente y Servicio
  - Si `expense_type` es 'ads_*': Auto-marcar checkbox y mostrar campos de campaña
- **Filtros en listado:**
  - "Todos los gastos"
  - "Solo gastos operativos"
  - "Solo costos de servicios"
  - "Por cliente"
  - "Por servicio"

#### 4. Reportes:
- **Dashboard de Gastos:**
  - Total gastos operativos
  - Total costos de servicios
  - Costos por cliente
  - Costos por servicio
- **Vista de Cliente:**
  - Mostrar costos asociados en detalle del cliente
  - Mostrar costos en resumen del servicio
- **Vista de Servicio:**
  - Mostrar todos los costos del servicio
  - Calcular rentabilidad: (Ingresos - Costos)

---

## 🎯 Casos de Uso Cubiertos

### Caso 1: Facebook Ads para Cliente
```
Cliente: Empresa X
Servicio: Gestión de Campañas Facebook Ads
Costo: $5,000 MXN
- Se registra como expense con:
  - expense_type = 'ads_facebook'
  - is_client_service_cost = TRUE
  - client_id = X
  - service_id = Y
  - campaign_id = 'fb_campaign_12345'
  - billing_period_start = '2025-01-01'
  - billing_period_end = '2025-01-31'
```

### Caso 2: Google Ads para Cliente
```
Similar a Facebook Ads pero con:
  - expense_type = 'ads_google'
  - campaign_id = 'google_campaign_67890'
```

### Caso 3: Gastos Operativos (sin cambios)
```
Gasto: Hosting mensual
- expense_type = 'hosting'
- is_client_service_cost = FALSE
- client_id = NULL
- service_id = NULL
```

---

## 📝 Próximos Pasos

1. **Revisar y aprobar** esta propuesta
2. **Definir campos adicionales** si se necesitan más específicos
3. **Implementar cambios en base de datos**
4. **Actualizar clase Expense.php** con nuevos métodos
5. **Actualizar interfaz admin_expenses.php**
6. **Agregar reportes** en dashboard y vistas de cliente/servicio
7. **Documentar** el nuevo flujo de trabajo

---

## ❓ Preguntas para Decisión

1. ¿Los costos de Ads se facturan al cliente o se absorben como costo operativo?
2. ¿Se necesita rastrear múltiples campañas por servicio?
3. ¿Se necesita calcular ROI automáticamente?
4. ¿Se necesita alertar cuando se excede un presupuesto?
5. ¿Hay otros tipos de costos externos además de Ads?

---

**¿Qué opción prefieres implementar?** 🤔

