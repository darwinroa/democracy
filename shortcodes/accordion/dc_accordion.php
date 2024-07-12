<?php
add_shortcode('dc_accordion', 'dc_accordion_function');

function dc_accordion_function()
{
  wp_enqueue_style('dc-accordion-style', get_stylesheet_directory_uri() . '/shortcodes/accordion/dc_accordion.css', array(), '1.0');
  wp_enqueue_script('dc-accordion-script', get_stylesheet_directory_uri() . '/shortcodes/accordion/dc_accordion.js', array('jquery'), null, true);
  $shortcode = do_shortcode('[INSERT_ELEMENTOR id="3292"]');
  ob_start();
  $html = '';
  $html .= "
    <div class='trem-team'>
      <div class='trem-team__item active'>
        <div class='trem-team__item__fig'>
          <div class='trem-team__item__fig__text'>
            <div class='dc__accordeon_step-letter'>a</div>
            <div class='dc__accordion_step-title'>Collaboration</div>
          </div>
          <div class='trem-team__item__fig__caption'>
            $shortcode
          </div>
        </div>
      </div>
      <div class='trem-team__item'>
        <div class='trem-team__item__fig'>
          <div class='trem-team__item__fig__text'>
            <div class='dc__accordeon_step-letter'>a</div>
            <div class='dc__accordion_step-title'>Collaboration</div>
          </div>
          <div class='trem-team__item__fig__caption'>
            <h3 class='trem-team__item__fig__caption__name'>JHON PRIETO</h3>
            <p class='trem-team__item__fig__caption__desc'>
              CoFundador y Director Comercial de Esto Es Tremendo S.A.S. una agencia de publicidad independiente 100% colombiana, ejecutora de ideas innovadoras, dedicada a la creación, desarrollo, producción y montaje de eventos y convenciones, así como activaciones, promociones y experiencias para productos, marcas y organizaciones.
            </p>
          </div>
        </div>
      </div>
      <div class='trem-team__item'>
        <div class='trem-team__item__fig'>
          <div class='trem-team__item__fig__text'>
            <div class='dc__accordeon_step-letter'>a</div>
            <div class='dc__accordion_step-title'>Collaboration</div>
          </div>
          <div class='trem-team__item__fig__caption'>
            <h3 class='trem-team__item__fig__caption__name'>JHON PRIETO</h3>
            <p class='trem-team__item__fig__caption__desc'>
              CoFundador y Director Comercial de Esto Es Tremendo S.A.S. una agencia de publicidad independiente 100% colombiana, ejecutora de ideas innovadoras, dedicada a la creación, desarrollo, producción y montaje de eventos y convenciones, así como activaciones, promociones y experiencias para productos, marcas y organizaciones.
            </p>
          </div>
        </div>
      </div>
    </div>
  ";
  $html .= ob_get_clean();
  return $html;
}
