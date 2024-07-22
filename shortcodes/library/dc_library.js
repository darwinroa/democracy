jQuery(document).ready(function($) {
  /**
   * Scipits para el filtro y el cargar más con Ajax usado para el Query de Library
   */
  var page = 1; // Inicializando el paginado
  var isLoadMore = false;
  // Esto se ejecuta cuando se presiona sobre el botón de filtrar
  // De modo que el filtro se realiza tomando los datos seleccionados
  $('#dc__button-filter-libraries').on('click', function(){
    page = 1; // Inicializando el paginado cada vez que se desea filtrar
    isLoadMore = false;
    dcLibraryAjax(page);
  })

  // Esto se ejecuta cuando se presiona sobre el botón de Load More
  // Realizando una petición de más post.
  // Considerando también los datos seleccionados para el filtro
  $('#dc__button-loadmore-libraries').on('click', function() {
    page++;
    isLoadMore = true;
    dcLibraryAjax(page);
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
        const loaderUrl = wp_ajax.theme_directory_uri + '/inc/img/ri-preloader.svg';
        const loaderIcon = `<div class='dc-loader-ajax' bis_skin_checked='1'><img id='dc__loadmore-icon' height='20' width='20' decoding='async' alt='Loading' data-src='${loaderUrl}' class='ls-is-cached lazyloaded e-font-icon-svg e-fas-spinner eicon-animation-spin' src='${loaderUrl}'></div>`;
        isLoadMore ||  $('#dc__library-section .dc__content-loop-grid').empty();
        $('#dc__library-section .dc__content-button-loadmore').append(loaderIcon);
        $('#dc__library-section .dc__button-loadmore').hide();
      },
      success: function(response) {
        if (response.success) {
        $('#dc__library-section .dc__button-loadmore').show();
        $('.dc-loader-ajax').remove();
          if(isLoadMore) {
            $('#dc__library-section .dc__content-loop-grid').append(response.data);
          } else {
            $('#dc__library-section .dc__content-loop-grid').html(response.data);
          }
        } else {
            $('#dc__library-section .dc__content-loop-grid').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }
});