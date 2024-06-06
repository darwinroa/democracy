<?php
if (!function_exists('dc_libraries_function')) {
  add_shortcode('dc_libraries', 'dc_libraries_function');

  function dc_libraries_function()
  {
    // wp_enqueue_style('dc-members-style', get_stylesheet_directory_uri() . '/shortcodes/members/dc_members.css', array(), '1.0');
    // wp_enqueue_script('dc-members-script', get_stylesheet_directory_uri() . '/shortcodes/members/dc_members.js', array('jquery'), null, true);
    // wp_localize_script('dc-members-script', 'wp_ajax', array(
    //   'ajax_url'          => admin_url('admin-ajax.php'),
    //   'nonce'             => wp_create_nonce('load_more_nonce'),
    // ));
    ob_start();
    $html = '';
    $html .= "<div id='dc__library-section'>";
    /**
     * Se crea un array con las taxonomías que se requieren para crear los filtros 
     * Este array se envía luego a la función dc_html_filter_form() que se encarga de retornar los filtros
     */
    $taxonomies = array(
      array(
        'slug' => 'formats',
        'name' => 'Format'
      ),
      array(
        'slug' => 'authors',
        'name' => 'Author'
      ),
      array(
        'slug' => 'years',
        'name' => 'Year'
      ),
      array(
        'slug' => 'languages',
        'name' => 'Language'
      ),
      array(
        'slug' => 'topic',
        'name' => 'Topic'
      ),
    );
    $form_ID = 'dc__filter-libraries';
    $html .= "<div class='dc__content-loop'>";
    $html .= dc_html_filter_form($taxonomies, $form_ID);
    $html .= "<div class='dc__content-loop-grid'>";
    /**
     * Aquí se optiene el Loop inicial al momento de cargar la web
     * Lo que se necesita es crear un array con los argumentos necesarios para el Query
     * Luego estos argumentos son enviados a la función dc_query_members_loop() 
     * Esta función es la encargada de retornar el loop con los argumentos necesarios
     */
    $args = array(
      'post_type' => 'library',
      'posts_per_page' => 9
    );
    $html .= dc_query_libraries_loop($args);
    $html .= "</div></div></div>";
    return $html;
  }
}

/**
 * Retorna el HTML del loop para la sección de libraries
 * $args son los argumentos necesarios para el loop
 */
function dc_query_libraries_loop($args)
{
  $query = new WP_Query($args);
  $html = "";
  if ($query->have_posts()) :
    ob_start();
    while ($query->have_posts()) : $query->the_post();
      $title = get_the_title();
      $info = get_field('informacion_extra');
      $description = get_the_content();
      $post_id = get_the_ID();
      $typoFormat = get_the_terms($post_id, 'formats');
      $formatURL = '';
      $formatName = '';
      if ($typoFormat && !is_wp_error($typoFormat)) {
        $formatID = $typoFormat[0]->term_id;
        $formatName = $typoFormat[0]->name;
        $formatURL = get_field('icon', 'formats_' . $formatID);
      }
      $html .= "
      <div class='dc__loop-card'>
        <div class='dc__card-content'>
          <img src='$formatURL' alt='$formatName' class='dc__icon-format' name='$formatName' width='54' height='54'>
          <h3 class='dc__card-title'>$title</h3>
          <p class='dc__card-info'>$info</p>
          <div class='dc__card-content'>$description</div>
          <a href='#' class='dc__card-button'>Card Link</a>
        </div>
      </div>";
    endwhile;
    wp_reset_postdata(); // Resetea los datos del post
    $html .= ob_get_clean();
  else : $html .= "<p class='loop__hidden'>No se encontraron resultados</p>";
  endif;
  return $html;
}
