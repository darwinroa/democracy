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
        const loaderIcon = `<div class='dc-loader-ajax' bis_skin_checked='1'><img decoding='async' alt='Loading' data-src='${loaderUrl}' class='ls-is-cached lazyloaded' src='${loaderUrl}'></div>`;
        isLoadMore ?
          $('#dc__library-section .dc__content-loop-grid').after(loaderIcon) :
          $('#dc__library-section .dc__content-loop-grid').html(loaderIcon);
      },
      success: function(response) {
        if (response.success) {
          if(isLoadMore) {
            $('.dc-loader-ajax').remove();
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


/**
 * Script para cargar los videos PopUp
 */
document.addEventListener('DOMContentLoaded', function() {
  var openPopupBtn = document.getElementById('openPopup');
  var closePopupBtn = document.getElementById('closePopup');
  var popup = document.getElementById('popup');
  var overlay = document.getElementById('overlay');

  // Función para abrir el pop-up y desactivar el scroll
  function openPopup() {
      popup.style.display = 'block';
      overlay.style.display = 'block';
      document.body.classList.add('no-scroll');
  }

  // Función para cerrar el pop-up y activar el scroll
  function closePopup() {
      popup.style.display = 'none';
      overlay.style.display = 'none';
      document.body.classList.remove('no-scroll');
  }

  // Agregar eventos a los botones
  openPopupBtn.addEventListener('click', openPopup);
  closePopupBtn.addEventListener('click', closePopup);

  // Cerrar el pop-up al hacer clic fuera de él
  overlay.addEventListener('click', closePopup);

  // Cerrar el pop-up al hacer clic fuera del contenido del pop-up
  document.addEventListener('click', function(event) {
      if (!popup.contains(event.target) && !openPopupBtn.contains(event.target)) {
          closePopup();
      }
  });
});