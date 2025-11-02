/**
 * Middleware para capturar el raw body
 * Necesario para la validación de firma de WhatsApp
 */
import { logger } from "../utils/logger.js";

/**
 * Captura el raw body antes de que sea parseado
 * Lo guarda en req.rawBody para usarlo en la validación de firma
 */
export function captureRawBody(req, res, next) {
  // Solo capturar para peticiones POST/PUT/PATCH
  if (["POST", "PUT", "PATCH"].includes(req.method)) {
    let data = "";

    req.on("data", (chunk) => {
      data += chunk.toString();
    });

    req.on("end", () => {
      req.rawBody = data;
      next();
    });

    req.on("error", (err) => {
      logger.error("❌ Error capturando raw body", { error: err.message });
      next(err);
    });
  } else {
    next();
  }
}
