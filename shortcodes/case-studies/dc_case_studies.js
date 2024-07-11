jQuery(document).ready(function($) {
  /**
   * Se activa luego de presionar sobre cualquier país del mapa
   */
  var page = 1; // Inicializando el paginado
  var isLoadMore = false;
  $('#mapa-mundi').on('click', 'g', function() {
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    var slugCountry = $(this).attr('id');
    $('#dc__button-loadmore-members-countries').attr('data-country', slugCountry);
    $('g').removeClass('active');
    $(this).addClass('active');
    var countryName = $(this).data('country');
    $('#dc__header-country').text(countryName);
    console.log('se presionó en--->', slugCountry);
    $('.dc__sidebar-location').removeClass('dc__hide');
    dcCaseStudyAjax(slugCountry);
    dcCountriesAjax('');
    $('.dc__sidebar-location .dc__location-title').removeClass('active');
  });

  $('#mapa-mundi').on('mouseenter', 'g', function() {
    var countryName = $(this).data('country');
    $('#dc__header-country').text(countryName);
    $('#dc__header-total-members').text('Our members in');
  });

  $('#mapa-mundi').on('mouseleave', 'g', function() {
    var countryName = $('g.active').data('country');
    $('#dc__header-country').text(countryName);
  });
  
  $('#dc-country-select').on('change', function() {
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    var slugCountry = $(this).val();
    $('#dc__button-loadmore-members-countries').attr('data-country', slugCountry);
    console.log('País seleccionado desde el select--->', slugCountry);
    var nameCountry = $(this).find('option:selected').data('countryselect');
    console.log('Nombre del País seleccionado desde el select --->', nameCountry);    
    $('#dc__header-country').text(nameCountry);
    $('#dc__header-total-members').text('Our members in');
    $('g').removeClass('active');
    $('#mapa-mundi #' + slugCountry).addClass('active');
    $('.dc__sidebar-location').removeClass('dc__hide');
    isLoadMore = false;
    dcCaseStudyAjax(slugCountry);
  });

  $('.dc__sidebar-filter .dc__sidebar-location').on('click', '.dc__location-title', function() {
    page = 1; // Inicializando el paginado
    isLoadMore = false;
    var slugCountry = $(this).data('country');
    var idCountry = $(this).data('countryid');
    $('#dc__button-loadmore-members-countries').attr('data-country', slugCountry);
    $('.dc__sidebar-location .dc__location-title').removeClass('active');
    $(this).addClass('active');
    $('g').removeClass('active');
    $('.dc__sidebar-location').removeClass('dc__hide');
    console.log('País seleccionado desde el select--->', slugCountry);
    isLoadMore = false;
    dcCaseStudyAjax(slugCountry);
    dcCountriesAjax(idCountry);
  })  

  // Esto se ejecuta cuando se presiona sobre el botón de Load More
  // Realizando una petición de más post.
  // Considerando también los datos seleccionados para el filtro
  $('#dc__button-loadmore-members-countries').on('click', function() {
    page++;
    isLoadMore = true;
    var slugCountry = $(this).attr('data-country');
    console.log('valor de country del boton-->', slugCountry);
    dcCaseStudyAjax (slugCountry, page);
  })

  function dcCaseStudyAjax (slugCountry, page = 1) {
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_case_study_ajax',
        nonce: wp_ajax.nonce,
        slugCountry,
        page
      },
      beforeSend: function(){
        const loaderUrl = wp_ajax.theme_directory_uri + '/inc/img/ri-preloader.svg';
        const loaderIcon = `<div class='dc-loader-ajax' bis_skin_checked='1'><img decoding='async' alt='Loading' data-src='${loaderUrl}' class='ls-is-cached lazyloaded' src='${loaderUrl}'></div>`;
        isLoadMore ?
          $('#dc__case_studies-section .dc__content-loop-grid').after(loaderIcon) :
          $('#dc__case_studies-section .dc__content-loop-grid').html(loaderIcon);
      },
      success: function(response) {
        if (response.success) {
          if(isLoadMore) {
            $('.dc-loader-ajax').remove();
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

  function dcCountriesAjax(idCountry) {
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_options_countries_ajax',
        nonce: wp_ajax.nonce,
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
