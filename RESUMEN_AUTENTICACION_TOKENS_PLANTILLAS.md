# 🔐 Resumen Ejecutivo: Autenticación, Tokens y Plantillas - WhatsApp

## 📋 Resumen Rápido

Este documento resume los aspectos más importantes sobre **autenticación**, **tokens** y **plantillas** en el módulo de WhatsApp de SOPHEA.

---

## 🔑 1. AUTENTICACIÓN Y TOKENS

### ¿Qué es un Token?

Un **Access Token** es una credencial de seguridad que permite a tu aplicación comunicarse con la API de WhatsApp Business de Meta. Es como una "llave" que autentica cada solicitud.

### Tipos de Tokens

| Tipo | Duración | Uso Recomendado |
|------|----------|-----------------|
| **Corta duración** | 1-2 horas | Solo desarrollo |
| **Larga duración** | 60 días | ✅ Producción |
| **Permanente** | No expira | ✅ Producción ideal |

### Cómo Obtener un Token

1. **Accede a Meta Business Manager**
   - URL: https://business.facebook.com
   - Navega: Configuración → Recursos empresariales → Tu App → WhatsApp → API Setup

2. **Genera el Token**
   - Selecciona permisos: `whatsapp_business_messaging` y `whatsapp_business_management`
   - Haz clic en "Generar Token de Acceso"
   - ⚠️ **Copia inmediatamente** (no podrás verlo de nuevo)

3. **Configura en SOPHEA**
   - Accede a: `admin_whatsapp_config.php`
   - Pega el token en "Access Token"
   - Guarda la configuración

### Dónde se Almacena

```php
// Archivo: config_whatsapp.php
define('WHATSAPP_ACCESS_TOKEN', 'TU_TOKEN_AQUI');
```

**⚠️ IMPORTANTE:**
- No subir este archivo a repositorios públicos
- Usar permisos restrictivos (`chmod 600`)
- Considerar variables de entorno en producción

### Cómo se Usa

El token se envía automáticamente en cada request a la API:

```http
POST https://graph.facebook.com/v18.0/PHONE_NUMBER_ID/messages
Authorization: Bearer TU_TOKEN_AQUI
Content-Type: application/json
```

### Validación del Token

El sistema verifica automáticamente el token en el panel de administración:

- ✅ **Verde**: Token válido
- ⚠️ **Amarillo**: Token por expirar (menos de 7 días)
- ❌ **Rojo**: Token expirado

**Ubicación**: `admin_whatsapp_config.php` → Muestra estado del token automáticamente

### Renovación del Token

**Cuando expira:**
- Error: `Error validating access token: Session has expired (Code: 190)`
- **Solución**: Generar nuevo token (ver pasos arriba)

**Para evitar renovaciones frecuentes:**
- Usa token de **larga duración** (60 días)
- O mejor aún, configura un **token permanente** (System User Token)

### Errores Comunes

| Código | Error | Solución |
|--------|-------|----------|
| 190 | Token expirado | Generar nuevo token |
| 200 | Token inválido | Verificar que sea correcto |
| 10 | Permisos insuficientes | Verificar permisos en Meta |

---

## 📝 2. PLANTILLAS DE WHATSAPP

### ¿Qué son las Plantillas?

Las **plantillas** son mensajes pre-aprobados por Meta que permiten enviar mensajes fuera de la ventana de 24 horas.

### ¿Cuándo Usar Plantillas?

✅ **Obligatorio usar plantillas cuando:**
- Envías mensajes fuera de la ventana de 24 horas
- Envías mensajes promocionales
- Realizas campañas de marketing
- Envías mensajes automatizados

✅ **Puedes usar mensajes libres cuando:**
- El usuario te escribió en las últimas 24 horas
- Es una respuesta directa a su mensaje

### Ventana de 24 Horas

```
Usuario escribe: "Hola" (10:00 AM)
├─ ✅ Puedes responder libremente hasta: 10:00 AM del día siguiente
└─ ❌ Después de eso: Solo plantillas aprobadas
```

### Tipos de Plantillas

