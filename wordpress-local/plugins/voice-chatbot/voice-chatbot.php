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
      
      <h3>📝 Respuesta esperada de n8n:</h3>
      <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">El webhook debe devolver el archivo MP3 directamente (binario)
Content-Type: audio/mpeg

NO devolver JSON, sino el archivo de audio directo.</pre>
      
      <h3>🔄 Flujo de Conversación (Modo Llamada):</h3>
      <ol>
        <li><strong>Activar:</strong> Usuario presiona botón para iniciar modo llamada</li>
        <li><strong>Detección automática:</strong> El sistema detecta cuando hablas</li>
        <li><strong>Grabación automática:</strong> Graba mientras hablas, se detiene con el silencio</li>
        <li><strong>Procesamiento:</strong> Envía a n8n automáticamente (NO puede hablar)</li>
        <li><strong>Respuesta:</strong> Reproduce el audio (puede interrumpir hablando encima)</li>
        <li><strong>Continúa:</strong> Vuelve a escuchar automáticamente</li>
      </ol>
      
      <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 15px;">
        <strong>⚠️ Importante:</strong>
        <ul style="margin: 10px 0 0 20px;">
          <li>El sistema detecta tu voz automáticamente (como ChatGPT en modo llamada)</li>
          <li>Se detiene automáticamente después de 1.5 segundos de silencio</li>
          <li>Durante el procesamiento NO puedes hablar</li>
          <li>Durante la respuesta puedes interrumpir hablando encima</li>
          <li>El webhook debe devolver el archivo MP3 directamente (binario), NO un JSON</li>
        </ul>
      </div>
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
  
  // Generar session_id único por usuario
  $current_user_id = get_current_user_id();
  $session_id = '';
  
  if ($current_user_id > 0) {
    // Usuario logueado: usar su ID de WordPress
    $session_id = 'wp_user_' . $current_user_id;
  }
  // Si no está logueado, JavaScript generará uno en el navegador

  // Pasar configuración a JavaScript
  wp_localize_script('voice-chatbot-js', 'voiceChatbotConfig', [
    'webhookUrl' => $webhook_url,
    'jwtToken' => $jwt_token,
    'sessionId' => $session_id,
    'hasConfig' => !empty($webhook_url) && !empty(get_option('voice_chatbot_jwt_secret', ''))
  ]);
});

// Shortcode para mostrar el componente
add_shortcode('voice_chatbot', function() {
  ob_start();
  ?>
  <div id="voice-chatbot-container">
    
    <!-- Avatar del asistente -->
    <div id="assistant-avatar">
      <div class="avatar-circle">
        <svg viewBox="0 0 24 24" width="48" height="48">
          <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
        </svg>
      </div>
      <div class="avatar-name">Asistente Virtual</div>
    </div>

    <!-- Indicador de estado visual -->
    <div id="call-status">
      <div id="status-indicator">
        <div class="pulse-ring"></div>
        <div class="status-dot"></div>
      </div>
      <div id="status-text">
        <div class="main-status">Presiona para llamar</div>
        <div class="sub-status">Llamada de voz</div>
      </div>
    </div>

    <!-- Tiempo de llamada -->
    <div id="call-timer" style="display: none;">
      <span id="timer-display">00:00</span>
    </div>

    <!-- Boton de llamada grande -->
    <div id="call-button-container">
      <button id="voice-btn" class="ready">
        <svg class="mic-icon" viewBox="0 0 24 24" width="40" height="40">
          <path fill="currentColor" d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
        </svg>
        <svg class="phone-icon" viewBox="0 0 24 24" width="40" height="40" style="display: none;">
          <path fill="currentColor" d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08c-.18-.17-.29-.42-.29-.7 0-.28.11-.53.29-.71C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.67c.18.18.29.43.29.71 0 .28-.11.53-.29.71l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28-.79-.74-1.68-1.36-2.66-1.85-.33-.16-.56-.5-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/>
        </svg>
      </button>
      <div class="button-label">Llamar</div>
    </div>

    <audio id="bot-audio" hidden></audio>
  </div>
  <?php
  return ob_get_clean();
});
