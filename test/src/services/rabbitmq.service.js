/**
 * Servicio de RabbitMQ
 * Maneja toda la lógica de conexión, reconexión y envío de mensajes a RabbitMQ
 */
import amqp from "amqplib";
import { config } from "../config/index.js";
import { logger } from "../utils/logger.js";

class RabbitMQService {
  constructor() {
    this.connection = null;
    this.channel = null;
  }

  /**
   * Obtiene el canal actual de RabbitMQ
   * @returns {Object|null} - Canal de RabbitMQ o null si no está disponible
   */
  getChannel() {
    return this.channel;
  }

  /**
   * Verifica si el servicio está conectado
   * @returns {boolean} - true si está conectado, false en caso contrario
   */
  isConnected() {
    return this.channel !== null;
  }

  /**
   * Conecta a RabbitMQ con reconexión automática
   */
  async connect() {
    while (true) {
      try {
        this.connection = await amqp.connect(config.rabbitUrl);

        this.connection.on("close", () => {
          this.channel = null;
          logger.warn("⚠️ Conexión con RabbitMQ cerrada, reconectando...");
          setTimeout(() => this.connect(), config.reconnectDelay);
        });

        this.connection.on("error", (err) => {
          logger.error("❌ Error en la conexión de RabbitMQ", { error: err.message });
        });

        this.channel = await this.connection.createChannel();

        this.channel.on("error", (err) => {
          logger.error("❌ Error en el channel de RabbitMQ", { error: err.message });
        });

        this.channel.on("close", () => {
          logger.warn("⚠️ Channel cerrado");
        });

        await this.channel.assertQueue(config.queueMain, { durable: true });
        await this.channel.assertQueue(config.queueError, { durable: true });

        logger.info("✅ Conectado a RabbitMQ y colas listas");
        break;
      } catch (error) {
        logger.error("❌ Error conectando a RabbitMQ, reintentando en 5s...", {
          error: error.message,
        });
        await new Promise((r) => setTimeout(r, config.reconnectDelay));
      }
    }
  }

  /**
   * Envía un mensaje a la cola principal con reintentos y backoff exponencial
   * @param {Object} message - Mensaje a enviar
   * @param {number} retries - Número de reintentos (opcional)
   * @param {number} delay - Delay inicial entre reintentos en ms (opcional)
   * @returns {boolean} - true si se envió correctamente, false en caso contrario
   */
  async sendMessage(message, retries = config.maxRetries, delay = config.initialRetryDelay) {
    // Validación crítica: verificar que el channel existe
    if (!this.channel) {
      const error = new Error("RabbitMQ channel no disponible");
      logger.error("❌ Channel no disponible, enviando a cola de errores", {
        error: error.message,
      });
      throw error;
    }

    for (let attempt = 1; attempt <= retries; attempt++) {
      try {
        // Verificar nuevamente antes de cada intento
        if (!this.channel) {
          throw new Error("Channel perdido durante reintentos");
        }

        await this.channel.sendToQueue(
          config.queueMain,
          Buffer.from(JSON.stringify(message)),
          { persistent: true }
        );
        logger.info("✅ Mensaje enviado a RabbitMQ", { message });
        return true;
      } catch (error) {
        logger.error(`❌ Error enviando mensaje (intento ${attempt}/${retries})`, {
          error: error.message,
        });
        if (attempt < retries) {
          logger.warn(`⏳ Reintentando en ${delay}ms...`);
          await new Promise((r) => setTimeout(r, delay));
          delay *= 2; // backoff exponencial
        } else {
          logger.error("🚨 Falló tras varios reintentos, enviando a cola de errores");
          await this.sendToErrorQueue(message, error.message);
          return false;
        }
      }
    }
  }

  /**
   * Envía un mensaje a la cola de errores
   * @param {Object} message - Mensaje original
   * @param {string} errorMessage - Mensaje de error
   */
  async sendToErrorQueue(message, errorMessage) {
    try {
      if (this.channel) {
        await this.channel.sendToQueue(
          config.queueError,
          Buffer.from(
            JSON.stringify({
              message,
              error: errorMessage,
              timestamp: new Date().toISOString(),
            })
          ),
          { persistent: true }
        );
      }
    } catch (errorQueueError) {
      logger.error("❌ Error crítico: No se pudo enviar a cola de errores", {
        error: errorQueueError.message,
      });
    }
  }

  /**
   * Cierre graceful de la conexión
   */
  async close() {
    try {
      if (this.channel) {
        await this.channel.close();
        logger.info("✅ Channel de RabbitMQ cerrado");
      }
      if (this.connection) {
        await this.connection.close();
        logger.info("✅ Conexión de RabbitMQ cerrada");
      }
    } catch (error) {
      logger.error("❌ Error cerrando conexiones", { error: error.message });
    }
  }
}

// Exportar una instancia singleton
export const rabbitmqService = new RabbitMQService();
