/**
 * Configuración de Express
 * Define la aplicación Express y sus middlewares
 */
import express from "express";
import webhookRoutes from "./routes/webhook.routes.js";
import { captureRawBodyVerify } from "./middlewares/raw-body.middleware.js";

const app = express();

// Middlewares
// express.json() con verify callback para capturar raw body
app.use(express.json({
  verify: captureRawBodyVerify
}));

// Rutas
app.use("/", webhookRoutes);

export default app;
