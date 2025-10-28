# 📊 Diagrama de Flujo del Voice Chatbot

## 🎯 Estados y Transiciones

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                    ESTADO INICIAL                           │
│                                                             │
│                  ✅ READY (Listo)                           │
│                    🟢 Botón verde                            │
│              "Listo para escuchar"                          │
│         "Presiona el botón para hablar"                     │
│                                                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Usuario presiona botón
                       ↓
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│              🎤 LISTENING (Escuchando)                      │
│                  🔴 Botón rojo pulsante                      │
│                   "🎤 Grabando..."                           │
│         "Habla ahora. Presiona nuevamente"                  │
│                                                             │
│              ✅ INTERRUMPIBLE (puede detener)               │
│                                                             │
│   MediaRecorder activo → Capturando audio                   │
│                                                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Usuario presiona botón de nuevo
                       ↓
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│           ⏳ PROCESSING (Procesando)                        │
│                🟡 Botón amarillo girando                     │
│                  "⏳ Procesando..."                          │
│      "El asistente está pensando. NO interrumpible"         │
│                                                             │
│            ❌ NO INTERRUMPIBLE (bloqueado)                  │
│                                                             │
│   1. FormData.append(audio, audioBlob)                      │
│   2. fetch(webhook, { JWT token })                          │
│   3. Esperando respuesta...                                 │
│   4. response.json() → { audioUrl: "..." }                  │
│                                                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Respuesta recibida
                       ↓
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│            🔊 SPEAKING (Hablando)                           │
│                 🔵 Botón azul pulsante                       │
│                   "🔊 Hablando..."                           │
│           "Presiona el botón para interrumpir"              │
│                                                             │
│             ✅ INTERRUMPIBLE (puede detener)                │
│                                                             │
│   audioElement.src = audioUrl                               │
│   audioElement.play()                                       │
│   🔊 Reproduciendo respuesta del asistente                   │
│                                                             │
└──────────┬───────────────────────────┬──────────────────────┘
           │                           │
           │ Termina naturalmente      │ Usuario interrumpe
           │                           │
           ↓                           ↓
    ┌─────────────┐          ┌──────────────────┐
    │  Completo   │          │   Interrumpido   │
    │ audioEnded  │          │  stopSpeaking()  │
    └──────┬──────┘          └────────┬─────────┘
           │                          │
           └──────────┬───────────────┘
                      ↓
             ┌─────────────────┐
             │  Volver a READY │
             └─────────────────┘
```

## 🚦 Control de Interrupciones

### ✅ Estados Interrumpibles

```
LISTENING (🎤 Grabando)
├─ Usuario puede: Detener grabación
├─ Acción: stopRecording()
└─ Resultado: Envía audio grabado

SPEAKING (🔊 Reproduciendo)
├─ Usuario puede: Interrumpir respuesta
├─ Acción: stopSpeaking()
└─ Resultado: Detiene audio y vuelve a READY
```

### ❌ Estados NO Interrumpibles

```
PROCESSING (⏳ Procesando)
├─ Usuario NO puede: Cancelar proceso
├─ Acción: showTemporaryMessage()
├─ Resultado: Muestra advertencia
└─ Razón: Evitar estados inconsistentes
```

## 🔄 Ciclo Completo de Conversación

```
Usuario                  Sistema                   n8n
  │                        │                        │
  │   Presiona botón       │                        │
  │──────────────────────>│                        │
  │                        │                        │
  │   🎤 Estado: LISTENING │                        │
  │   Habla...             │                        │
  │                        │                        │
  │   Presiona de nuevo    │                        │
  │──────────────────────>│                        │
  │                        │                        │
  │   ⏳ Estado: PROCESSING│                        │
  │   ❌ NO interrumpible  │   POST /webhook        │
  │                        │──────────────────────>│
  │                        │   JWT + Audio Blob     │
  │                        │                        │
  │                        │                        │  Validar JWT
  │                        │                        │  Transcribir
  │                        │                        │  Procesar IA
  │                        │                        │  Generar TTS
  │                        │                        │
  │                        │   { audioUrl: "..." }  │
  │                        │<──────────────────────│
  │                        │                        │
  │   🔊 Estado: SPEAKING  │                        │
  │   ✅ SÍ interrumpible  │                        │
  │   🔊 Reproduce audio   │                        │
  │                        │                        │
  │   (Escucha)            │                        │
  │   ...                  │                        │
  │                        │                        │
  │   Puede interrumpir:   │                        │
  │   Presiona botón       │                        │
  │──────────────────────>│                        │
  │                        │                        │
  │   ⏸️ Audio detenido    │                        │
  │   ✅ Estado: READY     │                        │
  │                        │                        │
