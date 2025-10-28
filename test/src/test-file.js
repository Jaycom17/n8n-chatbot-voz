import axios from "axios";
import FormData from "form-data";
import fs from "fs";
import jwt from "jsonwebtoken";

// URL del webhook de n8n
const WEBHOOK_URL = "http://localhost:5678/webhook-test/0202d3c5-0352-491b-a12e-ae7b2a06be73";

// 🔐 Debe ser la misma clave que configuraste en n8n
const SECRET = "wjsdobcv973w24g9783gvvvbu2iohb4v9uebrb290v";

// 1️⃣ Generas el JWT (puedes incluir datos en el payload si quieres)
const token = jwt.sign(
  {
    user: "Camilo",
    role: "cliente",
    iat: Math.floor(Date.now() / 1000),
  },
  SECRET,
  { expiresIn: "5m" } // Token válido por 5 minutos
);

// 2️⃣ Luego envías la petición con el JWT en el header
async function sendFile() {
  try {
    const form = new FormData();
    form.append("file", fs.createReadStream("./test.txt"));
    form.append("nombre", "Camilo");
    form.append("tipo", "documento");

    const response = await axios.post(WEBHOOK_URL, form, {
      headers: {
        ...form.getHeaders(),
        Authorization: `Bearer ${token}`,
      },
    });

    console.log("✅ Archivo enviado con éxito:");
    console.log(response.data);
  } catch (error) {
    if (error.response) {
      console.error("❌ Error al enviar el archivo:");
      console.error("Status:", error.response.status);
      console.error("Body:", error.response.data);
    } else {
      console.error(error.message);
    }
  }
}

sendFile();
