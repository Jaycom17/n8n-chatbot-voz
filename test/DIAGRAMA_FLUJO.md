# 🔄 Flujo de Mensajes - Diagrama Visual

## 📱 Flujo Completo: WhatsApp → API → RabbitMQ

```
┌─────────────────┐
│   👤 Usuario    │
│   (WhatsApp)    │
└────────┬────────┘
         │ Envía mensaje
         ↓
┌─────────────────────────────────────────────────────────────┐
│                   🌐 Meta/WhatsApp API                      │
│  - Recibe mensaje del usuario                               │
│  - Genera firma HMAC SHA256 con App Secret                  │
│  - Agrega header: X-Hub-Signature-256                       │
└────────┬────────────────────────────────────────────────────┘
         │ POST /webhook
         ↓
┌─────────────────────────────────────────────────────────────┐
│              🚪 Tu Webhook API (Express)                    │
│                                                              │
│  ┌──────────────────────────────────────────────┐          │
│  │  1️⃣  captureRawBody Middleware               │          │
│  │  - Captura el body sin parsear                │          │
│  │  - Guarda en req.rawBody                      │          │
│  └────────────────┬─────────────────────────────┘          │
│                   ↓                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │  2️⃣  express.json() Middleware                │          │
│  │  - Parsea el JSON del body                    │          │
│  │  - Disponible en req.body                     │          │
│  └────────────────┬─────────────────────────────┘          │
│                   ↓                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │  3️⃣  validateWhatsAppSignature Middleware     │          │
│  │  - Lee X-Hub-Signature-256                    │          │
│  │  - Calcula HMAC de req.rawBody                │          │
│  │  - Compara firmas (timing-safe)               │          │
│  │                                                 │          │
│  │  ❌ Si NO coinciden                            │          │
│  │     → 401 Unauthorized                         │          │
│  │     → Log: "⚠️ firma inválida"                │          │
│  │     → FIN ❌                                   │          │
│  │                                                 │          │
│  │  ✅ Si coinciden                               │          │
│  │     → Continúa al controlador                  │          │
│  └────────────────┬─────────────────────────────┘          │
│                   ↓                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │  4️⃣  webhook.controller.js                    │          │
│  │  - Parsea mensaje con whatsapp-parser         │          │
│  │  - Valida tipo (text o audio)                 │          │
│  │  - Verifica RabbitMQ disponible               │          │
│  └────────────────┬─────────────────────────────┘          │
│                   ↓                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │  5️⃣  rabbitmq.service.js                      │          │
│  │  - Envía a cola con reintentos                │          │
│  │  - Backoff exponencial: 2s → 4s → 8s         │          │
│  │                                                 │          │
│  │  ❌ Si falla tras 3 intentos                   │          │
│  │     → Envía a cola de errores                  │          │
│  │     → Log: "🚨 enviando a cola de errores"    │          │
│  │     → 200 OK (con mensaje de advertencia)     │          │
│  │                                                 │          │
│  │  ✅ Si tiene éxito                             │          │
│  │     → Log: "✅ Mensaje enviado a RabbitMQ"    │          │
│  │     → 200 OK                                   │          │
│  └────────────────┬─────────────────────────────┘          │
│                   ↓                                          │
└─────────────────────────────────────────────────────────────┘
         │
         ↓
┌─────────────────────────────────────────────────────────────┐
│                   🐰 RabbitMQ                                │
│                                                              │
│  ┌──────────────────────┐    ┌─────────────────────────┐  │
│  │  whatsapp_messages   │    │   whatsapp_errors       │  │
│  │  (Cola Principal)    │    │   (Cola de Errores)     │  │
│  │                       │    │                          │  │
│  │  ✅ Mensajes válidos │    │  ❌ Mensajes fallidos   │  │
│  │  listos para         │    │  para revisión manual    │  │
│  │  procesamiento       │    │                          │  │
│  └──────────────────────┘    └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         │                              │
         ↓                              ↓
┌────────────────────┐    ┌──────────────────────────┐
│  📊 Consumidor     │    │  🔍 Análisis de Errores  │
│  (n8n, etc.)       │    │  (Revisión Manual)       │
└────────────────────┘    └──────────────────────────┘
```

