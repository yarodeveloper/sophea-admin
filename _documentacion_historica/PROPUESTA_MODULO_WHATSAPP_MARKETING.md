# 📱 Propuesta: Módulo de Marketing por WhatsApp Business API

## 🎯 Objetivo

Crear un módulo completo de marketing automatizado por WhatsApp que permita gestionar campañas, programar envíos masivos, controlar créditos y analizar resultados, todo integrado con la base de datos de leads existente.

---

## 📊 1. Dashboard Principal

### 1.1 Panel de Control de Créditos
- **Créditos Disponibles**: Muestra el saldo actual de créditos de WhatsApp
- **Créditos Usados (Mes Actual)**: Contador de mensajes enviados en el mes
- **Proyección de Uso**: Estimación de cuántos días durarán los créditos actuales
- **Gráfico de Uso**: Visualización del consumo diario/semanal/mensual
- **Alertas**: Notificaciones cuando los créditos estén por agotarse (< 20%)

### 1.2 Métricas Clave (KPIs)
- Total de mensajes enviados (hoy, semana, mes)
- Tasa de entrega (% de mensajes entregados)
- Tasa de lectura (% de mensajes leídos)
- Tasa de respuesta (% de mensajes con respuesta)
- Costo por mensaje
- ROI estimado de campañas

### 1.3 Actividad Reciente
- Últimas campañas ejecutadas
- Mensajes programados pendientes
- Errores recientes (si los hay)
- Leads nuevos que requieren atención

---

## 📅 2. Programación de Envíos

### 2.1 Tipos de Campañas

#### A. Campañas de Citas
- **Confirmación de Cita**: Envío automático X horas antes de la cita
- **Recordatorio de Cita**: Recordatorio 24h y 2h antes
- **Seguimiento Post-Cita**: Mensaje de agradecimiento y encuesta

**Características:**
- Integración con calendario de citas (si existe)
- Plantillas personalizables por tipo de servicio
- Parámetros dinámicos: nombre, fecha, hora, doctor, motivo
- Opción de confirmación con botones interactivos

#### B. Campañas de Cancelaciones
- **Notificación de Cancelación**: Informar al cliente sobre cancelación
- **Reagendamiento**: Ofrecer nuevas fechas disponibles
- **Política de Cancelación**: Recordar políticas y penalizaciones

**Características:**
- Envío inmediato al cancelar
- Sugerencias automáticas de nuevas fechas
- Botones para reagendar directamente

#### C. Campañas Promocionales
- **Promociones Especiales**: Descuentos, ofertas limitadas
- **Nuevos Servicios**: Anunciar nuevos tratamientos/servicios
- **Eventos**: Invitaciones a webinars, talleres, etc.
- **Cumpleaños**: Mensajes personalizados en fechas especiales

**Características:**
- Segmentación por especialidad, estado del lead, fecha de último contacto
- Programación de lanzamiento en fecha/hora específica
- Límite de envíos por día para evitar spam
- Opción de excluir leads que ya recibieron la promoción

#### D. Campañas de Seguimiento
- **Follow-up Automático**: Seguimiento después de X días sin contacto
- **Reactivación**: Campañas para leads inactivos
- **Nurturing**: Secuencia de mensajes educativos

### 2.2 Sistema de Programación
- **Calendario Visual**: Vista mensual/semanal con campañas programadas
- **Programación Flexible**:
  - Envío inmediato
  - Programar fecha y hora específica
  - Programación recurrente (diaria, semanal, mensual)
  - Envío basado en eventos (ej: 2 días después de crear lead)
- **Zonas Horarias**: Respetar horarios de atención (9 AM - 6 PM)
- **Días de la Semana**: Excluir fines de semana si se desea

---

## 👥 3. Gestión de Segmentación

### 3.1 Filtros de Segmentación
- **Por Estado del Lead**: Nuevo, Contactado, Calificado, Convertido
- **Por Especialidad/Giro**: Filtrar por especialidad del negocio
- **Por Fecha**: Leads creados en un rango específico
- **Por Último Contacto**: Leads sin contacto en X días
- **Por Ubicación**: Si se agrega campo de ciudad/estado
- **Por Fuente**: Website, WhatsApp, Referido, etc.
- **Por Etiquetas Personalizadas**: Sistema de tags para categorización

### 3.2 Listas de Contactos
- Crear y guardar listas personalizadas
- Combinar múltiples filtros
- Exportar/importar listas
- Compartir listas entre usuarios admin

---

## 📧 4. Editor de Mensajes

### 4.1 Editor Visual
- **Selector de Plantilla**: Elegir entre plantillas aprobadas
- **Editor de Texto**: Personalizar mensajes con variables dinámicas
- **Variables Disponibles**:
  - `{nombre}` - Nombre del lead
  - `{especialidad}` - Especialidad del negocio
  - `{fecha_cita}` - Fecha de cita (si aplica)
  - `{hora_cita}` - Hora de cita (si aplica)
  - `{doctor}` - Nombre del doctor
  - `{motivo}` - Motivo de la cita
  - `{telefono}` - Número de contacto
  - Y más según necesidades

