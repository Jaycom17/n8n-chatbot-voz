#!/usr/bin/env node

/**
 * Script de prueba para verificar la validación de firma
 * Genera una firma válida para probar el endpoint
 */

import crypto from "crypto";

// Configuración
const APP_SECRET = process.env.WHATSAPP_APP_SECRET || "test_secret_123";
const WEBHOOK_URL = process.env.WEBHOOK_URL || "http://localhost:3000/webhook";

// Mensaje de prueba
const testMessage = {
  object: "whatsapp_business_account",
  entry: [
    {
      id: "123456789",
      changes: [
        {
          value: {
            messaging_product: "whatsapp",
            metadata: {
              display_phone_number: "1234567890",
              phone_number_id: "123456789",
            },
            messages: [
              {
                from: "521234567890",
                id: "wamid.test123",
                timestamp: Math.floor(Date.now() / 1000),
                type: "text",
                text: {
                  body: "Mensaje de prueba",
                },
              },
            ],
          },
          field: "messages",
        },
      ],
    },
  ],
};

// Convertir a JSON string
const payload = JSON.stringify(testMessage);

// Generar firma HMAC SHA256
const signature = "sha256=" + crypto
  .createHmac("sha256", APP_SECRET)
  .update(payload)
  .digest("hex");

console.log("🧪 Test de Validación de Firma de WhatsApp\n");
console.log("📦 Payload:");
console.log(payload);
console.log("\n🔐 Firma generada:");
console.log(signature);
console.log("\n📝 Header a enviar:");
console.log(`X-Hub-Signature-256: ${signature}`);

console.log("\n\n✅ Comando curl para probar:");
console.log(`
curl -X POST ${WEBHOOK_URL} \\
  -H "Content-Type: application/json" \\
  -H "X-Hub-Signature-256: ${signature}" \\
  -d '${payload}'
`);

console.log("\n❌ Para probar con firma inválida:");
console.log(`
curl -X POST ${WEBHOOK_URL} \\
  -H "Content-Type: application/json" \\
  -H "X-Hub-Signature-256: sha256=firma_invalida" \\
  -d '${payload}'
`);
