jQuery(document).ready(function($) {
  var page = 1; // Inicializando el paginado
  var isLoadMore = false;
  // Esto se ejecuta cuando se presiona sobre el botón de filtrar
  // De modo que el filtro se realiza tomando los datos seleccionados
  $('#dc__button-filter-members').on('click', function() {
    page = 1; // Inicializando el paginado cada vez que se desea filtrar
    isLoadMore = false;
    dcMembersAjax(page);
  })
 
  // Esto se ejecuta cuando se presiona sobre el botón de Load More
  // Realizando una petición de más post.
  // Considerando también los datos seleccionados para el filtro
  $('#dc__button-loadmore-members').on('click', function() {
    page++;
    isLoadMore = true;
    dcMembersAjax(page);
  })

  // Función Ajax para la petición del filtro y el cargar más
  function dcMembersAjax (page) {
    const memberType = $('#type_member').val();
    const region = $('#region').val();
    const fieldWork = $('#field_of_work').val();
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_member_ajax_filter',
        nonce: wp_ajax.nonce,
        page,
        memberType,
        region,
        fieldWork
      },
      beforeSend: function(){
        const loaderUrl = wp_ajax.theme_directory_uri + '/inc/img/ri-preloader.svg';
        const loaderIcon = `<div class='dc-loader-ajax' bis_skin_checked='1'><img decoding='async' alt='Loading' data-src='${loaderUrl}' class='ls-is-cached lazyloaded' src='${loaderUrl}'></div>`;
        isLoadMore ?
          $('#dc__members-section .dc__content-loop-grid').after(loaderIcon) :
          $('#dc__members-section .dc__content-loop-grid').html(loaderIcon);
      },
      success: function(response) {
        if (response.success) {
          if(isLoadMore) {
            $('.dc-loader-ajax').remove();
            $('#dc__members-section .dc__content-loop-grid').append(response.data);
          } else {
            $('#dc__members-section .dc__content-loop-grid').html(response.data);
          }
        } else {
            $('#dc__members-section .dc__content-loop-grid').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }
})
