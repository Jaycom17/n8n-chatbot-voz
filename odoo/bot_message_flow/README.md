# Bot Message Flow - Addon Odoo

## Descripción
Módulo de Odoo para gestionar mensajes y etapas del bot de chatbot de voz. Permite crear y editar mensajes que el bot enviará en cada etapa del flujo de conversación.

## Requisitos
- Odoo 14+ (compatible con versiones modernas)
- Módulo `crm` instalado
- Módulo `base` instalado

## Instalación

### 1. Copiar el addon en la ubicación correcta
Coloca la carpeta `bot_message_flow` en una de estas ubicaciones:

**Opción A: Addons personalizados de Odoo**
```
/ruta/a/odoo/addons/bot_message_flow/
```

**Opción B: Carpeta custom addons (más común)**
```
/ruta/a/custom-addons/bot_message_flow/
```

**Opción C: Si Odoo está instalado en Windows con pip/PyPI**
```
C:\Users\{usuario}\AppData\Local\Programs\Python\Python3x\Lib\site-packages\odoo\addons\bot_message_flow\
```

### 2. Configurar Odoo para encontrar el addon
Si usaste la Opción B, edita el archivo `odoo.conf`:
```ini
[options]
addons_path = /ruta/a/odoo/addons,/ruta/a/custom-addons
```

### 3. Instalar el addon en Odoo

1. Abre Odoo en tu navegador
2. Ve a **Aplicaciones**
3. Haz clic en **Actualizar lista de módulos**
4. Busca "Bot Message Flow"
5. Haz clic en **Instalar**

## Uso

### Acceder al módulo
1. Ve a **CRM**
2. En el menú lateral, busca **Configuración**
3. Haz clic en **Configuración del Bot**
4. Selecciona **Mensajes del Bot**

### Crear un nuevo mensaje
1. Haz clic en **Crear**
2. Completa los campos:
   - **Nombre de la Etapa**: Nombre descriptivo (ej: "Saludo")
   - **Mensaje a enviar**: El contenido del mensaje (ej: "Hola, ¿en qué te puedo ayudar?")
   - **Siguiente etapa**: Selecciona qué etapa sigue después (opcional)
   - **Activo**: Marca si está activo o no
3. Haz clic en **Guardar**

## Estructura del addon

```
bot_message_flow/
├── __init__.py                    # Importa los módulos
├── __manifest__.py                # Configuración del addon
├── models/
│   ├── __init__.py               # Importa los modelos
│   └── bot_stage_message.py       # Modelo principal
├── views/
│   └── bot_stage_message_views.xml # Vistas y menús
└── README.md                       # Este archivo
```

## Características

✅ **Gestión de etapas de mensajes**: Crea etapas con mensajes personalizados
✅ **Flujo de conversación**: Define qué etapa sigue a cada mensaje
✅ **Filtros**: Busca y filtra mensajes por estado (activo/inactivo)
✅ **Validación**: Previene referencias circulares entre etapas
✅ **Integración con CRM**: Accesible desde el módulo de CRM de Odoo

## Campos del Modelo

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | Char | Sí | Nombre de la etapa |
| `message` | Text | Sí | Contenido del mensaje a enviar |
| `next_stage_id` | Many2one | No | Referencia a la siguiente etapa |
| `active` | Boolean | No | Indica si la etapa está activa (default: True) |

## Solución de problemas

### El addon no aparece en "Aplicaciones"
- Asegúrate de que la carpeta esté en la ruta correcta
- Reinicia el servicio de Odoo
- Ve a **Aplicaciones** → **Actualizar lista de módulos**

### Error "Modelo no encontrado"
- Verifica que `__init__.py` en `models/` importa correctamente
- Reinicia Odoo

### No puedo ver el menú de CRM
- Instala el módulo `crm` primero
- Reinicia Odoo
- Actualiza la lista de módulos

## Desarrollo

### Agregar nuevos campos
Edita `models/bot_stage_message.py` y agrega los campos necesarios.
Luego actualiza `views/bot_stage_message_views.xml`.

### Personalizar vistas
Edita `views/bot_stage_message_views.xml` para cambiar el diseño de formularios y listas.

## Licencia
LGPL-3

## Soporte
Para problemas o sugerencias, contacta al equipo de desarrollo.
