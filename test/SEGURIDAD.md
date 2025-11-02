# 🔒 Guía de Seguridad - Validación de Firma de WhatsApp

## ¿Por qué necesitas esto?

Sin validación de firma, **cualquier persona** puede enviar peticiones a tu webhook y tu servidor las procesaría como si vinieran de WhatsApp. Esto podría:

- ❌ Llenar tu cola de RabbitMQ con mensajes falsos
- ❌ Causar un ataque DDoS
- ❌ Corromper tu base de datos con información falsa
- ❌ Generar costos innecesarios en tu infraestructura

## ✅ Solución: Validación de Firma HMAC SHA256

Meta/WhatsApp firma cada petición POST con un secret compartido. Tu servidor valida esta firma antes de procesar el mensaje.

## 📋 Cómo obtener tu App Secret de WhatsApp

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Selecciona tu aplicación de WhatsApp Business
3. En el menú lateral, ve a **"App Settings"** → **"Basic"**
4. Encuentra el campo **"App Secret"**
5. Haz clic en **"Show"** y copia el valor

## ⚙️ Configuración

### 1. Crea un archivo `.env` (si no existe)

```bash
cp .env.example .env
```

### 2. Agrega tu App Secret al archivo `.env`

```bash
WHATSAPP_APP_SECRET=tu_app_secret_real_de_meta
```

⚠️ **IMPORTANTE**: Nunca subas tu `.env` a Git. Asegúrate de que esté en `.gitignore`.

### 3. Reinicia tu servidor

```bash
npm start
```

## 🔍 ¿Cómo funciona?

### Flujo de validación:

1. **WhatsApp envía una petición POST** con el header `X-Hub-Signature-256`
2. **Middleware `captureRawBody`** guarda el body sin parsear
3. **Express.json()** parsea el body para tu uso
4. **Middleware `validateWhatsAppSignature`** ejecuta:
   - Lee el header `X-Hub-Signature-256` (firma de WhatsApp)
   - Calcula el hash del raw body usando tu App Secret
   - Compara ambas firmas de forma segura (evitando timing attacks)
   - Si coinciden ✅ → continúa al controlador
   - Si no coinciden ❌ → rechaza con 401 Unauthorized

### Ejemplo de firma:

```
X-Hub-Signature-256: sha256=a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e
```

## 🧪 Probar la validación

### ✅ Petición válida (desde WhatsApp)

WhatsApp incluye automáticamente el header con la firma correcta:

```bash
# Las peticiones reales de WhatsApp funcionarán automáticamente
```

### ❌ Petición inválida (sin firma)

```bash
curl -X POST http://localhost:3000/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'

# Respuesta: 401 Unauthorized
# {
#   "error": "Unauthorized",
#   "message": "Missing signature header"
# }
```

### ❌ Petición inválida (firma incorrecta)

```bash
curl -X POST http://localhost:3000/webhook \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=firma_falsa" \
  -d '{"test": "data"}'

# Respuesta: 401 Unauthorized
# {
#   "error": "Unauthorized",
#   "message": "Invalid signature"
# }
```

## 🛡️ Capas de Seguridad Implementadas

1. **Validación de firma HMAC SHA256** ✅
   - Solo procesa peticiones de WhatsApp
   
2. **Timing-safe comparison** ✅
   - Previene timing attacks
   
3. **Raw body preservation** ✅
   - Mantiene integridad para validación
   
4. **Error handling** ✅
   - No expone información sensible en errores
   
5. **Logging de intentos fallidos** ✅
   - Registra IP y detalles de intentos no autorizados

## 📊 Logs de Seguridad

### Petición válida:
```json
{
  "level": "info",
  "message": "✅ Firma de WhatsApp validada correctamente",
  "timestamp": "2025-11-02T10:30:00.000Z"
}
```

### Petición rechazada:
```json
{
  "level": "warn",
  "message": "⚠️ Petición rechazada: firma inválida",
  "received": "sha256=...",
  "ip": "192.168.1.100",
  "timestamp": "2025-11-02T10:30:00.000Z"
}
```

## 🚨 Troubleshooting

### Error: "Missing signature header"
- **Causa**: La petición no incluye el header `X-Hub-Signature-256`
- **Solución**: Verifica que estás recibiendo peticiones desde WhatsApp

### Error: "Invalid signature"
- **Causa**: El App Secret configurado no coincide con el de tu app de Meta
- **Solución**: Verifica que `WHATSAPP_APP_SECRET` en `.env` sea correcto

### Error: "Configuration error"
- **Causa**: `WHATSAPP_APP_SECRET` no está configurado
- **Solución**: Agrega la variable de entorno con tu App Secret

### Error: "Unable to validate signature"
- **Causa**: El raw body no está disponible
- **Solución**: Verifica que `captureRawBody` esté antes de `express.json()`

## 🔐 Mejores Prácticas

1. ✅ **Nunca hardcodees** el App Secret en el código
2. ✅ **Usa variables de entorno** para configuración sensible
3. ✅ **Rota tu App Secret** periódicamente en Meta
4. ✅ **Monitorea logs** de intentos fallidos
5. ✅ **Implementa rate limiting** adicional si es necesario
6. ✅ **Usa HTTPS** en producción
7. ✅ **Mantén el .env** fuera de Git (en `.gitignore`)

## 📚 Referencias

- [WhatsApp Business API - Signature Validation](https://developers.facebook.com/docs/graph-api/webhooks/getting-started#verification-requests)
- [HMAC SHA256 - Wikipedia](https://en.wikipedia.org/wiki/HMAC)
- [Timing Attacks - OWASP](https://owasp.org/www-community/attacks/Timing_attack)

## ✨ Próximos pasos de seguridad (opcional)

- Implementar **rate limiting** (por ejemplo con `express-rate-limit`)
- Agregar **IP whitelisting** de rangos de Meta/WhatsApp
- Implementar **request logging** detallado
- Configurar **alertas** para intentos de acceso no autorizado
- Usar **secrets management** como AWS Secrets Manager o HashiCorp Vault
