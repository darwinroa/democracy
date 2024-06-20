jQuery(document).ready(function($) {
  /**
   * Script para cargar PopUp para los miembros de equipo
   */
  var $openPopupBtn = $('.dc_team-popup');
  var $closePopupBtn = $('#closePopup');
  var $popup = $('#teamPopup');
  var $overlay = $('#overlay');

  // Agregar eventos a los botones
  $('body').on('click', '.dc_team-popup', openPopup);
  $closePopupBtn.on('click', closePopup);

  // Cerrar el pop-up al hacer clic fuera de él
  $overlay.on('click', closePopup);

  // Función para abrir el pop-up y desactivar el scroll
  function openPopup() {
    const dataId = $(this).data('id');

    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_ajax_team_popup',
        nonce: wp_ajax.nonce,
        dataId
      },
      success: function(response) {
        $popup.show();
        $overlay.show();        
        $('body').addClass('no-scroll');
        if (response.success) {
          $('#teamPopupContent').html(response.data)
        } else {
            $('#teamPopupContent').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }

  // Función para cerrar el pop-up y activar el scroll
  function closePopup() {
    $popup.hide();
    $overlay.hide();
    $('#teamPopupContent').empty();
    $('body').removeClass('no-scroll');
  }

});