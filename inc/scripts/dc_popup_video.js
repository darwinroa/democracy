jQuery(document).ready(function($){
  /**
   * Script para cargar los videos PopUp
   */
  var $openPopupBtn = $('.dc_video_pop_up');
  var $closePopupBtn = $('#closePopup');
  var $popup = $('#videoPopup');
  var $overlay = $('#overlay');

  // Agregar eventos a los botones
  $(document).on('click', '.dc_video_pop_up', openPopup);
  $closePopupBtn.on('click', closePopup);

  // Cerrar el pop-up al hacer clic fuera de él
  $overlay.on('click', closePopup);

  // Cerrar el pop-up al hacer clic fuera del contenido del pop-up
  $(document).on('click', function(event) {
    if (!$popup.is(event.target) && $popup.has(event.target).length === 0 && !$openPopupBtn.is(event.target)) {
      closePopup();
    }
  });

  // Función para abrir el pop-up y desactivar el scroll
  function openPopup() {
    const videoId = $(this).data('id');
    $.ajax({
      url: popup_wp_ajax.popup_ajax_url,
      type: 'post',
      data: {
        action: 'dc_ajax_popup',
        nonce: popup_wp_ajax.nonce,
        videoId
      },
      success: function(response) {
        $popup.show();
        $overlay.show();        
        $('body').addClass('no-scroll');
        if (response.success) {
          $('#videoPopupContent').html(response.data)
        } else {
            $('#videoPopupContent').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }

  // Función para cerrar el pop-up y activar el scroll
  function closePopup() {
    $popup.hide();
    $overlay.hide();
    $('#videoPopupContent').empty();
    $('body').removeClass('no-scroll');
  }
})