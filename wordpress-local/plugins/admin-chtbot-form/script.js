jQuery(document).ready(function($) {
  
  // ============================================
  // SISTEMA DE PESTAÑAS
  // ============================================
  
  $(document).on('click', '.fb-tab-btn', function() {
    var tab = $(this).data('tab');
    
    // Actualizar botones activos
    $('.fb-tab-btn').removeClass('active');
    $(this).addClass('active');
    
    // Mostrar panel correspondiente
    $('.fb-tab-panel').removeClass('active');
    $('#tab-' + tab).addClass('active');
    
    // Si es la pestaña de documentos, cargar la lista
    if (tab === 'manage') {
      cargarDocumentos();
    }
  });
  
  // ============================================
  // COLLAPSIBLE (PRIVACIDAD)
  // ============================================
  
  $(document).on('click', '.fb-collapsible-trigger', function() {
    $(this).toggleClass('active');
    $(this).next('.fb-collapsible-content').slideToggle(300);
  });
  
  // ============================================
  // PREVIEW DE ARCHIVOS SELECCIONADOS
  // ============================================
  
  // Array para almacenar archivos seleccionados
  var archivosSeleccionados = [];
  
  function actualizarVistaArchivos() {
    var $selectedFiles = $('#fb-selected-files');
    var $filesList = $('#fb-files-list');
    
    if (archivosSeleccionados.length > 0) {
      $filesList.empty();
      
      for (var i = 0; i < archivosSeleccionados.length; i++) {
        var file = archivosSeleccionados[i];
        var fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
        var fileIcon = getFileIcon(file.name);
        
        $filesList.append(
          '<li class="fb-file-item" data-index="' + i + '">' +
            '<span class="fb-file-icon">' + fileIcon + '</span>' +
            '<span class="fb-file-info">' +
              '<strong>' + file.name + '</strong>' +
              '<span class="fb-file-size">' + fileSize + ' MB</span>' +
            '</span>' +
            '<button type="button" class="fb-remove-file" data-index="' + i + '" title="Eliminar archivo">' +
              '<span>❌</span>' +
            '</button>' +
          '</li>'
        );
      }
      
      $selectedFiles.slideDown(300);
    } else {
      $selectedFiles.slideUp(300);
    }
    
    // Actualizar contador
    actualizarContador();
  }
  
  function actualizarContador() {
    var count = archivosSeleccionados.length;
    var texto = count === 1 ? count + ' archivo seleccionado' : count + ' archivos seleccionados';
    $('#fb-selected-files h4').text(texto);
  }
  
  function agregarArchivos(files) {
    // Convertir FileList a Array y agregar a los archivos seleccionados
    for (var i = 0; i < files.length; i++) {
      var file = files[i];
      
      // Verificar si el archivo ya está en la lista (por nombre y tamaño)
      var existe = archivosSeleccionados.some(function(f) {
        return f.name === file.name && f.size === file.size;
      });
      
      if (!existe) {
        archivosSeleccionados.push(file);
      }
    }
    
    actualizarVistaArchivos();
  }
  
  // Evento change del input file
  $(document).on('change', '#archivos', function() {
    if (this.files.length > 0) {
      agregarArchivos(this.files);
      // Resetear el input para poder seleccionar los mismos archivos de nuevo si se desea
      this.value = '';
    }
  });
  
  // Evento para eliminar archivo individual
  $(document).on('click', '.fb-remove-file', function() {
    var index = $(this).data('index');
    var fileName = archivosSeleccionados[index].name;
    
    if (confirm('¿Eliminar "' + fileName + '" de la lista?')) {
      archivosSeleccionados.splice(index, 1);
      actualizarVistaArchivos();
    }
  });
  
  // ============================================
  // DRAG AND DROP
  // ============================================
  
  // Prevenir comportamiento por defecto
  $(document).on('drag dragstart dragend dragover dragenter dragleave drop', '.fb-upload-area', function(e) {
    e.preventDefault();
    e.stopPropagation();
  });
  
  // Agregar clase cuando se arrastra sobre el área
  $(document).on('dragover dragenter', '.fb-upload-area', function() {
    $(this).addClass('fb-dragover');
  });
  
  // Remover clase cuando se sale del área
  $(document).on('dragleave dragend drop', '.fb-upload-area', function() {
    $(this).removeClass('fb-dragover');
  });
  
  // Manejar el drop
  $(document).on('drop', '.fb-upload-area', function(e) {
    var files = e.originalEvent.dataTransfer.files;
    
    if (files.length > 0) {
      agregarArchivos(files);
    }
  });
  
  // Función helper para iconos de archivo
  function getFileIcon(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var icons = {
      'pdf': '📕',
      'doc': '📘',
      'docx': '📘',
      'txt': '📄',
      'jpg': '🖼️',
      'jpeg': '🖼️',
      'png': '🖼️'
    };
    return icons[ext] || '📎';
  }
  
  // ============================================
  // FUNCIÓN GLOBAL PARA CARGAR DOCUMENTOS
  // ============================================
  
  function cargarDocumentos() {
    $('#fb-documentos-container').html(
      '<div class="fb-loading-state">' +
        '<div class="fb-spinner"></div>' +
        '<p>Cargando documentos...</p>' +
      '</div>'
    );
    
    $.ajax({
      url: fb_ajax.url,
      type: 'POST',
      data: {
        action: 'fb_listar_documentos'
      },
      success: function(response) {
        if (response.success) {
          $('#fb-documentos-container').html(response.data.html);
          
          // Actualizar contador en el badge
          var count = response.data.count || 0;
          $('#fb-docs-count').text(count);
        } else {
          $('#fb-documentos-container').html(
            '<div class="fb-error-state">' +
              '<div class="fb-error-icon">❌</div>' +
              '<p>' + response.data + '</p>' +
            '</div>'
          );
        }
      },
      error: function(xhr, status, error) {
        $('#fb-documentos-container').html(
          '<div class="fb-error-state">' +
            '<div class="fb-error-icon">❌</div>' +
            '<p>Error al cargar documentos: ' + error + '</p>' +
          '</div>'
        );
      }
    });
  }
  
  // ============================================
  // FORMULARIO DE SUBIDA DE ARCHIVOS
  // ============================================
  $(document).on('submit', '#fb-form', function(e) {
    e.preventDefault();

    // Verificar que se hayan seleccionado archivos
    if (archivosSeleccionados.length === 0) {
      $('#fb-respuesta').html(
        '<div class="fb-alert fb-alert-error">' +
          '<span class="fb-alert-icon">⚠️</span>' +
          '<div class="fb-alert-content">Por favor selecciona al menos un archivo.</div>' +
        '</div>'
      );
      return;
    }

    // Crear FormData para enviar archivos
    var formData = new FormData();
    formData.append('action', 'fb_enviar_formulario');

    // Agregar archivos del array
    for (var i = 0; i < archivosSeleccionados.length; i++) {
      formData.append('archivos[]', archivosSeleccionados[i]);
    }

    // Mostrar mensaje de carga
    var archivoTexto = archivosSeleccionados.length === 1 ? 'archivo' : 'archivos';
    $('#fb-respuesta').html(
      '<div class="fb-alert fb-alert-info">' +
        '<span class="fb-alert-icon">📤</span>' +
        '<div class="fb-alert-content">Enviando ' + archivosSeleccionados.length + ' ' + archivoTexto + '... Por favor espera.</div>' +
      '</div>'
    );
    
    // Deshabilitar el botón mientras se envía
    var $submitBtn = $('#fb-form button[type="submit"]');
    $submitBtn.prop('disabled', true).html('<span class="fb-btn-icon fb-spin">⏳</span> Enviando...');

    $.ajax({
      url: fb_ajax.url,
      type: 'POST',
      data: formData,
      processData: false,  // No procesar los datos
      contentType: false,  // No establecer contentType
      success: function(res) {
        if (res.success) {
          $('#fb-respuesta').html(
            '<div class="fb-alert fb-alert-success">' +
              '<span class="fb-alert-icon">✅</span>' +
              '<div class="fb-alert-content">' + res.data + '</div>' +
            '</div>'
          );
          $('#fb-form')[0].reset();
          
          // Limpiar array de archivos seleccionados
          archivosSeleccionados = [];
          $('#fb-selected-files').slideUp(300);
          
          // Recargar la lista de documentos
          setTimeout(function() {
            cargarDocumentos();
            // Animar el badge
            $('#fb-docs-count').addClass('fb-pulse');
            setTimeout(function() {
              $('#fb-docs-count').removeClass('fb-pulse');
            }, 1000);
          }, 1000);
        } else {
          $('#fb-respuesta').html(
            '<div class="fb-alert fb-alert-error">' +
              '<span class="fb-alert-icon">❌</span>' +
              '<div class="fb-alert-content">' + res.data + '</div>' +
            '</div>'
          );
        }
      },
      error: function(xhr, status, error) {
        $('#fb-respuesta').html(
          '<div class="fb-alert fb-alert-error">' +
            '<span class="fb-alert-icon">❌</span>' +
            '<div class="fb-alert-content">Error al enviar los archivos: ' + error + '</div>' +
          '</div>'
        );
      },
      complete: function() {
        // Rehabilitar el botón
        var $submitBtn = $('#fb-form button[type="submit"]');
        $submitBtn.prop('disabled', false).html('<span class="fb-btn-icon">📤</span> Enviar Archivos');
      }
    });
  });

  // ============================================
  // GESTIÓN DE DOCUMENTOS
  // ============================================
  
  // Botón de refrescar
  $(document).on('click', '#fb-refresh-docs', function() {
    var $btn = $(this);
    $btn.prop('disabled', true);
    $btn.html('<span class="fb-btn-icon fb-spin">🔄</span> Actualizando...');
    
    cargarDocumentos();
    
    setTimeout(function() {
      $btn.prop('disabled', false);
      $btn.html('<span class="fb-btn-icon">🔄</span> Actualizar Lista');
    }, 1000);
  });
  
  // Función para eliminar documento (delegada)
  $(document).on('click', '.fb-delete-doc', function() {
    var fileName = $(this).data('filename');
    var $button = $(this);
    var $row = $button.closest('tr');
    
    if (!confirm('¿Estás seguro de que deseas eliminar "' + fileName + '"?\n\nEsta acción no se puede deshacer.')) {
      return;
    }
    
    $button.prop('disabled', true).html('⏳ Eliminando...');
    
    $.ajax({
      url: fb_ajax.url,
      type: 'POST',
      data: {
        action: 'fb_eliminar_documento',
        file_name: fileName
      },
      success: function(response) {
        if (response.success) {
          // Animar la eliminación de la fila
          $row.addClass('fb-row-deleting');
          setTimeout(function() {
            $row.fadeOut(400, function() {
              $(this).remove();
              // Recargar la lista completa
              cargarDocumentos();
            });
          }, 300);
        } else {
          alert('❌ ' + response.data);
          $button.prop('disabled', false).html('🗑️ Eliminar');
        }
      },
      error: function(xhr, status, error) {
        alert('❌ Error al eliminar el documento: ' + error);
        $button.prop('disabled', false).html('🗑️ Eliminar');
      }
    });
  });
});
