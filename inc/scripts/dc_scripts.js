jQuery(document).ready(function($) {
  function viewMoreExpand() {
    // Usa la delegación de eventos para manejar el evento click en los elementos dinámicamente añadidos
    $(document).on('click', '.boton-expandir', function() {
      var $expandirButton = $(this);
      var $contenidoExpandible = $expandirButton.next('.contenido-expandible');
      
      // Oculta el botón expandir
      $expandirButton.hide();
      
      // Muestra el contenido expandible y añade la clase 'mostrar'
      $contenidoExpandible.addClass('mostrar');
      
      // Asegúrate de mostrar el botón de ocultar
      $contenidoExpandible.find('.boton-ocultar').show();
    });

    // Delegación de eventos para el botón de ocultar que es hijo del contenido-expandible
    $(document).on('click', '.contenido-expandible .boton-ocultar', function() {
      var $ocultarButton = $(this);
      var $contenidoExpandible = $ocultarButton.closest('.contenido-expandible');
      var $expandirButton = $contenidoExpandible.prev('.boton-expandir');

      // Oculta el contenido expandible y quita la clase 'mostrar'
      $contenidoExpandible.removeClass('mostrar');

      // Muestra el botón expandir
      $expandirButton.show();
      
      // Asegúrate de ocultar el botón de ocultar
      $ocultarButton.hide();
    });
  }

  viewMoreExpand();
});
