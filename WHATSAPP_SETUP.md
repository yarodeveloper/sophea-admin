# 📱 Configuración de WhatsApp Business API

## ✅ Archivos Creados

1. **`config_whatsapp.php`** - Configuración de la API
2. **`classes/WhatsAppAPI.php`** - Clase para enviar mensajes
3. **`send_whatsapp.php`** - Endpoint para procesar envíos desde el admin
4. **`admin.php`** - Actualizado con botones de WhatsApp

## 🔑 Configuración Requerida

### 1. Obtener Access Token de Meta

El Access Token es necesario para autenticarse con la API de WhatsApp Business. Sigue estos pasos:

1. **Accede a Meta Business Manager:**
   - Ve a: https://business.facebook.com
   - Inicia sesión con tu cuenta de Facebook Business

2. **Navega a WhatsApp Business API:**
   - Ve a "Configuración" → "Recursos empresariales"
   - Selecciona tu cuenta de WhatsApp Business
   - Ve a "WhatsApp" → "API Setup"

3. **Genera un Access Token:**
   - En la sección "Temporary access token" o "System user access token"
   - Genera un token con permisos:
     - `whatsapp_business_messaging`
     - `whatsapp_business_management`
   - Copia el token generado

4. **Configura el token en `config_whatsapp.php`:**
   ```php
   define('WHATSAPP_ACCESS_TOKEN', 'TU_TOKEN_AQUI');
   ```

### 2. Verificar Configuración Actual

Los siguientes valores ya están configurados:

- ✅ **Phone Number ID:** `619215614617031`
- ✅ **Business Account ID:** `130339163500704`
- ✅ **API Version:** `v18.0`
- ⚠️ **Access Token:** Necesita ser configurado

## 🚀 Cómo Usar

### Desde el Panel de Administración

1. **Accede al admin:** `http://localhost/sopheaadmin/admin.php`

2. **En la tabla de leads:**
   - Cada lead tiene un botón verde "Enviar WhatsApp"
   - Haz clic en el botón

3. **En el modal de detalles:**
   - También hay un botón "Enviar Mensaje por WhatsApp" al final

4. **Escribe el mensaje:**
   - Se abre un modal con el nombre y WhatsApp del lead
   - Escribe tu mensaje (máximo 4096 caracteres)
   - Haz clic en "Enviar Mensaje"

5. **Resultado:**
   - Si es exitoso, el lead se marca automáticamente como "contactado"
   - El modal se cierra y la página se recarga

## 📋 Características

- ✅ Envío de mensajes de texto
- ✅ Normalización automática de números telefónicos
- ✅ Validación de formato de teléfono
- ✅ Actualización automática de estado del lead
- ✅ Contador de caracteres en tiempo real
- ✅ Manejo de errores con mensajes claros
- ✅ Logging de todos los envíos

## 🔧 Estructura de la API

### Endpoint de Envío

**URL:** `POST /send_whatsapp.php`

**Parámetros:**
- `lead_id` (int) - ID del lead
- `message` (string) - Mensaje a enviar (máx 4096 caracteres)

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Mensaje enviado exitosamente",
    "message_id": "wamid.xxx"
}
```

**Respuesta de error:**
```json
{
    "success": false,
    "message": "Error al enviar mensaje: [descripción]"
}
```

## 🐛 Solución de Problemas

### Error: "Access Token not configured"

**Solución:** Configura `WHATSAPP_ACCESS_TOKEN` en `config_whatsapp.php`

### Error: "Invalid OAuth access token"

**Solución:** 
- Verifica que el token sea válido
- Los tokens temporales expiran, genera uno nuevo
- Asegúrate de tener los permisos correctos

### Error: "Invalid phone number"

**Solución:**
- Verifica que el número tenga el formato correcto
- Debe incluir código de país (ej: 52 para México)
- El número se normaliza automáticamente

### Error: "Message too long"

**Solución:**
- WhatsApp tiene un límite de 4096 caracteres
- El contador en el modal te muestra cuántos caracteres usas

## 📝 Notas Importantes

1. **Tokens Temporales:**
   - Los tokens temporales expiran después de 24 horas
   - Para producción, configura un System User Token permanente

2. **Límites de la API:**
   - WhatsApp Business API tiene límites de rate
   - Consulta la documentación de Meta para límites específicos

3. **Números de Teléfono:**
   - Los números deben estar verificados en Meta Business
   - El formato debe ser: código de país + número (sin + ni espacios)

4. **Templates:**
   - Para mensajes promocionales, necesitas templates aprobados
   - Los mensajes de texto simples funcionan para respuestas dentro de 24h

## 🔐 Seguridad

- ✅ El endpoint `send_whatsapp.php` requiere autenticación de admin
- ✅ Validación de datos de entrada
- ✅ Sanitización de mensajes
- ✅ Logging de todas las operaciones

## 📚 Recursos

- [Meta WhatsApp Business API Docs](https://developers.facebook.com/docs/whatsapp)
- [API Reference](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Getting Started Guide](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started)

---

**⚠️ IMPORTANTE:** Antes de usar en producción, asegúrate de:
1. Configurar el Access Token permanente
2. Probar con números de prueba
3. Configurar webhooks para recibir confirmaciones
4. Implementar manejo de errores robusto

