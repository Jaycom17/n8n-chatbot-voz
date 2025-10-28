# 🚀 Instalación Rápida - Voice Chatbot

## ⚡ 5 Pasos para Empezar

### 1️⃣ Activar el Plugin (30 segundos)

```bash
# El plugin ya está en:
wordpress-local/plugins/voice-chatbot/

# Ve a WordPress Admin
→ Plugins
→ Busca "Voice Chatbot"
→ Clic en "Activar"
```

### 2️⃣ Configurar el Plugin (2 minutos)

```bash
# En WordPress Admin
→ Ajustes
→ Voice Chatbot
```

**Configurar:**
- **URL del Webhook**: `https://tu-n8n.com/webhook/voice-chat`
- **Secreto JWT**: Copia el sugerido o crea uno de 32+ caracteres

**Ejemplo de secreto:**
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

> ⚠️ **Importante**: El mismo secreto debe estar en n8n para validar.

### 3️⃣ Crear Página con el Chatbot (1 minuto)

```bash
# En WordPress Admin
→ Páginas
→ Añadir nueva
→ Título: "Asistente de Voz"
→ En el contenido, agregar:
```

```
[voice_chatbot]
```

```bash
→ Publicar
→ Ver página
```

### 4️⃣ Configurar n8n (10 minutos)

**Opción A: Workflow Completo**
```bash
# Ver archivo: N8N_WORKFLOW_EXAMPLE.md
# Incluye:
- Validación JWT
- Transcripción con Whisper
- IA con OpenAI
- Text-to-Speech
- Respuesta con URL del audio
```

**Opción B: Prueba Rápida (Mock)**
```javascript
// Crear un workflow simple en n8n:
// 1. Webhook Trigger (POST)
// 2. Function Node:

return {
  json: {
    audioUrl: "https://www2.cs.uic.edu/~i101/SoundFiles/PinkPanther30.wav"
  }
};

// 3. Respond to Webhook
```

### 5️⃣ Probar (30 segundos)

```bash
1. Abre la página con el shortcode
2. Permite acceso al micrófono (si solicita)
3. Presiona el botón verde 🟢
4. Habla tu mensaje
5. Presiona de nuevo para enviar
6. Espera la respuesta (estado amarillo ⏳)
7. Escucha el audio (estado azul 🔵)
8. ¡Listo! ✅
```

---

## 🧪 Prueba sin n8n (Test Local)

Si quieres probar sin configurar n8n primero:

```bash
1. Abre: wordpress-local/plugins/voice-chatbot/test.html
2. Activa "Modo de prueba"
3. Presiona "Guardar Configuración"
4. Recarga la página
5. ¡Prueba el flujo con audio simulado!
```

---

## 📋 Checklist Pre-Launch

- [ ] Plugin activado en WordPress
- [ ] Webhook URL configurada
- [ ] JWT Secret configurado (32+ caracteres)
- [ ] Mismo JWT Secret en n8n
- [ ] Workflow n8n activo
- [ ] Webhook devuelve `{ "audioUrl": "..." }`
- [ ] Audio accesible públicamente
- [ ] Sitio usa HTTPS (requerido para micrófono)
- [ ] Shortcode insertado en página
- [ ] Página publicada
- [ ] Probado en navegador

---

## 🎯 Estados del Sistema

| Estado | Color | Descripción | Interrumpible |
|--------|-------|-------------|---------------|
| 🟢 READY | Verde | Listo para grabar | - |
| 🔴 LISTENING | Rojo | Grabando | ✅ Sí |
| 🟡 PROCESSING | Amarillo | Procesando | ❌ No |
| 🔵 SPEAKING | Azul | Reproduciendo | ✅ Sí |

---

## 🐛 Solución Rápida de Problemas

### ❌ "Plugin no configurado"
```bash
→ Ir a Ajustes > Voice Chatbot
→ Configurar Webhook URL y JWT Secret
→ Guardar
```

### ❌ "No se puede acceder al micrófono"
```bash
→ Verificar que el sitio use HTTPS
→ Permitir acceso al micrófono cuando pregunte
→ Verificar permisos en navegador
```

### ❌ "Error del servidor"
```bash
→ Verificar que el webhook de n8n esté activo
→ Verificar que la URL sea correcta
→ Revisar logs de n8n
→ Probar el webhook con Postman/curl
```

### ❌ "La respuesta no contiene audioUrl"
```bash
→ Verificar respuesta de n8n en Network tab
→ Debe ser: { "audioUrl": "https://..." }
→ Verificar que la propiedad sea audioUrl (case-sensitive)
```

### ❌ "El audio no se reproduce"
```bash
→ Verificar que la URL sea accesible públicamente
→ Probar abrir la URL directamente en navegador
→ Verificar CORS headers si es dominio diferente
→ Verificar formato de audio (MP3, WAV, OGG)
```

---

## 🔧 Configuración Mínima de n8n

```
┌─────────────────┐
│  Webhook Node   │
│  (POST)         │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Function Node  │
│  return {       │
│    json: {      │
│      audioUrl:  │
│      "URL"      │
│    }            │
│  }              │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Respond Node   │
└─────────────────┘
```

---

## 📞 ¿Necesitas Ayuda?

1. **Documentación completa**: Ver `README.md`
2. **Ejemplo de n8n**: Ver `N8N_WORKFLOW_EXAMPLE.md`
3. **Diagrama de flujo**: Ver `DIAGRAMA_FLUJO.md`
4. **Resumen técnico**: Ver `RESUMEN.md`

---

## 🎉 ¡Felicidades!

Si llegaste hasta aquí y todo funciona:

```
✅ Plugin activo
✅ Webhook configurado
✅ n8n funcionando
✅ Audio grabando
✅ Audio reproduciéndose
🎊 ¡Tienes un chatbot de voz funcionando!
```

---

**Tiempo estimado total**: 15-20 minutos  
**Dificultad**: ⭐⭐☆☆☆ (Media)  
**Requisitos**: WordPress + n8n + HTTPS

**¡Disfruta tu Voice Chatbot!** 🎙️🤖

