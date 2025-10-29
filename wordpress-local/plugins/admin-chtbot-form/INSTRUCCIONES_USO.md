# 📘 Formulario Bonito - Instrucciones de Uso

## 🎯 Descripción
Plugin de WordPress para gestionar documentos en un sistema RAG (Retrieval-Augmented Generation) con integración a n8n.

---

## ⚙️ Configuración Inicial

### 1. Accede al Panel de Administración
Ve a **Formulario Bonito** → **Configuración** en el menú lateral de WordPress.

### 2. Configura los Parámetros

#### 🔐 JWT Secret
- **Qué es:** Clave secreta compartida entre WordPress y n8n para autenticar las peticiones
- **Importante:** Debe ser exactamente el mismo en WordPress y en n8n
- **Recomendación:** Usa al menos 20 caracteres con letras, números y símbolos
- **Ejemplo:** `Jc2024_RAG_Secret_xyz789!`

#### 🌐 Webhook URLs
Configura las siguientes URLs de tus webhooks en n8n:

1. **Webhook URL (n8n)** - Para subir archivos
   - Método: POST
   - Formato: `https://tu-n8n.com/webhook/upload-rag`

2. **Webhook URL Listar Documentos (GET)**
   - Método: GET
   - Formato: `https://tu-n8n.com/webhook/list-rag-documents`
   - Respuesta esperada: `[{"file_name": "documento.pdf"}]`

3. **Webhook URL Eliminar Documentos (DELETE)**
   - Método: DELETE
   - Formato: `https://tu-n8n.com/webhook/delete-rag-document`
   - Body requerido: `{"file_name": "documento.pdf"}`

---

## 📝 Uso de Shortcodes

### 1️⃣ Formulario Completo (Subida + Gestión) - **RECOMENDADO** ⭐

**Shortcode:** `[formulario_bonito]`

**Dónde usarlo:**
- Páginas de WordPress
- Entradas/Posts
- Widgets de texto

**Ejemplo:**
```
[formulario_bonito]
```

**Funcionalidad:**
- ✅ Permite subir múltiples archivos (PDF, DOCX, TXT, imágenes)
- ✅ Envía los archivos automáticamente a n8n
- ✅ Lista todos los documentos almacenados en el RAG
- ✅ Botón de actualizar lista de documentos
- ✅ Opción de eliminar documentos individualmente
- ✅ Confirmación antes de eliminar
- ✅ Auto-refresco de la lista después de subir archivos
- ✅ Mensajes de éxito/error
- ✅ Validación de tipos de archivo
- ✅ **TODO EN UNA SOLA VISTA** 🎯

**Este es el shortcode principal que incluye TODO lo necesario para gestionar documentos del RAG.**

---

### 2️⃣ Solo Gestión de Documentos (Opcional)

**Shortcode:** `[gestion_documentos_rag]`

**Dónde usarlo:**
- Si solo necesitas listar/eliminar documentos sin el formulario de subida
- Para páginas de administración separadas

**Ejemplo:**
```
[gestion_documentos_rag]
```

**Funcionalidad:**
- Lista todos los documentos almacenados en el RAG
- Botón de actualizar lista
- Opción de eliminar documentos individualmente
- Confirmación antes de eliminar
- Mensajes de éxito/error

---

## 🎨 Ejemplo de Uso Básico

Crea una página en WordPress con solo esto:

```
<h1>📤 Sistema RAG - Gestión de Documentos</h1>

[formulario_bonito]
```

**¡Y listo!** Con ese único shortcode ya tienes:
- Formulario de subida
- Lista de documentos
- Gestión completa

---

## 🎨 Ejemplo de Uso Avanzado (Separado)

Si prefieres tener las secciones en páginas separadas:

**Página 1: Solo subida**
```
<h1>Subir Documentos</h1>
[formulario_bonito]
```

**Página 2: Solo gestión**
```
<h1>Gestionar Documentos</h1>
[gestion_documentos_rag]
```

---

## 🔒 Seguridad y Permisos

### Acceso Público (Por defecto)
Por defecto, cualquier persona puede:
- Subir documentos
- Ver la lista de documentos
- Eliminar documentos

### Restringir Acceso (Opcional)

Si deseas que solo usuarios registrados puedan gestionar documentos, edita el archivo `formulario-bonito.php` y descomenta las líneas de verificación:

**En la función `fb_listar_documentos()`:**
```php
if (!is_user_logged_in()) {
    wp_send_json_error('Debes iniciar sesión para ver los documentos.');
    return;
}
```

**En la función `fb_eliminar_documento()`:**
```php
if (!is_user_logged_in()) {
    wp_send_json_error('Debes iniciar sesión para eliminar documentos.');
    return;
}
```

---

## 🛠️ Solución de Problemas

### Error: "Webhook no configurado"
- Verifica que hayas guardado las URLs de los webhooks en la configuración
- Asegúrate de que las URLs estén activas en n8n

### Error: "Error de autenticación"
- Verifica que el JWT Secret sea exactamente el mismo en WordPress y n8n
- Revisa que n8n esté validando el token correctamente

### Error: "No se puede conectar con el webhook"
- Verifica que n8n esté ejecutándose
- Si usas Docker, asegúrate de usar la URL correcta (no localhost)
- Verifica que el webhook esté activo en n8n

### Los documentos no se muestran
- Verifica que el webhook de listado esté devolviendo el formato correcto
- Abre la consola del navegador (F12) para ver errores JavaScript
- Verifica que el webhook responda con código 200

### No se pueden eliminar documentos
- Verifica que el webhook de eliminación esté configurado
- Asegúrate de que el webhook acepte método DELETE
- Verifica los logs de n8n para ver si llega la petición

---

## 📊 Formatos de Archivo Soportados

### Documentos
- PDF (`.pdf`)
- Microsoft Word (`.doc`, `.docx`)
- Texto plano (`.txt`)

### Imágenes
- JPEG (`.jpg`, `.jpeg`)
- PNG (`.png`)
- GIF (`.gif`)
- BMP (`.bmp`)
- SVG (`.svg`)
- WebP (`.webp`)

---

## 🎯 Mejores Prácticas

1. **Configura primero:** Antes de usar los shortcodes, configura todos los webhooks
2. **Prueba en staging:** Prueba primero en un ambiente de desarrollo
3. **Monitorea n8n:** Revisa los logs de n8n para ver si llegan las peticiones
4. **Backups regulares:** Mantén backups de tus documentos importantes
5. **Documenta cambios:** Si modificas el código, documenta los cambios

---

## 🆘 Soporte

Para problemas o dudas:
1. Revisa los logs de WordPress (`wp-content/debug.log` si WP_DEBUG está activado)
2. Revisa los logs de n8n
3. Verifica la consola del navegador (F12)
4. Contacta al desarrollador: Camilo Orejuela (Jaycom)

---

## 📝 Changelog

### Versión 2.1
- ✅ Sistema de subida de archivos con JWT
- ✅ Gestión de documentos en admin
- ✅ Shortcode para gestión de documentos en frontend
- ✅ Soporte para múltiples formatos de archivo
- ✅ Validación y mensajes de error descriptivos

---

**Desarrollado con ❤️ por Jaycom 😎**
