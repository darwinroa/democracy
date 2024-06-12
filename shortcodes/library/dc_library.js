jQuery(document).ready(function($) {
  $('#dc__button-filter-libraries').on('click', function(){
    dcLibraryAjax(1);
  })

  // Función Ajax para la petición del filtro y el cargar más
  function dcLibraryAjax (page) {
    const formats = $('#formats').val();
    const authors = $('#authors').val();
    const years = $('#years').val();
    const languages = $('#languages').val();
    const topics = $('#topics').val();
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_library_ajax_filter',
        nonce: wp_ajax.nonce,
        page,
        formats,
        authors,
        years,
        languages,
        topics
      },
      beforeSend: function(){
        $('.dc__content-loop-grid').html("<div class='dc-loader-ajax' bis_skin_checked='1'><img decoding='async' alt=' data-src='http://redimpacto.local/wp-content/themes/charitian-child/assets/img/ri-preloader.svg' class=' ls-is-cached lazyloaded' src='http://redimpacto.local/wp-content/themes/charitian-child/assets/img/ri-preloader.svg'></div>");
      },
      success: function(response) {
        if (response.success) {
            $('.dc__content-loop-grid').html(response.data);
        } else {
            $('.dc__content-loop-grid').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }
});