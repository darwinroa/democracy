jQuery(document).ready(function($) {
  /**
   * Se activa luego de presionar sobre cualquier país del mapa
   */
  $('#mapa-mundi').on('click', 'g', function() {
    var idCountry = $(this).attr('id');
    $('g').removeClass('active');
    $(this).addClass('active');
    console.log('se presionó en--->', idCountry);
    isLoadMore = false;
    dcCaseStudyAjax(idCountry);
  });
  
  $('#dc-country-select').on('change', function() {
    var idCountry = $(this).val();
    console.log('País seleccionado desde el select--->', idCountry);
    $('g').removeClass('active');
    $('#mapa-mundi #' + idCountry).addClass('active');
    isLoadMore = false;
    dcCaseStudyAjax(idCountry);
  })

  function dcCaseStudyAjax (idCountry) {
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_case_study_ajax',
        nonce: wp_ajax.nonce,
        idCountry
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
});
