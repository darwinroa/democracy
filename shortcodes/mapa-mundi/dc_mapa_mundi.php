<?php

/**
 * Crea el shortcode que imprime el mapa mundi
 * y el filtro por regiones y países
 * [dc_mapa_mundi]
 */
if (!function_exists('dc_mapa_mundi_function')) {
  add_shortcode('dc_mapa_mundi', 'dc_mapa_mundi_function');
  function dc_mapa_mundi_function($atts)
  {
    // Los atributos aceptados y sus valores predeterminados
    $attributes = shortcode_atts(
      array(
        'post_type'  => 'our_reach',
      ),
      $atts
    );

    $post_type = $attributes['post_type'];
    wp_enqueue_style('dc-mapa-mundi-style', get_stylesheet_directory_uri() . '/shortcodes/mapa-mundi/dc_mapa_mundi.css', array(), '1.0');
    wp_enqueue_script('dc-mapa-mundi-script', get_stylesheet_directory_uri() . '/shortcodes/mapa-mundi/dc_mapa_mundi.js', array('jquery'), null, true);
    wp_localize_script('dc-mapa-mundi-script', 'wp_ajax', array(
      'ajax_url'            => admin_url('admin-ajax.php'),
      'nonce'               => wp_create_nonce('load_more_nonce'),
      'theme_directory_uri' => get_stylesheet_directory_uri(),
      'post_type'           => $post_type,
    ));

    $args = array(
      'post_type' => $post_type,
    );
    $totalPost = dc_query_total_case_studies($args); // Retorna el total de posts relacionado a los argumentos enviados
    $mapaImg = dc_mapa_mundi_svg($post_type); // Retorna el SVG del mapa mundi
    $sidebarLocationList = dc_sidebar_location_list($post_type); // Retorna el listado de regiones que irá en el sidebar
    $countryOptions = dc_country_list($post_type); // Retorna el listado de Paises que irán en el select
    $button_ID = 'loadmore-members-countries';
    $buttonLoadMore = dc_html_loadmore_button($button_ID);
    $sidebarTitle = $post_type === 'our_reach' ? 'Global members' : 'Global Case Studies';
    $topText = $post_type === 'our_reach' ? 'To view our members across organizations and individuals' : 'To view the case studies';
    ob_start();
    $html = '';
    $html .= "
      <div id='dc__case_studies-section'>
        <div class='dc__case_studies-header'>
          <h5 id='dc__header-total-members' class='dc__header-total-members $post_type'>$topText</h5>
          <h3 id='dc__header-country' class='dc__header-country'>Select a country</h3>
        </div>
        $mapaImg
        <div class='dc__content-loop'>
          <div class='dc__sidebar-filter'>
            <div class='dc__sidebar-title'>$sidebarTitle</div>
            <ul class='dc__sidebar-locations'>
              <li class='dc__sidebar-location'>
                <span class='dc__location-count'>$totalPost</span>
                <h3 data-country='' class='dc__location-title'>Members worldwide</h3>
              </li>
              $sidebarLocationList
            </ul>
          </div>
          <div class='dc__content-body'>
            <div class='dc__content-head'>
              <select name='dc-country-select' id='dc-country-select' class='dc__country-select'>
                $countryOptions
              </select>
            </div>
            <div class='dc__content-loop-grid'></div>
            $buttonLoadMore
          </div>
        </div>
      </div>
    ";
    return $html;
  }
}

/**
 * Retorna el listado de regiones que irá en el sidebar
 */
function dc_sidebar_location_list($post_type)
{
  $isParent = true;
  $parent_locations = dc_get_locations($isParent); // Retorna las localizaciones padres.
  $html = '';
  if (!empty($parent_locations)) {
    foreach ($parent_locations as $location) {
      $html .= "";
      $args = array(
        'post_type' => $post_type,
        'tax_query'     => array(
          array(
            'taxonomy'  => 'locations',
            'field'     => 'slug',
            'terms'     => $location->slug
          )
        )
      );
      $colorItem = get_color($location->slug);
      $totalPost = dc_query_total_case_studies($args); // Retorna el total de posts relacionado a los argumentos enviados
      if ($totalPost === 0) continue; // No imprime las regiones que no contenga ningún post agregado
      $html .= "
            <li class='dc__sidebar-location dc__hide'>
              <span class='dc__location-count' style='background-color: $colorItem'>$totalPost</span>
              <h3 data-countryid='$location->term_id' data-country='$location->slug' class='dc__location-title'>$location->name</h3>
            </li>
          ";
    }
  }
  return $html;
}

/**
 * Retorna el listado de Paises que irán en el select
 */
