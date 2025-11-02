/**
 * Configuración de Express
 * Define la aplicación Express y sus middlewares
 */
import express from "express";
import webhookRoutes from "./routes/webhook.routes.js";
import { captureRawBody } from "./middlewares/raw-body.middleware.js";

const app = express();

// Middlewares
// IMPORTANTE: captureRawBody debe ir ANTES de express.json()
app.use(captureRawBody);
app.use(express.json());

// Rutas
app.use("/", webhookRoutes);

export default app;
