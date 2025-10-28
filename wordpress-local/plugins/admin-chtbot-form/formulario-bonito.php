<?php
/**
 * Plugin Name: Formulario Bonito
 * Description: Un formulario elegante hecho a mano por Jaycom 😎 con integración a n8n - Soporta PDFs, DOCX, TXT e imágenes
 * Version: 2.1
 * Author: Camilo Orejuela
 */

if (!defined('ABSPATH')) exit; // Seguridad básica

// ============================================
// MENÚ DE ADMINISTRACIÓN
// ============================================

// Agregar menú en el panel de administración
function fb_agregar_menu_admin() {
    add_menu_page(
        'Configuración Formulario',
        'Formulario Bonito',
        'manage_options',
        'formulario-bonito-config',
        'fb_pagina_configuracion',
        'dashicons-admin-generic',
        100
    );
}
add_action('admin_menu', 'fb_agregar_menu_admin');

// Página de configuración
function fb_pagina_configuracion() {
    ?>
    <div class="wrap">
        <h1>⚙️ Configuración del Formulario Bonito</h1>
        
        <div style="background: #fff; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">ℹ️ ¿Para qué sirve esta configuración?</h3>
            <p>Este plugin permite a los usuarios subir archivos que serán enviados automáticamente a tu webhook de n8n para alimentar tu sistema RAG (Retrieval-Augmented Generation) o cualquier otro sistema de procesamiento de documentos.</p>
            <p><strong>Los datos configurados aquí son utilizados para:</strong></p>
            <ul>
                <li><strong>JWT Secret:</strong> Autenticar las peticiones entre WordPress y n8n. Este debe ser el mismo secreto que configures en el nodo de autenticación del webhook en n8n.</li>
                <li><strong>Webhook URL:</strong> Definir el destino donde se enviarán los archivos. Esta es la URL que te proporciona n8n al crear un nodo Webhook.</li>
            </ul>
            <p style="color: #d63638;"><strong>⚠️ Importante:</strong> El JWT Secret debe ser exactamente el mismo que configures en n8n para que la autenticación funcione correctamente.</p>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('fb_config_group');
            do_settings_sections('fb_config_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">🔐 JWT Secret</th>
                    <td>
                        <input type="password" name="fb_jwt_secret" value="<?php echo esc_attr(get_option('fb_jwt_secret')); ?>" class="regular-text" placeholder="Ejemplo: mi_clave_super_segura_2024" />
                        <p class="description">
                            <strong>Qué es:</strong> Una clave secreta compartida entre WordPress y n8n para autenticar las peticiones.<br>
                            <strong>Importante:</strong> Este debe ser <u>exactamente el mismo secreto</u> que configures en el nodo de webhook de n8n (en la sección de autenticación/Header Auth).<br>
                            <strong>Cómo funciona:</strong> WordPress genera un token JWT con esta clave y lo envía en el header <code>Authorization: Bearer {token}</code>. n8n valida el token usando la misma clave.<br>
                            <strong>Recomendación:</strong> Usa una combinación de letras, números y símbolos (mínimo 20 caracteres). Ejemplo: <code>Jc2024_RAG_Secret_xyz789!</code>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">🌐 Webhook URL (n8n)</th>
                    <td>
                        <input type="url" name="fb_webhook_url" value="<?php echo esc_attr(get_option('fb_webhook_url')); ?>" class="regular-text" placeholder="https://tu-n8n.com/webhook/upload-rag" />
                        <p class="description">
                            <strong>Qué es:</strong> La URL del webhook de n8n que recibirá los archivos subidos.<br>
                            <strong>Cómo obtenerla:</strong> En tu workflow de n8n, en tu nodo "Webhook", actívalo y copia la URL de producción que te muestra.<br>
                            <strong>Formato:</strong> <code>https://tu-dominio.com/webhook/nombre-del-webhook</code>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0;">
                <p style="margin: 0;"><strong>💡 Tip:</strong> Después de guardar, configura la autenticación en tu webhook de n8n usando el mismo JWT Secret. Luego prueba el formulario para verificar que los archivos llegan correctamente.</p>
            </div>
            
            <?php submit_button('💾 Guardar Configuración'); ?>
        </form>
    </div>
    <?php
}

// Registrar configuraciones
function fb_registrar_configuraciones() {
    register_setting('fb_config_group', 'fb_jwt_secret');
    register_setting('fb_config_group', 'fb_webhook_url');
}
add_action('admin_init', 'fb_registrar_configuraciones');

// ============================================
// FUNCIONES JWT
// ============================================

function fb_detectar_mime_type($filename, $filepath) {
    // Primero intentar con finfo si está disponible
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        if ($mime_type && $mime_type !== 'application/octet-stream') {
            return $mime_type;
        }
    }
    
    // Si finfo no funciona, detectar por extensión
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Solo los tipos de archivo permitidos para el sistema RAG
    $mime_types = [
        // Documentos
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        
        // Imágenes
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];
    
    if (isset($mime_types[$extension])) {
        return $mime_types[$extension];
    }
    
    // Fallback
    return 'application/octet-stream';
}

