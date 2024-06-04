jQuery(document).ready(function($) {
  $('#member-filter-button').on('click', function(){
    const memberType = $('#type_member').val();
    const region = $('#region').val();
    const fieldWork = $('#field_of_work').val();
    $.ajax({
      url: wp_ajax.ajax_url,
      type: 'post',
      data: {
        action: 'dc_member_ajax_filter',
        nonce: wp_ajax.nonce,
        memberType,
        region,
        fieldWork
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
  })
})