<?php
if (!function_exists('dc_team_function')) {
  echo is_page('dc_team');
  add_shortcode('dc_team', 'dc_team_function');
  function dc_team_function()
  {
    wp_enqueue_style('dc-team-style', get_stylesheet_directory_uri() . '/shortcodes/team/dc_team.css', array(), '1.0');
    wp_enqueue_script('dc-team-script', get_stylesheet_directory_uri() . '/shortcodes/team/dc_team.js', array('jquery'), null, true);
    wp_localize_script('dc-team-script', 'wp_ajax', array(
      'ajax_url'            => admin_url('admin-ajax.php'),
      'nonce'               => wp_create_nonce('load_more_nonce'),
    ));

    $iconPlus = dc_icon_plus();
    $html = "";
    $html .= "<div id='overlay'></div>";
    $html .= "<div id='teamPopup'><div id='closePopup'>$iconPlus</div><div id='teamPopupContent'>hola mundo</div></div>";
    return $html;
  }
}

if (!function_exists('dc_plus_btn_popup_function')) {
  add_shortcode('dc_plus_btn_popup', 'dc_plus_btn_popup_function');
  function dc_plus_btn_popup_function()
  {
    $currentId = get_the_ID();
    $iconPlus = dc_icon_plus();
    return "<div data-id='$currentId' class='dc__btn-plus dc_team-popup'>$iconPlus</div>";
  }
}

/**
 * Funcion para mostrar el pop up con información de cada miembro del equipo
 */
function dc_html_popup_team() //Esta función contiene el HTML para el pop up
{
  $iconPlus = dc_icon_plus();
  $html = "";
  $html .= "<div id='overlay'></div>";
  $html .= "<div id='teamPopup'><div id='closePopup'>$iconPlus</div><div id='teamPopupContent'></div></div>";
  return $html;
}

if (!function_exists('dc_ajax_team_popup')) {
  add_action('wp_ajax_nopriv_dc_ajax_team_popup', 'dc_ajax_team_popup');
  add_action('wp_ajax_dc_ajax_team_popup', 'dc_ajax_team_popup');

  function dc_ajax_team_popup()
  {
    $dataId = $_POST['dataId'];
    $profile = get_the_post_thumbnail($dataId, 'medium');
    $memberType = "";
    $name = get_the_title($dataId);
    $position = get_field('cargo', $dataId);
    $description = apply_filters('the_content', get_the_content(null, false, $dataId));
    $links = "";
    ob_start();
    $html = "
      <div class='popup__header'>
        $profile
        <div class='header__content'>
          <h3 class='member__type'>$memberType</h3>
          <h3 class='member__name'>$name</h3>
          <div class='member__divider'></div>
          <span class='member__position'>$position</span>
        </div>
      </div>
      <div class='popup__body'>
          $description
      </div>
      <div class='popup__footer'></div>";
    $html .= ob_get_clean();
    wp_send_json_success($html);
    wp_die();
  }
}
