# 🔧 Ejemplo de Workflow n8n para Voice Chatbot

Este es un ejemplo básico de cómo estructurar tu workflow en n8n para trabajar con el Voice Chatbot de WordPress.

## 📋 Estructura del Workflow

### 1. Webhook Node (Trigger)
```
Tipo: POST
Path: /voice-chat
Response Mode: When Last Node Finishes
```

### 2. Function Node - Validar JWT

```javascript
// Validar JWT recibido
const jwt = require('jsonwebtoken');

// Obtener el token del header
const authHeader = $('Webhook').first().json.headers.authorization;

if (!authHeader || !authHeader.startsWith('Bearer ')) {
  throw new Error('Token JWT no proporcionado');
}

const token = authHeader.split(' ')[1];

// Tu secreto JWT (debe coincidir con WordPress)
const JWT_SECRET = 'tu_secreto_super_seguro_minimo_32_caracteres';

try {
  // Verificar el token
  const decoded = jwt.verify(token, JWT_SECRET);
  
  // Validar expiración
  if (decoded.exp < Date.now() / 1000) {
    throw new Error('Token expirado');
  }
  
  // Retornar datos validados
  return {
    json: {
      valid: true,
      user_id: decoded.user_id,
      site: decoded.iss
    }
  };
  
} catch (error) {
  throw new Error('Token JWT inválido: ' + error.message);
}
```

### 3. Move Binary Data Node
```
Mode: Binary to JSON
Source Key: audio
Destination Key: audioData
```

### 4. HTTP Request Node - Transcribir Audio

**Opción A: Usar OpenAI Whisper**
```
Method: POST
URL: https://api.openai.com/v1/audio/transcriptions
Authentication: Header Auth
  Header Name: Authorization
  Header Value: Bearer YOUR_OPENAI_API_KEY
Body:
  - file: binary (audioData)
  - model: whisper-1
  - language: es
```

**Opción B: Usar otro servicio de transcripción**
```
(Configurar según tu servicio preferido)
```

### 5. Function Node - Procesar Transcripción

```javascript
// Obtener el texto transcrito
const transcription = $('HTTP Request').first().json.text;

return {
  json: {
    userMessage: transcription,
    timestamp: new Date().toISOString()
  }
};
```

### 6. OpenAI Node o LLM de tu elección

```
Resource: Message
Operation: Create
Model: gpt-4 (o el que prefieras)
Prompt: {{ $json.userMessage }}

System Message (opcional):
"Eres un asistente de voz útil y amigable. 
Responde de forma concisa y natural, 
como si estuvieras teniendo una conversación hablada."
```

### 7. HTTP Request Node - Convertir a Audio (Text-to-Speech)

**Opción A: OpenAI TTS**
```
Method: POST
URL: https://api.openai.com/v1/audio/speech
Authentication: Header Auth
  Header Name: Authorization
  Header Value: Bearer YOUR_OPENAI_API_KEY
Body (JSON):
{
  "model": "tts-1",
  "voice": "alloy",
  "input": "{{ $json.text }}",
  "response_format": "mp3"
}
```

**Opción B: Eleven Labs**
```
Method: POST
URL: https://api.elevenlabs.io/v1/text-to-speech/{voice_id}
Authentication: Header Auth
  Header Name: xi-api-key
  Header Value: YOUR_ELEVENLABS_API_KEY
Body (JSON):
{
  "text": "{{ $json.text }}",
  "model_id": "eleven_multilingual_v2",
  "voice_settings": {
    "stability": 0.5,
    "similarity_boost": 0.75
  }
}
```

### 8. Write Binary File Node

```
File Name: audio-{{ $now.format('YYYY-MM-DD-HHmmss') }}.mp3
Binary Data: data (del nodo anterior)
Destination Path: /var/www/html/public/audio/
(Ajusta según tu servidor)
```

### 9. Function Node - Generar URL del Audio

```javascript
// Generar la URL pública del audio
const fileName = $('Write Binary File').first().json.fileName;
const publicUrl = `https://tu-servidor.com/audio/${fileName}`;

