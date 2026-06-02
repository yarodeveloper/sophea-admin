# 📊 Explicación: Estructura de `project_transactions`

## 🗂️ Estructura de Datos

### Tabla: `project_transactions`
Esta es la **TABLA** que almacena todas las transacciones financieras de proyectos.

### Campo: `transaction_type`
Este es un **CAMPO ENUM** en la tabla `project_transactions` que puede tener uno de estos **VALORES**:

1. **`income_fee`** - Honorarios de gestión (va al reporte de ventas)
2. **`income_ads`** - Inversión publicitaria (billetera virtual del proyecto)
3. **`expense_ads_consumed`** - Presupuesto consumido en plataformas

## 📋 Ejemplo de Registros

```sql
-- Registro 1: Honorarios de gestión
INSERT INTO project_transactions (
    service_id, 
    client_id, 
    transaction_type,  -- ← Campo
    amount
) VALUES (
    1, 
    2, 
    'income_fee',      -- ← Valor del campo transaction_type
    2000.00
);

-- Registro 2: Inversión publicitaria
INSERT INTO project_transactions (
    service_id, 
    client_id, 
    transaction_type,  -- ← Campo
    amount
) VALUES (
    1, 
    2, 
    'income_ads',      -- ← Valor del campo transaction_type
    2000.00
);

-- Registro 3: Presupuesto consumido
INSERT INTO project_transactions (
    service_id, 
    client_id, 
    transaction_type,  -- ← Campo
    amount
) VALUES (
    1, 
    2, 
    'expense_ads_consumed',  -- ← Valor del campo transaction_type
    -1500.00
);
```

## 🔍 Consultas

### Para obtener todas las inversiones en Ads:
```sql
SELECT * 
FROM project_transactions 
WHERE transaction_type = 'income_ads'  -- ← Filtrar por este valor
AND service_id = 1;
```

### Para obtener el total de inversión:
```sql
SELECT SUM(amount) as total_investment
FROM project_transactions
WHERE transaction_type = 'income_ads'  -- ← Filtrar por este valor
AND service_id = 1;
```

## 📊 Resumen

- **Tabla**: `project_transactions` (una sola tabla)
- **Campo**: `transaction_type` (un campo ENUM)
- **Valores posibles**:
  - `income_fee` (Honorarios)
  - `income_ads` (Inversión publicitaria) ← Este es el que buscamos
  - `expense_ads_consumed` (Presupuesto consumido)

## ⚠️ Problema Común

Si las transacciones `income_ads` no tienen `service_id` asociado, la consulta:
```sql
SELECT SUM(amount) 
FROM project_transactions 
WHERE transaction_type = 'income_ads' 
AND service_id = 1;  -- ← No encontrará nada si service_id es NULL
```

No encontrará resultados porque `service_id` es `NULL` o `0`.

## ✅ Solución

Usar el script `database/fix_income_ads_service_id.sql` para actualizar las transacciones usando el `service_id` del pago asociado.

