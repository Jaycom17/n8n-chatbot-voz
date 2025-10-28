# 🔧 Corrección: Soporte para PDFs y Archivos RAG

## 🎯 Problema Identificado

El plugin funcionaba correctamente con **imágenes** pero fallaba con **PDFs** y otros tipos de archivos.

### Causas del Problema:

1. **Detección incorrecta del MIME type**: Los navegadores y WordPress no siempre detectan correctamente el tipo MIME de archivos como PDFs, enviando `application/octet-stream` o un tipo vacío.

2. **Falta de fallback**: No había un sistema de respaldo para detectar el tipo de archivo cuando el navegador no lo proporcionaba.

3. **Manejo de errores incompleto**: No se registraban los errores de upload de archivos individuales.

---

## ✅ Soluciones Implementadas

### 1. Nueva Función: `fb_detectar_mime_type()`

Se agregó una función robusta que detecta el MIME type correcto de archivos para el sistema RAG:

```php
function fb_detectar_mime_type($filename, $filepath)
```

**Características:**
- ✅ Usa `finfo_open()` de PHP para detección automática (si está disponible)
- ✅ Fallback a detección por extensión optimizado para RAG
- ✅ Soporta los formatos necesarios para procesamiento de documentos:
  - **Documentos**: PDF, DOC, DOCX, TXT
  - **Imágenes**: JPG, JPEG, PNG, GIF, BMP, SVG, WEBP

### 2. Validación de Archivos en el Servidor

Se agregó validación para asegurar que solo se suban los tipos de archivo permitidos:

```php
$extensiones_permitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
```

**Beneficios:**
- ✅ Seguridad adicional en el lado del servidor
- ✅ Mensajes de error claros cuando se intenta subir un archivo no permitido
- ✅ Evita el procesamiento de archivos que el sistema RAG no puede manejar

### 3. Restricción en el Input HTML

El campo de archivo ahora especifica exactamente qué tipos se pueden seleccionar:

```html
<input type="file" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg">
```

**Ventajas:**
- ✅ El explorador de archivos solo muestra los tipos permitidos
- ✅ Mejor experiencia de usuario
- ✅ Prevención de errores antes de enviar

### 4. Manejo de Errores de Upload

Se agregó logging completo de errores de upload:

```php
$error_messages = [
    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
    // ... más errores
];
```

---

## 📝 Formatos Soportados

### ✅ Documentos
- **PDF** (`.pdf`) - application/pdf
- **Word** (`.doc`, `.docx`) - Documentos de Microsoft Word
- **Texto** (`.txt`) - Texto plano

### ✅ Imágenes
- **JPEG** (`.jpg`, `.jpeg`) - image/jpeg
- **PNG** (`.png`) - image/png
- **GIF** (`.gif`) - image/gif
- **BMP** (`.bmp`) - image/bmp
- **SVG** (`.svg`) - image/svg+xml
- **WebP** (`.webp`) - image/webp

---

## 🧪 Cómo Probar

1. **Reinicia el servidor de WordPress** (si está en Docker: `docker-compose restart`)
2. Intenta subir:
   - ✅ Un PDF → Debería funcionar perfectamente
   - ✅ Una imagen → Sigue funcionando como antes
   - ✅ Un DOCX → Funciona correctamente
   - ❌ Un archivo MP3 o ZIP → Se rechazará con mensaje claro

---

## 🔒 Seguridad

### Validación en Tres Capas:

1. **Cliente (HTML)**: El atributo `accept` del input
2. **Servidor (PHP)**: Validación de extensiones permitidas
3. **MIME Type**: Detección correcta del tipo de contenido

Esta estrategia de defensa en profundidad asegura que solo los archivos apropiados sean procesados por el sistema RAG.

---

## 🔍 Debugging

Si algo no funciona, revisa los logs de PHP:

```bash
# En el contenedor de WordPress
tail -f /var/log/apache2/error.log

# O si usas PHP-FPM
tail -f /var/log/php-fpm/error.log
```

Los errores de upload ahora se registran con:
```
Error subiendo archivo nombre.pdf: [mensaje de error]
```

---

## 🎯 Ventajas del Enfoque Actual

1. **Foco en RAG**: Solo acepta archivos que el sistema puede procesar efectivamente
2. **Mejor rendimiento**: No se desperdician recursos en archivos no compatibles
3. **Seguridad mejorada**: Superficie de ataque reducida
4. **UX clara**: El usuario sabe exactamente qué puede subir
5. **Mantenibilidad**: Código más limpio y fácil de mantener

---

## 📌 Agregar Nuevos Formatos (Si es necesario)

Si en el futuro necesitas agregar más tipos de archivo:

1. **Actualiza el array de extensiones permitidas** (línea ~304):
```php
$extensiones_permitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'NUEVA_EXT'];
```

2. **Agrega el MIME type** en `fb_detectar_mime_type()` (línea ~115):
```php
'nueva_ext' => 'application/tipo-mime',
```

3. **Actualiza el atributo accept** del input (línea ~244):
```html
accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.nueva_ext"
```

---

**Autor:** Jaycom 🚀  
**Versión del Plugin:** 2.1  
**Fecha:** Octubre 2025
**Estado:** ✅ Optimizado para sistema RAG
