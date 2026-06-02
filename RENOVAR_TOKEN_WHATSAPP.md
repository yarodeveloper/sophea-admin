# 🔑 Guía Rápida: Renovar Token de Acceso de WhatsApp

## ⚠️ Problema Detectado

Tu token de acceso de WhatsApp ha **expirado**. Necesitas generar uno nuevo.

**Error:** `Error validating access token: Session has expired (Code: 190)`

## 📋 Pasos para Generar un Nuevo Token

### Opción 1: Desde Meta Business Manager (Recomendado)

1. **Accede a Meta Business Manager**
   - Ve a: https://business.facebook.com
   - Inicia sesión con tu cuenta

2. **Navega a tu App de WhatsApp**
   - Ve a: **Configuración** → **Recursos empresariales**
   - Selecciona tu **App de WhatsApp Business**

3. **Genera el Token**
   - Ve a: **Herramientas** → **Token de acceso**
   - O directamente: https://developers.facebook.com/tools/explorer/
   - Selecciona tu App en el menú desplegable
   - Selecciona los permisos necesarios:
     - `whatsapp_business_messaging`
     - `whatsapp_business_management`
   - Haz clic en **"Generar Token de Acceso"**

4. **Copia el Token**
   - El token aparecerá en el campo
   - **⚠️ IMPORTANTE:** Cópialo inmediatamente, no podrás verlo de nuevo

5. **Actualiza en tu Sistema**
   - Ve a: `https://ia.sopheamkt.com/admin_whatsapp_config.php`
   - Pega el nuevo token en el campo **"Access Token"**
   - Haz clic en **"Guardar Configuración"**

### Opción 2: Desde Graph API Explorer

1. Ve a: https://developers.facebook.com/tools/explorer/
2. Selecciona tu App en el menú desplegable
3. Selecciona los permisos necesarios
4. Haz clic en **"Generar Token de Acceso"**
5. Copia el token y actualízalo en la configuración

## 🔄 Tipos de Tokens

### Token de Corta Duración
- Duración: 1-2 horas
- Se genera automáticamente
- No recomendado para producción

### Token de Larga Duración
- Duración: 60 días
- Se genera desde el token de corta duración
- Recomendado para producción

### Token Permanente (System User Token)
- No expira
- Requiere configuración adicional
- Ideal para producción

## 📝 Actualizar el Token en el Sistema

1. Accede a: `https://ia.sopheamkt.com/admin_whatsapp_config.php`
2. Ingresa tu contraseña de admin
3. En el campo **"Access Token"**, pega el nuevo token
4. Haz clic en **"Guardar Configuración"**
5. Prueba enviando un mensaje desde `test_send_whatsapp.php`

## ✅ Verificar que Funciona

Después de actualizar el token:

1. Ve a: `https://ia.sopheamkt.com/test_send_whatsapp.php`
2. Ingresa un número de teléfono de prueba
3. Envía un mensaje de prueba
4. Si aparece un **Message ID**, el token está funcionando correctamente

## 🔒 Seguridad

- **NUNCA** compartas tu token de acceso
- **NUNCA** lo subas a repositorios públicos
- Los tokens deben mantenerse en archivos de configuración seguros
- Considera usar variables de entorno en producción

## 📞 Soporte

Si tienes problemas:

1. Verifica que el token tenga los permisos correctos
2. Asegúrate de que la App esté en modo "Producción" si es necesario
3. Revisa los logs del servidor para más detalles
4. Consulta la documentación de Meta: https://developers.facebook.com/docs/whatsapp

## ⏰ Recordatorio

Los tokens de larga duración expiran después de **60 días**. Considera:

- Configurar un recordatorio para renovar el token
- Usar un token permanente (System User Token) para producción
- Implementar notificaciones automáticas cuando el token esté por expirar

