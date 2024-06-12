<?php
function dc_html_loadmore_button($button_ID)
{
  $html = "<div class='dc__content-button-loadmore'>
            <button type='button' id='$button_ID' class='dc__button-loadmore'>Load More</button>
          </div>";
  return $html;
}
