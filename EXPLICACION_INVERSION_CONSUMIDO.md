# Explicación: Inversión vs Consumido

## Conceptos Clave

### 1. **INVERSIÓN (income_ads)** 💵
**¿Qué es?**
- Es el dinero que el **cliente paga** para que se invierta en publicidad
- Este dinero entra a una **"billetera virtual"** del proyecto
- Es dinero que tienes **disponible** para gastar en plataformas publicitarias

**¿Cuándo se registra?**
- Cuando el cliente paga y se desglosa el pago:
  - Ejemplo: Cliente paga $1,000
  - $750 van a "Honorarios de Gestión" (income_fee) → va a tus ingresos
  - $250 van a "Fondo para Inversión" (income_ads) → entra a la billetera virtual
- Cuando se crea un servicio con "Monto de Inversión Inicial"
  - Ejemplo: Al crear el servicio, especificas $550 de inversión inicial
  - Esto crea automáticamente una transacción income_ads de $550
- Cuando se hace una "Inyección de Capital" a mitad de mes
  - Ejemplo: Cliente dice "agrega otros $500 a la campaña"
  - Se registra un nuevo pago y se desglosa, la parte de inversión va a income_ads

**Ejemplo:**
```
Cliente paga: $1,000
├─ Honorarios: $750 (income_fee) → Tus ingresos
└─ Inversión: $250 (income_ads) → Billetera virtual
```

---

### 2. **CONSUMIDO (expense_ads_consumed)** 📊
**¿Qué es?**
- Es el dinero que **realmente se ha gastado** en las plataformas publicitarias
- Representa el presupuesto que ya se usó en Meta, Google, TikTok, etc.
- Es dinero que **salió** de la billetera virtual

**¿Cuándo se registra?**
- Cuando usas el **icono naranja** (Costos del Servicio) para registrar "Presupuesto Consumido"
- Ejemplo: Gastaste $133 en Meta Ads este mes
  - Abres el modal del icono naranja
  - Registras: Monto $133, Plataforma META, Período, Descripción
  - Esto crea una transacción expense_ads_consumed de -$133

**Ejemplo:**
```
Gastaste en Meta: $133
└─ Se registra como expense_ads_consumed: -$133
```

---

### 3. **SALDO EN CUSTODIA** 💼
**¿Qué es?**
- Es la **diferencia** entre Inversión y Consumido
- Fórmula: `Saldo = Inversión - Consumido`

**Interpretación:**
- **Saldo POSITIVO** (verde): 
  - Tienes dinero disponible para gastar
  - El cliente ya pagó más de lo que has gastado
  - Ejemplo: Inversión $1,000 - Consumido $500 = Saldo $500 ✅

- **Saldo NEGATIVO** (rojo):
  - Estás financiando al cliente
  - Has gastado más de lo que el cliente ha pagado
  - Ejemplo: Inversión $500 - Consumido $1,000 = Saldo -$500 ⚠️
  - **Alerta**: El sistema te avisa cuando esto pasa

---

## Flujo Completo de Ejemplo

### Escenario: Cliente paga $1,000 para campaña de Ads

**Paso 1: Cliente paga**
```
Pago registrado: $1,000
├─ Honorarios: $750 (income_fee) → Tus ingresos brutos
└─ Inversión: $250 (income_ads) → Billetera virtual
```

**Estado:**
- Inversión: $250
- Consumido: $0
- Saldo en Custodia: $250 ✅

---

**Paso 2: Gastas $133 en Meta Ads**
```
Registras costo (icono naranja): $133
└─ expense_ads_consumed: -$133
```

**Estado:**
- Inversión: $250
- Consumido: $133
- Saldo en Custodia: $117 ✅ (aún tienes $117 disponibles)

---

**Paso 3: Gastas otros $200 en Google Ads**
```
Registras costo (icono naranja): $200
└─ expense_ads_consumed: -$200
```

**Estado:**
- Inversión: $250
- Consumido: $333
- Saldo en Custodia: -$83 ⚠️ (estás financiando al cliente)

---

**Paso 4: Cliente agrega más presupuesto**
```
Cliente paga otros $500
├─ Honorarios: $375 (income_fee)
└─ Inversión: $125 (income_ads)
```

**Estado:**
- Inversión: $375 ($250 + $125)
- Consumido: $333
- Saldo en Custodia: $42 ✅ (ahora tienes $42 disponibles)

---

## Resumen Visual

```
┌─────────────────────────────────────┐
│   BILLETERA VIRTUAL DEL PROYECTO   │
├─────────────────────────────────────┤
│                                     │
│  INGRESOS (income_ads)              │
│  ┌─────────────────────────────┐   │
│  │ Cliente paga: +$250          │   │
│  │ Inversión inicial: +$550    │   │
│  │ Inyección capital: +$125    │   │
│  │ TOTAL INVERSIÓN: $925       │   │
│  └─────────────────────────────┘   │
│                                     │
│  EGRESOS (expense_ads_consumed)     │
│  ┌─────────────────────────────┐   │
│  │ Meta Ads: -$133             │   │
│  │ Google Ads: -$200           │   │
│  │ TOTAL CONSUMIDO: $333       │   │
│  └─────────────────────────────┘   │
│                                     │
│  ────────────────────────────────  │
│  SALDO EN CUSTODIA: $592 ✅        │
│  (Dinero disponible para gastar)   │
└─────────────────────────────────────┘
```

---

## Preguntas Frecuentes

**P: ¿Por qué se llama "Consumido" y no "Gastado"?**
R: Porque representa el presupuesto que se "consumió" en las plataformas. Es más específico que "gastado" porque se refiere al presupuesto publicitario.

**P: ¿Puedo registrar un "Consumido" mayor que la "Inversión"?**
R: Sí, el sistema lo permite. Esto indica que estás financiando al cliente. El sistema te mostrará una alerta visual (rojo) cuando el saldo sea negativo.

**P: ¿La "Inversión Inicial" se crea automáticamente?**
R: Sí, desde la última actualización. Cuando creas un servicio Ads con "Monto de Inversión Inicial", se crea automáticamente una transacción income_ads.

**P: ¿Qué pasa si no registro los "Consumidos"?**
R: El saldo en custodia mostrará toda la inversión como disponible, pero en realidad ya la gastaste. Es importante registrar los consumidos para tener control real del presupuesto.

---

## Dónde Ver Cada Concepto

1. **Inversión (income_ads)**:
   - Se muestra como "Ads $XXX" debajo de la Tarifa en "Resumen por Proyecto"
   - En el modal "Ver Todas las Transacciones" aparece como "Inversión Publicitaria"

2. **Consumido (expense_ads_consumed)**:
   - Se muestra en el modal del icono naranja (Costos del Servicio)
   - Aparece como "Total Consumido" en la lista de costos

3. **Saldo en Custodia**:
   - Se muestra en la sección derecha del detalle del cliente
   - Aparece en el dashboard general
   - Se calcula automáticamente: Inversión - Consumido

