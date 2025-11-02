# RabbitMQ Production Deployment

Configuración profesional de RabbitMQ para despliegue en servidor de producción.

## 🚀 Características

- ✅ **Seguridad mejorada**: Credenciales mediante variables de entorno
- ✅ **Persistencia de datos**: Volúmenes Docker para datos y logs
- ✅ **Health checks**: Monitoreo automático de salud del servicio
- ✅ **Límites de recursos**: Control de CPU y memoria
- ✅ **Rotación de logs**: Gestión automática de archivos de log
- ✅ **Configuración personalizada**: Archivos de configuración avanzados
- ✅ **Red aislada**: Red Docker personalizada
- ✅ **Alta disponibilidad**: Preparado para clustering (opcional)

## 📋 Requisitos Previos

- Docker >= 20.10
- Docker Compose >= 2.0
- Mínimo 2GB RAM disponible
- Mínimo 5GB espacio en disco

## 🔧 Instalación

### 1. Configurar variables de entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar el archivo .env con tus credenciales
nano .env
```

**⚠️ IMPORTANTE**: Cambia las siguientes variables en producción:
- `RABBITMQ_PASSWORD`: Usa una contraseña fuerte
- `RABBITMQ_ERLANG_COOKIE`: Genera un string aleatorio único

### 2. Generar contraseña segura (recomendado)

```bash
# Generar contraseña aleatoria
openssl rand -base64 32

# Generar Erlang cookie
openssl rand -hex 32
```

### 3. Ajustar configuración (opcional)

Edita `rabbitmq.conf` según tus necesidades:
- Límites de memoria y disco
- Configuración de TLS/SSL
- Políticas de clustering
- Timeouts y heartbeats

### 4. Iniciar RabbitMQ

```bash
# Iniciar en modo detached
docker-compose up -d

# Ver logs en tiempo real
docker-compose logs -f rabbitmq

# Verificar estado
docker-compose ps
```

## 🔍 Verificación

### Health Check

```bash
# Verificar que el contenedor esté healthy
docker ps

# Ejecutar diagnóstico manual
docker exec rabbitmq_production rabbitmq-diagnostics status
```

### Acceder a la UI de Management

Abre tu navegador en:
```
http://tu-servidor:15672
```

Credenciales: Las definidas en tu archivo `.env`

## 📊 Monitoreo

### Ver estadísticas

```bash
# Estado general
docker exec rabbitmq_production rabbitmqctl status

# Listar colas
docker exec rabbitmq_production rabbitmqctl list_queues

# Listar conexiones
docker exec rabbitmq_production rabbitmqctl list_connections

# Uso de memoria
docker exec rabbitmq_production rabbitmqctl status | grep memory
```

### Ver logs

```bash
# Logs de Docker
docker-compose logs -f rabbitmq

# Logs internos de RabbitMQ
docker exec rabbitmq_production cat /var/log/rabbitmq/rabbit@rabbitmq-server.log
```

## 🔒 Seguridad

### Firewall

Si usas UFW (Ubuntu):

```bash
# Permitir puerto AMQP (solo desde IPs específicas)
sudo ufw allow from TU_IP_CLIENTE to any port 5672

# Permitir Management UI (solo desde IPs específicas)
sudo ufw allow from TU_IP_ADMIN to any port 15672
```

### Habilitar TLS/SSL (Recomendado para producción)

1. Genera o obtén certificados SSL
2. Descomenta la sección TLS en `rabbitmq.conf`
3. Monta los certificados como volúmenes en `docker-compose.yml`

```yaml
volumes:
  - ./certs/ca_certificate.pem:/etc/rabbitmq/certs/ca_certificate.pem:ro
  - ./certs/server_certificate.pem:/etc/rabbitmq/certs/server_certificate.pem:ro
  - ./certs/server_key.pem:/etc/rabbitmq/certs/server_key.pem:ro
```

## 🔄 Backup y Restore

### Backup

```bash
# Exportar definiciones (colas, exchanges, usuarios, etc.)
docker exec rabbitmq_production rabbitmqctl export_definitions /tmp/backup.json
docker cp rabbitmq_production:/tmp/backup.json ./backup-$(date +%Y%m%d).json

# Backup de datos completo
docker run --rm -v rabbitmq_data:/data -v $(pwd):/backup alpine tar czf /backup/rabbitmq-data-backup-$(date +%Y%m%d).tar.gz /data
```

### Restore

```bash
# Importar definiciones
docker cp ./backup.json rabbitmq_production:/tmp/backup.json
docker exec rabbitmq_production rabbitmqctl import_definitions /tmp/backup.json

# Restore de datos completo
docker run --rm -v rabbitmq_data:/data -v $(pwd):/backup alpine sh -c "cd / && tar xzf /backup/rabbitmq-data-backup.tar.gz"
```

## 📈 Escalabilidad

### Aumentar límites de recursos

Edita `docker-compose.yml`:

```yaml
deploy:
  resources:
    limits:
      cpus: '4'      # Aumentar CPUs
      memory: 4G     # Aumentar RAM
```

### Clustering (múltiples nodos)

Para alta disponibilidad, puedes configurar un cluster. Consulta la documentación oficial de RabbitMQ.

## 🛠️ Comandos Útiles

```bash
# Reiniciar RabbitMQ
docker-compose restart rabbitmq

# Detener RabbitMQ
docker-compose down

# Detener y eliminar volúmenes (¡CUIDADO!)
docker-compose down -v

# Ver uso de recursos
docker stats rabbitmq_production

# Acceder a la shell del contenedor
docker exec -it rabbitmq_production sh

# Actualizar imagen
docker-compose pull
docker-compose up -d
```

## 🐛 Troubleshooting

### El contenedor no inicia

```bash
# Ver logs detallados
docker-compose logs rabbitmq

# Verificar permisos de volúmenes
docker volume inspect rabbitmq_data
```

### No puedo conectarme desde otra aplicación

1. Verifica que los puertos estén abiertos en el firewall
2. Verifica las credenciales en tu aplicación
3. Revisa las conexiones activas: `docker exec rabbitmq_production rabbitmqctl list_connections`

### Problemas de memoria

Ajusta `RABBITMQ_MEMORY_LIMIT` en `.env` o `vm_memory_high_watermark` en `rabbitmq.conf`

## 📚 Recursos

- [Documentación oficial RabbitMQ](https://www.rabbitmq.com/documentation.html)
- [RabbitMQ Configuration](https://www.rabbitmq.com/configure.html)
- [Production Checklist](https://www.rabbitmq.com/production-checklist.html)

## 📝 Notas

- Este setup está optimizado para un servidor único
- Para alta disponibilidad, considera implementar clustering
- Realiza backups regulares de tus definiciones y datos
- Monitorea el uso de recursos regularmente
- Mantén RabbitMQ actualizado a la última versión estable