function dc_country_list($post_type, $isAjax = false, $parentId = '')
{
  $child_locations = $isAjax ? dc_get_child_locations($parentId) : dc_get_locations(false);
  $html = "<option value='' class='dc__country-option' selected>Select a Country</option>";
  // Imprimir los nombres de las categorías hijas
  if (!empty($child_locations)) {
    foreach ($child_locations as $location) {
      $args = array(
        'post_type' => $post_type,
        'tax_query'     => array(
          array(
            'taxonomy'  => 'locations',
            'field'     => 'slug',
            'terms'     => $location->slug
          )
        )
      );
      $totalPost = dc_query_total_case_studies($args); // Retorna el total de posts relacionado a los argumentos enviados
      if ($totalPost === 0) continue; // No imprime las regiones que no contenga ningún post agregado
      $html .= "<option value='$location->slug' data-countryselect='$location->name' class='dc__country-option'>$location->name</option>";
    }
  }
  return $html;
}

/**
 * Retorna las localizaciones padre o hijos. 
 * Recibe un parámetro booleano.
 * Este parámetro se usa para indicar si retorna los valores padres de la taxonomía o los hijos
 */
function dc_get_locations($isParent)
{
  // Argumentos para obtener términos de la taxonomía 'locations'
  $args = array(
    'taxonomy'   => 'locations',
    'hide_empty' => true,
    'parent'     => 0, // Solo términos padres
  );

  // Obtener los términos de la taxonomía 'locations' que sean padres
  $parent_terms = get_terms($args);

  // Verificar si se obtuvieron términos
  if (!is_wp_error($parent_terms) && !empty($parent_terms)) {
    if ($isParent) return $parent_terms;

    $child_terms = array();
    // Recorrer cada término padre y obtener sus hijos
    foreach ($parent_terms as $parent) {
      $children = dc_get_child_locations($parent->term_id);
      // Agregar los términos hijos al array de términos hijos
      if (!is_wp_error($children) && !empty($children)) {
        $child_terms = array_merge($child_terms, $children);
      }
    }

    // Ordenar alfabéticamente por el nombre del término
    usort($child_terms, function ($a, $b) {
      return strcmp($a->name, $b->name);
    });

    return $child_terms;
  }
  // Retornar un array vacío si no hay términos padres o si ocurre un error
  return array();
}


function dc_get_child_locations($parentId)
{
  $child_args = array(
    'taxonomy'   => 'locations',
    'hide_empty' => true,
    'parent'     => $parentId, // Solo términos hijos de este padre
  );

  // Obtener los términos hijos del padre actual
  $children = get_terms($child_args);
  return $children;
}

/**
 * Retorna el total de posts relacionado al query. 
 * El parámetro $args, son los argumentos necesarios del query
 */
function dc_query_total_case_studies($args)
{
  $query = new WP_Query($args);
  $totalPost = $query->found_posts;
  return $totalPost;
}

/**
 * Retorna el HTML del loop de cards relacionadas a un país.
 * El parámetro $args, son los argumentos necesarios del query.
 */
function dc_query_case_studies_loop($args)
{
  $query = new WP_Query($args);
  $post_type = $args['post_type'];
  $html = "";
  if ($query->have_posts()) :
    ob_start();
    while ($query->have_posts()) : $query->the_post();
      $title = get_the_title();
      $description = get_the_content();
      $term = get_the_terms(get_the_ID(), 'locations');
      $locationField = get_field('mapa_location');
      $location = empty($locationField) ? esc_html($term[0]->name) : $locationField;
      $locationRegion = parent_continent_by_slug_country($term[0]->slug);
      $colorBorder = get_color($locationRegion);
      $img = get_the_post_thumbnail(
        null,
        'medium',
        array(
          'class'   => 'dc__card-logo',
          'width'   => 300,
          'height'  => 300,
        )
      );
      // Genera el HTML de los links dependiendo del post type
      if ($post_type === 'our_reach') {
        $linkText = get_field('case_study_text_link');
        $url = get_field('case_study_link_web');
        $html_links = "<a href='$url' target='_blank' rel='noopener noreferrer' class='dc__card-link'>$linkText</a>";
      } else {
        $urlDownload = get_field('pdf_case_studies');
        $urlMoreInformation = get_field('more_information_link_case_studies');
        $html_links = $urlDownload ? "<a href='$urlDownload' target='_blank' rel='noopener noreferrer' class='dc__card-link'>Download</a>" : '';
        $html_links .= $urlMoreInformation ? "<a href='$urlMoreInformation' target='_blank' rel='noopener noreferrer' class='dc__card-link'>View more</a>" : '';
      }

      $html .= "
      <div class='dc__loop-card' style='border-top-color: $colorBorder'>
        <div class='dc__card-content'>
          $img
          <h3 class='dc__card-title'>$title</h3>
          <div class='dc__card-location'>$location</div>
          <p class='dc__card-description'>$description</p>
          <div class='dc__card-links'>
            $html_links
          </div>
        </div>
      </div>";
    endwhile;
    wp_reset_postdata(); // Resetea los datos del post
    $html .= ob_get_clean();
  else : $html .= "<div class='dc__without-results'>No more results</div>";
  endif;
  return $html;
}

