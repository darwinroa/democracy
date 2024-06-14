<?php

use function PHPSTORM_META\type;

if (!function_exists('dc_libraries_function')) {
  add_shortcode('dc_libraries', 'dc_libraries_function');

  function dc_libraries_function()
  {
    wp_enqueue_style('dc-library-style', get_stylesheet_directory_uri() . '/shortcodes/library/dc_library.css', array(), '1.0');
    wp_enqueue_script('dc-library-script', get_stylesheet_directory_uri() . '/shortcodes/library/dc_library.js', array('jquery'), null, true);
    wp_localize_script('dc-library-script', 'wp_ajax', array(
      'ajax_url'            => admin_url('admin-ajax.php'),
      'nonce'               => wp_create_nonce('load_more_nonce'),
      'theme_directory_uri' => get_stylesheet_directory_uri(),
    ));
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
        'name' => 'Year',
        'order' => 'DESC'
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
    $form_ID = 'filter-libraries';
    $html .= "<div class='dc__content-loop'>";
    $html .= dc_html_filter_form($taxonomies, $form_ID);
    $html .= "<div class='dc__content-loop-grid'>";
    /**
     * Aquí se optiene el Loop inicial al momento de cargar la web
     * Lo que se necesita es crear un array con los argumentos necesarios para el Query
     * Luego estos argumentos son enviados a la función dc_query_members_loop() 
     * Esta función es la encargada de retornar el loop con los argumentos necesarios
     */
    $post_per_page = 3;
    $args = array(
      'post_type' => 'library',
      'posts_per_page' => $post_per_page
    );
    $query_loop = dc_query_libraries_loop($args);
    $html .= $query_loop[0];
    $total_post = $query_loop[1];
    $html .= "</div>";
    $button_ID = 'loadmore-libraries';
    $show_hide_button = dc_show_loadmore_button($total_post, $post_per_page, 1); // Retorna true / false para mostrar o no el botón de load more
    $html .= $show_hide_button ? dc_html_loadmore_button($button_ID) : '';
    $html .= "</div></div>";
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
  $total_post = $query->found_posts;
  $html = "";
  if ($query->have_posts()) :
    ob_start();
    while ($query->have_posts()) : $query->the_post();
      $title = get_the_title();
      $info = get_field('informacion_extra');
      $description = get_the_content();
      $post_id = get_the_ID();
      $typeFormat = get_the_terms($post_id, 'formats'); // Importante para mostrar el ícono en función del tipo de formato
      $formatURL = '';
      $formatName = '';
      if ($typeFormat && !is_wp_error($typeFormat)) {
        $formatID = $typeFormat[0]->term_id;
        $formatName = $typeFormat[0]->name;
        $formatURL = get_field('icon', 'formats_' . $formatID);
      }
      $htmlLink = dc_html_card_btn($typeFormat);
      $html .= "
      <div class='dc__loop-card'>
        <div class='dc__card-content'>
          <img src='$formatURL' alt='$formatName' class='dc__icon-format' name='$formatName' width='54' height='54'>
          <h3 class='dc__card-title'>$title</h3>
          <p class='dc__card-info'>$info</p>
          <div class='dc__card-content'>$description</div>
          $htmlLink
        </div>
      </div>";
    endwhile;
    wp_reset_postdata(); // Resetea los datos del post
    $html .= ob_get_clean();
  else : $html .= "<div class='dc__without-results'>No se encontraron resultados</div>";
  endif;
  return array($html, $total_post);
}

/**
 * Retorna el HTML del link del post, ya sea para ver 
 * un video, descargar un pdf o enviar a una página externa
 */
function dc_html_card_btn($typeFormat)
{
  $formatSlug = $typeFormat[0]->slug;
  if ($formatSlug === 'video') return "<button id='dc_video_pop_up' class='dc__card-link'>Watch video</button>";
  $materialLectura = get_field('material_de_lectura');
  $isExternalLink = $materialLectura['pdf__link'];
  $url = $isExternalLink ? $materialLectura['link_externo'] : $materialLectura['pdf'];
  $name = $isExternalLink ? 'Read' : 'Download';
  return "<a href='$url' target='_blank' rel='noopener noreferrer' class='dc__card-link'>$name</a>";
}

/**
 * Función para la respuesta del Ajax
 */
if (!function_exists('dc_library_ajax_filter')) {
  add_action('wp_ajax_nopriv_dc_library_ajax_filter', 'dc_library_ajax_filter');
  add_action('wp_ajax_dc_library_ajax_filter', 'dc_library_ajax_filter');

  function dc_library_ajax_filter()
  {
    check_ajax_referer('load_more_nonce', 'nonce');
    $page = $_POST['page'];
    $formats = isset($_POST['formats']) ? sanitize_text_field($_POST['formats']) : '';
    $authors = isset($_POST['authors']) ? sanitize_text_field($_POST['authors']) : '';
    $years = isset($_POST['years']) ? sanitize_text_field($_POST['years']) : '';
    $languages = isset($_POST['languages']) ? sanitize_text_field($_POST['languages']) : '';
    $topics = isset($_POST['topics']) ? sanitize_text_field($_POST['topics']) : '';

    /**
     * Construyendo los argumentos necesarios para el Query
     */
    $tax_query = array('relation' => 'AND');
    if ($formats) {
      $tax_query[] =  array(
        'taxonomy' => 'formats',
        'field' => 'term_id',
        'terms' => intval($formats)
      );
    }
    if ($authors) {
      $tax_query[] =  array(
        'taxonomy' => 'authors',
        'field' => 'term_id',
        'terms' => intval($authors)
      );
    }
    if ($years) {
      $tax_query[] =  array(
        'taxonomy' => 'years',
        'field' => 'term_id',
        'terms' => intval($years)
      );
    }
    if ($languages) {
      $tax_query[] =  array(
        'taxonomy' => 'languages',
        'field' => 'term_id',
        'terms' => intval($languages)
      );
    }
    if ($topics) {
      $tax_query[] =  array(
        'taxonomy' => 'topics',
        'field' => 'term_id',
        'terms' => intval($topics)
      );
    }
    $post_per_page = 3;
    $args = array(
      'post_type' => 'library',
      'posts_per_page' => $post_per_page,
      'tax_query' => $tax_query,
      'paged' => $page
    );
    $query_loop = dc_query_libraries_loop($args);
    $html = $query_loop[0];

    wp_send_json_success($html);
    wp_die();
  }
}
