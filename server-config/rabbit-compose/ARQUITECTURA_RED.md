# 🌐 Arquitectura de Red - RabbitMQ Seguro

## 🎯 Diseño de Redes

```
Internet
   │
   ▼
┌──────────────────────────────────────────────────────┐
│              RED: proxy-tier                         │
│  ┌──────────────┐         ┌───────────────┐        │
│  │ Nginx-Proxy  │────────▶│   Tu API      │        │
│  │ (con SSL)    │         │               │        │
│  └──────────────┘         └───────┬───────┘        │
│                                    │                 │
└────────────────────────────────────┼─────────────────┘
                                     │
                    Tu API está en AMBAS redes
                                     │
┌────────────────────────────────────▼─────────────────┐
│           RED: rabbitmq_network (PRIVADA)            │
│                                                      │
│         ┌───────────────┐                           │
│         │   RabbitMQ    │  ← NO expuesto            │
│         │               │                           │
│         └───────────────┘                           │
│                                                      │
└──────────────────────────────────────────────────────┘
```

## 📋 ¿Qué hace cada componente?

### 1. **RabbitMQ** (en `rabbitmq_network`)
- ✅ Solo en red privada `rabbitmq_network`
- ✅ Puerto 5672 NO expuesto al exterior
- ✅ Management UI (15672) solo en `127.0.0.1`
- ✅ Accesible solo por tu API

### 2. **Tu API** (en AMBAS redes)
```yaml
networks:
  - proxy-tier        # Para recibir requests de Nginx
  - rabbitmq_network  # Para conectarse a RabbitMQ
```

### 3. **Nginx** (en `proxy-tier`)
- ✅ Recibe tráfico de Internet
- ✅ Proxy reverso a tu API
- ✅ NO tiene acceso a RabbitMQ

---

## 🚀 Docker Compose de tu API

```yaml
version: "3.8"

services:
  tu-api:
    build: .
    container_name: tu-api
    restart: always
    env_file:
      - .env
    
    environment:
      # Conexión a RabbitMQ (nombre del contenedor)
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672
      - RABBITMQ_USER=${RABBITMQ_USER}
      - RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}
      - RABBITMQ_VHOST=/
      
      # Nginx (para exponer tu API)
      - VIRTUAL_HOST=api.tudominio.com
      - LETSENCRYPT_HOST=api.tudominio.com
      - LETSENCRYPT_EMAIL=tu@email.com
    
    expose:
      - "3000"  # Puerto interno de tu API
    
    networks:
      - proxy-tier        # ← Para Nginx
      - rabbitmq_network  # ← Para RabbitMQ
    
    # Opcional: esperar a que RabbitMQ esté listo
    depends_on:
      rabbitmq:
        condition: service_healthy

networks:
  proxy-tier:
    external: true
  rabbitmq_network:
    external: true  # ← Red creada por RabbitMQ
```

---

## 🔌 Conexión desde tu API

### Node.js (amqplib)

```javascript
const amqp = require('amqplib');

async function connectRabbitMQ() {
  try {
    const connection = await amqp.connect({
      protocol: 'amqp',
      hostname: 'rabbitmq',  // ← Nombre del contenedor
      port: 5672,
      username: process.env.RABBITMQ_USER,
      password: process.env.RABBITMQ_PASSWORD,
      vhost: '/',
    });
    
    console.log('✅ Conectado a RabbitMQ');
    return connection;
  } catch (error) {
    console.error('❌ Error conectando a RabbitMQ:', error);
    throw error;
  }
}

// Uso
const connection = await connectRabbitMQ();
const channel = await connection.createChannel();
```

### Python (pika)

```python
import pika
import os

def connect_rabbitmq():
    credentials = pika.PlainCredentials(
        os.getenv('RABBITMQ_USER'),
        os.getenv('RABBITMQ_PASSWORD')
    )
    
    parameters = pika.ConnectionParameters(
        host='rabbitmq',  # ← Nombre del contenedor
        port=5672,
        virtual_host='/',
        credentials=credentials,
        # Reconexión automática
        connection_attempts=5,
        retry_delay=5
    )
    
    connection = pika.BlockingConnection(parameters)
    print('✅ Conectado a RabbitMQ')
    return connection

# Uso
connection = connect_rabbitmq()
channel = connection.channel()
```

### Java (Spring Boot)

```yaml
# application.yml
spring:
  rabbitmq:
    host: rabbitmq  # ← Nombre del contenedor
    port: 5672
    username: ${RABBITMQ_USER}
    password: ${RABBITMQ_PASSWORD}
    virtual-host: /
    
    # Configuración de reconexión
    connection-timeout: 10000
    requested-heartbeat: 60
```

```java
@Configuration
public class RabbitConfig {
    @Bean
    public ConnectionFactory connectionFactory() {
        CachingConnectionFactory factory = new CachingConnectionFactory("rabbitmq");
        factory.setPort(5672);
        factory.setUsername(rabbitUser);
        factory.setPassword(rabbitPassword);
        factory.setVirtualHost("/");
        return factory;
    }
}
```

---

## 🔍 Verificación y Testing

### 1. Verificar Redes

```bash
# Ver la red de RabbitMQ
docker network inspect rabbitmq_network

# Deberías ver solo "rabbitmq" en la lista de containers

# Tu API debe estar en ambas redes
docker inspect tu-api | grep -A 10 Networks
# Deberías ver "proxy-tier" y "rabbitmq_network"
```

### 2. Probar Conectividad desde tu API

