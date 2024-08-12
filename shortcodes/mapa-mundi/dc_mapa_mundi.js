jQuery(document).ready(function($) {
  /**
   * Se activa luego de presionar sobre cualquier país del mapa
   */
  var page = 1; // Inicializando el paginado
  var isLoadMore = false;
  var loadMoreButon = $('#dc__button-loadmore-members-countries');
  /**
   * Se activa al dar clic en cualquier país del mapa
   */
  $('#mapa-mundi').on('click', 'g', function() {
    var slugCountry = $(this).attr('id'); // Obtiene el slug del país contenido en el id del país en el mapa
    var countryName = $(this).data('country'); // Obtiene el nombre del país en el hover para poder mostrar en el título del mapa
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    loadMoreButon.show();
    loadMoreButon.attr('data-country', slugCountry); // Agrega un data-country con el valor del slug del país para cargar más
    $('g').removeClass('active');
    $(this).addClass('active');
    $('#dc__header-country').text(countryName); // Agrega el nombre del país en el título del mapa
    $('.dc__sidebar-location').removeClass('dc__hide');
    dcCaseStudyAjax(slugCountry); // Función que imprime el loop de la consulta ajax al seleccionar un país en el mapa
    dcCountriesAjax(''); // Ejecuta la consulta para agregar los países en el select
    $('.dc__sidebar-location .dc__location-title').removeClass('active');
  });

  /**
   * Se activa al posicionar el cursor sobre un país en el mapa
   * Actualiza el título del mapa con el nombre del país donde estoy en hover
   */
  $('#mapa-mundi').on('mouseenter', 'g', function() {
    var countryName = $(this).data('country');
    $('#dc__header-country').text(countryName);
    $('#dc__header-total-members.our_reach').text('Our members in');
    $('#dc__header-total-members.case_studies').text('Case study in');
  });

  /**
   * Se activa al retirar el cursor sobre un país en el mapa
   * Actualiza el título del mapa con el nombre del país que está activo, si es que hay un país activado
   */
  $('#mapa-mundi').on('mouseleave', 'g', function() {
    var countryName = $('g.active').data('country');
    $('#dc__header-country').text(countryName);
  });
  
  /**
   * Se activa al elegir un país en el select
   */
  $('#dc-country-select').on('change', function() {
    var slugCountry = $(this).val();
    var nameCountry = $(this).find('option:selected').data('countryselect'); // Actualiza el título con el nombre del país seleccionado  
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    loadMoreButon.show();
    loadMoreButon.attr('data-country', slugCountry); // Actualiza el data-country del botón de cargar más
    $('#dc__header-country').text(nameCountry);
    $('g').removeClass('active');
    $('#mapa-mundi #' + slugCountry).addClass('active');
    $('.dc__sidebar-location').removeClass('dc__hide');
    dcCaseStudyAjax(slugCountry); // Función que imprime el loop de la consulta ajax al seleccionar un país en el select
  });

  /**
   * Se activa al elegir una región en el sidebar
   */
  $('.dc__sidebar-filter .dc__sidebar-location').on('click', '.dc__location-title', function() {
    var slugCountry = $(this).data('country');
    var idCountry = $(this).data('countryid');
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    loadMoreButon.show();
    loadMoreButon.attr('data-country', slugCountry);
    $('.dc__sidebar-location .dc__location-title').removeClass('active');
    $(this).addClass('active');
    $('g').removeClass('active');
    $('.dc__sidebar-location').removeClass('dc__hide');
    dcCaseStudyAjax(slugCountry);  // Función que imprime el loop de la consulta ajax al seleccionar una región en el sideabr
    dcCountriesAjax(idCountry);  // Ejecuta la consulta para agregar los países en el select relacionados a la región seleccionada en el sidebar
  })  

  // Esto se ejecuta cuando se presiona sobre el botón de Load More
  // Realizando una petición de más post.
  // Considerando también los datos seleccionados para el filtro
  $('#dc__button-loadmore-members-countries').on('click', function() {
    page++;
    isLoadMore = true;
    var slugCountry = $(this).attr('data-country');
    dcCaseStudyAjax(slugCountry, page); // Función que imprime el loop de la consulta ajax al presionar en cargar más
  })

  /**
   * Función Ajax que retorna el la consulta de posts y los agrega en el html con clase .dc__content-loop-grid
   */
  function dcCaseStudyAjax (slugCountry, page = 1) {
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_case_study_ajax',
        nonce: wp_ajax.nonce,
        postType: wp_ajax.post_type,
        slugCountry,
        page
      },
      beforeSend: function(){
        const loaderUrl = wp_ajax.theme_directory_uri + '/inc/img/ri-preloader.svg';
        const loaderIcon = `<div class='dc-loader-ajax' bis_skin_checked='1'><img id='dc__loadmore-icon' height='20' width='20' decoding='async' alt='Loading' data-src='${loaderUrl}' class='ls-is-cached lazyloaded e-font-icon-svg e-fas-spinner eicon-animation-spin' src='${loaderUrl}'></div>`;
        isLoadMore ||  $('#dc__case_studies-section .dc__content-loop-grid').empty();
        $('#dc__case_studies-section .dc__content-button-loadmore').append(loaderIcon);
        $('#dc__case_studies-section .dc__button-loadmore').hide();        
      },
      success: function(response) {
        if (response.success) {
          $('#dc__case_studies-section .dc__button-loadmore').show();
          $('.dc-loader-ajax').remove();
          if(isLoadMore) {
            $('#dc__case_studies-section .dc__content-loop-grid').append(response.data);
          } else {
            $('#dc__case_studies-section .dc__content-loop-grid').html(response.data);
          }
        } else {
            $('#dc__case_studies-section .dc__content-loop-grid').html('<p>Hubo un error en la solicitud.</p>');
        }
      }
    })
  }

  /**
   * Función que imprime las opciones del select con id #dc-country-select
   */
  function dcCountriesAjax(idCountry) {
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_options_countries_ajax',
        nonce: wp_ajax.nonce,
        postType: wp_ajax.post_type,
        idCountry
      },
      success: function(response) {
        if (response.success) {
          $('#dc-country-select').html(response.data);
        }
      }
    })
  }
});
