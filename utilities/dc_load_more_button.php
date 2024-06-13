<?php
function dc_html_loadmore_button($button_ID)
{
  $html = "<div class='dc__content-button-loadmore'>
            <button type='button' id='dc__button-$button_ID' class='dc__button-loadmore'>Load More</button>
          </div>";
  return $html;
}

function dc_show_loadmore_button($total_post, $post_per_page, $page)
{
  $paged = $total_post / $post_per_page;
  return $page < $paged;
}