return {
  json: {
    audioUrl: publicUrl
  }
};
```

### 10. Respond to Webhook Node

```
Response Code: 200
Response Body:
{
  "audioUrl": "{{ $json.audioUrl }}"
}
```

## 🔒 Instalación de Dependencias en n8n

Si usas n8n con Docker, necesitas instalar `jsonwebtoken`:

```dockerfile
# En tu Dockerfile de n8n
FROM n8nio/n8n

# Instalar dependencias adicionales
USER root
RUN cd /usr/local/lib/node_modules/n8n && \
    npm install jsonwebtoken
USER node
```

O en n8n cloud/self-hosted, usa el Code Node con:

```javascript
// Alternativa sin library externa
function validateJWT(token, secret) {
  const parts = token.split('.');
  if (parts.length !== 3) throw new Error('Token JWT mal formado');
  
  const [header, payload, signature] = parts;
  
  // Verificar firma
  const crypto = require('crypto');
  const signatureCheck = crypto
    .createHmac('sha256', secret)
    .update(`${header}.${payload}`)
    .digest('base64url');
  
  if (signature !== signatureCheck) {
    throw new Error('Firma JWT inválida');
  }
  
  // Decodificar payload
  const payloadJson = JSON.parse(
    Buffer.from(payload, 'base64url').toString()
  );
  
  // Verificar expiración
  if (payloadJson.exp < Date.now() / 1000) {
    throw new Error('Token expirado');
  }
  
  return payloadJson;
}

// Usar la función
const authHeader = $input.first().json.headers.authorization;
const token = authHeader.split(' ')[1];
const JWT_SECRET = 'tu_secreto_super_seguro_minimo_32_caracteres';

const decoded = validateJWT(token, JWT_SECRET);

return { json: decoded };
```

## 🎯 Alternativas Simplificadas

### Opción Simple: Respuesta Pre-grabada

Si solo quieres probar el plugin, puedes devolver una URL de audio fija:

```javascript
// Function Node
return {
  json: {
    audioUrl: "https://www2.cs.uic.edu/~i101/SoundFiles/BabyElephantWalk60.wav"
  }
};
```

### Opción Media: Solo TTS sin IA

```
1. Webhook Trigger
2. Validar JWT
3. Text to Speech (servicio de tu elección)
4. Guardar archivo
5. Respond con URL
```

## 🧪 Testing

### Probar JWT desde n8n

```javascript
// En un Function Node
const crypto = require('crypto');

const JWT_SECRET = 'tu_secreto_super_seguro_minimo_32_caracteres';

// Crear un JWT de prueba
const header = Buffer.from(JSON.stringify({
  typ: 'JWT',
  alg: 'HS256'
})).toString('base64url');

const payload = Buffer.from(JSON.stringify({
  iss: 'https://tu-sitio.com',
  iat: Math.floor(Date.now() / 1000),
  exp: Math.floor(Date.now() / 1000) + 300,
  user_id: 1
})).toString('base64url');

const signature = crypto
  .createHmac('sha256', JWT_SECRET)
  .update(`${header}.${payload}`)
  .digest('base64url');

const token = `${header}.${payload}.${signature}`;

return { json: { token } };
```

## 📊 Variables de Entorno Recomendadas

En tu n8n, configura estas variables:

```env
VOICE_CHATBOT_JWT_SECRET=tu_secreto_super_seguro_minimo_32_caracteres
OPENAI_API_KEY=sk-...
ELEVENLABS_API_KEY=...
PUBLIC_AUDIO_URL=https://tu-servidor.com/audio
```

## 🚀 Deploy del Workflow

1. Copia el workflow JSON del archivo `chatbot-voz.json`
2. Importa en n8n
3. Configura las credenciales (OpenAI, etc.)
4. Ajusta las URLs según tu servidor
5. Activa el workflow
6. Copia la URL del webhook a WordPress

## 📝 Notas Importantes

- El audio debe ser accesible públicamente (CORS habilitado)
- Los archivos de audio deben tener permisos de lectura
- Considera limpiar archivos antiguos periódicamente
- Usa HTTPS en producción
- Implementa rate limiting para evitar abuso

---

**Última actualización**: Octubre 2025
