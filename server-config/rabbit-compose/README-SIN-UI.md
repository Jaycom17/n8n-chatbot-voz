# 🐰 RabbitMQ Sin UI - Guía de Uso

## 🎯 Configuración Actual

```
RabbitMQ (Sin Management UI)
├─ Imagen: rabbitmq:3.12-alpine
├─ Puertos: NINGUNO expuesto
├─ Red: rabbitmq_network (privada)
├─ Acceso: Solo desde tu API
└─ Monitoreo: CLI / Logs
```

---

## 🚀 Inicio Rápido

```bash
# 1. Configurar variables
cp .env.example .env
nano .env

# 2. Generar contraseña segura
openssl rand -base64 32

# 3. Iniciar RabbitMQ
docker-compose up -d

# 4. Verificar que está corriendo
docker ps | grep rabbitmq
```

---

## 🔌 Conexión desde tu API

### Variables de Entorno de tu API

```env
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=tu_password_del_env
RABBITMQ_VHOST=/
```

### Docker Compose de tu API

```yaml
version: "3.8"

services:
  tu-api:
    build: .
    container_name: tu-api
    
    environment:
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672
      - RABBITMQ_USER=${RABBITMQ_USER}
      - RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}
    
    networks:
      - proxy-tier        # Para Nginx
      - rabbitmq_network  # Para RabbitMQ
    
    depends_on:
      rabbitmq:
        condition: service_healthy

networks:
  proxy-tier:
    external: true
  rabbitmq_network:
    external: true
```

### Código de Conexión (Node.js)

```javascript
const amqp = require('amqplib');

async function connectRabbitMQ() {
  const connection = await amqp.connect({
    protocol: 'amqp',
    hostname: process.env.RABBITMQ_HOST || 'rabbitmq',
    port: process.env.RABBITMQ_PORT || 5672,
    username: process.env.RABBITMQ_USER,
    password: process.env.RABBITMQ_PASSWORD,
    vhost: process.env.RABBITMQ_VHOST || '/',
  });
  
  console.log('✅ Conectado a RabbitMQ');
  return connection;
}

// Uso
const connection = await connectRabbitMQ();
const channel = await connection.createChannel();

// Asegurar que la cola existe y es durable
await channel.assertQueue('mi_cola', {
  durable: true,  // Sobrevive a reinicios
});

// Enviar mensaje persistente
channel.sendToQueue('mi_cola', Buffer.from('Hola'), {
  persistent: true,  // Mensaje se guarda en disco
});
```

### Código de Conexión (Python)

```python
import pika
import os

def connect_rabbitmq():
    credentials = pika.PlainCredentials(
        os.getenv('RABBITMQ_USER'),
        os.getenv('RABBITMQ_PASSWORD')
    )
    
    parameters = pika.ConnectionParameters(
        host=os.getenv('RABBITMQ_HOST', 'rabbitmq'),
        port=int(os.getenv('RABBITMQ_PORT', 5672)),
        virtual_host=os.getenv('RABBITMQ_VHOST', '/'),
        credentials=credentials,
        heartbeat=60,
        connection_attempts=5,
        retry_delay=5
    )
    
    return pika.BlockingConnection(parameters)

# Uso
connection = connect_rabbitmq()
channel = connection.channel()

# Crear cola durable
channel.queue_declare(queue='mi_cola', durable=True)

# Enviar mensaje persistente
channel.basic_publish(
    exchange='',
    routing_key='mi_cola',
    body='Hola',
    properties=pika.BasicProperties(
        delivery_mode=2,  # Mensaje persistente
    )
)
```

---

## 📊 Monitoreo (Sin UI)

### Script Automático

```bash
# Dar permisos de ejecución
chmod +x monitor-rabbitmq.sh

# Ejecutar monitoreo
./monitor-rabbitmq.sh
```

### Comandos Manuales

#### Estado General
```bash
docker exec rabbitmq rabbitmqctl status
```

#### Ver Colas
```bash
# Listar todas las colas con mensajes y consumidores
docker exec rabbitmq rabbitmqctl list_queues name messages consumers

# Solo colas con mensajes pendientes
docker exec rabbitmq rabbitmqctl list_queues | grep -v "^.*\s0$"
```

#### Ver Conexiones
```bash
# Listar conexiones activas
docker exec rabbitmq rabbitmqctl list_connections name state peer_host

# Número de conexiones
docker exec rabbitmq rabbitmqctl list_connections | wc -l
```

#### Ver Canales
```bash
docker exec rabbitmq rabbitmqctl list_channels connection number
```

#### Ver Exchanges
```bash
docker exec rabbitmq rabbitmqctl list_exchanges name type
```

#### Ver Bindings
```bash
docker exec rabbitmq rabbitmqctl list_bindings
```

#### Uso de Memoria
```bash
# Memoria de RabbitMQ
docker exec rabbitmq rabbitmqctl status | grep memory

# Memoria del contenedor
docker stats rabbitmq --no-stream
```

---

## 📝 Logs

### Ver Logs en Tiempo Real
```bash
docker logs -f rabbitmq
```

### Últimas 100 líneas
```bash
docker logs rabbitmq --tail 100
```

### Buscar Errores
```bash
docker logs rabbitmq 2>&1 | grep -i error
```

### Logs con Timestamp
```bash
docker logs rabbitmq -t
```

---

## 🔧 Administración

### Crear Usuario
```bash
docker exec rabbitmq rabbitmqctl add_user nuevo_usuario password123
docker exec rabbitmq rabbitmqctl set_user_tags nuevo_usuario administrator
docker exec rabbitmq rabbitmqctl set_permissions -p / nuevo_usuario ".*" ".*" ".*"
```

