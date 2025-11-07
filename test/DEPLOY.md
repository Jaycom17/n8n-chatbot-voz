# WhatsApp Webhook API - Despliegue con Docker

API de Node.js para recibir webhooks de WhatsApp y enviarlos a RabbitMQ.

## 🚀 Despliegue

### Prerrequisitos

1. **Docker y Docker Compose** instalados
2. **Redes externas creadas:**
   ```bash
   docker network create proxy-tier
   docker network create rabbitmq_network
   ```
3. **Nginx Proxy con Let's Encrypt** corriendo (para SSL automático)
4. **RabbitMQ** corriendo en la red `rabbitmq_network`

### Configuración

1. **Copiar el archivo de ejemplo de variables de entorno:**
   ```bash
   cp .env.example .env
   ```

2. **Editar el archivo `.env` con tus valores:**
   ```bash
   nano .env
   ```

   Variables importantes:
   - `RABBIT_URL`: URL de conexión a RabbitMQ
   - `API_DOMAIN`: Dominio donde se desplegará la API (ej: `api.tudominio.com`)
   - `WEBHOOK_VERIFY_TOKEN`: Token de verificación de WhatsApp
   - `WHATSAPP_APP_SECRET`: Secret de tu app de WhatsApp
   - `LETSENCRYPT_EMAIL`: Tu email para los certificados SSL

### Construir y Levantar

```bash
# Construir la imagen
docker-compose build

# Levantar el servicio
docker-compose up -d

# Ver logs
docker-compose logs -f
```

### Comandos Útiles

```bash
# Ver el estado del contenedor
docker-compose ps

# Detener el servicio
docker-compose down

# Reconstruir y reiniciar
docker-compose up -d --build

# Ver logs en tiempo real
docker-compose logs -f whatsapp-webhook-api

# Ejecutar comandos dentro del contenedor
docker-compose exec whatsapp-webhook-api sh
```

## 🔧 Configuración de Nginx Proxy

Este servicio está configurado para trabajar con **nginx-proxy** y **letsencrypt-companion**.

Las variables de entorno que configuran el proxy son:
- `VIRTUAL_HOST`: El dominio de tu API
- `LETSENCRYPT_HOST`: El dominio para el certificado SSL
- `LETSENCRYPT_EMAIL`: Email para notificaciones de Let's Encrypt
- `VIRTUAL_PORT`: Puerto interno del contenedor (3000)

## 🌐 Redes

El contenedor se conecta a dos redes:

1. **proxy-tier**: Red del nginx-proxy para recibir tráfico HTTPS
2. **rabbitmq_network**: Red para comunicarse con RabbitMQ

## 🔍 Health Check

El servicio incluye un health check que verifica cada 30 segundos que el endpoint `/webhook` responda correctamente.

## 📝 Endpoints

- `GET /webhook` - Verificación de WhatsApp
- `POST /webhook` - Recepción de mensajes de WhatsApp

## 🔒 Seguridad

- El contenedor corre con usuario no-root
- Valida la firma de WhatsApp usando `X-Hub-Signature-256`
- Logs limitados a 10MB por archivo (máximo 3 archivos)
- Health checks para detectar problemas

## 🐛 Troubleshooting

### El contenedor no inicia
```bash
# Ver logs detallados
docker-compose logs whatsapp-webhook-api

# Verificar las redes
docker network ls | grep -E "proxy-tier|rabbitmq_network"
```

### No se puede conectar a RabbitMQ
```bash
# Verificar que RabbitMQ esté en la red correcta
docker network inspect rabbitmq_network

# Probar la conexión
docker-compose exec whatsapp-webhook-api wget -O- rabbitmq:15672
```

### Problemas con SSL
Asegúrate de que:
1. El dominio apunte a tu servidor
2. nginx-proxy esté corriendo
3. letsencrypt-companion esté configurado
4. El puerto 80 y 443 estén abiertos

## 📊 Monitoreo

Para ver las métricas y logs:

```bash
# Logs de la aplicación
docker-compose logs -f

# Estadísticas del contenedor
docker stats whatsapp-webhook-api
```
