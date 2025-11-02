/**
 * Rutas del Webhook
 * Define los endpoints relacionados con el webhook de WhatsApp
 */
import express from "express";
import { verifyWebhook, receiveWebhook } from "../controllers/webhook.controller.js";
import { validateWhatsAppSignature } from "../middlewares/whatsapp-signature.middleware.js";

const router = express.Router();

// GET /webhook - Verificación del webhook (sin validación de firma)
router.get("/webhook", verifyWebhook);

// POST /webhook - Recepción de mensajes (con validación de firma)
router.post("/webhook", validateWhatsAppSignature, receiveWebhook);

export default router;
