jQuery(document).ready(function($) {
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
        const loaderIcon = "<div class='dc-loader-ajax' bis_skin_checked='1'><img decoding='async' alt=' data-src='http://redimpacto.local/wp-content/themes/charitian-child/assets/img/ri-preloader.svg' class=' ls-is-cached lazyloaded' src='http://redimpacto.local/wp-content/themes/charitian-child/assets/img/ri-preloader.svg'></div>";
        isLoadMore ?
          $('.dc__content-loop-grid').after(loaderIcon) :
          $('.dc__content-loop-grid').html(loaderIcon);
      },
      success: function(response) {
        if (response.success) {
          if(isLoadMore) {
            $('.dc-loader-ajax').remove();
            $('.dc__content-loop-grid').append(response.data);
          } else {
            $('.dc__content-loop-grid').html(response.data);
          }
        } else {
            $('.dc__content-loop-grid').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }
});