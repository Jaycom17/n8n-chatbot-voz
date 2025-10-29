# 📦 Formulario Bonito - Plugin WordPress

Plugin de WordPress para gestión de documentos RAG (Retrieval-Augmented Generation) con integración a n8n.

## 🚀 Características

- ✅ **Todo en uno** - Un solo shortcode con subida + gestión de documentos
- ✅ **Subida de archivos múltiples** - PDF, DOCX, TXT e imágenes
- ✅ **Gestión integrada** - Lista y elimina documentos en la misma vista
- ✅ **Auto-refresco** - Lista se actualiza automáticamente al subir archivos
- ✅ **Autenticación JWT** - Seguridad entre WordPress y n8n
- ✅ **Shortcodes frontend** - Usa desde cualquier página
- ✅ **Panel de administración** - Solo para configuración (JWT + URLs)
- ✅ **Validación de archivos** - Solo formatos permitidos
- ✅ **Mensajes descriptivos** - Errores claros para troubleshooting
- ✅ **Diseño responsive** - Funciona perfectamente en móviles y tablets

## 📋 Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- n8n con webhooks configurados
- jQuery (incluido en WordPress)

## 📥 Instalación

1. Copia la carpeta del plugin a `/wp-content/plugins/admin-chtbot-form/`
2. Activa el plugin desde el panel de WordPress
3. Ve a **Formulario Bonito** → **Configuración**
4. Configura el JWT Secret y las URLs de los webhooks
5. Guarda los cambios

## ⚙️ Configuración de Webhooks en n8n

### 1. Webhook de Subida (POST)
```
Método: POST
Autenticación: Header Auth (Bearer Token con JWT)
Recibe: multipart/form-data con archivos
```

### 2. Webhook de Listado (GET)
```
Método: GET
Autenticación: Header Auth (Bearer Token con JWT)
Respuesta: [{"file_name": "documento.pdf"}, ...]
```

### 3. Webhook de Eliminación (DELETE)
```
Método: DELETE
Autenticación: Header Auth (Bearer Token con JWT)
Body: {"file_name": "documento.pdf"}
```

## 📝 Uso de Shortcodes

### Shortcode Principal (Recomendado) ⭐

```
[formulario_bonito]
```

**Incluye TODO:**
- Formulario de subida de archivos
- Lista de documentos almacenados
- Gestión (eliminar documentos)
- Auto-refresco de lista al subir archivos

### Shortcode Solo Gestión (Opcional)

```
[gestion_documentos_rag]
```

Solo muestra la lista de documentos con opción de eliminar (sin formulario de subida).

## 🎨 Ejemplo de Uso Básico

Crea una página en WordPress con **solo esto**:

```html
<h1>Sistema RAG</h1>
[formulario_bonito]
```

**¡Listo!** Ya tienes:
- ✅ Subida de archivos
- ✅ Lista de documentos
- ✅ Gestión completa

Todo en una sola vista integrada y bonita.

## 🔒 Seguridad

Por defecto, las funciones están abiertas al público. Para restringir el acceso:

1. Edita `formulario-bonito.php`
2. Descomenta las líneas de verificación en:
   - `fb_listar_documentos()`
   - `fb_eliminar_documento()`

```php
if (!is_user_logged_in()) {
    wp_send_json_error('Debes iniciar sesión.');
    return;
}
```

## 📁 Estructura de Archivos

```
admin-chtbot-form/
├── formulario-bonito.php    # Archivo principal del plugin
├── script.js                 # JavaScript para AJAX
├── style.css                 # Estilos CSS
├── INSTRUCCIONES_USO.md      # Guía completa de uso
└── README.md                 # Este archivo
```

## 🎯 Formatos Soportados

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

## 🐛 Solución de Problemas

### No se conecta con n8n
- Verifica que n8n esté ejecutándose
- Comprueba las URLs de los webhooks
- Si usas Docker, usa IPs/dominios correctos (no localhost)

### Error de autenticación
- Verifica que el JWT Secret sea idéntico en WordPress y n8n
- Revisa la configuración de autenticación en n8n

### No se muestran documentos
- Verifica que el webhook de listado devuelva el formato correcto
- Abre la consola del navegador (F12) para ver errores
- Revisa los logs de n8n

## 📊 Funciones Principales

### PHP
- `fb_generar_jwt()` - Genera token JWT para autenticación
- `fb_enviar_formulario()` - Maneja subida de archivos vía AJAX
- `fb_listar_documentos()` - Obtiene lista de documentos del RAG
- `fb_eliminar_documento()` - Elimina documentos del RAG
- `fb_detectar_mime_type()` - Detecta tipo MIME de archivos

### JavaScript
- Subida de archivos con FormData
- Gestión de documentos con AJAX
- Confirmaciones de eliminación
- Mensajes de éxito/error dinámicos

## 🔄 Changelog

### Versión 2.2 (Actual)
- ✅ **TODO EN UNO:** Shortcode `[formulario_bonito]` incluye subida + gestión
- ✅ Auto-refresco de lista al subir archivos
- ✅ Diseño integrado con separador visual entre secciones
- ✅ JavaScript mejorado para gestión de documentos
- ✅ CSS responsive para tabla de documentos
- ✅ Mensajes de éxito animados
- ✅ Soporte para usuarios no logueados (configurable)
- ✅ Panel de admin solo para configuración (más limpio)

### Versión 2.1
- ✅ Sistema de subida con JWT
- ✅ Panel de administración
- ✅ Gestión de documentos en admin
- ✅ Validación de archivos
- ✅ Mensajes descriptivos de error

## 👨‍💻 Autor

**Camilo Orejuela (Jaycom)** 😎

## 📄 Licencia

Este plugin es de uso libre para proyectos personales y comerciales.

## 🆘 Soporte

Para dudas o problemas:
1. Revisa `INSTRUCCIONES_USO.md`
2. Verifica logs de WordPress y n8n
3. Contacta al desarrollador

---

**Desarrollado con ❤️ por Jaycom**
