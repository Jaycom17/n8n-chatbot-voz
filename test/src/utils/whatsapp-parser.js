/**
 * Parser para mensajes de WhatsApp
 * Extrae y normaliza la información de los webhooks de WhatsApp
 */
import { logger } from "./logger.js";

/**
 * Parsea el payload de WhatsApp y extrae la información relevante
 * @param {Object} payload - Payload recibido desde el webhook de WhatsApp
 * @returns {Object|null} - Objeto con los datos parseados o null si falla
 */
export function parseWhatsAppMessage(payload) {
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
