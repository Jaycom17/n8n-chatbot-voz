/**
 * Middleware de validación de firma de WhatsApp
 * Verifica que las peticiones POST realmente provengan de Meta/WhatsApp
 */
import crypto from "crypto";
import { config } from "../config/index.js";
import { logger } from "../utils/logger.js";

/**
 * Verifica la firma X-Hub-Signature-256 enviada por WhatsApp
 * @param {Object} req - Request de Express
 * @param {Object} res - Response de Express
 * @param {Function} next - Next middleware
 */
export function validateWhatsAppSignature(req, res, next) {
  // Solo validar en peticiones POST (los GET son para verificación inicial)
  if (req.method !== "POST") {
    return next();
  }

  // Obtener la firma del header
  const signature = req.get("X-Hub-Signature-256");

  if (!signature) {
    logger.warn("⚠️ Petición rechazada: falta el header X-Hub-Signature-256");
    return res.status(401).json({ 
      error: "Unauthorized", 
      message: "Missing signature header" 
    });
  }

  // Obtener el App Secret de WhatsApp
  const appSecret = config.whatsappAppSecret;

  if (!appSecret) {
    logger.error("❌ ERROR DE CONFIGURACIÓN: WHATSAPP_APP_SECRET no está configurado");
    return res.status(500).json({ 
      error: "Configuration error", 
      message: "Server misconfigured" 
    });
  }

  try {
    // El body ya viene parseado por express.json(), necesitamos el raw body
    // Para esto, vamos a usar el rawBody que guardamos en otro middleware
    const rawBody = req.rawBody;

    if (!rawBody) {
      logger.error("❌ Raw body no disponible para validación de firma");
      return res.status(500).json({ 
        error: "Server error", 
        message: "Unable to validate signature" 
      });
    }

    // Calcular el hash esperado usando HMAC SHA256
    const expectedSignature = "sha256=" + crypto
      .createHmac("sha256", appSecret)
      .update(rawBody)
      .digest("hex");

    // Comparación segura para evitar timing attacks
    const signatureBuffer = Buffer.from(signature);
    const expectedBuffer = Buffer.from(expectedSignature);

    if (signatureBuffer.length !== expectedBuffer.length) {
      logger.warn("⚠️ Petición rechazada: firma inválida (longitud diferente)", {
        receivedLength: signatureBuffer.length,
        expectedLength: expectedBuffer.length,
      });
      return res.status(401).json({ 
        error: "Unauthorized", 
        message: "Invalid signature" 
      });
    }

    // crypto.timingSafeEqual previene timing attacks
    const isValid = crypto.timingSafeEqual(signatureBuffer, expectedBuffer);

    if (!isValid) {
      logger.warn("⚠️ Petición rechazada: firma inválida", {
        received: signature,
        ip: req.ip,
      });
      return res.status(401).json({ 
        error: "Unauthorized", 
        message: "Invalid signature" 
      });
    }

    // Firma válida, continuar
    logger.info("✅ Firma de WhatsApp validada correctamente");
    next();
  } catch (error) {
    logger.error("❌ Error validando firma de WhatsApp", { 
      error: error.message,
      stack: error.stack 
    });
    return res.status(500).json({ 
      error: "Server error", 
      message: "Error validating signature" 
    });
  }
}
