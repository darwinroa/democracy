<?php

/**
 * Esta función imprime un botón con el ícono de más, 
 * que se puede usar para ver más información de de un post
 * o incluso también para usarlo en un pop up.
 * Para un pop up se gira el ícono 45° y queda como una X
 */
function dc_icon_plus()
{
  $iconPlus = get_stylesheet_directory_uri() . '/inc/img/plus-circle.svg';
  return "<img src='$iconPlus' alt='icon plus'>";
}
