<?php
add_shortcode('button_play_video', 'button_play_video_function');
function button_play_video_function()
{
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