/**
 * Función Ajax para el grid de casos de estudios
 */
if (!function_exists('dc_case_study_ajax')) {
  add_action('wp_ajax_nopriv_dc_case_study_ajax', 'dc_case_study_ajax');
  add_action('wp_ajax_dc_case_study_ajax', 'dc_case_study_ajax');

  function dc_case_study_ajax()
  {
    check_ajax_referer('load_more_nonce', 'nonce');
    $slugCountry = isset($_POST['slugCountry']) ? sanitize_text_field($_POST['slugCountry']) : false;
    $postType = sanitize_text_field($_POST['postType']);
    $page = $_POST['page'];

    /**
     * Construyendo los argumentos necesarios para el Query
     */
    $tax_query = array();
    $tax_query[] =  array(
      'taxonomy' => 'locations',
      'field' => 'slug',
      'terms' => $slugCountry,
    );
    $post_per_page = 9;

    $slugCountry ?
      $args = array(
        'post_type' => $postType,
        'posts_per_page' => $post_per_page,
        'tax_query' => $tax_query,
        'paged' => $page,
      ) :
      $args = array(
        'post_type' => $postType,
        'posts_per_page' => $post_per_page,
        'paged' => $page,
      );
    $query_loop = dc_query_case_studies_loop($args);
    $html = $query_loop;

    wp_send_json_success($html);
    wp_die();
  }
}

/**
 * Función Ajax para listar los países que se muestran en el select
 * luego de presionar sobre una localización del sidebar
 */
if (!function_exists('dc_options_countries_ajax')) {
  add_action('wp_ajax_nopriv_dc_options_countries_ajax', 'dc_options_countries_ajax');
  add_action('wp_ajax_dc_options_countries_ajax', 'dc_options_countries_ajax');

  function dc_options_countries_ajax()
  {
    check_ajax_referer('load_more_nonce', 'nonce');
    $idCountry = isset($_POST['idCountry']) ? sanitize_text_field($_POST['idCountry']) : false;
    $post_type = sanitize_text_field($_POST['postType']);

    // si idCountry tiene valor, dc_country_list carga los países hijos al parent proveniente de idCountry
    // Si no tiene valor, entonces dc_country_list lista a todos los países
    $html = $idCountry ? dc_country_list($post_type, true, $idCountry) : dc_country_list($post_type, $idCountry);

    wp_send_json_success($html);
    wp_die();
  }
}

/**
 * Retorna el slug del término padre de la taxonomía locations
 * De esta manera saber a que continente pertenece el slug de un país
 */
function parent_continent_by_slug_country($countrySlug)
{
  $taxonomy = 'locations';

  // Obtiene el término hijo por su slug.
  $child_term = get_term_by('slug', $countrySlug, $taxonomy);

  if ($child_term && !is_wp_error($child_term)) {
    // Obtiene el ID del término padre.
    $parent_id = $child_term->parent;

    if ($parent_id) {
      // Obtiene el término padre por su ID.
      $parent_term = get_term($parent_id, $taxonomy);

      if ($parent_term && !is_wp_error($parent_term)) {
        // Retorna el término padre.
        return $parent_term->slug;
      }
    }
  }
  return '';
}

/**
 * Retorna el color correspondiente a la región
 */
function get_color($location)
{
  switch ($location) {
    case 'africa':
      return '#F9CD32';
    case 'asia':
      return '#F76F67';
    case 'australia':
      return '#03CCCC';
    case 'europe':
      return '#4AC3E9';
    case 'latin-america':
      return '#40CF99';
    case 'north-america':
      return '#7774ED';
    default:
      return '';
  }
}

/**
 * Retorna el SVG del mapa mundi
 */
function dc_mapa_mundi_svg($post_type)
{
  // Ruta del archivo SVG
  $svgFile = get_stylesheet_directory_uri() . '/inc/img/mapa-mundi-' . $post_type . '.svg';

  $svgContent = file_get_contents($svgFile);

  // Imprimir el contenido del archivo SVG
  $html = $svgContent;

  return $html;
}
