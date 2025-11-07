# Usar imagen base de Node.js 20 LTS Alpine
FROM node:20-alpine

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./

# Instalar dependencias de producción
RUN npm ci --only=production

# Copiar el código fuente
COPY src ./src

# Exponer el puerto de la aplicación
EXPOSE 3000

# Usuario no-root para seguridad
USER node

# Comando de inicio
CMD ["node", "src/server.js"]
