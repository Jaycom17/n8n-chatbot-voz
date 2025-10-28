jQuery(document).ready(function($) {
  $('#fb-form').on('submit', function(e) {
    e.preventDefault();

    // Verificar que se hayan seleccionado archivos
    var archivos = $('#archivos')[0].files;
    if (archivos.length === 0) {
      $('#fb-respuesta').html('<p style="color:red;">⚠️ Por favor selecciona al menos un archivo.</p>');
      return;
    }

    // Crear FormData para enviar archivos
    var formData = new FormData();
    formData.append('action', 'fb_enviar_formulario');

    // Agregar archivos
    for (var i = 0; i < archivos.length; i++) {
      formData.append('archivos[]', archivos[i]);
    }

    // Mostrar mensaje de carga
    var archivoTexto = archivos.length === 1 ? 'archivo' : 'archivos';
    $('#fb-respuesta').html('<p style="color:blue;">📤 Enviando ' + archivos.length + ' ' + archivoTexto + '... Por favor espera.</p>');
    
    // Deshabilitar el botón mientras se envía
    $('#fb-form button[type="submit"]').prop('disabled', true).text('⏳ Enviando...');

    $.ajax({
      url: fb_ajax.url,
      type: 'POST',
      data: formData,
      processData: false,  // No procesar los datos
      contentType: false,  // No establecer contentType
      success: function(res) {
        if (res.success) {
          $('#fb-respuesta').html('<p style="color:green;">' + res.data + '</p>');
          $('#fb-form')[0].reset();
        } else {
          // Mostrar mensaje de error (puede contener HTML)
          $('#fb-respuesta').html('<div style="color:red; background: #fee; padding: 15px; border-radius: 8px; border-left: 4px solid red;">' + res.data + '</div>');
        }
      },
      error: function(xhr, status, error) {
        $('#fb-respuesta').html('<p style="color:red;">❌ Error al enviar los archivos: ' + error + '</p>');
      },
      complete: function() {
        // Rehabilitar el botón
        $('#fb-form button[type="submit"]').prop('disabled', false).text('📤 Enviar Archivos');
      }
    });
  });
});
