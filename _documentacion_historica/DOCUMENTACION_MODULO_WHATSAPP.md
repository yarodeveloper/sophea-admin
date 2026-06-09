# 📱 Documentación Completa del Módulo WhatsApp - SOPHEA

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Módulo](#arquitectura-del-módulo)
3. [Autenticación y Tokens](#autenticación-y-tokens)
4. [Configuración](#configuración)
5. [Plantillas de WhatsApp](#plantillas-de-whatsapp)
6. [Envío de Mensajes](#envío-de-mensajes)
7. [Webhooks](#webhooks)
8. [Módulo de Marketing](#módulo-de-marketing)
9. [Manejo de Errores](#manejo-de-errores)
10. [Seguridad](#seguridad)
11. [Troubleshooting](#troubleshooting)

---

## 1. Introducción

El módulo de WhatsApp de SOPHEA permite la integración completa con la **WhatsApp Business API** de Meta, permitiendo:

- ✅ Envío de mensajes de texto libres
- ✅ Envío de mensajes usando plantillas aprobadas por Meta
- ✅ Recepción de mensajes entrantes
- ✅ Seguimiento de estados de mensajes (enviado, entregado, leído)
- ✅ Campañas de marketing masivo
- ✅ Gestión de créditos y costos
- ✅ Webhooks para eventos en tiempo real

### Archivos Principales

```
sopheaadmin/
├── config_whatsapp.php              # Configuración principal
├── classes/
│   ├── WhatsAppAPI.php              # Clase principal para API
│   └── WhatsAppMarketing.php        # Clase para marketing
├── admin_whatsapp_config.php        # Panel de configuración
├── send_whatsapp.php                # Endpoint para envío
├── webhook_whatsapp.php             # Webhook handler
└── admin_whatsapp_marketing.php     # Panel de marketing
```

---

## 2. Arquitectura del Módulo

### 2.1 Flujo de Autenticación

```
┌─────────────────┐
│  Meta Business  │
│     Manager     │
└────────┬────────┘
         │
         │ 1. Genera Access Token
         │
         ▼
┌─────────────────┐
│ config_whatsapp │
│     .php        │ ◄─── Almacena token
└────────┬────────┘
         │
         │ 2. Carga configuración
         │
         ▼
┌─────────────────┐
│  WhatsAppAPI    │
│     .php        │ ◄─── Usa token en requests
└────────┬────────┘
         │
         │ 3. Envía con Bearer Token
         │
         ▼
┌─────────────────┐
│  Meta Graph API │
│  (WhatsApp API) │
└─────────────────┘
```

### 2.2 Componentes Principales

#### **WhatsAppAPI.php**
Clase principal que maneja:
- Autenticación con tokens
- Normalización de números telefónicos
- Envío de mensajes de texto
- Envío de mensajes con plantillas
- Validación de tokens
- Manejo de errores de API

#### **WhatsAppMarketing.php**
Clase para funcionalidades avanzadas:
- Gestión de campañas
- Seguimiento de créditos
- Análisis y métricas
- Plantillas personalizadas
- Segmentación de leads

---

## 3. Autenticación y Tokens

### 3.1 ¿Qué es un Access Token?

El **Access Token** es una credencial de seguridad que permite a tu aplicación autenticarse con la API de WhatsApp Business de Meta. Es similar a una contraseña, pero específica para acceso a la API.

### 3.2 Tipos de Tokens

#### **Token de Corta Duración**
- ⏱️ **Duración**: 1-2 horas
- 🔄 **Uso**: Solo para desarrollo y pruebas
- ⚠️ **No recomendado** para producción

#### **Token de Larga Duración**
- ⏱️ **Duración**: 60 días
- ✅ **Recomendado** para producción
- 🔄 Se genera desde un token de corta duración

#### **Token Permanente (System User Token)**
- ⏱️ **Duración**: No expira (hasta que se revoque manualmente)
- ✅ **Ideal** para producción
- 🔧 Requiere configuración adicional en Meta Business Manager

### 3.3 Obtención del Access Token

#### **Paso 1: Acceder a Meta Business Manager**
1. Ve a: https://business.facebook.com
2. Inicia sesión con tu cuenta de Facebook Business

#### **Paso 2: Navegar a la Configuración de WhatsApp**
1. Ve a **Configuración** → **Recursos empresariales**
2. Selecciona tu **App de WhatsApp Business**
3. Ve a **WhatsApp** → **API Setup**

#### **Paso 3: Generar el Token**
1. En la sección **"Temporary access token"** o **"System user access token"**
2. Selecciona los permisos necesarios:
   - `whatsapp_business_messaging` - Para enviar mensajes
   - `whatsapp_business_management` - Para gestionar la cuenta
3. Haz clic en **"Generar Token de Acceso"**
4. **⚠️ IMPORTANTE**: Copia el token inmediatamente, no podrás verlo de nuevo

#### **Paso 4: Configurar en SOPHEA**
1. Accede a: `admin_whatsapp_config.php`
2. Pega el token en el campo **"Access Token"**
3. Haz clic en **"Guardar Configuración"**

### 3.4 Almacenamiento del Token

El token se almacena en `config_whatsapp.php`:

```php
define('WHATSAPP_ACCESS_TOKEN', 'TU_TOKEN_AQUI');
```

**⚠️ SEGURIDAD**: 
- Este archivo contiene información sensible
- No lo subas a repositorios públicos
- Mantén permisos restrictivos (chmod 600)
- Considera usar variables de entorno en producción

### 3.5 Validación del Token

El sistema incluye una función para verificar la validez del token:

```php
// En WhatsAppAPI.php
public function checkTokenValidity() {
    // Verifica si el token es válido
    // Retorna información sobre expiración
    // Detecta si está próximo a expirar
}
```

**Uso en el panel de administración:**
- El panel `admin_whatsapp_config.php` verifica automáticamente el token
- Muestra alertas si:
  - El token ha expirado (rojo)
  - El token expirará pronto (amarillo)
  - El token es válido (verde)

### 3.6 Renovación del Token

#### **Cuando el Token Expira**

Si recibes el error:
```
Error validating access token: Session has expired (Code: 190)
```

**Solución:**
1. Genera un nuevo token siguiendo los pasos de la sección 3.3
2. Actualiza el token en `admin_whatsapp_config.php`
3. Guarda la configuración

#### **Generar Token de Larga Duración (60 días)**

Para evitar renovaciones frecuentes:

1. **Obtén un token de corta duración** (paso 3.3)
2. **Convierte a token de larga duración**:
   ```
   https://graph.facebook.com/v18.0/oauth/access_token?
     grant_type=fb_exchange_token&
     client_id=TU_APP_ID&
     client_secret=TU_APP_SECRET&
     fb_exchange_token=TOKEN_CORTA_DURACION
   ```
3. **Copia el nuevo token** y actualízalo en la configuración

### 3.7 Uso del Token en Requests

El token se envía en el header `Authorization` de cada request:

```php
$headers = [
    'Authorization: Bearer ' . $this->accessToken,
    'Content-Type: application/json'
];
```

**Ejemplo de Request:**
```http
POST https://graph.facebook.com/v18.0/619215614617031/messages
Authorization: Bearer EAATmFem3hWYBQPcRk0v1hWQn4CkqDq3GbBmeQ06w1D8DVZAZBAwytHZA2QVbsXSXYLTB4Stgp3whv2wntPvaHSTm6ons8ZCqJdr2kZCsTdMlU0fR97lSqxY6ZCBhsDJSK0aFDtXMnSkZCbqbgMyyK8K1doHFrafZB2i46AcvvrhbjDmGSDbejU2ZAeOO0xutUoEbS5ZAjluMecfm1ZAQiQpylSjF2ZBcOob7kgybsvphIEpz7Ps4jaTKZA3GEbuu7RUpqTWvXZBxDFTtvDfHUPin66CQkvDPLEyGCSVUln1LGHDgZDZD
Content-Type: application/json

{
  "messaging_product": "whatsapp",
  "to": "521234567890",
  "type": "text",
  "text": {
    "body": "Hola, este es un mensaje de prueba"
  }
}
```

### 3.8 Errores Comunes de Autenticación

| Código | Error | Solución |
|--------|-------|----------|
| 190 | Token expirado | Generar nuevo token |
| 200 | Token inválido | Verificar que el token sea correcto |
| 10 | Permisos insuficientes | Verificar permisos en Meta Business Manager |

---

## 4. Configuración

### 4.1 Archivo de Configuración

**Ubicación**: `config_whatsapp.php`

```php
<?php
// Estado de la API
define('WHATSAPP_API_ENABLED', true);

// IDs de WhatsApp Business
define('WHATSAPP_PHONE_NUMBER_ID', '619215614617031');
define('WHATSAPP_BUSINESS_ACCOUNT_ID', '130339163500704');

// Token de acceso
define('WHATSAPP_ACCESS_TOKEN', 'TU_TOKEN_AQUI');

// Versión de la API
define('WHATSAPP_API_VERSION', 'v18.0');

// URL base de la API
define('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/' . WHATSAPP_API_VERSION);

// Configuración de webhook
define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'SwEwGuW1g3DGRyi7rhVdsd6VrmZGYgI4');
define('WHATSAPP_WEBHOOK_URL', 'https://ia.sopheamkt.com/webhook_whatsapp.php');

// Configuración adicional
define('WHATSAPP_LOG_MESSAGES', true);
define('WHATSAPP_DEFAULT_MESSAGE_TEMPLATE', 'Hola {nombre}, gracias por contactarnos.');
```

### 4.2 Parámetros de Configuración

#### **WHATSAPP_API_ENABLED**
- **Tipo**: Boolean
- **Descripción**: Habilita/deshabilita el módulo de WhatsApp
- **Uso**: Permite desactivar temporalmente sin eliminar configuración

#### **WHATSAPP_PHONE_NUMBER_ID**
- **Tipo**: String (numérico)
- **Descripción**: ID del número de teléfono de WhatsApp Business
- **Dónde encontrarlo**: Meta Business Manager → WhatsApp → API Setup

#### **WHATSAPP_BUSINESS_ACCOUNT_ID**
- **Tipo**: String (numérico)
- **Descripción**: ID de la cuenta de WhatsApp Business
- **Dónde encontrarlo**: Meta Business Manager → Configuración → Recursos empresariales

#### **WHATSAPP_ACCESS_TOKEN**
- **Tipo**: String (token largo)
- **Descripción**: Token de acceso para autenticación
- **⚠️ SENSIBLE**: No compartir ni exponer públicamente

#### **WHATSAPP_API_VERSION**
- **Tipo**: String
- **Valores comunes**: `v18.0`, `v19.0`, `v20.0`
- **Descripción**: Versión de la API de Meta Graph
- **Recomendación**: Usar la versión más reciente estable

#### **WHATSAPP_WEBHOOK_VERIFY_TOKEN**
- **Tipo**: String
- **Descripción**: Token para verificar el webhook con Meta
- **Uso**: Debe coincidir con el configurado en Meta Business Manager

#### **WHATSAPP_WEBHOOK_URL**
- **Tipo**: URL completa
- **Descripción**: URL pública donde Meta enviará eventos
- **Requisitos**: 
  - Debe ser HTTPS en producción
  - Debe ser accesible públicamente
  - No debe requerir autenticación para GET requests

### 4.3 Panel de Configuración

**Ubicación**: `admin_whatsapp_config.php`

**Características:**
- ✅ Interfaz visual para editar configuración
- ✅ Validación de token en tiempo real
- ✅ Alertas de expiración de token
- ✅ Generador de tokens de verificación
- ✅ Backup automático antes de guardar cambios

**Acceso:**
1. Navega a: `https://tudominio.com/admin_whatsapp_config.php`
2. Ingresa la contraseña de administración
3. Edita los valores necesarios
4. Haz clic en **"Guardar Configuración"**

---

## 5. Plantillas de WhatsApp

### 5.1 ¿Qué son las Plantillas?

Las **plantillas** son mensajes pre-aprobados por Meta que permiten enviar mensajes fuera de la ventana de 24 horas. Son obligatorias para:

- ✅ Mensajes promocionales
- ✅ Mensajes fuera de la ventana de 24 horas
- ✅ Campañas de marketing
- ✅ Mensajes automatizados

### 5.2 Ventana de 24 Horas

**Regla de WhatsApp:**
- Puedes enviar mensajes **libres** (sin plantilla) solo dentro de las **24 horas** después de que el usuario te haya escrito
- Después de 24 horas, **debes usar plantillas** aprobadas

**Ejemplo:**
```
Usuario escribe: "Hola" (10:00 AM)
├─ Puedes responder libremente hasta: 10:00 AM del día siguiente
└─ Después de eso: Solo plantillas aprobadas
```

### 5.3 Tipos de Plantillas

#### **1. Plantillas Aprobadas por Meta**

Estas son plantillas que has creado y Meta ha aprobado en tu cuenta de WhatsApp Business.

**Características:**
- ✅ Pre-aprobadas por Meta
- ✅ Disponibles en múltiples idiomas
- ✅ Pueden tener variables (parámetros)
- ✅ Se almacenan en Meta Business Manager

**Ejemplo de uso:**
```php
$whatsappAPI = new WhatsAppAPI();
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',           // Número de teléfono
    'recordatorio',            // Nombre de la plantilla
    ['Juan', '2024-01-15']    // Parámetros
);
```

#### **2. Plantillas Personalizadas (Sistema)**

Estas son plantillas que defines en el sistema SOPHEA pero que **deben estar aprobadas en Meta**.

**Almacenamiento**: Tabla `whatsapp_templates_custom`

**Estructura:**
```sql
CREATE TABLE whatsapp_templates_custom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('cita', 'cancelacion', 'promocion', 'seguimiento', 'bienvenida', 'otro'),
    template_text TEXT NOT NULL,
    variables JSON,
    example_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Ejemplo de plantilla personalizada:**
```json
{
  "name": "recordatorio_cita",
  "category": "cita",
  "template_text": "Hola {{nombre}}, te recordamos tu cita el {{fecha}} a las {{hora}}.",
  "variables": ["nombre", "fecha", "hora"],
  "example_data": {
    "nombre": "Juan Pérez",
    "fecha": "2024-01-15",
    "hora": "10:00 AM"
  }
}
```

### 5.4 Crear una Plantilla en Meta

#### **Paso 1: Acceder a Meta Business Manager**
1. Ve a: https://business.facebook.com
2. Navega a: **WhatsApp** → **Plantillas de mensajes**

#### **Paso 2: Crear Nueva Plantilla**
1. Haz clic en **"Crear plantilla"**
2. Selecciona el **tipo de plantilla**:
   - **Texto**: Solo texto
   - **Texto con botones**: Texto + botones de acción
   - **Texto con lista**: Texto + lista desplegable
   - **Medios**: Con imagen/video/documento

#### **Paso 3: Configurar la Plantilla**
1. **Nombre**: Debe ser único, sin espacios, solo minúsculas
   - ✅ Correcto: `recordatorio_cita`
   - ❌ Incorrecto: `Recordatorio Cita`
2. **Categoría**: 
   - `MARKETING` - Promociones
   - `UTILITY` - Transaccionales
   - `AUTHENTICATION` - Códigos de verificación
3. **Idioma**: Selecciona el idioma (ej: `es`, `es_MX`)
4. **Contenido**: Escribe el mensaje
   - Usa `{{1}}`, `{{2}}`, etc. para variables
   - Ejemplo: `Hola {{1}}, tu cita es el {{2}}`

#### **Paso 4: Enviar para Aprobación**
1. Revisa la plantilla
2. Haz clic en **"Enviar"**
3. Meta revisará la plantilla (puede tardar horas o días)
4. Una vez aprobada, estará disponible para usar

### 5.5 Usar Plantillas en el Código

#### **Método 1: Envío Directo**

```php
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

// Enviar plantilla simple
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',        // Número de teléfono
    'recordatorio',        // Nombre de la plantilla (debe estar aprobada)
    ['Juan', '2024-01-15'] // Parámetros (opcional)
);

if ($result['success']) {
    echo "Mensaje enviado. ID: " . $result['message_id'];
} else {
    echo "Error: " . $result['message'];
}
```

#### **Método 2: Desde el Panel de Marketing**

1. Accede a: `admin_whatsapp_marketing.php`
2. Ve a la sección **"Plantillas"**
3. Selecciona una plantilla aprobada
4. Configura los parámetros
5. Envía la campaña

#### **Método 3: En Campañas Automatizadas**

```php
require_once 'classes/WhatsAppMarketing.php';

$marketing = new WhatsAppMarketing();

// Crear campaña con plantilla
$campaignData = [
    'name' => 'Recordatorios de Cita',
    'type' => 'cita',
    'template_name' => 'recordatorio',
    'status' => 'scheduled',
    'scheduled_at' => '2024-01-15 10:00:00'
];

$campaignId = $marketing->createCampaign($campaignData);
```

### 5.6 Parámetros de Plantillas

Las plantillas pueden tener **variables** que se reemplazan con valores reales:

**Ejemplo de plantilla:**
```
Hola {{1}}, tu cita es el {{2}} a las {{3}}.
```

**Uso en código:**
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

### 5.7 Idiomas de Plantillas

Las plantillas pueden tener múltiples traducciones. El sistema intenta automáticamente diferentes códigos de idioma:

```php
// El sistema intenta en este orden:
$languageCodes = [
    'es',      // Español genérico
    'es_MX',   // Español México
    'es_ES',   // Español España
    'en_US',   // Inglés Estados Unidos
    'en'       // Inglés genérico
];
```

**Configurar idioma específico:**
```php
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',
    'recordatorio',
    ['Juan', '2024-01-15'],
    'es_MX'  // Idioma específico
);
```

### 5.8 Errores Comunes con Plantillas

| Código | Error | Solución |
|--------|-------|----------|
| 132000 | Plantilla no existe | Verificar nombre exacto en Meta Business Manager |
| 132001 | Plantilla no encontrada en idioma | Verificar que la plantilla tenga traducción en ese idioma |
| 132005 | Parámetros inválidos | Verificar que el número de parámetros coincida con la plantilla |
| 131026 | Fuera de ventana de 24h | Usar plantilla en lugar de mensaje libre |

### 5.9 Gestión de Plantillas en el Sistema

#### **Listar Plantillas Aprobadas**

```php
// Obtener plantillas desde Meta API (requiere implementación adicional)
// Por ahora, las plantillas se gestionan manualmente en Meta Business Manager
```

#### **Plantillas Personalizadas en Base de Datos**

```php
$marketing = new WhatsAppMarketing();

// Obtener todas las plantillas personalizadas
$templates = $marketing->getAllCustomTemplates();

// Obtener por categoría
$citasTemplates = $marketing->getAllCustomTemplates('cita');

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

---

## 6. Envío de Mensajes

### 6.1 Mensajes de Texto Libres

**Uso**: Solo dentro de la ventana de 24 horas

```php
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

try {
    $result = $whatsappAPI->sendMessage(
        '521234567890',                    // Número de teléfono
        'Hola, este es un mensaje de prueba' // Mensaje
    );
    
    if ($result['success']) {
        echo "Mensaje enviado. ID: " . $result['message_id'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Limitaciones:**
- ⏱️ Solo funciona dentro de 24 horas después del último mensaje del usuario
- 📝 Máximo 4096 caracteres
- ❌ No permite mensajes promocionales

### 6.2 Mensajes con Plantillas

**Uso**: Fuera de la ventana de 24 horas o mensajes promocionales

```php
$result = $whatsappAPI->sendTemplateMessage(
    '521234567890',           // Número
    'recordatorio',            // Nombre de plantilla
    ['Juan', '2024-01-15']    // Parámetros
);
```

### 6.3 Normalización de Números

El sistema normaliza automáticamente los números telefónicos:

```php
// Entrada: "+52 123 456 7890"
// Entrada: "52 123 456 7890"
// Entrada: "1234567890" (México)
// Salida: "521234567890" (formato WhatsApp)
```

**Reglas de normalización:**
1. Elimina caracteres no numéricos (excepto `+`)
2. Elimina el `+` inicial
3. Si es número mexicano de 10 dígitos, agrega `52` (código de país)
4. Retorna número en formato internacional sin espacios

### 6.4 Endpoint de Envío

**Ubicación**: `send_whatsapp.php`

**Uso desde el panel de administración:**

```javascript
// Ejemplo de uso desde JavaScript
fetch('send_whatsapp.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        'lead_id': 123,
        'message': 'Hola, este es un mensaje',
        'use_template': '0',
        'template_name': '',
        'template_params': '[]'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Mensaje enviado:', data.message_id);
    } else {
        console.error('Error:', data.message);
    }
});
```

**Parámetros:**
- `lead_id` (requerido): ID del lead en la base de datos
- `message` (opcional): Texto del mensaje (si no usa plantilla)
- `use_template` (opcional): `'1'` para usar plantilla, `'0'` para mensaje libre
- `template_name` (opcional): Nombre de la plantilla
- `template_params` (opcional): JSON array con parámetros

### 6.5 Respuesta del API

**Éxito:**
```json
{
  "success": true,
  "message": "Mensaje enviado exitosamente. ID: wamid.xxx",
  "message_id": "wamid.xxx",
  "details": "El mensaje fue aceptado por la API de WhatsApp."
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error al enviar mensaje: [descripción del error]",
  "errors": []
}
```

---

## 7. Webhooks

### 7.1 ¿Qué es un Webhook?

Un **webhook** es un endpoint HTTP que recibe eventos en tiempo real de Meta cuando ocurren acciones en WhatsApp (mensajes recibidos, estados de mensajes, etc.).

### 7.2 Configuración del Webhook

#### **En Meta Business Manager:**

1. Ve a: **WhatsApp** → **Configuración** → **Webhooks**
2. Haz clic en **"Configurar webhooks"**
3. Ingresa:
   - **URL de callback**: `https://tudominio.com/webhook_whatsapp.php`
   - **Token de verificación**: El valor de `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
4. Selecciona los eventos a suscribir:
   - ✅ `messages` - Mensajes entrantes y estados
   - ✅ `message_status` - Cambios de estado
   - ✅ `account_alerts` - Alertas de cuenta
5. Haz clic en **"Verificar y guardar"**

#### **Verificación del Webhook (GET)**

Meta envía un request GET para verificar el endpoint:

```php
// En webhook_whatsapp.php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($mode === 'subscribe' && $token === WHATSAPP_WEBHOOK_VERIFY_TOKEN) {
        // Meta espera SOLO el challenge como respuesta (text/plain)
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }
}
```

### 7.3 Eventos del Webhook

#### **Mensajes Entrantes (POST)**

```php
// Estructura del webhook
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "field": "messages",
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "1234567890",
          "phone_number_id": "619215614617031"
        },
        "contacts": [{
          "profile": {
            "name": "Juan Pérez"
          },
          "wa_id": "521234567890"
        }],
        "messages": [{
          "from": "521234567890",
          "id": "wamid.xxx",
          "timestamp": "1234567890",
          "type": "text",
          "text": {
            "body": "Hola, necesito información"
          }
        }]
      }
    }]
  }]
}
```

#### **Estados de Mensajes**

```php
{
  "statuses": [{
    "id": "wamid.xxx",
    "status": "delivered",  // sent, delivered, read, failed
    "timestamp": "1234567890",
    "recipient_id": "521234567890"
  }]
}
```

### 7.4 Procesamiento de Eventos

**Ubicación**: `webhook_whatsapp.php`

**Funciones principales:**

1. **`processIncomingMessage()`** - Procesa mensajes entrantes
2. **`processMessageStatus()`** - Actualiza estados de mensajes
3. **`processAccountAlerts()`** - Maneja alertas de cuenta

**Ejemplo de procesamiento:**

```php
function processIncomingMessage($value) {
    $messages = $value['messages'] ?? [];
    
    foreach ($messages as $message) {
        $from = $message['from'];
        $text = $message['text']['body'] ?? '';
        
        // Buscar lead en base de datos
        $db = Database::getInstance();
        $lead = $db->findLeadByWhatsApp($from);
        
        if ($lead) {
            // Actualizar lead
            $db->updateLeadStatus($lead['id'], 'contactado', $text);
        }
    }
}
```

### 7.5 Seguridad del Webhook

**Verificación de firma (recomendado para producción):**

```php
// Meta envía un header X-Hub-Signature-256
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!empty($signature) && !empty(WHATSAPP_CERTIFICATE)) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $input, WHATSAPP_CERTIFICATE);
    
    if (!hash_equals($signature, $expectedSignature)) {
        http_response_code(403);
        exit;
    }
}
```

---

## 8. Módulo de Marketing

### 8.1 Características

El módulo de marketing permite:
- 📊 Campañas masivas
- 📈 Análisis y métricas
- 💰 Gestión de créditos
- 🎯 Segmentación de leads
- 📅 Programación de envíos
- 📝 Gestión de plantillas

### 8.2 Campañas

#### **Crear Campaña**

```php
$marketing = new WhatsAppMarketing();

