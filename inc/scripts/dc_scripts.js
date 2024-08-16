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


// Función para aplicar estilos a cada elemento
function applyStyles() {
  // Selecciona todos los elementos con la clase 'bdt-ep-circle-info-sub-circle'
  const elements = document.querySelectorAll('.bdt-ep-circle-info-sub-circle');
  
  // Define los estilos que se deben aplicar a cada elemento
  const styles = [
      'transform: translate3d(0px, -200px, 0px);',
      'transform: translate3d(-200px, 0px, 0px);',
      'transform: translate3d(0px, 200px, 0px);',
      'transform: translate3d(200px, 0px, 0px);'
  ];
  
  // Aplica los estilos a cada elemento
  elements.forEach((element, index) => {
      if (index < styles.length) {
          element.style.cssText = styles[index];
      }
  });
}

// Añadir el evento click a los elementos con la clase 'bdt-ep-accordion-item'
document.querySelectorAll('.bdt-ep-accordion-item').forEach(item => {
  item.addEventListener('click', () => {
      // Ejecutar la función para aplicar los estilos cuando se hace clic en el elemento
      applyStyles();
  });
});

