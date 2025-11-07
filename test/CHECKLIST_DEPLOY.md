# ✅ Checklist de Despliegue a Producción

## 📋 Antes de Desplegar

### 1. Configuración de Seguridad

- [ ] **Obtener App Secret de Meta**
  - Ve a [Meta for Developers](https://developers.facebook.com/)
  - App Settings → Basic → App Secret
  - Copia el valor

- [ ] **Configurar variables de entorno**
  ```bash
  # CRÍTICO: Configurar en tu servidor de producción
  WHATSAPP_APP_SECRET=tu_app_secret_real_aqui
  WEBHOOK_VERIFY_TOKEN=token_super_seguro_aleatorio
  RABBIT_URL=amqp://usuario:password@tu-rabbitmq-host
  ```

- [ ] **Nunca subir el archivo `.env` a Git**
  - Verificar que `.env` está en `.gitignore`
  - Solo subir `.env.example` como plantilla

### 2. Infraestructura

- [ ] **RabbitMQ en producción**
  - RabbitMQ instalado y corriendo
  - Usuario y contraseña seguros
  - Colas creadas (se crearán automáticamente, pero verifica)
  
- [ ] **HTTPS configurado**
  - Certificado SSL/TLS válido
  - Reverse proxy configurado (Nginx/Caddy/Traefik)
  - Redirección HTTP → HTTPS

- [ ] **Firewall configurado**
  - Solo puerto 443 (HTTPS) expuesto al público
  - RabbitMQ accesible solo internamente

### 3. Código

- [ ] **Tests ejecutados**
  ```bash
  npm run test:signature  # Verificar que la firma funciona
  ```

- [ ] **Dependencias actualizadas**
  ```bash
  npm audit fix  # Arreglar vulnerabilidades
  npm update     # Actualizar dependencias
  ```

- [ ] **Build verificado**
  ```bash
  npm install --production  # Solo dependencias de producción
  ```

### 4. Monitoreo y Logs

- [ ] **Logs configurados**
  - Verificar que `error.log` y `combined.log` se escriben
  - Configurar rotación de logs (logrotate)
  
- [ ] **Monitoreo de salud**
  - Considerar agregar endpoint `/health`
  - Configurar alertas para errores

## 🚀 Durante el Despliegue

### 1. Configurar WhatsApp Business API

- [ ] **Actualizar URL del webhook en Meta**
  - Ve a tu app en [Meta for Developers](https://developers.facebook.com/)
  - Webhooks → Edit
  - URL: `https://tu-dominio.com/webhook`
  - Token: El mismo que `WEBHOOK_VERIFY_TOKEN`
  - Campos: `messages`

- [ ] **Verificar el webhook**
  - Meta enviará una petición GET para verificar
  - Debes ver en logs: "✅ Webhook verificado correctamente"

### 2. Iniciar el servidor

```bash
# Opción 1: Con npm
npm start

# Opción 2: Con PM2 (recomendado para producción)
pm2 start src/index.js --name whatsapp-webhook

# Opción 3: Con systemd
systemctl start whatsapp-webhook

# Opción 4: Con Docker
docker-compose up -d
```

### 3. Verificar que todo funciona

- [ ] **Servidor iniciado**
  ```bash
  # Verificar logs
  tail -f combined.log
  
  # Debes ver:
  # ✅ Conectado a RabbitMQ y colas listas
  # 🚀 Webhook escuchando en...
  ```

- [ ] **RabbitMQ conectado**
  - Ver log: "✅ Conectado a RabbitMQ y colas listas"
  - Verificar colas en RabbitMQ Management UI

- [ ] **Webhook verificado en Meta**
  - Estado: "Verificado ✓" en Meta Developer Console

## 🧪 Pruebas Post-Despliegue

### 1. Prueba de seguridad

- [ ] **Petición sin firma (debe fallar)**
  ```bash
  curl -X POST https://tu-dominio.com/webhook \
    -H "Content-Type: application/json" \
    -d '{"test": "data"}'
  
  # Esperado: 401 Unauthorized
  ```

- [ ] **Ver en logs**
  ```
  ⚠️ Petición rechazada: falta el header X-Hub-Signature-256
  ```

### 2. Prueba con mensaje real de WhatsApp

- [ ] **Enviar mensaje de prueba**
  - Envía un mensaje al número de WhatsApp conectado
  
- [ ] **Verificar en logs**
  ```
  ✅ Firma de WhatsApp validada correctamente
  ✅ Mensaje enviado a RabbitMQ
  ```

- [ ] **Verificar en RabbitMQ**
  - Ver mensaje en cola `whatsapp_messages`
  - Verificar estructura del mensaje

### 3. Prueba de reconexión

- [ ] **Reiniciar RabbitMQ**
  ```bash
  systemctl restart rabbitmq-server
  ```

- [ ] **Verificar reconexión automática**
  ```
  ⚠️ Conexión con RabbitMQ cerrada, reconectando...
  ✅ Conectado a RabbitMQ y colas listas
  ```

## 📊 Monitoreo Continuo

### Logs a vigilar

- [ ] **Logs de error**
  ```bash
  tail -f error.log
  ```
  
  **Alerta si ves:**
  - ❌ Error conectando a RabbitMQ (más de 3 veces seguidas)
  - 🚨 Falló tras varios reintentos
  - ⚠️ Petición rechazada: firma inválida (muchos intentos)

- [ ] **Logs combinados**
  ```bash
  tail -f combined.log | grep "Petición rechazada"
  ```
  
  **Alerta si hay muchos intentos de acceso no autorizado**

### Métricas a monitorear

- [ ] **RabbitMQ**
  - Tamaño de colas
  - Mensajes por segundo
  - Conexiones activas

- [ ] **Servidor**
  - Uso de CPU
  - Uso de memoria
  - Conexiones activas

- [ ] **Aplicación**
  - Tasa de errores
  - Tiempo de respuesta
  - Peticiones rechazadas (intentos de ataque)

## 🔄 Actualizaciones

- [ ] **Proceso de actualización**
  1. Hacer backup de `.env`
  2. Descargar nueva versión del código
  3. `npm install`
  4. Verificar cambios en `.env.example`
  5. Reiniciar servidor con graceful restart
  6. Verificar logs

## 🚨 Plan de Rollback

- [ ] **En caso de problemas**
  1. Revertir a versión anterior del código
  2. Reiniciar servidor
  3. Verificar que RabbitMQ está conectado
  4. Verificar que webhook funciona
  5. Investigar causa del problema en logs

## 📞 Contactos de Emergencia

- [ ] **Documentar contactos**
  - Admin de RabbitMQ
  - Admin de servidor
  - Soporte de Meta/WhatsApp Business
  - Team lead del proyecto

## ✨ Post-Despliegue

- [ ] **Documentar el despliegue**
  - Fecha y hora
  - Versión desplegada
  - Problemas encontrados
  - Soluciones aplicadas

- [ ] **Notificar al equipo**
  - Despliegue exitoso
  - URL del webhook
  - Métricas iniciales

---

**¿Todo listo?** 🎉  
¡Tu webhook está seguro y listo para producción!

**Documentación adicional:**
- [README.md](./README.md) - Documentación general
- [SEGURIDAD.md](./SEGURIDAD.md) - Guía de seguridad detallada
- [ARQUITECTURA.md](./ARQUITECTURA.md) - Arquitectura del proyecto
- [RESUMEN_SEGURIDAD.md](./RESUMEN_SEGURIDAD.md) - Resumen rápido de seguridad
