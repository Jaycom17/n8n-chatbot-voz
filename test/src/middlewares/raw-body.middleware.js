/**
 * Middleware para capturar el raw body
 * Necesario para la validación de firma de WhatsApp
 * 
 * Este middleware usa la función verify de express.json()
 * para capturar el raw body ANTES de que sea parseado
 */

/**
 * Función verify para express.json() que captura el raw body
 * Se ejecuta antes del parsing, guardando el buffer original
 */
export function captureRawBodyVerify(req, res, buf, encoding) {
  if (buf && buf.length) {
    req.rawBody = buf.toString(encoding || 'utf8');
  }
}