function fb_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function fb_generar_jwt() {
    $secret = get_option('fb_jwt_secret', '');
    
    if (empty($secret)) {
        return false;
    }
    
    // Header
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);
    
    // Payload
    $payload = json_encode([
        'iat' => time(),
        'exp' => time() + 3600, // Expira en 1 hora
        'plugin' => 'formulario-bonito'
    ]);
    
    // Encode Header
    $base64UrlHeader = fb_base64url_encode($header);
    // Encode Payload
    $base64UrlPayload = fb_base64url_encode($payload);
    
    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    // Encode Signature
    $base64UrlSignature = fb_base64url_encode($signature);
    
    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    return $jwt;
}

// ============================================
// SHORTCODE Y FORMULARIO
// ============================================

// Registrar shortcode para mostrar el formulario
function fb_mostrar_formulario() {
    ob_start();
    ?>
    <form id="fb-form" class="fb-form" enctype="multipart/form-data">
        <h2>📤 Subir Archivos al RAG</h2>
        
        <div class="fb-group">
            <label for="archivos">Selecciona los archivos a procesar</label>
            <input type="file" id="archivos" name="archivos[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg" required>
            <p class="description">Puedes seleccionar múltiples archivos. Formatos aceptados: PDF, DOCX, TXT e imágenes (JPG, PNG, GIF, etc.).</p>
        </div>

        <div class="fb-privacy-notice">
            <h4>🔒 Información sobre Privacidad y Tratamiento de Datos</h4>
            <p><strong>⚠️ IMPORTANTE - Lee antes de enviar:</strong></p>
            <ul>
                <li><strong>Contenido de los archivos:</strong> Todo el contenido de los archivos que subas (texto, imágenes, información, datos) será procesado y almacenado en el sistema RAG (Retrieval-Augmented Generation).</li>
                <li><strong>⚠️ NO envíes datos personales sensibles:</strong> Evita subir archivos que contengan información personal sensible como números de identificación, datos bancarios, contraseñas, información médica confidencial o cualquier dato que no desees que sea procesado.</li>
                <li><strong>Uso del contenido:</strong> El contenido de los archivos será utilizado para entrenar y mejorar el sistema de inteligencia artificial, generación de respuestas y contenido contextual.</li>
                <li><strong>Análisis automático:</strong> Los archivos serán analizados automáticamente para extraer texto, entidades, conceptos y relaciones que alimentarán la base de conocimiento del sistema.</li>
                <li><strong>Seguridad:</strong> La transmisión se realiza mediante conexión segura con autenticación JWT.</li>
            </ul>
            <p style="font-size: 13px; color: #d63638; font-weight: 600; margin-top: 15px; padding: 10px; background: #fff8e5; border-left: 4px solid #d63638;">
                ⚠️ Al hacer clic en "Enviar archivos", confirmas que:<br>
                • Has leído y comprendes esta política<br>
                • El contenido de los archivos NO contiene datos personales sensibles<br>
                • Autorizas el procesamiento del contenido para alimentar el sistema RAG
            </p>
        </div>
        
        <button type="submit" class="fb-btn">📤 Enviar Archivos</button>
        <div id="fb-respuesta"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_bonito', 'fb_mostrar_formulario');


// Agregar estilos
function fb_agregar_estilos() {
    wp_enqueue_style('fb-estilos', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('fb-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], false, true);
    wp_localize_script('fb-script', 'fb_ajax', [
        'url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'fb_agregar_estilos');

// ============================================
// ACCIÓN AJAX Y ENVÍO A N8N
// ============================================

// Acción AJAX
function fb_enviar_formulario() {
    // Verificar que se hayan enviado archivos
    if (empty($_FILES['archivos']['name'][0])) {
        wp_send_json_error('Por favor selecciona al menos un archivo.');
        return;
    }
    
    // Definir extensiones permitidas
    $extensiones_permitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    
    // Validar extensiones de archivos
    $files = $_FILES['archivos'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        $file_name = $files['name'][$i];
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensiones_permitidas)) {
            wp_send_json_error('❌ El archivo "' . $file_name . '" no es válido. Solo se permiten: PDF, DOC, DOCX, TXT e imágenes (JPG, PNG, GIF, BMP, WEBP, SVG).');
            return;
        }
    }
    
    // Obtener configuraciones
    $webhook_url = get_option('fb_webhook_url', '');
    
    if (empty($webhook_url)) {
        wp_send_json_error('Webhook no configurado. Por favor contacta al administrador del sitio.');
        return;
    }
    
    // Generar JWT
    $jwt = fb_generar_jwt();
    
    if (!$jwt) {
        wp_send_json_error('Sistema de autenticación no configurado. Por favor contacta al administrador del sitio.');
        return;
    }
    
    // Preparar datos para enviar
    $boundary = wp_generate_password(24, false);
    $payload = '';
    
    // Agregar metadata adicional
    $metadata = [
        'timestamp' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    foreach ($metadata as $name => $value) {
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
        $payload .= $value . "\r\n";
    }
    
    // Procesar archivos
    $files = $_FILES['archivos'];
    $file_count = count($files['name']);
    $archivos_procesados = 0;
    $archivos_info = []; // Para debugging
    $errores_detallados = []; // Para reportar errores específicos
    
    for ($i = 0; $i < $file_count; $i++) {
        $file_name = $files['name'][$i];
        $file_error = $files['error'][$i];
        
        // Log detallado del estado de cada archivo
        error_log("Procesando archivo: {$file_name}, Error code: {$file_error}");
        
        if ($file_error === UPLOAD_ERR_OK) {
            // Verificar que el archivo temporal existe
            if (!file_exists($files['tmp_name'][$i])) {
                $errores_detallados[] = "El archivo temporal de '{$file_name}' no existe.";
                error_log("Error: Archivo temporal no existe para {$file_name}");
                continue;
            }
            
            $file_content = file_get_contents($files['tmp_name'][$i]);
            
            // Verificar que se pudo leer el contenido
            if ($file_content === false) {
                $errores_detallados[] = "No se pudo leer el contenido de '{$file_name}'.";
                error_log("Error: No se pudo leer contenido de {$file_name}");
                continue;
            }
            
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];
            
            // Detectar MIME type correcto si no está presente o es genérico
            if (empty($file_type) || $file_type === 'application/octet-stream') {
                $file_type = fb_detectar_mime_type($file_name, $files['tmp_name'][$i]);
            }
            
            // Guardar info para log (opcional, para debugging)
            $archivos_info[] = [
                'nombre' => $file_name,
                'tipo' => $file_type,
                'tamaño' => $file_size
            ];
            
            error_log("Archivo procesado OK: {$file_name} - Tipo: {$file_type} - Tamaño: {$file_size}");
            
            $payload .= '--' . $boundary . "\r\n";
            $payload .= 'Content-Disposition: form-data; name="archivos[]"; filename="' . $file_name . '"' . "\r\n";
            $payload .= 'Content-Type: ' . $file_type . "\r\n\r\n";
            $payload .= $file_content . "\r\n";
            
            $archivos_procesados++;
        } else {
            // Log de errores de upload
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
            ];
            
            $error_msg = isset($error_messages[$file_error]) 
                ? $error_messages[$file_error] 
                : 'Error desconocido (código: ' . $file_error . ')';
            
            $errores_detallados[] = "'{$file_name}': {$error_msg}";
            error_log("Error subiendo archivo {$file_name}: {$error_msg}");
        }
    }
    
    if ($archivos_procesados === 0) {
        $mensaje_error = 'No se pudieron procesar los archivos.';
        if (!empty($errores_detallados)) {
            $mensaje_error .= '<br><br><strong>Detalles:</strong><br>' . implode('<br>', $errores_detallados);
        }
        error_log("ERROR FINAL: No se procesó ningún archivo. Total intentados: {$file_count}");
        wp_send_json_error($mensaje_error);
        return;
    }
    
    $payload .= '--' . $boundary . '--';
    
    // Enviar al webhook de n8n
    $response = wp_remote_post($webhook_url, [
        'timeout' => 60,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary
        ],
        'body' => $payload
    ]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        
        // Mensajes de error más descriptivos
        if (strpos($error_message, 'Could not connect') !== false || strpos($error_message, 'Failed to connect') !== false) {
            wp_send_json_error('❌ No se puede conectar con el webhook de n8n. Verifica que:<br>
                • n8n esté ejecutándose<br>
                • El webhook esté activo en n8n<br>
                • La URL del webhook sea correcta<br>
                • Si WordPress está en Docker, usa la URL correcta (no localhost)<br><br>
                <small>Error técnico: ' . $error_message . '</small>');
        } elseif (strpos($error_message, 'timed out') !== false) {
            wp_send_json_error('⏱️ Tiempo de espera agotado. El webhook de n8n no respondió a tiempo. Verifica que esté funcionando correctamente.');
        } else {
            wp_send_json_error('❌ Error al enviar archivos: ' . $error_message);
        }
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code >= 200 && $response_code < 300) {
        $mensaje_plural = $archivos_procesados === 1 ? 'archivo ha' : 'archivos han';
        wp_send_json_success("✅ ¡Éxito! {$archivos_procesados} {$mensaje_plural} sido enviado(s) y procesado(s) correctamente.");
    } else {
        // Mensajes específicos por código de error
        if ($response_code == 401 || $response_code == 403) {
            wp_send_json_error('🔒 Error de autenticación (código ' . $response_code . '). Verifica que el JWT Secret configurado aquí sea exactamente el mismo que en n8n.');
        } elseif ($response_code == 404) {
            wp_send_json_error('🔍 Webhook no encontrado (404). Verifica que la URL del webhook sea correcta.');
        } elseif ($response_code >= 500) {
            wp_send_json_error('⚠️ Error del servidor de n8n (' . $response_code . '). Revisa los logs de n8n.<br><small>' . $response_body . '</small>');
        } else {
            wp_send_json_error('❌ Error del servidor: código ' . $response_code . '<br><small>' . $response_body . '</small>');
        }
    }
}
add_action('wp_ajax_fb_enviar_formulario', 'fb_enviar_formulario');
add_action('wp_ajax_nopriv_fb_enviar_formulario', 'fb_enviar_formulario');

