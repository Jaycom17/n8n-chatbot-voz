-- Datos de ejemplo para bot_stage_message
-- Este script es solo referencia, Odoo maneja la base de datos automáticamente

-- Ejemplo de cómo se vería en la base de datos (NO ejecutar directamente)
/*

INSERT INTO bot_stage_message (
    name, 
    message, 
    next_stage_id, 
    active, 
    create_date, 
    write_date
) VALUES 
(
    'Saludo',
    'Hola, bienvenido a nuestro chatbot de voz. ¿En qué puedo ayudarte?',
    2,
    true,
    NOW(),
    NOW()
),
(
    'Consulta Servicio',
    'Por favor, selecciona el servicio que deseas conocer más.',
    3,
    true,
    NOW(),
    NOW()
),
(
    'Información Técnica',
    'Aquí te proporciono la información técnica que solicitaste.',
    4,
    true,
    NOW(),
    NOW()
),
(
    'Despedida',
    'Gracias por usar nuestro servicio. ¡Hasta pronto!',
    NULL,
    true,
    NOW(),
    NOW()
);

*/

-- En cambio, accede a través de la interfaz de Odoo:
-- 1. Ve a CRM > Configuración del Bot > Mensajes del Bot
-- 2. Haz clic en Crear
-- 3. Completa los campos según corresponda
