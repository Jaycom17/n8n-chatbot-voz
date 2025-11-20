/**
 * Configuración centralizada de la aplicación
 * Todas las variables de entorno y constantes se definen aquí
 */
// Cargar variables de entorno desde .env
import fs from 'fs';
if (fs.existsSync('.env')) {
  require('dotenv').config();
  console.log('📄 Dotenv: Cargando desde archivo .env');
} else {
  console.log('🐳 Docker: Usando variables de entorno del sistema');
}

export const config = {
  // RabbitMQ
  rabbitUrl: process.env.RABBIT_URL || "amqp://admin:admin@localhost",
  queueMain: process.env.QUEUE_MAIN || "whatsapp_messages",
  queueError: process.env.QUEUE_ERROR || "whatsapp_errors",
  
  // Servidor
  port: process.env.PORT || 3000,
  
  // Webhook
  webhookVerifyToken: process.env.WEBHOOK_VERIFY_TOKEN || "mi_token_secreto_123",
  
  // WhatsApp Security
  whatsappAppSecret: process.env.WHATSAPP_APP_SECRET,
  
  // Reintentos
  maxRetries: 3,
  initialRetryDelay: 2000,
  reconnectDelay: 5000,
};