```

## 🎨 Estados Visuales

### Botón Principal

```
READY       🟢  Verde sólido         cursor: pointer
LISTENING   🔴  Rojo + pulso         cursor: pointer
PROCESSING  🟡  Amarillo + giro      cursor: not-allowed
SPEAKING    🔵  Azul + pulso         cursor: pointer
```

### Indicador de Estado

```
READY       ⚫  Gris estático
LISTENING   🟢  Verde pulsante       + ring animado
PROCESSING  🟡  Amarillo parpadeante
SPEAKING    🔵  Azul pulsante        + ring animado
```

## 📊 Flujo de Datos

### Request (WordPress → n8n)

```
┌───────────────────────────────────────────┐
│           WordPress Plugin                │
├───────────────────────────────────────────┤
│  mediaRecorder.stop()                     │
│         ↓                                 │
│  new Blob(audioChunks, 'audio/webm')      │
│         ↓                                 │
│  formData.append('audio', blob)           │
│         ↓                                 │
│  fetch(webhook, {                         │
│    headers: {                             │
│      Authorization: 'Bearer ' + JWT       │
│    },                                     │
│    body: formData                         │
│  })                                       │
└───────────────────┬───────────────────────┘
                    │
                    │ HTTP POST
                    ↓
┌───────────────────────────────────────────┐
│              n8n Webhook                  │
├───────────────────────────────────────────┤
│  1. Recibe FormData                       │
│  2. Valida JWT                            │
│  3. Extrae audio binario                  │
│  4. Transcribe (Whisper/otro)             │
│  5. Procesa con IA                        │
│  6. Genera TTS                            │
│  7. Guarda archivo                        │
│  8. Devuelve URL                          │
└───────────────────┬───────────────────────┘
                    │
                    │ JSON Response
                    ↓
┌───────────────────────────────────────────┐
│           WordPress Plugin                │
├───────────────────────────────────────────┤
│  const data = await response.json()       │
│         ↓                                 │
│  audioElement.src = data.audioUrl         │
│         ↓                                 │
│  audioElement.play()                      │
│         ↓                                 │
│  Usuario escucha respuesta                │
└───────────────────────────────────────────┘
```

## 🔐 Estructura JWT

```
Header
┌─────────────────────┐
│ {                   │
│   "typ": "JWT",     │
│   "alg": "HS256"    │
│ }                   │
└──────────┬──────────┘
           │
           │ base64url_encode
           ↓
        eyJhbGc...

Payload
┌─────────────────────────────┐
│ {                           │
│   "iss": "https://site.com",│
│   "iat": 1698765432,        │
│   "exp": 1698765732,        │  ← +5 minutos
│   "user_id": 123            │
│ }                           │
└──────────┬──────────────────┘
           │
           │ base64url_encode
           ↓
        eyJpc3M...

Signature
┌────────────────────────────────┐
│ HMACSHA256(                    │
│   base64(header) + "." +       │
│   base64(payload),             │
│   SECRET                       │
│ )                              │
└──────────┬─────────────────────┘
           │
           │ base64url_encode
           ↓
        SflKxwRJ...

Final JWT
└──> eyJhbGc...eyJpc3M...SflKxwRJ
     ^^^^^^^^^ ^^^^^^^^^ ^^^^^^^^^
     header    payload   signature
```

## 🎯 Casos de Uso

### Caso 1: Conversación Normal
```
1. Usuario → 🎤 Graba pregunta
2. Sistema → ⏳ Procesa (NO interrumpible)
3. Sistema → 🔊 Reproduce respuesta (interrumpible)
4. Usuario → Escucha completa
5. Volver a paso 1
```

### Caso 2: Interrumpir Respuesta
```
1. Usuario → 🎤 Graba pregunta
2. Sistema → ⏳ Procesa (NO interrumpible)
3. Sistema → 🔊 Reproduce respuesta
4. Usuario → 🛑 Interrumpe (presiona botón)
5. Sistema → ✅ Vuelve a READY
6. Volver a paso 1
```

### Caso 3: Detener Grabación
```
1. Usuario → 🎤 Empieza a grabar
2. Usuario → 🛑 Cambia de opinión (presiona botón)
3. Sistema → Envía audio grabado hasta el momento
4. Continúa flujo normal
```

### Caso 4: Intento de Interrumpir Durante Procesamiento
```
1. Usuario → 🎤 Graba pregunta
2. Sistema → ⏳ Procesa
3. Usuario → 🛑 Intenta interrumpir (presiona botón)
4. Sistema → ❌ Muestra mensaje de advertencia
5. Sistema → Continúa procesando
6. Sistema → 🔊 Reproduce respuesta cuando esté lista
```

## 📱 Responsive Behavior

```
Desktop (>640px)
├─ Container: 600px max-width
├─ Botón: 80x80px
├─ Chat height: 300-500px
└─ Font sizes: Normal

Mobile (<640px)
├─ Container: Full width con margin
├─ Botón: 64x64px
├─ Chat height: 200-300px
└─ Font sizes: Reducido
```

## 🎨 Theme Colors

```css
Primary (Verde)    #10a37f  ✅ READY
Danger (Rojo)      #ef4444  🎤 LISTENING
Warning (Amarillo) #f59e0b  ⏳ PROCESSING
Info (Azul)        #3b82f6  🔊 SPEAKING
```

---

**Este diagrama muestra el flujo completo del Voice Chatbot**  
**Con especial énfasis en el control de interrupciones**

