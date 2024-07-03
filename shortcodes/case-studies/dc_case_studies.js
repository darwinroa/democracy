jQuery(document).ready(function($) {
  $('#mapa-mundi').on('click', 'g', function() {
      var id = $(this).attr('id');
      console.log('se presionó en--->', id);
  });
});
