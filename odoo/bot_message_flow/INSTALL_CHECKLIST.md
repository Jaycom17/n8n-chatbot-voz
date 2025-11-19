# Checklist de Instalación - Bot Message Flow

## ✅ Verificación Pre-Instalación

- [ ] La carpeta `bot_message_flow` está en la ruta correcta de addons
- [ ] `__manifest__.py` existe y tiene la configuración correcta
- [ ] `models/__init__.py` importa correctamente `from . import bot_stage_message`
- [ ] `models/bot_stage_message.py` define la clase `BotStageMessage`
- [ ] `views/bot_stage_message_views.xml` contiene las vistas y menús
- [ ] El archivo `__init__.py` en la raíz importa `from . import models`

## 📝 Pasos para Instalar

### Paso 1: Copiar el addon
```bash
# En Linux/Mac
cp -r bot_message_flow /ruta/a/custom-addons/

# En Windows (PowerShell)
Copy-Item -Path "bot_message_flow" -Destination "C:\path\to\custom-addons\" -Recurse
```

### Paso 2: Configurar Odoo
1. Edita `odoo.conf` (o crea uno si no existe)
2. Añade la ruta de custom-addons en `addons_path`
   ```ini
   addons_path = /ruta/a/odoo/addons,/ruta/a/custom-addons
   ```

### Paso 3: Reiniciar Odoo
```bash
# En Linux/Mac
systemctl restart odoo

# O si ejecutas desde terminal
pkill -f odoo-bin  # O Ctrl+C
odoo-bin --addons-path=/ruta/a/custom-addons
```

### Paso 4: Instalar desde Odoo
1. Abre Odoo en el navegador (http://localhost:8069)
2. Ve a **Aplicaciones**
3. Haz clic en **Actualizar lista de módulos** (esquina superior derecha)
4. Busca "Bot Message Flow"
5. Haz clic en **Instalar**

## ✅ Verificación Post-Instalación

- [ ] El módulo aparece como instalado en **Aplicaciones**
- [ ] Aparece **CRM** en el menú lateral
- [ ] Dentro de CRM, aparece **Configuración** → **Configuración del Bot**
- [ ] Se ve **Mensajes del Bot** como opción
- [ ] Puedo hacer clic y ver la lista vacía
- [ ] Puedo crear un nuevo registro

## 🧪 Prueba Rápida

1. Ve a **CRM** → **Configuración del Bot** → **Mensajes del Bot**
2. Haz clic en **Crear**
3. Rellena:
   - **Nombre**: "Prueba"
   - **Mensaje**: "Mensaje de prueba"
4. Haz clic en **Guardar**
5. Deberías ver el registro en la lista

## 🔧 Solución de Problemas

### No aparece en Aplicaciones
```bash
# Actualiza la lista manualmente desde terminal
odoo-bin --addons-path=/ruta/a/custom-addons --update=bot_message_flow
```

### Error "Modelo no encontrado"
- Verifica los imports en `__init__.py`
- Reinicia el servicio de Odoo

### No aparece el menú de CRM
- Instala el módulo `crm` primero
- Actualiza la lista de módulos

### Error de sintaxis XML
- Valida el XML en `bot_stage_message_views.xml`
- Comprueba que todos los tags estén cerrados

## 📊 Estructura de Carpetas (Verificación)

```
bot_message_flow/
├── __init__.py ✅
├── __manifest__.py ✅
├── models/
│   ├── __init__.py ✅
│   └── bot_stage_message.py ✅
├── views/
│   └── bot_stage_message_views.xml ✅
├── README.md ✅
├── odoo.conf.example ✅
├── sql_examples.sql ✅
└── INSTALL_CHECKLIST.md ✅
```

## 📞 Información Adicional

- **Versión Odoo**: 14+
- **Dependencias**: base, crm
- **Licencia**: LGPL-3
- **Autor**: Tu Nombre o Empresa

---

Si todo está correctamente instalado, deberías poder:
1. Crear mensajes del bot
2. Definir flujos entre etapas
3. Filtrar por estado (activo/inactivo)
4. Ver la estructura en la base de datos