## 🔒 Puntos de Seguridad

### ✅ Capa 1: Validación de Origen (Firma HMAC)
```
WhatsApp API → Genera firma con App Secret
                    ↓
Tu API → Valida firma con mismo App Secret
         ✅ Coinciden = Petición válida
         ❌ No coinciden = Petición rechazada
```

**Protege contra:**
- 🚫 Personas enviando peticiones falsas
- 🚫 Ataques de suplantación
- 🚫 Mensajes basura que llenan tus colas

### ✅ Capa 2: Validación de Tipo de Mensaje
```
Mensaje recibido → Verifica tipo
                   ✅ text o audio = Procesa
                   ❌ otro tipo = Ignora (200 OK)
```

### ✅ Capa 3: Verificación de RabbitMQ
```
Antes de enviar → Verifica channel disponible
                  ✅ Disponible = Envía
                  ❌ No disponible = 503 Service Unavailable
```

### ✅ Capa 4: Reintentos y Cola de Errores
```
Intento 1 → Falla → Espera 2s
Intento 2 → Falla → Espera 4s
Intento 3 → Falla → Espera 8s
Intento 4 → Falla → Envía a cola de errores
```

## 📊 Estructura de Mensajes

### Mensaje en Cola Principal (whatsapp_messages)
```json
{
  "phone_number_id": "123456789",
  "from": "5491112345678",
  "type": "text",
  "body": "Hola, este es un mensaje de prueba",
  "audio_id": null
}
```

### Mensaje en Cola de Errores (whatsapp_errors)
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
  "timestamp": "2025-11-02T10:30:00.000Z"
}
```

## 🎯 Escenarios Comunes

### Escenario 1: Mensaje Exitoso ✅
```
Usuario envía mensaje
  ↓
WhatsApp API firma y envía
  ↓
Firma válida ✅
  ↓
RabbitMQ disponible ✅
  ↓
Mensaje encolado ✅
  ↓
200 OK
```

### Escenario 2: Ataque/Petición No Autorizada ❌
```
Atacante envía petición
  ↓
Sin firma o firma inválida ❌
  ↓
401 Unauthorized
  ↓
Log de intento fallido
  ↓
Petición rechazada
```

### Escenario 3: RabbitMQ Temporalmente Caído ⚠️
```
Usuario envía mensaje
  ↓
WhatsApp API firma y envía
  ↓
Firma válida ✅
  ↓
RabbitMQ no disponible ❌
  ↓
503 Service Unavailable
  ↓
WhatsApp reintentará más tarde
```

### Escenario 4: Fallo Temporal de Red 🔄
```
Usuario envía mensaje
  ↓
Firma válida ✅
  ↓
Intento 1 → Falla (error red) ❌
  ↓ (espera 2s)
Intento 2 → Éxito ✅
  ↓
Mensaje encolado
  ↓
200 OK
```

### Escenario 5: RabbitMQ Permanentemente Caído 🚨
```
Usuario envía mensaje
  ↓
Firma válida ✅
  ↓
Intento 1 → Falla ❌ (espera 2s)
Intento 2 → Falla ❌ (espera 4s)
Intento 3 → Falla ❌ (espera 8s)
  ↓
Envía a cola de errores (si está disponible)
  ↓
200 OK (con warning)
  ↓
Requiere revisión manual
```

## 📈 Métricas a Monitorear

| Métrica | Qué observar | Acción si... |
|---------|--------------|--------------|
| **Peticiones 401** | Intentos no autorizados | > 10/min → Posible ataque, revisar IP |
| **Peticiones 503** | RabbitMQ no disponible | > 0 → Revisar RabbitMQ |
| **Mensajes en cola de errores** | Fallos persistentes | > 10 → Revisar logs y RabbitMQ |
| **Tiempo de respuesta** | Performance | > 1s → Revisar carga del servidor |
| **Reconexiones RabbitMQ** | Estabilidad | > 3/hora → Revisar red y RabbitMQ |

---

**Este diagrama muestra el flujo completo con todas las capas de seguridad implementadas.** 🎯
