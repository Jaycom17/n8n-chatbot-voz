# 🚀 WhatsApp to RabbitMQ Webhook

Servicio robusto para recibir mensajes de la API de WhatsApp Business y reenviarlos a RabbitMQ con manejo completo de errores y reintentos.

## ✨ Características

- ✅ **Reconexión automática** a RabbitMQ con reintentos cada 5 segundos
- ✅ **Reintentos con backoff exponencial** (3 intentos: 2s, 4s, 8s)
- ✅ **Cola de errores** para mensajes que fallan después de todos los reintentos
- ✅ **Logger estructurado** con Winston (archivos + consola)
- ✅ **Validación de channel** antes de enviar mensajes
- ✅ **Endpoint de verificación** para WhatsApp webhook (GET)
- ✅ **Graceful shutdown** para cerrar conexiones limpiamente
- ✅ **Variables de entorno** para configuración segura
- ✅ **Manejo de errores** en connection y channel
- ✅ **Soporte para mensajes** de texto y audio

## 🔧 Configuración

### 1. Variables de entorno

Copia el archivo `.env.example` a `.env` y configura tus valores:

```bash
cp .env.example .env
```

Variables disponibles:

| Variable | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `RABBIT_URL` | URL de conexión a RabbitMQ | `amqp://admin:admin@localhost` |
| `QUEUE_MAIN` | Nombre de la cola principal | `whatsapp_messages` |
| `QUEUE_ERROR` | Nombre de la cola de errores | `whatsapp_errors` |
| `PORT` | Puerto del servidor | `3000` |
| `WEBHOOK_VERIFY_TOKEN` | Token de verificación de WhatsApp | `mi_token_secreto_123` |

### 2. Instalar dependencias

```bash
npm install
```

### 3. Configurar WhatsApp Business API

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Configura tu aplicación de WhatsApp Business
3. En la sección de Webhooks, configura:
   - **URL del webhook**: `https://tu-dominio.com/webhook`
   - **Token de verificación**: El mismo valor que configuraste en `WEBHOOK_VERIFY_TOKEN`
   - **Campos suscritos**: `messages`

## 🚀 Uso

### Iniciar el servidor

```bash
npm start
```

O con variables de entorno personalizadas:

```bash
PORT=8080 WEBHOOK_VERIFY_TOKEN=mi_token_super_secreto npm start
```

### Endpoints

#### GET `/webhook`
Endpoint de verificación para WhatsApp. WhatsApp llama a este endpoint para verificar tu webhook.

**Query parameters:**
- `hub.mode`: debe ser "subscribe"
- `hub.verify_token`: debe coincidir con tu `WEBHOOK_VERIFY_TOKEN`
- `hub.challenge`: valor que debe ser devuelto

#### POST `/webhook`
Endpoint principal que recibe los mensajes de WhatsApp.

**Tipos de mensaje soportados:**
- `text`: Mensajes de texto
- `audio`: Mensajes de audio/voz

**Respuestas:**
- `200`: Mensaje recibido y encolado exitosamente
- `400`: Mensaje no válido
- `500`: Error interno del servidor
- `503`: RabbitMQ no disponible temporalmente

## 📊 Estructura de mensajes

### Mensaje en la cola principal (`whatsapp_messages`)

```json
{
  "phone_number_id": "123456789",
  "from": "5491112345678",
  "type": "text",
  "body": "Hola, esto es un mensaje de prueba",
  "audio_id": null
}
```

### Mensaje en la cola de errores (`whatsapp_errors`)

```json
{
  "message": {
    "phone_number_id": "123456789",
    "from": "5491112345678",
    "type": "text",
    "body": "Mensaje que falló",
    "audio_id": null
  },
  "error": "Channel perdido durante reintentos",
  "timestamp": "2025-10-24T12:34:56.789Z"
}
```

## 📝 Logs

Los logs se almacenan en:
- `error.log`: Solo errores (nivel: error)
- `combined.log`: Todos los logs (nivel: info y superior)
- Consola: Formato simple para desarrollo

Formato de log:
```json
{
  "level": "info",
  "message": "✅ Mensaje enviado a RabbitMQ",
  "timestamp": "2025-10-24T12:34:56.789Z",
  "message": { "phone_number_id": "...", "from": "..." }
}
```

## 🛡️ Manejo de errores

### Niveles de protección:

1. **Validación de channel**: Antes de enviar mensajes, verifica que el channel de RabbitMQ esté disponible
2. **Reintentos automáticos**: 3 intentos con backoff exponencial (2s → 4s → 8s)
3. **Cola de errores**: Mensajes que fallan se envían a `QUEUE_ERROR`
4. **Reconexión automática**: Si RabbitMQ se desconecta, reconecta automáticamente
5. **Graceful shutdown**: Cierra conexiones limpiamente al recibir SIGTERM/SIGINT

## 🔒 Seguridad

- ✅ No hardcodees credenciales (usa variables de entorno)
- ✅ Usa un token de verificación fuerte y aleatorio
- ✅ Implementa HTTPS en producción (usa Nginx/Caddy como reverse proxy)
- ✅ Limita el acceso a RabbitMQ con credenciales seguras
- ✅ Considera agregar autenticación adicional en el webhook

## 📦 Dependencias

- `express`: Framework web
- `amqplib`: Cliente de RabbitMQ
- `winston`: Logger estructurado

## 🐛 Debugging

Para ver logs más detallados, puedes modificar el nivel de log:

```javascript
const logger = winston.createLogger({
  level: "debug", // Cambia de "info" a "debug"
  // ...
});
```

## 🚨 Troubleshooting

### El webhook no se verifica en WhatsApp

- Verifica que `WEBHOOK_VERIFY_TOKEN` coincida con el token configurado en Meta
- Asegúrate de que tu servidor sea accesible públicamente (usa ngrok para pruebas)
- Revisa los logs para ver si la petición GET está llegando

### Los mensajes no se encolan

- Verifica que RabbitMQ esté corriendo: `docker ps` o `systemctl status rabbitmq-server`
- Revisa los logs para ver errores de conexión
- Verifica las credenciales en `RABBIT_URL`

### Error "RabbitMQ channel no disponible"

- El servidor respondió antes de que RabbitMQ se conectara
- Espera a ver el log "✅ Conectado a RabbitMQ y colas listas"
- En producción, considera un health check endpoint

## 📄 Licencia

MIT
