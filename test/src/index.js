import express from "express";
import amqp from "amqplib";
import winston from "winston";

const app = express();
app.use(express.json());

// 🪣 Configuración principal
const RABBIT_URL = process.env.RABBIT_URL || "amqp://admin:admin@localhost";
const QUEUE_MAIN = process.env.QUEUE_MAIN || "whatsapp_messages";
const QUEUE_ERROR = process.env.QUEUE_ERROR || "whatsapp_errors";
const PORT = process.env.PORT || 3000;
const WEBHOOK_VERIFY_TOKEN = process.env.WEBHOOK_VERIFY_TOKEN || "mi_token_secreto_123";

let channel;
let connection;

// 🧾 Logger estructurado (JSON)
const logger = winston.createLogger({
  level: "info",
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.json()
  ),
  transports: [
    new winston.transports.File({ filename: "error.log", level: "error" }),
    new winston.transports.File({ filename: "combined.log" }),
    new winston.transports.Console({ format: winston.format.simple() }),
  ],
});

// 🔌 Conexión y reconexión automática a RabbitMQ
async function connectRabbit() {
  while (true) {
    try {
      connection = await amqp.connect(RABBIT_URL);

      connection.on("close", () => {
        channel = null;
        logger.warn("⚠️ Conexión con RabbitMQ cerrada, reconectando...");
        setTimeout(connectRabbit, 5000);
      });

      connection.on("error", (err) => {
        logger.error("❌ Error en la conexión de RabbitMQ", { error: err.message });
      });

      channel = await connection.createChannel();

      channel.on("error", (err) => {
        logger.error("❌ Error en el channel de RabbitMQ", { error: err.message });
      });

      channel.on("close", () => {
        logger.warn("⚠️ Channel cerrado");
      });

      await channel.assertQueue(QUEUE_MAIN, { durable: true });
      await channel.assertQueue(QUEUE_ERROR, { durable: true });

      logger.info("✅ Conectado a RabbitMQ y colas listas");
      break;
    } catch (error) {
      logger.error("❌ Error conectando a RabbitMQ, reintentando en 5s...", {
        error: error.message,
      });
      await new Promise((r) => setTimeout(r, 5000));
    }
  }
}

// 📦 Enviar mensaje con reintentos y backoff exponencial
async function sendMessageToQueue(message, retries = 3, delay = 2000) {
  // Validación crítica: verificar que el channel existe
  if (!channel) {
    const error = new Error("RabbitMQ channel no disponible");
    logger.error("❌ Channel no disponible, enviando a cola de errores", {
      error: error.message,
    });
    // Intentar almacenar en memoria o rechazar
    throw error;
  }

  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      // Verificar nuevamente antes de cada intento
      if (!channel) {
        throw new Error("Channel perdido durante reintentos");
      }

      await channel.sendToQueue(
        QUEUE_MAIN,
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
        try {
          if (channel) {
            await channel.sendToQueue(
              QUEUE_ERROR,
              Buffer.from(JSON.stringify({ message, error: error.message, timestamp: new Date().toISOString() })),
              { persistent: true }
            );
          }
        } catch (errorQueueError) {
          logger.error("❌ Error crítico: No se pudo enviar a cola de errores", {
            error: errorQueueError.message,
          });
        }
        return false;
      }
    }
  }
}

// 🧩 Parsear mensaje de WhatsApp
function parseWhatsAppMessage(payload) {
  try {
    const entry = payload?.entry?.[0];
    const change = entry?.changes?.[0];
    const value = change?.value;

    if (!value) {
      logger.warn("⚠️ No se encontró el objeto 'value' en el mensaje");
      return null;
    }

    const phone_number_id = value.metadata?.phone_number_id;
    const message = value.messages?.[0];
    const from = message?.from;
    const type = message?.type;

    // Mensaje de texto
    const body = message?.text?.body || null;

    // Mensaje de audio
    const audioId = message?.audio?.id || null;

    const result = { phone_number_id, from, type, body, audio_id: audioId };
    return result;
  } catch (error) {
    logger.error("❌ Error parseando mensaje de WhatsApp", { error: error.message });
    return null;
  }
}

// � Endpoint de verificación del Webhook (GET)
app.get("/webhook", (req, res) => {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  logger.info("📞 Solicitud de verificación del webhook recibida", { mode, token });

  if (mode === "subscribe" && token === WEBHOOK_VERIFY_TOKEN) {
    logger.info("✅ Webhook verificado correctamente");
    res.status(200).send(challenge);
  } else {
    logger.warn("⚠️ Verificación de webhook fallida - Token incorrecto");
    res.sendStatus(403);
  }
});

// �📩 Endpoint principal del Webhook (POST)
app.post("/webhook", async (req, res) => {
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
    if (!channel) {
      logger.error("❌ RabbitMQ no disponible, rechazando mensaje");
      return res.status(503).send("Servicio temporalmente no disponible");
    }

    const success = await sendMessageToQueue(parsedMessage);
    
    if (success) {
      res.status(200).send("✅ Mensaje recibido y encolado");
    } else {
      res.status(200).send("⚠️ Mensaje recibido pero falló al encolar (enviado a cola de errores)");
    }
  } catch (error) {
    logger.error("❌ Error procesando webhook", { error: error.message, stack: error.stack });
    res.status(500).send("Error interno");
  }
});

// � Graceful shutdown
async function gracefulShutdown(signal) {
  logger.info(`🛑 Señal ${signal} recibida, cerrando servidor...`);
  
  try {
    if (channel) {
      await channel.close();
      logger.info("✅ Channel de RabbitMQ cerrado");
    }
    if (connection) {
      await connection.close();
      logger.info("✅ Conexión de RabbitMQ cerrada");
    }
  } catch (error) {
    logger.error("❌ Error cerrando conexiones", { error: error.message });
  }
  
  process.exit(0);
}

process.on("SIGTERM", () => gracefulShutdown("SIGTERM"));
process.on("SIGINT", () => gracefulShutdown("SIGINT"));

// �🚀 Iniciar servidor
app.listen(PORT, async () => {
  await connectRabbit();
  logger.info(`🚀 Webhook escuchando en http://localhost:${PORT}/webhook`);
  logger.info(`🔐 Token de verificación configurado: ${WEBHOOK_VERIFY_TOKEN.substring(0, 5)}...`);
});
