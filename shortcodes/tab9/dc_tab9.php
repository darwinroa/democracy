<?php

/**
 * Este shortcode se utiliza para imprimir contenido en el 
 * tab 9 de la página Deliberation on Difficult Issues
 */
add_shortcode('filter_select', 'filter_function');

function  filter_function()
{
  wp_enqueue_style('dc-tab9-style', get_stylesheet_directory_uri() . '/shortcodes/tab9/dc_tab9.css', array(), '1.0');
  wp_enqueue_script('dc-tab9-script', get_stylesheet_directory_uri() . '/shortcodes/tab9/dc_tab9.js', array('jquery'), null, true);
  $html = '';
  $content = '';
  $index = 0;
  $contentTab = get_field('tab_9_contenido');
  $rows = get_field('selection_filter');
  $html .= "<div class='dc__tab9-content'>
              <p class='tab9__text'>$contentTab</p>
            </div>";
  if ($rows) {
    $html .= '<div class="content-toggle">';
    $html .= '<div class="filter-top">';
    $html .= '<div class="filter-title">';
    $html .= '<h3 id="title" class="title">' . $rows[0]['filtering_values']['title'] . '</h3>';
    $html .= '</div>'; // end class filter-title
    $html .= '<div class="filter-select">';
    // Generar el select personalizado
    $html .= '<div class="custom-select">';
    $html .= '<div class="select-selected">' . $rows[0]['filtering_values']['title'] . '<span class="hfe-menu-toggle sub-arrow hfe-menu-child-0"><i class="fa"></i></span></div>';
    $html .= '<div class="select-items">';
    foreach ($rows as $row) {
      $class = $index ? 'hide' : '';
      $contentOption = $row['filtering_values'];
      $html .= '<div class="select-item" data-value="option-' . $index . '">' . $contentOption['title'] . '</div>';
      $content .= '<div class="filter-content ' . $class . '" id="option-' . $index . '">' . $contentOption['template'] . '</div>';
      $index++;
    }

    $html .= '</div>'; // end class select-items
    $html .= '</div>'; // end class custom-select
    $html .= '</div>'; // end class filter-select
    $html .= '</div>'; // end class filter-top
    $html .= $content;
    $html .= '</div>'; // end class content-toggle
  }
  return $html;
}
