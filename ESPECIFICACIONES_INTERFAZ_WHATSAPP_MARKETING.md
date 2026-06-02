# 🎨 Especificaciones de Interfaz - Módulo WhatsApp Marketing

## 📱 Página Principal: Dashboard

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  WhatsApp Marketing Dashboard                           │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Créditos     │  │ Enviados Hoy │  │ Tasa Entrega │ │
│  │ Disponibles  │  │   1,234       │  │    98.5%     │ │
│  │   5,000      │  └──────────────┘  └──────────────┘ │
│  │ (20% usado) │                                     │
│  └──────────────┘  ┌──────────────┐  ┌──────────────┐ │
│                    │ Tasa Lectura │  │ Tasa Resp.   │ │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Gráfico de Uso de Créditos (Últimos 30 días)   │ │
│  │ [Gráfico de líneas]                             │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Actividad Reciente                                    │
│  ┌──────────────────────────────────────────────────┐ │
│  │ • Campaña "Promo Enero" completada - 1,200 env. │ │
│  │ • Nueva campaña programada para mañana 10:00 AM │ │
│  │ • 5 mensajes fallidos requieren atención        │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Componentes Clave
- **Tarjetas de Métricas**: 4-6 tarjetas con KPIs principales
- **Gráfico de Créditos**: Visualización del consumo diario
- **Tabla de Actividad**: Últimas 10 acciones importantes
- **Alertas**: Notificaciones de créditos bajos, errores, etc.

---

