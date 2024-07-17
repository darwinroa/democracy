<?php
add_shortcode('button_play_video', 'button_play_video_function');
function button_play_video_function()
{
  // Script para el video pop up
  wp_enqueue_script('dc-popup-video-script', get_stylesheet_directory_uri() . '/inc/scripts/dc_popup_video.js', array('jquery'), null, true);
  wp_localize_script('dc-popup-video-script', 'popup_wp_ajax', array(
    'popup_ajax_url'      => admin_url('admin-ajax.php'),
    'nonce'               => wp_create_nonce('nonce'),
  ));

  $isVideo = get_field('is_video');
  if ($isVideo) {
    $post_id = get_the_ID();
    $svgFile = get_stylesheet_directory_uri() . '/inc/img/play-button.svg';
    $iconPlay = file_get_contents($svgFile);
    ob_start();
    $html = "<div data-id='$post_id' class='dc_play_button dc_video_pop_up'>$iconPlay</div>";
    $html .= ob_get_clean();
    return $html;
  }
}

add_shortcode('html_popup', 'dc_html_popup');
