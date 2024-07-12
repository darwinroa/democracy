jQuery(document).ready(function($) {
  $('.trem-team__item').on('click', function(){
    $('.trem-team__item').removeClass('active');
    $(this).addClass('active');
  });
});