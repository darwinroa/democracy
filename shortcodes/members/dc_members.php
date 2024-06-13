<?php
if (!function_exists('dc_members_function')) {
  add_shortcode('dc_members', 'dc_members_function');

  function dc_members_function()
  {
    wp_enqueue_style('dc-members-style', get_stylesheet_directory_uri() . '/shortcodes/members/dc_members.css', array(), '1.0');
    wp_enqueue_script('dc-members-script', get_stylesheet_directory_uri() . '/shortcodes/members/dc_members.js', array('jquery'), null, true);
    wp_localize_script('dc-members-script', 'wp_ajax', array(
      'ajax_url'          => admin_url('admin-ajax.php'),
      'nonce'             => wp_create_nonce('load_more_nonce'),
    ));
    ob_start();
    $html = '';
    $html .= "<div id='dc__members-section'>";
    /**
     * Se crea un array con las taxonomías que se requieren para crear los filtros 
     * Este array se envía luego a la función dc_html_filter_form() que se encarga de retornar los filtros
     */
    $taxonomies = array(
      array(
        'slug' => 'type_member',
        'name' => 'Member Type'
      ),
      array(
        'slug' => 'region',
        'name' => 'Region'
      ),
      array(
        'slug' => 'field_of_work',
        'name' => 'Field of work'
      )
    );
    $form_ID = 'filter-members';
    $html .= dc_html_filter_form($taxonomies, $form_ID);
    $html .= "<div class='dc__content-loop'>";
    $html .= "<h2 class='dc__content-loop-title'>Organizaciones</h2>";
    $html .= "<div class='dc__content-loop-grid'>";
    /**
     * Aquí se optiene el Loop inicial al momento de cargar la web
     * Lo que se necesita es crear un array con los argumentos necesarios para el Query
     * Luego estos argumentos son enviados a la función dc_query_members_loop() 
     * Esta función es la encargada de retornar el loop con los argumentos necesarios
     */
    $post_per_page = 4;
    $args = array(
      'post_type' => 'members',
      'posts_per_page' => $post_per_page
    );
    $query_loop = dc_query_members_loop($args);
    $html .= $query_loop[0];
    $total_post = $query_loop[1];
    $html .= "</div>";
    $button_ID = 'loadmore-members';
    $show_hide_button = dc_show_loadmore_button($total_post, $post_per_page, 1); // Retorna true / false para mostrar o no el botón de load more
    $html .= $show_hide_button ? dc_html_loadmore_button($button_ID) : '';
    $html .= "</div></div>";
    return $html;
  }
}

/**
 * Retorna el HTML del loop para la sección de miembros
 * $args son los argumentos necesarios para el loop
 */
function dc_query_members_loop($args)
{
  $query = new WP_Query($args);
  $total_post = $query->found_posts;
  $html = "";
  if ($query->have_posts()) :
    ob_start();
    while ($query->have_posts()) : $query->the_post();
      $html .= do_shortcode('[INSERT_ELEMENTOR id="1112"]');
    endwhile;
    wp_reset_postdata(); // Resetea los datos del post
    $html .= ob_get_clean();
  else : $html .= "<div class='dc__without-results'>No se encontraron resultados</div>";
  endif;
  return array($html, $total_post);
}

/**
 * Función para la respuesta del Ajax
 */
if (!function_exists('dc_member_ajax_filter')) {
  add_action('wp_ajax_nopriv_dc_member_ajax_filter', 'dc_member_ajax_filter');
  add_action('wp_ajax_dc_member_ajax_filter', 'dc_member_ajax_filter');

  function dc_member_ajax_filter()
  {
    check_ajax_referer('load_more_nonce', 'nonce');
    $page = $_POST['page'];
    $member_type = isset($_POST['memberType']) ? sanitize_text_field($_POST['memberType']) : '';
    $region = isset($_POST['region']) ? sanitize_text_field($_POST['region']) : '';
    $field_of_work = isset($_POST['fieldWork']) ? sanitize_text_field($_POST['fieldWork']) : '';

    /**
     * Construyendo los argumentos necesarios para el Query
     */
    $tax_query = array('relation' => 'AND');
    if ($member_type) {
      $tax_query[] =  array(
        'taxonomy' => 'type_member',
        'field' => 'term_id',
        'terms' => intval($member_type)
      );
    }
    if ($region) {
      $tax_query[] =  array(
        'taxonomy' => 'region',
        'field' => 'term_id',
        'terms' => intval($region)
      );
    }
    if ($field_of_work) {
      $tax_query[] =  array(
        'taxonomy' => 'field_of_work',
        'field' => 'term_id',
        'terms' => intval($field_of_work)
      );
    }
    $post_per_page = 4;
    $args = array(
      'post_type' => 'members',
      'posts_per_page' => $post_per_page,
      'tax_query' => $tax_query,
      'paged' => $page
    );
    $query_loop = dc_query_members_loop($args);
    $html = $query_loop[0];

    wp_send_json_success($html);
    wp_die();
  }
}

/**
 * Se requeire este shortcode para agregarlo en el template de elementor para que se imprima el Custom field de País
 */
add_shortcode('get_member_country', 'get_member_country_function');
function get_member_country_function()
{
  return get_field('country');
}