$campaignData = [
    'name' => 'Promoción Enero 2024',
    'type' => 'promocion',
    'template_name' => 'promo_enero',
    'status' => 'draft',
    'scheduled_at' => '2024-01-15 10:00:00'
];

$campaignId = $marketing->createCampaign($campaignData);
```

#### **Tipos de Campañas**

- `cita` - Recordatorios de citas
- `cancelacion` - Cancelaciones
- `promocion` - Promociones
- `seguimiento` - Seguimiento post-venta
- `personalizado` - Campañas personalizadas

#### **Enviar Campaña**

```php
$result = $marketing->sendCampaign($campaignId);

if ($result['success']) {
    echo "Campaña enviada. Mensajes: " . $result['total_sent'];
}
```

### 8.3 Créditos

WhatsApp Business API funciona con un sistema de créditos. El módulo rastrea:

- Créditos disponibles
- Créditos usados
- Costos por mensaje
- Métricas mensuales

**Obtener información de créditos:**

```php
$credits = $marketing->getCreditsInfo();

echo "Disponibles: " . $credits['available'];
echo "Usados hoy: " . $credits['used_today'];
echo "Usados este mes: " . $credits['used_month'];
```

### 8.4 Métricas y Análisis

```php
$metrics = $marketing->getDashboardMetrics();