### 4.2 Vista Previa
- Vista previa del mensaje con datos de ejemplo
- Simulación de cómo se verá en WhatsApp
- Validación de longitud (máximo 4096 caracteres)

### 4.3 Botones Interactivos (cuando aplique)
- Botones de respuesta rápida
- Listas desplegables
- Enlaces a sitios web

---

## 📈 5. Analytics y Reportes

### 5.1 Reportes de Campañas
- **Resumen de Campaña**:
  - Total de mensajes enviados
  - Mensajes entregados
  - Mensajes leídos
  - Respuestas recibidas
  - Tasa de conversión
  - Costo total

### 5.2 Gráficos y Visualizaciones
- Gráfico de líneas: Envíos por día
- Gráfico de barras: Comparación entre campañas
- Gráfico circular: Distribución por estado
- Heatmap: Horarios de mayor respuesta

### 5.3 Exportación de Datos
- Exportar reportes a CSV/Excel
- Generar PDF con resumen ejecutivo
- Envío automático de reportes por email

---

## 🔔 6. Automatizaciones Inteligentes

### 6.1 Flujos Automáticos
- **Nuevo Lead**: Envío automático de bienvenida
- **Lead Sin Contacto**: Recordatorio después de 3 días
- **Cita Programada**: Confirmación automática
- **Cita Cancelada**: Notificación y oferta de reagendamiento
- **Lead Convertido**: Mensaje de agradecimiento y encuesta

### 6.2 Reglas Personalizables
- Crear reglas "Si... entonces..."
- Ejemplo: "Si lead está en estado 'nuevo' por más de 2 días, enviar mensaje de seguimiento"
- Múltiples acciones por regla

---

## 💾 7. Estructura de Base de Datos Propuesta

### 7.1 Tabla: `whatsapp_campaigns`
```sql
CREATE TABLE whatsapp_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'personalizado') NOT NULL,
    template_name VARCHAR(100),
    message_text TEXT,
    status ENUM('draft', 'scheduled', 'sending', 'completed', 'paused', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_recipients INT DEFAULT 0,
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_read INT DEFAULT 0,
    total_replied INT DEFAULT 0,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_type (type)
);
```

### 7.2 Tabla: `whatsapp_campaign_recipients`
```sql
CREATE TABLE whatsapp_campaign_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    lead_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    message_id VARCHAR(100),
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    replied_at DATETIME NULL,
    error_message TEXT,
    FOREIGN KEY (campaign_id) REFERENCES whatsapp_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_status (status)
);
```

### 7.3 Tabla: `whatsapp_credits`
```sql
CREATE TABLE whatsapp_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    credits_available INT DEFAULT 0,
    credits_used INT DEFAULT 0,
    cost_per_message DECIMAL(10,4) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
);
```

### 7.4 Tabla: `whatsapp_templates_custom`
```sql
CREATE TABLE whatsapp_templates_custom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'otro') NOT NULL,
    template_text TEXT NOT NULL,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 7.5 Tabla: `whatsapp_automation_rules`
```sql
CREATE TABLE whatsapp_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    trigger_condition JSON NOT NULL,
    action_type ENUM('send_message', 'update_status', 'add_tag') NOT NULL,
    action_config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🎨 8. Interfaz de Usuario Propuesta

### 8.1 Estructura de Navegación
```
WhatsApp Marketing
├── Dashboard (Inicio)
│   ├── Créditos y Métricas
│   ├── Actividad Reciente
│   └── Gráficos de Rendimiento
│
├── Campañas
│   ├── Ver Todas las Campañas
│   ├── Crear Nueva Campaña
│   ├── Campañas Programadas
│   └── Historial de Campañas
│
├── Programar Envío
│   ├── Nueva Cita
│   ├── Cancelación
│   ├── Promoción
│   └── Personalizado
│
├── Segmentación
│   ├── Crear Lista
│   ├── Mis Listas
│   └── Filtros Avanzados
│
├── Plantillas
│   ├── Plantillas Aprobadas
│   ├── Plantillas Personalizadas
│   └── Crear Plantilla
│
├── Automatizaciones
│   ├── Reglas Activas
│   ├── Crear Regla
│   └── Log de Ejecuciones
│
└── Reportes
    ├── Reportes de Campañas
    ├── Análisis de Rendimiento
    └── Exportar Datos
```

---

## ⚙️ 9. Funcionalidades Técnicas

### 9.1 Integración con WhatsApp API
- Uso de la clase `WhatsAppAPI` existente
- Manejo de rate limiting de WhatsApp
- Reintentos automáticos en caso de fallo
- Manejo de errores específicos (token expirado, número inválido, etc.)

### 9.2 Sistema de Cola de Envíos
- Cola de mensajes para envíos masivos
- Procesamiento en lotes (batch processing)
- Control de velocidad de envío (respetar límites de API)
- Priorización de mensajes urgentes

### 9.3 Sincronización de Créditos
- Consulta automática de créditos disponibles vía API de Meta
- Actualización diaria del saldo
- Alertas cuando créditos < 20%

