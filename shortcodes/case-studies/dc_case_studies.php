<?php

/**
 * Crea el shortcode que imprime el mapa mundi
 * y el filtro por regiones y países
 * [dc_case_studies]
 */
if (!function_exists('dc_case_studies_function')) {
  add_shortcode('dc_case_studies', 'dc_case_studies_function');
  function dc_case_studies_function()
  {
    wp_enqueue_style('dc-case-studies-style', get_stylesheet_directory_uri() . '/shortcodes/case-studies/dc_case_studies.css', array(), '1.0');
    wp_enqueue_script('dc-case-studies-script', get_stylesheet_directory_uri() . '/shortcodes/case-studies/dc_case_studies.js', array('jquery'), null, true);
    wp_localize_script('dc-case-studies-script', 'wp_ajax', array(
      'ajax_url'            => admin_url('admin-ajax.php'),
      'nonce'               => wp_create_nonce('load_more_nonce'),
      'theme_directory_uri' => get_stylesheet_directory_uri(),
    ));
    $args = array(
      'post_type' => 'case_studies',
    );
    $totalPost = dc_query_total_case_studies($args); // Retorna el total de posts relacionado a los argumentos enviados
    $mapaImg = dc_mapa_mundi_svg(); // Retorna el SVG del mapa mundi
    $sidebarLocationList = dc_sidebar_location_list(); // Retorna el listado de regiones que irá en el sidebar
    $countryOptions = dc_country_list(); // Retorna el listado de Paises que irán en el select
    ob_start();
    $html = '';
    $html .= "
      <div id='dc__case_studies-section'>
        <div class='dc__case_studies-header'>
          <h5 id='dc__header-total-members' class='dc__header-total-members'>To view our members across organizations and individuals</h5>
          <h3 id='dc__header-country' class='dc__header-country'>Select a country</h3>
        </div>
        $mapaImg
        <div class='dc__content-loop'>
          <div class='dc__sidebar-filter'>
            <div class='dc__sidebar-title'>Global members</div>
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
function dc_sidebar_location_list()
{
  $isParent = true;
  $parent_locations = dc_get_locations($isParent); // Retorna las localizaciones padres.
  $html = '';
  if (!empty($parent_locations)) {
    foreach ($parent_locations as $location) {
      $html .= "";
      $args = array(
        'post_type' => 'case_studies',
        'tax_query'     => array(
          array(
            'taxonomy'  => 'locations',
            'field'     => 'slug',
            'terms'     => $location->slug
          )
        )
      );
      $totalPost = dc_query_total_case_studies($args); // Retorna el total de posts relacionado a los argumentos enviados
      $html .= "
            <li class='dc__sidebar-location dc__hide'>
              <span class='dc__location-count'>$totalPost</span>
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
function dc_country_list($isAjax = false, $parentId = '')
{
  $child_locations = $isAjax ? dc_get_child_locations($parentId) : dc_get_locations(false);
  $html = "<option value='' class='dc__country-option' selected>Select a Country</option>";
  // Imprimir los nombres de las categorías hijas
  if (!empty($child_locations)) {
    foreach ($child_locations as $location) {
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
    'hide_empty' => false,
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

    return $child_terms;
  }
  // Retornar un array vacío si no hay términos padres o si ocurre un error
  return array();
}

function dc_get_child_locations($parentId)
{
  $child_args = array(
    'taxonomy'   => 'locations',
    'hide_empty' => false,
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
  $html = "";
  if ($query->have_posts()) :
    ob_start();
    while ($query->have_posts()) : $query->the_post();
      $title = get_the_title();
      $linkText = get_field('case_study_text_link');
      $url = get_field('case_study_link_web');
      $description = get_the_content();
      $term = get_the_terms(get_the_ID(), 'locations');
      $location = esc_html($term[0]->name);
      $img = get_the_post_thumbnail(
        null,
        'medium',
        array(
          'class'   => 'dc__card-logo',
          'width'   => 300,
          'height'  => 300,
        )
      );
      $html .= "
      <div class='dc__loop-card'>
        <div class='dc__card-content'>
          $img
          <h3 class='dc__card-title'>$title</h3>
          <div class='dc__card-location'>$location</div>
          <p class='dc__card-description'>$description</p>
          <a href='$url' target='_blank' rel='noopener noreferrer' class='dc__card-link'>$linkText</a>
        </div>
      </div>";
    endwhile;
    wp_reset_postdata(); // Resetea los datos del post
    $html .= ob_get_clean();
  else : $html .= "<div class='dc__without-results'>No se encontraron resultados</div>";
  endif;
  return $html;
}

/**
 * Retorna un array donde lista cuantos miembros hay por cada país
 */
// if (!function_exists('dc_country_and_total_members_data_ajax')) {
//   add_action('wp_ajax_nopriv_dc_country_and_total_members_data_ajax', 'dc_country_and_total_members_data_ajax');
//   add_action('wp_ajax_dc_country_and_total_members_data_ajax', 'dc_country_and_total_members_data_ajax');

//   function dc_country_and_total_members_data_ajax()
//   {
//     check_ajax_referer('load_more_nonce', 'nonce');
//     $data = array();
//     $child_locations = dc_get_locations(false);
//     if (!empty($child_locations)) {
//       foreach ($child_locations as $location) {
//         $dataSlug = $location->slug;
//         $argsCounter = array(
//           'post_type' => 'case_studies',
//           'tax_query'     => array(
//             array(
//               'taxonomy'  => 'locations',
//               'field'     => 'slug',
//               'terms'     => $dataSlug
//             )
//           )
//         );
//         $totalMembers = dc_query_total_case_studies($argsCounter);
//         $data[] = array(
//           'slug' => $dataSlug,
//           'total_members' => $totalMembers
//         );
//       }
//     }
//     wp_send_json_success($data);
//     wp_die();
//   }
// }

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

    /**
     * Construyendo los argumentos necesarios para el Query
     */
    $tax_query = array();
    $tax_query[] =  array(
      'taxonomy' => 'locations',
      'field' => 'slug',
      'terms' => $slugCountry,
    );
    $post_per_page = 6;

    $slugCountry ?
      $args = array(
        'post_type' => 'case_studies',
        'posts_per_page' => $post_per_page,
        'tax_query' => $tax_query,
      ) :
      $args = array(
        'post_type' => 'case_studies',
        'posts_per_page' => $post_per_page,
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

    // si idCountry tiene valor, dc_country_list carga los países hijos al parent proveniente de idCountry
    // Si no tiene valor, entonces dc_country_list lista a todos los países
    $html = $idCountry ? dc_country_list(true, $idCountry) : dc_country_list($idCountry);

    wp_send_json_success($html);
    wp_die();
  }
}

/**
 * Retorna el SVG del mapa mundi
 */
function dc_mapa_mundi_svg()
{
  return '
    <?xml version="1.0" encoding="utf-8"?>
    <!-- Generator: Adobe Illustrator 27.9.4, SVG Export Plug-In . SVG Version: 9.03 Build 54784)  -->
    <svg version="1.1" id="mapa-mundi" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
      viewBox="0 0 2500 1600" style="enable-background:new 0 0 2500 1600;" xml:space="preserve">
      <style type="text/css">
        .st0{fill:#E5E5E5;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st1{fill:#F9DC5B;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st2{fill:#FAE585;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st3{fill:#F9CD32;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st4{fill:#FAE170;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st5{fill:#03CCCC;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st6{fill:#68E0E0;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st7{fill:#F76F66;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st8{fill:#4AC3E9;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st9{fill:#92DBF2;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st10{fill:#6ECFED;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st11{fill:#B7E7F6;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st12{fill:#8CE2C2;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st13{fill:#66D9AD;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st14{fill:#40CF99;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st15{fill:#B3ECD6;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st16{fill:#9290F1;stroke:#F7F7F7;stroke-miterlimit:10;}
        .st17{fill:#7774ED;stroke:#F7F7F7;stroke-miterlimit:10;}
      </style>
      <g id="cyprus" data-country="Cyprus">
        <path class="st0" d="M1433.9,806c-0.7-0.8-2.4,0.4-3.2,0.7c-3.3,1.5-7-0.2-10.1,1.1c-0.9,1.2,0.2,3.4-2.3,3
          c-4.1-0.2-4.2,4.5-0.1,4.1c2.3,2.4,9.5-1.2,11.7-3.2c1.7-2.4,0.1-3.1,4.1-4.7C1433.8,806.7,1434.1,806.4,1433.9,806L1433.9,806z"/>
      </g>
      <g id="the-gambia" data-country="The Gambia">
        <path class="st0" d="M1100,965c-0.9-4.5-9.1-1.7-15.1-1.1c0.8,1.4,1.6,3.1,2.5,4.3C1094.1,968,1102.3,968.8,1100,965z"/>
      </g>
      <g id="senegal" data-country="Senegal">
        <path class="st1" d="M1127.6,978.6c-5.3-13.3-2.4-14.4-14.8-24.7c-2.6-8.2-17-4.2-23.1-5.1c-0.9,4.7-1.7,10.6-6.8,12.3
          c0.8,0.8,1.4,1.8,2,2.8c6-0.6,14.2-3.4,15.1,1.1c2.3,3.8-5.9,3-12.6,3.2c1.7,2.3,3.1,4.9,4.6,7.3c11.1-0.7,9.1-2.5,18,5.1
          c2.1-0.3,17.4,0.1,19.2-0.3C1129.2,980.3,1127.8,979.1,1127.6,978.6z"/>
      </g>
      <g id="guinea-bissau" data-country="Guinea Bissau">
        <path class="st0" d="M1107.3,983.7c1-0.9,2.5-1.7,2.6-3.1c-9.1-7.8-6.8-5.6-18.1-5.1c3.2,4.2,6.6,8.5,8.6,13.5
          C1103,987.7,1105.2,985.6,1107.3,983.7L1107.3,983.7z"/>
      </g>
      <g id="ginea" data-country="Ginea">
        <path class="st0" d="M1150.6,1011.8c-1.5-2.1-0.9-4.7-1.6-7.1c-1.8-2.7-0.6-5.5-0.8-8.6c-1.5-6.7-7.5-12-8.9-19
          c-0.1,0-0.2,0-0.3-0.1c-8,5-20.1,3-28.9,3.5c-1.9,3.4-6.1,6.2-9.4,8.4c1.5,5.1-0.2,11.7,5.4,14.7c28.9-19.9,19.5-0.6,37.3,14.5
          c2.6-1.8,4.7-4.1,7.1-6.1C1150.5,1012.1,1150.6,1012,1150.6,1011.8L1150.6,1011.8z"/>
      </g>
      <g id="sierra-leone" data-country="Sierra Leone">
        <path class="st0" d="M1130.2,1013.6c1.5-1.4,3.2-2.5,4.8-3.6c0,0,0.1,0,0.1-0.1c-7.7-20.1-13.4-16.3-29.3-6
          c6.9,4.5,11.4,11.6,17.8,16.7C1125.8,1018.2,1127.9,1015.8,1130.2,1013.6L1130.2,1013.6z"/>
      </g>
      <g id="liberia" data-country="Liberia">
        <path class="st0" d="M1152.1,1028.5c-3.5-2.9-6.2-6.6-9-10.1c-2.4-2.8-5.6-5.5-7.9-8.5c0,0-0.1,0-0.1,0.1
          c-4.4,2.6-7.8,6.6-11.1,10.5c3.5,3.4,10.9,6.3,14.1,10.6c7.2,2.1,8.6,11.8,17.5,8.5v-9.7C1155,1029.4,1152.7,1029,1152.1,1028.5z"
          />
      </g>
      <g id="ivory-coast" data-country="Ivory Coast">
        <path class="st0" d="M1183.7,1017.2c0.9-2.1,2.3-4.5,3.9-6c0.5-4.2-2.2-8-1.5-12.3c-7-0.8-14.8,3.6-18.5-4.6
          c-2.5,2.8-5.7,2.5-8.7,1.3c-3.7-0.5-7.4,1-10.9,0.6c0.3,3.1-1,6,0.8,8.6c0.7,2.3,0.1,4.6,1.4,6.8c-0.7,2.6-5,4.9-7.1,6.9
          c3.6,4.3,6.8,9.9,12.5,11.5v9.7c9-1.2,18.9-5.3,27.8-2.3V1018C1183.4,1017.7,1183.5,1017.5,1183.7,1017.2L1183.7,1017.2z"/>
      </g>
      <g id="ghana" data-country="Ghana">
        <path class="st2" d="M1218.5,1023.5c-6.6-13.8-13.6-14.6-13.8-32.6c-4.5,1.6-11.8-0.8-14.7,3.1c-1.1,1.7-3.5,5.4-4.2,5.2
          c-0.7,4.4,2,8,1.5,12.3c-6.1,3.6-3.7,20.3-4.1,26.1c12.7,4.9,26.1-3,38.3-6.8C1220.6,1028.5,1219.5,1025.7,1218.5,1023.5
          L1218.5,1023.5z"/>
      </g>
      <g id="burkina-faso" data-country="Burkina Faso">
        <path class="st0" d="M1220.7,980.6c-6.4-4.5-10.8-12.4-14.9-19.1c-8.3,0.7-12.9,8.5-19.9,12c-1.2-0.6-2.6,0.1-3.8-0.3
          c-0.7-0.4-1.7-0.4-2.2,0.3c-5,6.1-8.1,13.6-11.9,20.6c0,0,0,0.1-0.1,0.1c3.7,8.2,11.6,3.8,18.5,4.6c1.4,0.1,3.9-5.4,5.4-6.9
          c8.6-0.5,17.3-2,26-2.6c1.9-2.1,7-4.6,8.7-7.2C1224.1,982,1221.9,981.2,1220.7,980.6L1220.7,980.6z"/>
      </g>
      <g id="togo" data-country="Togo">
        <path class="st0" d="M1227.6,1028.1c-5.1-7.9-7.4-16.9-8-26.5c-0.4-4.1-3.3-7.8-2.4-12.2c-4.2,0.3-8.4,0.7-12.5,1.2
          c0.8,6.2,0.9,13.5,4.5,18.8c6,5.6,9.3,13.3,12.5,20.8c2.3-0.7,3.6-1,5.9-0.9v-1.7C1227.7,1027.6,1227.6,1028.4,1227.6,1028.1
          L1227.6,1028.1z"/>
      </g>
      <g id="benin" data-country="Benin">
        <path class="st0" d="M1240.2,1002.2c-2.8-8.4-2-17.4-4-25.4c-2.5,2.3-6.6,6-10.1,5.8c-6.1,6.8-12,5.1-7.1,16.1
          c2.6,8.8,1,19.8,7.7,27.1c1.3,1.7,1.3,2.3,1.3,4.1c2.1,0.1,3.9,0,6-0.1C1231.9,1012.4,1240.3,1012.8,1240.2,1002.2L1240.2,1002.2z"
          />
      </g>
      <g id="nigeria" data-country="Nigeria">
        <path class="st1" d="M1319,973.5c-3.9-4.2-9.5-6.6-13.9-10.4c-1.7,3-2.3,5.7-3,8.9c0,0.7-1.4,2.9-3,2.2c-5.1,1.4-9.7,1.4-14.9,0.7
          c-5.9-0.1-9.8,6.1-16,4.1c-4.7-7.2-11.2-1.5-17.3-3c-3.8-1.4-8.1-1.5-12.1-2.1c-0.9,0.4-1.6,2.2-2.2,2.9c0,1.1,1.4,2.3,0.5,3.7
          c0,1.8,1.3,3.2,0.8,5.1c-0.9,6.8,3.3,13.3,2.5,20.1c-5,7.7-7.3,14.6-6.3,23.9c15.3,5.6,0,14.8,28.2,10.4c3-3.6,6.4-6.8,10.2-9.9
          c2.8-4,4.8-9.1,9.8-10.7c1.6,1.4,4.1,1.8,5.6,3.3c6.6-14.9,19.1-26.3,26.9-40.6c0-0.8,0.1-1.8-0.1-2.6c-0.1,0-0.1,0.1-0.2,0.1
          C1315.7,977.7,1321.5,976,1319,973.5z"/>
      </g>
      <g id="central-african-republic" data-country="Central African Republic">
        <path class="st0" d="M1408.1,1031.6c-3.3-8.1-11.8-13.1-16.5-20.2c-0.9-1-3.2,0.2-4.2,0.7c-1.4,0.8-1,0.2-1.7-0.8
          c-1.7-1.2-2.9-2.5-4.6-3.8c0-2.3,0.9-9.2,0.8-11.3c-1.7,0-2.6-1.4-3.5-2.6c-3.9-1.1-5.6-2.9-9.6-4.1c0.1,0.8,0.1,1.7,0.1,2.6
          c-12,7.7-6.3,13.9-21.4,14.2c-14.7,15.6-16.5,7.4-30.8,14.4c-3,9.3-2.6,14.9-2.9,23.7c0.6,3.4,2.7,6.9,5.5,9.1
          c0.5,0.9,0.7,1.9,2,1.9c1.2,0.3,1.3-0.2,2.3-1c5.3-6.1,13.2-4.6,20.4-4.7c-0.1-4.5,2.5-8.3,4.3-12.2c1.1-4.3,6.4-2.4,9.5-2.1
          c2.6,2,3.5,6.2,6.5,7.9c0.1-0.2,0.1-0.4,0-0.7c4.3,0,8.5-1.2,11.9-4c9.6-5.4,21-4.7,31.5-6.7
          C1407.6,1031.6,1408.1,1031.6,1408.1,1031.6L1408.1,1031.6z"/>
      </g>
      <g id="cameroon" data-country="Cameroon">
        <path class="st0" d="M1274.3,1071.7c2-1.2,8.6-10,11.3-9.1c7.8,0.7,15.3-4.2,20.6,3.5c3.6,0,8.4-0.5,11.6-1.8c3-2.3,2.8-5.6,2.9-9
          c-1.2-0.1-1.3-1-1.8-1.9c-2.9-2.2-4.8-5.6-5.5-9.1c0.3-8.9-0.1-14.4,2.9-23.7c-1.4,0,1.9-14.3-2.5-15.9c-3.1-0.5-2.8-4-4.2-6.1
          c-2-6.4,9.6,0.1,8.1-6c-0.9-3.6-3.1-6.9-3.2-10.7c-7.8,14.3-20.3,25.8-26.9,40.6c-1.4-1.5-4.1-1.9-5.6-3.3c-5,1.7-7.1,6.7-9.8,10.7
          c-3.6,3.2-7.6,6.1-10.6,9.8c11.9,2.5,2.2,24.2,1.3,32.3C1266.6,1071.8,1270.9,1072,1274.3,1071.7z"/>
      </g>
      <g id="equatorial-guinea" data-country="Equatorial Guinea">
        <path class="st0" d="M1274,1071.6c-3.5,0.1-7.4,0.3-10.8,0.5c-0.5,2.8-1,5.5-1.7,8.2C1267.8,1080.9,1274.5,1079.9,1274,1071.6z"/>
      </g>
      <g id="gabon" data-country="Gabon">
        <path class="st0" d="M1310.1,1068.3c-1.3-0.5-2.5-1.4-3.6-2.2c-0.1,0-0.1-0.1-0.2-0.1c-5.2-7.8-14-2.7-21.8-3.5
          c-1.4,0-9,8.3-10.1,9.1c-0.1,0-0.2,0-0.4,0c0.5,8.3-6.3,9.2-12.6,8.7c-4.4,14.9-6.7,13.1,2.1,26.8l13.9-0.4
          c5.5-5.7,11.8-10.9,18.5-15.2c2.6-0.4,4.3,2.5,6.8,2.8c4.1,0.7,3.7-4.1,5.7-6.4c3.5-4.5,0.9-9.5,1.9-14.5c0.4-1.7,0.7-3.4,0.8-5.1
          C1311.3,1067.5,1310.9,1068.6,1310.1,1068.3z"/>
      </g>
      <g id="republic-of-the-congo" data-country="Republic of the Congo">
        <path class="st0" d="M1334.9,1049.6c-7.1-1.5-8.8,4.4-14,5.6c1,10.3-6.3,10.6-14.5,10.8c0,0,0.1,0.1,0.2,0.1
          c1.2,0.8,2.3,1.7,3.6,2.2c0.8,0.3,1.2-0.8,1.1,0.1c-1.4,9,0.2,17.9-5.8,25.6c-2.8,1.3-5.3-0.9-7.6-2.2c-7.7,1.2-14.3,10.1-20.3,15
          l-13.9,0.4c9.3,9.9,30.6,6.5,42.9,6c2.2-0.8,5.1-1.6,6.9-3.3c0.8-2.6,3.4-4.2,3.9-6.8c0.6-3.6,2.7-6.3,2.3-10.2
          c2-4.8,7.5-7.3,10.3-11.2c1.7-4,2.6-8.1,2.5-12.5c0.7-7.8,8.2-13.1,11.8-19.7C1340.9,1049.4,1337.9,1049.5,1334.9,1049.6
          L1334.9,1049.6z"/>
      </g>
      <g id="madagascar" data-country="Madagascar">
        <path class="st0" d="M1555.5,1153c-0.7-3.9-2.2-7.3-3.6-11c0.5-2.2-1.5-3.8-3.6-3.3c-1.9,0.4-2,2.2-2.9,3.5c-0.9,1.4-2.9,2-3.7,3.7
          c-0.4,2.7-2.3,4.4-3.6,6.5c-0.1,3.1-1.4,5.2-3.2,7.4c0.2,1.3-0.8,1.7-1.8,2.2c-14,13.9-6.9,0.5-25,8.6c0.6,6.2-3.5,14.8,1.4,19.6
          c7.6,12,0.7,9.5-5.6,17.4c-5.9,6.1-1,12.7,3.1,18c2.9,5.7,3.6,12.4,7.1,17.7c2.6,0.4,7-1.7,8.7-3.5c0.1-0.9,0.5-1.6,1.5-1.8
          c6.4-3.8,7.1,0.1,11.1-8.7c1-9.2,3.3-18,8.8-25.5c0.1-2.9,2.1-5.5,3.1-8.2c1.2-5.6,1.1-11.3,2.3-16.9c1.3-2.7,1-5.8,1.4-8.7
          c0.7-1.7,1.9-1.5,3.2-2.3C1558.5,1164.3,1556.8,1157.6,1555.5,1153L1555.5,1153z"/>
      </g>
      <g id="mauritius" data-country="Mauritius">
        <path class="st1" d="M1600.7,1190.1c0.9-4.8-1.5-6.4-5.3-3.6c0.7,2.5-2.2,10.5,1.3,10C1600,1198.4,1602.3,1192.9,1600.7,1190.1z"/>
      </g>
      <g id="eswatini" data-country="Eswatini">
        <path class="st1" d="M1420,1269c-16.7,12.3-1.4,20.9,9.1,7.6c-3.8-2.4-4-6.4-3.6-10.5C1422.9,1266.4,1420.6,1267.1,1420,1269z"/>
      </g>
      <g id="lesotho" data-country="Lesotho">
        <path class="st2" d="M1400.7,1295.3c-6.4-2.1-7.5,1.4-12.2,5c-1.9,1.7-1.5,6-1.6,8.1c-1,3.6,0.2,6,4.2,5.6c3.8,1.2,5.7-2.6,7.7-5.2
          C1403.7,1304.6,1407.5,1299.7,1400.7,1295.3L1400.7,1295.3z"/>
      </g>
      <g id="south-africa" data-country="South Africa">
        <path class="st1" d="M1432.5,1279.1c-1.4-0.7-2.4-1.4-3.4-2.5c-4.6,8.2-21.5,10.1-15.1-2.6c4.4-1,5.7-7.9,11.5-7.9
          c0.4-7.4-0.4-15.1-4.2-21.5c0.4-0.5-19.7,1.3-19.7,1.2c-10.2,6.6-16.3,17.6-25.8,25.2c-15.3,1-13.7,0.5-21.6,13.4
          c-0.7,3.5-5.5,1.7-8.1,2.6c-2.9,1.1-4.2-1-3.6-3.7c-0.6-2.5-1.6-4.8-1.4-7.5c0.1-0.9,0.3-1.7-0.5-2.3c-0.5-1.4-2.2-1.7-3.8-2.1
          c0.7,7-2.1,13.6-3.8,20.1c-0.7,7.4-4.9,16.4-13.1,17.1c-4.3-1.7-6.6-9-11.2-3.5c-0.5,0.3-4.7,2.5-4,0.9c0.4,6.7,5.1,12.2,7,18.6
          c1.3,7.6,7.5,12.1,10.6,18.5c0.9,3.1-1.5,6.9-2,9.6c3.6,2.9,3.6,8.7,7.5,11.6c7.8,4.4,6.9-4.2,16.9-2.7c36.7-1,30.9-17.5,52.6-32.6
          c1.5-6.1,7.3-8.7,12.1-11.9c3.1-9.9,14-14,16.7-23.6c3.9-4.4-1-12.9,7-13.9C1432.9,1279.3,1432.7,1279.1,1432.5,1279.1z
          M1403.8,1302.5c-2.5,4.3-6.1,8.5-9.9,11.5c-4.4,0.4-8.9,0.1-7-5.6c-0.2-3.8,0.3-8.4,4.4-10.2
          C1394.3,1291.1,1407.7,1296.1,1403.8,1302.5z"/>
      </g>
      <g id="botswana" data-country="Botswana">
        <path class="st0" d="M1400.5,1243.8c-5.1-5.9-7.3-2.1-5.2-11.6c-2.4-3.5-5.5-6.5-7.7-10.2c-2.6-3.9-4.4-8.7-8.5-11.5
          c-0.1,0-0.1-0.1-0.2-0.1c-3.2-3.7-6.7,3.8-9.8,4.2c-3.8-2.7-8.5-4.5-11,1c-2.9,2.4-6.7,3.1-10.1,1l-2.1,28.2
          c-2.7,0.7-8.3,2.1-8.8,5.4c-1.1,7.6-1.5,14.2,0,21.5c4.7,0.2,4.4,4.2,4.5,7.7c0,1.6,1.2,2.6,1.2,4.2c0,1.1-0.3,3.5,0.9,3.9
          c3.1-0.1,6.6-0.6,9.7-0.9c2.9-4.5,5.4-9.5,9.1-13.6c2.6-1.2,10.7-1.1,13.6-1.7c9.6-7.5,15.5-18.6,25.8-25.2
          C1401.7,1245.1,1400.9,1244.2,1400.5,1243.8L1400.5,1243.8z"/>
      </g>
      <g id="namibia" data-country="Namibia">
        <path class="st0" d="M1346.8,1215.7c-4.8-3.2-11.4,0.9-15.1-3.8c-10.7-0.8-22.4,1.4-33.3,2.8c-1.4-0.1-1.6-1.7-2.7-2.3
          c-1-0.5-2.2-0.3-3.1-1.1c-2.2-2.3-4.8-3.8-8.1-2.9c-3.7,12.6,3.8,23.4,6.5,35.4c0.8,8.2,7.9,13.5,7.9,21.8
          c2.8,12.7,0.9,27.8,5.9,40.5l0.1-0.3c0.4,2.1,5.6-1.9,6.4-3c3.6-0.6,5.5,4.7,8.8,5.6c4.8-0.1,9.1-4.6,11.1-8.8
          c1.9-8.8,5.6-17.3,5.9-26.3c0.7-4-1.6-7.3-1.4-11.1c1-8.2-0.7-16.8,10.1-17.9l2.1-28.2C1347.6,1216.1,1347.2,1215.9,1346.8,1215.7
          L1346.8,1215.7z"/>
      </g>
      <g id="angola" data-country="Angola">
        <path class="st0" d="M1378.6,1209c-1.4-0.8-2-2-3-3.1c-4-1.5-9.5-0.8-13-3.7c-0.7-0.9-0.7-2.2-1.4-3.2c-5.3-3.3-2-9.9-2.2-15
          c-1.6-7.9,7.3-12.7,14.1-10.9l1.2-16.4c-3.2-0.2-6.8,1.4-9.8,1c-5.2-8-3.1-18.3-4.8-27.5c-6.3-0.3-16.3-6.1-19.7,2.2
          c-6.8,7.7-18.9-1.1-20.5-9.3l-25.8,1.6c6.3,11.7-4.4,36.7,5.1,43.2c-3.7,13.4-11.1,26.5-14.4,40.6c3.3-1.1,5.8,0.8,8.1,2.9
          c0.9,0.8,2.1,0.6,3.1,1.1c1.2,0.6,1.3,2.2,2.7,2.3c9.1-1.7,18.6-2.1,27.8-2.9c4.7-2.2,5.7,1.6,9.3,2.6c8.1-2.8,15.7,7.1,22.6,0.9
          c2.7-5.5,6.9-3.7,11-1c3-0.3,6.7-8,9.8-4.2c0.1,0,0.1,0.1,0.2,0.1C1379,1209.9,1378.9,1209.4,1378.6,1209L1378.6,1209z"/>
      </g>
      <g id="zambia" data-country="Zambia">
        <path class="st0" d="M1451.4,1142.9c-2.1-2.5-4.6-2.5-5.1-2.6c-12.8-4.9-25.8-6.4-29.7-5.2c-1.5-0.1-2.6-1.5-2.6,0.9
          c-3,6.8-2.8,15.1-3.7,22.5c2.7,1.9,5.8,3.8,8.7,5.3c0.6,2.1,0.2,8.1-3.3,7.1c-4.8-3.1-10.4-5.8-14.1-10.3
          c-6.3,1.2-11.7-1.5-17.7-3.2c-0.8-0.3-5.4,0.2-5.6-0.8c-1.3,0.1-2.6,0.2-3.9,0.3l-1.2,16.4c-6.8-1.7-15.7,2.9-14.1,10.9
          c-1,12.4-2.4,8.3,3.6,18.2c3.5,2.9,9,2.1,13,3.7c4.7,8.7,14.4,4.7,22.6,2.6c2.8-8.8,9.6-16.1,18.2-19c3.1-6.3,11.2-7.8,16.8-11.6
          c1-1.2,2.4-2.2,3.2-3.4c4.7-7.3,7.8-9.9,11.1-11.5c1.2-0.6,3.8-1.7,3.8-1.7s-0.3-3-0.4-4c2.1-0.4,4.5-0.7,4.5-0.7
          c0,0-1.8-8.3-2.3-10.2L1451.4,1142.9z"/>
      </g>
      <g id="zimbabwe" data-country="Zimbabwe">
        <path class="st0" d="M1437.9,1198.9c-3.1-1.2-6-3-9-4.4c-4.3-1.4-9.4-1.1-12.5-4.9c-8.5,2.9-15.5,10.2-18.2,19
          c-6.3,1.6-11.9,3.6-18.1,2.3c3.9,3.9,5.9,8.8,9.1,13.1c1.6,3.1,4.5,5.1,6.2,8.1c-3,10.9,3,5.9,6.4,13.6c0.3,0,19.6-1.4,19.9-1.4
          c7.5-8.5,15.3-18,16.2-29.7C1437.4,1214,1440.3,1198.2,1437.9,1198.9L1437.9,1198.9z"/>
      </g>
      <g id="malawi" data-country="Malawi">
        <path class="st0" d="M1447.3,1163.3c-0.1,0.4-0.5,0.8-0.7,1.2c-2.6,3.8-2.1,9-2.4,13.6c1.7,3.7,8.2,23.6,12.1,12.8
          c1-5,2.6-11.2-1.3-15.4l-0.6-2.5c-0.8,0-1.8,0-2,0c-0.3-4-0.6-7-0.9-11.5L1447.3,1163.3C1447.3,1163.2,1447.3,1163.2,1447.3,1163.3
          L1447.3,1163.3z"/>
      </g>
      <g id="mozambique" data-country="Mozambique">
        <path class="st0" d="M1497.5,1153c-1-2.8-0.8-6.1-0.8-9.1c-5.5,2.5-6.3,3.2-10.4,4.6c-9,6.9-24.7,7-35.3,8.9
          c0.5,3.8,1,11.2,1.3,15.5c1,0,1.4,0,2,0c0.3,1.5,0.5,2.2,1,3.6c1.7,5.7,2,10.2,0.8,15.3c-3.9,9.6-10.3-10.1-11.9-13.4
          c0.3-4.5-0.1-9.6,2.3-13.4c0-0.1,0.1-0.1,0.1-0.2c0.2-0.4,0.6-0.8,0.7-1.2c0.1,0,0.2-0.1,0.3-0.1c0-0.2,0-0.3,0-0.5
          c-0.1,0.1-0.2,0.2-0.3,0.2c-6.9,3.7-9.3,10.3-14,15.5c-5.6,3.9-13.7,5.3-16.8,11.6c3.1,3.8,8.2,3.5,12.5,4.9c3.2,1.8,7,3.3,10,5.1
          c0.3,17.6-4.5,32.3-17.3,44.7c0,0.1-0.1,0.2-0.1,0.3c9.1,12.9-2.7,28,11.8,34.9c6.6-2.7,15.2-5.4,14.7-14
          c0.2-12.3,0.9-24.6,2.1-36.6C1494.1,1198.1,1495.4,1210.8,1497.5,1153L1497.5,1153z"/>
      </g>
      <g id="democratic-republic-of-the-congo" data-country="Democratic Republic of the Congo">
        <path class="st0" d="M1321.5,1127.6c3.1,6,13.6,11.4,18.7,4.9c3.4-8.3,13.4-2.5,19.7-2.2c1.7,9.2-0.4,19.6,4.8,27.5
          c4.3,0.2,9.2-1.4,13.7-1.3c0.3,1.1,4.8,0.6,5.6,0.8c6,1.6,11.4,4.5,17.7,3.2c3.7,4.5,9.4,7.2,14.1,10.3c3.5,1,3.8-4.9,3.3-7.1
          c-2.9-1.5-6-3.4-8.7-5.3c0.7-4,1.1-27.1,6.3-23.4c4.3-0.7,10,0.2,14.1,0.9c-5.8-6.9-12.6-15.1-12.6-15.1s-1-4.9-1.7-13.5
          c0.4-1.5,2.4-7.2,2.4-7.2s-3.4,0.2-5.6,0.3c0-0.1,5.6-0.3,5.6-0.3c0-0.1-5.6,0.4-5.6,0.3c0.7-3.4,1.6-7,2-10.4
          c2.8-3.5,8.6-5.3,7.9-10.5c0.3-28.8,12.3-6.7,11.8-36.3c-6.4-8.1-7.2-9.9-17.2-5.3c-0.8,0.1-0.9,0-1.4-0.5c-2.8-1.7-4.8-4.7-7.8-6
          c-9.2,1.5-18.6,1.6-27.6,4.5c-5.5,2.3-10.2,6.7-16.5,6.2c0.1,0.2,0,0.4,0,0.7c-3.9-2-4.2-9.3-9.7-8.3c-6.3-1.9-6.5,4-9.2,8.1
          c-1.7,2.4-0.2,5.7-2.3,7.7c-5.7,8.3-12.6,15.5-11.5,26.3c-1.7,6.9-5.7,7.3-9.4,12.3c-3.3,2.2-2.8,5.7-3.3,9.1c-0.9,1.5-2,3-1.8,4.8
          c-3.9,14.1-23.1,9.9-33.9,11.5c4.6,2,8.3,5.3,10.5,10.1l25.8-1.6C1320.3,1124.6,1320.7,1126.4,1321.5,1127.6L1321.5,1127.6z"/>
      </g>
      <g id="tanzania" data-country="Tanzania">
        <path class="st0" d="M1423.4,1099.9c-2.8,0.3-4.6,0.3-4.6,0.3s-1.4,3.9-2.4,7.2c0.2,5.1,1.7,13.5,1.7,13.5s8.6,10,12.6,15.1
          c2.7,0.6,7.4,1.7,10,2.8c1.9,0.3,3.9,0.9,5.5,1.6c7.7,0.5,7.9,10.5,9.2,16.4c10.6-1.9,21.9-1.4,30.9-8.4c4.2-1.4,4.8-2.1,10.4-4.6
          c0.3-12.1-0.9-24.1-0.4-36.2c-13.6-12.6-18.6-20.3-38.4-27.7c0,0-25.9-0.3-27.5,0c-0.6,5.2-0.5,10.9-1.7,15.6
          C1427.4,1097.1,1425,1098.4,1423.4,1099.9"/>
      </g>
      <g id="burundi" data-country="Burundi">
        <path class="st0" d="M1418,1087.3c-5.3,2.8-2.6,8.8-5.1,13.2l10.5-0.6c2.1-2,5.7-3.3,6.1-6.4l0,0
          C1426.2,1090.9,1422,1088.3,1418,1087.3z"/>
      </g>
      <g id="rwanda" data-country="Rwanda">
        <path class="st3" d="M1430.1,1085.6l0.5-6.8c-2.5,0.5-5.2,0.7-7.7,0.8c0.7,3.8-2.2,5.3-4.7,7.5c3.8,1.5,7.4,3.3,10.6,5.7
          c0.3,0.2,0.6,0,0.6-0.3C1429.2,1090.4,1429.9,1087.7,1430.1,1085.6z"/>
      </g>
      <g id="uganda" data-country="Uganda">
        <path class="st4" d="M1462.8,1054.6c-0.7-5.1-4.8-8.1-7.5-11.9l-18.2,0.8c-0.3,0.1-0.7,0.1-1,0c0-0.1-1.2,0.2-1.4,0
          c0.4,29.7-11.3,7.3-11.8,36.3c4.9,0.4,6.6-0.1,9.5-0.1c5.8-0.1,6.2-0.1,12.5-0.1c2,0,1.6,0.2,13.1,0.2c-4.1-1.7-4.9-4.1-4.8-6.3
          c1.3-0.1,2.2-0.6,3.2-1.4C1461.1,1068.2,1463.3,1061,1462.8,1054.6L1462.8,1054.6z"/>
      </g>
      <g id="kenya" data-country="Kenya">
        <path class="st1" d="M1504.4,1088.9c-6-6.4-6.9-15.1-6.5-23.5c0-7.1-0.9-21.5,7.2-24.2c-13.2,5.3-26.2,0.2-35.9-9.4
          c-1.8,5.6-11.5,4.5-12.1,10.9l-1.9,0.1c3.4,4.4,8.7,8.7,7.6,15.2c-2.3,4.8-3.4,14.9-9.6,15.5c-1.2,7,15.6,10,20.9,14.3
          c9.6,6.2,12.4,12.6,22.3,20.2c0.2-7,6.2-11.2,9.7-16.7C1505.3,1090,1504.5,1089,1504.4,1088.9L1504.4,1088.9z"/>
      </g>
      <g id="somalia" data-country="Somalia">
        <path class="st0" d="M1556.7,982.8c-5.8,2.7-19.8,3.9-28.9,5.1c-6.5,0.2-13.4,3.9-19.5,2.9c-1.7,0.4-3.5,0.8-5.2,1.2
          c1.3,12.7,32.9,18.5,43.8,17.8c-4.9,14.6-20.2,22.4-33.5,28.5c-4.7,0.7-10.5,2.4-13.3,6.4c-0.3,4.9-3.3,8.9-2.3,13.9
          c-0.4,11.7-0.6,23.4,8.1,32.2c14.4-21.3,33.1-39.9,44.6-61.8c7.2-14.8,13.4-34.1,15.8-50.6C1566.6,978.5,1561.1,980.9,1556.7,982.8
          L1556.7,982.8z"/>
      </g>
      <g id="ethiopia" data-country="Ethiopia">
        <path class="st4" d="M1540.8,1009.7c-12.8-1-26.9-4.7-36.4-13.4c-3.1-6.2-0.3-14.3-0.2-21.1c-6.5-4.3-9-5-9.9-13.7
          c-4.8,0.3-10.1,0.7-14.4,2.6c-2.6,5.5-6.1,10.5-8.7,15.9c-2.9,6.5-8.2,10.9-11.3,17.1c-1,2.9,2.4,5,2.1,7.6
          c-3.8,1.1-6.3,4.8-9.5,6.9c-1.6,2,1.1,4.3,2.7,5.2c5.2-1.5,5.4,0.6,7.6,4.6c7.7,17.6,29.1,27.6,46.7,17.5
          c15.1-3.9,31.8-14,37.4-29.1C1545,1010,1542.8,1009.8,1540.8,1009.7L1540.8,1009.7z"/>
      </g>
      <g id="eritrea" data-country="Eritrea">
        <path class="st0" d="M1485.9,954.2c-2.7-1.5-6.7-7.9-9.7-13.5c-4,3.9-7.7,8.4-4.5,14c1.9,6.5,1.1,13.2,2.5,19.7
          c7-12.5,5.3-11.5,20.1-12.8C1492.9,957.4,1489.7,956.3,1485.9,954.2L1485.9,954.2z"/>
      </g>
      <g id="sudan" data-country="Sudan">
        <path class="st0" d="M1474.3,973.5c-1.9-6-0.4-12.8-2.5-18.8c-3.1-5.6,0.5-10.2,4.5-14c-4.6-9.3-6.6-13.4-14.2-19
          c-2.4-10.3-12.1-17.6-16.8-26.8c-3.8,3.8-7.5,10-12.5,12.1c0,0-54.2,0-54.2,0c0.1,2.5,0.2,5,0.4,7.3l-5.8,5.8c0.2,0,0.1,0,0,0
          c-0.8,6.3,2.2,28.4-2.4,33.3c-6,5.8-9.3,12.8-8.6,21.2c0,2.5,2.3,3,3.3,4.9c1.4,3.1,2.6,6.3,3.7,9.5c4,1.2,5.8,3.1,9.6,4.1
          c0.9,1.1,1.9,2.7,3.5,2.6c0.1,2.2-0.8,9-0.8,11.3c1.7,1.3,2.8,2.6,4.6,3.8c0.8,1,0.3,1.6,1.7,0.8c1-0.5,3.3-1.8,4.2-0.7
          c4.5,6.4,11.5,11.4,15.7,18.2c0.5,2.2,1.5,2.1,3,3.3c9.7,11.8,12.1-1.8,18.4,4c3,1.7,3.9,6.6,7.3,6.6c1.9,0.1,19.5-0.9,21.1-0.9
          c0.6-6.4,10.2-5.2,12.1-10.9c-4-4.1-5.4-10.1-9-14.7c-1.9-1.6-5.6,1.7-6.9-1.8c-4.5-2.7,6.3-9.2,8.8-10.3c-0.7-4.7-4.4-6-0.1-11.1
          c5.2-5.7,8.8-12.5,12.5-19.3c0-0.1,0-0.1,0-0.2C1474.3,974,1474.3,973.8,1474.3,973.5L1474.3,973.5z"/>
      </g>
      <g id="chad" data-country="Chad">
        <path class="st0" d="M1373,920.3c-4.6-0.3-8.3-4.2-12-6.7c-4.7-2.7-10-4.2-14.9-6.3c-7.8-4.4-15.8-8.8-24.4-11.2
          c-2.1,0.5-3.2,2.8-5.7,2.2c0.2,6.9-1.7,15.7,4.5,20.7c-1,8.8-0.2,17.9-1.7,26.8c-1.9,1.4-4.4,6.6-5.7,8.6c-2.2,3.3-6,5.4-7.9,9l0,0
          c4.4,3.8,10,6.2,13.9,10.4c2.6,2-2.9,4.5-4.3,5.8c0,3.3,0.6,6.2,1.8,9.3c7.2,13.6-12.1,0-5.2,13.8c0.5,1.4,1.5,1.9,2.8,2.3
          c4.4,1.6,1.2,16,2.5,15.9c14.2-6.9,16.3,1,30.8-14.4c15.2-0.3,9.4-6.5,21.4-14.2c0.3-6.5-4.4-11.7-7.1-17c0,0,0,0.1,0,0.2v-0.2
          c0,0,0,0,0,0.1C1363.3,946,1375.9,970.5,1373,920.3C1372.9,920.3,1372.8,920.3,1373,920.3L1373,920.3z"/>
      </g>
      <g id="niger" data-country="Niger">
        <path class="st0" d="M1320.3,918.9c-6.2-5.2-4.3-13.7-4.5-20.7c-2.2-0.3-3.8-2.5-4.5-4.4c-5.7-1.2-12.8-2-19.1-1.9
          c-8.5,14.8-26.3,20.3-39.7,29.5c-3.6,7-10,6.9-16.5,9.3v21.2c-17.1,15.2-14.5,3.5-30.3,9.8c6.3,10.8,17.5,30.2,30.4,15.1
          c0,0,0.1,0,0.1-0.1c0.6-0.7,1.3-2.6,2.2-2.9c4,0.6,8.3,0.7,12.1,2.1c6,1.5,12.8-4.1,17.3,3c8,1.7,11.6-6.1,19.9-3.8
          c3.8,0.9,7.3-0.4,11-1c4.4,0.7,3.4-7.9,6-10.8l0,0c1.9-3.6,5.6-5.6,7.9-9c1.3-1.9,3.8-7.1,5.7-8.6c1.5-8.5,0.5-17.2,1.7-25.6
          C1320.4,919.7,1320.4,919.2,1320.3,918.9L1320.3,918.9z"/>
      </g>
      <g id="mali" data-country="Mali">
        <path class="st0" d="M1231.9,931.5c-5.6,2.5-2.9-4.9-3.9-7.5c-5.1-1-9.1-5.7-13.3-8.7c-5.9-4.4-11.6-9-16.9-14.1
          c-6.8-6.5-15.7-10.3-23.5-15.6c-4.9,0.2-10-0.6-14.8,0.8c0.3,6.1,4.1,11.3,5.1,17.4c2.2,10,0.6,20.3,2.1,30.4
          c0.2,5.4,3.1,10.2,2.4,15.7c2.8,4.1-1,7.5-4.8,8.9c-7.4,0.6-15-1.7-22.3-0.7c-11,0.9-5.2,2.7-15.1-2.1c-4.3,0,0.1,7.2-3.5,8.7
          c-0.2-0.4-0.5-0.7-0.8-0.9c2.9,5.1,1.9,12.5,6.6,16.5c2.3,0.9,7.7-3.1,10-3.1c1.6,6.9,7.3,12.9,9.2,19.1c3.5,0,7-1.1,10.6-0.7
          c3,1.2,6.3,1.6,8.8-1.4c3.9-7.1,6.8-14.5,11.9-20.6c1.6-1.1,4.2,0,6,0c10-6.6,13.7-12,26.5-14.1c13.9,1.6,12.9,0.4,23.7-7.7v-21.2
          C1234.7,930.9,1233.2,931,1231.9,931.5L1231.9,931.5z"/>
      </g>
      <g id="mauritania" data-country="Mauritania">
        <path class="st0" d="M1169.7,951.2c-1.4-2.6-0.5-6.2-1.2-9.1c-3.8-8.3-1.9-17.8-2.8-26.6c0.5-10.3-4.5-19.3-6.3-29.1
          c4.8-1.3,9.9-0.7,14.8-0.8c-9-5.2-15.7-15.9-26.7-16.6c-2.4,1.9-1.9,6.1-4.3,7.5c-6.2,1.8-12.6,1.1-18.9,2.3
          c0.9,7.1-2.8,13.1-3.6,19.9c-7.6,3-0.8,12.2-10.6,16.3c-5.4,1.7-11.1-1.2-16.6,0c2.1,11.8-3.3,22.1-4,33.7
          c24.5-0.5,17.2-0.8,33.7,15.9c3.7-1.6-0.8-8.7,3.5-8.7c9.8,4.8,4.2,3,15.1,2.1c7.4-1,14.8,1.3,22.3,0.7
          C1167.3,957.3,1170.9,955.1,1169.7,951.2L1169.7,951.2z"/>
      </g>
      <g id="western-sahara" data-country="Western Sahara">
        <path class="st0" d="M1146.3,864.5H1114c-11.8,15-23.5,30.5-20.4,50.7c5.5-1.1,11.2,1.6,16.6,0c1.8-1.5,4.1-2.3,5.4-4.4
          c1.1-3.9,0.3-10.5,5.2-11.9c0.9-6.8,4.5-12.9,3.6-19.9c6-2.2,17.8,1.6,20.5-5C1146.8,869.9,1148.5,869.2,1146.3,864.5L1146.3,864.5
          z"/>
      </g>
      <g id="morocco" data-country="Morocco">
        <path class="st0" d="M1146.3,864.4c-1.6-6.2,2-9.7,7.5-11.7c6.1-3.2,9.7-9.8,16-12.8c12.8-2.7,3.2-9,10.1-10
          c35.1-8.5,15.7-5.9,13.9-30.6c-6.2-1.7-12.3-4.7-19.1-4.6c-9.2-5.4-9,11.4-12.4,16.5c-3.7,4.6-12.2,2.4-15.2,8.6
          c-11.2,14.8-20.6,30.6-32.8,44.7h32.3C1146.3,864.5,1146.3,864.4,1146.3,864.4L1146.3,864.4z"/>
      </g>
      <g id="algeria" data-country="Algeria">
        <path class="st0" d="M1290.2,888.1c-2.1-2.1-5.3-0.4-7.8-1.2c-4.5-3.1-10.8-6.2-9.4-12.7c-0.5-2,0.4-2.9,1.7-4.2
          c1.9-9,1.4-18.6,1.4-27.8c-7.1-0.3-4.2,1.8-6.6-4.3c-4-10.8-2.7-13-11.1-21.8c-4.3-8.6,11.4-11.3,8.3-20c-0.4-6.6-2.9-12-5.7-17.9
          c-13,5.7-28.1,4-41.9,4.6c-10.1,10-10,14.3-25.5,16.5c1.8,24.7,21.3,22.1-13.9,30.6c-1.3,0.4-2.8,0.1-2.8,1.7
          c2.1,8.5-9.6,7.2-13.4,12.7c-9.3,12.2-21.7,6.6-16.3,24.8c10,0.2,15.9,9.9,24,14.5c7.7,5.8,16.3,10.1,24.2,15.6
          c6.6,7.2,14.6,12.7,22.1,18.8c3,2.4,6.5,5.4,10.1,6.1c1,2.6-1.6,10,3.9,7.5c7.6-2,16.6-2.5,20.7-10.1
          C1257.5,917.2,1300.6,896.9,1290.2,888.1L1290.2,888.1z"/>
      </g>
      <g id="tunisia" data-country="Tunisia">
        <path class="st0" d="M1291.2,815.3c-6.5-4-14.6-8.3-19.1-14.5c-1.6-4.2,4.2-5.1,3.6-9.2c0.1-2.7-1-4.9-2.7-6.8
          c-0.6-2.4,0.4-5.2-0.8-7.6c-3.8-0.2-7.6,0-11.1,1.2c3.2,6.7,6,13.1,5.8,20.6c0.7,6.8-12.7,9.5-8.4,17.3c2.7,3.6,6.1,6.4,7.7,10.6
          c0.6,2.8,0.8,5.5,2.4,7.9c3.5,8.3-0.4,7.8,7.6,7.6c5.6,0.7,0.6-4.9,6-12C1285.9,825.6,1293.2,822.6,1291.2,815.3L1291.2,815.3z"/>
      </g>
      <g id="libya" data-country="Libya">
        <path class="st0" d="M1378.4,907.2c-1.3-11,0.2-48.9-1-60.1c-3.3-7.2-4.4-14.7-3.7-22.6c-21.4,3.7-17.4-17.1-32.1-5.8
          c-0.6,3.9-1.2,8-0.7,12c-4.8,10.9-22-6.3-32.6-3.3c-7.5,0.2-6-10.2-17.1-11.8c1.8,7.3-5.4,10-9,14.9c0,0.2-0.1,0.3-0.1,0.5
          c-4.9,4.4-0.4,12.6-5.9,11.5l0,0c-0.8,9.2,2.1,21.5-2.8,29.2c-0.6,1.3-0.7,7.3,0.3,8.4c3.2,2.9,6.9,7.6,11.6,7.3
          c2.9-0.7,7.6,1.3,6.9,4.8c6.2-0.1,13.4,0.7,19.1,1.9c0.7,1.9,2.3,4.1,4.5,4.4c2.5,0.6,3.6-1.7,5.7-2.2c8.7,2.4,16.5,6.8,24.4,11.2
          c5,2.1,10.2,3.7,14.9,6.3c3.7,2.5,7.4,6.5,12,6.7l5.8-5.8C1378.6,912.1,1378.5,909.6,1378.4,907.2L1378.4,907.2z"/>
      </g>
      <g id="egypt" data-country="Egypt">
        <path class="st0" d="M1444.6,854.3c1.6-2.1,2.8-4.4,4.7-6.2c-1.9-5.9-4.3-11.6-7.6-16.9c-4.1,0.6-7.1-2.6-11.1-2.8
          c-6.6,0.1-13.4-0.4-19.9,0.8c-0.7-1.2-1.2-2.5-1.7-3.8c-2,0.9-3.6,2.7-5.1,4.3c-8.1,5.1-20.8-6-30.3-5.4
          c-0.7,7.9,0.5,15.4,3.7,22.6c1.2,11.2-0.3,49,1,60.1h54.2c5-2.2,8.7-8.1,12.5-12.1c-0.4-4.2-3.5-7-5.8-10.3l0,0
          c-4.6-11.3-9.7-23.7-5-35.7c4,0.6,6.3,4.8,8,8.2C1443.2,856.2,1444,855.3,1444.6,854.3L1444.6,854.3z"/>
      </g>
      <g id="australia-australia" data-country="Australia">
        <path class="st5" d="M2210,1280.5c2-4.2,1.1-8.9,1.8-13.2c2.7-1.3,3.3-5.4,1.3-7.6c-0.4,4.1-3.8,8.5-5.8,2.4
          c-2.1-4.9-7.7-7.8-9.7-12.5c1.1-2.7,4.9-5.5,1.2-8.5c-10.1-5.3-9.7-3.6-13.6-14.5c1.6-10.6-14.5-10.7-17.5-19
          c-0.3-6.9,2.5-14.3-5.1-18.7c-0.5-6.4,2.8-15-3.5-19.3c-2.6-2.4-5.6-5.7-8.7-1.7c-3.3-1.9-2.5-6.3-3.4-9.5
          c-3.1-6.2-8.4-10.4-7.4-17.7c-2.4-4.4-8.8-0.8-8.1,3.7c0,6.8-6,11.8-5.5,18.5c0.3,11.7,1.7,25.8-12.7,29.2
          c-7.4-6.7-34.1-12.4-31.4-24c3.4-2.9,4.2-7.1,3-11.5c2.5-0.3,4.6-2.1,5.7-4.3c4.9-13-16,0.1-21.3-7.5c-9.9-9.4-7.6,4.9-17.6,2.7
          c-9.2,0.7-13.3,11.5-17.8,17.7c0.2,2.5,7.4,3.8,3.6,7.2c-7.1,1.1-1.7-8.5-15,1c-1-1.2,1-2.9,0.8-4.4c-0.2-1.7-2.5-2.1-4-3
          c-2.6-2.7-5.6-4-8.7-1.1c-4.4,4.6-14.7,7.6-15,14.6c0.5,1.5,2.7,3.5,0.8,4.8c-20.8-2.7,1.1,7-10.6,9.1c-1.9-1.7-3-5.8-5.8-6
          c-6.8,2-4.6,8.2-1,11.9c0.5,3.3-4.3,3.5-5.7,5.6c-1.3,4.3-2.1,9-7.6,9.9c-10-0.2-17.3,8.7-26.5,9.4c-4-1-6.2,2.2-8.7,5
          c-3,2.8-9.1,5.5-7.5,10.5c1.1,1.6,0.8,4.6-1.6,3.3c-1.1-1-0.2-4.2-2.2-4.2c-4,2.6,0.5,7.7,1.5,11c-0.6,4.4-6.9,9.4-2.9,13.5
          c2.2,4,8.4,6,8.3,10.7c-3.7,0.5-6-4.5-10-3.2c2.3,5.2,9.1,8.4,9.9,14.3c0.6,2.6,3.5,2.7,4.2,5.2c0.7,2.8,4,4.2,5.3,6.8
          c0.7,11.2,6,11,8.6,19.5c-0.9,6.4,6.5,14.7-4,15.8c-1,0.6-1.3,2-0.9,3c1.2,2.7,4.8,2.5,7.2,3.6c4,2.3,7.4,6,12.5,4.8
          c3.6-0.5,6.1-3,8.7-5.3c4.3-1.1,8-2.7,8.6-7.5c7.8-6.8,23.8,4.4,25.8-9.7c5.7-3.2,11.1-8.3,18.1-6.8c6,0.1,11.1-4.2,16.9-5.9
          c7.8-0.9,17.5-3.9,23.9,1.9c3.3,1.6,8.9,0,10.5,4.2c0.6,5.5,7.6,7.9,7.6,14.2c-0.5,4.1,7.5,9.4,4.7,3.4c-1.3-4,9.7-7.4,12.7-13.7
          c3,1.1,2.6,7.6-0.9,8c-4.1,1-1.9,7.6-6.1,7.5c-0.8,1.8,1.4,2.9,2.9,2.7c3.4,0.2,4.7-4.7,5.9-7.2c2.1,7.1-0.4,12.9-9,10.5
          c-2.5-0.8-4.8,2.5-1.4,3.2c4.3,1.9,8.1,0.7,11.5-2.2c13.6-5.8,5.7,20.7,20,23.1c6,0.6,14.6,7.7,19.4,2.6c0.8-1.1,2.5-1.1,3.7-0.5
          c8,7.6,12.9,8.8,19.2-1.5c17.5,1.5,13.5-5.5,17.8-18.8c6.6-7.7,13.5-15.1,19.2-23.5c6.1-5.1,5.4-13.8,8.9-20
          c4.8-3.7,4.7-10,5.7-15.5C2212.7,1284.6,2209.8,1282.9,2210,1280.5z M2180.3,1387.1c-20.6,6.2-10-1.1-22.7-0.4
          c-0.3,4.9,2.1,8.3,5.5,11.6c0.8,1.3,0.1,1.7-0.3,2.9c-1.1,4.7,4.8,8.3,8.2,10.5c2.7,0.6,2.6-1.8,2.8-3.7
          C2178.2,1403.7,2190.9,1390.9,2180.3,1387.1z"/>
      </g>
      <g id="papua-new-guinea" data-country="Papua New Guinea">
        <path class="st6" d="M2198.1,1134.5c-4.1-2.7-7.1-5.7-9.4-10c-6.2-1.9-11.6-6.1-13.2-12.7c4.2-6.6,8.7-1.6,8.3-13.5
          c-28.6,5.4-24.9-13.6-45-22.3c-1-0.9-2.1-1.7-3.2-2.3v52.5c1.1,0.2,2.3,0.2,3.3,0.1c23.2-33.4,29.2,17,64.3,12.5
          C2203.6,1136.9,2199.2,1135.6,2198.1,1134.5z M2188.4,1107.7c4,0.7,9.6,1.5,10.9-3.5c-3.7-0.1-8.5-0.3-12.1,0.5c0,0.1,0,0.2,0,0.3
          C2185.8,1106.6,2186.6,1107.2,2188.4,1107.7z M2211.5,1128.9c-1.8,0.2-5.9-2.3-5,1C2208.8,1130.4,2211.1,1132.9,2211.5,1128.9z"/>
      </g>
      <g id="indonesia" data-country="Indonesia">
        <path class="st0" d="M1970.6,1049.3c-1.7-1.6-3.7-3-5.6-4.3c3.4-2.8-5.6-8.7-5.8-12c8.3,0,2-2,5.4-3.5c7.5-1.8,4.9-2.1,2.1-7.1
          c2.5-0.7,5.1-1.1,7.2-2.8c-0.7-1.3-4.1-0.5-5.2-1.7c-0.3-3.5-2.3-1.6-4.7-1.6c0-0.2,0.1-0.4,0.3-0.5c-0.7-0.7-2-0.2-2.8,0
          c-0.6-4.8-4-3.2-5.3-7.3c-0.9,0.1-1,1.1-1.1,1.8c-5.4-2.6-4.2,7.8-8.5,8.9c0.8,2.2,0.2,5.8-1.6,6.8c-3,9,3,20-5,25
          c-8.9,5.9-17.2,2.5-24.6-2.5c-0.4,1.5-1.5,3-0.9,4.6c-1.2,1-1.8,0.4-2.9,0.1c-3.3-0.7-7.2,0.5-10-1.5c0.1-0.2,0-0.3-0.2-0.4
          c-3.2,0.7-4.2,5.5-3.1,8.1c0.1,0,0.2-0.1,0.2-0.1c0.9,3.1,1.9,6.7,2.4,9.9c1.6,0.9,1.1,2.3,1.9,3.7c2.1,1,5.7,0.9,6,4.4
          c-0.7,4,2.3,6.7,2.1,10.6c3.5-0.8,6.7-0.5,10.1-1.6c2.7-0.2,2.7,5.6,5.9,3c2.4-0.2,2.6-3.7,5.2-3.5c2.2,1,4.5,1.8,7,2.2
          c1.8-0.5,3.2-1.3,5.1-1c0.9,4.1,3.4,6.5,7.1,3.2c2.8-0.4,3.4-3.2,5.9-4.4c-0.3-2.7-0.3-3.6,2.7-4.6c-0.7-3.4-4.2-7.2-0.6-10.3h-0.1
          c12.7-7.8-1-7.2,6.8-15.9c0.3-3.1,4.2,0.1,9.9-2.8C1975.3,1050,1972.1,1050.4,1970.6,1049.3z M2057.4,1085.6c1.3-1.4-1.8-4.1-2.6-5
          c-1.6-1.8-4.1-0.4-6-1.2c-1.9-1.2-3.9-0.5-5.8,0.1c-1.6,0.3-5.2-0.7-6.2,0c-0.9,0.4-3.4,3.4-1.3,3.4c1.8-0.7,3.2-0.1,4.9,0.7
          c3.1-0.4,2.6-1.6,6-0.2C2050.4,1081.3,2053.7,1086.8,2057.4,1085.6z M2105.9,1110.8c-3.1-1.6-4.8,0.5-6.1,3.1
          C2094,1121,2112.5,1114,2105.9,1110.8z M2018.9,1035c-5.2,4-6,7.6-13.6,6.5c-2.7,1.5-6.4-0.4-9.4-0.8c-6.1-3.1-8.1-2.9-11.8,2.6
          c-1,0.3-2.3-0.2-2.9,1.1c-0.2,2.6-1.6,5.2-1.9,7.8c-0.5,1.4,0.8,4.8-1.3,4.6c-4.2,0.3-0.2,7-1.2,9.8c-1,2.9-1.5,5.6-3.6,7.8
          c0.5,1.9,1.3,5.6,2.1,7.2c2.5-5,3.7,2.7,4.3,4.9c0.3,4.6-1.4,8.7-1.2,13.3c20.6,4.5-3-24.5,11.5-26.4c-0.5,7,5,12,4.6,18.9
          c3.9,1.4,5.5-1.6,8.1-3.5c6-0.1,2.5-3.1,0.1-5.6c-3.4-3.7-1.2-11.2-6.9-13.6c-1.6-0.7-2.9-0.6-2.4-2.8c5.4-0.4,6.4-6.5,10.7-8.4
          c1.5-0.4,4.4,0.1,3.6-2.5c-0.7-2.6-5.8,0-7.6,0.8c-3.2,2.2-7.6,0.3-9.7,4c-0.7,2.2-3.4,3-5.2,1.9c-0.8-0.8-0.4-2.8-1-3.8
          c-2.1-2.3-1.4-5.5-1.9-8.4c-0.3-5.4,6.2-3.5,9.3-3.8c7-1.2,13.8,0,20,2.1C2015.1,1048.2,2024.6,1036,2018.9,1035z M2061.6,1115.7
          c-2,1.6-7.8,1.6-6.4,5.3c1.1-0.2,1.9-0.2,2.7,0.4C2061.4,1120.4,2062.4,1119.3,2061.6,1115.7z M2080.4,1107.7
          c0.9-4.8-1.5-6.4-5.3-3.6c0.7,2.5-2.2,10.5,1.3,10C2079.7,1116,2082,1110.5,2080.4,1107.7z M2070.4,1103.7c-6.4-4.4-6,7.1,0.2,1.5
          C2070.4,1104.7,2070.5,1104.2,2070.4,1103.7z M2067.1,1102.2v0.5c0.1,0,0.2-0.1,0.3-0.1C2067.4,1102.5,2067.3,1102.3,2067.1,1102.2
          z M2130.3,1071.7c-5.4,0.7-9-5.1-14.9-4.2c-5.8,0.1-9.8-5.6-15.1-5.4c-2.6,1.2-5.9,4.1-6.4,6.9c-3.3,1.6-7.2,3-8.9,6.5
          c-0.4,1.2-0.5,2.8-1.8,3c-1.6,0.3-1.6-1.5-2.6-2.1c-2.3,0.2-2.2-2.9-3.3-3.9c-3.1,6.7-1.5-11.9-2.7-13.7c-1-1.7-4.8-0.1-6.5-1.1
          c-6.6-5.7-13,1.7-17.4,6.2c4.6,2.4,7.3-1.6,9.7,5.3c3.5,2.9,14.4-2.8,11.1,3c-6.7,0.1-7,4-13.2,3.4c0.7,2,6.1,1.8,7.1,3.5
          c0.5,1.2-0.5,2.7-0.5,3.8c0,2.5,3.6,3.3,4.1,0.5c0.4-1.2,1.3-4.6,2.7-4.6c0.1,0.9,0.2,1.9,0,2.8c4.5,2.5,5.2-1.8,7.5,3.7
          c2.1,4,5.9,1.6,9.1,3.4c4.9,3.9,11.9,1,16.5,5.1c3.1,2.7-1.8,3.6,2,8.3c0.1,2.1,1.7,3.7,1.2,5.8c2.1,2.4,2.5,5.5-0.3,7.5
          c-1.4,1.8,0.5,1.8,1.7,0.9c1.6-1.7,2.9-0.6,4.9-1.1c2.3-2,3.5-1.2,3.2,2c4.3,1.1,5.5,7.6,10.5,6.4c2.2,1.3,4.9,2.4,7.7,2.7v-52.5
          C2133.9,1072.8,2132.2,1072.2,2130.3,1071.7z"/>
      </g>
      <g id="timor-leste" data-country="Timor Leste">
        <path class="st0" d="M2051.9,1123.5c-3.3,0-6.5,0.9-9.6,2c-0.1,1.3,0,2.5,0.5,3.7C2044.6,1127.3,2054.4,1126.7,2051.9,1123.5z"/>
      </g>
      <g id="singapore" data-country="Singapore">
        <path class="st0" d="M1889,1114c-0.7,1-1.2,2-1.4,3.1c6.4,7,14,4.1,8.3-5.1C1893.1,1111,1890.5,1112.5,1889,1114z"/>
      </g>
      <g id="malaysia" data-country="Malaysia">
        <g>
          <path class="st0" d="M1970.8,1126.5c-0.7,2.1-3.2,0.3-4.1,1.4c-0.3,1,3.5,1.9,4.2,2c0,0.1,0,0.2,0,0.4c-2.9-1-5.9-1.9-8.6-3.7
            c-1.8-1.7-2.8-3.3-5.5-3c-5.1-1.6-9.8-5.7-15.3-5.6c-4,7.5-19.7,0.8-26.6,0.3c-1.5,1-1.9,2.6-2.6,4.3c-0.5,0.7-1.2,0-1.3,1.1
            c3.6-0.2,3.9,3.5,7,3.8c2.9-0.5,6.7-1.2,9.6,0c3.5,0.5,6.9-1.5,10.5-0.7c7.9,3.6,17.4,1.8,26.1,4.3c2.1,0.1,7.3,3.3,8,0.2
            C1976.9,1128.5,1976.1,1126.3,1970.8,1126.5z"/>
          <path class="st0" d="M1895,1108.3c0.2-2.6,0.7-5.3,0.3-7.9c-0.9-2.1-1-3.9-0.5-6.2c1-3.5-3-5-4.4-7.5c-1.4-0.9-2.4-2.2-3.2-3.6
            c-1.2-0.5-3.9,0.4-4.3-1.1c0.1-3.1,1.8-5.1,2.1-8c-6.1-6.8-11.9-14.4-20.7-18.3c-6.3-7.1-12-17.5-20.2-22.1
            c-4.7-3.3-9.4-6.5-14.2-9.6c-0.5,11.2,9.9,16.7,15,25.2c-0.3,7.1,3.7,10.6,7.7,16c0.3,2.9,2.3,4.1,4.4,5.8
            c1.2,6.2,6.3,8.3,5.8,13.6c1.6,14.2,17.5,21.1,24.8,32.5c0.7-3.5,4.2-6.3,8.3-5.1C1895.4,1110.8,1894.8,1109.5,1895,1108.3z"/>
          <path class="st0" d="M1940,1051c8-5,2-16,5-25c1.7-1,2.5-4.7,1.6-6.8c-2.3,0.6-4.1,2-2.6,4.4c-0.2,1.9-1.4,1.4-2.7,0.5
            c-1-0.6-2.8,1.1-3.6,1.8c-5.2,4-6.6,10.9-12.8,14c-3.6,2.6-10.2,2.9-9.5,8.5C1922.8,1053.5,1931.1,1056.9,1940,1051z"/>
          <path class="st0" d="M1846.6,999c1.2,2.2,2.8,3.7,2.5,6.5c1.3,3.9,6.5,5.2,8.5,9c0.2,9.4-0.7,18.7,7,25.5c3,2.9,5.5,6.4,9.5,7.9
            c2.2,1.2,4.6,1.4,6.3,3.5c3.7,5.9,8,0.7,5.7-4.4c-2.9-6.5-2.1-14.6-4-21.7c-3.7-9.8-15.4-6.2-21.6-15.6c-4.1-5.9-11.6-8-13.2-16
            C1846.5,995.3,1846.3,997.2,1846.6,999z"/>
          <path class="st0" d="M2000.7,1129.6c0.2-1.3-0.7-2.3-2-1.9c-1.3-1-0.9-0.9-2.5-0.4c-1.3,0-3.2-2.9-4.4-1.8c-1.7,1.6,2.8,2.8,3,3.6
            c-2.3,1.4-4.1-0.5-6.2-0.9c-9-0.1-5.8,10.5,7.2,2.2c-0.1,1.1,2.7,0.6,3.5,0.6C1998.9,1129.7,2000,1130.1,2000.7,1129.6z"/>
          <path class="st0" d="M2029.7,1128.6c0.2-0.9,3.3-4.9,1.1-4.7c-0.8,0.1-0.9,1-1.2,1.7c-0.6,1.7-2.9,2.7-4.6,2.7
            c-0.8-0.2-1.4-1-2.3-0.8c-3.5,2.7-13.9-4.5-16.7,2c-1,5.5,3.9,0.8,6.3,1.8c2.7,0.7,5.2-1.3,7.6-1.2c1.4,2.2,4-0.4,5.9-0.7
            C2027,1129.2,2028.7,1129.3,2029.7,1128.6z"/>
          <path class="st0" d="M1979.3,1128c-1.7,1.5-2.2,4.9,1,4.6c0.6-0.5,0.5-0.4,0.7-0.4C1983.2,1130.4,1982.6,1125.3,1979.3,1128z"/>
          <path class="st0" d="M2043,1125.2c-10.8,1.3-3.8,2.3-9.3,4.1c-4.6,1.7-11.8,4.7-8.3,10.6c1.4,1.6,2.4,0.4,3.1-0.2
            c6.6-0.9,9.4-7.7,15-10.8C2043.1,1127.7,2042.9,1126.5,2043,1125.2z"/>
          <path class="st0" d="M2008.5,1136c-2.6-3.4-12.8,0.9-5.2,2.7c2.9-0.6,3.8,2,6.3,3C2018.2,1144.8,2012.3,1137.7,2008.5,1136z"/>
        </g>
      </g>
      <g id="japan" data-country="Japan">
        <path class="st7" d="M2129.8,635c-0.2,2.7,1.7,4.6,3.3,6.6c1.3,11.9-3,23.5-1.8,35.6c-2.7,4.7-4.5,31.2,4.7,18.2
          c0.9-0.6,1.3-0.2,2.1,0.4c1.6,1.1,3.9,1.2,3.3,3.7c5,4.2,0.8-10.4-3.1-10.6c-3.5-6.4,1-6.5-0.1-14.5c0-1.8,2-2.9,2.3-4.6
          c0.4-1.1-0.4-4,1.3-4c2.7-0.1,4.3,2.7,6.2,4.2c1.6,0.6,3.2,0.2,2.5-1.8c-2.6-5.7-5-11.4-6.9-17.5c-1.1-3.4-4.5-6.6-3.6-10.4
          c0.9-6.9,1.1-15.6-1.8-22.2c-3.5-2.8-1.5-8.8-4.2-11.4c-2.9,3.8,0,8.3-0.2,12.5c-0.5,1.5-2.7,1.4-3.4,2.7
          C2129.1,626.1,2127.8,630.8,2129.8,635z M2161.4,717.3c-4.1,0.1-6.5,4.5-10.4,4.5c-2.1-2.3-5.5-2.5-8-4.2
          c-4.1-3.7-8.3-14.1-14.6-9.7c1.8,4.6,2.3,10.3-0.3,14.8c-0.5,1.9,1.3,5.7-0.8,6.7c-0.9,0-0.8-1.9-2-1.1c-2.6,0-4.9,0.7-6.2,3.1
          c-0.1,2.4-4,2.7-3,5.5c1.1,4.7,3.3,1.6,2.2,8.1c0.1,0,0.1,0,0.1,0c0.2,3.5,2.9,0.3,4-0.5c8.3-1.5,5.5-3.6-0.8-5.1
          c-2,0-1.8-3.2,0.1-2.5c3.2,2.2,6.5,1.3,8-2.3c1.8,2,3.9,3.5,6.2,4.8c0.3,5,7.3,3.7,6.6-0.8c0.8-2.5,3.7-3.3,5.3-5.2
          c1.8-3.5,3.4,2.7,8-0.5c3.1-1.3,3.6-6,0-7c-1.8-3.9,5.9-6.2,8-8.6C2163.5,716.5,2161.9,717.1,2161.4,717.3z M2062.6,820.6
          c-1.8-1.7-3.2-4.4-5.8-4.9c-0.1-1.8-2.2-3.6-3.9-2.7c-0.7,2.1-10.9,5.2-9.7,8.2c2.4,1,8.4-2.1,7.1,2.8c-2.4,2.6-5.5,4.8-4.8,8.4
          c2.3,2.8-0.9,8.7,6.1,5.1c6.7-1.9,7-6.7,8.5-12.5C2062.6,824,2063,823.4,2062.6,820.6z M2132.4,762.5c-0.4-5.1-3.4-10.3-6.6-14.2
          c-1.6-0.2-1.8,2.9-3.8,1.6c-1.8-0.7-4.4,0.1-4.1,2.4c-0.4,1.8-1.6,3.1-1.3,5.2c0.1,5.4,1.3,10.4-1.5,15.5c-0.5,2.1-0.7,4.4-2.7,5.6
          c-1.3,1.2-4.1,0.9-4.8,2.6c0.2,1.2-0.4,1.8-1,2.7c-1,2.6-8.3,7.6-8.2,2.4c0.7-6-3.1-4.2-4.2,0.1c-2.1,4.5-5.4,3-4.6,10.2
          c-0.3,5.9-5.9-0.6-8.9,0.1c-5.3,1.1-5.6-3.3-11.8,0.5c-6.7,3.5-3.2,8.6-11.2,10.1c-9.4,2.1-1.2,5.3,3.7,4.5
          c4.6-1.2,6.5-3.4,11.8-2.4c1.3-0.4,1.6-2.3,3.3-2.6c1.4-1.4,4.1,1.9,5.7,2.6c2.8,0.6,0,5,2.8,6.4c3.7,0.5,11.5-3.4,8.4-7.9
          c-1.7-5.4,4.2,1.7,9.9,1c4.1-1.7,7.7-4.4,11.1-6.9c3.6-0.6,2,7.9,6.7,3.2c2.7-3.9,0.8-9.5,1.6-14c1.5-5.5,3.8-10.8,4.3-16.6
          C2129.5,769.9,2133.7,768.6,2132.4,762.5z M2079.4,812.9c-3.1-4.1-7.6-1.8-10.6,1.1c-1.6,1.1-3.8,1.1-5.1,2.5
          c-1.3,2.9,0.3,6.9,3.9,6.6c1.7-1.1,3.4-3.4,5.4-4.6C2076.1,818.8,2082.8,816.9,2079.4,812.9z"/>
      </g>
      <g id="south-korea" data-country="South Korea">
        <path class="st7" d="M2025.1,781.3c0.4,0.1,0.8,0.3,0.9,0.5c7,13-2.2,4.8-1,12.3c1.6,2.8,3.3,6.3,2.4,9.7
          c-5.2,15.5,14.2,0.1,18.6-3c5.1-8.9-0.5-20.4-5.6-28.3C2034.9,774.8,2030,777.7,2025.1,781.3L2025.1,781.3z"/>
      </g>
      <g id="north-korea" data-country="North Korea">
        <path class="st7" d="M2049.8,746.2c-13.1,6.4-26,13.4-40.5,16.2c8.3-0.6,7.9,1.2,5.3,8.4c0.5,2.3-1.2,7,0.9,8.2
          c3.4-0.2,6.7,1.2,9.7,2.5c4.7-3.5,10.1-6.7,15.5-8.9c-15.2-19.7,5.1-15.1,9.8-26.5C2050.2,746,2050,746.1,2049.8,746.2
          L2049.8,746.2z"/>
      </g>
      <g id="vietnam" data-country="Vietnam">
        <path class="st0" d="M1920.5,955.4c-2.3-8.8-8.7-16.8-15.4-23.1c-4.5-4.6-14.1-5.9-12.7-14c-1.3-7,6.8-9,11.4-12
          c-6.1-6.3-15.7-5.7-23.1-2.1c-2.4,3.5-6.1,3.7-10,3.7v6.2c6.1,1.6,14.1,4.3,16.1,10.8c-5.7,2.6,8.4,8.1,10.5,10.3
          c24.1,26.5,8.5,42.5-17.9,56.2c7.5,21.3,17.1,5.1,25.3-5.6c3.8-3.5,9.5-4.8,11.8-9.8C1916.1,968.6,1921.4,962.7,1920.5,955.4
          L1920.5,955.4z"/>
      </g>
      <g id="cambodia" data-country="Cambodia">
        <path class="st0" d="M1909.1,959.9c-0.1-1.1-0.5-2.3-0.5-3.4c-5.2-0.1-9.5,3.7-14.5,3.7c-5.1,0.1-10.3-2-15.4-0.6
          c-1.4,0.5-1.3,1.7-1.3,2.9c-1.4,4.6-4,8.6-6.1,12.9c3.7,4.8,6.3,10,8.1,15.8C1891.3,983.9,1909.2,976.1,1909.1,959.9L1909.1,959.9z
          "/>
      </g>
      <g id="laos" data-country="Laos">
        <path class="st0" d="M1861.8,922.7c-0.5,0.5-1.1,0.9-1.7,1.3c5.5,2.7,6.3,8,7.4,13h11.8c-0.4-2.4,2.5-2.6,4.2-2.3
          c2.6,3.5,5,7.6,6.6,11.7c-0.2,4.9,4.1,8.8,4,13.7c5,0,9.4-3.8,14.5-3.7c-1.5-23-29.4-27.9-21.8-31.7c-2-6.5-10-9.2-16.1-10.8v-6.2
          c-2.8,0.3-4.3,1.6-3.9,4.5l-1.6-0.1C1864.9,915.8,1864.8,920.1,1861.8,922.7L1861.8,922.7z"/>
      </g>
      <g id="thailand" data-country="Thailand">
        <path class="st0" d="M1874.2,969.4c1.1-2.4,2.8-4.3,3.2-6.9c0-1.2-0.1-2.5,1.3-2.9c5.1-1.4,10.3,0.8,15.4,0.6
          c-0.1-3.8-2.2-7.3-3.8-10.7c-0.1-2.1-0.3-4.1-1.4-6c-1.9-2.8-3.2-6.2-5.4-8.7c-1.7-0.3-4.5-0.2-4.2,2.3h-11.8
          c-0.9-4.8-2.2-10.5-7.4-13c-4.9,2.2-10.1,4.2-14.4,7.4c0,1.3-0.2,2.7,0,4c2.1,3.4,5.2,6.6,5.9,10.8c4.4,8.6,1.2,18.8-1.7,27.1
          c-0.9,2.5,0.1,4,1.1,6.2c1.1,5.4-3,9.3-3.5,14.3c1.4,7.7,9.3,10.4,13.2,16c3.9,4.2,8.5,8.3,14.6,8.1c-3.7-5.3-10.4-7.1-13.2-12.8
          c-7.8-6.1-8.5-15.3-8.3-24.5c2.3-3.5,2.4-8.3,4.9-11.8c-0.3-2.6-0.3-4.9,3-4.5c2.9,1.4,1.4,6.8,2.1,9.6c0,1.9,4.1,0.8,5.4,0.5
          c1.1-0.9,1.5-0.2,1.9,1.1C1872.1,973.7,1873.4,971.3,1874.2,969.4L1874.2,969.4z"/>
      </g>
      <g id="myanmar" data-country="Myanmar">
        <path class="st0" d="M1862.1,912.1c-2.5-4.7-5.9-12-10.2-15.5c-1.9-0.4-7.1,2.5-7-0.7c1.5-3,3.9-5.4,6-8.2c0.5-3.1,1.7-6.2,3.1-9
          c-2.8-2.4-3.8-6.9-6.6-9.4c-0.9-0.2-7,0.2-7.4-1v5.6c-2.5,1.1-4,3.4-6.8,3.7c-0.7,0.1-2.1,0.1-2.2,1.1c-3.7,5.8-6,19.1-14.1,19
          c-2.6,5.1-3,11.2-5.9,16.2c7.5,16.9,10.1,3.1,7.4,27c4.6,4,6.9,5.5,12,1.3c2.1-1.5,2.7-5.8,5.6-5.6c4.4,4,4.7,12,8.9,16.7
          c11.7,14.4-10.8,28.1,2,45.8c-1-6.2,4.1-11.1,4.2-17c0.5-4-3.1-6.6-0.4-10.6c4.2-10.5,4.2-22-1.7-31.8c-1.8-2.3-4.1-4.2-3.2-7.5
          c5.8-7.6,21.2-5.8,19.6-19.9L1862.1,912.1L1862.1,912.1z"/>
      </g>
      <g id="bhutan" data-country="Bhutan">
        <path class="st0" d="M1789,876.2c5.2,4,15.5,3.1,18.7-3c-0.3-1.3-0.2-2.6,0-3.9c-3.5,0.4-6.6-1-10-1c-3.4,1.9-7.3,2.2-11.2,2.5v5.2
          C1787.3,876,1788.3,875.8,1789,876.2z"/>
      </g>
      <g id="bangladesh" data-country="Bangladesh">
        <path class="st0" d="M1809.5,906c0.1-3.6-3.7-4.5-6.2-5.9c0-4.5,2.4-4.7,0.1-10.7c-6.2-0.5-12.1,1.4-13.3-6.2
          c-1.7-1.5-4.7,0.5-6.1-1.7c-1.3,1.5-3,2.6-4.5,3.8c7.3,8.5-11.8,3.1,5.8,11.4V910c7.9,2.9,16.3-6.5,23-0.1
          C1809.4,908.8,1809.6,907.5,1809.5,906z"/>
      </g>
      <g id="nepal" data-country="Nepal">
        <path class="st0" d="M1778.1,872c-4,0.5-7.4-1-10.8-2.7c-3.6-1.2-7.4-2.2-10.4-4.5c-5.3-2.5-9.3-8.7-15-9.5c-0.2,0-6.2,0-6.4,0
          c-2.1,2.1-3.7,5.3-5.9,7.4c-2,1-1.4,3.2,0.6,3.6c6.1,5.5,14.1,6.2,21.3,9.3c7.9,4.8,15.8,6.5,25.1,6c0.4-1.2,0.8-2.4,1.5-3.4
          L1778.1,872L1778.1,872z"/>
      </g>
      <g id="tajikistan" data-country="Tajikistan">
        <path class="st0" d="M1697.4,800.5c-3.8-4.5-4-10.5-7.1-15.4c-10.3-0.2-20.7-1.6-31-0.6c-0.6-2.2,0.1-5.1-1-7.3
          c-3,0.8-3.5,4.5-6.1,5.9c-1,1.1-3.4,0.8-3.6,2.5c-0.1,1.1,1,1.2,1.2,2c0,1.4,1.9,1.9,1.6,3.3c1.6,2.6,3.1,8.3-0.9,9.2
          c6.8,7.2,10.5-1.9,17.2-5.1c1.8-1.3,3.9,2.8,4.5,3.8c3.3,3.5,1.3,4.7,2.8,7.9c7.9-2,16.4-1.1,24.5-2.4
          C1699,802.8,1698.2,801.7,1697.4,800.5L1697.4,800.5z"/>
      </g>
      <g id="sri-lanka" data-country="Sri Lanka">
        <path class="st0" d="M1737.4,1008c-1.3-3-3.7-6-6.5-7.8c-0.9-2.6-2.2-2.2-5.1-3.3c2.6,7.7-2.5,17.7,1.4,22.5
          c4,1.4,11.5,1.8,12.4-3.7C1743,1013.2,1738.7,1010.3,1737.4,1008L1737.4,1008z"/>
      </g>
      <g id="india" data-country="India">
        <path class="st0" d="M1839.7,867.7c-8.1-7.8-9.7-6.1-20.2-6.1c-3.8,2.5-6.9,7.3-11.7,7.5c-0.2,1.3-0.3,2.6,0,3.9
          c-2.3,5.2-9.2,5.5-14.2,5.2c-2.6-0.3-4.3-3-7-2.4v-5.2c-2.8,0.9-5.4,1.4-8.4,1.2v6.2l0,0c-0.5,0.6-1.4,2-2.6,3.7
          c-9.1,0.4-16.5-1.4-24.1-6c-5.7-3-12.9-2.8-18-7.1c-9.1-5.3-4.3-3.5,2-13.2c-2.9-5.2-10.4-4.9-14.7-8.2c0-0.3-1-8.2-1-8.5
          c11-2.7-3.4-7.2,3.2-12.1c2.1-2.6,8.2-1.8,7.8-6.1c-0.8-6.5-11.9-12.1-16.6-5.9c-6.1,8.3-16.3,5.7-25.2,6.1c0.4,4.3,1.2,8.8,1.6,13
          c0.4,3.7,8.8,3,8.4,7.4c-1.7,0.5-3.6,1-5.3,1.5v6.9c-6,6.5-11.4,15.5-19.2,20.6h-10.6c-0.3,2.5-1.6,5.5-1.1,8.1
          c4.8,0.4,5.1,5.7,6.4,9.1c1.1,2.4,3,4.7,4,7c-6.3,3.1-13.6,1.5-20.5,2.1c4.6,4,7.8,9,11.6,13.5c1.6,0.9,3.2-0.9,4.6-1.6
          c0.8-10.2,5.5-2.3,8.8,1.8c4.6,3,1.9,9,2.4,13.6c0.3,3.5-0.1,8.2,2.7,10.8c1,3.2-0.3,7.3,2.1,10.1c1.3,4.3,1.6,9.7,4.9,13.4
          c5.5,9.9,4.6,22.1,11.9,31.1c4.7,5,1.8,14.2,10.1,15.7c2.6-7.1,9.9-11.8,11.7-19.5c4.8-8.1,4-18.9,4.5-28.2
          c-0.7-16.8,22.6-19.7,32.5-29.4c3.3-2.4,8.5-4.6,9.8-8.7c2.8-4.9,8.9-11.9,15.2-9.5v-13.2c-2.6-0.8-7.1-1.5-7.9-4.5
          c1.3-0.7,3.2-1.2,3.8-2.9c0.6-1.7-2.1-1.9-1.9-3.6c1.4-1.3,3.3-2.9,4.6-4.1c1.6,2.1,4.4,0.1,6.2,1.7c1,7.5,7.4,5.9,13.3,6.2
          c2.2,4.9-0.1,6.6-0.1,10.7c4.3,1.3,8.6,5.8,4.9,10c1.1,1.1,2.1,2.3,2.8,3.6c2.7-4.9,3.3-10.9,5.8-15.9c7.9,0.3,10.5-13.3,14.1-19
          c0.2-1,1.3-1,2.2-1.1c2.9-0.2,4.3-2.7,6.8-3.7V868C1839.9,867.9,1839.8,867.8,1839.7,867.7z"/>
      </g>
      <g id="pakistan" data-country="Pakistan">
        <path class="st0" d="M1666.2,896.6c2.6,0.5,4.6-1,7-1.8c-1-2.3-2.9-4.6-4-7c-1.2-3.4-1.7-8.7-6.4-9.1c-0.5-2.6,0.8-5.6,1.1-8.1
          h10.6c7.8-5.2,13-14,19.2-20.6c0,0,0-6.9,0-6.9c1.7-0.5,3.6-1,5.3-1.5c0.1-4-6.3-4-8.4-6.6v-1c-0.4-4.2-1.2-8.8-1.6-13
          c8.8-0.4,19,2.3,25-5.9c0.1,0,0.1-0.1,0.2-0.2c-8.3-2.1-10.8-2.5-14.6-10.8c-8.1,1.2-16.6,0.5-24.5,2.4c-1.2-0.5-0.6-2-0.6-1.3
          c0.5,5.8,1.8,11.8-3,16.3c-1.2,1.1-2.2,2.1-1.6,3.9c-3.8-0.3-5.6-1.7-8.7,1.7c-1,1,4.1,5.2,5.1,5.7c-2.5,3.1-6,6-8.5,9.3
          c-0.8,3.9-5.2,0.4-7.1-0.5c-20.6,5.3-4.7,10-17.8,16.2c0,0-27.4,0-27.4,0c2,4.9,5.6,8.3,9.4,11.7c7.7,2.3,15.7,2.9,23.6,4.3
          c2.1,3.1,4.7,5.9,7.4,8.5c2.6,4.6,2.8,10.5,6.5,14.6C1657.2,896.8,1661.7,896.5,1666.2,896.6z"/>
      </g>
      <g id="afghanistan" data-country="Afghanistan">
        <path class="st0" d="M1674.7,801.8c-2-1.8-3.4-7.7-6.7-7c-4.5,2-8.2,7.8-13.1,8.1c-1.2-1.1-2.8-2-4.1-3c-3.9,2.1-9,0.7-12.1-2
          c-3,0.8-6.1,1.9-8.9,3.2c-1.2,1.6,0.7,4.1,0.2,5.7c-13.7,8.3-9.4,12.4-26.3,11.3l-0.1,6.3c-0.7,0.8-4.1,1.4-4.5,2.5
          c-0.4,4.7,0.8,9.4,1,14.1c2.5,0.2,5.2,0.1,7.7,0.8c-1.8,2.3-4.2,4.3-5.4,7.3c-0.4,0.6-1.8,1.3-1,2.1c2.7,1,3.1,4.1,4.4,6.3h27.4
          c13.1-6.3-2.8-10.7,17.8-16.2c1.9,0.9,6.3,4.3,7.1,0.5c2.5-3.3,5.9-6.1,8.5-9.3c-0.8-0.5-6.3-4.8-5.1-5.7c3.1-3.3,4.9-2.1,8.7-1.7
          c-0.7-1.7,0.5-2.8,1.6-3.9C1677.4,815.8,1674.7,808.5,1674.7,801.8L1674.7,801.8z"/>
      </g>
      <g id="united-arab-emirates" data-country="United Arab Emirates">
        <path class="st0" d="M1575.5,892.1c4.3,6.2,12.1,8.3,19.3,9.8c3.7-8.9,5-13.5,15.4-16.5c-4.4-3.1-5.9-2.8-10.5-7.2
          c-1.8-1.8-2.8-3.8-5.3-3.5c-9.7,0.4-13,10.5-18.9,16.5l0,0l0,0C1575.4,891.5,1575.4,891.8,1575.5,892.1L1575.5,892.1z"/>
      </g>
      <g id="oman" data-country="Oman">
        <path class="st0" d="M1624.3,893.1c-4.6-2.3-9.1-6-14.3-7.7c-10.1,2.8-11.9,7.9-15.4,16.5c1.6,0.5,1,1.2,1.4,2.4
          c1.3,1.6,0.8,4.3,1.8,6.1c-3.6,7.2-14.2,11.5-21.7,14.2c4.1,4.6,8.4,9,12.4,13.7C1595,934.4,1638.9,896.9,1624.3,893.1
          L1624.3,893.1z"/>
      </g>
      <g id="yemen" data-country="Yemen">
        <path class="st0" d="M1576.4,924.6c-11.8,6.1-30.7,4-36.5,17.8c-1.3-1-2.9-2.4-3-4.1c-17.5-4.3-22.4-3.5-37.1,6.6
          c6,7.6,8.2,30.7,13.6,32.6c8.6-2.2,15.5-9.7,24-12.5c18.4-6,34.7-17.7,51.4-27.1C1584.9,933.4,1580.5,929.3,1576.4,924.6
          L1576.4,924.6z"/>
      </g>
      <g id="saudi-arabia" data-country="Saudi Arabia">
        <path class="st0" d="M1598.1,910.5c-1-1.9-0.5-4.5-1.8-6.1c-0.5-1.2,0.3-2-1.4-2.4c-14.7-2.8-20.6-6.9-23.1-22.4
          c-1.3-3.8-5.8-1.2-8.7-2.4c-5-8.6-8.1-18.5-14.9-26.3c-5,1.3-12.8,0.7-15.9-4.1c-0.1,0.1-0.2,0.1-0.2,0.2c-3,0.9-6.4-2-9.8-1.3
          c-4.8-0.4-12.1,0.2-13-5.9c-2.7-7.1-1-5.3-7.7-9.5c-6.1-4.6-13.8-7-21.1-8.8c-9.6,3.9-21.7,2.6-8,13.7c-1.4,2.2-5,3.7-6.5,5.9
          c-3.9-1.5-7.2,2.3-10.5,3.6c-6.5,1.6-9.2,7.4-13.1,12.3c6.8,14.4,19.6,24.5,24.2,40.1c7.7,9.9,14.8,20,20.4,31.4
          c3.7,5.7,10.2,9.9,12.6,16.5c14.8-10.1,19.6-11,37.1-6.6c0,1.9,1.8,2.9,3,4.1c4-11.5,17.8-10.7,27.3-14.7
          C1577.8,924,1592.1,920.7,1598.1,910.5z"/>
      </g>
      <g id="kuwait" data-country="Kuwait">
        <path class="st0" d="M1548.2,850.9c-2.4-3.7-6.3-6.8-8-11c-3.3,1.5-4.5,5.5-7.9,6.9C1535.4,851.6,1543.3,852.2,1548.2,850.9z"/>
      </g>
      <g id="jordan" data-country="Jordan">
        <path class="st0" d="M1473.4,812.5c-3.3,2-6.2,4.1-9.8,5.5c-2.8-0.5-5.3-1.6-7.9-2.6c-1.8,11-8.2,21.8-6.4,32.7
          c1.4-2.1,4.2-1.8,6.2-3.3c3.2-1.4,6.3-4.7,10-3.8c0.6,0.6,1.2-0.4,1.4-0.9c1.5-1.6,4.5-3,5.6-4.8c-11.2-9.5-6-9.4,5-12.7v-12.8
          C1476.2,810.7,1474.8,811.6,1473.4,812.5z"/>
      </g>
      <g id="israel" data-country="Israel">
        <path class="st8" d="M1453.9,814.8c-0.5-0.1-1.1-0.2-1.7-0.2c-9.5,6.3-3.3,12.1-10.4,16.6c2.9,4.9,5.5,10,7.1,15.6
          c-0.1-10.7,4.8-20.7,6.9-31.4C1455.1,815.1,1454.5,814.9,1453.9,814.8z"/>
      </g>
      <g id="syria" data-country="Syria">
        <path class="st0" d="M1497.5,785.3c-15.6-0.4-31.2,0.7-46.7,2.3c-2.2,0.8-3.3,4.1-4.6,6.1c1.5,0.1,3.1,0.7,4.5,0.5
          c2.8-1.2,8.1-7.7,7.3-0.2c-1.1,2.8-4.6,3.8-6.2,6.2c-0.1,4,0.6,14,0.2,14.6c4,0.1,7.4,2.9,11.4,3.4c10.5-5.1,19.8-12.5,30.6-17.1
          c1.6-6.2-1.3-10.4,4.6-15.1C1498.4,785.8,1498,785.5,1497.5,785.3L1497.5,785.3z"/>
      </g>
      <g id="iraq" data-country="Iraq">
        <path class="st0" d="M1541.8,835.5c0.6,0.1,1.1,0.3,1.5,0.6l-1-14.8c-0.6-0.5-1.5-1.2-1.5-2.2c-4.2-2.9-8.9-5.5-13.5-7.9
          c-1.4-1.8-2.1-4.4-3.3-6.4l3.7-3.7c-5.9-4.9-6.7-5.1-3.2-12.2c-7.3-10.5-14.4,5.7-25.3-2.9c-6.1,4.8-2.9,8.6-4.6,15.1
          c-5.6,2.6-11.3,5.7-16.6,8.9v12.8c10.2-2.3,20.6,5.9,29.1,11.1c1,0.7,1.4,1.5,1.2,2.6c0.6,2.4,1.9,4.9,2.8,7.1
          c6.4,3.4,14.1,1.7,20.9,4c3.6-1.5,5.1-5.7,8.6-7.4c-0.3-1.1-0.4-2.3-0.4-3.5C1539.7,836.3,1540.7,835.2,1541.8,835.5z"/>
      </g>
      <g id="georgia" data-country="Georgia">
        <path class="st8" d="M1514.9,773.6c-3.9-5.7,2.6-7.1,8.1-6.1c-1.6-1.4-2.7-3.2-4-4.8c-9.5-1-17.9-5.8-23.1-15.4
          c-0.5,1.8-2.9,1.6-4.1,2.8c5,4,8.5,8.9,11.1,14.7c2.7,3.6,5,7.5,6.4,11.8c3.9,1.3,6,5,9,7.7
          C1518.6,780.5,1516.7,775.3,1514.9,773.6z"/>
      </g>
      <g id="turkey" data-country="Turkey">
        <path class="st0" d="M1517.4,783.8c-2.7-2.3-4.3-5.7-7.8-6.8c-0.4,0-0.2-0.4-0.7-0.4c-1.4-4.4-3.7-8.1-6.4-11.8
          c-2.6-5.8-6.1-10.7-11.1-14.7c-6.5,5-18.7,4.2-26.5,3.1v0.1c-4-0.3-8.9-1.9-10.6-5.9c-1.8-1.6-5-0.1-6.4-2.2
          c-4.7-4.3-13.3-4-18.8-1.4c-3.7,1.4-8.7,3.7-12,6.3c-2.2-0.1-4.4-0.3-6.6-0.4l0.1-0.1c0,0-0.3,0-0.7,0c-5.1,0.2-10,0.1-14.9-0.5
          c-0.8,0.1-3-0.2-3.3-0.4c-1.3-4-3.7-7.3-7.9-8.5c-4.7,2.6-8.3,6.9-13.1,9.4c3.5,1.1,6.5,4.8,9.7,6.2c7.2,0.9,15-6,21.5-2.5
          c3.4,7.4-18,3.1-22.1,7c-3.6,1.2-0.6,4.3-0.5,6.7c0.8,4.4-2.9,9.6,1.8,12.6c3.1,2.1,0.8,7.5,5.3,8.7c11,5.4,6.8,7.9,20.6,7.1
          c-2.2-13.6,2.5-7.9,9.5-2.6c2.7-0.3,5.3,0.8,8,0.9c11.8,4.4,10.3-3.8,15.3-3c2.2,1.1,4.1,2.8,6.8,2.7c1-1.8,2-5.4,4.2-6
          c5.2-0.1,10.2-1.6,15.4-1.3V786c10.4-0.8,20.8-1.3,31.2-1c6.7,5.5,14,2.8,20.9-0.4C1518,784.4,1517.7,784.1,1517.4,783.8z
          M1518.5,784.7c0,0-0.1,0-0.2,0C1518.5,784.8,1518.6,784.9,1518.5,784.7z"/>
      </g>
      <g id="azerbaijan" data-country="Azerbaijan">
        <path class="st0" d="M1519,784.9c2.4,0.1,4,2.5,5.6,4.1c2.6-9.7,6.4-11.8-1.6-21.5c-17.5-1.7-3.9,7.5-4.6,16.8
          c0.1,0.1,0.2,0.2,0.4,0.4C1518.8,784.8,1518.9,784.9,1519,784.9z"/>
      </g>
      <g id="iran" data-country="Iran">
        <path class="st0" d="M1614.4,868.5c-7-3.6-7.6-12.6-13.1-17.1c-0.8-0.7,0.5-1.5,1-2.1c1.2-3,3.6-5,5.4-7.3
          c-2.4-0.7-5.2-0.6-7.7-0.8c-0.1-4.7-1.5-9.4-1-14.1c0.4-1,3.8-1.7,4.5-2.5c0-3.9,0.2-12.6,0.3-16.5c-11-1.2-18.5-12.1-28.6-14.3
          v0.3c-5.9-0.2-11.7,0.7-17.6,1.2c4.4,20.5-20,15.6-29.8,6.3c-0.1,0-0.2-0.1-0.2-0.2l-3.7,3.7c2.2,8.2,10.7,9.6,16.8,14.3
          c0.1,0.9,0.9,1.8,1.5,2.2c0,0,1,14.8,1,14.8c6,7.3,9.8,4.4,14.8,13.9c2.5,4.4,8,5.2,10,10c6,11.8,9.1,4.7,18.9,2.7
          c12.4-4.3,8.1,6.7,16.9,6.1c3.8,0.1,7.5-0.5,11.1,0.6C1614.9,869.2,1614.7,868.9,1614.4,868.5L1614.4,868.5z"/>
      </g>
      <g id="turkmenistan" data-country="Turkmenistan">
        <path class="st0" d="M1638.3,797.8c-10.1-7.2-19.8-14.6-25.9-25.5c-1.9-3.1-7.2,0.6-10.1,0c-0.6-0.5,0.1-1.6-0.2-2.3
          c-2.8-1.9-3.8-5.1-6.6-7.2c-5.2-6-20.7,1-21.8,7.6c-0.6,1.9-5.6,1.3-7.3,0.7c-1.7-3.5-3.4-7.8-6.4-10.2c-2.3,0.1-4.6-0.3-7-0.5
          c1.2,3.5-3.7,2.5-3.6,5.4c8.7-0.9,11.7,0.2,11.8,9.7c-4,1.6-9.9-1.3-12.6,2.6c-1.1,6.7,6.2,5.1,8.8,17.2c5.9-0.5,11.7-1.4,17.6-1.2
          v-0.3c7.6,0.7,13.3,9,20.4,11.8c2.8,0.7,5.5,1.6,8.2,2.5c0,0.3-0.2,9.9-0.2,10.2c17.1,1.1,12.4-2.9,26.3-11.3
          c0.5-1.6-1.3-4.1-0.2-5.7c2.8-1.3,5.8-2.4,8.9-3.2C1638.5,797.9,1638.4,797.8,1638.3,797.8L1638.3,797.8z"/>
      </g>
      <g id="uzbekistan" data-country="Uzbekistan">
        <path class="st0" d="M1679.7,774.4c-3.6-2-8.2-1.7-11.3-4.5c-0.1,0-0.2-0.3-0.2-0.3c-1.8-0.7-2.9-1.6-2.5-3.7
          c-8.5,0-17.4,2.5-25.8,1.3c-3.8-4.3-4.3-10.5-7.4-15.2c-0.3-1.4-1.3-2.4-2.8-2.2c-7.5,0.2-16.2,3.7-23.1-0.7
          c-6.5,2.3-14.2,5.3-20.7,1.5c0.1-5.4,0.2-11,0.7-16.3c-4.9,1.9-9.8,4.1-14.7,5.6v31.4c2.9,0.1,1.5-2.2,2.7-3.8
          c6.7-6.1,17.6-11.4,24-2.1c1.1,1.6,1.9,3.4,3.7,4.4c0.3,0.6-0.3,1.9,0.2,2.3c3.7,0.3,9.7-3.7,11.1,1.6
          c6.1,10.5,15.5,17.1,25.1,24.1c3.7,3.4,9.5,3.9,13.8,1.3c2.3-4.1-0.7-8.4-2.4-11.8c-1-1.1-2.1-2.8,0.1-3.3c4-1,4.8-5.7,8.4-7.1
          c10.3-5.7,5.7,7.6,21.1-2c0,0,0.4,0,0.4-0.1C1680.3,774.8,1680.1,774.7,1679.7,774.4L1679.7,774.4z"/>
      </g>
      <g id="kyrgyzstan" data-country="Kyrgyzstan">
        <path class="st0" d="M1730,757.7c-9.9,0.6-45.5-9.8-48.2,2.1c0.1,0.2,0.1,0.3,0.1,0.5c-4-0.9-12-6.3-13.7-0.1
          c-1.5,2.4-3.8,6.7-1.2,8.9c0.6,0.4,1.4,0.4,1.7,1.1c2.8,3.2,9.6,2,11.6,5.3c0,0-0.3,0.1-0.4,0.1c-15.5,9.6-10.9-3.8-21.1,2
          c1.1,2.3,0.5,5.1,1,7.3c10.3-0.9,20.7,0.4,31,0.6c-1.2-1.6,0.7-4.7,1.1-6.5c0.4-3.1,4.1-1.6,6.2-1.7c8.2,0.3,15.6-4.3,23.4-6
          c2.5-5.1,7.5-8.5,10.6-13.3C1731.1,757.6,1730.6,757.6,1730,757.7L1730,757.7z"/>
      </g>
      <g id="china" data-country="China">
        <path class="st0" d="M2074.3,706.9c0-6.7-14.3,2.3-20.9-0.5c-2.6-0.3-2.2-6.3-3.2-8.5c-1.9-11.7-18-2.2-22.6-9.4
          c-11.3-6.3-7.9-30.4-24.1-27.8c-5.1,1.3-30.1,1.6-29.1,6.7c2.9,1.2,9.3,3.6,5.4,7.4c-1.7,5.7-6,10.7-7.4,16.5c-5,2-13.5-3-19.9-0.8
          c0.2,0,0.3,0.1,0.5,0.1v12.7c1.4,2.6,2.1,4.4,5.6,3.7c1.9-0.5,5.7,1.3,5.5-1.5c5.6-2.9,15.4,7.7,17.1,10.8
          c-3.6,1.1-12.4,1.1-16.2,1c0.1,7.6-14.5,11-16.8,16.6c-4.9,9.7-19.1-5.2-15.1,11.8c0.1-0.1,0.2-0.2,0.4-0.2c0.1,0.4,0.1,0.7-0.1,1
          c-0.2,0-0.2-0.4-0.3-0.8c-5.4,7.9-13.6,14.2-21.8,18.9c-8.2,1.6-16.7,2.7-24.7,5.6c-5.1,1.1-10-2.8-14.5-4.7
          c-4.9-1.9-10.6-3.2-14.8-6c-0.5-0.5-16.6-0.3-17.7-0.3c-6.8-2.2-11.3-9.4-15.8-14.8c-1.7-0.4-0.2-3.4-2.3-3.1
          c-5.5,1.3-12.5,0.5-18.1,0.7c-11.1-4.7-1.6-10.4-9.3-23.9c-2.1-1.8-6.5,0.1-9.2-1.4c-4-0.4-1.1-6.9-2.8-9.8
          c-3.2-0.8-6.3-1.9-9.3-3.1c-0.1,6.4-10.4,6.1-8.9,12.5c0,1.5,0.8,6-1.3,6.1c-5.2-0.5-11.6-1.9-16.2,0.9c-0.9,5.1-4.5,9.6-3.1,15
          c-1.3-0.1-13.6,0.3-14.5-0.5c4.9,25.6,14,4.6-7.4,33.5c-7.8,1.7-15.3,6.3-23.4,6c-2.1,0.1-5.9-1.4-6.2,1.7
          c-2.2,3.7-1.4,7.1,0.9,10.4c1.1,3.9,2.4,8.3,5.1,11.5c5.9,9,5.3,12.1,16.9,14.2c4.7-6.3,15.8-0.6,16.6,5.9c0.4,4.5-5.7,3.5-7.8,6.1
          c-6.6,4.9,7.8,9.3-3.2,12.1c0,0.3,1,8.2,1,8.5c4.3,3.3,11.7,3.1,14.7,8.2c0.2,0,6.2,0,6.4,0c2.8-0.3,4.8,2,6.8,3.7
          c2.7,2.9,6.4,4,9.2,6.6c13.2,6.2,19.1,7.9,33.6,4.7c2.8-0.1,5-2,7.6-2c8.9,3.1,13.9-0.6,20.3-6.5c3.6,0,8.1,0,11.8,0
          c3.7,0.8,6.4,5.4,9.7,7.1c0,0,6.4,0.3,6.4,0.3c2.9,2.6,3.7,6.9,6.6,9.4c-1.4,3-2.6,5.8-3.1,9c-2,2.8-4.5,5.2-6,8.2
          c0,3.3,5.1,0.3,7,0.7c4.3,3.6,7.7,10.7,10.2,15.5c1.1,0.1,3.9,0.2,4.9,0.3c-1-6.6,7.5-3.4,11.1-5.5c6.5-6.8,19.1-7.5,25.9-0.6
          c4.1-1.9,8.4-3.8,12.7-4.9c2.8-0.1,3,6.1,3.2,7.9c0,5-4.7,7.9-7.4,11.5c-5.9,5.5,4.4,5.2,7.4,2.8c10.7-1.4,6-13.4,9.2-20
          c6.4-2.2,13.9-2.8,19.4-7l1.4,0.5c3.4-0.7,5.2-3.4,7.3-5.9c9.4-4.7,18.2-10.4,22-20.5c5.1-5.1,8.1-7,7.9-15.3
          c0-2.8,10-9.8,5.4-11.8c-1.4-0.1-4.6,0.8-5.6-0.5c-1.2-1.7,1.4-2,2.2-2.2c2.2-0.3,7.6-5.7,2.2-6c-2.5-0.9-4.7-3.6-5.6-6.2
          c2.3-0.4,4.7,0.3,7,0c1-4.5-2.4-8.3-4.6-11.6c-1-3.1-1-6.4-2.7-9.3c-6.1-6.1-10.2-0.2-6.1-14.2c0.1-0.2,0.1-0.3,0.2-0.5
          c4.7-3,10.5-4.4,14-8.8c3.3-0.8,5.3-3.1,0.8-4.1c-1.7-1.5-3.4-3-5.9-2.6c-5.5-5.3-5.8,5.3-10.6,5.6c-4.7,0.6-3.3-6.7-7.6-7.7
          c-7.7-0.3-8.2-2.9-5.5-9.7c1.7-2,4.6-2.1,6.8-2.5c10.8,0.2,13.7-13,23.3-14.4c1.5,4.6-1.8,7.5-3.2,11.4c-0.4,3.4,0.9,6.6-1.7,9.3
          c4.2,2.3,6.1-3,8.8-5c18.3-3.7,35.5-10.1,51.9-19.1c-0.7-5.8,3.1-10.4,7.5-13.6c0.2-3.5,1-7,4.2-9c2.7-1.7,5.8-0.8,8.6-1.9
          c0.8-0.9,0.4-2.9,0.7-4.2C2071.9,713.6,2075,710.7,2074.3,706.9z M1990.1,884.3c-0.4-0.7-1.4-1.1-1.4-2c-3.7-0.3-4.7,5-6.6,7.4
          c-1.7,2.1-4.9,3.2-6.4,5.6c-0.6,3.8,1.3,12.7,3.9,14.6c5.4,0.5,4.4-5.7,5.9-9C1990,896.9,1991.3,890,1990.1,884.3z"/>
      </g>
      <g id="mongolia" data-country="Mongolia">
        <path class="st0" d="M1782.9,714.3c-0.2,4.1,7.1,2.6,10,3c2.3,0.7,3.4,7.2,4.6,9.6c0.7,6.5-2.1,12.4,5.9,15
          c5.6-0.2,12.6,0.6,18.1-0.7c2.1,0,0.6,2.6,2.3,3.1c4.5,5.5,8.9,12.6,15.8,14.8c1.2,0,17-0.2,17.7,0.3c4.1,2.8,10.1,4,14.8,6
          c4.5,2,9.3,5.7,14.5,4.7c8-2.8,16.5-4,24.7-5.6c8.2-4.8,16.4-11,21.8-18.9c-4.2-16.8,10.4-2.2,15.1-11.8c2-5.4,17.1-9.2,16.8-16.6
          c3.8,0.1,12.5,0.1,16-1c-1.7-3.1-11.4-13.6-17.1-10.8c0.2,2.8-3.6,1-5.5,1.5c-3.3,0.7-4.3-1.2-5.6-3.7v-12.7
          c-10.7-4.5-15.6,1.1-21,9.8c0.1,0.1,0.2,0.2,0.2,0.3c-2.5,0.7-5,0.4-7.5-0.2l0,0c0.6,0.1,1.2,0.2,1.7,0.5c-0.4,0.5-1.1,0-1.7-0.4
          l0,0l0,0c-7.5-5-18.3-9.9-26-13.4c-10.5,1.9-5.5,3.9-18.2,2.8c-5-2.7-1.9-10.8-7.4-13.3c-6.6-1.7-15.1-5.4-21.5-2.4
          c-6.7,4.1-10.4,3.7-3.9,11.2c0.5,0.5,0.9,0.3,1.5,0.4c-1.1,17.8-30.2-3.6-41.3,2.6c-9.7,2.9-17,10.6-26.5,13.9
          C1782.2,706.4,1783.4,710,1782.9,714.3L1782.9,714.3z"/>
      </g>
      <g id="kazakhstan" data-country="Kazakhstan">
        <path class="st0" d="M1773.9,696.7c-0.6-4.1-6.5-0.1-9.2-1.7c-3.6-4.6-9.7-7-12.8-12.2c-2.9-1.2-6.1,1.9-9,1.8
          c-2.9-4.2-7.9-4.1-12.2-2.7c0.1,0-1.2,0.3-1.2,0.3c0,0.4,0,0.9,0,1.3c-2.3-17.3-20.7-18.9-23.7-31.9c-0.3-2.7-6.5-2.4-8.7-2
          c-3.7,1-3.4,6.1-6.7,8c-5.8,3.2-3.6-11.3-17.7-7.2c-0.4-2.8,0.1-6.3,0.6-9.1c-6-0.6-12.3-2.6-18.4-2.4c0,0.1,0,0.2,0,0.3
          c-15.9,1.9-30.4,10.2-46.2,12.9c-4.1-0.2-7.2,4.3-4.2,7.6c8.1,4.3-4.6,4.4-3.1,14.6c13,5.4,4.4,10.6-4.6,12.9
          c-9.1-2-19.2-5.6-28.4-2.3c-9.2,4.8-13.6-8-21.2-10.2c-7.7,1-14.7,3.9-20.8,8.7c-3,1.4-6,3.4-5.1,7c-1.4,1.7-5-0.2-6.9-0.8
          c-8.3,6.5-4.9,14.4-6.4,22.7c8.8,2.1,11,1.7,14.5,11c13.8-2.3,27-10.8,35.9,4.7c1.5,2.3,10.3,10.1,4,10.6c-5.2,2-10.4-1.5-15.4,0.1
          c-3.1,5.5-10.8,3.3-10.3,6c3.9,3,4.4,8.6,8.7,11.8c2.1,2.5,6.2,1.6,8.2,3.9c2.2,0.8,6.3-0.3,7.8,1.2c2.5,2.7,4.2,6.2,5.6,9.5
          c1.8,0.6,3.5,0.7,5.3,0.4V740c11.9-4.2,24.3-9.2,35.8-14.6c3.8-1.3,1.8,3,2.5,5.1c2.9,17-1.7,7.2-4.8,16.7
          c1.5,5.2,7.9,3.1,11.9,4.2c22-4.1,12.3,1.3,22.6,15.9c8.6,1.2,17.1-1.3,25.8-1.3c2-12.1,6.8-9.2,15.9-5.9c0-0.2,0-0.3-0.1-0.5
          c2.7-12.3,39.7-0.9,49.9-2.1c1-1.7,2.2-3.3,3.4-4.7c-3.4-4.5-6.4-9.8-6.6-15.5c-0.1,0-0.2,0-0.2,0c0-0.2,0-0.3,0.2-0.2
          c0,0.1,0,0.1,0.1,0.2c0.8,0.8,13.2,0.5,14.5,0.5c-1.4-5.4,2.2-9.9,3.1-15c6.9-5.5,20.2,5,17.5-7c-1.4-7.2,9.8-4.6,10.6-18.2
          C1774.2,697.6,1774,697.1,1773.9,696.7L1773.9,696.7z"/>
      </g>
      <g id="russia" data-country="Russia">
        <path class="st0" d="M1547.2,452.6c0.7,6.1,7.2,1.6,9.5-1c-0.1-2.4,4.3-3.2,3.1-6.5c1-3.5-3.3-3.9-4.9-6.3
          C1544.8,435,1550.2,447.7,1547.2,452.6z"/>
        <path class="st0" d="M1678.2,365.1c1.8,3.5,7.2,1.9,9.3-0.6c0.4,0.7,0.4,0.2,0.4-0.3c0.3-1.4-1.1-1.5-1.9-2.2
          c-2.6-0.8-0.4-4.4-1.8-5.9C1681.1,353.5,1675.2,363,1678.2,365.1z"/>
        <path class="st0" d="M2017.8,353.3C2017.8,353.4,2017.8,353.4,2017.8,353.3L2017.8,353.3z"/>
        <path class="st0" d="M1727.7,373.4c1,1.7,5.5,1.8,6.4-0.1c0.2-2,0.2-4.5-1.9-5.3c-2.1-0.6-5.8,2.4-4.9,4.6
          C1727.3,372.8,1727.5,373.1,1727.7,373.4z"/>
        <path class="st0" d="M1928.8,341.6c3.5,3.1,8.5-2.8,8.6-6.3c0.3-1.7-1.9-2.3-1.6-3.9c-2.8-0.3-5.8,1.6-7.9,3.2
          C1928.3,337,1925.8,340.2,1928.8,341.6z"/>
        <path class="st0" d="M2415.2,480.9c0.1-4.8-5.8-6.5-7.8-10.5c0.4-4.4-0.8-7.5-5.9-6.9c-1.6-1-3.1-2.4-5.2-2
          c-3.4-0.6-2.9,2.7-2.6,4.9c-0.1,2.3-3.6,5.5-5.9,3.7c-1.7-4.4,1.5-9.3,0.6-13.9c-2.3-10.2-13.7-15-20.3-21.9
          c-11.3-6.1-24.4-7.7-35.2-15.2c-4.8-1.8-13.6,0.2-14.8-6.5c-7.4,0.1-14.9,1.2-21.9,3.5c0.6,5.4,0.2,6.1,4.9,9.9
          c4.1,4.9,1.2,10.8-5.3,8.4c-2.8-1.5-3.6-4.9-6.8-6.1c-1-0.6-3.9-1.1-2.2-2.6c2.8-2.3,2.6-9.1-2.4-8.2c-4.7-0.1-7.8,3.4-9,7.5
          c-8,1.1-16.4-5.5-24.3-1.2c-3.1,3.2,1.9,14-6,13.9c-1.2-3.3,2-7.2,0.8-10.5c-1.4,1.1-0.4,2.9-3.4,2.4c-10.9,1.3-1.2-16-6.5-20.4
          c-13.3-10.2-17.4-14.2-34.8-8.2c-1.9,1-2.7,3.6-5.2,2.2c-3-0.8-6.8-0.2-9.2-2.4c-2.8-6.6-7.5-7.7-13.9-9.8c-1.4-0.5-2-0.6-1.9-2.2
          c7.2-8.7-4.1-6.1-5.9-16.4c-3.7-0.1-7.3,1.6-10.8,2.5c-0.2,4.6-0.8,9.7-2.2,14c-5.2,3.7-11.5,2.8-7.9-4.8c1.1-1.5,3.7-2.6,4.3-4.4
          c0.5-2.1,0.1-3.4-2.5-2.9c-1.6,0.3-4-0.2-3-2.3c2.9-1.8,6.7-0.7,4.5-5.8c-8.1-0.2-20.4-5.6-25.9,2.4c-1.8,2.1-10.2-0.6-9.8,2.4
          c0,1.6,2.6,1.3,3.5,2.4c1.7,1.7-1.3,4.6-2.5,5.6c-0.8,0.5-1.7,0.3-2,1.4c-0.6,5.7,11.3,8.3-0.8,9.6c0,4.2-3.1,5.1-6.9,5.5
          c-3-1.6-5.8-4.7-8.7-6.5c-6.7-4.7-10.3,1.3-16.6,1.3c-6.7-1-7.6-8.9-12.5-12.5c-10,3-6.3,15.4-12.1,24.2c-4.8,5.5-4.5,15.8-15.3,13
          c-0.3-0.5-0.6-0.9-1-1.3c0.2,0.1,0.3,0.1,0.3,0.1c-0.2-0.1-0.3-0.2-0.5-0.2c-9.5-8.2-2.9-22.1,0.4-31.8c-1.4-9,4.6-29.1-10-27.1
          c-3.2,0-10.3,1-9.3-3.9c-1.3-3.9-6.7-1.9-9.7-3.3c-14.8-6.8-14.4,3.2-5.7,12c3.4,5.1,10.6,4.9,9.6,12.9
          c-4.1,11.9-12.8-8.8-19.9-7.6c-5,1.3-10.6,2.3-15.6,0.9c-4.3-3.2-12.4-0.7-14.4-7.2c-0.4-2.4,1.1-6.1-2-6.9
          c-5.5-0.4-17.3-4-19.5,2.6c0.7,3.6-1.6,14.1-6.5,9.1c-2.4-4.8,0.7-10.6-0.4-15.6c-2.1-0.4-5.6,1.1-6.4-1.6
          c-0.2-1.1,0.5-1.8-0.7-2.2c-18.5,1.9-8.8,2.8-9.6,10.4c-3.7,7.5-25.8,3.2-20.8,14.6c-5.4,3.6-10.8,6.8-16.7,9.3
          c7-10.3,3.9-17.9,17.1-26.2c7.3-5.6,14.4-12.2,20.6-19.1c6.1-4.5,12.1-8.9,15.7-15.9c1.5-2,4.9-4,4.8-7c-0.2-5.6-8.9,0.2-7-8
          c1.5-0.5,5,0.3,5.1-1.9c-0.2-3.8,0.9-8.3-0.8-11.8c-2.7-1.3-4-2.2-2.5-5.3c1-5.3,0.6-7-5-7.9c-7-3-14.6-4.2-21.8-1.3
          c1.2,5.5-5.5,5.9-9.2,4.8c0-4.1,6.4-8.4,4.2-13.4c-4.5-4.1-14.3,2.6-18-3.2c0.7-4.7,7.5-5.4,8.7-9.6c-4.5-17.8-23.5,1.5-28,10.3
          c-0.8,3.4,3.1,4.4,2.1,7.9c0.6,5.4-4.5,10.2-9.9,9.3c-4-0.3-5.8,1.8-3.6,5.4c3.7,1.2,5.4,1.8,4.4,6.3c-3.6,1-4.3-4.1-7-4.8
          c-7.8-3.2-18.6,16.5-16.7,1.3c-0.6-1.4-2.1-0.3-3-0.1c-4.3,0.8-8.8,1.8-13.3,2.4c-8.4,2.3-4,3.1-6.1,8.4
          c-8.3,10.1-23.4,12.7-31.2,23.2c0.3,6.2-7.5,11.4-1,15.4c5,14.7-22.6,0.5-38.1,16.4c2.6,4.9,1,11.5,2.6,16.8
          c1.7,4.3,5.8,7.1,6.5,11.9c0.9,8.3,9.6,6,14.6,10.1c3.4,3.1-2.5,6.2-2.1,9.6c-1.3,2.7-0.7,4.7,0.8,7.2c0.7,4.7-2.6,8.8-1.6,13.6
          c-0.2,12.6-12.6-1.2-12.8-7.3c-2.5-18.7,3.8-9.2,4.5-18.3c0.4-4.6-4.8,0.9-10.8-11c-3.7-4.9-11.4-3.8-16.4-6.7
          c-10.3,0.8,6.8,7.7,0.7,11.6c-2.1,0.3-4.5-1.1-6.6-0.3c-0.2,7.8,11.1,10.2,10.7,17.7c-1.4,3.3-2.9-1.7-4.7-1.9
          c-4.5-2.7-9.9-2.8-14.7-4.7c0.6-8.6,4.8-17.3,1.3-26.2c-3.6-2.5-4.1-10.5-9.1-10.4c-2.3,2,2.1,5.6,2.7,7.9
          c6.6,13.1-8.2,10.8-7.4,21.6c0.1,3.8,1.3,7.7,4.3,10.3c3.3,1.9,2.1,6.5,2.1,9.7c-4.8,4.8-8.4,9-4.7,16c0.2,3.6-0.6,7,2.6,9.5
          c2.9,3.7,6.2,0.6,9.5-0.5c3.4-1,5.3,1.1,7.8,2.8c4,3.4,11,4,10.5,10.6c0,3.5-3.1,6.5-1.2,10c7.9,7.8,8.8,9.4-3.4,7.9
          c-2.4-0.6-2.2-2.7-2.3-4.7c-0.4-1.7-1.7-2.9-2-4.7c0.6-11.5-0.7-12.8-12.1-9.8c-0.4,1.4-2.4,0.9-2.1,2.6c0.7,8.3-1.4,15.7-5.6,22.6
          c-4.3,6.1-9.2,14.8-17.2,15.8c-3.7,0.2-7.8,0.7-9-3.7c-0.9-1.7-1.3-2.6,0.1-3.9c8.4-5.7,18.1-10.5,22.6-20.5
          c6-5.3,0.9-10.8-0.4-16.9c-0.3-0.2-1-2.3-1.6-2.1c-0.3-11.4-0.1-23.7-0.1-35c0.5-6.5-5.8-10.9-5.6-17.1c3.7-4.8,8.3-23.4,2.6-27.4
          c-4.1-0.3-7.6,3.7-11.9,3.5c-3.4-0.3-10.2-3.2-8.9,3.1c-0.5,5.5-3.9,9.3-2.6,15.5c3.7,14.4-20.3,13.1-10.4,26.1
          c0.6,2.2-0.5,4.6,0.4,6.6c1,12.6,0.4,18.1,9.1,28.9c0.6,4.6,2.4,13.6-4.8,7.3c-2-2.1-1.3-5.5-3.3-7.7
          c-13.6-32.1-15.1-9.3-37.6-22.5c-2.7-2.4-5.8-2.9-9.2-2.5c-5.6-0.9-5.3,5.6-1.1,7.6c4.4,6.9,14.2,6.8,18.2,13.6
          c-2.3,7.6-17.2,12.9-16.7,0.9c-2-0.3-3.9,0.1-5.8,0.5c0.4,7.7-10.7,3.5-15,5.5c-0.3,3.1-2.7,4.9-5.7,3.4c-6,0.1-2.4-4.8-1.3-8
          c2.1-9.6-10.9,6-16.8,5.1c-3.9,0.6-5.4,3.6-7.5,6.6c-4.9,5.2-14,2.8-18.3,8.9c-1.6,3.8,0.9,12.8-5.1,13.2c-1.5-1.5-2.3-3.9-4.3-5.1
          c-2-2.3-3.4-7.6-1-9.7c4.4-0.4,7.5-4.8,5-8.8c-5.1-4.8-14.3-12.1-21.4-7.8c6.7,3.7,12.4,7.4,8.8,15.9c-1,2.3-4,5.1-3.2,7.6
          c4.6,1,1.7,2.8,4.8,6.6c-0.1,0-0.1-0.1-0.1-0.1c-5.9-4.3-15.5,0.8-19.4,5.9c-2.7,2.4-7.8,5.5-8.4,9.1c-2.5,2.8,0.5,7.3,1.4,10.4
          c-7,1.9-21.4-12.8-24.8-4.6c3.3,4.7,10.4,7.1,14.2,11.7c4.3,9.5-8.1,6.4-11.6,1.9c-3.8-2.8-8.6-4-12.8-5.7c-0.8-3.4-2.6-6-4.5-9.1
          c-1.4-6.6-4.5-12.4,0.2-18.2c-0.4-5.5-20.5-6.6-23.4-11.7c-4.4-13.1,41,12.4,46.7,13.5c5.6,2.5,10.4-1.7,15.8-2.4
          c5-1.1,11.9-3.8,11.1-10c-2-9.2,1.5-12.3-9.6-15.7c-11.4-7-20.8-16.7-32.5-22.9c-4.8-1.9-10.4-1-15.2-1.8c-1-3-4.8-1.9-7.3-2.9
          c-3.4-0.8-5.3-3.3-0.9-4.3c-3.6-9.5-16.7,2.1-23.2-1.2c-1.2-2.4-7.1-2.7-5.5-5.8c-3.3,1.2-4.6,4-6.1,6.8
          c-5.6,4.4-9.7,14.9-0.6,17.7c2,4.7,5.2,9.2,6.5,14.1c-8.6,5.7-3.1,22.2,1.2,29.5c0.7,4.9-2.4,10.8-1.5,15.6
          c1.2,4.3,4.6,7.9,5.1,12.4c-3.2,6.1-0.9,12.9,4.6,16.8c0.1,5.5-3.3,9.4-7,13.2c-4.1,5.9-8.1,12.2-12.9,17.5c2.9,5.7,8.7,6.8,14.8,7
          c2.1,0.2,3.9,1.7,3.6,3.7c-3.6,2.1-8.3,0.2-12.3,0.5c-10.1,0.6-19.9,0.2-29.5,4.3c1.4,8.1,3.8,18.2-1.1,25.5
          c1.3,4.2,3.9,8.2,5.8,12.2c20.3,1.4,26.3,11,28.4,30.9c8.1-2.5,10.6-2,16,4.6c-6.6,1.6-3.9,9,2.2,6.7c0.6,2.4,1.8,5.2,3,7.1
          c5.6,1,11.7,1.3,17.1,3.1c3.2,1.1,5.5,3.9,8.7,5.2c2.6,1.5,3.7,5.1,3.6,8.3c-2.2,10.2-7.1,19.2-12.8,28c9.5-0.4,6.4,5.3,15.6,9.3
          c5.7,3.4,9.7,9.4,16.3,11.5c7.3,1.6,11.2,9.4,9.6,16.4c5.2,9.6,13.6,14.4,23.1,15.4c-12.1-12.3-11.3-15.2-8.7-31.4
          c2.2-3.1,7.6-2.9,9.3-6.3c0.7-1.1,1.6-1.5,2.8-1.9c-3.6-9.4-5.6-8.9-14.5-11c1.4-8.1-1.7-16.6,6.4-22.7c1.8,0.6,5.6,2.5,6.9,0.8
          c-0.9-3.7,2-5.5,5.1-7c6.1-4.8,13.2-7.7,20.8-8.7c7.6,2.2,12.1,15,21.2,10.2c9.1-3.3,19.4,0.3,28.4,2.3c8.9-2.3,17.7-7.4,4.6-12.9
          c-1.6-9.9,11.1-10.3,3.1-14.6c-3-3.4,0-7.7,4.2-7.6c15.8-2.7,30.3-11.1,46.2-12.9c0-0.1,0-0.2,0-0.3c6.2-0.1,12.3,1.8,18.4,2.4
          c-0.4,2.8-1,6.3-0.6,9.1c14.4-3.9,11.6,10.3,17.7,7.2c3.3-1.9,3-7,6.7-8c1.9-0.4,8.7-0.6,8.7,2c3.1,12.8,20.6,14.3,23.7,31.3
          c0-0.2,0-0.5,0-0.7c4.6-1.4,10.2-2.2,13.4,2.4c13.9-4.4,5.8-0.5,19.5,7.7c3.6,7.9,16.5-4.2,10.2,11.4c3.1,1.2,6.2,2.3,9.3,3.1
          c-0.4-1.6-0.7-3.1-1.1-4.7c9.5-3.3,16.8-11,26.5-13.9c11.1-6.2,40.3,15.2,41.3-2.6c-1.8,0.3-2.1-1.6-2.9-2.7c-0.8-1-2.1-1.8-2.2-3
          c1.6-4.3,10.9-8.6,15.5-6.6c12,1.8,16.5,1.6,18.9,14.5c1.8,3.4,6.8,1.4,9.9,2.2c3.5-0.3,6.4-3.3,10.3-3.1c8,3.6,18.1,8.2,26,13.4
          c2.5,0.5,4.9,1,7.5,0.2c0-0.1-0.1-0.2-0.2-0.3c3.4-4.2,6.2-11,12.1-11.4c9.4,1.3,18.9,1.5,28.2,3c1-6.2,5.8-11.2,7.5-17.2
          c3.7-3.7-2.1-5.8-4.8-7.2c-3.6-2.2,5.9-3.7,7.3-4.7c9.2,0.3,18.1-2.8,27.3-2.2c9.3,5.7,8.2,19.8,16.5,26.7
          c4.3,5.7,8.1,3.1,14.5,4.2c9.5-1.4,9.7,8,11.7,14.5c6.8,2.1,13.7-1,20.3-2.1c3.9,4.2-2.2,10.7-1.4,15.9c0.2,4.5-12.6-2.4-13.2,12.2
          c2.2-1.4,4.6,0.9,6.6,1.6c10.7,3.5,18.2-8.8,23.7-16.2c5.9-10,12.7-19.8,18.1-29.9c1.1-5.4,3.1-9.9,7.3-13.5
          c9.7-8.6,1.1-22,10.9-37.7c-4-9.5-4.3-30.9-19.8-24.7c-2.7,3.3-6.4,3.8-10.4,3c1.3-5.4-1.3-5.8-6.4-7.4
          c13.1-12.6,23.6-29.1,39.2-39.8c1.2-1,0.8-1.6,0.9-2.8c1.7-3.9,7.7-3.1,11.2-2.4c9.7-0.2,21.9,4.2,30.2-2.3
          c2.4-1.7,4.6-4.1,7.8-3.3c5.8,0.6,2.7,8.5-1,10c-3.2,8.6,7.5,3.4,10.9,1.2c3.7-3.5,11.1-1.6,13.2-6.6c-5.3-8.3,6.3-10.7,9.7-16.2
          c4.3-3.6,6.7-9.2,11.8-11.6c25.5-2.1,5.6,6.7,10.1,13.1c4.1,5.2,8.7-9.5,14.6-9.3c10.5,0.4,3.6-16.3,12.5-16.3
          c5.3,1.6,10.7,1.1,3.7,6.8c-4,2.2-2,5.9-6.3,7.8c-3.3,9.3,0.2,10.2-11.9,14.2c-4.4,2.2-2.7,8.1-5.4,11.6c-3.5,2-6.8,0.6-8,5.7
          c-0.8,2.8-3.3,3.6-5,5.7c-3.4,3.5-6.7,7.2-11.1,9.6c-4.7,2.5-4.4,8.1-2.6,12.3c-2.4,10.8-3.2,22.4-0.4,33.3c1.5,6-0.4,18.4,6,21.2
          c3.9-4.2,7.4-8.1,7.1-14.1c0.2-1.5-0.8-5,1.2-5.5c12.2,0.4,1.2-6,7.1-11c3.1-4.4,3.6-4.6,9.2-4.8c7.7-0.5-1.6-13.3,6.7-14
          c5.3-3.6,4.2-12,4-17.7c-1.7-3.4-6.6-1.6-3.9-7.3c0.8-2.4,3.8-3.9,4.6-6.1c0.2-2-1.1-2.4,1-3.7c2.6-2.5-0.7-7.9,3.7-9.8
          c1.7-1,14.2-1,14.2,0.5c3.5-1.7,6.4-4.5,8.9-7.4c1.2-1.2,2.2-0.5,3.6-0.3c1.7-0.3,2.1,0.8,2.9,1.9c2.5,0.4,5.4,0.3,7.8-0.8
          c8.5-5,18.5-6.4,26.4-12.4c6.7-3.7,9.8-16.9,19.4-11c2.3,0.8,2.6,2.9,3.2,4.9c0.5,1.6,1.9,0.8,3.1,0.6c2.5-0.5,8-0.3,8.1-3.5
          c-1.1-4.2-3.9-8.3-6.4-11.9c-1.7-5.4-4-12.5-9.9-14.5c-2.9,0.2-19.9-4.2-12.8-8.1c10.1-1.8,19.2,6.1,28.1-1.1
          c3-1.5,5.2-0.7,4.2-5.1c0.8-2.7-2.1-11,2.1-10.8c3.7-0.1,4.8,2.8,4.8,5.8c1.4,1.2,3.9,1.9,3.8,4.2c1.9,0.8,3.5,0.2,3.6-1.8
          c8.6-3.9,4.9,13.9,22.5,18.2c3.4,0.3,4.9-1.1,4.7-4.3c4.8-3.1,0.5-10.1,1.5-14.6C2405,481.5,2416.3,488.2,2415.2,480.9z"/>
      </g>
      <g id="finland" data-country="Finland">
        <path class="st0" d="M1409.1,560.6c4.6-5.2,7.8-11.4,12.3-16.7c3.5-3.1,5.4-6.9,5.5-11.5c-5.5-3.9-7.8-10.6-4.6-16.8
          c-0.5-4.5-3.9-8.1-5.1-12.4c-0.9-4.8,2.2-10.6,1.5-15.6c-4.3-7.2-9.8-23.9-1.2-29.5c-1.5-4.9-4.4-9.4-6.5-14.1
          c-9.1-2.8-4.9-13.4,0.6-17.7c1.5-2.7,2.8-5.7,6.1-6.8c0.3-0.5,0.8-0.8,1.3-1.1c-12.2-3.6-17.4-3.4-28,0c-3.1,4.5-2.9,11-3.6,16.3
          c-1.5,3.8-6.9,4.1-10.5,4.2c-9.1,1.7-18.7-0.1-21.1-10.2c-0.4-0.9-4.8,1.5-6.3,2.5c2.8,8.1,11.7,9.1,17.2,14.2
          c3,2.9,3.7,7,5.5,10.6c0.6,1.5,0.2,24.7,0.3,26.9c0.8,0.9,2.2,2.3,3.2,3.2c2.6-1.9,4.6,1.3,7.1,2.2c3.4,1.4,7.6,1.8,9.8,5.2
          c2,3,3.7,11.9-0.3,13.5c-2.2,0.6-5.6-0.9-7.2,0.6c-0.5,1.4,0.4,3.2-0.2,4.6c-3.4,2.8-6.1,6.1-8.8,9.5c-4.3,7.8-15.1,8.4-19.6,15.3
          c-0.3,5,10.1,10.3,4.2,14.5c-2.2,0.9-4.5,2.9-2.4,4.7c1.3,3.2-0.4,8,4.2,8.9c2.8,1.3,6.9-0.1,5.6,4.6c-0.4,0.9,0.5,1.6,0.7,2.3
          c5.8,2.3,9.9-3.1,15.3-3.5c4.2-0.7,9-1,12.8-2.5c2.3-2.1,6.7-3.3,10-3.4c0,0.1,0,0.2,0.1,0.3
          C1407.8,562.2,1408.5,561.8,1409.1,560.6L1409.1,560.6z"/>
      </g>
      <g id="sweden" data-country="Sweden">
        <path class="st0" d="M1372.4,483.2c-0.2-2.3,0.4-25.4-0.3-26.9c-1.7-3.6-2.6-7.7-5.5-10.6c-5.6-5.1-14.4-6.1-17.2-14.2
          c-0.1,0-0.1,0.1-0.2,0.1c-11.1,5.7-1.8,14.2-17.5,14.5c-2.1,0-1.6,1.2-2.3,2.7c-2.3,8.8-7.6,15.1-14.2,21.2
          c-6.9,9.7-14.9,20-16.1,32c0.2,1.9,2.2,3.1,2.4,5c-0.1,1.5,1.1,6-1.5,5.1c-2.5,0.4-5.6-1.3-7.8,0.2c-2.7,1.7-8.3,1.1-7.6,5.5
          c1.2,9.1-3.1,17.1-2.4,26.1v-0.2c3.6,3.1,8.7,6.1,2.7,11.1c-2.2,1.8,1.5,3.7,1.7,5.6c0.1,0,0.2,0,0.2,0c-1.4,1.7-1.3,5.4-1.2,7.4
          c-5.1,4.7-8.7,3.6-7.6,11.9c0.1,0.1,0.3,0.2,0.4,0.2c1.3,1.2,2.9,2.3,2.7,4.3c1.9,2,1.6,4.3,1.6,6.9c2.2,3.4-0.8,7.8,2.1,11
          c3.1,2.4,6.2,4.6,4.1,8.7c0,2.7,5.1,3.5,3.8,6.7c-4,1.2-12.5,0-14.3,4.3c0.7,3.5,4.3,4.5,7.4,4.1c2.3-0.4,3.2-3.3,5.8-3.6
          c5.5-0.7,1.8,1.7,3.8,3.6c2.2,0.2,6.2-0.4,6.4-3c1-2.3,0.1-5.8,3.3-6.2c4.5-0.3,11.1,0.6,14.5-3c0.1-6.5-0.7-13.1-1.9-19.3
          c6.3-1.6-1-7.7,0.7-9c3.2-1,8.3,0,10.3-3.2c10.3-3-1.6-5.3-5.9-5c-0.1-5.2,7.6-0.5,10.5-1.5c2.2,0.5,3.5-0.4,4.5-2.1
          c3.1-3.5-4.9-4.6-6.4-7.1c-2.4-3.2-8.3-2.2-9.1,1.8c-4.9,0.6-3.6-1-2-4c3.3-0.2,8.7-0.3,6.5-5c-1.6-3.6-0.4-7.4-2.2-10.9
          c-2.5-3-3.1-4.6-1.3-8.4c0.4-4.2,0.8-7.8,5.2-9.6c8.9-4.4,17.6-9.4,25-16c-0.9-11.8-2.8-21,10.7-25.9c3.4-2.3,7.7-0.4,11.2-2.9
          C1374.6,485.4,1373.2,484,1372.4,483.2L1372.4,483.2z"/>
      </g>
      <g id="norway" data-country="Norway">
        <path class="st0" d="M1435,414c-4.3-2.2-21-14.3-18.6-2.4c-13.7,0-4.4-3.8-5.9-10.4c-14.6,0.6-3.8,11.1-11.4,10.9
          c-0.7-1.9,0.4-4.2-0.1-6.2c-5.6-0.4-3.7,6.6-5.9,9.3c-4.4,2-4.1-5.6-2.4-7.8c0.7-1.9,5-3.5,2.5-5.5c-5-0.6-3.2,2-5.7,3.4
          c-3,1.3-4.9,4.4-7.4,5.4c-3.7-1.4-7.5-3.4-11.6-1.9c-1.3,0.2-4.3,1-2,2.1c5.3,5.7-1.4,2.6-2.8,4c-0.6,1.3,2.2,1.1,1.9,2.5
          c-1.2,1.5-3,2.9-5.1,2.7c-5.7-0.3-6.6,6.1-12,5.6c-3,0.3-3.3-1.9-2.8-4.2c0.4-2-0.1-3.1-2.3-2.6c-4.6,1-7.1,4.5-9.4,8.2
          c-1.4,3.2-8.8,3.3-9.1,6.6c5.1,4.1,2,0.8,2.1,6.3c1,2.3-6.9,0.3-8.3,0.2c-2.1-1.8-3.5-4.8-6.9-3.3c-4.2,1.4-4.5,4.1-1.8,7
          c0.5,3.3-2.9,3.7-4.7,5.3c2.2,0.7,5.9,0.9,8-0.6c0-0.7,0-1.3,0.6-1.8c0.4-0.5,1.2-1.9,1.9-1.5c1.6,0.9,3.7,1.6,4.8,3
          c0.2,3.4,1.8,6.9-3.1,4.6c-2-0.8-3.7,0.9-5.2,2c-0.3,4,0.1,4.6-4.1,6.3c-13.4,7.2-2.4,6.8-14.7,17.6c-3.5,4.8-2.2,11.1-4,16.3
          c-0.5,1.1-2,2-2.6,3.2c-0.2,1.4-0.1,1.9-1.6,2.6c-1.4,1.5-4,2.6-4,4.7c-0.7,1.2-1.5,2.1-1.7,3.4c-3,1.9-5.6,4.4-7.4,7.4
          c-6,1.4-1.5,6.3,2.8,4.2c0.7-0.2,0.8-0.3,1.1-0.9c0.2-2.2,3.7-1.1,5.1-0.8c0.1,2.1-2.4,2.8-3.6,4.2c-1.2,3.8-6.8,2.8-10,2
          c-1.9-0.2-3.4-2.1-5.3-1.2c-1.6,0.3-3.6-0.5-3.6,1.2c0,1.3-1.5,2.2-1.8,3.4c-0.2,0.8,0.1,0.7-0.8,1.2c-3.9,3.3-8.4,6.2-11.5,10.3
          c-2.2,2.1-6.3,2.6-9.1,3.6c-2.4,1.8-1,4.6-1.5,7c-1.6,1-1.7,2.6-1,4.3c-0.8,2.8,3.9,1.2,5,0.5c1.4-0.6,0.9-2.3,1.8-3.1
          c3,0.5,6.1,2,9.3,1.5c0.2-1.4,1.8-2.2,3.3-2c-0.2,2.2-2.4,5.9-3.9,7.7c-5.6,1.5-11.1-3.2-16.9-0.1c1.3,2.3,4,3.8,6.7,3.7
          c0,2-4.6,4.3-2.5,6.9c4.3,2,4.5-3.1,6.7-5.1c9.8-3.1,2.1,4.2-0.6,7.4c-1.6,1.3,0,3.5-2,4.5c-2.6,0.5-2.4,1-0.6,2.5
          c1,1.5,0.9,3.5,0.7,5.2c-0.4,1.2-2.2,1.2-2.2,2.6c0.4,2.7,4.5,3,6.6,4.4c4.4,0.6,9.3,0.9,13.5,1.8c-0.2-0.6-0.9-1-1.3-1.4
          c5.1-0.8,7.5-2.2,10.4-6.5c1.5-0.9,2-2.2,2.6-3.7c1-0.9,2.4-1.4,3.2-2.6c1.5,0.3,3.9,0.9,5.4,1.8c-1-8.3,2.4-7.1,7.6-11.9
          c-0.1-2-0.2-5.7,1.2-7.4c-0.1,0-0.2,0-0.2,0c-0.3-1.7-2.3-2.7-2.4-4.4c2.9-3.7,5.7-6.9,0.3-10.5c-6.6-3.8,1.1-14.7,0.1-20.8
          c0.5-3.3-1.9-9.9,2.7-10.6c4.2-2,8.1-2.7,12.7-2c2.6,0.9,1.4-3.6,1.5-5.1c-0.2-1.9-2.2-3.1-2.4-5c1.1-12,9.3-22.3,16.1-32
          c6.5-6.1,12.1-12.5,14.2-21.2c0.6-1.3,0.3-2.8,2.3-2.7c15.5-0.2,6.6-8.9,17.5-14.5c0.1,0,0.1-0.1,0.2-0.1c1.5-1,5.9-3.5,6.3-2.5
          c2.4,10.1,12,11.9,21.1,10.2c3.6,0,8.9-0.4,10.5-4.2c0.7-5.3,0.5-11.8,3.6-16.3c10.5-3.4,15.9-3.6,28,0c5.4-0.9,12.1,0.3,16.8-3.2
          C1435.6,415.2,1435.3,414.4,1435,414L1435,414z"/>
      </g>
      <g id="denmark" data-country="Denmark">
        <path class="st9" d="M1267.6,624.8c0.7-1.8-0.1-4.8,1.9-5.6c2.4-0.6,7.6-5.7,2.4-5.7c-1.8,0-2.4-2.4-1.2-3.2c3-2.4,1.8-6.2,2-9.7
          c0.8-7-6.2-1.4-9.2-0.3c-3.1,1-3.5,2.8-3.6,5.6c-1.6,2.8-4.1,5.1-5.5,8c-1.4,10.7,6.1,5.5,5,13.9c2.7-0.4,5.4-0.9,8-1.5
          C1267.5,625.7,1267.5,625.3,1267.6,624.8z"/>
      </g>
      <g id="bulgaria" data-country="Bulgaria">
        <path class="st0" d="M1383.1,739.8c-2-3.2,8.5-5.6,10.3-7.8c1.2-1,1.8-2.5,1.3-4.1c-1.8-4.4,5.8-4.8,6.9-8.3
          c-4.2-1.3-8.1-2.3-11.6-5c-6.4,4.1-13.2,7.8-20.2,10.7c0,0-16.2,0-16.2,0c-1.3,3.4-3.2,11.5-2.8,15.9c0.9,1.5,1.7,2.8,2.9,4.2
          c5.7-2,12,1.9,17.2,4.2c4.7-2.5,8.5-6.6,13.1-9.4C1383.8,740,1383.3,740,1383.1,739.8L1383.1,739.8z"/>
      </g>
      <g id="moldova" data-country="Moldova">
        <path class="st0" d="M1401,708c8.7-17.2,7.2-34.7-15-27c0,2,0,3.9,0,5.9c7.5,2,15.2,10.4,12.8,18.4c-2.7,1.4-4.7,3.7-6.8,5.7l0,0
          C1395,711,1399,712,1401,708z"/>
      </g>
      <g id="romania" data-country="Romania">
        <path class="st0" d="M1352.2,693.2c-3.3,5.9-8.8,10.2-13.2,15.3l0,0c2,9.1,8.3,11.1,14.4,16.9h16.2c8.1-4.1,17.6-6.7,22.7-14.7
          c2-2,4.2-4.3,6.6-5.5c2.9-11.4-9.3-17.4-18-20.8c-1.7,1.6-4.4,3-5.3,5.3h-20.7c0.1,0-0.6,0.2-0.4,0.2c0,0.1-0.1,0.2-0.1,0.3
          C1353.8,691.2,1352.9,692.2,1352.2,693.2L1352.2,693.2z"/>
      </g>
      <g id="ukraine" data-country="Ukraine">
        <path class="st0" d="M1465.9,676.3c-7.1-7.6-17.5-9.5-27.6-10.5c-1.6,0-3-5.6-3.6-7.2c-6.2,2.2-8.8-4.8-2.2-6.7
          c-5.5-6.6-7.9-7.1-16-4.6c-2,0.4-4.2,1.1-6.3,1c-2.1,2.3-4.9,5.6-6,8.8c-9.9-0.4-22.4,2.1-30.2-5.6c-6.4-0.3-14.1-0.2-19.7,3.3
          c0.9,18.8,0.2,7.3-9.9,20.4v10.2h0.1c3.2,1.7,6.5,3.8,9.9,4.6c-0.1,0,0.6-0.2,0.4-0.2h20.7c1-2.4,3.5-3.5,5.3-5.3
          c1.6,1.1,3.4,1.8,5.2,2.4c0-2,0-3.9,0-5.9c5,1,9-4,13-2c12.2,3.3,7.1,35.4-7,32c-6.3,5.9,5.4,6.9,9.3,8.8c2.4-5,0.1-13.1,7.6-13.4
          c1.5-0.9,3.4-0.3,4.6,0.8c0.7,1.5,0.6,2.9,1.6,4.4c4.1,0.8,6.9-0.9,4.3,4.8c0.2,1.6-1.8,1.4-1.5,2.9c2.6,1.8,7.4,0.4,10.5,0.4
          c7.9-4.2,16.2-9.3,25.7-9.1c2.8-5.5,7.4-10.1,8.7-16.3C1465.4,689.2,1468.5,681.8,1465.9,676.3z"/>
      </g>
      <g id="estonia" data-country="Estonia">
        <path class="st9" d="M1362.4,593.5c7.5,1.5,14.7,5.4,20.1,10.6c4.9-7.3,2.5-17.4,1.1-25.5c-5.7,2.6-5.2,0-10.5,0.6
          c-4.6,1.6-15.2,2.4-16.5,7.1c-0.6,2.5-0.5,4.8,2.1,6.1C1360,592.8,1361.1,593.3,1362.4,593.5z"/>
      </g>
      <g id="latvia" data-country="Latvia">
        <path class="st0" d="M1386.9,614.2c-1.4-3.3-3.7-6.5-4.6-9.9l0,0c-6.3-6.3-15-9.8-23.7-11.5c-0.1,1.8,1.3,2.6,2.8,3.4
          c0.6,1.3,0.8,2.8,1.3,4.2c1.2,1.2,1.5,2.5,1.3,4.2c-0.1,2.5-4.4,3.5-6.4,2.3c-4.6-1.3-6-9.1-11.4-7.4c-0.4,0.9-0.4,2.4-1.2,3.3
          c-2.7,2.7-4.6,5.3-4.7,9.3c7.7,1.2,15.6,1.3,23.4,1.6c6,2.1,12.2,4.9,18.3,6.8c2-1.2,3.9-3.7,6.2-3.8
          C1387.7,615.7,1387.3,614.9,1386.9,614.2L1386.9,614.2z"/>
      </g>
      <g id="belarus" data-country="Belarus">
        <path class="st0" d="M1414.6,638c-1.3-15.9-14.8-20.8-27.6-21.3c-7,4.6-13.5,10-19.7,15.6c-4.9-0.5-10.1-0.1-15,0.3
          c3.4,2.4,1.3,9.3,0.8,13c-7.4,3.7-4.6,6,1.2,9.2c5.5-3.5,13.4-3.6,19.7-3.3c7.7,7.7,20.4,5.2,30.2,5.6c1-3.3,3.8-6.3,6-8.8
          c2.1,0.1,4.3-0.5,6.3-1C1415.4,644.4,1415.1,641.1,1414.6,638L1414.6,638z"/>
      </g>
      <g id="lithuania" data-country="Lithuania">
        <path class="st0" d="M1355.7,632.2c3.9,0,7.8-0.5,11.7,0c4.5-4.3,9.6-8.1,14.4-11.9c-6.2-2-12.2-4.7-18.3-6.8
          c-7.8-0.4-15.7-0.3-23.4-1.6c0.3,2.1-0.3,4.2,0.6,6.2c9.3,1.5,12.3,5.2,11.4,14.6l0,0l0,0C1353.4,632.6,1354.6,632.4,1355.7,632.2z
          "/>
      </g>
      <g id="kaliningrado-russia" data-country="Kaliningrado Russia">
        <path class="st0" d="M1341.1,631.1c4.4,2,6.2,2.3,11.2,1.5c0.8-9.5-1.9-13.1-11.4-14.6c0,0,0,0.1,0.1,0.1c-0.4,2,5.7,4.7,3,6.2
          c-1.7,0.7-3.5-0.4-5.2,0.3c-1.3,0.4-1.2,1.2-1.1,2.3c-0.5,1.1-1.3,2.2-2,3.3C1337.4,631,1339.4,631.1,1341.1,631.1z"/>
      </g>
      <g id="poland" data-country="Poland">
        <path class="st0" d="M1354.3,660.5v-5.8c-5.8-3.2-8.6-5.5-1.2-9.2c0.5-3.7,2.5-10.5-0.8-13c-2.6,0.2-3.4,0.3-5.5,0.6
          c-3.4-1.3-7.3-2.3-10.9-3.2c-2.1,2.4-4.8,3.8-7.8,4.4c0,0.1,0,0.1,0,0.2c-7.4-1.4-5.1-6.1-15.9-2.4c-1.5,1.3-4,2-4.5,4.1
          c-4,0.8-8,6.4-12,4.3v20c-0.1,0-0.3,0-0.4,0.1c4.4,3.5,13.8,1.6,15.3,7.5c0,0.3-0.1,0.7,0.4,0.6c5.5-1.3,9.4,4,13.3,7
          c0.1,0,19.3-0.4,20.2-0.4C1350.5,666.2,1356.3,670.2,1354.3,660.5L1354.3,660.5z"/>
      </g>
      <g id="czechia" data-country="Czechia">
        <path class="st0" d="M1319.1,671.2c-3.5-4.4-7.7-1.8-8.7-3.4c-1.7-6-10.9-3.9-15.3-7.5c-19.5,7-19,6.8-7,22.8h14
          c-1.5-4.5,3.5-2.8,5.2-0.6c6,2.5,12.3-3.3,16.5-7.5C1323,674.5,1320.2,672.2,1319.1,671.2L1319.1,671.2z"/>
      </g>
      <g id="slovakia" data-country="Slovakia">
        <path class="st8" d="M1318.1,680.6c-0.8,0.5-4.1,2.4-4.2,2.5c-0.4,0-0.8,0.1-1.2,0.1c0,0,0,0-0.1,0v8.5c3.8,0.1,7.4,1.3,11.3,1
          c6-4,13.8-5.6,20.6-8V675c-0.9,0-20,0.4-20.2,0.4C1322.5,677.4,1320.2,679.1,1318.1,680.6L1318.1,680.6z"/>
      </g>
      <g id="hungary" data-country="Hungary">
        <path class="st10" d="M1351.1,688.6c-2.2-1-4.5-2.1-6.6-3.4h-0.1v-0.4c-6.9,2.4-14.5,4-20.6,8c-3.8,0.4-7.5-1-11.3-1v0.2
          c-2.1,3-5.7,6.2-5.6,10.1c3.1,2.6,8.4,2.8,10.3,6.6c1.3,1.4,3.9-0.4,5.6,0c0,0,16.1,0,16.1,0c5.4-6,11.7-11.5,15.4-18.8
          C1353.4,689.8,1352,688.9,1351.1,688.6L1351.1,688.6z"/>
      </g>
      <g id="serbia" data-country="Serbia">
        <path class="st0" d="M1350.1,722.2c-5.7-3.1-9.4-7.2-11-13.7l0,0H1328c2.9,5.4,2.4,12.1,2.6,18c2.9,4.9,5.4,10.3,8.5,15.1
          c3.9-0.2,7.8-0.4,11.7-0.3c-0.4-4.4,1.5-12.5,2.8-15.9C1352.5,724.5,1351.1,723,1350.1,722.2L1350.1,722.2z"/>
      </g>
      <g id="north-macedonia" data-country="North Macedonia">
        <path class="st0" d="M1350.7,741.3c-3.9,0-7.8,0-11.7,0.3c-0.7,3-0.5,7.8,1.6,10.7c4.5-2.7,8.7-4.2,13-6.8l0,0
          C1352.4,744.3,1351.6,742.7,1350.7,741.3L1350.7,741.3z"/>
      </g>
      <g id="greece" data-country="Greece">
        <path class="st0" d="M1378.3,754.6c-5.9-4.9-13.3-8.4-20.9-9.7c-5,0.2-8.5,3.7-13,5.4c-3.7,1.1-5.2,4.6-7.5,7.6
          c0.1,1.4-0.9,1.6-1.9,2.3c2.4,6,7.7,5.4,4.7,13.3c1.5,1,4,1.7,4.7,3.7c-1,4.2-1.8,5.4,3.2,6.8c4.8,4,10.1,1.1,10.2-4.8
          c0.2-1.3-2-1.9-1.4-3.3c2.4-1.3,5.8,0.3,8.3,0.7c1.4-4.2-3.4-5.8-5.7-8.3c-1.9-2.5-3.1-5.4-5.8-7.2c-1.3-0.7-3.6-2.3-1.1-2.9
          c3.2-0.8,6.5,3.7,9.7,1.8c1.8-1.6,0-4.5,1.9-6c3.5-3.5,8.2,1.1,8.9,4.8c2.3,1.9,4.6-3.8,8.6-2.9
          C1381.2,755.9,1379.1,755.1,1378.3,754.6z M1372.4,793.9c0-0.1,0.1-0.2,0.1-0.3c-4.9-1.4-10.5-0.7-15.3-2.7
          c-1.2-0.5-3.3-2.1-3.3,0.3c1.8,3.3,13.8,5.8,17.8,5.6C1374.9,797.3,1374.4,792.9,1372.4,793.9z"/>
      </g>
      <g id="albania" data-country="Albania">
        <path class="st0" d="M1340.5,752c-4.3-6.5,1.1-9.9-5-16.1c-2.7,2.5-5.5,4.8-8.2,7.2c11.4,5.1,0.2,7.4,7.6,17.1c1-0.6,2-1,1.9-2.3
          c1.4-1.8,2.7-3.6,3.8-5.6C1340.6,752.2,1340.5,752.1,1340.5,752L1340.5,752z"/>
      </g>
      <g id="montenegro" data-country="Montenegro">
        <path class="st0" d="M1335.5,736c-1.8-2.7-2.5-6-4.5-8.6c-4.3,3.1-8,6.8-11.9,10.5c3.2,1,4.7,4.7,8.1,5.4
          C1329.9,740.8,1333,738.5,1335.5,736L1335.5,736z"/>
      </g>
      <g id="bosnia-and-herzegovina" data-country="Bosnia and Herzegovina">
        <path class="st0" d="M1326.1,731.3c1.7-1.4,3.3-2.7,5-4c-0.2-0.4-0.5-0.8-0.5-0.8c0.1-3.1,0-6.3-0.5-9.3c-5.7,2-27.9-7.9-22.5,4
          c3.1,5.2,7,5.4,1.8,11.1c0.1,0,0.1,0.1,0.1,0.1c2.9,2.4,5.9,4.8,9.7,5.5C1321.6,735.8,1323.7,733.5,1326.1,731.3L1326.1,731.3z"/>
      </g>
      <g id="croatia" data-country="Croatia">
        <path class="st0" d="M1310.7,730.2c3-2.4,0.2-4.6-1.6-6.7c-10.6-14.7,14.2-4.7,20.9-6.3c0-3.1-0.6-6-2.1-8.7
          c-16.6,0.6-4.8,1-18.3-5.1c-4.5,4.1-7.5,9.5-11.3,14.2c7.3,2,4.3,11.9,11,14.6C1309.6,731.4,1310.3,730.9,1310.7,730.2
          L1310.7,730.2z"/>
      </g>
      <g id="slovenia" data-country="Slovenia">
        <path class="st0" d="M1305.9,707.5c1-1.4,2.3-2.8,3.7-4c-0.9-0.4-1.9-0.9-2.6-1.5v0.1c-6,0.9-11.4,3.1-17.6,2.8l0.2,9.7
          c2.4,1.7,5.9,1.9,8.6,2.9C1300.8,714.2,1303.4,710.8,1305.9,707.5L1305.9,707.5z"/>
      </g>
      <g id="austria" data-country="Austria">
        <path class="st11" d="M1311.1,683.2c-3,1.4-4.6-2.4-7.1-2.8c-1.5,0.2-2.4,1.6-1.5,3h-14c0.1,0.1,0.1,0.1,0.1,0.1
          c-1.2,1.9-4.5,7.3-6.4,9.5c-5.5,0.7-12.6,1.7-18.2,1.3v2c2.2,0.4,5.4,1.8,7.3,2.4c0,0,10.3,0,10.3,0c2,2.4,4.9,4.7,7.9,6v0.1
          c6,0.2,11.9-1.8,17.6-2.9c-0.1-4,3.4-7.1,5.6-10.1v-8.7C1312.1,683.2,1311.6,683.2,1311.1,683.2L1311.1,683.2z"/>
      </g>
      <g id="italy" data-country="Italy">
        <path class="st10" d="M1320.4,756.9c-2.9-3.2-7.5-4.6-10.5-7.7c-2.1-2.4,1.5-8.4-5-5.7c-0.7,1.6,0.5,3.9-2.2,1.9
          c-3.5-1.6-4-6.4-8-7.2c-3.5-0.1-2-3.6-4-5.2c-1.4-0.6-2.8-1.5-3.8-2.7c-2.1-1.7-4.6-4.7-1.9-6.9c0.9-2.9-2.3-8.5,0.9-9.6
          c0.5-0.2,1.1-0.6,1.7-0.7c0.8-0.5,1.2,1.2,2,1.5c0-0.2-0.2-9.7-0.2-9.8c-3-1.2-5.9-3.6-7.9-6h-10.3c-2.1,7.6-7.6,6.4-13.8,6.8
          l-2.8,2.9c-2,0.4-4.5,0.9-6.6,0.7V724c1.9,1.9,5.5,2,4.7,5.3c9.9-4.3,7.3-7.7,18.7-2.7c0,0.4-1.8,1-1.6,1.4
          c1.2,1.6,1.9,3.3,2.1,5.3c-0.1,2,2,2.8,2.4,4.4c-0.3,1.1,0.6,1.8,0.7,2.8c8.8,3.8,17.2,8.7,25.7,13.1c5.1,4,10.3,14.5,3.2,18.7
          c-5.3,6.8-6.9,3.7-13.8,1.3c-10.8,0.8,4.1,7.3,7.4,7.9c3.9,1.5,8.2,4.3,7.3-2.5c0-1.6,2.4-1.7,3-3.1c0.7-2.2,2.5-3.6,3.4-5.8
          c0.4-2.5,3.1-2.8,4.2-4.7c1.6-3.8-2.7-2.8-2-6.2c-0.3-1.8,2.7-3.1,4-2.5c0.8,0.7,0.7,2,1.4,2.8
          C1322.9,761.5,1325,760.5,1320.4,756.9z M1268.2,754.7c-1.4-2.5-5.2-2.6-7.7-2.4c0,1.5-1.2,1.7-1.7,2.8c0.1,1.2-0.1,2.3-0.5,3.3
          c0.5,3.4-1,6.3-1.2,9.6C1263.3,771,1269.1,760.2,1268.2,754.7z M1267.6,740.4c-1.6-0.8-3-1.7-4.3-3c-6.9,4.1-6.4,4.5-1.8,10.1
          C1264.5,749.2,1268.3,742.9,1267.6,740.4z"/>
      </g>
      <g id="switzerland" data-country="Switzerland">
        <path class="st8" d="M1268.6,697.9c-1.5-0.5-3.2-1.2-4.8-1.5v-2h-15.5c-1.8,3.5-4,7.1-5.4,10.6c2.4-0.1,4.7,2,5.1,4.5
          c2.1,0.2,4.6-0.3,6.6-0.7l2.8-2.9c6.1-0.4,11.7,0.9,13.8-6.8C1271.2,699.1,1269.2,698.1,1268.6,697.9L1268.6,697.9z"/>
      </g>
      <g id="germany" data-country="Germany">
        <path class="st8" d="M1295.6,640.3c-2.8-2-2.5-5.8-5-8c-5.4-1.8-9.4,4.4-13.3,6.8c-2.1,0.6-6.3-2.2-6.6-4.3c-0.8-2.1-5.6-2.5-4-5.5
          c1.4-0.6,0.9-1.9,0.9-3.2c-2.7,0.6-5.3,1.1-8,1.5c1.2,4,2.4,7.7,0.2,11.9c-2.7,2.5-7.4,1.9-11,3c-0.5,12.9-5.7,24.5-5,37.2
          c10.5,2.2,9.4,6.1,4.7,14.6c7.6,0.1,26.3,0.2,33.6-1.3c1.9-2.3,5.2-7.6,6.4-9.5c0,0,0-0.1-0.1-0.1c-12.3-16.6-12.5-15.7,7.4-22.9
          L1295.6,640.3L1295.6,640.3z"/>
      </g>
      <g id="netherlands" data-country="Netherlands">
        <path class="st9" d="M1240.9,667.8c0.9,1.6,1.7,3,2.8,4.4c1.7-9.8,4.2-19.3,5-29.7c-8.6,0.5-8.5,9.3-17.1,2.2
          c-0.9,0.3-0.7,2.3,0.1,2.7c0.9,0.9,3.2,0.9,3.7,1.6c1.2,2.2,0.3,5,0.9,7.5c1.7,1.3-0.1,2.7-1.6,3.7
          C1236.5,662.7,1239.3,665.3,1240.9,667.8L1240.9,667.8z"/>
      </g>
      <g id="belgium" data-country="Belgium">
        <path class="st8" d="M1229.3,670.9c3.8,4.2,9.7,5.8,14.3,8.9c0-0.3,0-7.2,0-7.5c-2.6-4.2-5.6-8.2-8.9-11.9
          c-3.5,2.6-7.6,4.4-11.7,5.5C1225.2,667.4,1227.5,669,1229.3,670.9L1229.3,670.9z"/>
      </g>
      <g id="france" data-country="France">
        <path class="st9" d="M1252.7,727.2c-0.7-1.5-3.4-1.8-4.6-3.2c-0.7-4.3,2.8-19.7-5.1-19.3c2.1-6.9,10.5-15,8.4-22.5
          c-2.6-1-5.4-1.8-7.9-2.8c-7.6-3.6-14.1-8.4-20.4-14c-9.9,2.8-5.3,5.8-11.2,9.6c-3.1,2.7-6.4,4.6-9.9,6.7c-0.5,1.6-2.6,1.8-4,1.6
          c-1.3-0.2-1.7-1.3-2.7-1.9c-1.3-0.6-4,0.1-3.5,1.9c3.5,4.9-0.6,6.6-5.3,5.7c-5.7,0.1-11.7,1-17.2,2.2c2,4,15.7,8.3,20.6,9.5
          c-0.1,1.2,0.3,1.8,1.2,2.6c0,0.6,1.1,0.5,1.5,0.7c0.7,0.6,1,1.6,1.7,2.1c1.3,0.8,3.7,1.3,3.4,3.1c-2.9,5-1.3,11.4-3.3,16.8
          c10.2,2.8,21.1,4,30.9,8.1c1.4-7.9,15.6,5.1,22.5-0.8c1-2.5,2.6-3.4,5.1-4.4C1252.8,728.6,1252.8,727.8,1252.7,727.2L1252.7,727.2z
          "/>
      </g>
      <g id="spain" data-country="Spain">
        <path class="st8" d="M1226.3,760.5c-2.6-0.8-8.1,1.8-6.5,4.8c3.1,1,7.7-0.7,7.7-4.2L1226.3,760.5z M1213.5,766.1
          c-1.7,0-3.4,0.9-3,2.8c2.3,0.9,6.7,0.7,6-2.6c0.3,0,0.7-0.1,1-0.2C1216.2,766.1,1214.8,766,1213.5,766.1z M1234,758.2
          c-2.3-0.7-4.7,0-5.3,2.5C1230.4,761.9,1237.4,760.8,1234,758.2z M1212.1,752.1c11.8-5.4,16-2.3,13.2-17.6c-10-4.1-20.7-5.4-31-8.2
          c-3.5,12-23.6,3.6-33,4.5c-4,0.3-8.5-2.2-12.3-0.2c-4.5,4-3.4,10.3-1.8,15.2c3.3,9.9-3.4,6.1-3.9,15.9c0,3.4-1.7,6.1-4.6,7.7
          c1.4,3.3,10,3.2,7.5,8.2c-0.8,1,0.1,3.4-1,4.4c4.9,0.5,11.2-1,15.4,0.5c10.4,10.2,5.7,3.5,15.3-0.1c3.8-2.1,7.8,1.2,11.7,1.2
          c0.8,0.2,1.7,0.5,2.1-0.5c2-5.8,8.8-6.3,13.1-9.8c0.8-1.6,3.2-1.6,4-3c-1-2-4.4-3.2-5-5.6C1201.2,762.7,1210,752.9,1212.1,752.1z"
          />
      </g>
      <g id="portugal" data-country="Portugal">
        <path class="st10" d="M1155.5,758.5c-0.9-10.8,6.2-18.4-9.7-20.1c-0.5,3.7,4,11.3,1.9,14.2c-6.2,4.1-2,13.5-8.9,16.7
          c1.4,3.3,10,3.2,7.5,8.2c-0.8,1,0.1,3.4-1,4.4c3,0,6,0,9,0C1152,774,1155.2,766.3,1155.5,758.5L1155.5,758.5z"/>
      </g>
      <g id="united-kingdom" data-country="United Kingdom">
        <path class="st10" d="M1222.2,646c-1.7-2.4-7.6-11.2-10.2-6.8c0.5,1.5-1.5,3.3-2.8,2.2c3-3.3-3.3-7.4,0.5-10.1
          c-1.3-3.3-4.3-5.9-5.7-9.3c-0.4-1.7-2-3-3.7-2.2c-3.1-0.8-2.1-5.2-3.8-7.3c-1-1.4-1.5-3-2.1-4.5c0.3-7.9-12.7,2.4-5.5-7.9
          c2.8-1.8,3.4-4.9,3.5-8c5-5.3,4.2-11.6-4.2-10.1c-3,0.1-5.4,2.1-8.4,1.5c0.2-2.4,3.3-3.8,2.7-6.4c1.7-2.6,5.6-4.3,4-7.9
          c-1.7-0.8-3.5,1.1-5.3,1.1c-3,0.3-7.4-0.8-9.5,1.9c0.1,0.8-0.7,1.4-1.2,2c-0.1,0.9-0.4,1.4-0.8,2.2c-0.3,2.5-2.1,4.1-2.3,6.6
          c-0.9,1.2,0.9,1.8,1.8,2.5c0.6,0.7-0.1,2.6-1,2.8c-0.5,0.1-0.9-0.1-1.4,0c0.1,3.1-1.6,8.8,3.2,8.8c0.5,0.1,0.4,0,0.5,0.6
          c-0.6,3.8,0.5,4.6,4,4.7c-0.7,1-0.4,2.3,0.8,2.4c-0.3,1.7-2.4,3.1,0.2,4.6c1.4,1.1,0.4,2.6-0.4,3.8c-0.7,5,6.5-0.4,9.2,0.9
          c0.8,0.2,0.4,1.1,0.1,1.6c-2,2.7-0.9,5.8,2.4,6.5c1.7,0.5-0.1,2.1,0.4,3.3c0.4,2.1-1,6.4,0.7,7.8c-0.8,2.8-3.1,0.9-5,1
          c-1.7,1.4-3.2,3.3-5.6,3.3c1.3,2,1.4,4.6,0.9,7c-0.9,1.6-8.4,3.5-5.6,5.4c14-0.5,5.9,4.2,16.8,0.1c-1.5,7.9-5.6,2.9-9.7,8.3
          c-1.8,4.9-11.7,3.7-11.3,9.9c1.9,1.4,5.7-0.8,8-1.4c5.8,1.2,4.3,0.3,7.7-3.5l0,0c1.8-0.2,3.4,1.1,5.3,1c3.6-1.1,7.4,0.7,8-4.3
          c3.2,2.4,4.7,2.9,8,0.2c3.4-1.4,7.5,0.6,10.6-1.2c-1-0.4-1.3-1.5-1.3-2.5c-1-1.7-2.8-1.6-0.9-4
          C1214.5,646.2,1224.3,651.2,1222.2,646z M1184.2,663.3c0.3-0.2,0.7-0.5,1-0.6C1184.5,663.1,1184.3,663.3,1184.2,663.3z
          M1170.5,622.5c2.2-1.9-2.2-8.4-2.8-11.1c-1.1-0.8-2-2.3-3.5-2.3c-3.1,0.9-7.3-1.1-9.7,1.3c4.6,3.8,9.1,7,12.6,14
          C1167.9,623.5,1169.5,623.2,1170.5,622.5z M1159.9,579.4c0.4,0.3,0.6,0.5,0.7,1c1.9-0.6,4-0.9,4.1-3c0.5-1.9,2.4-3.2,2.6-5.2
          c0.1-2.9-2,0.1-3.3,0C1161,572.7,1156.3,576.7,1159.9,579.4z"/>
      </g>
      <g id="ireland" data-country="Ireland">
        <path class="st11" d="M1154.5,610.4C1154.5,610.5,1154.5,610.5,1154.5,610.4c-0.4,1.2-0.8,2.2-1.3,3.4c-1.1,0.6-2.2,1.8-3.7,1.9
          c-6,2.6,6.1,8.2-4,8.2c-4-0.8-7.1-1.1-6.2,4c-1.3,1.5,0.1,3.1,0.2,4.6c0.8,0.9,0,2,0.6,2.6c4.3-0.8,8.1,2.7,4.1,6.5
          c-2.8,1.7-6.1,1.4-6.9,5.3c-1,2-7.1,3.7-2.5,5.3c5.7,5.1,13.9,3.1,17.9-2.9c3-2.9,8-1.5,10.5-5.3c5.3-3.1,1.4-9.5,1.7-14.3
          c-1.5,0.2-2.9-1.6-3.7-2.7c1.2-2.3,3.4,1.9,5.9-2.6c-0.1-0.1-0.1-0.3-0.1-0.4C1163.1,617.3,1159.3,614.2,1154.5,610.4z"/>
      </g>
      <g id="chile" data-country="Chile">
        <path class="st0" d="M706.3,1501.5c-0.2,0-0.5-0.1-0.8,0C705.6,1501.7,705.6,1502,706.3,1501.5z M724.2,1258.7
          c-4.8-4.5-3.4-12.2-7.5-17.1c-2-2-2-4.5-2-7.2c0.9-5.3-4.8-10.1-7.8-14c0.2,1.2-0.8,1.4-1.1,2.4c0.1,1.3-0.2,2.5-0.5,3.7
          c0.1,2.2-1.8,3.7-1.5,5.9c-1.6,2-5.2,2.3-7.6,3.6c2.1,2.5,5.7,4.6,6.5,8.2c-0.7,11-2.1,22.3-5.8,32.7c-3.7,15.9-3,32.3-7.1,48.1
          c-0.2,0-0.3-0.1-0.4-0.3c-2.8,10.8-3,21.2,0.2,31.5c1.2,4.4-1.3,8.9-0.5,13.4c-10.9,23,1.1,8.5-1.5,31.9c0.1,0.1,0.2,0.2,0.4,0.3
          c0.3,4.7-2.6,10.2,1.8,13.8c-0.5,2.1-2,3.9-1.8,6.2c0.2,3.2,3.3,0.8,3.8,3.8c-0.7,4.6-5.3,7.7-4.2,12.7c0.6,5.9-1.4,12.1-0.5,17.6
          c0.3,4.7,1.3,9.7-0.6,14.4c-3.1,4-1.1,11,3.1,13.5c2.9,1.1,3.6,5.7,0.9,7c-4,2.9-0.7,6.6,3.4,6.5c-0.7,1.1-0.4,1.5,0.6,2.9
          c1.3,2.3,4.9,0.6,6.8,2.3c0.7,0.3,5.8-1.1,3.5-1.7c-2-0.7-1.5-2.5-2.4-3.9c-3-3.2-2.7-7.7-4.1-11.7c3-9.5,3-19.5-1.3-28.7
          c-1.1-1,0.4-4.8-1.3-5.7c-0.9-6.9,4-13.2,4.3-20c1.2-9.7-4.8-18.6-2.1-28.3c1.3-9.9,0.2-20.1,1.7-29.8c2.4-5.9,4.6-12.4,6.8-18
          c-0.1-7,3-13.7,2.4-20.8c-0.4-8-8.1-16-3.3-24c3.1-5.2,4.2-11,5.3-17c0.6-1.7,3.2-1.7,3.7-3.7c1.4-1.7,1.1-3.7,2.3-5.5
          c-0.4-3.6-1.8-7.4-1.2-11.1c1.9-0.1,3.5-0.8,5.4-1.1c2.5-3.8,3.1-8.1,5.3-12C725.5,1259.3,724.7,1259,724.2,1258.7z M725.2,1525.6
          c0-0.2,0-0.3,0-0.4c-5.5-6.7-11.4-13.3-17.5-19.1c-1.5,0.4-2.8,0.2-4.1,0.8c-0.1,0.3,0,0.5,0,0.8c1.1,2.5,3.4,3.6,5.1,5.6
          c1.4,1.3,3.8,1.3,4.8,3.1c-1.8,1.6-5.2,0.5-7.6,1.7c1.6,4.6,7.1,7.1,11.6,8.2c1.2,3.1,5.3,3.1,6.5,6.3
          C726.4,1531.6,724.8,1527.7,725.2,1525.6z"/>
      </g>
      <g id="uruguay" data-country="Uruguay">
        <path class="st0" d="M818.8,1339.8c-2.6-6.3-18.6-18.2-25.2-20.3c-10.2,8.5-5.2,22.9-4.7,34.3c2.5,2.5,7.3,1,10.5,2.5
          c4.2-0.3,10.1,3.5,13.1-0.5c2-2.3,4.8-8.4,6.3-10.8C818.8,1345,818.8,1340.4,818.8,1339.8L818.8,1339.8z"/>
      </g>
      <g id="paraguay" data-country="Paraguay">
        <path class="st0" d="M811.5,1281.4c0.6-7.3-9.4-4.8-10.4-12.6c-0.2-1.4,0.9-3.6-0.5-4.7c-4.6-1-7.7-5.4-12.6-5
          c-4.6-2.7-6.7-10.7-2.1-14.6c-2.8-0.1-3-1.8-4.5-3.2c-6.5-1-13.6-4.6-19.1,0.9c-8.4,4.2-6.3,14.6-6.9,22.5c0.4,3.5,5,3.7,7.2,5.6
          c3.2,5,6.5,5.5,11.5,7.5c0,0.2,0.1,0.4,0.1,0.6c2.8,2.1,5.4,3.2,8.9,3.7c3.6,3.2-1,7.7-3.2,10.3c0.1,5.1,8.5,3,11.9,4
          c5.4,0.6,9.7-2.9,14.7-4.5c2.5-2.7,3.6-6.9,5.3-10.3C811.8,1281.7,811.6,1281.6,811.5,1281.4L811.5,1281.4z"/>
      </g>
      <g id="argentina" data-country="Argentina">
        <path class="st12" d="M814,1286.4c0-1.6-1.2-3.2-2-4.6c-1.7,3.4-2.8,7.6-5.3,10.3c-5,1.6-9.2,5.2-14.7,4.5
          c-3.5-1.1-11.6,1.1-11.9-4c2.2-2.6,6.9-7.2,3.2-10.3c-3.3-0.4-6.3-1.7-8.9-3.7c0-0.2-0.1-0.4-0.1-0.6c-5.1-2-8.2-2.5-11.5-7.5
          c-7.3-3.3-8.1-4.8-7-12.8c-4.1,0.8-8.7-0.8-12.7,0.3c-0.8,0.7-2.2,4-3.6,3.3c-3.2-5.6-7.6-1.7-11.4-5.4c-3.9,4.7-4.5,10.9-7.7,15.9
          c-1.8,0.5-3.5,0.9-5.4,1.1c-0.5,3.8,0.8,7.4,1.2,11.1c-1.4,2.5-1.3,5.8-4,7.5c-3.6,1.3-2.2,6.4-3.5,9.4c-1.5,3.2-1.6,6.4-3.8,9.3
          c-4,7.1,1.1,14.8,3.3,21.8c0.8,7.9-2.3,15.3-2.4,23c-2.2,5.6-4.3,12.1-6.8,18c-1.5,9.7-0.3,20-1.7,29.8c-2.7,9.7,3.2,18.6,2.1,28.3
          c-0.3,6.9-5.2,13.1-4.3,20c1.7,1,0.2,4.8,1.3,5.7c2.7,6.8,5.1,15,2.6,22.2c-0.1,3.1-2.1,6.2-0.6,9.2c1.4,3.1,0.5,6.6,3.4,9
          c0.8,2,1,3.7,3.6,4.5c6.2,2.1,12.3,4.5,19.1,5.1c-2-5.6-1.5-13.4,3.2-17.5c3.9-3.2,16-15.1,4.8-14.8c-3.8-1.7-2.4-6.1,0.1-8.2
          c-5.3-1.7-5.5-8-4.1-12.8c1.3-3,4.3-1.8,5.9-4.2c4.4-6.1-5-7.1,5.8-10.7c3.4-3.5-1.9-5.3-3.3-8.6c-1.9-2.7,0.3-7.1-0.4-10.2
          c-0.4-1.1-1.6-2.7-0.6-4.1c2.4,0.7,3.6,3.3,5.7,4.6c7,2,3.3-11.1,3.9-15c11,3.9,26.8-2.2,18.6-15.6c-0.9-3.2,22.2-2.9,25.6-2.8
          c0.1-2.4,5.8-9.6,5.6-12.3c0.3-4-1.8-7.8-3-11.5c-2.1-3.2-4.7-5.6-3.9-9.2c0.5-11.4-5.2-25.9,5.2-34.3c-0.6-7.6,2.8-7.7,5.8-12.6
          C804.8,1292,820.3,1306.1,814,1286.4z M748.3,1524.5c-7.9-3.9-16.7-7.5-22.8-14.2c-4.3,0-8-2.6-12.1-3.3c-2.3,0.6-3.9-1.1-5.9-0.9
          c6.2,5.8,12,12.4,17.5,19.1c3,0,6.9,1.9,9.7,3c2.8,0.5,5-1.9,7.7-1.5C744.4,1526.7,751.9,1528.2,748.3,1524.5z"/>
      </g>
      <g id="bolivia" data-country="Bolivia">
        <path class="st13" d="M787.8,1231.4c-2.2-15.6-7.3-9.9-17.5-13.6c-1.5-1-1.5-3-2.2-4.5c-1.1-1.6-2.2-2.9-1.8-5
          c1.4-6.2-6.2-7.4-10.7-8.2c-3.6-1.1-6.4-4.1-9.7-5.6c0.1,0.2,0,0.4,0,0.7c-4.6,0.9-8.8-3.4-12.4-5.6c-1.9-1.3-0.8-3.7-0.7-5.6
          c5.6-5.2,2.6-13-5.4-8.9c-0.9,0.3-1.9,0.7-2.4,1.5c-0.6,0.7,0,0.5-1.4,0.5c-6.1,9.3-11.7,6-21.1,5.5c1.5,2.8,3.3,5.2,6.2,6.6
          c0,5.2,0,12,0,17.3c-1.8,2.5-6.9,1.4-5.5,6.3c-0.4,3.2,4.3,4.2,3,7.9c11.8,11.7,4.8,11.7,9.8,21.2c4.6,5,2.2,15.5,9.6,17.9
          c0.6-1.4,1.3-2.7,2.4-3.9c3.7,3.5,8.5,0,11.4,5.4c1.4,0.7,2.8-2.6,3.6-3.3c4-1.1,8.5,0.5,12.7-0.3c0.9-3,0.1-6.3,0.7-9.4
          c2.7-3.9,8-8.5,12.8-9c4.2-0.3,8.2,1.9,12.3,2.2c1.6,1.4,1.6,3.2,4.5,3.2C788.2,1240.8,787.7,1235.8,787.8,1231.4L787.8,1231.4z"/>
      </g>
      <g id="brazil" data-country="Brazil">
        <path class="st14" d="M930.6,1145.3c-0.3-0.7-0.8-1-0.8-1.8c-7.5-1.6-21.8,1.3-22.6-9.5c-0.7-1.8-2.7-2.2-4.3-2.8
          c-1.2-0.4-7.1-1.5-6.1-3.5c-4.7,0-11-1.4-14.5,2.2c-2.2,0.5-5.1-2.4-6.8-4c-2.7-3-7.5-3.1-10.8-5.1c-5.1-6.1-13.2-3.3-19.5-6.9
          c-11.9-1.8-10.7-19.6-18.3-26.4c-2.7,2.9-2.5,7.3-6.1,9.4c-1.4,0.8-1.5,3-3.5,2.7c-2.7-0.2-2.6-1.9-6.2-0.1
          c-1.1-3.5-5.6-4.6-8.2-2.5c-0.6,2.2-2.9,3.7-4.3,5.4c-0.2,0.1-1.1,0.1-1.3,0c-5.2-6.9-13.1,3-19.9,1.4c-4.6,0.3-5.8-7-5.5-10.6
          c-1.5-3.6,1.9-5.2,1.5-8.5c-2-2.7-6.8-2.9-8.2-6.4c-0.4-0.9-0.4-1.5-1.5-0.9c-8.1,7.6-16.2,4.6-25.9,5.5c-4,3.2,3.5,4.7,1.5,8.4
          c-0.1,2,2.5,1.5,3.4,2.9c3.5,4.2-7,9-9.9,11.1c-3.4,3.8-8.5,0.4-10-3.5c-1.5-1-3-2.3-4.4-3.4c-5-1.3-10.1,2-15.2,2.2
          c-0.6,3.3,6.1,4,7.3,5.8c-0.6,1.8-5.1,2.2-6.6,3c-7.4,8.3,7.3,16.1-1.8,26.6c-2.6,6.6-11.7,6.4-17.2,9.9c-0.1,2.6-1.4,5.2-2.1,7.8
          c-2.1,2.7-5.9,4.5-6.9,7.8c2.7,5.8,5.5,13.2,11.8,16c8.5,1.7,9.7-12.6,11,5.3c0.8,0.3,3.1,0,3.6,0c9.4,0.3,14.9,3.9,21.1-5.5
          c1.4,0.1,0.8,0.2,1.4-0.5c0.5-1,1.5-1.1,2.4-1.5c7.9-4.2,11,3.7,5.4,8.9c-0.1,1.9-1.1,4.3,0.7,5.6c3.8,2.2,7.7,6.6,12.4,5.6
          c0.1-0.2,0.1-0.4,0-0.7c3.4,1.5,6,4.5,9.7,5.6c4.5,0.7,12.1,2,10.7,8.2c-0.4,2.1,0.7,3.4,1.8,5c0.7,1.5,0.8,3.5,2.2,4.5
          c3.8,2.7,10.1-0.4,13.3,3.4c5.4,5.4,4.9,14.4,3.2,21.3c-0.2,1.4-1.1,3-2.3,3.6c-1.1,2.4-1.5,5.8-0.6,8.4c2.1,3.8,4.6,5,8.3,5.2
          c2.3,1,3.6,3.4,6.3,3.7c2,0.1,3.3,1.4,2.9,3.5c-1.2,10.2,11,6.7,10.2,14.6c2.7,3.6,4.1,9.4,3.7,14.9c-2.7,3.2-7.5,2.1-10.8,4
          c-4.4,2.3-4.1,8.5-8.7,10.8c-1.4,1-2.6,8.4-1.8,8.9c1.5,0.6,3.3,0.9,4.7,1.8c6,4.6,12.7,9.4,18,14.7c2.6,1.4,2.1,6.7,2.1,8.8
          c5.1-13.9,20.6-17.4,26.4-28.1c0.2-0.1,0.3-0.3,0.5-0.4c3.5-10.4,3.8-21.6,6.3-32.1c2.4-2.8,6.8-3.3,10.3-4
          c3.3,0.9,4.4-3.6,6.8-5.1c6.8-4.7,16.7-6.4,22.5-11.9c10.5-6.4,11.8-17.7,13.2-28.7c5.6-10.1,4.8-21.7,8.2-32.5
          c2.3-5.9,4.2-12.6,8.5-17.2c2.8-1.9,4.3-4.7,5-7.9c1.8-4.3,0.3-9.3,1.2-13.8c0.4-2.7,2.5-4.7,2.2-7.5
          C929.8,1153,932.1,1148.5,930.6,1145.3L930.6,1145.3z"/>
      </g>
      <g id="french-guiana" data-country="French Guiana">
        <path class="st0" d="M804.8,1083.8c-1.9,3.8,0,8.9,2.4,12.1c2.1-0.1,3.1,2,4,3.5c3.4-1.7,3.4-0.2,6.2,0.1c2.1,0.3,2-1.9,3.5-2.7
          c3.6-2,3.4-6.6,6.1-9.4c-3.5-10.4-9.9-15.9-21.4-15.1C805.2,1076,806.6,1080.5,804.8,1083.8L804.8,1083.8z"/>
      </g>
      <g id="suriname" data-country="Suriname">
        <path class="st0" d="M789.2,1072.6c0.5,3-1.9,4.5-4.1,5.8c-3.8,1.6-2.1,6-1.4,8.9c4.4,2.6,7,7.7,8.5,12.4c2.3,0,4.3,0.7,5.3,2.5
          c1.6,0.6,1.9-1.2,3.1-1.9c2.2-2.1,2.8-5.2,6.7-4.5c-2.4-3.3-4.4-8.2-2.4-12.1c1.6-3.4,0.3-7.8,0.7-11.5c-5.4-1-11,0.6-16.1-1.9
          C789.5,1071.1,789.3,1071.9,789.2,1072.6L789.2,1072.6z"/>
      </g>
      <g id="guyana" data-country="Guyana">
        <path class="st0" d="M764,1059.7c-3.5,5.4-8.5,2.7-5.1,11.7c1.7,0.7,6.1,5.4,6.5,7.1c1.5,3.3,6.3,3.6,8.2,6.3
          c0.3,1.3-0.2,2.7-1.1,3.7c-1.4,1.4-0.4,3.1-0.4,4.8c0.1,2.3-0.2,4.9,0.9,6.9c3.8,8.3,13,0.9,19.2-0.2c-1.5-4.6-4.2-9.9-8.5-12.4
          c-0.7-2.9-2.4-7.2,1.4-8.9c3.6-1.5,4.4-4.5,4.4-8.1c-3.5-1.1-3.6-4.2-5.8-6.6c-6.4-3.2-13.3-5.3-20-8.1c0,0.1,0,0.1,0,0.1
          C764.2,1057.2,764.6,1058.4,764,1059.7L764,1059.7z"/>
      </g>
      <g id="venezuela" data-country="Venezuela">
        <path class="st0" d="M681.2,1051.3c-0.9,9.9,3.8,5.7,3.8,9.4c-0.5,4.2,0.4,9,5.7,8.3c2.6,1.1,6.5-1.6,9.1-1c2.3,3.4,3.2,8,8.1,7.5
          c1.2,0.3,4-0.9,4.2,0.8c-1.9,9.7,1.2,13,2.1,21.4c7.7-2.4,8.9,8.8,15.8,8.7c3.5-1.9,20-10.3,10.9-14c-3.4-1.7,1.2-3.7-4-7.2
          c-1.6-6,15.8-1.6,20-3.7c1.8-0.6,8.1-7.1,8.5-3.7c-0.5-2.2-3.2-3.7-4.5-5.7c-0.8-1.2-2.6-1.2-2.5-2.9c-3.5-8.4,8.5-6.3,5.4-14.2
          c-1.6-0.7-3-1.6-3.8-3.3c-3.8-2.3-9.2-2-12.8-4.7c-1.2-1.4-0.2-2.7-0.8-4.3c-0.6-1.6-8-2.7-9-1.1c-1.5,1.1-1,3.1-2.1,4.2
          c-1.7,0.5-3.4-0.9-4.2-2.3c-6.2-6.1-18.7,4.6-24.3-4.7c-2.1-2.2-4.8-3-4.3-6.6c-4.3-1.3-6.2,2.8-9.9,3.7c-1.7,0.6-1.3,3.8-0.3,5
          c0.7,0.7,1.1,0.6,1.5,1.3c1.6,3.1,0.8,6.7,1.2,10c-2.1,1.7-11.4-3.2-13.3-5C681.2,1048.7,681.3,1050.2,681.2,1051.3L681.2,1051.3z"
          />
      </g>
      <g id="peru" data-country="Peru">
        <path class="st15" d="M708.5,1203.7c0-4.3,0.1-10.4,0-14.6c-2.9-1.4-4.7-3.8-6.2-6.6c-0.5,0-2.8,0.3-3.6,0
          c-1.3-17.8-2.5-3.6-11-5.3c-6.3-2.6-9.1-10.3-11.8-16c0.1-1.9,2.5-2.7,3.2-4.4c0.9-1.5,3-1.8,3.7-3.4c0.7-2.7,2.1-5.1,2.1-7.8
          c4.2-1.6,8-3.8,12.4-5.1c-4.6-2-0.3-7.8-2.9-11.1c-20.9-0.7-12.1-1.2-24.7-12.2c-0.9,2.1-1.4,4.3-2.4,6.3c-1,1.3-2.8,1.7-3.2,3.5
          c-1.3,2.3-2.6,2.1-4.7,2.9c-3.7,4.5-7.8,2.7-11.1,5.8c-1.4,2.9,0.1,7.2-1.7,9.9c-5.6-0.9-9.6-6-12.5-10.5c-1.8,1-7.3,3.5-7.3,5.8
          c0.9,1.1,3.1,1,3,2.7c2,5,7.3,8,10.8,11.8c6.4,8,6.5,18.7,11.7,27.1c0.5,3.7,5.8,3.4,6.4,7.2c2.2,6.4,5.3,12.5,9.5,17.9
          c1.4,3.5,1.2,9.4,6,9.8c7.6,5.5,14.3,12.5,21.5,18.6c2.3-1.4,8.2-1.5,7.6-5c1.2-2.5,2.1-5.4,2-8.2c1.1-1.5,1.5-3.3,1-5.2
          c-2.3-3.4-3.6-2.7-2.9-7.7c0.5-2.4,4.8-1.6,5.5-3.7C708.6,1205.6,708.5,1204.6,708.5,1203.7L708.5,1203.7z"/>
      </g>
      <g id="ecuador" data-country="Ecuador">
        <path class="st0" d="M634.9,1136.2c2.3,4.3,6.7,8.6,11.6,9.4c1.9-2.6,0.2-7.1,1.7-9.9c3.2-3.2,7.3-1.2,11.1-5.8
          c2-0.8,3.4-0.6,4.7-2.9c0.4-1.8,2.1-2.2,3.2-3.5c1.1-2,1.5-4.2,2.4-6.3c-1.9-1.1-2.9-3.1-4.8-4.1c-4.3-0.8-10.1,1.2-13.8-1.3
          c-1.6-1.5-3-3.4-5.3-3.8c-1.6-2.2-3.3-4.1-5.8-5.3c-4.8,3-5.4,9.1-7.8,13.7c-5.9,4.8-3.4,2.8-3.1,9.4c2,0.3,4.9-0.1,6.7,1.1
          c-1.7,2.5,0.3,6.2-1.9,8.3C634.3,1135.5,634.6,1135.8,634.9,1136.2L634.9,1136.2z"/>
      </g>
      <g id="colombia" data-country="Colombia">
        <path class="st13" d="M705.6,1099.8c3.1-0.1,5.6-1.3,8.6-1.9c-0.9-8.4-4-11.7-2.1-21.4c-0.2-2-4.4,0.1-5.7-1.1
          c-3.9,0.5-4.6-4.7-6.6-7.2c-3.6-0.9-9.6,1.6-13.1,0.2c-3.3-2.3-0.4-6.7-2.4-9.1c-2-0.7-3.5-2.3-3.1-4.6
          c-2.7-20.5,5.3-16.2,4.2-24.2c-9.1,1.7-7.5,5-15.8,8.9c-4.3,2.1-8-2.3-8.7,3.7c0.3,6.1-5,9.7-7.2,15c-0.4,3.4-3.3,5.4-5.1,8.1
          c5.4,0.6,7.7,7.6,3.8,11.4c-2.9,9.2-6,17.9-12.6,25.3c2.5,1.1,4.2,3.3,5.8,5.3c1.1,0.5,2.2,0.5,3,1.6c3.7,5.2,10.7,2.8,16.1,3.5
          c16.1,11.3,7.2,16.4,29.5,16.3c2.6,3.3-1.7,9.2,2.9,11.1c5.6-2,8.8-10.9,7.5-16.6c-2.3-4.2-4.2-8.6-2.1-13.2
          c-0.1-1.8,1.7-1.6,3-2.2c12.9-4-3.4-2.9-2.5-8.2C704.1,1100.2,704.8,1099.9,705.6,1099.8z"/>
      </g>
      <g id="puerto-rico" data-country="Puerto Rico">
        <path class="st0" d="M732.2,1006.1v0.3c-2.9,0.6-8.2-2.6-9.5,0.9c1.3,2,5,0.8,7.1,1.4c2.7,1.2,5.3,0.7,4.2-2.7
          C733.3,1005.8,732.8,1005.9,732.2,1006.1z"/>
      </g>
      <g id="dominican-republic" data-country="Dominican Republic">
        <path class="st0" d="M714.8,1004.8c-5.3-4.5-12.6-9.4-19.7-10c-0.3,4.9-0.9,9.7-1.1,14.8c3.5-0.5,5.7-3.2,9.1-3.5
          C704.9,1005.6,718.9,1008.7,714.8,1004.8z"/>
      </g>
      <g id="haiti" data-country="Haiti">
        <path class="st0" d="M694.8,994.8c-3,0.7-9.7-0.7-11.8,1.2c-0.6,3.3,1,2.9,2.7,4.1c1.2,0.1,2,1.8,0.5,2c-3.5,0.4-7.8,0.8-10.6,3.1
          c2,2.4,8.7-0.2,12,1.2c3.1-0.4,3.5,3.7,6.5,3.2c0.2-5,0.8-9.9,1.1-14.8C695,994.8,694.9,994.8,694.8,994.8z"/>
      </g>
      <g id="jamaica" data-country="Jamaica">
        <path class="st0" d="M662,1002.3c-3.7-0.8-10-3.2-12.8-0.3C649.7,1005.9,670,1009.6,662,1002.3L662,1002.3z"/>
      </g>
      <g id="cuba" data-country="Cuba">
        <path class="st0" d="M676.4,989.1c-11.2-10.2-27.5-14.6-40.5-22.5c-9.8,0.7-19.2,3.8-29,5.2c2.3,1.3,1.4,1.5,1.2,2.3
          c0,4.3,6.2-0.3,8.5-0.3c6.4-0.8,12.6-0.8,17.8,3c3.7,1.6,7.8,1.9,10.8,5.1c3.7,2.3,16.1,2.7,6.5,7.9c-1.1,3,7.9,0.7,9.8,0.9
          C666,989.6,672.8,992.1,676.4,989.1L676.4,989.1z"/>
      </g>
      <g id="panama" data-country="Panama">
        <path class="st0" d="M651.9,1058.9c-4.2-0.4-7.7-3.2-11.3-5.1c-9.5-6.1-8,7.1-19.2-5c-1.1,4-1,9.1-2.5,13.1c3.2,4,4.3,4.5,8,6.1
          c5.2,3.5,4-2.5,4.1-5.7c2.7-5.2,6.2-3.4,10.8-2c2.5,1.9,3.9,4.8,7.2,5.6c1.7-2.6,4.7-4.7,5.1-8.1
          C653.3,1058.4,652.7,1059,651.9,1058.9z"/>
      </g>
      <g id="costa-rica" data-country="Costa Rica">
        <path class="st0" d="M617.8,1046.1c-1.2-0.8-2.7-1.9-3-3.4c-3.9,3.4-9.7,3.2-15,1.9c0.3,0.3,0.7,0.6,1,1l0,0c0,0.1,0,0.1,0,0.1
          c2.8,2.6,4.8,6.2,8.4,7.5c3.5,0.9,5.5,3.5,7.7,6.2c0,0.1,1,1.2,2,2.5c1.4-4,1.3-9.1,2.5-13.1
          C619.9,1047.6,618.1,1046.3,617.8,1046.1z"/>
      </g>
      <g id="nicaragua" data-country="Nicaragua">
        <path class="st0" d="M614.8,1042.7c-3-7.2,4.7-12.4,3.8-19.5c-6.5,0.1-13.2,0-19.7,0.9c-3.9,2-5,7.3-7.8,10.6
          c5.7,1.6,6.1,5.8,8.7,9.8C605.2,1045.9,610.9,1046.1,614.8,1042.7z"/>
      </g>
      <g id="el-salvador" data-country="El Salvador">
        <path class="st0" d="M580.5,1026c-0.6,2.2-1.7,4.1-2.8,6c4.3,1.6,8.9,1.9,13.4,2.9c0.7-0.9,1.2-2.4,1.9-3.3
          C591.6,1027.9,584.4,1026.1,580.5,1026z"/>
      </g>
      <g id="honduras" data-country="Honduras">
        <path class="st0" d="M618.6,1020.3c-2.1-7.7-1-5.6-7.7-9c-6.4-3.8-18.1-0.7-25,0.9c-4.3,4-4.9,8.7-5.4,13.8
          c3.9,0.1,11.2,1.9,12.5,5.6c0-0.1,0.1-0.1,0.1-0.2c2.5-2.2,2.4-6.2,5.8-7.2c6.5-0.9,13.2-0.8,19.7-0.9
          C618.6,1022.3,618.7,1021.3,618.6,1020.3z"/>
      </g>
      <g id="guatemala" data-country="Guatemala">
        <path class="st0" d="M585.9,1012.2c-9.7-1.5-9.2-0.3-5.4-9c-3.1,1.3-17.9,1.6-8.9,6.6c1,0.6,0.7,6.4,0.1,7.6c-4,3.3-9.8,3.8-13.2,8
          c5.4,4.9,12.9,5.9,19.2,6.6C582.2,1026.1,579.5,1017.6,585.9,1012.2z"/>
      </g>
      <g id="mexico" data-country="Mexico">
        <path class="st0" d="M560.7,1023.2c6.2-4.3,13.3-3.3,11.5-12.9c-0.3-1.4-2.6-0.8-3-2.4c-0.5-5.4,8.2-2.7,11.4-4.9
          c0.4-2.1-0.9-4.7,1.4-6c2.3-2.3-0.6-4.1-1.6-6.5c-3.2-9.8,8.1-13.6,12.8-19.7c0.6-5.3-33.9-0.2-32,6.2c0.4,23.3-0.9,16-22,16
          c-5.1-2.7-6.7-9.3-10.3-13.4c-8.9-7-12.3-23.4-6.8-33.4c-6.4-1.1-10.2-7.8-13.6-12.5c-0.2,0-0.4-0.1-0.5-0.1c-1.3-2-3.3-4-3.5-6.6
          c-2.1-2.4-4.8-4.3-6-7.6c-1.5-2.3-3.2-4.7-3.8-7.4c-2.3-1.6-5.7-2.8-8.5-1.9c-2.7,1.9,1.5,9.3-4,8.9c-10.8-3.2-5.1-7-7.7-10.9
          c-4.6-3.1-10.5-4.4-13.9-9.1c-5.2-5.9-13.3,5.8-17.7,2.4c-7.7,0.9-17.1-1.7-22.1-8.1c-4.8-4-9.3-7.6-13.4-12.2
          c-7-1.1-14.5,0.4-21.7,0.1c0,0.1,0,0.2,0.1,0.2c-0.4,9,7.2,14.2,9.6,22.1c1.4,3.6,0.9,7.6,2.4,11.2c0.5,1.2,1.9,2,0.5,2.7
          c-2.9,0.4-7.8-1.3-6.2,3.8c2.9,5.5,10.7,7.9,14,13.3c8.2,9.5,18.8,24.7,32.6,25c-1.8-7.8-10.3-12.3-15.6-17.6
          c-2.6-11.6-11.1-20-18.2-29c-10-41.4,4.7-13.4,17.3,2.7c7,15.2,32.2,24.1,33.8,38.6c-0.6,5.8,6.3,10.5,4,16c-0.6,1.6-1,3.1,0.2,5
          c4.6,4.4,9,10.5,15.2,12.4c8.7,1.4,17.7,10.9,26.4,14.3c7,3,14.2,4,20.1,9c3.5,1.3,5.9,5.4,9.9,4.8c1.7,0.2,3.3,0.3,4.2-1.3
          c11.5-5.1,15.7,2.5,22.7,10.3C559.2,1024.5,559.9,1023.7,560.7,1023.2L560.7,1023.2z"/>
      </g>
      <g id="canada" data-country="Canada">
        <path class="st16" d="M364.3,756.9c-0.2-3.7-23.3-24.5-17.5-12.2c5.4,3.2,12.9,19,18.4,17C365.5,760.1,365.5,758.1,364.3,756.9z
          M814.1,770c-2.7-2.2-5.9-4.9-8.2-7.3c1.1-3.6,3.5-6.3-0.1-9.4c-1.2-2.6-4.4-2.3-6.7-2.1c-1,0.4-2.5,3-3.6,2.5
          c0.2-4.1,0.1-9.5-5.5-6.9c3.7-4.6,4.6-11.2,4.9-17c-3.6-2.4-4.3,4.5-5.8,6.8c-0.5,2.7-3.7,2.5-5.3,4.3c-2,4.9-5.1,9.1-7.1,13.9
          c-1.4,4.3-2.8,8.8-5.2,12.6c-1.7,1.5,1.5,1.8,2.5,2.2c4.5,1.9,8.6-0.8,12.8-1.9c15.2-2.7,1.5,9,13.9,3.6c1.2,1.2,2.5,1.4,3.8,0.2
          c2.1-1.2,2.1,4.6,3,5.6C811.4,779.6,815.6,773.8,814.1,770z M756.4,754.2c-4.3-0.8-8.2-3.3-12.5-4c-2,6.6,6.2,5.9,10.2,7.9
          c3.3,2.7,5.1-1.1,2.3-3.3C756.4,754.6,756.5,754.4,756.4,754.2z M297.4,683.3c-5.5-3.9-8.2-10.1-12.5-15c-0.7-1.3-0.8-1.9-3-0.9
          c6.5,5.5,9.3,16.4,17.1,19.7C301.9,686.2,298.7,684.1,297.4,683.3z M308.7,700.5c1.2-3.2-3.6-7.4-5.3-9.8
          C301,693.8,304.4,704.6,308.7,700.5z M315.9,723.5c-4.4-3.1-3.3-6.7-3.3-11.2c-20-6.6-5,2.1-1.6,10.8
          C317.8,735.3,322.3,729.8,315.9,723.5z M625.2,593.9c1.3,4.7,10.4,3.4,6.9-1.8c-1.1-2-2.2-3.6-3.9-5.2c-1-1.4,0.5-3.5-0.9-4.8
          c-1.6-1.9-3.4-3.7-4.6-5.9c-3.1-5.4-10.4-5.9-12.9-11.6c-1-1.6,0.2-5-2.8-3.7c-1.1,0-2,0.5-1.8,1.7c-0.3,1.7-1.7,3-2.1,4.6
          c-1.5,10.3-3.5,16.1-12.8,21.4h0.5c-3,7.8,6,5.1,10.3,6.4c3.2,7.2,3,9,9.8,3C616.1,592.9,618.3,584.9,625.2,593.9z M622.7,612
          c-0.6-1.8-2.6-2.5-4.3-2c0.2,4.3-2.6,8.5-0.9,12.8C624.1,629.4,624.3,615.6,622.7,612z M634.6,613.8c-0.3,3.8,2.9,6.8,5.5,9.2
          c0.1-2.4,1.2-0.8,1.9-2.2c0.6-1.5-1.2-2.6-1.5-4c-0.2-0.5-0.2-1.9-0.7-2.3C638.1,614.3,636.4,613.6,634.6,613.8z M643.3,605.9
          c5.1,3.8,3.3-5.7-1.2-3.9c-0.1,0.6-0.2,1.8,0.4,2C642.4,604.8,642.5,605.3,643.3,605.9z M654.1,601.6c1.5,1,2.4-0.8,1.6-2
          c-2-2.9-5.5-2.3-8.6-3.9C645,600.7,651.3,599.6,654.1,601.6z M643,686.1c4.6,3.7,4.5-4.3,4.5-7.1c-0.7-1.4-2.4-2.6-4-2.7V676
          c-1.4-0.4-2.8,3-3,3.9c-0.3,1.3,0.6,1.8,1.2,2.8C642.4,683.7,642.2,685.2,643,686.1z M567.6,448.4c-1.3,7.9-1.3,12.3,6.7,16.2
          c4.7,2.7,21.3-1.7,12.1,7.6c-3.1-0.6-6.2-2.3-9.4-0.9c-4.4,1.6-2.5,2.6-0.3,4.8c8.3,8.2,20.5,12.1,31.3,16.4
          c9.6,2.1,25.9,9.4,32.3-1.2c0.4-3.3-0.8-7.6,1.1-10.6c9.4,6.7,16.6,15.3,22.8,24.7c4.8,2.7,7.7,7.4,11,11.7
          c4.8,2.5,5.9,8.2,8.1,12.7c3.2,6.7,2.6,15.7-3.5,21.4c-1.1,2.8-6.3,2.6-6,5.5c-0.1,2.7,2.6,3.2,4,4.8c0.4,5.7-8.4,5.4-12.1,7.5
          c-3.9,0.6-7.7-1-11.6-0.5c-13.4,4.8-11.5,18,2.5,19.5c8.8,2.7,12.6-10.2,21.3-7.6c7.4,4.5,8.9,10.9,9.4,18.8
          c7.4,8.5,20.4,15.9,31.6,17c5.6-0.3,10.4,4.6,15.7,2c-2.4-1.4-6.1-3-6-6.2c-3-3.9-7.9-6.7-11.2-10.5c-1.3-0.9-9.6-4.1-5.7-6.6
          c4.3-1.6,8.4,2,10.4,5.6c3.2,4.4,10.7,4.8,12.2,10.6c1.6,0.2,7.4,1.5,6.6-1.8c-0.5-5.8,0.8-11.6,0-17.2c-0.3-1.9-1-2.8-2.5-3.9
          c-5-3.1,1.7-7-5.1-10.9c-3.1-3.4-8.7,0.2-12.4-2.5c-3.6-2.9,1.4-11.1-4.8-12.3c-3.8-1,1.4-3.8,2.1-5.5c0.1-4.5,4-9.6,7.8-4.7
          c1.9,1.8,3.6-0.9,5.5-0.6c1.3,1.6-0.1,2.6,2.6,3.8c1.6,4.6-5.1,12.2,3.1,13.7c1.2-0.2,1.7,0.5,1.9,1.7c1.1,2.5,3.6,1.2,5.8,2.2
          c0.8,0.3,1.6,0.7,2.5,0.4c-0.7-8.9,0.9-16.7,7.8-22.9c2-1.6,2.8-3.4,2.4-5.9c0.8-7.4-9.5-5.2-14.2-6.9c-6-1-2-7.4-5-11
          c-4.2-2.8-8.4-5.2-11.7-9.2c-3.2-2.3-8.4-0.7-11-3.5c-2.7-14.7,11.7-4.1,10.2-13.9c-3.6-0.1-12,2.8-10.1-3.7
          c2.3-1.8,6.3-2.2,5.5-6.1c-0.2-2-3.2-4.4-5.5-3.8c-0.1,2.1-5.1,3.1-4.1-0.1c0.9-5.1-0.9-11.3-7.6-9.8c-3.5,0.8-4,6.1-7.9,5.2
          c-0.9-2.2,0.8-3.9,2-5.3c-0.3-1,0.5-2.7-0.9-3c-2.2,0.2-3.3,0-5.2-1.4c1.6-0.4,2.6-2.2,4.5-1.9c1.1-0.2,0.8,0.1,1-0.9
          c1.2-5.2-3.9-8.7-7.8-11.2c-4.1-0.3,0.3,5.4-9.2,2.1c-3.6-17.2-8.5-9.9-20.8-15.9c-17.1-9.9-7-11.4-30.4-7.3
          c-1,5.8,6.2,7.5,9.5,10.6c1,4.9-6.2,3.3-8,1c-2.2-6.9-10.2-15.8-17.7-9.8c-13.8,3.7-18.2,6.4-10.2,20.2c4.1,7.6,2.5,11.9-4,3.5
          c-3.1-3.5-8.1-7.1-5.8-12.5c0.8-1.7,3-2.8,4-4.5c0.7-4.9,4.4-9.8,9-12c1.9-1.1-0.7-4.1-2.4-3.3c-9.1-4-19,3.7-28.1,5.5
          C572.1,438.9,569.3,444,567.6,448.4z M670.1,524.2c-1.1-2.1-0.7-6-3.9-5.8c-1.5-0.1-5.2-0.3-4.8,2.1c-0.1,5.8-7.8,8.3-8.8,13.8
          c4.1,4.6,13.9,3,18.3-0.2C671.4,530.9,670.7,527.4,670.1,524.2z M645.5,505.6c-3.8-0.3-12,7.3-3.9,6.8c5,0.3,4.4-2.8,4.2-6.5
          L645.5,505.6z M650.8,500.2c2.1-2.2,0-4.4-2.6-3.8l-0.3-0.3C645.8,499.8,646,503.4,650.8,500.2z M654.9,503.4
          c-5.9,2.8,0.6,7.8,2.7,2.4c0.4-1,1.3-2.1-0.3-2.3v-0.3C656.5,503.3,655.7,503.1,654.9,503.4z M658,513.7c-1.5,8,13-0.3,5.5-2.5
          l0.3-0.3C661.4,510.8,658.5,510.8,658,513.7z M676.5,530.5c5.8,0.6,1.5-6.2-2.4-5.7c-1.1,0.2-1.4,1.2-1.6,2.2
          C671.5,529.7,674.7,530.2,676.5,530.5z M335.9,456.1c2.7,2.8,7.8,2.2,11.1,3.9c0.1,2.5,0.3,2.5,0.4,2.6c-0.1,1.8,0,3.2,1.7,4.2
          c1,3.8,5.3,5.9,8.9,4.3c0.1-1.9,2.1-2.2,3.2-3.3c1-1,1-1.7,2.4-2c19.1,2,2.4-11.5,25-21.2c6.1-3.1,12.3-6.5,19.1-7.9
          c12-0.7,18.9-2.7,5-11.2c-2.1-3.3-7.6-7.3-11.2-3.9c-0.5,1.2,1,2.4-1,2.5c-4.7-0.8-6.2-7-10.1-9.3c-7.7,0.9-17-6.5-23.4-0.5
          c-2.4,2.4-8.1,3.4-6.9,7.7c1.3,11.5-1,10-9.2,15.7c0,2.8-1.7,5-2,7.9C346.6,451,329.4,445.9,335.9,456.1z M490.1,443.7
          c2.7,2,5.2,4,6.8,7c3.3,5.1,9.1,9.7,11.6,14.2c9.6,1,12.7-3.7,19.2-8.4c1.5-0.8,2.9-1.8,2.6-3.7c2.5-2.2,4-3.6,0.6-6.1
          c0-0.2-0.1-0.3-0.1-0.5c-2.3-2.7-6.4-5.4-7.5-8.7c4.7-5.6,9.3-10.9,4.1-18.2c-3.5-6.8-9.8,1.7-15.2,0.6c-5-0.3-10.5,0.1-13.6,4.3
          c-2.5,2.7,1.2,5.4,1.8,8c1.5,1,5.1,0.5,4.4,3.3c-0.9,1.6-0.5,5-3.3,3.9c-3.2-2.9-8.4-8.4-12.2-2.8
          C487.8,439,490.2,441.3,490.1,443.7z M514.5,517.1c9.5,13.4,29.2-25-0.1-16.1c1.3,8.7-6.7,4.7-10.1,9.1c-0.6,3.1,6.9,3.4,8,4
          C511.4,515.2,513.1,515.3,514.5,517.1z M783.7,728.4c17.3-4.5,10.6,4.7,12.3-14l0.2,0.4c3.4-7.3-7.2-9.6-11.8-12.8
          c-8.1-0.9-15.2,7.3-23.7,4.2c-2.3-2.1,1.5-3,3.2-2c1.8,0.1,2.2-0.2,3.8-1.3c5.4-3.5,11.8-4.1,17.8-5c3.2-8.5-11.6-7.6-16.6-10.4
          c-5.8-3.3-10.8-7.8-16.3-11.5c0.4-3,5.5-1.5,7.8-2.1c-2.7-29.2-20.7-27-23.3-41.9c-15.3,0.4-5.2,13.9-10.5,22.4
          c-3.4,3.6-10.8,6.2-14.9,2.3c-0.8-5.1-1.8-10-4.3-14.8c0.2-2.5,4.4-4.1,3.1-7.1c-0.8-1-1.3-1.9-1-3.2c8.2-10.4-13.6,0.3-17.4-16
          c-4.6-11.2-16.1-4.7-24.5-4.4c-2.8-2.5-7.1-4.6-11-3.1c-1.2,1.9-6,1.4-6.5,4.2c-0.1,2.4,3.5,3.9,3.2,6.3c-1,2.8-5.2,4.4-3.9,7.9
          c1.6,1.1,2.2,3.5,3.8,4.9c0.8,1.1,2,1.2,2.3,2.7c0,5.3,3.5,12.2-4.4,12.8c-8,2.7-0.9,1.4-0.5,5.2c5.9,7.4,11,15.7,13.2,25.4
          c1.4,2.1-0.1,3.5-1.9,4.5c-7.6,9-6.3,8.7-18,13.4c3.1,3.1,2.2,8,4.7,11.6c2.8,5.3,3.4,11.3,4.6,17.1c-0.7,14.2-30.7-2.7-15.1-6.8
          c0.8-0.2,2.1-0.2,2-1.3c-2.4-2.5-6.8-2-9.5-4.6c-1.4-1.6,0-4.5-2.4-5.8c-2.7-2.6-0.9-7.2-1.6-10.6c-0.4-10.3-13.7-5-20.1-8.6
          c-5.6-3.5-12-5.3-18-7.9c-5.2-3.9-10.8-8.2-16.8-10.3c0,4-8.4,10.3-9.9,4.2c-0.1,0,3.5-1.1,3.2,0.4c1-2.4,4.4-4,4.3-6.9
          c-13.9-9.9-2.7-19-22-18.2c-2.3-2,0.9-6.2,0.2-9c-1.1-9.8,2-19.1,8.6-26.3c4.6-5.8,12.7-8.1,18-13.3c-1.2-5.6-8.2-6.4-12.9-7.1
          c-1.4-0.7-0.5-2.2,0-3.3c4.4-4.7,12.4,6.8,16.1,0.4c1.4-3.7,5.6-3.3,8.8-3.9c5-3.1,12.1-11,8.8-17c-4.3-0.2-11.2,0.8-13.4-3.9
          c3.5-4.9,9.1,0.1,13.5-0.2c1.7-3.3,11-5.1,7.8-9.9c-4.4-1.7-6.4-5.7,0.1-5c1.4,2.7,5.6,3.1,8.2,4.7c1.7-0.2,2.8-2.2,4.5-2.5
          c0.8-1.4-0.7-4.2,0.9-5.3c2.5-1.3,3.7,2.7,5.1,4.2c7.1-10.1,15.7-3.3,10.9-18.4c-1.8-2.7-10.4-6.6-7.6-10.1
          c0.7-0.8,1.3-1.6,1.8-2.5c1.4-1,4.9,1.5,5.2-0.7c-0.2-2.5,1.6-3.9,2.2-6.2c-3.1-3.1-5.2-7.7-9-10.4c-4.9-1.9-8.8-7.4-14.5-6.3
          c-3.9-0.4-5.2,5.7-5,8.8c-0.1,4.1,8.3,1.9,5.6,7.7c-11.5,4.6-1.5,9.4-5.3,13c-7.5,4.3,0.6,12.9-11.9,13.1c-2.3-3.3-5.1-6.5-7-10.2
          c-1-6.9,5.8-25.1-7.2-22.8c-3.4,2-1.1,19.4-6,14.8c3.3-17-9.5-14.2-9.4-23.1c5.4-7.9,9-13.2-3.5-15.7c-13.2-5.6,1-19.7,3.6-27.9
          c-3.1-17.8,0-15.2,8.1-30.3l-23.8,3.9c-5.3,10.1,1.5,23-5.6,32.4c-6.5,7-1.5,15.5-3,23.6c1,5.8,10.7,20,17.6,18.6
          c-0.7,0.5-1.5-0.2-2,0.5c-0.2,2-2.4,4,0.4,5.3c0.5,0.5,0.3,0.8,0.1,1.4c-0.8,2.2-0.7,4.7-2,6.7c-7.9,1.6-8.1,16.8-12.8,17.3
          c-4-1.4-1.4-6.5-3.8-8.4c-6.5-1.4-3,4.7-6.4,5.9c-1.9,0.4-3.4-2.1-5-1.9c-6.5,0.9-13.4,1.8-20,1.5c-2.7-0.1-4.6-1.4-6.2-3.3
          c-4.3-2.7-11.5,1.4-14.8-3.4c-1.6-3.8-3.8-8.7-8.8-7.4c-1.3,4.5-10.5,9.3-9.5,11.2c0,2.6,1.7,3.8,3.1,5.5
          c0.9,4.5,2.2,12.2-4.6,11.7c-2.5-0.2-2.5-4-0.9-5.3c2.3-1.3,2-3.5,0-4.9c-3-2.6-6.9-4.2-9.2-7.6c-0.5-0.6-0.8-0.4-1.5-0.4
          c-3.9,1.4-6.8,5.4-12,4.5c-5.7-0.3-30-0.2-17.1-9.2c-0.3-1.7-0.8-3.3-1.6-4.8c7.1,2.7,16.6,2.5,22.6-2c0.4-1.3-0.3-1.8,0.6-2.4
          c1.7-0.7,5.5-0.5,5.3-3.1c11-11.3,17.4-10.6,27.8,0.9c1.7,1.8,4.9,6.6,8.1,5.8c0.5-1.9-1-3.9,1.5-4.3c5.5-1.2,12.1-9.6,2.8-10.8
          c-4.7-3-4.1-6.5,1.8-5.4c4.5,1.4,0,6.8,9.4,4.8c0.1-7.4-1.2-16.5-7.7-20.9c-3.3-1.5-7-1.4-9.7-3.9c-9.9-9.2-11.6-26.4-5.7-38.1
          c3.5-5.3-1.2-11.1-5.6-13.5c-4.1-1.6-9.4-1-12.4,2.4c-1.3,1.3-4.2,3-2.8,4.8c1.6,2,10.5,3.4,8.6,7.2c-2.5,4.2-9.7,0.8-11.6,5.4
          c0.2,4,3.7,13.4-2.8,14c0.1,0-1.4-0.4-1.2-0.4c-3.2-1.5,0.9-14.3-5.2-10.5c-4.8,2.7-10.9-4.8-15.6-0.4c-1.4,2.3-4.1,2.4-6.5,1.9
          c-0.2-2.4,0.1-5,1.4-7c0.6-6.5-19.9,6.9-24.3,6.7c-7.3,2.7-13.8,7.8-19.2,12.7c3.8,2,8.9,2.3,11.6,6.2c-5-0.5-9.3,4.4-8.1,9.3
          c6.2,7.1,24-0.9,21.2,7.3c-1.1,5.5-20.9,5.7-15.4,11.3c2.3,1.8,2.1,5.4,4.7,6.9c3.3,1.8,15.8-1.4,14.3,4.4c0.4,2-0.3,3.5-1.8,4.7
          c-0.1,0-0.3,0-0.5,0c-9.3-5.4-21-6.7-28.1-15.3c-3.1,0.3-9.8-1.7-9.9,2.5c-0.2,1.6-5.1,6.1-5.7,3.6c-1.7-3.1-1.9-6.2-1-9.9
          c-1.3-1.4-4.9-0.2-5,1.8c0.5,3.2-1.5,6.3-3.8,8.5c-3.3,4-10.9-7.8-11.8-11.2c-0.5-2.6,1.9-6.9-2-7.6c-4,3.1-2.2,9.8-6.8,12.6
          c-6.4,2.5-10.9,11.8-18.2,11.1c2.7-8.2,12.6-11.8,19-17c1.9-1.9-2.4-1.9-3.3-1.9c-11.5-0.7-17.1,14.5-29.1,13.4
          c-2-0.3-1.7,1.4-2.3,2.8c-5.6,3,3.6,6.8-2.5,8.8c-1.4,0.2-2.6-1.3-3.5-2.2c-4.8-6.9-9,3.4-14.7-1.8c-2.4-1.3-3.7-3.7-6-5.1
          c-2.2-2.1-6.1-3.4-6.2-6.9c-3.5-0.3-7.4,1-10.8,0.4v141.2c5.3,0.8,7.2,5.6,10.4,9.2c6.5,4.3,13.3,8.3,19.6,12.7
          c24.1,19.4,42.6,45.1,60.8,69.6c1,8.3,13.7,6,16.6,13.4c0.6,7.4,9.6,10.3,10,17.7H533c-1-4.5,0-5.8,4.3-3.9c0,1,0.3,0.8,1,1.3
          c1.3,1.4,2.4,2.8,3.6,4.2c2.5,0.5,3.6,2.6,5.6,3.7c4.1,1,8.3-0.1,12.2,1.9c3.8,1.5,8.2,0.2,11.7,2.2c3.6-2.6,5.2-6.7,7.4-10
          c3-0.5,7.9-1.4,10.4,0.6c4.3,2.4,7.1,6.8,11.2,9c-0.6,8.8,0.6,9.2,7.1,14.9c5.1,0.8,10.5-0.2,15.4,2.4c4.4,1.4,8.7,4.2,8.9,9.1
          c2.4,6.4-7,10.5-9.5,3.7c-4.4-1.2-0.9,11.1-3.8,13.2c0.3,3.8-3.3,6.4-3.1,10.2c5.6,3.4,4.1-8.7,22.1-5.8c2.5-0.8,4.9-1.7,7.3-2.7
          c-1.4,0.1-2.5-0.6-3.6-1.3c-2.1-1-5.3,1-7-0.8c-1.9-6.5,5.7-4.7,9.8-4.8c4.8-1.1,9.2-3.9,14.2-4.4c1.3-8.5,11.4-4.7,17-5.8
          c4.5-1.4,8.4-3.4,12.8-5.1c1.9-0.1,2-1.3,2.2-3c3.4-4,3.6-10.8,9.3-13.1c3.5-0.4,4-4.8,7.8-5.1c3.2-0.4,10.8-5.6,11.4-0.3
          c1.7,6.1,1,12.7,1.1,19.1c7-2.2,11.5-7.9,17.9-11c2.7,6.9-2.4,10.1-8.7,10.5c-3.4,2.6-3.9,7.7-0.5,10.6c8.6-0.3,14.5-4.7,19.8-10.9
          c4.9-3.6,20.9-7.1,12.2-15c-2.4-1-4.2,1.7-6.2,2.8c-5.7,0.3-12.2-1.8-17.3-4.2c-3.4-2.7-0.2-7.8-3.7-10.2c-1.1-3.8,4.2-5,4.3-8
          c-11.1-7.6-28.7,9.3-38.1,14.5c3.1-10.5,15.3-15.3,22.7-22.5c11-13.4,30.4-6.4,45.4-8.6C772.3,734.2,778.1,731.3,783.7,728.4z
          M395.8,575.1c-1.5,0.7-4.1,4-5.5,2.9c0.2-4.1,6-7.1,4.7-11.6c-4.7,0.8-5.8,5-7.8,8.5c-5.5,6.4-15-4.2-6.1-7.5
          c6-1.6-4.1-6.9,5.9-6.2c0.8-0.1,2.8-0.3,3.4-0.9c0.5-1.3,0.5-3.6-1-4.2c-3.2-0.5-4.7,3-7.7,3c-4.4-0.5-20.4,4.9-15.5-4.1
          c6.5-2.9,14.1-4.6,21.2-6.1c6.1-0.6,9.5-7.3,16-6.1c1.1,0,3.2-0.3,2.8,1.4c-2.2,5.7-8.1,4.7-8.2,7.7c-0.6,4.3,5.8,3.4,8.5,3.4
          c4.8-4.9,12.3-9.6,7.5,1.7c-1.9,1.7-2.5,4.2-2.6,6.6c-1.4,2.6-3.4,3.7-4.6,0.3c-1.5-1.2-5.1-0.7-6.1,0.9
          C403.1,572.2,400.5,568.5,395.8,575.1z M438.4,617.1c-3,5.5-9.1,7.8-14.8,9.5c-8.2,4.7-11,3.7-16.9-3.2c-2.6-1.1-11.3,1.5-8.4-4
          c2.7-5.7,9.1,0.8,12.8,2.2c2.6-2.6,9-4.9,6.6-9.5c-0.7-3.6-3.5-5.4-6.5-7.2c-2.8-5.3,0.6-10.5,5.6-4.9c4.3,7.3,2.8,2.2,6.8,1.3
          c0.3,17.9,19.3-5.2,27.6-3.1c3,1.2,4.9,4.7,8.5,4.4C455.4,610.4,442.9,608.9,438.4,617.1z M474.1,651.2c-5.6,3.5-13.3-0.2-19.5,0.8
          c-0.5,2.8-11.3,7.8-9.9,8.8c-8.1-1.4-0.4-7.6,2.8-10.3c3.9-3.1,2-6.4,7.4-8.4c1.4,0,3.8-0.6,3.5,1.6c-0.7,2.6,1.1,3.3,3.4,3.2
          C465.7,647.5,472.2,647.8,474.1,651.2z M531,742c-1,2.1-2.7,3.6-3.1,5.8c-0.6,0.4-2,0-2.6,0.2c-2.8,1.3-1.9-2-3.1-3.3
          c-0.3-2.6,1-5,1.2-7.6c1.1-4.7-6.1-3.5-9-12.6c-5.7-6.6-7.4,2.4-7.4,7.2c3.3,4.5,6.6,5.3,7.5,11.8c1.4,1.6,3.8,3.6,3.2,6
          c-0.1,0-0.2,0-1.6,0.1c-3.3,3.6-4.6-10.7-6.5-11.9c-2.8-2.5-6.5-4.7-6.4-9.1c0.2-4.2-4.4-3.8-5-7.3c-1.2-8.4,8.7-2.4,12.6-5.6
          c1.7-2.8,3.3-7.1,7.2-6.9c1.8-0.5,3.1,0.4,3.1,2.3c1.5,3.1-0.6,7.1,1.9,9.9c0.8,1.2,0.4,1.7,0.8,3.1c0.9,1.6,1.8,2.9,1.3,4.8
          c0.2,1.8,1.6,3.2,1.7,5.1C528,736.8,532.5,738,531,742z"/>
      </g>
      <g id="united-states" data-country="United States">
        <path class="st17" d="M180.1,659.7c-0.9,1.4-1.4,2.7-2.3,4.1c-0.3,1.4-0.2,3-2,3.6c-3,1.9-5.9,4-9.2,5.5c-1.3,1.1-0.3,3.7-0.9,5.2
          c5.2-0.6,14.7-3.1,15.5-8.9c-1.1-0.6-2.8-1.1-2.8-2.7c-0.2-2.8,3.3-2.9,4.3-4.6C182.8,660,182.9,660.1,180.1,659.7z M83.2,709
          c-0.3-0.1-0.5-0.3-1-0.8c0,0.1,0.1,0.1,0.1,0.2c-4,4-11.2,5.3-13.3,11c2.6,1.9,7.4-3.2,9.1-5.2c2.2-0.9,6.3-1.9,5.8-5
          C83.7,709.1,83.4,709.1,83.2,709z M92.5,643.7c3.4,0.1,6.7,2.5,10,1.9c1.5-1.2,1.1-3.1-0.6-3.8C99.4,639.4,82.1,640.7,92.5,643.7z
          M255.6,497.6c-4-2-7.4-5.2-11.3-7.1c-1.9,0.5-5.5-0.2-6.9,1.5c-0.6,0.8-2.8,2.9-3.7,2.3c-0.2-5.6-7.3-3.5-11-4.8
          c-1.5-1.4-2.2-3.7-4.8-3.1c-4.8,1.1-7.9-2.7-12.3-2.8c-0.9,0.1-1.3,0.6-1.5,1.3c-3.1,0.5-6.5-1-9.7-1.4c-1.9,0.2-2.9-1.4-4.1-2.6
          c-2.4-2-5.9-5.2-9.1-3.7c-3-1.1-6.1-2-9.3-2.5c-0.3-1-2.2-1.2-2.9-0.5c-0.8-0.1-1.6-0.1-2.4-0.2c3.2-2.3,0.5-4.7-2.6-4.2
          c-1.9,0.6-3.2,3.2-4.9,4.2c-4,0.5-8.9-0.4-11.5,3.3c-3,2-5.5,5.2-8.3,7.5c-3.4-1.6-5.9,3.4-9,4.2c-3.9-0.6-5.1,2.2-1.1,3.4
          c-0.6,0.4-1.2,0.9-1.8,1.3c-0.5-0.1-1.1-0.1-1.7,0c0.1,0.3,0.2,0.8,0.2,1.1c-7.1,5.7-11.4,16.3-22.2,15.2c-3.1,1-9.7,6.9-5,9.5
          c3.7,0.8,6.2,3.5,8.3,6.4c25.8,16.6,4.9,13.9,21.2,18.7c16.2,3,14.7,5.6,0.9,12c-28.3,6.1-3.5-19.1-30.3-4.8
          c-12,15.4-12,4.3-0.1,16.7c1.9,1.4-0.3,4,0.3,5.9c5.8,6.7,16.1,6.6,24.2,6.3c4.3,1.2,4.6-4.7,8.7-4.4c0.7,3.5-1.7,7.2,0.4,10.3
          c2.5,1.9,2.5,5.2-0.9,5.9c-10.8,0.6-20.1,4.6-27.9,12c-4.8,3.1-2.9,8.5-1.4,12.7c-0.6,7.4,12.4,3.7,11.4,11c-1.8,1.5-6.3,2.7-4,5.9
          c4.3,5.1,10.5,8.1,13.9,0.4c0.7-1.7,0.1-3.2-0.2-4.8c-0.4-2.2,3.5-3.6,4.9-1.8c7.2,7.2-0.7,5-2.6,10.3c-1.3,8.2,0.7,16.8,10.8,12.4
          c5.1-2.6,4,6.1,18.4,0.4c4.3,7.8-3.8,10.6-3.4,19c-3.3,5.1-10.9,3.9-15.9,6.6c-15.7,7.9-33.1,13.3-46.8,24.7
          c16.3,4.6,50.4-16.8,64.7-26.7c7.8-9.3,12.7-20.3,23-27.2c-3.5-6,0.8-9.4,5.6-13c4.6-3.9,7.4-9.3,11.1-14.1
          c3.3-3.8,7.9-6.1,9.1,0.5c-1.1,5.7-5.1,8.5-10.6,8.3c-1.6,5.1-2.4,10.6-2.5,15.9c28.5-5.6,14.1-14.1,22.2-25.1
          c8,3.5,13.5,10.1,21.4,13.2c8.5,0.5,15.1,5.4,23.5,6C255.6,638.2,257.1,498,255.6,497.6z M719.5,775c-1-9.4-6.1-4-12.2-2.8
          c-3.4,0.2-4.1,3.6-6.7,4.9c-2.1,0.1-3.2,1.7-4.6,3c-3.5,2.6-2.6,7.6-5.8,10.3c-0.2,1.5-0.3,3.1-2.2,3c-4.4,1.7-8.2,3.7-12.8,5.1
          c-5.6,1.1-15.7-2.7-17,5.8c0.3,0,0.7,0,1,0.1c0.3,2.3,1.3,5-1.1,6.5c-1.1,1.3-2,3.9-3.9,4c-5.8,0.5-11.4,0.9-16.7,3.4
          c1.1,6.9-21.8,15-27.9,13.4c-0.4-4,0-9.8,5.5-7.9c0.1,0,0.2,0.1,0.3,0.1c-0.3-3.7,3.5-6.5,3.1-10.2c-7,6.7-2-6.4-7.9-5.3
          c-10.8,4.6-1.5-6.1-2.5-10.7c-2.4-2.9-8.1-3.4-11.4-1.8c-2.8,3.8-6.2,7.1-9.3,10.5c-1.3,4,2.4,6.6,3.9,9.8
          c3.1,18.5-12.9,16.1-12.7,0.2c-0.1-6.3,4.8-10.9,3.4-17.5c-1.7-5.7,4-5.8,7-8.6c3.3-1.6,12,1.7,12.7-3.3c-1.2-0.6-3-1-3.7-2.5
          c-4.2-5.1-10.5,3.3-15.4,0.5c-6.7-8.3-10-2-17.3-0.1c-1.3,0.3-1.4-1.3-2.3-1.6c-3.2-0.9-5.2,1.9-8.3,2.1c-0.8-1.3,0.2-2.5-0.4-1.5
          c-1.9-2,14.5-9.6,16.7-13.2c-7.7-2.1-15.9-3.1-23.9-4.1c-2-1.2-3.1-3.1-5.6-3.7c-1.1-1.4-2.4-2.8-3.6-4.2c-0.8-0.5-1-0.3-1-1.3
          c-4.4-2-5.2-0.5-4.3,3.9h-159c1.3,10.2-12.5-4.1-11.6,5.6c11.5,19.1-6.6,32.6,0.3,50.1c-1,1.5-1.9,3.1-2.8,4.8
          c4.3,9.3-5.7,10.8-1.8,17.2c1.9,2.9,0.1,7,4.4,8.4c2.2,6.4-1.3,5.6,6.2,9.5c2.3,4.9-4.4,13.3,3.8,14.5c1.5,0.3,6.8,1,6.1,3.2
          c3.5,1.1,6.6,2.2,7.3,6.1c2.3,0,4.6,0,6.9,0c0.6,0.6,1,0.6,1.5,0.7c3.8-1.1,7.9-0.1,11.9-0.4c2.4-0.1,3.3,3.3,5.6,4.1
          c1.2,0.5,1.7,1.9,2.2,2.9c1.3,1,2.9,1.7,4.1,3c4.2,3.7,7.9,8.4,13.9,9.2c3.5,1.9,7.5,1.8,11.2,1.4c1.7,1.5,4.5,0.4,6.1-0.8
          c2.6-2.4,5.7-3.3,9.1-3c2.4-0.2,3.1,3,5.1,3.9c3.1,3.2,7.8,3.9,11.3,6.6c2.5,4.5-2.9,7.7,7.7,10.9c4.8,0.4,2.2-5.5,3.3-8.1
          c1.5-2.6,5.6-0.2,7.8,0.1c2.2,0.7,1.6,3.2,2.9,4.7c2.3,4.2,4.8,8.1,8.3,11.3c0.2,2.5,2.3,4.6,3.5,6.6c0.2,0,0.4,0.1,0.5,0.1
          c3.4,4.8,7.2,11.3,13.6,12.5c9.3-15.7-13.8-7.6,15.1-29.7c4.5-2.4,6.7-7.7,12.6-6.2c10.9,0.7,23.8,6,34,2.1c-1-2.1-4.4-2.4-5.7-4.3
          c-2.8-6.6,6.6-9.8,11.5-9.1c6.1-1.3,4.6,6.4,7.4,9.7c18.1-5.7,11.1,0.6,13.6,15.6c1,3.6,4.6,5.4,5.7,8.9c2,4.1,3.8,9.1,7.2,12
          c11.1,2.9,2.2-11.8,3.5-17.2c2-14.7,6.6-13.3-0.5-29.4c5.4-1.6,9.1-5.9,12.4-10.3c3.4-2.9,7.4-4.7,10.1-8.3
          c4.1-4.3,10.5-5.2,14-9.8c3.8-2.4,10.4-4.7,7.7-10.4c-0.3-1.2-0.2-0.5-0.9-1.2c-5.4-4.9-1.5-6.1-4.1-10.2c0.5-1.7,1.2-3.4,2.3-4.7
          c3.5,4.2,4.3-1,3.6-4.1c1.2-1.3,2.3-2.5,3.5-3.9c2,1.2,5-0.9,6.2-2.3c1-2,0.2-4.2,1.6-6.2c4.4-0.6,9.6-1.2,12.9-4.3
          c4.4-1.1,8.5-2.7,13-3.2c-4.7-7.6-4.3-8.8-3.5-17.6c0,0.1-1.3-0.3-2.4-0.6c6-3,10.9-7.8,17-10.7C719.6,785.7,720.2,780.3,719.5,775
          z"/>
      </g>
      <image style="overflow:visible;" width="858" height="835" xlink:href="../Desktop/Captura de Pantalla 2024-07-08 a la(s) 5.45.44 p. m..png"  transform="matrix(0.2606 0 0 0.2715 96.2108 1998.2255)">
      </image>
    </svg>
  ';
}
