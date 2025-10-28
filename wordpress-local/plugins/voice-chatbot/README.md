# 🎙️ Voice Chatbot para WordPress

Plugin de WordPress que permite conversaciones de voz estilo ChatGPT con un asistente conectado a n8n.

## ✨ Características

- **🎤 Grabación de voz**: Captura audio del usuario en alta calidad
- **🔒 Seguridad JWT**: Autenticación segura con tokens JWT
- **🚫 Control de interrupciones inteligente**:
  - **Durante procesamiento**: NO se puede interrumpir
  - **Durante reproducción**: SÍ se puede interrumpir
- **💬 Interfaz estilo ChatGPT**: Diseño moderno y profesional
- **📱 Responsive**: Funciona en móviles y tablets
- **⚙️ Configurable**: Panel de administración para configurar el webhook

## 🔄 Flujo de Conversación

```
1. Usuario presiona el botón
   ↓
2. 🎤 GRABANDO (Estado: listening)
   - Usuario habla
   - Puede detener la grabación presionando el botón
   ↓
3. ⏳ PROCESANDO (Estado: processing) ❌ NO INTERRUMPIBLE
   - Audio se envía a n8n
   - Se espera respuesta del servidor
   - Usuario NO puede interrumpir
   ↓
4. 🔊 REPRODUCIENDO (Estado: speaking) ✅ SÍ INTERRUMPIBLE
   - Se reproduce el audio de respuesta
   - Usuario puede interrumpir presionando el botón
   ↓
5. ✅ LISTO (Estado: ready)
   - Conversación lista para continuar
```

## 📦 Instalación

1. Copia la carpeta `voice-chatbot` a `wp-content/plugins/`
2. Activa el plugin en WordPress
3. Ve a **Ajustes > Voice Chatbot** para configurar
4. Usa el shortcode `[voice_chatbot]` en cualquier página

## ⚙️ Configuración

### En WordPress:

1. **URL del Webhook**: La URL completa de tu webhook de n8n
   ```
   https://tu-n8n.com/webhook/voice-chat
   ```

2. **Secreto JWT**: Un secreto seguro para firmar tokens (mínimo 32 caracteres)
   ```
   tu_secreto_super_seguro_minimo_32_caracteres
   ```

### En n8n:

Tu workflow debe:

1. **Recibir** el archivo de audio via POST
2. **Validar** el JWT del header `Authorization: Bearer <token>`
3. **Procesar** el audio (transcripción, lógica, etc.)
4. **Generar** audio de respuesta
5. **Devolver** JSON con la URL del audio

## 📡 API del Webhook

### Request (WordPress → n8n)

**Método**: `POST`

**Headers**:
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: multipart/form-data
```

**Body**:
```
FormData {
  audio: File (audio/webm)
}
```

**Estructura del JWT**:
```json
{
  "iss": "https://tu-sitio.com",
  "iat": 1698765432,
  "exp": 1698765732,
  "user_id": 123
}
```

### Response (n8n → WordPress)

**Status**: `200 OK`

**Content-Type**: `application/json`

**Body**:
```json
{
  "audioUrl": "https://tu-servidor.com/respuesta.mp3"
}
```

⚠️ **Importante**: La URL del audio debe ser accesible públicamente.

## 🎨 Uso del Shortcode

Simplemente agrega el shortcode en cualquier página o post:

```
[voice_chatbot]
```

## 🔒 Seguridad

- ✅ Tokens JWT con expiración de 5 minutos
- ✅ Validación de origen del sitio
- ✅ ID de usuario incluido en el token
- ✅ Secreto compartido configurable
- ✅ Prevención de acceso directo a archivos PHP

## 🎯 Estados del Sistema

| Estado | Descripción | Interrumpible | Color |
|--------|-------------|---------------|-------|
| `ready` | Listo para grabar | - | Verde |
| `listening` | Grabando audio | ✅ Sí (detener) | Verde pulsante |
| `processing` | Enviando a n8n | ❌ No | Amarillo |
| `speaking` | Reproduciendo respuesta | ✅ Sí | Azul |

## 📁 Estructura de Archivos

```
voice-chatbot/
├── voice-chatbot.php    # Plugin principal (PHP)
├── voice-chatbot.js     # Lógica del chatbot (JavaScript)
├── style.css           # Estilos (CSS)
└── README.md           # Esta documentación
```

## 🐛 Solución de Problemas

### Error: "Plugin no configurado"
- ✅ Verifica que hayas guardado la URL del webhook y el secreto JWT

### Error: "No se puede acceder al micrófono"
- ✅ Verifica que el sitio use HTTPS (requerido por navegadores)
- ✅ Permite el acceso al micrófono cuando el navegador lo solicite

### Error: "La respuesta no contiene audioUrl"
- ✅ Verifica que tu webhook de n8n devuelva el JSON correcto
- ✅ Asegúrate de que la propiedad sea `audioUrl` (case-sensitive)

### No se puede interrumpir durante procesamiento
- ✅ **Esto es intencional** - el usuario debe esperar la respuesta

### El audio no se reproduce
- ✅ Verifica que la URL del audio sea accesible públicamente
- ✅ Verifica que el formato del audio sea compatible (MP3, WAV, OGG)
- ✅ Revisa la consola del navegador para errores

## 🔧 Ejemplo de Workflow en n8n

```
1. Webhook Trigger
   ↓ Recibe audio + JWT
   
2. Function: Validar JWT
   ↓ Verifica token
   
3. Binary to Text (Transcripción)
   ↓ Convierte audio a texto
   
4. OpenAI/LLM
   ↓ Procesa y genera respuesta
   
5. Text to Speech
   ↓ Convierte respuesta a audio
   
6. Guardar archivo
   ↓ Guarda en servidor/cloud
   
7. Respond to Webhook
   ↓ Devuelve { "audioUrl": "..." }
```

## 📝 Logs y Debug

El plugin registra información en la consola del navegador:

```javascript
// Abre DevTools > Console para ver:
- Estado actual del chatbot
- Errores de micrófono
- Respuestas del servidor
- Estados de reproducción
```

## 🚀 Roadmap

- [ ] Soporte para múltiples idiomas
- [ ] Transcripción en tiempo real
- [ ] Historial de conversaciones
- [ ] Personalización de colores
- [ ] Modo offline con queue
- [ ] Soporte para audio en diferentes formatos

## 📄 Licencia

Este plugin es de código abierto y puede ser modificado libremente.

## 👨‍💻 Soporte

Para reportar problemas o sugerencias, contacta al desarrollador.

---

**Versión**: 2.0  
**Última actualización**: Octubre 2025