### 9.4 Webhook Integration
- Procesar actualizaciones de estado (entregado, leído, respondido)
- Actualizar automáticamente la base de datos
- Trigger de acciones basadas en respuestas

---

## 🚀 10. Mejoras Adicionales Propuestas

### 10.1 Sistema de Etiquetas (Tags)
- Etiquetar leads con categorías personalizadas
- Filtrar campañas por etiquetas
- Automatizar etiquetado basado en comportamiento

### 10.2 A/B Testing
- Probar diferentes mensajes en la misma campaña
- Comparar tasas de respuesta
- Seleccionar automáticamente el mejor mensaje

### 10.3 Respuestas Automáticas (Chatbot Básico)
- Respuestas automáticas a palabras clave
- Menú interactivo para opciones comunes
- Escalamiento a humano cuando sea necesario

### 10.4 Integración con Calendario
- Sincronizar con Google Calendar o calendario propio
- Envío automático de recordatorios basado en eventos
- Detección automática de cancelaciones

### 10.5 Personalización Avanzada
- Campos personalizados en leads
- Variables dinámicas en mensajes
- Condicionales en mensajes (si X entonces Y)

### 10.6 Notificaciones y Alertas
- Notificaciones por email de campañas completadas
- Alertas de errores críticos
- Recordatorios de campañas programadas

### 10.7 Multi-usuario
- Diferentes niveles de acceso
- Historial de quién creó/modificó cada campaña
- Límites de créditos por usuario

---

## 📋 11. Plan de Implementación

### Fase 1: Base y Dashboard (Semana 1-2)
- ✅ Crear estructura de base de datos
- ✅ Dashboard con créditos y métricas básicas
- ✅ Integración con API de créditos de Meta

### Fase 2: Campañas Básicas (Semana 3-4)
- ✅ Crear/editar campañas
- ✅ Programación de envíos
- ✅ Envío masivo básico

### Fase 3: Segmentación (Semana 5)
- ✅ Sistema de filtros
- ✅ Listas de contactos
- ✅ Integración con base de leads

### Fase 4: Tipos de Campañas Específicas (Semana 6-7)
- ✅ Campañas de citas
- ✅ Campañas de cancelaciones
- ✅ Campañas promocionales

### Fase 5: Analytics y Reportes (Semana 8)
- ✅ Dashboard de analytics
- ✅ Reportes exportables
- ✅ Gráficos y visualizaciones

### Fase 6: Automatizaciones (Semana 9-10)
- ✅ Sistema de reglas
- ✅ Flujos automáticos
- ✅ Webhook processing

### Fase 7: Mejoras y Optimización (Semana 11-12)
- ✅ A/B Testing
- ✅ Optimizaciones de rendimiento
- ✅ Testing completo

---

## 💰 12. Consideraciones de Costos

### 12.1 Créditos de WhatsApp
- **Conversación iniciada por empresa**: ~$0.005 - $0.09 USD por mensaje (según país)
- **Conversación iniciada por usuario**: Gratis dentro de ventana de 24h
- **Plantillas**: Gratis (pero requieren aprobación)

### 12.2 Recomendaciones
- Usar plantillas siempre que sea posible (gratis)
- Optimizar horarios para maximizar respuestas (ventana de 24h)
- Segmentar bien para evitar mensajes innecesarios
- Monitorear costos en tiempo real

---

## 🔒 13. Seguridad y Cumplimiento

### 13.1 Protección de Datos
- Encriptación de números de teléfono
- Logs de auditoría
- Consentimiento explícito para marketing
- Opción de opt-out en cada mensaje

### 13.2 Cumplimiento Legal
- Respetar horarios de atención
- No enviar spam
- Cumplir con políticas de WhatsApp
- Respetar preferencias de usuarios

---

## 📊 14. Métricas de Éxito

### KPIs a Monitorear
- **Tasa de Entrega**: > 95%
- **Tasa de Lectura**: > 60%
- **Tasa de Respuesta**: > 15%
- **Costo por Lead**: < $2 USD
- **ROI de Campañas**: > 300%
- **Tiempo de Respuesta**: < 2 horas

---

## 🎯 15. Próximos Pasos

1. **Revisar y aprobar esta propuesta**
2. **Definir prioridades** (qué funcionalidades son más urgentes)
3. **Crear mockups** de las interfaces principales
4. **Desarrollar Fase 1** (Dashboard y base de datos)
5. **Testing y feedback** continuo

---

## 📝 Notas Finales

Este módulo transformará SOPHEA en una plataforma completa de marketing automatizado por WhatsApp, permitiendo:
- ✅ Aumentar la tasa de conversión de leads
- ✅ Reducir el tiempo de respuesta
- ✅ Automatizar procesos repetitivos
- ✅ Mejorar la experiencia del cliente
- ✅ Optimizar el uso de créditos de WhatsApp
- ✅ Obtener insights valiosos sobre el comportamiento de clientes

**¿Estás listo para comenzar con la implementación?** 🚀

