jQuery(document).ready(function($) {
  function viewMoreExpand() {
    // Usa la delegación de eventos para manejar el evento click en los elementos dinámicamente añadidos
    $(document).on('click', '.boton-expandir', function() {
      // Encuentra el elemento hermano con la clase 'contenido-expandible' y toglea la clase 'mostrar'
      $(this).next('.contenido-expandible').toggleClass('mostrar');
    });
  }
  viewMoreExpand();
});