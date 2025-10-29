# 📝 Changelog - Formulario Bonito

Todos los cambios notables de este proyecto serán documentados en este archivo.

---

## [2.2.0] - 2025-10-29

### ✨ Nuevo: Gestión Integrada Todo en Uno

#### 🎯 Características Principales
- **Shortcode unificado**: `[formulario_bonito]` ahora incluye TANTO subida de archivos COMO gestión de documentos en una sola vista
- **Auto-refresco inteligente**: La lista de documentos se actualiza automáticamente 1 segundo después de subir archivos
- **Diseño integrado**: Separador visual bonito entre la sección de subida y la de gestión
- **Panel de admin simplificado**: Solo para configuración (JWT Secret + 3 URLs de webhooks)

#### 🔧 Mejoras Técnicas
- Función global `cargarDocumentosFrontend()` para reutilización
- CSS mejorado con contenedor completo y secciones separadas
- Estilos responsive optimizados para móviles
- JavaScript refactorizado para mejor organización

#### 📦 Nuevas Secciones
- `.fb-contenedor-completo` - Contenedor principal
- `.fb-seccion-upload` - Sección de subida de archivos
- `.fb-seccion-gestion` - Sección de gestión de documentos
- `.fb-separador` - Separador visual con gradiente

#### 📚 Documentación
- README.md actualizado con nuevo flujo
- INSTRUCCIONES_USO.md con ejemplos del shortcode unificado
- CHANGELOG.md creado para seguimiento de versiones

#### 🎨 UX/UI
- Separador visual elegante entre secciones
- Animaciones suaves en transiciones
- Mejor organización visual del contenido
- Responsive mejorado para tablets y móviles

---

## [2.1.0] - 2025-10-29

### ✨ Primera Versión con Gestión de Documentos

#### 🎯 Características Principales
- Sistema completo de subida de archivos al RAG
- Panel de administración con configuración de webhooks
- Autenticación JWT entre WordPress y n8n
- Shortcode `[formulario_bonito]` para subir archivos
- Shortcode `[gestion_documentos_rag]` para gestión de documentos

#### 📦 Funcionalidades de Subida
- Soporte para múltiples archivos
- Validación de tipos de archivo
- Formatos soportados: PDF, DOC, DOCX, TXT, JPG, PNG, GIF, BMP, WEBP, SVG
- Detección automática de MIME types
- Mensajes descriptivos de error

#### 📦 Funcionalidades de Gestión
- Listado de documentos almacenados en RAG
- Eliminación individual de documentos
- Confirmación antes de eliminar
- Botón de actualizar lista
- Mensajes de éxito/error

#### 🔐 Seguridad
- Autenticación JWT con HS256
- Token con expiración de 1 hora
- Validación de archivos por extensión
- Sanitización de inputs

#### 🎨 Diseño
- Formulario elegante y moderno
- Tabla de documentos profesional
- Aviso de privacidad y tratamiento de datos
- CSS responsive para móviles

#### ⚙️ Configuración
- Panel de administración en WordPress
- Campos para JWT Secret
- URL de webhook de subida (POST)
- URL de webhook de listado (GET)
- URL de webhook de eliminación (DELETE)

#### 🔧 Técnico
- AJAX para todas las peticiones
- Manejo de errores robusto
- Logging detallado
- Compatibilidad con Docker

---

## Tipos de Cambios

- **✨ Nuevo**: Nuevas características
- **🔧 Mejora**: Mejoras en características existentes
- **🐛 Arreglo**: Corrección de bugs
- **📚 Documentación**: Cambios en documentación
- **🎨 UX/UI**: Cambios visuales y de experiencia de usuario
- **🔐 Seguridad**: Mejoras de seguridad
- **⚡ Performance**: Mejoras de rendimiento
- **♻️ Refactor**: Refactorización de código

---

**Desarrollado con ❤️ por Jaycom (Camilo Orejuela)**
