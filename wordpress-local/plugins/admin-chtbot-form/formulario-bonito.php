<?php
/**
 * Plugin Name: Formulario Bonito
 * Description: Un formulario elegante hecho a mano por Jaycom 😎 con integración a n8n - Soporta PDFs, DOCX, TXT e imágenes. Incluye gestión de documentos RAG.
 * Version: 2.2
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
    
    // Agregar submenú para gestión de documentos
    add_submenu_page(
        'formulario-bonito-config',
        'Gestión de Documentos',
        '📄 Documentos RAG',
        'manage_options',
        'formulario-bonito-documentos',
        'fb_pagina_documentos'
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
                <tr valign="top">
                    <th scope="row">📋 Webhook URL Listar Documentos (GET)</th>
                    <td>
                        <input type="url" name="fb_webhook_list_url" value="<?php echo esc_attr(get_option('fb_webhook_list_url')); ?>" class="regular-text" placeholder="https://tu-n8n.com/webhook/list-rag-documents" />
                        <p class="description">
                            <strong>Qué es:</strong> La URL del webhook que devuelve la lista de documentos del RAG.<br>
                            <strong>Método:</strong> GET<br>
                            <strong>Respuesta esperada:</strong> <code>[{"file_name": "documento.pdf"}]</code>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">🗑️ Webhook URL Eliminar Documentos (DELETE)</th>
                    <td>
                        <input type="url" name="fb_webhook_delete_url" value="<?php echo esc_attr(get_option('fb_webhook_delete_url')); ?>" class="regular-text" placeholder="https://tu-n8n.com/webhook/delete-rag-document" />
                        <p class="description">
                            <strong>Qué es:</strong> La URL del webhook para eliminar documentos del RAG.<br>
                            <strong>Método:</strong> DELETE<br>
                            <strong>Body requerido:</strong> <code>{"file_name": "documento.pdf"}</code>
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
    register_setting('fb_config_group', 'fb_webhook_list_url');
    register_setting('fb_config_group', 'fb_webhook_delete_url');
}
add_action('admin_init', 'fb_registrar_configuraciones');

// Página de gestión de documentos
function fb_pagina_documentos() {
    ?>
    <div class="wrap">
        <h1>📄 Gestión de Documentos RAG</h1>
        
        <div style="background: #fff; border-left: 4px solid #00a32a; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">ℹ️ Acerca de esta sección</h3>
            <p>Aquí puedes ver todos los documentos que están actualmente almacenados en tu sistema RAG y eliminar aquellos que ya no necesites.</p>
            <p><strong>Funciones disponibles:</strong></p>
            <ul>
                <li><strong>Listar documentos:</strong> Ver todos los archivos almacenados en el RAG</li>
                <li><strong>Eliminar documentos:</strong> Borrar documentos específicos del sistema</li>
            </ul>
        </div>

        <button id="fb-refresh-docs" class="button button-primary" style="margin-bottom: 20px;">
            🔄 Actualizar Lista
        </button>
        
        <div id="fb-documentos-container">
            <p>Cargando documentos...</p>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Función para cargar documentos
        function cargarDocumentos() {
            $('#fb-documentos-container').html('<p>⏳ Cargando documentos...</p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fb_listar_documentos'
                },
                success: function(response) {
                    if (response.success) {
                        $('#fb-documentos-container').html(response.data.html);
                    } else {
                        $('#fb-documentos-container').html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#fb-documentos-container').html('<div class="notice notice-error"><p>❌ Error al cargar documentos: ' + error + '</p></div>');
                }
            });
        }
        
        // Cargar documentos al inicio
        cargarDocumentos();
        
        // Botón de refrescar
        $('#fb-refresh-docs').on('click', function() {
            cargarDocumentos();
        });
        
        // Función para eliminar documento (delegada)
        $(document).on('click', '.fb-delete-doc', function() {
            var fileName = $(this).data('filename');
            var $button = $(this);
            
            if (!confirm('¿Estás seguro de que deseas eliminar "' + fileName + '"?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            
            $button.prop('disabled', true).text('⏳ Eliminando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fb_eliminar_documento',
                    file_name: fileName
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        cargarDocumentos(); // Recargar la lista
                    } else {
                        alert('❌ ' + response.data);
                        $button.prop('disabled', false).text('🗑️ Eliminar');
                    }
                },
                error: function(xhr, status, error) {
                    alert('❌ Error al eliminar el documento: ' + error);
                    $button.prop('disabled', false).text('🗑️ Eliminar');
                }
            });
        });
    });
    </script>
    
    <style>
    .fb-docs-table {
        width: 100%;
        background: #fff;
        border-collapse: collapse;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .fb-docs-table th {
        background: #f0f0f1;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #c3c4c7;
    }
    
    .fb-docs-table td {
        padding: 12px;
        border-bottom: 1px solid #dcdcde;
    }
    
    .fb-docs-table tr:hover {
        background: #f6f7f7;
    }
    
    .fb-delete-doc {
        background: #d63638;
        color: white;
        border: none;
        padding: 6px 12px;
        cursor: pointer;
        border-radius: 3px;
        font-size: 13px;
    }
    
    .fb-delete-doc:hover {
        background: #b32d2e;
    }
    
    .fb-delete-doc:disabled {
        background: #c3c4c7;
        cursor: not-allowed;
    }
    
    .fb-no-docs {
        padding: 40px;
        text-align: center;
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 4px;
    }
    
    .fb-no-docs-icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
    </style>
    <?php
}

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

// Registrar shortcode para mostrar el formulario CON gestión de documentos
function fb_mostrar_formulario() {
    ob_start();
    ?>
    <div class="fb-contenedor-principal">
        <!-- HEADER DEL PLUGIN -->
        <div class="fb-header">
            <div class="fb-header-content">
                <h1 class="fb-header-title">
                    <span class="fb-icon">📚</span>
                    Sistema de Gestión RAG
                </h1>
                <p class="fb-header-subtitle">Administra tus documentos de forma inteligente</p>
            </div>
        </div>

        <!-- SISTEMA DE PESTAÑAS -->
        <div class="fb-tabs-container">
            <div class="fb-tabs-header">
                <button class="fb-tab-btn active" data-tab="upload">
                    <span class="fb-tab-icon">📤</span>
                    Subir Archivos
                </button>
                <button class="fb-tab-btn" data-tab="manage">
                    <span class="fb-tab-icon">📂</span>
                    Mis Documentos
                    <span class="fb-tab-badge" id="fb-docs-count">0</span>
                </button>
            </div>

            <div class="fb-tabs-content">
                <!-- TAB: SUBIR ARCHIVOS -->
                <div class="fb-tab-panel active" id="tab-upload">
                    <div class="fb-card">
                        <div class="fb-card-header">
                            <h2 class="fb-card-title">📤 Subir Nuevos Documentos</h2>
                            <p class="fb-card-description">Selecciona uno o más archivos para agregar al sistema RAG</p>
                        </div>
                        
                        <div class="fb-card-body">
                            <form id="fb-form" class="fb-form" enctype="multipart/form-data">
                                <div class="fb-upload-area">
                                    <div class="fb-upload-icon">📄</div>
                                    <div class="fb-upload-text">
                                        <label for="archivos" class="fb-upload-label">
                                            <strong>Haz clic para seleccionar archivos</strong>
                                            <span>o arrastra y suelta aquí</span>
                                        </label>
                                        <input type="file" id="archivos" name="archivos[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                                    </div>
                                    <p class="fb-upload-formats">
                                        <strong>Formatos aceptados:</strong> PDF, DOCX, TXT, JPG, PNG
                                    </p>
                                </div>

                                <div class="fb-selected-files" id="fb-selected-files" style="display:none;">
                                    <h4>0 archivos seleccionados</h4>
                                    <ul id="fb-files-list"></ul>
                                </div>

                                <div class="fb-collapsible-section">
                                    <button type="button" class="fb-collapsible-trigger">
                                        🔒 Política de Privacidad y Tratamiento de Datos
                                        <span class="fb-collapsible-arrow">▼</span>
                                    </button>
                                    <div class="fb-collapsible-content">
                                        <div class="fb-privacy-notice">
                                            <p><strong>⚠️ IMPORTANTE - Lee antes de enviar:</strong></p>
                                            <ul>
                                                <li><strong>Contenido de los archivos:</strong> Todo el contenido será procesado y almacenado en el sistema RAG.</li>
                                                <li><strong>⚠️ NO envíes datos sensibles:</strong> Evita información personal como IDs, datos bancarios, contraseñas o información médica.</li>
                                                <li><strong>Uso del contenido:</strong> Los archivos serán utilizados para entrenar y mejorar el sistema de IA.</li>
                                                <li><strong>Análisis automático:</strong> Se extraerá texto, entidades y relaciones automáticamente.</li>
                                                <li><strong>Seguridad:</strong> Transmisión segura con autenticación JWT.</li>
                                            </ul>
                                            <div class="fb-privacy-alert">
                                                ⚠️ Al enviar archivos, confirmas que has leído esta política y que el contenido NO contiene datos personales sensibles.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="fb-btn fb-btn-primary">
                                    <span class="fb-btn-icon">📤</span>
                                    Enviar Archivos
                                </button>
                                
                                <div id="fb-respuesta"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB: MIS DOCUMENTOS -->
                <div class="fb-tab-panel" id="tab-manage">
                    <div class="fb-card">
                        <div class="fb-card-header">
                            <h2 class="fb-card-title">📂 Documentos Almacenados</h2>
                            <p class="fb-card-description">Visualiza y administra tus documentos en el sistema RAG</p>
                        </div>
                        
                        <div class="fb-card-body">
                            <div class="fb-toolbar">
                                <button id="fb-refresh-docs" class="fb-btn fb-btn-secondary">
                                    <span class="fb-btn-icon">🔄</span>
                                    Actualizar Lista
                                </button>
                            </div>
                            
                            <div id="fb-documentos-container">
                                <div class="fb-loading-state">
                                    <div class="fb-spinner"></div>
                                    <p>Cargando documentos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    $extensiones_permitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
    
    // Validar extensiones de archivos
    $files = $_FILES['archivos'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        $file_name = $files['name'][$i];
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensiones_permitidas)) {
            wp_send_json_error('❌ El archivo "' . $file_name . '" no es válido. Solo se permiten: PDF, DOCX, TXT, JPG, PNG.');
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

// ============================================
// GESTIÓN DE DOCUMENTOS - LISTAR
// ============================================

function fb_listar_documentos() {
    // Nota: Permitimos acceso sin verificar permisos para que funcione en frontend
    // Si quieres restringir, descomenta la siguiente línea:
    // if (!is_user_logged_in()) {
    //     wp_send_json_error('Debes iniciar sesión para ver los documentos.');
    //     return;
    // }
    
    // Obtener configuración
    $webhook_list_url = get_option('fb_webhook_list_url', '');
    
    if (empty($webhook_list_url)) {
        wp_send_json_error('La URL del webhook de listado no está configurada. Por favor configúrala en la página de configuración.');
        return;
    }
    
    // Generar JWT
    $jwt = fb_generar_jwt();
    
    if (!$jwt) {
        wp_send_json_error('Sistema de autenticación no configurado. Por favor configura el JWT Secret.');
        return;
    }
    
    // Hacer petición GET al webhook
    $response = wp_remote_get($webhook_list_url, [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt
        ]
    ]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_send_json_error('Error al conectar con el webhook: ' . $error_message);
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code >= 200 && $response_code < 300) {
        $documentos = json_decode($response_body, true);
        
        if (!is_array($documentos)) {
            wp_send_json_error('Respuesta inválida del webhook. Se esperaba un array JSON.');
            return;
        }
        
        // Generar HTML de la tabla
        $html = '';
        
        if (empty($documentos)) {
            $html = '<div class="fb-no-docs">
                        <div class="fb-no-docs-icon">📭</div>
                        <h2>No hay documentos</h2>
                        <p>Aún no se han subido documentos al sistema RAG.</p>
                    </div>';
        } else {
            $html = '<table class="fb-docs-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>📄 Nombre del Archivo</th>
                                <th style="width: 150px; text-align: center;">🛠️ Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            $contador = 1;
            foreach ($documentos as $doc) {
                $file_name = isset($doc['file_name']) ? esc_html($doc['file_name']) : 'Sin nombre';
                $file_name_attr = isset($doc['file_name']) ? esc_attr($doc['file_name']) : '';
                
                $html .= '<tr>
                            <td>' . $contador . '</td>
                            <td><strong>' . $file_name . '</strong></td>
                            <td style="text-align: center;">
                                <button class="fb-delete-doc" data-filename="' . $file_name_attr . '">
                                    🗑️ Eliminar
                                </button>
                            </td>
                          </tr>';
                $contador++;
            }
            
            $html .= '</tbody></table>';
            $html .= '<p style="margin-top: 15px; color: #666;">Total de documentos: <strong>' . count($documentos) . '</strong></p>';
        }
        
        wp_send_json_success([
            'html' => $html,
            'count' => count($documentos)
        ]);
    } else {
        if ($response_code == 401 || $response_code == 403) {
            wp_send_json_error('Error de autenticación. Verifica que el JWT Secret sea correcto.');
        } elseif ($response_code == 404) {
            wp_send_json_error('Webhook no encontrado (404). Verifica que la URL sea correcta.');
        } else {
            wp_send_json_error('Error del servidor: código ' . $response_code . ' - ' . $response_body);
        }
    }
}
add_action('wp_ajax_fb_listar_documentos', 'fb_listar_documentos');
add_action('wp_ajax_nopriv_fb_listar_documentos', 'fb_listar_documentos');

// ============================================
// GESTIÓN DE DOCUMENTOS - ELIMINAR
// ============================================

function fb_eliminar_documento() {
    // Nota: Permitimos acceso sin verificar permisos para que funcione en frontend
    // Si quieres restringir, descomenta la siguiente línea:
    // if (!is_user_logged_in()) {
    //     wp_send_json_error('Debes iniciar sesión para eliminar documentos.');
    //     return;
    // }
    
    // Obtener el nombre del archivo
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
    
    if (empty($file_name)) {
        wp_send_json_error('No se especificó el nombre del archivo.');
        return;
    }
    
    // Obtener configuración
    $webhook_delete_url = get_option('fb_webhook_delete_url', '');
    
    if (empty($webhook_delete_url)) {
        wp_send_json_error('La URL del webhook de eliminación no está configurada. Por favor configúrala en la página de configuración.');
        return;
    }
    
    // Generar JWT
    $jwt = fb_generar_jwt();
    
    if (!$jwt) {
        wp_send_json_error('Sistema de autenticación no configurado. Por favor configura el JWT Secret.');
        return;
    }
    
    // Preparar el body JSON
    $body = json_encode([
        'doc' => $file_name
    ]);
    
    // Hacer petición DELETE al webhook
    $response = wp_remote_request($webhook_delete_url, [
        'method' => 'DELETE',
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt,
            'Content-Type' => 'application/json'
        ],
        'body' => $body
    ]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_send_json_error('Error al conectar con el webhook: ' . $error_message);
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code >= 200 && $response_code < 300) {
        wp_send_json_success('Documento "' . $file_name . '" eliminado correctamente.');
    } else {
        if ($response_code == 401 || $response_code == 403) {
            wp_send_json_error('Error de autenticación. Verifica que el JWT Secret sea correcto.');
        } elseif ($response_code == 404) {
            wp_send_json_error('Documento no encontrado o webhook no disponible.');
        } else {
            wp_send_json_error('Error del servidor: código ' . $response_code . ' - ' . $response_body);
        }
    }
}
add_action('wp_ajax_fb_eliminar_documento', 'fb_eliminar_documento');
add_action('wp_ajax_nopriv_fb_eliminar_documento', 'fb_eliminar_documento');

