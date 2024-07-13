jQuery(document).ready(function($) {
  $(document).on('elementor/popup/show', function() {
    $('.trem-team__item').on('click', function(){
      $('.trem-team__item').removeClass('active');
      $(this).addClass('active');
    });
  });
});