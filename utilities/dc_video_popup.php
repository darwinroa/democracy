<?php
if (!function_exists('dc_ajax_popup')) {
  add_action('wp_ajax_nopriv_dc_ajax_popup', 'dc_ajax_popup');
  add_action('wp_ajax_dc_ajax_popup', 'dc_ajax_popup');

  function dc_ajax_popup() // Aquí se crea el HTML del video para el post que se le da clic.
  {
    $videoId = $_POST['videoId'];
    ob_start();
    $html = "";
    $videoType = get_field('material_de_video', $videoId)['tipo_de_video']; //Retorna true si el video viene de una url externa y false si el video esta cargado en wordpress
    if ($videoType) {
      $html .= get_field('material_de_video', $videoId)['video_url'];
    } else {
      $urlVideo = get_field('material_de_video', $videoId)['archivo_video'];
      $html .= "<video width='640' height='360' controls class='dc_video-wp'>
                  <source src='$urlVideo' type='video/webm'>
                  Tu navegador no soporta el elemento de video.
                </video>";
      $html .= ob_get_clean();
    }
    wp_send_json_success($html);
    wp_die();
  }
}

/**
 * Funcion para mostrar el video en un pop up
 */
function dc_html_popup() //Esta función contiene el HTML para el pop up
{
  $iconPlus = dc_icon_plus();
  $html = "";
  $html .= "<div id='overlay'></div>";
  $html .= "<div id='videoPopup'><div id='closePopup'>$iconPlus</div><div id='videoPopupContent'></div></div>";
  return $html;
}
