/**
 * Controlador de Webhook
 * Maneja la lógica de negocio para los endpoints del webhook de WhatsApp
 */
import { config } from "../config/index.js";
import { logger } from "../utils/logger.js";
import { parseWhatsAppMessage } from "../utils/whatsapp-parser.js";
import { rabbitmqService } from "../services/rabbitmq.service.js";

/**
 * Verificación del webhook (GET)
 * Endpoint utilizado por WhatsApp para verificar el webhook
 */
export async function verifyWebhook(req, res) {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  logger.info("📞 Solicitud de verificación del webhook recibida", { mode, token });

  if (mode === "subscribe" && token === config.webhookVerifyToken) {
    logger.info("✅ Webhook verificado correctamente");
    res.status(200).send(challenge);
  } else {
    logger.warn("⚠️ Verificación de webhook fallida - Token incorrecto");
    res.sendStatus(403);
  }
}

/**
 * Recepción de mensajes del webhook (POST)
 * Procesa los mensajes entrantes de WhatsApp y los encola en RabbitMQ
 */
export async function receiveWebhook(req, res) {
  try {

    const parsedMessage = parseWhatsAppMessage(req.body);

    if (!parsedMessage) {
      logger.warn("⚠️ Mensaje no válido recibido");
      return res.status(400).send("Mensaje no válido");
    }

    if (parsedMessage.type !== "text" && parsedMessage.type !== "audio") {
      logger.info("ℹ️ Tipo de mensaje no soportado", { type: parsedMessage.type });
      return res.status(200).send("Evento ignorado");
    }

    // Validar que RabbitMQ esté disponible antes de procesar
    if (!rabbitmqService.isConnected()) {
      logger.error("❌ RabbitMQ no disponible, rechazando mensaje");
      return res.status(503).send("Servicio temporalmente no disponible");
    }

    const success = await rabbitmqService.sendMessage(parsedMessage);
    
    if (success) {
      res.status(200).send("✅ Mensaje recibido y encolado");
    } else {
      res.status(200).send("⚠️ Mensaje recibido pero falló al encolar (enviado a cola de errores)");
    }
  } catch (error) {
    logger.error("❌ Error procesando webhook", { error: error.message, stack: error.stack });
    res.status(500).send("Error interno");
  }
}
