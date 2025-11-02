# WhatsApp Webhook API - Arquitectura por Capas

## 📁 Estructura del Proyecto

```
src/
├── config/              # Configuración de la aplicación
│   └── index.js        # Variables de entorno y constantes
│
├── controllers/         # Controladores (lógica de negocio)
│   └── webhook.controller.js
│
├── middlewares/         # Middlewares de Express
│   ├── raw-body.middleware.js            # Captura el raw body
│   └── whatsapp-signature.middleware.js  # Valida firma de WhatsApp
│
├── routes/              # Definición de rutas
│   └── webhook.routes.js
│
├── services/            # Servicios (lógica de infraestructura)
│   └── rabbitmq.service.js
│
├── utils/               # Utilidades compartidas
│   ├── logger.js       # Logger centralizado
│   └── whatsapp-parser.js  # Parser de mensajes de WhatsApp
│
├── app.js               # Configuración de Express
├── server.js            # Punto de entrada del servidor
└── index.js             # Archivo principal (importa server.js)
```

## 🏗️ Capas de la Arquitectura

### 1. **Capa de Configuración** (`config/`)
Centraliza todas las variables de entorno y constantes de la aplicación.

- `index.js`: Exporta un objeto `config` con todas las configuraciones necesarias.

### 2. **Capa de Utilidades** (`utils/`)
Contiene funciones de ayuda y utilidades reutilizables.

- `logger.js`: Logger estructurado usando Winston.
- `whatsapp-parser.js`: Parser para mensajes de WhatsApp.

### 3. **Capa de Middlewares** (`middlewares/`)
Middlewares de Express para validación y procesamiento de peticiones.

- `raw-body.middleware.js`: Captura el body sin parsear (necesario para validación de firma).
- `whatsapp-signature.middleware.js`: Valida que las peticiones vengan de WhatsApp usando HMAC SHA256.

### 4. **Capa de Servicios** (`services/`)
Maneja la lógica de infraestructura y comunicación con servicios externos.

- `rabbitmq.service.js`: Servicio singleton que maneja toda la lógica de RabbitMQ:
  - Conexión y reconexión automática
  - Envío de mensajes con reintentos
  - Manejo de colas de error
  - Cierre graceful

### 5. **Capa de Controladores** (`controllers/`)
Contiene la lógica de negocio de los endpoints.

- `webhook.controller.js`: 
  - `verifyWebhook()`: Verificación del webhook de WhatsApp
  - `receiveWebhook()`: Procesamiento de mensajes entrantes

### 6. **Capa de Rutas** (`routes/`)
Define los endpoints de la API.

- `webhook.routes.js`: Rutas relacionadas con el webhook de WhatsApp (con middlewares de seguridad)

### 7. **Capa de Aplicación**
- `app.js`: Configuración de Express y middlewares
- `server.js`: Inicialización del servidor y manejo del ciclo de vida
- `index.js`: Punto de entrada principal

## 🚀 Uso

### Iniciar el servidor
```bash
npm start
```

### Variables de entorno
Puedes configurar las siguientes variables:

```bash
RABBIT_URL=amqp://admin:admin@localhost
QUEUE_MAIN=whatsapp_messages
QUEUE_ERROR=whatsapp_errors
PORT=3000
WEBHOOK_VERIFY_TOKEN=mi_token_secreto_123
WHATSAPP_APP_SECRET=tu_app_secret_de_meta  # REQUERIDO para producción
```

⚠️ **IMPORTANTE**: Para producción, debes configurar `WHATSAPP_APP_SECRET` para validar que las peticiones realmente vienen de WhatsApp. Ver [SEGURIDAD.md](./SEGURIDAD.md) para más detalles.

## ✨ Ventajas de esta Arquitectura

1. **Separación de responsabilidades**: Cada capa tiene una función específica.
2. **Mantenibilidad**: Es más fácil localizar y modificar código.
3. **Escalabilidad**: Puedes agregar nuevos servicios, rutas o controladores fácilmente.
4. **Testabilidad**: Cada capa puede ser testeada de forma independiente.
5. **Reutilización**: Los servicios y utilidades pueden ser reutilizados en diferentes partes de la aplicación.
6. **Legibilidad**: El código es más fácil de entender y navegar.

## 📦 Dependencias

- `express`: Framework web
- `amqplib`: Cliente de RabbitMQ
- `winston`: Logger estructurado
- `axios`: Cliente HTTP (si se necesita en el futuro)
- `jsonwebtoken`: Para autenticación JWT (si se necesita en el futuro)

## 🔄 Flujo de una Petición

### GET /webhook (Verificación)
1. **Request** → Llega a la ruta GET en `routes/webhook.routes.js`
2. **Controller** → `verifyWebhook()` valida el token con WhatsApp
3. **Response** → Devuelve el challenge si el token es válido

### POST /webhook (Recepción de mensajes)
1. **Request** → Llega al endpoint POST en `routes/webhook.routes.js`
2. **Middleware** → `captureRawBody` guarda el body sin parsear
3. **Middleware** → `express.json()` parsea el body
4. **Middleware** → `validateWhatsAppSignature` valida la firma HMAC
   - ✅ Si es válida → continúa
   - ❌ Si es inválida → retorna 401 Unauthorized
5. **Controller** → `receiveWebhook()` procesa la petición utilizando:
   - **Utils**: Para parsear y loguear
   - **Services**: Para interactuar con RabbitMQ
   - **Config**: Para obtener configuraciones
6. **Response** → El controlador envía la respuesta al cliente

## 🛠️ Próximos Pasos (Opcional)

- Agregar capa de **middlewares** para validación y autenticación
- Implementar **tests unitarios** para cada capa
- Agregar **documentación de API** con Swagger
- Implementar **rate limiting** y seguridad adicional
