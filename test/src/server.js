/**
 * Punto de entrada del servidor
 * Inicia el servidor Express y maneja el ciclo de vida de la aplicación
 */
import app from "./app.js";
import { config } from "./config/index.js";
import { logger } from "./utils/logger.js";
import { rabbitmqService } from "./services/rabbitmq.service.js";

/**
 * Graceful shutdown
 * Maneja el cierre controlado de la aplicación
 */
async function gracefulShutdown(signal) {
  logger.info(`🛑 Señal ${signal} recibida, cerrando servidor...`);
  
  await rabbitmqService.close();
  
  process.exit(0);
}

// Manejo de señales de terminación
process.on("SIGTERM", () => gracefulShutdown("SIGTERM"));
process.on("SIGINT", () => gracefulShutdown("SIGINT"));

/**
 * Iniciar el servidor
 */
async function startServer() {
  try {
    // Conectar a RabbitMQ
    await rabbitmqService.connect();
    
    // Iniciar servidor Express
    app.listen(config.port, () => {
      logger.info(`🚀 Webhook escuchando en http://localhost:${config.port}/webhook`);
      logger.info(`🔐 Token de verificación configurado: ${config.webhookVerifyToken.substring(0, 5)}...`);
    });
  } catch (error) {
    logger.error("❌ Error iniciando el servidor", { error: error.message });
    process.exit(1);
  }
}

// Iniciar la aplicación
startServer();
