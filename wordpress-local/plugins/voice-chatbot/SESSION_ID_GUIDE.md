# Session ID para Memoria de Conversacion en n8n

## Como funciona

El plugin ahora envia automaticamente un `session_id` con cada peticion al webhook de n8n.

### Tipos de Session ID:

1. **Usuario logueado en WordPress**:
   ```
   session_id = "wp_user_123"
   ```
   - Se mantiene siempre igual para ese usuario
   - Permite historial entre dispositivos
   - Se pierde si cierra sesion

2. **Usuario NO logueado (invitado)**:
   ```
   session_id = "session_1730000000_abc123xyz"
   ```
   - Se genera automaticamente en el navegador
   - Se guarda en localStorage
   - Se mantiene aunque cierre el navegador
   - Es unico por dispositivo/navegador

## Como se envia

### FormData que recibe n8n:

```
POST /webhook/voice-chat
Content-Type: multipart/form-data

FormData {
  audio: [archivo audio.webm],
  session_id: "wp_user_123" o "session_1730000000_abc123xyz"
}

Headers {
  Authorization: "Bearer [JWT_TOKEN]"
}
```

## Configuracion en n8n

### 1. Extraer el session_id

En tu workflow de n8n, despues del nodo Webhook:

```javascript
// Function Node - Extraer Session ID
const sessionId = $input.first().json.body.session_id;

return {
  json: {
    session_id: sessionId,
    audio: $input.first().binary
  }
};
```

### 2. Usar con Agente de IA (con memoria)

#### Opcion A: AI Agent Node (n8n)

```
AI Agent Node
├─ Memory: Window Buffer Memory
│  └─ Session Key: {{ $json.session_id }}
├─ Chat Model: OpenAI GPT-4
└─ Tools: [tus tools]
```

**Configuracion del Memory Node:**
- Type: Window Buffer Memory
- Session Key: `{{ $json.session_id }}`
- Context Window Length: 10 (o el que prefieras)

#### Opcion B: Langchain (Code Node)

```javascript
const { BufferMemory } = require('langchain/memory');
const { ChatOpenAI } = require('langchain/chat_models/openai');
const { ConversationChain } = require('langchain/chains');

const sessionId = $json.session_id;

// Crear memoria con session_id
const memory = new BufferMemory({
  sessionId: sessionId,
  returnMessages: true,
  memoryKey: 'chat_history'
});

const model = new ChatOpenAI({
  openAIApiKey: process.env.OPENAI_API_KEY,
  modelName: 'gpt-4'
});

const chain = new ConversationChain({
  llm: model,
  memory: memory
});

// Usar la cadena con memoria
const response = await chain.call({
  input: transcribedText
});

return {
  json: {
    response: response.response,
    session_id: sessionId
  }
};
```

### 3. Base de Datos Externa (PostgreSQL/MongoDB)

Si quieres guardar conversaciones permanentemente:

```javascript
// Function Node - Guardar en DB
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL
});

const sessionId = $json.session_id;
const userMessage = $json.transcription;
const botResponse = $json.ai_response;

// Guardar en base de datos
await pool.query(`
  INSERT INTO conversations (session_id, user_message, bot_response, timestamp)
  VALUES ($1, $2, $3, NOW())
`, [sessionId, userMessage, botResponse]);

// Recuperar historial
const history = await pool.query(`
  SELECT user_message, bot_response, timestamp
  FROM conversations
  WHERE session_id = $1
  ORDER BY timestamp DESC
  LIMIT 10
`, [sessionId]);

return {
  json: {
    session_id: sessionId,
    history: history.rows
  }
};
```

## Ejemplo de Workflow Completo en n8n

```
1. Webhook Trigger (POST)
   ├─ Recibe: audio + session_id
   └─ Headers: Authorization JWT

2. Function: Extraer Datos
   ├─ session_id = body.session_id
   └─ audio = binary.audio

3. Whisper: Transcribir Audio
   └─ Input: audio binary

4. Function: Preparar Contexto
   ├─ Obtener session_id
   └─ Recuperar historial (si existe)

5. AI Agent con Memoria
   ├─ Session Key: {{ $json.session_id }}
   ├─ Input: texto transcrito
   └─ Memory: Usa session_id para contexto

6. OpenAI TTS: Generar Audio
   └─ Input: respuesta del agente

7. Function: Log Conversacion (opcional)
   └─ Guardar en DB con session_id

8. Respond to Webhook
   └─ Return: audio binario (MP3)
```

## Ventajas de este sistema

### Usuario Logueado:
- ✅ Historial persistente entre dispositivos
- ✅ Identificacion clara del usuario
- ✅ Puede ver su historial en WordPress
- ✅ Facil de gestionar

### Usuario Invitado:
- ✅ Mantiene conversacion en el mismo navegador
- ✅ No requiere login
- ✅ Privacidad preservada
- ✅ Experiencia fluida

## Limpiar Session (Reset Conversacion)

Si quieres permitir que el usuario "reinicie" la conversacion:

### JavaScript (agregar boton en WordPress):

```javascript
function resetConversation() {
  if (confirm('¿Reiniciar la conversacion?')) {
    localStorage.removeItem('voiceChatbotSessionId');
    location.reload();
  }
}
```

### n8n (endpoint adicional):

```
POST /webhook/reset-session
Body: { session_id: "..." }

Function Node:
  - Eliminar historial de memoria
  - Limpiar base de datos
  - Return: { success: true }
```

## Debug en Consola

El plugin ya muestra en consola:

```javascript
console.log('Session ID:', SESSION_ID);
// Output: Session ID: wp_user_123
// o
// Output: Session ID: session_1730000000_abc123xyz
```

Puedes verificar en Chrome DevTools > Console

## Ejemplo Real de Conversacion con Memoria

```
Usuario (session_id: wp_user_123):
"Hola, me llamo Juan"

Bot:
"Hola Juan, mucho gusto. ¿En que puedo ayudarte?"

--- Segunda interaccion (mismo session_id) ---

Usuario (session_id: wp_user_123):
"Como me llamo?"

Bot:
"Te llamas Juan, me lo dijiste hace un momento."
```

El agente RECUERDA porque comparten el mismo `session_id`.

## Resumen

**Lo que se envia ahora:**
```
FormData {
  audio: [webm binary]
  session_id: "wp_user_123" o "session_xxx_yyy"
}
```

**Como usarlo en n8n:**
```
AI Agent Node > Memory > Session Key: {{ $json.session_id }}
```

**Listo!** El agente ahora tendra memoria de conversacion por usuario.

---

**Actualizado**: Octubre 2025
