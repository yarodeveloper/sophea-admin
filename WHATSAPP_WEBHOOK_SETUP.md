# 🔗 Configuración de Webhook para WhatsApp Business API

## 📋 Información del Webhook

### URL de Callback (Callback URL)
```
https://tudominio.com/webhook_whatsapp.php
```
**⚠️ IMPORTANTE:** Reemplaza `tudominio.com` con tu dominio real.

### Token de Verificación (Verify Token)
```
sophea_webhook_2025_secure_token_change_this
```
**⚠️ IMPORTANTE:** Cambia este token por uno seguro y único antes de configurar en Meta.

## 🚀 Pasos para Configurar en Meta Business Manager

### 1. Generar un Token de Verificación Seguro

Antes de configurar en Meta, genera un token seguro:

```php
// Genera un token aleatorio seguro
$token = bin2hex(random_bytes(32));
echo $token;
```

O usa este comando en terminal:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Configurar el Token en el Código

1. Edita `config_whatsapp.php`
2. Cambia el valor de `WHATSAPP_WEBHOOK_VERIFY_TOKEN`:
   ```php
   define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'tu_token_seguro_aqui');
   ```

### 3. Configurar en Meta Business Manager

1. **Accede a Meta Business Manager:**
   - Ve a: https://business.facebook.com
   - Inicia sesión

2. **Navega a Configuración de Webhook:**
   - Ve a "Configuración" → "Recursos empresariales"
   - Selecciona tu cuenta de WhatsApp Business
   - Ve a "WhatsApp" → "Configuración" → "Webhooks"

3. **Configura el Webhook:**
   - **Callback URL:** `https://tudominio.com/webhook_whatsapp.php`
   - **Verify Token:** El mismo token que configuraste en `config_whatsapp.php`
   - Haz clic en "Verificar y guardar"

4. **Selecciona Eventos a Suscribir:**
   - ✅ `messages` - Mensajes recibidos
   - ✅ `message_status` - Estados de mensajes (enviado, entregado, leído, fallido)
   - ✅ `message_template_status_update` - Estados de templates (opcional)

5. **Guarda la Configuración**

## ✅ Verificación del Webhook

Meta enviará una petición GET para verificar el webhook:

```
GET /webhook_whatsapp.php?hub.mode=subscribe&hub.verify_token=TU_TOKEN&hub.challenge=RANDOM_STRING
```

El webhook debe responder con el `hub.challenge` si el token coincide.

## 📨 Eventos que Recibe el Webhook

### 1. Mensajes Recibidos

Cuando un usuario envía un mensaje, Meta envía:

```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "changes": [{
      "value": {
        "messages": [{
          "from": "521234567890",
          "id": "wamid.xxx",
          "timestamp": "1234567890",
          "type": "text",
          "text": {
            "body": "Hola, quiero información"
          }
        }],
        "contacts": [{
          "profile": {
            "name": "Juan Pérez"
          },
          "wa_id": "521234567890"
        }]
      }
    }]
  }]
}
```

**Acción:** El sistema busca el lead por número de WhatsApp y actualiza su estado.

### 2. Estados de Mensajes

Cuando cambia el estado de un mensaje enviado:

```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "changes": [{
      "value": {
        "statuses": [{
          "id": "wamid.xxx",
          "status": "delivered",
          "timestamp": "1234567890",
          "recipient_id": "521234567890"
        }]
      }
    }]
  }]
}
```

**Estados posibles:**
- `sent` - Mensaje enviado
- `delivered` - Mensaje entregado
- `read` - Mensaje leído
- `failed` - Mensaje fallido

## 🔒 Seguridad

### Verificación de Firma (Recomendado para Producción)

Meta envía un header `X-Hub-Signature-256` para verificar que el webhook viene de Meta.

Para habilitar la verificación:

1. Obtén el App Secret de tu app en Meta
2. Configúralo en `config_whatsapp.php`:
   ```php
   define('WHATSAPP_APP_SECRET', 'tu_app_secret');
   ```

3. Descomenta la verificación en `webhook_whatsapp.php`:
   ```php
   $expectedSignature = 'sha256=' . hash_hmac('sha256', $input, WHATSAPP_APP_SECRET);
   if (!hash_equals($signature, $expectedSignature)) {
       http_response_code(403);
       exit;
   }
   ```

## 🧪 Probar el Webhook

### 1. Verificar que el Endpoint Funciona

Accede a:
```
https://tudominio.com/webhook_whatsapp.php?hub.mode=subscribe&hub.verify_token=TU_TOKEN&hub.challenge=test123
```

Deberías recibir: `test123`

### 2. Probar con Meta

Meta enviará automáticamente un evento de prueba después de configurar el webhook.

### 3. Verificar Logs

Revisa los logs de PHP para ver los eventos recibidos:
```
C:\xampp\apache\logs\error.log
```

Busca líneas que empiecen con "SOPHEA Webhook"

## 📊 Funcionalidades Actuales

- ✅ Verificación de webhook (GET)
- ✅ Recepción de mensajes entrantes
- ✅ Actualización automática de estado de leads
- ✅ Logging de todos los eventos
- ✅ Manejo de diferentes tipos de mensajes (texto, imagen, documento)
- ⚠️ Verificación de firma (comentada, habilitar para producción)

## 🔧 Personalización

### Agregar Más Funcionalidades

Puedes extender `webhook_whatsapp.php` para:

1. **Crear leads automáticamente** desde mensajes entrantes
2. **Responder automáticamente** con un chatbot
3. **Guardar historial de conversaciones** en base de datos
4. **Notificaciones por email** cuando llegan mensajes
5. **Estadísticas** de mensajes enviados/recibidos

### Ejemplo: Guardar Mensajes en Base de Datos

Crea una tabla `whatsapp_messages`:

```sql
CREATE TABLE whatsapp_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    message_id VARCHAR(255),
    direction ENUM('inbound', 'outbound'),
    from_number VARCHAR(50),
    to_number VARCHAR(50),
    message_text TEXT,
    message_type VARCHAR(50),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id)
);
```

## 🐛 Solución de Problemas

### Webhook no se verifica

- Verifica que la URL sea accesible públicamente
- Verifica que el token coincida exactamente
- Revisa los logs de PHP para errores

### No se reciben eventos

- Verifica que los eventos estén suscritos en Meta
- Verifica que el webhook esté activo
- Revisa los logs para ver si llegan peticiones

### Errores 403 o 500

- Verifica permisos del archivo
- Verifica que PHP tenga acceso a la base de datos
- Revisa los logs de errores

## 📝 Checklist de Configuración

- [ ] Generar token de verificación seguro
- [ ] Configurar token en `config_whatsapp.php`
- [ ] Configurar URL del webhook en Meta
- [ ] Configurar token de verificación en Meta
- [ ] Suscribir eventos necesarios
- [ ] Verificar que el webhook responde correctamente
- [ ] Probar con un mensaje real
- [ ] Habilitar verificación de firma (producción)
- [ ] Configurar HTTPS (requerido para producción)

---

**⚠️ IMPORTANTE:** 
- El webhook debe ser accesible públicamente (HTTPS en producción)
- Cambia el token de verificación por uno seguro
- Habilita la verificación de firma para producción