// Mensajes enviados
echo "Hoy: " . $metrics['sent_today'];
echo "Esta semana: " . $metrics['sent_week'];
echo "Este mes: " . $metrics['sent_month'];

// Tasas
echo "Tasa de entrega: " . $metrics['delivery_rate'] . "%";
echo "Tasa de lectura: " . $metrics['read_rate'] . "%";
```

---

## 9. Manejo de Errores

### 9.1 Códigos de Error Comunes

| Código | Descripción | Solución |
|--------|------------|----------|
| 190 | Token expirado | Renovar token |
| 131047 | Número no registrado en WhatsApp | Verificar número |
| 131026 | Fuera de ventana de 24h | Usar plantilla |
| 131031 | Número inválido | Verificar formato |
| 132000 | Plantilla no existe | Verificar nombre |
| 132001 | Plantilla no encontrada en idioma | Verificar traducción |
| 132005 | Parámetros inválidos | Verificar parámetros |

### 9.2 Logging

El sistema registra todos los eventos en los logs de PHP:

```php
// Habilitar logging
define('WHATSAPP_LOG_MESSAGES', true);

// Los logs se guardan en:
// - error_log de PHP
// - O en el archivo configurado en php.ini
```

**Ejemplo de log:**
```
SOPHEA WhatsApp API Request: {"method":"POST","url":"...","to":"521234567890","http_code":200}
SOPHEA WhatsApp API Success: Message ID = wamid.xxx
```

### 9.3 Manejo de Excepciones

```php
try {
    $result = $whatsappAPI->sendMessage($phone, $message);
} catch (Exception $e) {
    // El sistema proporciona mensajes de error descriptivos
    error_log("Error: " . $e->getMessage());
    
    // Mensajes específicos según el tipo de error
    if (strpos($e->getMessage(), '190') !== false) {
        // Token expirado
    } elseif (strpos($e->getMessage(), '131026') !== false) {
        // Fuera de ventana de 24h
    }
}
```

---

## 10. Seguridad

### 10.1 Protección del Access Token

- ✅ **No subir a repositorios públicos**
- ✅ **Permisos restrictivos**: `chmod 600 config_whatsapp.php`
- ✅ **No exponer en URLs o logs públicos**
- ✅ **Usar variables de entorno en producción** (recomendado)

### 10.2 Autenticación del Panel

El panel `admin_whatsapp_config.php` requiere:
- Contraseña de administración
- Sesión con timeout (30 minutos)
- Regeneración de ID de sesión

### 10.3 Validación de Webhook

- Verificación de token en requests GET
- Validación de firma en requests POST (recomendado)
- Rate limiting (implementar si es necesario)

### 10.4 Sanitización de Datos

```php
// El sistema sanitiza automáticamente:
- Números telefónicos (normalización)
- Parámetros de plantillas (escapado)
- Mensajes de texto (validación de longitud)
```

---

## 11. Troubleshooting

### 11.1 El Token No Funciona

**Síntomas:**
- Error 190 o 200
- "Token inválido"

**Solución:**
1. Verificar que el token sea correcto (copiar completo)
2. Verificar que no haya espacios al inicio/final
3. Generar nuevo token en Meta Business Manager
4. Actualizar en `admin_whatsapp_config.php`

### 11.2 Los Mensajes No Se Envían

**Verificar:**
1. ✅ `WHATSAPP_API_ENABLED` está en `true`
2. ✅ Token es válido (verificar en panel)
3. ✅ Número de teléfono es correcto
4. ✅ Número está registrado en WhatsApp
5. ✅ Revisar logs de PHP para errores específicos

### 11.3 Error "Fuera de Ventana de 24 Horas"

**Solución:**
- Usar plantilla aprobada en lugar de mensaje libre
- El sistema intenta automáticamente usar plantilla si falla el mensaje libre

### 11.4 Webhook No Recibe Eventos

**Verificar:**
1. ✅ URL del webhook es accesible públicamente
2. ✅ URL usa HTTPS (en producción)
3. ✅ Token de verificación coincide
4. ✅ Webhook está suscrito a eventos en Meta Business Manager
5. ✅ Revisar logs del servidor

### 11.5 Plantilla No Se Encuentra

**Verificar:**
1. ✅ Nombre exacto (case-sensitive, sin espacios)
2. ✅ Plantilla está aprobada en Meta
3. ✅ Plantilla tiene traducción en el idioma especificado
4. ✅ Número de parámetros es correcto

---

## 12. Recursos Adicionales

### 12.1 Documentación Oficial

- **Meta WhatsApp Business API**: https://developers.facebook.com/docs/whatsapp
- **Graph API Reference**: https://developers.facebook.com/docs/graph-api
- **Webhooks Guide**: https://developers.facebook.com/docs/graph-api/webhooks

### 12.2 Archivos de Referencia en el Sistema

- `WHATSAPP_SETUP.md` - Guía de configuración inicial
- `RENOVAR_TOKEN_WHATSAPP.md` - Guía para renovar tokens
- `WHATSAPP_WEBHOOK_SETUP.md` - Configuración de webhooks

### 12.3 Soporte

Para problemas o preguntas:
1. Revisar esta documentación
2. Revisar logs de PHP
3. Verificar configuración en Meta Business Manager
4. Consultar documentación oficial de Meta

---

## 13. Ejemplos Completos

### 13.1 Envío Simple de Mensaje

```php
<?php
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