### Listar Usuarios
```bash
docker exec rabbitmq rabbitmqctl list_users
```

### Eliminar Usuario
```bash
docker exec rabbitmq rabbitmqctl delete_user nombre_usuario
```

### Cambiar Password
```bash
docker exec rabbitmq rabbitmqctl change_password admin nueva_password
```

### Limpiar Cola
```bash
docker exec rabbitmq rabbitmqctl purge_queue nombre_cola
```

### Eliminar Cola
```bash
docker exec rabbitmq rabbitmqctl delete_queue nombre_cola
```

---

## 🐛 Troubleshooting

### RabbitMQ no inicia

```bash
# Ver logs detallados
docker logs rabbitmq

# Verificar configuración
docker inspect rabbitmq

# Revisar recursos
docker stats rabbitmq
```

### API no puede conectarse

```bash
# Verificar que RabbitMQ está corriendo
docker ps | grep rabbitmq

# Verificar que están en la misma red
docker network inspect rabbitmq_network

# Probar conectividad desde tu API
docker exec tu-api ping rabbitmq
docker exec tu-api nc -zv rabbitmq 5672
```

### Colas con muchos mensajes sin consumir

```bash
# Ver colas con mensajes
docker exec rabbitmq rabbitmqctl list_queues name messages

# Verificar consumidores
docker exec rabbitmq rabbitmqctl list_consumers

# Ver si hay conexiones activas
docker exec rabbitmq rabbitmqctl list_connections
```

### Memoria alta

```bash
# Ver uso de memoria
docker exec rabbitmq rabbitmqctl status | grep memory

# Ajustar límite en .env
RABBITMQ_MEMORY_LIMIT=0.4  # Reducir a 40%

# Reiniciar
docker-compose restart rabbitmq
```

---

## 🔄 Backup y Restore

### Exportar Definiciones

```bash
# Exportar configuración (colas, exchanges, usuarios, etc.)
docker exec rabbitmq rabbitmqctl export_definitions /tmp/backup.json
docker cp rabbitmq:/tmp/backup.json ./backup-$(date +%Y%m%d).json
```

### Importar Definiciones

```bash
# Restaurar configuración
docker cp ./backup.json rabbitmq:/tmp/backup.json
docker exec rabbitmq rabbitmqctl import_definitions /tmp/backup.json
```

### Backup Completo de Datos

```bash
# Backup del volumen
docker run --rm \
  -v rabbitmq_data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/rabbitmq-data-$(date +%Y%m%d).tar.gz /data
```

---

## 📈 Optimizaciones de Rendimiento

### En tu código (Node.js)

```javascript
// Usar prefetch para control de flujo
channel.prefetch(10);  // Procesar máximo 10 mensajes a la vez

// Confirmar mensajes manualmente (más confiable)
channel.consume('mi_cola', async (msg) => {
  try {
    await procesarMensaje(msg.content);
    channel.ack(msg);  // Confirmar solo si se procesó bien
  } catch (error) {
    channel.nack(msg, false, true);  // Reencolar si falla
  }
}, { noAck: false });
```

### En tu código (Python)

```python
# Control de prefetch
channel.basic_qos(prefetch_count=10)

# Confirmar mensajes manualmente
def callback(ch, method, properties, body):
    try:
        procesar_mensaje(body)
        ch.basic_ack(delivery_tag=method.delivery_tag)
    except Exception as e:
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=True)

channel.basic_consume(queue='mi_cola', on_message_callback=callback)
```

---

## 🚦 Health Check

### Script de Health Check

```bash
#!/bin/bash
# health-check.sh

if docker exec rabbitmq rabbitmq-diagnostics -q ping; then
    echo "✅ RabbitMQ está saludable"
    exit 0
else
    echo "❌ RabbitMQ no responde"
    exit 1
fi
```

### Agregar a Cron

```bash
# Verificar cada 5 minutos
*/5 * * * * /path/to/health-check.sh || echo "ALERTA: RabbitMQ caído" | mail -s "RabbitMQ Alert" tu@email.com
```

---

## ✅ Checklist de Producción

- [ ] `.env` configurado con credenciales fuertes
- [ ] `.env` en `.gitignore`
- [ ] RabbitMQ iniciado: `docker ps | grep rabbitmq`
- [ ] Tu API puede conectarse: test de conexión exitoso
- [ ] Colas creadas como `durable: true`
- [ ] Mensajes enviados con `persistent: true`
- [ ] Consumidores con confirmación manual (ACK)
- [ ] Script de monitoreo funcionando
- [ ] Backups configurados (opcional)
- [ ] Health checks activos

---

## 🎯 Resumen

**Ventajas de esta configuración:**

| Característica | Beneficio |
|----------------|-----------|
| Sin UI | ✅ Más ligero (menos memoria) |
| Sin puertos expuestos | ✅ Máxima seguridad |
| CLI para administración | ✅ Todo desde terminal |
| Monitoreo por logs | ✅ Integrable con sistemas de logging |
| Red privada | ✅ Solo tu API tiene acceso |

**Tu setup final:**
- 🐰 RabbitMQ: En `rabbitmq_network`, sin puertos
- 🚀 Tu API: En `proxy-tier` + `rabbitmq_network`
- 📊 Monitoreo: CLI + logs + script
- 🔒 Seguridad: Máxima (nada expuesto)

---

## 📚 Recursos

- [RabbitMQ CLI Documentation](https://www.rabbitmq.com/cli.html)
- [RabbitMQ Best Practices](https://www.rabbitmq.com/production-checklist.html)
- [amqplib Documentation](https://amqp-node.github.io/amqplib/)
- [Pika Documentation](https://pika.readthedocs.io/)

¡Configuración profesional sin dependencias de UI! 🚀
