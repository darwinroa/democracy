<?php
add_shortcode('dc_accordion', 'dc_accordion_function');

function dc_accordion_function($atts)
{
  // Los atributos aceptados y sus valores predeterminados
  $attributes = shortcode_atts(
    array(
      'step'  => '00',
    ),
    $atts
  );

  wp_enqueue_style('dc-accordion-style', get_stylesheet_directory_uri() . '/shortcodes/accordion/dc_accordion.css', array(), '1.0');
  wp_enqueue_script('dc-accordion-script', get_stylesheet_directory_uri() . '/shortcodes/accordion/dc_accordion.js', array('jquery'), null, true);
  $allSteps = get_field('steps_snl');
  $numberStep = intval($attributes['step']);
  $protocolSteps = $allSteps[$numberStep]['protocol_steps'];

  ob_start();
  $protocolStepsHTML = '';
  $isActive = true;
  foreach ($protocolSteps as $protocolStep) {
    $protocolStepsHTML .= dc_get_protocol_step_html($protocolStep, $isActive);
    $isActive = false;
  }
  $html = "
    <div class='trem-team'>
      $protocolStepsHTML
    </div>
  ";
  $html .= ob_get_clean();
  return $html;
}

function dc_get_protocol_step_html($protocolStep, $isActive)
{
  ob_start();
  $classActive = $isActive ? 'active' : '';
  $letterStep = $protocolStep['letter_step'];
  $titleStep = $protocolStep['title_step'];
  $templateStep = $protocolStep['template_step'];
  $html = "
    <div class='trem-team__item $classActive'>
      <div class='trem-team__item__fig'>
        <div class='team__item__text'>
          <div class='dc__accordeon_step-letter'>$letterStep</div>
          <div class='dc__accordion_step-title'>$titleStep</div>
        </div>
        <div class='trem-team__item__content'>
          $templateStep
        </div>
      </div>
    </div>
  ";
  $html .= ob_get_clean();
  return $html;
}
