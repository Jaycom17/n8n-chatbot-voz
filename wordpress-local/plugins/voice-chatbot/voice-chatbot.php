<?php
/*
Plugin Name: Voice Chatbot
Description: Plugin que permite hablar con un asistente de voz conectado a n8n - Estilo ChatGPT.
Version: 2.0
Author: Tu Nombre
*/

if (!defined('ABSPATH')) {
  exit; // Evita acceso directo
}

// ============================================
// Configuración del Plugin
// ============================================

// Agregar menú en el admin
add_action('admin_menu', 'voice_chatbot_add_admin_menu');
function voice_chatbot_add_admin_menu() {
  add_options_page(
    'Configuración Voice Chatbot',
    'Voice Chatbot',
    'manage_options',
    'voice-chatbot-settings',
    'voice_chatbot_settings_page'
  );
}

// Registrar configuraciones
add_action('admin_init', 'voice_chatbot_settings_init');
function voice_chatbot_settings_init() {
  register_setting('voice_chatbot_settings', 'voice_chatbot_webhook_url');
  register_setting('voice_chatbot_settings', 'voice_chatbot_jwt_secret');

  add_settings_section(
    'voice_chatbot_section',
    'Configuración de n8n',
    'voice_chatbot_section_callback',
    'voice-chatbot-settings'
  );

  add_settings_field(
    'voice_chatbot_webhook_url',
    'URL del Webhook de n8n',
    'voice_chatbot_webhook_url_render',
    'voice-chatbot-settings',
    'voice_chatbot_section'
  );

  add_settings_field(
    'voice_chatbot_jwt_secret',
    'Secreto JWT',
    'voice_chatbot_jwt_secret_render',
    'voice-chatbot-settings',
    'voice_chatbot_section'
  );
}

function voice_chatbot_section_callback() {
  echo '<p>Configura la conexión con tu webhook de n8n y el secreto para generar tokens JWT.</p>';
}

function voice_chatbot_webhook_url_render() {
  $value = get_option('voice_chatbot_webhook_url', '');
  echo '<input type="url" name="voice_chatbot_webhook_url" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://tu-n8n.com/webhook/voice-chat" required>';
  echo '<p class="description">La URL completa de tu webhook de n8n que procesará el audio.</p>';
}

function voice_chatbot_jwt_secret_render() {
  $value = get_option('voice_chatbot_jwt_secret', '');
  echo '<input type="password" name="voice_chatbot_jwt_secret" value="' . esc_attr($value) . '" class="regular-text" placeholder="tu_secreto_super_seguro" required>';
  echo '<p class="description">Secreto compartido para firmar los tokens JWT. Debe coincidir con el configurado en n8n.</p>';
  if (empty($value)) {
    $suggested_secret = bin2hex(random_bytes(32));
    echo '<p class="description"><strong>Sugerencia de secreto seguro:</strong> <code style="user-select: all;">' . $suggested_secret . '</code></p>';
  }
}

// Página de configuración
function voice_chatbot_settings_page() {
  ?>
  <div class="wrap">
    <h1>⚙️ Configuración Voice Chatbot</h1>
    <form action="options.php" method="post">
      <?php
      settings_fields('voice_chatbot_settings');
      do_settings_sections('voice-chatbot-settings');
      submit_button('Guardar Configuración');
      ?>
    </form>

    <hr>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
      <h2>📖 Instrucciones de Uso</h2>
      <ol>
        <li><strong>URL del Webhook:</strong> Pega la URL completa de tu webhook de n8n.</li>
        <li><strong>Secreto JWT:</strong> Define un secreto seguro (o copia el sugerido). Este mismo secreto debe estar configurado en n8n para validar las peticiones.</li>
        <li><strong>En n8n:</strong> Configura un nodo que valide el JWT recibido en el header <code>Authorization: Bearer &lt;token&gt;</code></li>
        <li><strong>Shortcode:</strong> Usa <code>[voice_chatbot]</code> en cualquier página para mostrar el chatbot.</li>
      </ol>
      
      <h3>🔐 Estructura del JWT</h3>
      <p>El token JWT incluye los siguientes datos:</p>
      <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">{
  "iss": "<?php echo esc_html(get_site_url()); ?>",
  "iat": timestamp_actual,
  "exp": timestamp_actual + 300 (5 minutos),
  "user_id": ID_del_usuario_o_0_si_no_logueado
}</pre>
      
      <h3>📝 Ejemplo de respuesta esperada de n8n:</h3>
      <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">{
  "audioUrl": "https://tu-servidor.com/ruta/al/archivo.mp3"
}</pre>
    </div>
  </div>
  <?php
}

// ============================================
// Funciones JWT
// ============================================

function voice_chatbot_base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function voice_chatbot_generate_jwt() {
  $secret = get_option('voice_chatbot_jwt_secret', '');
  
  if (empty($secret)) {
    return null;
  }

  $header = json_encode([
    'typ' => 'JWT',
    'alg' => 'HS256'
  ]);

  $current_user_id = get_current_user_id();
  $issued_at = time();
  $expiration = $issued_at + 300; // Token válido por 5 minutos

  $payload = json_encode([
    'iss' => get_site_url(),
    'iat' => $issued_at,
    'exp' => $expiration,
    'user_id' => $current_user_id
  ]);

  $base64_header = voice_chatbot_base64url_encode($header);
  $base64_payload = voice_chatbot_base64url_encode($payload);
  
  $signature = hash_hmac('sha256', $base64_header . "." . $base64_payload, $secret, true);
  $base64_signature = voice_chatbot_base64url_encode($signature);

  return $base64_header . "." . $base64_payload . "." . $base64_signature;
}

// ============================================
// Cargar scripts y estilos
// ============================================

add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style('voice-chatbot-style', plugin_dir_url(__FILE__) . 'style.css');
  wp_enqueue_script('voice-chatbot-js', plugin_dir_url(__FILE__) . 'voice-chatbot.js', [], '2.0', true);

  // Obtener configuración desde la base de datos
  $webhook_url = get_option('voice_chatbot_webhook_url', '');
  $jwt_token = voice_chatbot_generate_jwt();

  // Pasar configuración a JavaScript
  wp_localize_script('voice-chatbot-js', 'voiceChatbotConfig', [
    'webhookUrl' => $webhook_url,
    'jwtToken' => $jwt_token,
    'hasConfig' => !empty($webhook_url) && !empty(get_option('voice_chatbot_jwt_secret', ''))
  ]);
});

// Shortcode para mostrar el componente
add_shortcode('voice_chatbot', function() {
  ob_start();
  ?>
  <div id="voice-chatbot-container">
    <div id="chat-messages"></div>
    
    <div id="voice-controls">
      <div id="status-indicator">
        <div class="pulse-ring"></div>
        <div class="status-dot"></div>
      </div>
      
      <div id="status-text">
        <div class="main-status">Listo para escuchar</div>
        <div class="sub-status">Presiona el botón para hablar</div>
      </div>
      
      <button id="voice-btn" class="ready">
        <svg class="mic-icon" viewBox="0 0 24 24" width="32" height="32">
          <path fill="currentColor" d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
          <path fill="currentColor" d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
        </svg>
        <svg class="stop-icon" viewBox="0 0 24 24" width="32" height="32">
          <rect x="6" y="6" width="12" height="12" fill="currentColor"/>
        </svg>
      </button>
    </div>
    
    <audio id="bot-audio" hidden></audio>
  </div>
  <?php
  return ob_get_clean();
});
