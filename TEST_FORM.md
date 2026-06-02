# 🧪 Guía de Prueba del Formulario y Base de Datos

## ✅ Verificación de Funcionalidad

### 1. Probar el Formulario de Contacto

1. **Abrir el sitio:**
   - Navegar a `http://localhost/sopheaadmin/index.php#contacto`
   - O usar tu dominio si está en producción

2. **Llenar el formulario:**
   - Nombre: "Dr. Juan Pérez"
   - Especialidad: "Odontología"
   - WhatsApp: "+52 961 123 4567"
   - Mensaje: "Me interesa una consultoría"

3. **Enviar el formulario:**
   - Hacer clic en "Solicitar Consultoría Gratuita"
   - Deberías ver un mensaje de éxito
   - El formulario se debe limpiar
   - Se debe abrir WhatsApp (opcional)

### 2. Verificar en Base de Datos

**Opción A: phpMyAdmin**
1. Abrir phpMyAdmin
2. Seleccionar base de datos `sophea_db`
3. Ir a la tabla `leads`
4. Verificar que el nuevo lead aparezca con:
   - Nombre correcto
   - Especialidad correcta
   - WhatsApp correcto
   - Mensaje (si se proporcionó)
   - Fecha de creación
   - Estado: "nuevo"

**Opción B: SQL**
```sql
SELECT * FROM leads ORDER BY created_at DESC LIMIT 5;
```

### 3. Verificar en Panel de Administración

1. **Acceder al admin:**
   - Navegar a `http://localhost/sopheaadmin/admin.php`
   - Ingresar contraseña: `sophea2025` (cambiar en producción)

2. **Verificar estadísticas:**
   - Total Leads debe incrementarse
   - Nuevos debe mostrar el nuevo lead
   - Este Mes debe actualizarse

3. **Verificar tabla de leads:**
   - El nuevo lead debe aparecer en la tabla
   - Debe mostrar:
     - ID del lead
     - Nombre
     - Especialidad
     - WhatsApp (clickeable)
     - Fecha
     - Estado: "Nuevo"
     - Botón "Ver Detalles"

4. **Probar "Ver Detalles":**
   - Hacer clic en "Ver Detalles" de un lead
   - Debe abrir un modal con toda la información
   - Debe permitir cambiar el estado
   - Debe permitir agregar notas

### 4. Probar Rate Limiting

1. **Enviar un formulario**
2. **Intentar enviar otro inmediatamente:**
   - Debe mostrar error: "Por favor espera X segundos..."
   - No debe guardar el segundo envío
3. **Esperar 60 segundos**
4. **Enviar de nuevo:**
   - Ahora debe funcionar correctamente

### 5. Verificar Logs de Errores

**Ubicación de logs:**
- XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log` o `/var/log/php_errors.log`

**Buscar en logs:**
```
SOPHEA Success: Lead #X saved to database for [nombre]
```

Si hay errores:
```
SOPHEA Database Error: [mensaje]
SOPHEA Failed Lead Data: [datos]
```

## 🔍 Checklist de Verificación

- [ ] El formulario se envía correctamente
- [ ] Los datos se guardan en la tabla `leads`
- [ ] El lead aparece en el panel de administración
- [ ] Las estadísticas se actualizan correctamente
- [ ] El rate limiting funciona (bloquea envíos rápidos)
- [ ] Los errores se registran en los logs
- [ ] El email se envía (si está configurado)
- [ ] El WhatsApp se abre correctamente (opcional)

## 🐛 Solución de Problemas

### El formulario no guarda en la base de datos

1. **Verificar configuración:**
   - Abrir `config_db.php`
   - Verificar que `ENABLE_DATABASE_STORAGE = true`
   - Verificar credenciales de BD

2. **Verificar conexión:**
   - Probar conexión manual a MySQL
   - Verificar que la base de datos `sophea_db` existe
   - Verificar que la tabla `leads` existe

3. **Revisar logs:**
   - Buscar errores de PDO en los logs
   - Verificar mensajes "SOPHEA Database Error"

### Los leads no aparecen en el admin

1. **Verificar conexión:**
   - El admin debe poder conectarse a la BD
   - Verificar mensaje de error en el admin

2. **Verificar consulta:**
   ```sql
   SELECT COUNT(*) FROM leads;
   ```

3. **Verificar permisos:**
   - El usuario de BD debe tener permisos SELECT

### Rate limiting no funciona

1. **Verificar sesiones:**
   - Asegurarse de que `session_start()` se llama antes
   - Verificar que las sesiones están habilitadas en PHP

2. **Verificar logs:**
   - Buscar errores relacionados con sesiones

## 📊 Estructura de Datos Esperada

**Tabla `leads`:**
```sql
id              INT (auto-increment)
nombre          VARCHAR(255)
especialidad    VARCHAR(255)
whatsapp        VARCHAR(50)
mensaje         TEXT (nullable)
ip_address      VARCHAR(45) (nullable)
user_agent      TEXT (nullable)
created_at      TIMESTAMP
status          ENUM('nuevo', 'contactado', 'calificado', 'convertido', 'descartado')
source          VARCHAR(100) DEFAULT 'website'
notes           TEXT (nullable)
```

## ✅ Confirmación de Funcionamiento

Si todos los pasos funcionan correctamente:

1. ✅ El formulario guarda datos en `leads`
2. ✅ Los datos se muestran en el admin
3. ✅ El rate limiting previene spam
4. ✅ Los errores se registran apropiadamente

**El sistema está funcionando correctamente!** 🎉

