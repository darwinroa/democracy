jQuery(document).ready(function($) {
  $(document).on('elementor/popup/show', function() {
    $('.trem-team__item').on('click', function(){
      $('.trem-team__item').removeClass('active');
      $(this).addClass('active');
    });

    // Delegación de eventos para elementos '.bdt-ep-fancy-tabs-item' dentro de los elementos '#step_02_a', '#step_02_b', '#step_02_c'
    $('.dc__tabs_popup').on('click', '.bdt-ep-fancy-tabs .bdt-ep-fancy-tabs-item', function(event) {
      // Obtener el elemento en el que se hizo clic
      var clickedElement = $(event.delegateTarget);
      
      // Determinar cuál de los pasos se ha clicado
      var stepID = clickedElement.attr('id');
      
      // Obtener el data-id del elemento .bdt-ep-fancy-tabs-item que fue clicado
      var dataID = $(this).data('id');

      $('#' + stepID + ' #' + dataID).addClass('active');
    });
  });
});
