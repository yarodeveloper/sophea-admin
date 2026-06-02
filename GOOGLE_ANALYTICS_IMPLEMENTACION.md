# 📊 Google Analytics - Implementación Completa

## ✅ Estado: IMPLEMENTADO

**ID de Google Analytics**: `G-0M43GSQVX8`  
**Versión**: GA4 (Google Analytics 4)  
**Fecha de implementación**: Actualización de metadatos

---

## 🎯 Características Implementadas

### 1. ✅ Código GA4 Base
- Script de Google Tag Manager (gtag.js) cargado asíncronamente
- Configuración básica con ID: `G-0M43GSQVX8`
- Anonimización de IP habilitada (`anonymize_ip: true`)
- Cookies con flags de seguridad (`SameSite=None;Secure`)

### 2. ✅ Consent Mode (Modo de Consentimiento)
- **Consentimiento por defecto**: Denegado hasta que el usuario acepte
- **Respeto a cookies**: Integrado con el sistema de cookies existente
- **Actualización dinámica**: El consentimiento se actualiza cuando el usuario acepta/rechaza cookies

### 3. ✅ Integración con Banner de Cookies
- **Aceptar todas**: Activa analytics y marketing storage
- **Rechazar todas**: Deniega analytics y marketing storage
- **Configuración personalizada**: Respeta las preferencias individuales del usuario
- **Persistencia**: Las preferencias se guardan en cookies por 365 días

---

## 📁 Archivos Modificados

### 1. `config.php`
```php
// Google Analytics
define('GOOGLE_ANALYTICS_ID', 'G-0M43GSQVX8');
define('GOOGLE_ANALYTICS_ENABLED', true);
```

### 2. `header.php`
- Código GA4 agregado antes del cierre de `</head>`
- Consent mode configurado por defecto como "denied"
- Función `updateAnalyticsConsent()` para actualizar consentimiento

### 3. `components/cookie_banner.php`
- Integración completa con Google Analytics
- Actualización de consentimiento en:
  - Aceptar todas las cookies
  - Rechazar todas las cookies
  - Guardar configuración personalizada
  - Carga inicial (si ya hay consentimiento previo)

---

## 🔧 Configuración Técnica

### Consent Mode Default
```javascript
gtag('consent', 'default', {
    'analytics_storage': 'denied',
    'ad_storage': 'denied',
    'wait_for_update': 500
});
```

### Configuración GA4
```javascript
gtag('config', 'G-0M43GSQVX8', {
    'anonymize_ip': true,
    'cookie_flags': 'SameSite=None;Secure'
});
```

### Actualización de Consentimiento
Cuando el usuario acepta cookies de análisis:
```javascript
gtag('consent', 'update', {
    'analytics_storage': 'granted'
});
```

---

## 🍪 Flujo de Consentimiento

### Escenario 1: Usuario Acepta Todas las Cookies
1. Usuario hace clic en "Aceptar" en el banner
2. Se guardan cookies: `cookie_consent=all`, `cookie_analytics=true`
3. Se actualiza consentimiento GA4: `analytics_storage: 'granted'`
4. Google Analytics comienza a rastrear

### Escenario 2: Usuario Rechaza Cookies de Análisis
1. Usuario abre configuración y desactiva "Cookies de Análisis"
2. Se guarda cookie: `cookie_analytics=false`
3. Se actualiza consentimiento GA4: `analytics_storage: 'denied'`
4. Google Analytics deja de rastrear

### Escenario 3: Usuario Ya Tiene Consentimiento Previo
1. Al cargar la página, se verifica cookie `cookie_analytics`
2. Si es `true`, se actualiza consentimiento automáticamente
3. Google Analytics se activa sin mostrar banner

---

## 📊 Eventos y Métricas

### Eventos Automáticos (GA4)
GA4 rastrea automáticamente:
- ✅ Page views (vistas de página)
- ✅ Scroll depth (profundidad de scroll)
- ✅ Time on page (tiempo en página)
- ✅ Bounce rate (tasa de rebote)
- ✅ User engagement (compromiso del usuario)

### Eventos Personalizados (Futuro)
Se pueden agregar eventos personalizados para:
- 📝 Envío de formularios de contacto
- 📞 Clicks en botón de WhatsApp
- 📧 Suscripciones al newsletter
- 🛒 Conversiones (si aplica)
- 📱 Interacciones específicas

---

## 🔒 Privacidad y Cumplimiento

### ✅ Características de Privacidad
- **Anonimización de IP**: Habilitada
- **Consentimiento explícito**: Requerido antes de rastrear
- **Cookies seguras**: `SameSite=None;Secure`
- **Respeto a Do Not Track**: (Se puede agregar si es necesario)

### ✅ Cumplimiento Legal
- ✅ **GDPR**: Consentimiento explícito requerido
- ✅ **LGPD**: Consentimiento explícito requerido
- ✅ **CCPA**: Opción de opt-out disponible
- ✅ **Política de Cookies**: Enlace disponible en banner

---

## 🧪 Verificación

### 1. Verificar que GA4 está cargando
1. Abre el sitio en el navegador
2. Abre DevTools (F12) → Network
3. Busca requests a `googletagmanager.com`
4. Deberías ver el script `gtag/js?id=G-0M43GSQVX8`

### 2. Verificar Consent Mode
1. Abre DevTools → Console
2. Escribe: `dataLayer`
3. Deberías ver el array con eventos de consentimiento

### 3. Verificar en Google Analytics
1. Accede a Google Analytics
2. Ve a Reports → Realtime
3. Deberías ver actividad en tiempo real (si hay tráfico)

### 4. Verificar Consentimiento de Cookies
1. Acepta cookies de análisis
2. En DevTools → Application → Cookies
3. Verifica que `cookie_analytics=true`
4. En Console, verifica: `gtag('consent', 'update', {...})`

---

## 🚀 Próximos Pasos Recomendados

### Opcional (Mejoras Futuras)
1. **Eventos personalizados**
   - Rastrear envío de formularios
   - Rastrear clicks en WhatsApp
   - Rastrear descargas de archivos

2. **Conversiones**
   - Configurar eventos como conversiones
   - Crear objetivos en GA4
   - Medir ROI de campañas

3. **Audiencias**
   - Crear audiencias personalizadas
   - Segmentar por comportamiento
   - Exportar a Google Ads

4. **Integraciones**
   - Conectar con Google Search Console
   - Conectar con Google Ads
   - Conectar con BigQuery (si aplica)

---

## 📝 Notas Importantes

### ⚠️ Importante
- Google Analytics **NO rastrea** hasta que el usuario acepte cookies de análisis
- El consentimiento se respeta según las preferencias del usuario
- Las cookies de consentimiento duran 365 días

### ✅ Beneficios
- ✅ Cumplimiento con GDPR/LGPD/CCPA
- ✅ Mejor experiencia de usuario (consentimiento explícito)
- ✅ Datos más precisos (solo usuarios que aceptan)
- ✅ Privacidad protegida

---

## 🔗 Enlaces Útiles

- **Google Analytics**: https://analytics.google.com/
- **GA4 Documentation**: https://developers.google.com/analytics/devguides/collection/ga4
- **Consent Mode**: https://developers.google.com/tag-platform/devguides/consent
- **Cookie Policy**: `/politica_cookies.php`

---

**✅ Google Analytics está completamente implementado y funcionando con respeto al consentimiento de cookies.**

