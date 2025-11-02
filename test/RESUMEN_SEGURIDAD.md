# 🔒 Resumen de Seguridad Implementada

## ✅ ¿Qué se implementó?

### Validación de Firma HMAC SHA256
Tu API ahora **valida que cada petición POST realmente viene de WhatsApp** usando criptografía.

## 📁 Archivos Nuevos Creados

```
src/
├── middlewares/                              ← NUEVO
│   ├── raw-body.middleware.js               ← Captura body sin parsear
│   └── whatsapp-signature.middleware.js     ← Valida firma de WhatsApp
│
├── config/
│   └── index.js                             ← ACTUALIZADO (agregado whatsappAppSecret)
│
├── routes/
│   └── webhook.routes.js                    ← ACTUALIZADO (agregado middleware)
│
└── app.js                                   ← ACTUALIZADO (agregado captureRawBody)

.env.example                                 ← ACTUALIZADO
SEGURIDAD.md                                 ← NUEVO (documentación completa)
ARQUITECTURA.md                              ← ACTUALIZADO
test-signature.js                            ← NUEVO (script de prueba)
```

## 🚀 Cómo Usar

### 1. Obtén tu App Secret de Meta

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Selecciona tu app de WhatsApp Business
3. Ve a **App Settings** → **Basic**
4. Copia el **App Secret**

### 2. Configura tu .env

```bash
# Crea el archivo .env si no existe
cp .env.example .env

# Edita y agrega tu App Secret
nano .env
```

Agrega esta línea:
```bash
WHATSAPP_APP_SECRET=tu_app_secret_real_de_meta
```

### 3. Reinicia tu servidor

```bash
npm start
```

## 🧪 Probar la Seguridad

### Opción 1: Usar el script de prueba
```bash
npm run test:signature
```
Este comando te generará los comandos curl con firma válida e inválida.

### Opción 2: Probar manualmente

#### ❌ Sin firma (será rechazado)
```bash
curl -X POST http://localhost:3000/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```
**Respuesta esperada:** `401 Unauthorized`

#### ✅ Con firma válida (desde WhatsApp)
Las peticiones reales de WhatsApp funcionarán automáticamente.

## 📊 Logs de Seguridad

### Petición rechazada (sin firma):
```
⚠️ Petición rechazada: falta el header X-Hub-Signature-256
```

### Petición rechazada (firma inválida):
```
⚠️ Petición rechazada: firma inválida
```

### Petición aceptada:
```
✅ Firma de WhatsApp validada correctamente
```

## 🛡️ Capas de Seguridad

1. ✅ **Validación de firma HMAC SHA256** - Solo procesa peticiones de WhatsApp
2. ✅ **Timing-safe comparison** - Previene timing attacks
3. ✅ **Error handling seguro** - No expone información sensible
4. ✅ **Logging de intentos fallidos** - Registra ataques

## ⚠️ IMPORTANTE

### En Desarrollo
- Puedes omitir `WHATSAPP_APP_SECRET` para desarrollo local
- Las peticiones sin firma funcionarán

### En Producción
- **DEBES** configurar `WHATSAPP_APP_SECRET`
- Sin él, cualquiera puede enviar peticiones a tu webhook
- Mantenlo secreto y nunca lo subas a Git

## 📚 Más Información

Lee la documentación completa en:
- **[SEGURIDAD.md](./SEGURIDAD.md)** - Guía detallada de seguridad
- **[ARQUITECTURA.md](./ARQUITECTURA.md)** - Arquitectura actualizada

## 🎯 ¿Qué protege esto?

✅ Evita que personas no autorizadas envíen mensajes falsos  
✅ Previene ataques DDoS a tu webhook  
✅ Protege tu cola de RabbitMQ de mensajes basura  
✅ Garantiza que solo WhatsApp puede comunicarse con tu API  

## 🚨 Troubleshooting Rápido

| Problema | Solución |
|----------|----------|
| "Missing signature header" | Verifica que estés recibiendo desde WhatsApp |
| "Invalid signature" | Verifica que `WHATSAPP_APP_SECRET` sea correcto |
| "Configuration error" | Agrega `WHATSAPP_APP_SECRET` a tu `.env` |

---

**¿Listo para producción?** 🚀  
Configura tu `WHATSAPP_APP_SECRET` y despliega con confianza.
