#!/bin/bash
# monitor-rabbitmq.sh
# Script para monitorear RabbitMQ sin UI

set -e

CONTAINER_NAME="rabbitmq"

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     RabbitMQ Monitor (Sin UI)         ║${NC}"
echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo ""

# Verificar que el contenedor existe
if ! docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${YELLOW}⚠️  El contenedor '${CONTAINER_NAME}' no existe${NC}"
    exit 1
fi

# Verificar que está corriendo
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${YELLOW}⚠️  El contenedor '${CONTAINER_NAME}' no está corriendo${NC}"
    exit 1
fi

# Status general
echo -e "${GREEN}═══ Estado General ═══${NC}"
docker exec $CONTAINER_NAME rabbitmqctl status | grep -E "RabbitMQ version|Erlang|Uptime" || echo "No disponible"
echo ""

# Colas
echo -e "${GREEN}═══ Colas ═══${NC}"
docker exec $CONTAINER_NAME rabbitmqctl list_queues name messages consumers || echo "Sin colas"
echo ""

# Conexiones
echo -e "${GREEN}═══ Conexiones Activas ═══${NC}"
docker exec $CONTAINER_NAME rabbitmqctl list_connections name state peer_host peer_port 2>/dev/null || echo "Sin conexiones"
echo ""

# Canales
echo -e "${GREEN}═══ Canales ═══${NC}"
docker exec $CONTAINER_NAME rabbitmqctl list_channels connection number 2>/dev/null || echo "Sin canales"
echo ""

# Uso de recursos
echo -e "${GREEN}═══ Uso de Recursos ═══${NC}"
docker stats $CONTAINER_NAME --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"
echo ""

# Logs recientes
echo -e "${GREEN}═══ Logs Recientes (últimas 10 líneas) ═══${NC}"
docker logs $CONTAINER_NAME --tail 10 2>&1