#### 1. Plantillas Aprobadas por Meta
- Creadas y aprobadas en Meta Business Manager
- Disponibles en múltiples idiomas
- Pueden tener variables (parámetros)

#### 2. Plantillas Personalizadas (Sistema)
- Definidas en SOPHEA
- Almacenadas en base de datos (`whatsapp_templates_custom`)
- **Deben estar aprobadas en Meta** para funcionar

### Crear una Plantilla en Meta

1. **Accede a Meta Business Manager**
   - URL: https://business.facebook.com
   - Navega: WhatsApp → Plantillas de mensajes

2. **Crea Nueva Plantilla**
   - Haz clic en "Crear plantilla"
   - Selecciona tipo: Texto, Texto con botones, etc.

3. **Configura la Plantilla**
   - **Nombre**: Sin espacios, solo minúsculas (ej: `recordatorio_cita`)
   - **Categoría**: MARKETING, UTILITY, o AUTHENTICATION
   - **Idioma**: Selecciona (ej: `es`, `es_MX`)
   - **Contenido**: Usa `{{1}}`, `{{2}}`, etc. para variables
     ```
     Ejemplo: Hola {{1}}, tu cita es el {{2}} a las {{3}}.
     ```

4. **Envía para Aprobación**
   - Revisa y envía
   - Meta revisará (puede tardar horas o días)
   - Una vez aprobada, estará disponible

### Usar Plantillas en el Código

#### Método Simple

```php
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

// Enviar plantilla con parámetros
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',                    // Número de teléfono
    'recordatorio',                    // Nombre de la plantilla
    ['Juan Pérez', '2024-01-15', '10:00 AM']  // Parámetros
);

if ($result['success']) {
    echo "Mensaje enviado. ID: " . $result['message_id'];
}
```

#### Parámetros de Plantilla

Si tu plantilla es:
```
Hola {{1}}, tu cita es el {{2}} a las {{3}}.
```

El código sería:
```php
$params = [
    'Juan Pérez',      // {{1}} - Nombre
    '2024-01-15',     // {{2}} - Fecha
    '10:00 AM'        // {{3}} - Hora
];

$whatsappAPI->sendTemplateMessage('521234567890', 'recordatorio', $params);
```

**Resultado enviado:**
```
Hola Juan Pérez, tu cita es el 2024-01-15 a las 10:00 AM.
```

### Idiomas de Plantillas

El sistema intenta automáticamente diferentes idiomas si uno falla:

```php
// Orden de intento:
1. 'es'      (Español genérico)
2. 'es_MX'   (Español México)
3. 'es_ES'   (Español España)
4. 'en_US'   (Inglés Estados Unidos)
5. 'en'      (Inglés genérico)
```

**Especificar idioma:**
```php
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',
    'recordatorio',
    ['Juan', '2024-01-15'],
    'es_MX'  // Idioma específico
);
```

### Errores Comunes con Plantillas

| Código | Error | Solución |
|--------|-------|----------|
| 132000 | Plantilla no existe | Verificar nombre exacto en Meta |
| 132001 | No encontrada en idioma | Verificar traducción |
| 132005 | Parámetros inválidos | Verificar número de parámetros |
| 131026 | Fuera de ventana de 24h | Usar plantilla (ya estás usando) |

### Gestión de Plantillas en SOPHEA

#### Plantillas Personalizadas

```php
require_once 'classes/WhatsAppMarketing.php';

$marketing = new WhatsAppMarketing();

// Obtener todas las plantillas
$templates = $marketing->getAllCustomTemplates();

// Crear nueva plantilla personalizada
$templateData = [
    'name' => 'bienvenida_nuevo_cliente',
    'category' => 'bienvenida',
    'template_text' => 'Hola {{nombre}}, bienvenido a SOPHEA. Tu código es {{codigo}}.',
    'variables' => ['nombre', 'codigo'],
    'example_data' => [
        'nombre' => 'Juan Pérez',
        'codigo' => 'ABC123'
    ],
    'is_active' => true,
    'requires_approval' => true
];

$templateId = $marketing->createCustomTemplate($templateData);
```

