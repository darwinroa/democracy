jQuery(document).ready(function($) {
  function viewMoreExpand() {
    // Agrega un evento de clic a todos los botones con la clase 'boton-expandir'
    $('.boton-expandir').click(function() {
      // Encuentra el elemento hermano con la clase 'contenido-expandible' y toglea la clase 'mostrar'
      $(this).next('.contenido-expandible').toggleClass('mostrar');
    });
  }
  viewMoreExpand();
});