## 📅 Página: Crear Campaña

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Crear Nueva Campaña                                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Paso 1: Tipo de Campaña                                │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                  │
│  │ Cita │ │Cancel│ │Promo │ │Seguim│                  │
│  └──────┘ └──────┘ └──────┘ └──────┘                  │
│                                                         │
│  Paso 2: Configuración                                  │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Nombre de la Campaña: [________________]         │ │
│  │                                                   │ │
│  │ Seleccionar Plantilla: [Dropdown ▼]             │ │
│  │                                                   │ │
│  │ Vista Previa:                                    │ │
│  │ ┌─────────────────────────────────────────────┐ │ │
│  │ │ Hola {nombre},                              │ │ │
│  │ │                                             │ │ │
│  │ │ Tu cita con {doctor} es el {fecha}...      │ │ │
│  │ └─────────────────────────────────────────────┘ │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Paso 3: Segmentación                                  │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Estado: [Todos ▼] Especialidad: [Todas ▼]      │ │
│  │ Último contacto: [Últimos 30 días ▼]           │ │
│  │                                                   │ │
│  │ Destinatarios estimados: 1,234 leads            │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Paso 4: Programación                                  │
│  ┌──────────────────────────────────────────────────┐ │
│  │ ○ Enviar ahora                                  │ │
│  │ ● Programar para: [Fecha] [Hora]                │ │
│  │                                                   │ │
│  │ ☑ Respetar horarios de atención (9 AM - 6 PM)  │ │
│  │ ☑ Excluir fines de semana                       │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  [Cancelar]                    [Guardar y Enviar]     │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Página: Lista de Campañas

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Campañas de Marketing                    [+ Nueva]     │
├─────────────────────────────────────────────────────────┤
│  Filtros: [Todas ▼] [Estado ▼] [Tipo ▼] [Buscar...]   │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Promo Enero 2024                    [●] Activa  │ │
│  │ Tipo: Promoción | Enviados: 1,200/1,234         │ │
│  │ Entrega: 98% | Lectura: 65% | Respuesta: 12%   │ │
│  │ Programada: 15 Ene 2024 10:00 AM                │ │
│  │ [Ver Detalles] [Pausar] [Duplicar] [Eliminar]   │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Recordatorio Citas Semana        [○] Programada  │ │
│  │ Tipo: Cita | Destinatarios: 45                   │ │
│  │ Programada: 20 Ene 2024 08:00 AM                │ │
│  │ [Ver Detalles] [Editar] [Cancelar]               │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  [Paginación: 1 2 3 ...]                               │
└─────────────────────────────────────────────────────────┘
```

---

## 📈 Página: Detalles de Campaña

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Campaña: Promo Enero 2024              [Editar] [●]    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Información General                                    │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Tipo: Promoción | Estado: Completada             │ │
│  │ Creada: 10 Ene 2024 | Enviada: 15 Ene 2024      │ │
│  │ Plantilla: appointment_confirmation_1            │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Métricas de Rendimiento                               │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │
│  │ Enviados │ │Entregados│ │  Leídos  │ │Respuestas│ │
│  │  1,234   │ │  1,215   │ │   802    │ │   148    │ │
│  │  100%    │ │   98.5%  │ │  66.0%   │ │  12.0%   │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Gráfico de Progreso                              │ │
│  │ [Gráfico de barras por día]                     │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Destinatarios (1,234)                                 │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Nombre        | Teléfono    | Estado    | Acción│ │
│  │ Juan Pérez    | 52961...    | Leído     | [Ver] │ │
│  │ María López   | 52961...    | Entregado | [Ver] │ │
│  │ ...           | ...         | ...       | ...   │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  [Exportar Reporte] [Ver Logs]                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 Página: Segmentación

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Segmentación de Leads              [+ Nueva Lista]    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Crear Nueva Lista                                      │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Nombre: [_____________________________]         │ │
│  │                                                   │ │
│  │ Filtros:                                         │ │
│  │ ☐ Estado: [Nuevo, Contactado, Calificado]       │ │
│  │ ☐ Especialidad: [Todas ▼]                       │ │
│  │ ☐ Fecha creación: [Últimos 30 días ▼]          │ │
│  │ ☐ Último contacto: [Sin contacto en 7 días]     │ │
│  │ ☐ Etiquetas: [Interesado, VIP]                  │ │
│  │                                                   │ │
│  │ Resultados: 1,234 leads encontrados              │ │
│  │ [Vista Previa] [Guardar Lista]                  │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  Mis Listas Guardadas                                  │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Leads Calientes (234)          [Usar] [Editar]   │ │
│  │ Leads Sin Contacto (156)       [Usar] [Editar]   │ │
│  │ Promoción Enero (1,200)       [Usar] [Editar]   │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## ⚙️ Página: Automatizaciones

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Automatizaciones              [+ Nueva Regla]          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Reglas Activas                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Bienvenida a Nuevos Leads          [●] Activa   │ │
│  │ Si: Lead creado                                   │ │
│  │ Entonces: Enviar plantilla "tes_unomedic"        │ │
│  │ Ejecutada: 45 veces hoy                           │ │
│  │ [Editar] [Pausar] [Eliminar]                      │ │
│  └──────────────────────────────────────────────────┘ │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Recordatorio Sin Contacto         [●] Activa     │ │
│  │ Si: Sin contacto por 3 días                      │ │
│  │ Entonces: Enviar seguimiento                      │ │
│  │ Ejecutada: 12 veces hoy                           │ │
│  │ [Editar] [Pausar] [Eliminar]                      │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## 🎨 Guía de Estilo

### Colores
- **Primario**: Púrpura (#667eea) - Botones principales, enlaces
- **Éxito**: Verde (#10b981) - Mensajes entregados, estados positivos
- **Advertencia**: Amarillo (#f59e0b) - Alertas, créditos bajos
- **Error**: Rojo (#ef4444) - Errores, fallos
- **Info**: Azul (#3b82f6) - Información, estados neutros

### Iconos
- Usar Phosphor Icons (ya implementado)
- Iconos consistentes para cada tipo de acción

### Tipografía
- Fuente: Inter (ya implementada)
- Títulos: Bold 600-800
- Texto: Regular 400
- Pequeño: 12-14px

---

## 📱 Responsive Design

### Mobile (< 768px)
- Dashboard: Tarjetas apiladas verticalmente
- Campañas: Lista simplificada
- Formularios: Campos a ancho completo
- Navegación: Menú hamburguesa

### Tablet (768px - 1024px)
- Dashboard: 2 columnas
- Campañas: Grid de 2 columnas
- Formularios: 2 columnas donde sea posible

### Desktop (> 1024px)
- Dashboard: 3-4 columnas
- Campañas: Tabla completa
- Formularios: Layout completo con sidebar

---

## 🔄 Flujos de Usuario

### Flujo 1: Crear Campaña Promocional
1. Click en "Nueva Campaña"
2. Seleccionar tipo "Promoción"
3. Elegir plantilla o escribir mensaje
4. Configurar segmentación
5. Programar fecha/hora
6. Revisar y confirmar
7. Campaña se programa y ejecuta automáticamente

### Flujo 2: Ver Resultados de Campaña
1. Ir a "Campañas"
2. Click en campaña deseada
3. Ver métricas en tiempo real
4. Revisar destinatarios individuales
5. Exportar reporte si necesario

### Flujo 3: Configurar Automatización
1. Ir a "Automatizaciones"
2. Click "Nueva Regla"
3. Definir condición (trigger)
4. Configurar acción
5. Activar regla
6. Monitorear ejecuciones

---

## 🎯 Mejoras de UX Propuestas

1. **Vista Previa en Tiempo Real**: Ver cómo se verá el mensaje mientras se escribe
2. **Validación Inteligente**: Detectar errores antes de enviar
3. **Sugerencias Automáticas**: Sugerir mejores horarios basados en historial
4. **Templates Sugeridos**: Recomendar plantillas según tipo de campaña
5. **Undo/Redo**: Deshacer cambios en el editor
6. **Atajos de Teclado**: Para usuarios avanzados
7. **Modo Oscuro**: Opcional para reducir fatiga visual

---

## 📊 Componentes Reutilizables

### 1. Tarjeta de Métrica
```html
<div class="metric-card">
  <div class="metric-value">1,234</div>
  <div class="metric-label">Mensajes Enviados</div>
  <div class="metric-change positive">+12% vs mes anterior</div>
</div>
```

### 2. Barra de Progreso de Campaña
```html
<div class="campaign-progress">
  <div class="progress-bar" style="width: 75%">
    <span>1,200 / 1,600 enviados</span>
  </div>
</div>
```

### 3. Badge de Estado
```html
<span class="status-badge status-active">Activa</span>
<span class="status-badge status-completed">Completada</span>
<span class="status-badge status-paused">Pausada</span>
```

---

## 🚀 Próximos Pasos de Diseño

1. Crear mockups detallados en Figma/Sketch
2. Prototipo interactivo para validar flujos
3. Testing de usabilidad con usuarios reales
4. Iteración basada en feedback
5. Implementación gradual por fases