try {
    $result = $whatsappAPI->sendMessage(
        '521234567890',
        'Hola, este es un mensaje de prueba desde SOPHEA.'
    );
    
    if ($result['success']) {
        echo "✅ Mensaje enviado exitosamente\n";
        echo "ID: " . $result['message_id'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

### 13.2 Envío con Plantilla

```php
<?php
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();

try {
    $result = $whatsappAPI->sendTemplateMessage(
        '521234567890',
        'recordatorio',
        [
            'Juan Pérez',
            '2024-01-15',
            '10:00 AM'
        ],
        'es_MX'
    );
    
    if ($result['success']) {
        echo "✅ Plantilla enviada exitosamente\n";
        echo "ID: " . $result['message_id'] . "\n";
        echo "Idioma usado: " . $result['language_used'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

### 13.3 Verificar Token

```php
<?php
require_once 'config_whatsapp.php';
require_once 'classes/WhatsAppAPI.php';

$whatsappAPI = new WhatsAppAPI();
$tokenStatus = $whatsappAPI->checkTokenValidity();

if ($tokenStatus['valid']) {
    echo "✅ Token válido\n";
    if (isset($tokenStatus['days_until_expiry'])) {
        echo "Expira en: " . $tokenStatus['days_until_expiry'] . " días\n";
    }
} else {
    echo "❌ Token inválido: " . ($tokenStatus['error'] ?? 'Desconocido') . "\n";
}
```

---

## 📝 Notas Finales

- Esta documentación cubre todos los aspectos principales del módulo de WhatsApp
- Para actualizaciones, consulta los archivos de código fuente
- Mantén siempre actualizada la versión de la API (`WHATSAPP_API_VERSION`)
- Revisa periódicamente la validez del token de acceso
- Monitorea los logs para detectar problemas tempranamente

---

**Última actualización**: 2024
**Versión del módulo**: 1.0
**Versión de la API**: v18.0