**Nota**: Las plantillas personalizadas en SOPHEA son solo para organización interna. **Deben estar aprobadas en Meta** para poder usarlas.

---

## 🔄 3. FLUJO COMPLETO: Token → Plantilla → Envío

### Paso 1: Configurar Token

```php
// 1. Obtener token de Meta Business Manager
// 2. Configurar en config_whatsapp.php
define('WHATSAPP_ACCESS_TOKEN', 'TU_TOKEN_AQUI');

// 3. Verificar en admin_whatsapp_config.php
// El panel mostrará el estado del token
```

### Paso 2: Crear Plantilla en Meta

```
1. Meta Business Manager → WhatsApp → Plantillas
2. Crear plantilla "recordatorio"
3. Contenido: "Hola {{1}}, tu cita es el {{2}}"
4. Enviar para aprobación
5. Esperar aprobación de Meta
```

### Paso 3: Usar en el Código

```php
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

// Verificar token (opcional)
$tokenStatus = $whatsappAPI->checkTokenValidity();
if (!$tokenStatus['valid']) {
    die("Token inválido: " . $tokenStatus['error']);
}

// Enviar mensaje con plantilla
try {
    $result = $whatsappAPI->sendTemplateMessage(
        '521234567890',
        'recordatorio',
        ['Juan Pérez', '2024-01-15']
    );
    
    if ($result['success']) {
        echo "✅ Enviado. ID: " . $result['message_id'];
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
```

---

## ⚠️ 4. PUNTOS CRÍTICOS

### Seguridad del Token

- ❌ **NUNCA** compartas el token públicamente
- ❌ **NUNCA** lo subas a repositorios públicos
- ✅ Usa permisos restrictivos en `config_whatsapp.php`
- ✅ Considera variables de entorno en producción

### Validación de Plantillas

- ✅ Verifica que la plantilla esté **aprobada** en Meta antes de usarla
- ✅ El nombre debe ser **exacto** (case-sensitive, sin espacios)
- ✅ El número de parámetros debe **coincidir** con la plantilla

### Ventana de 24 Horas

- ✅ Usa **mensajes libres** solo dentro de 24 horas
- ✅ Usa **plantillas** fuera de 24 horas
- ✅ El sistema intenta automáticamente usar plantilla si falla mensaje libre

---

## 📚 5. RECURSOS ADICIONALES

### Documentación Completa
- **[DOCUMENTACION_MODULO_WHATSAPP.md](./DOCUMENTACION_MODULO_WHATSAPP.md)** - Documentación completa
- **[INDICE_DOCUMENTACION_WHATSAPP.md](./INDICE_DOCUMENTACION_WHATSAPP.md)** - Índice de navegación

### Enlaces Útiles
- **Meta Business Manager**: https://business.facebook.com
- **Documentación Meta**: https://developers.facebook.com/docs/whatsapp
- **Graph API Explorer**: https://developers.facebook.com/tools/explorer/

### Archivos del Sistema
- `config_whatsapp.php` - Configuración principal
- `classes/WhatsAppAPI.php` - Clase para API
- `admin_whatsapp_config.php` - Panel de configuración
- `RENOVAR_TOKEN_WHATSAPP.md` - Guía de renovación

---

## ✅ Checklist Rápido

### Configuración Inicial
- [ ] Token obtenido de Meta Business Manager
- [ ] Token configurado en `config_whatsapp.php`
- [ ] Token verificado en `admin_whatsapp_config.php`
- [ ] Phone Number ID configurado
- [ ] Business Account ID configurado

### Plantillas
- [ ] Plantillas creadas en Meta Business Manager
- [ ] Plantillas aprobadas por Meta
- [ ] Nombres de plantillas verificados (sin espacios, minúsculas)
- [ ] Parámetros de plantillas documentados

### Pruebas
- [ ] Token válido (verde en panel)
- [ ] Mensaje de prueba enviado exitosamente
- [ ] Plantilla de prueba enviada exitosamente
- [ ] Webhook configurado (si aplica)

---

**Última actualización**: 2024