```bash
# Entrar al container de tu API
docker exec -it tu-api sh

# Probar DNS (debe resolver)
ping rabbitmq

# Probar puerto AMQP
nc -zv rabbitmq 5672
# Debe mostrar: Connection to rabbitmq 5672 port [tcp/*] succeeded!

# Probar Management API
curl -u admin:password http://rabbitmq:15672/api/overview
# Debe devolver JSON con info de RabbitMQ
```

### 3. Verificar que NO está expuesto

```bash
# Desde fuera del servidor (tu máquina local)
# Esto DEBE FALLAR:
telnet tu-servidor.com 5672    # ❌ Connection refused
telnet tu-servidor.com 15672   # ❌ Connection refused

# Desde el servidor
# Management UI DEBE funcionar:
curl http://127.0.0.1:15672  # ✅ Responde
```

---

## 🖥️ Acceder al Management UI

### Desde el Servidor

```bash
# SSH al servidor
ssh usuario@tu-servidor

# Abrir navegador en el servidor (si tiene GUI)
xdg-open http://127.0.0.1:15672

# O usar curl para verificar
curl -u admin:password http://127.0.0.1:15672/api/overview | jq
```

### Desde tu Computadora (SSH Tunnel)

```bash
# En tu máquina local, crear túnel SSH
ssh -L 15672:127.0.0.1:15672 usuario@tu-servidor.com

# Dejar terminal abierta y en otro navegador:
http://localhost:15672

# Credenciales: las de tu .env
```

---

## 📦 Ejemplo Completo de Setup

### Estructura de Archivos

```
tu-proyecto/
├── api/
│   ├── docker-compose.yml  ← Tu API
│   ├── Dockerfile
│   ├── .env
│   └── src/
│       └── index.js
│
└── server-config/
    └── rabbit-compose/
        ├── docker-compose.yml  ← RabbitMQ
        └── .env
```

### Orden de Despliegue

```bash
# 1. Verificar que proxy-tier existe
docker network ls | grep proxy-tier

# 2. Desplegar RabbitMQ primero
cd server-config/rabbit-compose
docker-compose up -d

# Verificar que la red rabbitmq_network existe
docker network ls | grep rabbitmq_network

# 3. Desplegar tu API
cd ../../api
docker-compose up -d

# 4. Verificar que todo está conectado
docker inspect tu-api | grep -A 10 Networks
```

---

## ⚙️ Variables de Entorno

### RabbitMQ (.env)

```env
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=tu_password_super_seguro
RABBITMQ_VHOST=/
```

### Tu API (.env)

```env
# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=tu_password_super_seguro
RABBITMQ_VHOST=/

# Nginx
VIRTUAL_HOST=api.tudominio.com
LETSENCRYPT_HOST=api.tudominio.com
LETSENCRYPT_EMAIL=tu@email.com

# Tu aplicación
PORT=3000
NODE_ENV=production
```

---

## 🛡️ Ventajas de esta Arquitectura

| Característica | Beneficio |
|----------------|-----------|
| **Aislamiento** | RabbitMQ en red privada, sin acceso directo desde Internet |
| **Seguridad** | Puerto 5672 nunca expuesto públicamente |
| **Flexibilidad** | Tu API puede conectarse a múltiples servicios en diferentes redes |
| **Escalabilidad** | Fácil agregar más consumidores/productores |
| **Simplicidad** | Cada servicio en su propia red según necesidad |

---

## 🚦 Flujo de Datos

```
1. Usuario hace request
   ↓
2. Nginx recibe en proxy-tier
   ↓
3. Nginx envía a tu-api (en proxy-tier)
   ↓
4. tu-api procesa y encola en RabbitMQ (vía rabbitmq_network)
   ↓
5. RabbitMQ almacena mensaje
   ↓
6. Otro servicio consume desde RabbitMQ (también en rabbitmq_network)
```

---

## 🐛 Troubleshooting

### API no puede conectarse a RabbitMQ

```bash
# Verificar que tu API está en rabbitmq_network
docker network inspect rabbitmq_network | grep tu-api

# Si no aparece, asegúrate que tu docker-compose.yml incluye:
networks:
  - rabbitmq_network

# Y que la red es externa:
networks:
  rabbitmq_network:
    external: true
```

### Error: network rabbitmq_network not found

```bash
# La red debe ser creada automáticamente por RabbitMQ
# Verifica que RabbitMQ esté corriendo
docker ps | grep rabbitmq

# Si no existe la red, iníciala manualmente:
docker network create rabbitmq_network

# Luego reinicia RabbitMQ
cd server-config/rabbit-compose
docker-compose down
docker-compose up -d
```

### Management UI no responde

```bash
# Verificar puerto
docker port rabbitmq

# Debería mostrar:
# 15672/tcp -> 127.0.0.1:15672

# Probar desde el servidor
curl http://127.0.0.1:15672
```

---

## ✅ Checklist Final

- [ ] RabbitMQ corriendo en `rabbitmq_network`
- [ ] Puerto 5672 NO expuesto públicamente
- [ ] Puerto 15672 solo en `127.0.0.1`
- [ ] Tu API en `docker-compose.yml` con ambas redes
- [ ] Variables de entorno configuradas
- [ ] Test de conectividad exitoso
- [ ] SSH tunnel funcionando para Management UI
- [ ] Logs sin errores

---

## 🎯 Resumen

**Tu solución es perfecta porque:**

1. ✅ **RabbitMQ aislado** - Solo en `rabbitmq_network`
2. ✅ **API como puente** - Conecta ambas redes
3. ✅ **Seguridad máxima** - RabbitMQ no expuesto
4. ✅ **Flexibilidad** - Fácil agregar más servicios
5. ✅ **Separación clara** - Cada capa en su red

Esta es la arquitectura que usan empresas profesionales. ¡Excelente decisión! 🚀
